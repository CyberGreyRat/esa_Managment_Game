<?php
require_once 'Database.php';

class PoliticsManager {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getCountries(int $userId): array {
        $sql = "SELECT c.*, IFNULL(ur.reputation, 50) as reputation 
                FROM countries c
                LEFT JOIN user_reputation ur ON c.id = ur.country_id AND ur.user_id = :uid";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Startet eine Budget-Verhandlung (Dauert Zeit, Ergebnis kommt per Event)
     */
    public function startNegotiation(int $userId, int $countryId, int $specialistId): array {
        try {
            $this->db->beginTransaction();

            // 1. Spezialist prüfen
            // Wir selektieren FOR UPDATE, um Race-Conditions zu vermeiden
            $stmt = $this->db->prepare("SELECT * FROM specialists WHERE id = :sid AND user_id = :uid FOR UPDATE");
            $stmt->execute([':sid' => $specialistId, ':uid' => $userId]);
            $spec = $stmt->fetch();

            if (!$spec) throw new Exception("Mitarbeiter nicht gefunden.");
            
            // NEU: Prüfung, ob er beschäftigt ist
            if (!empty($spec['busy_until']) && new DateTime($spec['busy_until']) > new DateTime()) {
                throw new Exception("{$spec['name']} ist noch bis " . date('H:i', strtotime($spec['busy_until'])) . " beschäftigt!");
            }
            
            // 2. Land prüfen
            $stmt = $this->db->prepare("SELECT * FROM countries WHERE id = :cid");
            $stmt->execute([':cid' => $countryId]);
            $country = $stmt->fetch();

            // 3. Dauer berechnen
            $duration = max(600, 3600 - ($spec['skill_value'] * 60)); 
            
            // 4. Event erstellen
            $stmt = $this->db->prepare("INSERT INTO event_queue (user_id, event_type, reference_id, start_time, end_time, is_processed) 
                                      VALUES (:uid, 'BUDGET_NEGOTIATION', :cid, NOW(), NOW() + INTERVAL :dur SECOND, 0)");
            $stmt->execute([':uid' => $userId, ':cid' => $countryId, ':dur' => $duration]);

            // 5. NEU: Mitarbeiter als beschäftigt markieren
            $stmt = $this->db->prepare("UPDATE specialists SET busy_until = NOW() + INTERVAL :dur SECOND WHERE id = :sid");
            $stmt->execute([':dur' => $duration, ':sid' => $specialistId]);

            $this->db->commit();
            return ['success' => true, 'message' => "{$spec['name']} ist auf dem Weg nach {$country['name']}. Dauer: " . gmdate("H:i", $duration)];

        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
?>