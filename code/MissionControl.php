<?php
require_once 'Database.php';

class MissionControl {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAvailableMissions(): array {
        $stmt = $this->db->query("SELECT * FROM mission_types ORDER BY reward_money ASC");
        return $stmt->fetchAll();
    }

    public function startMission(int $userId, int $rocketId, int $missionId): array {
        try {
            $this->db->beginTransaction();

            // 1. Rakete prüfen
            $stmt = $this->db->prepare("SELECT * FROM user_fleet WHERE id = :rid AND user_id = :uid FOR UPDATE");
            $stmt->execute([':rid' => $rocketId, ':uid' => $userId]);
            $rocket = $stmt->fetch();

            if (!$rocket) throw new Exception("Rakete nicht gefunden.");
            if ($rocket['status'] !== 'idle') throw new Exception("Rakete ist nicht bereit.");

            // 2. Mission prüfen
            $stmt = $this->db->prepare("SELECT * FROM mission_types WHERE id = :mid");
            $stmt->execute([':mid' => $missionId]);
            $mission = $stmt->fetch();

            // 3. Kapazität prüfen
            $stmt = $this->db->prepare("SELECT cargo_capacity_leo FROM rocket_types WHERE id = :rtid");
            $stmt->execute([':rtid' => $rocket['rocket_type_id']]);
            $rocketStats = $stmt->fetch();

            if ($rocketStats['cargo_capacity_leo'] < $mission['required_cargo_capacity']) {
                throw new Exception("Rakete zu schwach! Benötigt: {$mission['required_cargo_capacity']}kg.");
            }

            // 4. START: Status UND Mission-ID speichern (Das ist neu!)
            $updateRocket = $this->db->prepare("
                UPDATE user_fleet 
                SET status = 'in_mission', 
                    current_mission_id = :mid 
                WHERE id = :rid
            ");
            $updateRocket->execute([':mid' => $missionId, ':rid' => $rocketId]);

            // 5. Event erstellen
            $duration = $mission['duration_seconds'];
            $insertEvent = $this->db->prepare("
                INSERT INTO event_queue (user_id, event_type, reference_id, start_time, end_time, is_processed) 
                VALUES (:uid, 'MISSION_RETURN', :rid, NOW(), NOW() + INTERVAL :duration SECOND, 0)
            ");
            $insertEvent->execute([
                ':uid' => $userId,
                ':rid' => $rocketId,
                ':duration' => $duration
            ]);

            $this->db->commit();
            return ['success' => true, 'message' => "Start erfolgreich! Mission '{$mission['name']}' läuft."];

        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ['success' => false, 'message' => "Start abgebrochen: " . $e->getMessage()];
        }
    }
}
?>