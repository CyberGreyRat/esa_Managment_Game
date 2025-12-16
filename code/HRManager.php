<?php
require_once 'Database.php';

class HRManager {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Holt alle Mitarbeiter, die dem User gehören
     */
    public function getMyEmployees(int $userId): array {
        $stmt = $this->db->prepare("SELECT * FROM specialists WHERE user_id = :uid");
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Holt alle Bewerber, die noch niemanden gehören (Markt)
     */
    public function getApplicants(): array {
        // Wir zeigen nur Bewerber ohne User-ID
        $stmt = $this->db->query("SELECT * FROM specialists WHERE user_id IS NULL");
        return $stmt->fetchAll();
    }

    /**
     * Mitarbeiter einstellen
     */
    public function hireSpecialist(int $userId, int $specId): array {
        try {
            $this->db->beginTransaction();

            // 1. Geld prüfen
            $stmt = $this->db->prepare("SELECT money FROM user_resources WHERE user_id = :uid FOR UPDATE");
            $stmt->execute([':uid' => $userId]);
            $money = $stmt->fetchColumn();

            // 2. Bewerber prüfen
            $stmt = $this->db->prepare("SELECT * FROM specialists WHERE id = :sid AND user_id IS NULL FOR UPDATE");
            $stmt->execute([':sid' => $specId]);
            $spec = $stmt->fetch();

            if (!$spec) throw new Exception("Bewerber nicht mehr verfügbar.");
            if ($money < $spec['salary_cost']) throw new Exception("Nicht genug Budget für das Handgeld.");

            // 3. Einstellen & Bezahlen
            $stmt = $this->db->prepare("UPDATE user_resources SET money = money - :cost WHERE user_id = :uid");
            $stmt->execute([':cost' => $spec['salary_cost'], ':uid' => $userId]);

            $stmt = $this->db->prepare("UPDATE specialists SET user_id = :uid WHERE id = :sid");
            $stmt->execute([':uid' => $userId, ':sid' => $specId]);

            $this->db->commit();
            return ['success' => true, 'message' => "{$spec['name']} wurde eingestellt!"];

        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
?>