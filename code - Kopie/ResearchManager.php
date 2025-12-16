<?php
require_once 'Database.php';

class ResearchManager {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Holt alle Technologien und markiert, welche der User schon hat.
     */
    public function getTechTree(int $userId): array {
        // FIX: Wir nutzen hier :uid1 und :uid2 statt einfach :uid.
        // Grund: Wenn PDO Emulation deaktiviert ist (in Database.php), werfen manche
        // Treiber einen Fehler, wenn derselbe Parameter-Name mehrfach im SQL vorkommt.
        $sql = "SELECT t.*, 
                       (ut.researched_at IS NOT NULL) as is_researched,
                       pt.name as parent_name,
                       (pt_ut.researched_at IS NOT NULL OR t.parent_tech_id IS NULL) as is_unlockable
                FROM technologies t
                LEFT JOIN user_technologies ut ON t.id = ut.tech_id AND ut.user_id = :uid1
                LEFT JOIN technologies pt ON t.parent_tech_id = pt.id
                LEFT JOIN user_technologies pt_ut ON t.parent_tech_id = pt_ut.tech_id AND pt_ut.user_id = :uid2
                ORDER BY t.cost_science_points ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':uid1' => $userId,
            ':uid2' => $userId
        ]);
        return $stmt->fetchAll();
    }

    /**
     * Versucht, eine Forschung zu kaufen.
     */
    public function research(int $userId, int $techId): array {
        try {
            $this->db->beginTransaction();

            // 1. User SP prüfen
            $stmt = $this->db->prepare("SELECT science_points FROM user_resources WHERE user_id = :uid FOR UPDATE");
            $stmt->execute([':uid' => $userId]);
            $currentSP = $stmt->fetchColumn();

            // 2. Tech prüfen
            $stmt = $this->db->prepare("SELECT * FROM technologies WHERE id = :tid");
            $stmt->execute([':tid' => $techId]);
            $tech = $stmt->fetch();

            if (!$tech) {
                throw new Exception("Technologie existiert nicht.");
            }

            // 3. Voraussetzungen prüfen
            if ($tech['parent_tech_id']) {
                $stmt = $this->db->prepare("SELECT * FROM user_technologies WHERE user_id = :uid AND tech_id = :pid");
                $stmt->execute([':uid' => $userId, ':pid' => $tech['parent_tech_id']]);
                if (!$stmt->fetch()) {
                    throw new Exception("Du musst erst die vorausgehende Technologie erforschen!");
                }
            }

            // 4. Schon erforscht?
            $stmt = $this->db->prepare("SELECT * FROM user_technologies WHERE user_id = :uid AND tech_id = :tid");
            $stmt->execute([':uid' => $userId, ':tid' => $techId]);
            if ($stmt->fetch()) {
                throw new Exception("Bereits erforscht!");
            }

            // 5. Kosten prüfen
            if ($currentSP < $tech['cost_science_points']) {
                throw new Exception("Zu wenig Forschungspunkte! Benötigt: " . $tech['cost_science_points'] . " SP");
            }

            // 6. KAUFEN: SP abziehen und Eintrag erstellen
            $stmt = $this->db->prepare("UPDATE user_resources SET science_points = science_points - :cost WHERE user_id = :uid");
            $stmt->execute([':cost' => $tech['cost_science_points'], ':uid' => $userId]);

            $stmt = $this->db->prepare("INSERT INTO user_technologies (user_id, tech_id) VALUES (:uid, :tid)");
            $stmt->execute([':uid' => $userId, ':tid' => $techId]);

            $this->db->commit();
            return ['success' => true, 'message' => "Forschung '{$tech['name']}' abgeschlossen!"];

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
?>