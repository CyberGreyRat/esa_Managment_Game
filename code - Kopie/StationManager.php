<?php
require_once 'Database.php';

class StationManager {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getBlueprints(int $userId): array {
        $sql = "SELECT smt.*, 
                       (ut.researched_at IS NOT NULL) as is_unlocked
                FROM station_module_types smt
                LEFT JOIN user_technologies ut ON smt.tech_id_required = ut.tech_id AND ut.user_id = :uid
                ORDER BY smt.cost ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll();
    }

    public function getInventory(int $userId): array {
        $sql = "SELECT um.*, smt.name, smt.mass_kg, smt.description
                FROM user_modules um
                JOIN station_module_types smt ON um.module_type_id = smt.id
                WHERE um.user_id = :uid
                ORDER BY um.status ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll();
    }

    public function constructModule(int $userId, int $moduleTypeId): array {
        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare("SELECT * FROM station_module_types WHERE id = :mid");
            $stmt->execute([':mid' => $moduleTypeId]);
            $type = $stmt->fetch();

            if ($type['tech_id_required']) {
                $stmt = $this->db->prepare("SELECT * FROM user_technologies WHERE user_id = :uid AND tech_id = :tid");
                $stmt->execute([':uid' => $userId, ':tid' => $type['tech_id_required']]);
                if (!$stmt->fetch()) throw new Exception("Technologie noch nicht erforscht!");
            }

            $stmt = $this->db->prepare("SELECT money FROM user_resources WHERE user_id = :uid FOR UPDATE");
            $stmt->execute([':uid' => $userId]);
            if ($stmt->fetchColumn() < $type['cost']) throw new Exception("Nicht genug Geld!");

            $this->db->prepare("UPDATE user_resources SET money = money - :cost WHERE user_id = :uid")->execute([':cost' => $type['cost'], ':uid' => $userId]);
            
            $this->db->prepare("INSERT INTO user_modules (user_id, module_type_id, status) VALUES (:uid, :mid, 'constructing')")->execute([':uid' => $userId, ':mid' => $moduleTypeId]);
            $moduleId = $this->db->lastInsertId();

            $this->db->prepare("INSERT INTO event_queue (user_id, event_type, reference_id, start_time, end_time, is_processed) VALUES (:uid, 'MODULE_CONSTRUCTION', :ref, NOW(), NOW() + INTERVAL :dur SECOND, 0)")
                     ->execute([':uid' => $userId, ':ref' => $moduleId, ':dur' => $type['build_time_seconds']]);

            $this->db->commit();
            return ['success' => true, 'message' => "Bau von '{$type['name']}' gestartet!"];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * NEU: Berechnet die Statistik der aktiven Raumstation
     */
    public function getStationStats(int $userId): array {
        // Wir holen nur Module, die 'assembled' sind (also Teil der Station)
        $sql = "SELECT 
                    COUNT(*) as module_count,
                    SUM(smt.power_generation) as total_power,
                    SUM(smt.crew_capacity) as total_crew_slots
                FROM user_modules um
                JOIN station_module_types smt ON um.module_type_id = smt.id
                WHERE um.user_id = :uid AND um.status = 'assembled'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        $stats = $stmt->fetch();

        // Aktuelle Crew im Orbit zählen
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM astronauts WHERE user_id = :uid AND status = 'in_orbit'");
        $stmt->execute([':uid' => $userId]);
        $stats['current_crew'] = (int)$stmt->fetchColumn();

        // Falls noch keine Station da ist, Nullen zurückgeben
        if (!$stats['module_count']) {
            return [
                'module_count' => 0,
                'total_power' => 0,
                'total_crew_slots' => 0,
                'current_crew' => 0
            ];
        }

        return $stats;
    }
}
?>