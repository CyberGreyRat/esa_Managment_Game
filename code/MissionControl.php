<?php
require_once 'Database.php';

class MissionControl {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Holt alle verfügbaren Missionstypen aus der Datenbank.
     */
    public function getAvailableMissions(): array {
        $stmt = $this->db->query("SELECT * FROM mission_types ORDER BY reward_money ASC");
        return $stmt->fetchAll();
    }

    /**
     * Versucht, eine Mission zu starten.
     * Nutzt Transaktionen für absolute Datensicherheit.
     */
    public function startMission(int $userId, int $rocketId, int $missionId): array {
        try {
            // 1. TRANSAKTION STARTEN
            // Ab hier ist die Datenbank "gesperrt" für Schreibzugriffe auf die betroffenen Zeilen,
            // wenn wir "FOR UPDATE" nutzen. Nichts wird permanent gespeichert, bis wir "commit" sagen.
            $this->db->beginTransaction();

            // 2. CHECK: Gehört die Rakete dem User und ist sie bereit?
            // "FOR UPDATE" sperrt die Zeile, damit kein anderer Prozess die Rakete gleichzeitig klauen kann.
            $stmt = $this->db->prepare("SELECT * FROM user_fleet WHERE id = :rid AND user_id = :uid FOR UPDATE");
            $stmt->execute([':rid' => $rocketId, ':uid' => $userId]);
            $rocket = $stmt->fetch();

            if (!$rocket) {
                throw new Exception("Rakete nicht gefunden.");
            }
            if ($rocket['status'] !== 'idle') {
                throw new Exception("Rakete ist nicht bereit (Status: {$rocket['status']}).");
            }

            // 3. CHECK: Mission laden und Kapazität prüfen
            $stmt = $this->db->prepare("SELECT * FROM mission_types WHERE id = :mid");
            $stmt->execute([':mid' => $missionId]);
            $mission = $stmt->fetch();

            // Hole Raketen-Daten (Nutzlastkapazität) aus der Typen-Tabelle
            $stmt = $this->db->prepare("SELECT cargo_capacity_leo FROM rocket_types WHERE id = :rtid");
            $stmt->execute([':rtid' => $rocket['rocket_type_id']]);
            $rocketStats = $stmt->fetch();

            if ($rocketStats['cargo_capacity_leo'] < $mission['required_cargo_capacity']) {
                throw new Exception("Rakete zu schwach! Benötigt: {$mission['required_cargo_capacity']}kg.");
            }

            // 4. LOGIK: Start durchführen
            
            // A) Rakete auf 'in_mission' setzen
            $updateRocket = $this->db->prepare("UPDATE user_fleet SET status = 'in_mission' WHERE id = :rid");
            $updateRocket->execute([':rid' => $rocketId]);

            // B) Event in die Warteschlange (Queue) schreiben
            // Die Mission endet JETZT + Missionsdauer
            $duration = $mission['duration_seconds'];
            $insertEvent = $this->db->prepare("
                INSERT INTO event_queue (user_id, event_type, reference_id, start_time, end_time, is_processed) 
                VALUES (:uid, 'MISSION_RETURN', :rid, NOW(), NOW() + INTERVAL :duration SECOND, 0)
            ");
            $insertEvent->execute([
                ':uid' => $userId,
                ':rid' => $rocketId,
                ':duration' => $duration
            ]);

            // 5. TRANSAKTION ABSCHLIEßEN
            // Erst jetzt werden die Änderungen wirklich für alle sichtbar gespeichert.
            $this->db->commit();

            return ['success' => true, 'message' => "Start erfolgreich! Mission '{$mission['name']}' läuft."];

        } catch (Exception $e) {
            // FEHLER! Alles rückgängig machen.
            // Wenn die Rakete schon geupdatet wurde, aber das Event nicht geschrieben werden konnte,
            // setzt rollBack() die Rakete wieder auf 'idle'. Sicherheit pur!
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'message' => "Start abgebrochen: " . $e->getMessage()];
        }
    }
}
?>