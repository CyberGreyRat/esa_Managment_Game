<?php
// Basis-Klasse für alle Ansichten
abstract class View {
    protected $userId;
    protected $db;
    protected $player;

    public function __construct($userId) {
        $this->userId = $userId;
        $this->db = Database::getInstance()->getConnection();
        $this->player = new Player($userId);
    }

    // Jede Unterklasse MUSS diese Methode haben
    abstract public function render(): void;

    // Hilfsmethode: Prüft POST-Aktionen für diese Seite
    public function handleAction(): ?array {
        return null; // Standardmäßig passiert nichts, Unterklassen können das überschreiben
    }
}
?>