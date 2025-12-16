<?php

class Database {
    // Statische Variable: Sie gehört der KLASSE, nicht einem Objekt.
    // Hier speichern wir die EINE einzige Instanz der Datenbank-Verbindung.
    private static ?Database $instance = null;

    // Das eigentliche Verbindungsobjekt (PDO = PHP Data Objects)
    private PDO $connection;

    // Konfiguration (Musst du anpassen!)
    private string $host = 'localhost'; // Bei den meisten Hostern 'localhost'
    private string $db   = 'terrae_novae_erp';
    private string $user = 'root';      // Dein DB-Benutzername
    private string $pass = '';          // Dein DB-Passwort
    private string $charset = 'utf8mb4';

    // Der Konstruktor ist PRIVATE.
    // Das verbietet: $db = new Database(); von außen.
    // Wir wollen ja erzwingen, dass man getInstance() nutzt.
    private function __construct() {
        // Data Source Name (DSN): Der Verbindungs-String für PDO
        $dsn = "mysql:host=$this->host;dbname=$this->db;charset=$this->charset";
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Fehler als "Exception" werfen
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Ergebnisse als assoziatives Array (Key => Value)
            PDO::ATTR_EMULATE_PREPARES   => false,                  // Sicherheits-Feature für "echte" Prepared Statements
        ];

        try {
            $this->connection = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (\PDOException $e) {
            // Wenn Verbindung fehlschlägt: Abbruch mit Fehlermeldung
            throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    // Die wichtigste Methode: getInstance()
    // Wenn es noch keine Verbindung gibt -> Erstelle sie.
    // Wenn es schon eine gibt -> Gib die vorhandene zurück.
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    // Eine Methode, um das PDO-Objekt zu bekommen, damit wir SQL-Befehle senden können.
    public function getConnection(): PDO {
        return $this->connection;
    }
    
    // Hilfsmethode: Verhindert, dass man die Verbindung klont (wichtig für Singleton)
    private function __clone() {}
}
?>