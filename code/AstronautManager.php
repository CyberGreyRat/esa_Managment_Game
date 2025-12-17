<?php
require_once 'Database.php';

class AstronautManager {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAstronauts(int $userId): array {
        $stmt = $this->db->prepare("SELECT a.*
                                    FROM astronauts a 
                                    WHERE a.user_id = :uid");
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Rekrutiert einen neuen Kandidaten (kostet Geld, startet Training)
     */
    public function recruitAstronaut(int $userId, string $name): array {
        try {
            $this->db->beginTransaction();

            // 1. Gebäude prüfen: Haben wir ein Astronauten-Zentrum?
            $stmt = $this->db->prepare("SELECT current_level FROM user_buildings WHERE user_id = :uid AND building_type_id = 4");
            $stmt->execute([':uid' => $userId]);
            $centerLevel = (int)$stmt->fetchColumn();

            if ($centerLevel < 1) throw new Exception("Du brauchst ein Astronauten-Zentrum (Level 1)!");

            // 2. Kosten (Pauschal 1 Mio pro Kandidat)
            $cost = 1000000;
            $stmt = $this->db->prepare("SELECT money FROM user_resources WHERE user_id = :uid FOR UPDATE");
            $stmt->execute([':uid' => $userId]);
            if ($stmt->fetchColumn() < $cost) throw new Exception("Nicht genug Geld (1 Mio € benötigt).");

            // 3. Bezahlen
            $this->db->prepare("UPDATE user_resources SET money = money - :cost WHERE user_id = :uid")->execute([':cost' => $cost, ':uid' => $userId]);

            // 4. Astronaut anlegen (Status: Training)
            $stmt = $this->db->prepare("INSERT INTO astronauts (user_id, name, status) VALUES (:uid, :name, 'training')");
            $stmt->execute([':uid' => $userId, ':name' => $name]);
            $astroId = $this->db->lastInsertId();

            // 5. Training Event starten (Dauer: 4 Stunden / Level)
            // Je höher das Zentrum, desto schneller das Training? Oder intensiver?
            // Wir machen es einfach: 2 Stunden Basiszeit.
            $duration = 7200; 
            
            $stmt = $this->db->prepare("INSERT INTO event_queue (user_id, event_type, reference_id, start_time, end_time, is_processed) 
                                      VALUES (:uid, 'ASTRO_TRAINING', :ref, NOW(), NOW() + INTERVAL :dur SECOND, 0)");
            $stmt->execute([':uid' => $userId, ':ref' => $astroId, ':dur' => $duration]);

            $this->db->commit();
            return ['success' => true, 'message' => "Rekrutierung von $name gestartet! Training beginnt."];

        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Schickt einen fertigen Astronauten zur Station
     * (Voraussetzung: Ein Modul ist oben und hat Platz)
     */
    public function launchAstronaut(int $userId, int $astronautId, int $rocketId): array {
        // ... (Das bauen wir gleich in MissionControl ein, da es ein Launch ist)
        return ['success' => false, 'message' => 'Nutze MissionControl für den Start.'];
    }
}
?>