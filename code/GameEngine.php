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

    // ... (calculatePassiveScience und updateLastActive bleiben gleich wie vorher, Platz sparen) ...
    private function calculatePassiveScience(int $userId): ?string {
        $stmt = $this->db->prepare("SELECT last_active FROM users WHERE id = :uid");
        $stmt->execute([':uid' => $userId]);
        $lastActiveStr = $stmt->fetchColumn();
        if (!$lastActiveStr) return null;
        $diff = (new DateTime())->getTimestamp() - (new DateTime($lastActiveStr))->getTimestamp();
        if ($diff < 10) return null;

        $stmt = $this->db->prepare("SELECT current_level FROM user_buildings WHERE user_id = :uid AND building_type_id = 2");
        $stmt->execute([':uid' => $userId]);
        $labLevel = (int)$stmt->fetchColumn(); 
        $stmt = $this->db->prepare("SELECT SUM(skill_value) FROM specialists WHERE user_id = :uid AND type = 'Scientist'");
        $stmt->execute([':uid' => $userId]);
        $scientistBonus = (int)$stmt->fetchColumn();
        $rate = ($labLevel * 10) + $scientistBonus;
        if ($rate <= 0) return null;
        $earned = floor(($diff / 3600) * $rate);
        if ($earned > 0) {
            $this->db->query("UPDATE user_resources SET science_points = science_points + $earned WHERE user_id = $userId");
            return "🧪 Passive Forschung: +$earned SP";
        }
        return null;
    }
    private function updateLastActive($uid) { $this->db->query("UPDATE users SET last_active = NOW() WHERE id = $uid"); }


    public function getActiveEvents(int $userId): array {
        // Wir joinen countries, falls es irgendeine Art von Negotiation ist
        $sql = "SELECT 
                    eq.*,
                    TIMESTAMPDIFF(SECOND, NOW(), eq.end_time) as seconds_remaining,
                    uf.name as rocket_name,
                    mt.name as mission_name,
                    mt.reward_money,
                    bt.name as building_name,
                    c.name as country_name
                FROM event_queue eq
                LEFT JOIN user_fleet uf ON eq.reference_id = uf.id AND eq.event_type = 'MISSION_RETURN'
                LEFT JOIN mission_types mt ON uf.current_mission_id = mt.id
                LEFT JOIN user_buildings ub ON eq.reference_id = ub.id AND eq.event_type = 'BUILDING_UPGRADE'
                LEFT JOIN building_types bt ON ub.building_type_id = bt.id
                -- Join für ALLE Verhandlungsarten (starten mit NEGOTIATION_)
                LEFT JOIN countries c ON eq.reference_id = c.id AND eq.event_type LIKE 'NEGOTIATION_%'
                WHERE eq.user_id = :uid AND eq.is_processed = 0 AND eq.end_time > NOW()
                ORDER BY eq.end_time ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll();
    }

    private function handleEvent(array $event): ?string {
        // Switch prüft nun auf die neuen Typen
        switch ($event['event_type']) {
            case 'MISSION_RETURN': return $this->completeMission($event);
            case 'BUILDING_UPGRADE': return $this->completeBuildingUpgrade($event);
            
            case 'NEGOTIATION_MONEY': return $this->completeNegotiation($event, 'MONEY');
            case 'NEGOTIATION_SCIENCE': return $this->completeNegotiation($event, 'SCIENCE');
            case 'NEGOTIATION_LOBBYING': return $this->completeNegotiation($event, 'LOBBYING');
            
            // Fallback für alte Events
            case 'BUDGET_NEGOTIATION': return $this->completeNegotiation($event, 'MONEY'); 
            
            default: return "Unbekanntes Event (Typ: {$event['event_type']}) verarbeitet.";
        }
    }

    /**
     * Schließt eine Verhandlung ab und berechnet Belohnung basierend auf Thema
     */
    private function completeNegotiation(array $event, string $topic): string {
        $countryId = $event['reference_id'];
        $userId = $event['user_id'];
        
        // 1. Ruf und Land laden
        $stmt = $this->db->prepare("SELECT c.name, IFNULL(ur.reputation, 50) as reputation 
                                    FROM countries c 
                                    LEFT JOIN user_reputation ur ON c.id = ur.country_id AND ur.user_id = :uid 
                                    WHERE c.id = :cid");
        $stmt->execute([':cid' => $countryId, ':uid' => $userId]);
        $data = $stmt->fetch();
        $reputation = $data['reputation'];
        $countryName = $data['name'];

        // 2. Belohnung berechnen
        if ($topic === 'MONEY') {
            // Formel: 2 Mio Basis + (Ruf * 50.000)
            $amount = 2000000 + ($reputation * 50000);
            $stmt = $this->db->prepare("UPDATE user_resources SET money = money + :val WHERE user_id = :uid");
            $stmt->execute([':val' => $amount, ':uid' => $userId]);
            return "💰 Budgeterhöhung aus $countryName erhalten: " . number_format($amount, 0, ',', '.') . " € (Ruf: $reputation)";
        }
        
        elseif ($topic === 'SCIENCE') {
            // Formel: 100 SP Basis + (Ruf * 5)
            $amount = 100 + ($reputation * 5);
            $stmt = $this->db->prepare("UPDATE user_resources SET science_points = science_points + :val WHERE user_id = :uid");
            $stmt->execute([':val' => $amount, ':uid' => $userId]);
            return "🔬 Technologie-Transfer mit $countryName: +$amount SP";
        }
        
        elseif ($topic === 'LOBBYING') {
            // Ruf steigt um 5 bis 10 Punkte
            $gain = rand(5, 10);
            $newRep = min(100, $reputation + $gain);
            
            // Upsert (Insert oder Update) für Reputation
            $sql = "INSERT INTO user_reputation (user_id, country_id, reputation) VALUES (:uid, :cid, :rep)
                    ON DUPLICATE KEY UPDATE reputation = reputation + :gain";
            // MySQL cap auf 100 machen wir hier im Code einfacher:
            // Wir lesen es beim nächsten Mal eh neu. Aber um sauber zu sein:
            if ($reputation + $gain > 100) $gain = 100 - $reputation;
            
            if ($gain > 0) {
                $this->db->prepare("INSERT INTO user_reputation (user_id, country_id, reputation) VALUES (:uid, :cid, :base) 
                                    ON DUPLICATE KEY UPDATE reputation = LEAST(100, reputation + :gain)")
                         ->execute([':uid' => $userId, ':cid' => $countryId, ':base' => 50 + $gain, ':gain' => $gain]);
                return "🤝 Erfolgreiches Lobbying in $countryName! Ruf verbessert um +$gain (Neu: " . ($reputation+$gain) . "/100).";
            } else {
                return "🤝 Lobbying in $countryName: Ruf ist bereits maximal (100).";
            }
        }

        return "Verhandlung beendet.";
    }

    // ... (restliche Funktionen completeMission etc. bleiben gleich) ...
    private function completeMission(array $event): string {
        $rocketId = $event['reference_id'];
        $sql = "SELECT uf.*, mt.name as mission_name, mt.reward_money, mt.reward_science FROM user_fleet uf JOIN mission_types mt ON uf.current_mission_id = mt.id WHERE uf.id = :rid";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':rid' => $rocketId]);
        $data = $stmt->fetch();
        $gewinn = $data ? $data['reward_money'] : 0;
        $science = $data ? $data['reward_science'] : 0;
        $missionName = $data ? $data['mission_name'] : "Mission";
        $this->db->prepare("UPDATE user_resources SET money = money + :m, science_points = science_points + :s WHERE user_id = :uid")->execute([':m'=>$gewinn, ':s'=>$science, ':uid'=>$event['user_id']]);
        $this->db->prepare("UPDATE user_fleet SET status = 'idle', current_mission_id = NULL, flights_completed = flights_completed + 1 WHERE id = :rid")->execute([':rid'=>$rocketId]);
        return "🚀 Mission '$missionName' erfolgreich! +".number_format($gewinn/1000000,1)."M € & +$science SP";
    }

    private function completeBuildingUpgrade(array $event): string {
        $this->db->prepare("UPDATE user_buildings SET status = 'active', current_level = current_level + 1 WHERE id = :bid")->execute([':bid' => $event['reference_id']]);
        return "🏗️ Bauarbeiten abgeschlossen! Gebäude-Level erhöht.";
    }

    private function markAsProcessed(int $eventId): void {
        $this->db->prepare("UPDATE event_queue SET is_processed = 1 WHERE id = :id")->execute([':id' => $eventId]);
    }
}
?>