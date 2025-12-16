<?php
require_once 'Database.php';

class GameEngine {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function processQueue(int $userId): array {
        $messages = [];
        
        $passiveMsg = $this->calculatePassiveScience($userId);
        if ($passiveMsg) $messages[] = $passiveMsg;

        $sql = "SELECT * FROM event_queue WHERE user_id = :uid AND end_time <= NOW() AND is_processed = 0 ORDER BY end_time ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        $events = $stmt->fetchAll();

        foreach ($events as $event) {
            $neueNachricht = $this->handleEvent($event);
            if ($neueNachricht) $messages[] = $neueNachricht;
            $this->markAsProcessed($event['id']);
        }
        
        $this->updateLastActive($userId);
        return $messages;
    }

    private function calculatePassiveScience(int $userId): ?string {
        $stmt = $this->db->prepare("SELECT last_active FROM users WHERE id = :uid");
        $stmt->execute([':uid' => $userId]);
        $lastActiveStr = $stmt->fetchColumn();
        if (!$lastActiveStr) return null;

        $lastActive = new DateTime($lastActiveStr);
        $now = new DateTime();
        $diffSeconds = $now->getTimestamp() - $lastActive->getTimestamp();
        if ($diffSeconds < 10) return null;

        $stmt = $this->db->prepare("SELECT current_level FROM user_buildings WHERE user_id = :uid AND building_type_id = 2");
        $stmt->execute([':uid' => $userId]);
        $labLevel = (int)$stmt->fetchColumn(); 

        $stmt = $this->db->prepare("SELECT SUM(skill_value) FROM specialists WHERE user_id = :uid AND type = 'Scientist'");
        $stmt->execute([':uid' => $userId]);
        $scientistBonus = (int)$stmt->fetchColumn();

        $spPerHour = ($labLevel * 10) + $scientistBonus;
        if ($spPerHour <= 0) return null;

        $hoursPassed = $diffSeconds / 3600;
        $earnedSP = floor($hoursPassed * $spPerHour);

        if ($earnedSP > 0) {
            $stmt = $this->db->prepare("UPDATE user_resources SET science_points = science_points + :sp WHERE user_id = :uid");
            $stmt->execute([':sp' => $earnedSP, ':uid' => $userId]);
            return "🧪 In deiner Abwesenheit generiert: +$earnedSP SP ($spPerHour SP/h)";
        }
        return null;
    }

    private function updateLastActive(int $userId) {
        $stmt = $this->db->prepare("UPDATE users SET last_active = NOW() WHERE id = :uid");
        $stmt->execute([':uid' => $userId]);
    }

    public function getActiveEvents(int $userId): array {
        // Wir müssen hier jetzt auch Countries joinen für die Anzeige!
        $sql = "SELECT 
                    eq.*,
                    TIMESTAMPDIFF(SECOND, NOW(), eq.end_time) as seconds_remaining,
                    uf.name as rocket_name,
                    mt.name as mission_name,
                    mt.reward_money,
                    bt.name as building_name,
                    c.name as country_name -- NEU
                FROM event_queue eq
                LEFT JOIN user_fleet uf ON eq.reference_id = uf.id AND eq.event_type = 'MISSION_RETURN'
                LEFT JOIN mission_types mt ON uf.current_mission_id = mt.id
                LEFT JOIN user_buildings ub ON eq.reference_id = ub.id AND eq.event_type = 'BUILDING_UPGRADE'
                LEFT JOIN building_types bt ON ub.building_type_id = bt.id
                LEFT JOIN countries c ON eq.reference_id = c.id AND eq.event_type = 'BUDGET_NEGOTIATION' -- NEU
                WHERE eq.user_id = :uid AND eq.is_processed = 0 AND eq.end_time > NOW()
                ORDER BY eq.end_time ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll();
    }

    private function handleEvent(array $event): ?string {
        switch ($event['event_type']) {
            case 'MISSION_RETURN': return $this->completeMission($event);
            case 'BUILDING_UPGRADE': return $this->completeBuildingUpgrade($event);
            case 'BUDGET_NEGOTIATION': return $this->completeNegotiation($event); // NEU
            default: return "Unbekanntes Event verarbeitet.";
        }
    }

    /**
     * NEU: Abschluss einer Verhandlung
     */
    private function completeNegotiation(array $event): string {
        $countryId = $event['reference_id'];
        
        // Land Infos holen
        $stmt = $this->db->prepare("SELECT * FROM countries WHERE id = :cid");
        $stmt->execute([':cid' => $countryId]);
        $country = $stmt->fetch();
        
        // Zufallsgenerator für Erfolg (vereinfacht)
        // In einem echten Spiel würde hier der Skill des Mitarbeiters einfließen
        $baseAmount = 5000000; // 5 Mio Basis
        $variance = rand(-1000000, 3000000); // Zufallsschwankung
        $finalAmount = $baseAmount + $variance;

        // Geld gutschreiben
        $stmt = $this->db->prepare("UPDATE user_resources SET money = money + :m WHERE user_id = :uid");
        $stmt->execute([':m' => $finalAmount, ':uid' => $event['user_id']]);

        return "💼 Verhandlung in {$country['name']} erfolgreich! Fördermittel erhalten: " . number_format($finalAmount, 0, ',', '.') . " €";
    }

    private function completeMission(array $event): string {
        $rocketId = $event['reference_id'];
        $sql = "SELECT uf.*, mt.name as mission_name, mt.reward_money, mt.reward_science FROM user_fleet uf JOIN mission_types mt ON uf.current_mission_id = mt.id WHERE uf.id = :rid";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':rid' => $rocketId]);
        $data = $stmt->fetch();

        $gewinn = $data ? $data['reward_money'] : 0;
        $science = $data ? $data['reward_science'] : 0;
        $missionName = $data ? $data['mission_name'] : "Unbekannte Mission";

        $stmt = $this->db->prepare("UPDATE user_resources SET money = money + :money, science_points = science_points + :science WHERE user_id = :uid");
        $stmt->execute([':money' => $gewinn, ':science' => $science, ':uid' => $event['user_id']]);

        $stmt = $this->db->prepare("UPDATE user_fleet SET status = 'idle', current_mission_id = NULL, flights_completed = flights_completed + 1 WHERE id = :rid");
        $stmt->execute([':rid' => $rocketId]);

        return "🚀 Mission '$missionName' erfolgreich! +".number_format($gewinn/1000000,1)."M € & +$science SP";
    }

    private function completeBuildingUpgrade(array $event): string {
        $stmt = $this->db->prepare("UPDATE user_buildings SET status = 'active', current_level = current_level + 1 WHERE id = :bid");
        $stmt->execute([':bid' => $event['reference_id']]);
        return "🏗️ Bauarbeiten abgeschlossen! Gebäude-Level erhöht.";
    }

    private function markAsProcessed(int $eventId): void {
        $stmt = $this->db->prepare("UPDATE event_queue SET is_processed = 1 WHERE id = :id");
        $stmt->execute([':id' => $eventId]);
    }
}
?>