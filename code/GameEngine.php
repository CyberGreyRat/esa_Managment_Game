<?php
require_once 'Database.php';

class GameEngine
{
    private PDO $db;

    public function __construct()
    {
        // Wir holen uns die Verbindung vom Singleton
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Die Haupt-Methode: Prüft ALLES, was in der Abwesenheit passiert ist.
     */
    public function processQueue(int $userId): array
    {
        $messages = [];

        // 1. Suche Events, die in der Vergangenheit liegen (end_time <= JETZT)
        // und noch NICHT verarbeitet wurden (is_processed = 0).
        $sql = "SELECT * FROM event_queue 
                WHERE user_id = :uid 
                AND end_time <= NOW() 
                AND is_processed = 0
                ORDER BY end_time ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        $events = $stmt->fetchAll();

        // 2. Jedes gefundene Event abarbeiten
        foreach ($events as $event) {
            $neueNachricht = $this->handleEvent($event);
            if ($neueNachricht) {
                $messages[] = $neueNachricht;
            }
            $this->markAsProcessed($event['id']);
        }

        return $messages;
    }

    /**
     * NEU: Holt alle NOCH LAUFENDEN Events für die Anzeige im Dashboard.
     * Nutzt komplexe Joins, um Namen von Raketen oder Gebäuden zu finden.
     */
    public function getActiveEvents(int $userId): array
    {
        $sql = "SELECT 
                    eq.*,
                    TIMESTAMPDIFF(SECOND, NOW(), eq.end_time) as seconds_remaining,
                    -- Namen für Raketen holen (wenn es eine Mission ist)
                    uf.name as rocket_name,
                    -- Namen für Gebäude holen (wenn es ein Upgrade ist)
                    bt.name as building_name
                FROM event_queue eq
                -- Join für Missionen (Verbindung zur Flotte)
                LEFT JOIN user_fleet uf ON eq.reference_id = uf.id AND eq.event_type = 'MISSION_RETURN'
                -- Join für Gebäude (Verbindung zu user_buildings -> building_types)
                LEFT JOIN user_buildings ub ON eq.reference_id = ub.id AND eq.event_type = 'BUILDING_UPGRADE'
                LEFT JOIN building_types bt ON ub.building_type_id = bt.id
                
                WHERE eq.user_id = :uid 
                AND eq.is_processed = 0
                AND eq.end_time > NOW() -- Nur was in der ZUKUNFT liegt
                ORDER BY eq.end_time ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Entscheidet, was bei welchem Event-Typ passieren soll.
     */
    private function handleEvent(array $event): ?string
    {
        switch ($event['event_type']) {
            case 'MISSION_RETURN':
                return $this->completeMission($event);
            case 'BUILDING_UPGRADE':
                return $this->completeBuildingUpgrade($event);
            default:
                return "Unbekanntes Event verarbeitet.";
        }
    }

    private function completeMission(array $event): string
    {
        $gewinn = 2000000; // Vereinfacht

        $stmt = $this->db->prepare("UPDATE user_resources SET money = money + :betrag WHERE user_id = :uid");
        $stmt->execute([':betrag' => $gewinn, ':uid' => $event['user_id']]);

        $stmt = $this->db->prepare("UPDATE user_fleet SET status = 'idle', flights_completed = flights_completed + 1 WHERE id = :rid");
        $stmt->execute([':rid' => $event['reference_id']]);

        return "🚀 Mission erfolgreich! Gewinn: " . number_format($gewinn, 2) . " €";
    }

    private function completeBuildingUpgrade(array $event): string
    {
        $stmt = $this->db->prepare("UPDATE user_buildings SET status = 'active', current_level = current_level + 1 WHERE id = :bid");
        $stmt->execute([':bid' => $event['reference_id']]);

        return "🏗️ Bauarbeiten abgeschlossen! Gebäude-Level erhöht.";
    }

    private function markAsProcessed(int $eventId): void
    {
        $stmt = $this->db->prepare("UPDATE event_queue SET is_processed = 1 WHERE id = :id");
        $stmt->execute([':id' => $eventId]);
    }
}
