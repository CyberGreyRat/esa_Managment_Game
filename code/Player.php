<?php
require_once 'Database.php';

class Player {
    private PDO $db;
    private int $id;
    
    // Eigenschaften (Public zum Lesen, aber wir könnten auch Getter nehmen)
    public string $username;
    public float $money;
    public int $sciencePoints;

    public function __construct(int $id) {
        $this->db = Database::getInstance()->getConnection();
        $this->id = $id;
        $this->loadData();
    }

    /**
     * Lädt die aktuellen Basis-Daten aus der DB
     */
    public function loadData(): void {
        // 1. User Namen holen
        $stmt = $this->db->prepare("SELECT username FROM users WHERE id = :id");
        $stmt->execute([':id' => $this->id]);
        $user = $stmt->fetch();
        $this->username = $user['username'] ?? 'Unbekannt';

        // 2. Ressourcen holen
        $stmt = $this->db->prepare("SELECT money, science_points FROM user_resources WHERE user_id = :id");
        $stmt->execute([':id' => $this->id]);
        $res = $stmt->fetch();
        
        // Casten auf float/int für saubere Typen
        $this->money = (float) ($res['money'] ?? 0);
        $this->sciencePoints = (int) ($res['science_points'] ?? 0);
    }

    /**
     * Holt die Flotte des Spielers als Array
     * Später könnten wir hier ein Array aus "Rocket"-Objekten zurückgeben.
     */
    public function getFleet(): array {
        $sql = "SELECT uf.id, uf.rocket_name, uf.status, rt.name as type_name, rt.cargo_capacity_leo 
                FROM user_fleet uf
                JOIN rocket_types rt ON uf.rocket_type_id = rt.id
                WHERE uf.user_id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $this->id]);
        return $stmt->fetchAll();
    }

    public function getId(): int {
        return $this->id;
    }
}
?>