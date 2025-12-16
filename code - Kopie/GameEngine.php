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
        $diff = (new DateTime())->getTimestamp() - (new DateTime($lastActiveStr))->getTimestamp();
        if ($diff < 10) return null;

        $stmt = $this->db->prepare("
            SELECT 
                COALESCE(SUM(smt.power_generation), 0) as total_power,
                COALESCE(SUM(smt.crew_capacity), 0) as total_capacity,
                COUNT(um.id) as module_count
            FROM user_modules um
            JOIN station_module_types smt ON um.module_type_id = smt.id
            WHERE um.user_id = :uid AND um.status = 'assembled'");
        $stmt->execute([':uid' => $userId]);
        $stats = $stmt->fetch();
        
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM astronauts WHERE user_id = :uid AND status = 'in_orbit'");
        $stmt->execute([':uid' => $userId]);
        $astroCount = (int)$stmt->fetchColumn();

        $stationIsOnline = ($stats['total_power'] >= 0);
        $stationBonus = 0;
        
        if ($stationIsOnline) {
            $validCrew = min($astroCount, $stats['total_capacity']);
            $stationBonus = ($stats['module_count'] * 50) + ($validCrew * 200);
        }

        $stmt = $this->db->prepare("SELECT current_level FROM user_buildings WHERE user_id = :uid AND building_type_id = 2");
        $stmt->execute([':uid' => $userId]);
        $labLevel = (int)$stmt->fetchColumn(); 
        $stmt = $this->db->prepare("SELECT SUM(skill_value) FROM specialists WHERE user_id = :uid AND type = 'Scientist'");
        $stmt->execute([':uid' => $userId]);
        $scientistBonus = (int)$stmt->fetchColumn();

        $rate = ($labLevel * 10) + $scientistBonus + $stationBonus;
        
        if ($rate <= 0) return null;

        $earned = floor(($diff / 3600) * $rate);
        if ($earned > 0) {
            $this->db->query("UPDATE user_resources SET science_points = science_points + $earned WHERE user_id = $userId");
            $msg = "🧪 Passive Forschung: +$earned SP";
            if (!$stationIsOnline) $msg .= " (Station OFFLINE!)";
            return $msg;
        }
        return null;
    }
    
    private function updateLastActive($uid) { $this->db->query("UPDATE users SET last_active = NOW() WHERE id = $uid"); }

    public function getActiveEvents(int $userId): array {
        $sql = "SELECT 
                    eq.*,
                    TIMESTAMPDIFF(SECOND, NOW(), eq.end_time) as seconds_remaining,
                    uf.name as rocket_name,
                    mt.name as mission_name,
                    mt.reward_money,
                    bt.name as building_name,
                    c.name as country_name,
                    smt.name as module_name,
                    a.name as astronaut_name
                FROM event_queue eq
                LEFT JOIN user_fleet uf ON eq.reference_id = uf.id AND eq.event_type = 'MISSION_RETURN'
                LEFT JOIN mission_types mt ON uf.current_mission_id = mt.id
                LEFT JOIN user_buildings ub ON eq.reference_id = ub.id AND eq.event_type = 'BUILDING_UPGRADE'
                LEFT JOIN building_types bt ON ub.building_type_id = bt.id
                LEFT JOIN countries c ON eq.reference_id = c.id AND eq.event_type LIKE 'NEGOTIATION_%'
                LEFT JOIN user_modules um ON eq.reference_id = um.id AND (eq.event_type = 'MODULE_CONSTRUCTION' OR eq.event_type = 'MODULE_LAUNCH')
                LEFT JOIN station_module_types smt ON um.module_type_id = smt.id
                LEFT JOIN astronauts a ON eq.reference_id = a.id AND (eq.event_type = 'ASTRO_TRAINING' OR eq.event_type = 'CREW_LAUNCH')
                
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
            case 'NEGOTIATION_MONEY': return $this->completeNegotiation($event, 'MONEY');
            case 'NEGOTIATION_SCIENCE': return $this->completeNegotiation($event, 'SCIENCE');
            case 'NEGOTIATION_LOBBYING': return $this->completeNegotiation($event, 'LOBBYING');
            case 'BUDGET_NEGOTIATION': return $this->completeNegotiation($event, 'MONEY'); 
            case 'MODULE_CONSTRUCTION': return $this->completeModuleConstruction($event);
            case 'MODULE_LAUNCH': return $this->completeModuleLaunch($event);
            case 'ASTRO_TRAINING': return $this->completeAstroTraining($event);
            case 'CREW_LAUNCH': return $this->completeCrewLaunch($event); 
            default: return "Unbekanntes Event (Typ: {$event['event_type']}) verarbeitet.";
        }
    }

    private function completeCrewLaunch(array $event): string {
        $astroId = $event['reference_id'];
        
        $stmt = $this->db->prepare("SELECT * FROM astronauts WHERE id = :id");
        $stmt->execute([':id' => $astroId]);
        $astro = $stmt->fetch();
        
        // FIX: Hole Rakete aus neuer Spalte assigned_rocket_id (NICHT module_id)
        $rocketId = $astro['assigned_rocket_id']; 
        
        // Wir löschen die Raketen-Zuweisung, da der Astronaut jetzt "frei" auf der Station schwebt.
        // assigned_module_id bleibt NULL (bedeutet: auf Station, aber keinem speziellen Modul fest zugewiesen).
        $stmt = $this->db->prepare("UPDATE astronauts SET status = 'in_orbit', assigned_rocket_id = NULL WHERE id = :id");
        $stmt->execute([':id' => $astroId]);
        
        if ($rocketId) {
            $stmt = $this->db->prepare("UPDATE user_fleet SET status = 'idle', flights_completed = flights_completed + 1 WHERE id = :rid");
            $stmt->execute([':rid' => $rocketId]);
        }
        
        return "🧑‍🚀 {$astro['name']} ist sicher auf der Station angekommen!";
    }

    private function completeAstroTraining(array $event): string {
        $astroId = $event['reference_id'];
        $this->db->prepare("UPDATE astronauts SET status = 'ready' WHERE id = :id")->execute([':id' => $astroId]);
        $stmt = $this->db->prepare("SELECT name FROM astronauts WHERE id = :id");
        $stmt->execute([':id' => $astroId]);
        $name = $stmt->fetchColumn();
        return "🎓 Training abgeschlossen: Astronaut $name ist bereit.";
    }

    private function completeModuleLaunch(array $event): string {
        $moduleId = $event['reference_id'];
        $stmt = $this->db->prepare("SELECT um.*, smt.name FROM user_modules um JOIN station_module_types smt ON um.module_type_id = smt.id WHERE um.id = :id");
        $stmt->execute([':id' => $moduleId]);
        $module = $stmt->fetch();
        $rocketId = $module['condition_percent'];
        $stmt = $this->db->prepare("UPDATE user_modules SET status = 'assembled', condition_percent = 100 WHERE id = :id");
        $stmt->execute([':id' => $moduleId]);
        $stmt = $this->db->prepare("UPDATE user_fleet SET status = 'idle', flights_completed = flights_completed + 1 WHERE id = :rid");
        $stmt->execute([':rid' => $rocketId]);
        $this->db->prepare("UPDATE user_reputation SET reputation = LEAST(100, reputation + 2) WHERE user_id = :uid")->execute([':uid' => $event['user_id']]);
        return "🛰️ ANDOCKMANÖVER ERFOLGREICH! '{$module['name']}' ist jetzt Teil der Raumstation.";
    }

    private function completeModuleConstruction(array $event): string {
        $moduleId = $event['reference_id'];
        $this->db->prepare("UPDATE user_modules SET status = 'stored' WHERE id = :id")->execute([':id' => $moduleId]);
        $stmt = $this->db->prepare("SELECT smt.name FROM user_modules um JOIN station_module_types smt ON um.module_type_id = smt.id WHERE um.id = :id");
        $stmt->execute([':id' => $moduleId]);
        $name = $stmt->fetchColumn();
        return "🏭 Fertigung abgeschlossen: '$name' liegt jetzt im Lager.";
    }

    private function completeNegotiation(array $event, string $topic): string {
        $countryId = $event['reference_id'];
        $userId = $event['user_id'];
        $stmt = $this->db->prepare("SELECT c.name, IFNULL(ur.reputation, 50) as reputation FROM countries c LEFT JOIN user_reputation ur ON c.id = ur.country_id AND ur.user_id = :uid WHERE c.id = :cid");
        $stmt->execute([':cid' => $countryId, ':uid' => $userId]);
        $data = $stmt->fetch();
        $reputation = $data['reputation'];
        $countryName = $data['name'];

        if ($topic === 'MONEY') {
            $amount = 2000000 + ($reputation * 50000);
            $this->db->prepare("UPDATE user_resources SET money = money + :val WHERE user_id = :uid")->execute([':val' => $amount, ':uid' => $userId]);
            return "💰 Budgeterhöhung aus $countryName erhalten: " . number_format($amount, 0, ',', '.') . " €";
        } elseif ($topic === 'SCIENCE') {
            $amount = 100 + ($reputation * 5);
            $this->db->prepare("UPDATE user_resources SET science_points = science_points + :val WHERE user_id = :uid")->execute([':val' => $amount, ':uid' => $userId]);
            return "🔬 Technologie-Transfer mit $countryName: +$amount SP";
        } elseif ($topic === 'LOBBYING') {
            $gain = rand(5, 10);
            $this->db->prepare("INSERT INTO user_reputation (user_id, country_id, reputation) VALUES (:uid, :cid, :base) ON DUPLICATE KEY UPDATE reputation = LEAST(100, reputation + :gain)")->execute([':uid' => $userId, ':cid' => $countryId, ':base' => 50 + $gain, ':gain' => $gain]);
            return "🤝 Erfolgreiches Lobbying in $countryName!";
        }
        return "Verhandlung beendet.";
    }

    private function completeMission(array $event): string {
        $rocketId = $event['reference_id'];
        $sql = "SELECT uf.*, mt.name as mission_name, mt.reward_money, mt.reward_science FROM user_fleet uf JOIN mission_types mt ON uf.current_mission_id = mt.id WHERE uf.id = :rid";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':rid' => $rocketId]);
        $data = $stmt->fetch();
        $gewinn = $data ? $data['reward_money'] : 2000000;
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