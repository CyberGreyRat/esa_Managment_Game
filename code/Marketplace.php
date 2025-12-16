<?php
require_once 'Database.php';

class Marketplace {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Holt alle kaufbaren Raketen-Modelle
     */
    public function getRocketTypes(): array {
        $stmt = $this->db->query("SELECT * FROM rocket_types ORDER BY cost ASC");
        return $stmt->fetchAll();
    }

    /**
     * Rakete kaufen
     */
    public function buyRocket(int $userId, int $rocketTypeId): array {
        try {
            $this->db->beginTransaction();

            // 1. Geld des Users prüfen (FOR UPDATE sperrt das Konto kurzzeitig)
            $stmt = $this->db->prepare("SELECT money FROM user_resources WHERE user_id = :uid FOR UPDATE");
            $stmt->execute([':uid' => $userId]);
            $money = $stmt->fetchColumn();

            // 2. Preis der Rakete prüfen
            $stmt = $this->db->prepare("SELECT * FROM rocket_types WHERE id = :rid");
            $stmt->execute([':rid' => $rocketTypeId]);
            $rocketType = $stmt->fetch();

            if (!$rocketType) {
                throw new Exception("Raketentyp existiert nicht.");
            }

            if ($money < $rocketType['cost']) {
                throw new Exception("Nicht genug Geld! Du brauchst " . number_format($rocketType['cost'], 2) . " €.");
            }

            // 3. Geld abziehen
            $stmt = $this->db->prepare("UPDATE user_resources SET money = money - :cost WHERE user_id = :uid");
            $stmt->execute([':cost' => $rocketType['cost'], ':uid' => $userId]);

            // 4. Rakete in die Flotte liefern
            // Wir generieren einen zufälligen Namen, z.B. "Ariane 62 #492"
            $newName = $rocketType['name'] . " #" . rand(100, 999);
            
            $stmt = $this->db->prepare("INSERT INTO user_fleet (user_id, rocket_type_id, name, status) VALUES (:uid, :rid, :name, 'idle')");
            $stmt->execute([
                ':uid' => $userId,
                ':rid' => $rocketTypeId,
                ':name' => $newName
            ]);

            $this->db->commit();
            return ['success' => true, 'message' => "Kauf erfolgreich! {$newName} steht im Hangar bereit."];

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'message' => "Kauf fehlgeschlagen: " . $e->getMessage()];
        }
    }
}
?>