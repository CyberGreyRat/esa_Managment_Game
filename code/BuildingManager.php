<?php
require_once 'Database.php';

class BuildingManager {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Holt alle Gebäudetypen und den aktuellen Status des Spielers dazu.
     * Nutzt LEFT JOIN, damit auch Gebäude angezeigt werden, die der Spieler noch nicht besitzt (Level 0).
     */
    public function getBuildings(int $userId): array {
        $sql = "SELECT 
                    bt.id as type_id, 
                    bt.name, 
                    bt.description, 
                    bt.base_cost, 
                    bt.base_construction_time, 
                    bt.cost_multiplier,
                    ub.id as user_building_id,
                    ub.current_level,
                    ub.status
                FROM building_types bt
                LEFT JOIN user_buildings ub ON bt.id = ub.building_type_id AND ub.user_id = :uid
                ORDER BY bt.id ASC";
                
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        
        $buildings = $stmt->fetchAll();

        // Wir berechnen hier direkt die Kosten für das NÄCHSTE Level
        foreach ($buildings as &$b) {
            $level = $b['current_level'] ?? 0;
            // Formel: Basispreis * (Multiplikator ^ Level)
            $b['next_cost'] = $b['base_cost'] * pow($b['cost_multiplier'], $level);
            $b['next_time'] = $b['base_construction_time'] * ($level + 1);
        }

        return $buildings;
    }

    /**
     * Startet den Ausbau eines Gebäudes.
     */
    public function startUpgrade(int $userId, int $buildingTypeId): array {
        try {
            $this->db->beginTransaction();

            // 1. User Geld sperren & prüfen
            $stmt = $this->db->prepare("SELECT money FROM user_resources WHERE user_id = :uid FOR UPDATE");
            $stmt->execute([':uid' => $userId]);
            $money = $stmt->fetchColumn();

            // 2. Gebäude-Infos laden
            $stmt = $this->db->prepare("SELECT * FROM building_types WHERE id = :bid");
            $stmt->execute([':bid' => $buildingTypeId]);
            $type = $stmt->fetch();

            // 3. Status des Spielers prüfen
            $stmt = $this->db->prepare("SELECT * FROM user_buildings WHERE user_id = :uid AND building_type_id = :bid");
            $stmt->execute([':uid' => $userId, ':bid' => $buildingTypeId]);
            $userBuilding = $stmt->fetch();

            $currentLevel = $userBuilding ? $userBuilding['current_level'] : 0;
            
            // Kosten berechnen
            $cost = $type['base_cost'] * pow($type['cost_multiplier'], $currentLevel);
            $duration = $type['base_construction_time'] * ($currentLevel + 1);

            if ($money < $cost) {
                throw new Exception("Zu wenig Geld! Benötigt: " . number_format($cost, 2) . " €");
            }

            if ($userBuilding && $userBuilding['status'] === 'upgrading') {
                throw new Exception("Gebäude wird bereits ausgebaut!");
            }

            // 4. Geld abziehen
            $stmt = $this->db->prepare("UPDATE user_resources SET money = money - :cost WHERE user_id = :uid");
            $stmt->execute([':cost' => $cost, ':uid' => $userId]);

            // 5. Gebäude-Eintrag erstellen oder updaten
            $userBuildingId = 0;
            if (!$userBuilding) {
                // Neu kaufen (Level 0 -> wird gebaut)
                $stmt = $this->db->prepare("INSERT INTO user_buildings (user_id, building_type_id, current_level, status) VALUES (:uid, :bid, 0, 'upgrading')");
                $stmt->execute([':uid' => $userId, ':bid' => $buildingTypeId]);
                $userBuildingId = $this->db->lastInsertId();
            } else {
                // Upgrade starten
                $stmt = $this->db->prepare("UPDATE user_buildings SET status = 'upgrading' WHERE id = :id");
                $stmt->execute([':id' => $userBuilding['id']]);
                $userBuildingId = $userBuilding['id'];
            }

            // 6. Event in die Queue
            $stmt = $this->db->prepare("INSERT INTO event_queue (user_id, event_type, reference_id, start_time, end_time, is_processed) 
                                      VALUES (:uid, 'BUILDING_UPGRADE', :ref, NOW(), NOW() + INTERVAL :secs SECOND, 0)");
            $stmt->execute([':uid' => $userId, ':ref' => $userBuildingId, ':secs' => $duration]);

            $this->db->commit();
            return ['success' => true, 'message' => "Bau von '{$type['name']}' gestartet! Dauer: " . gmdate("H:i:s", $duration)];

        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
?>