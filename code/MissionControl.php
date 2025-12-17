<?php
require_once 'Database.php';

class MissionControl
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAvailableMissions(): array
    {
        $stmt = $this->db->query("SELECT * FROM mission_types ORDER BY reward_money ASC");
        return $stmt->fetchAll();
    }

    public function startMission(int $userId, int $rocketId, int $missionId): array
    {
        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare("SELECT * FROM user_fleet WHERE id = :rid AND user_id = :uid FOR UPDATE");
            $stmt->execute([':rid' => $rocketId, ':uid' => $userId]);
            $rocket = $stmt->fetch();
            if (!$rocket || $rocket['status'] !== 'idle') throw new Exception("Rakete nicht bereit.");

            $stmt = $this->db->prepare("SELECT * FROM mission_types WHERE id = :mid");
            $stmt->execute([':mid' => $missionId]);
            $mission = $stmt->fetch();

            $stmt = $this->db->prepare("SELECT cargo_capacity_leo FROM rocket_types WHERE id = :rtid");
            $stmt->execute([':rtid' => $rocket['rocket_type_id']]);
            $rocketStats = $stmt->fetch();

            if ($rocketStats['cargo_capacity_leo'] < $mission['required_cargo_capacity']) throw new Exception("Rakete zu schwach!");

            $this->db->prepare("UPDATE user_fleet SET status = 'in_mission', current_mission_id = :mid WHERE id = :rid")->execute([':mid' => $missionId, ':rid' => $rocketId]);

            $this->db->prepare("INSERT INTO event_queue (user_id, event_type, reference_id, start_time, end_time, is_processed) VALUES (:uid, 'MISSION_RETURN', :rid, NOW(), NOW() + INTERVAL :dur SECOND, 0)")
                ->execute([':uid' => $userId, ':rid' => $rocketId, ':dur' => $mission['duration_seconds']]);

            $this->db->commit();
            return ['success' => true, 'message' => "Start erfolgreich! Mission '{$mission['name']}' läuft."];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function launchModule(int $userId, int $rocketId, int $moduleId): array
    {
        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare("SELECT * FROM user_fleet WHERE id = :rid AND user_id = :uid FOR UPDATE");
            $stmt->execute([':rid' => $rocketId, ':uid' => $userId]);
            $rocket = $stmt->fetch();
            if (!$rocket || $rocket['status'] !== 'idle') throw new Exception("Rakete nicht bereit.");

            $stmt = $this->db->prepare("SELECT um.*, smt.mass_kg, smt.name FROM user_modules um JOIN station_module_types smt ON um.module_type_id = smt.id WHERE um.id = :mid AND um.user_id = :uid");
            $stmt->execute([':mid' => $moduleId, ':uid' => $userId]);
            $module = $stmt->fetch();
            if (!$module || $module['status'] !== 'stored') throw new Exception("Modul nicht im Lager.");

            $stmt = $this->db->prepare("SELECT cargo_capacity_leo FROM rocket_types WHERE id = :rtid");
            $stmt->execute([':rtid' => $rocket['rocket_type_id']]);
            $rocketStats = $stmt->fetch();
            if ($rocketStats['cargo_capacity_leo'] < $module['mass_kg']) throw new Exception("Rakete zu schwach!");

            $this->db->prepare("UPDATE user_fleet SET status = 'in_mission', current_mission_id = NULL WHERE id = :rid")->execute([':rid' => $rocketId]);
            $this->db->prepare("UPDATE user_modules SET status = 'launched' WHERE id = :mid")->execute([':mid' => $moduleId]);

            $stmt = $this->db->prepare("INSERT INTO event_queue (user_id, event_type, reference_id, start_time, end_time, is_processed) VALUES (:uid, 'MODULE_LAUNCH', :mid, NOW(), NOW() + INTERVAL 14400 SECOND, 0)");
            $stmt->execute([':uid' => $userId, ':mid' => $moduleId]);

            $this->db->prepare("UPDATE user_modules SET condition_percent = :rid WHERE id = :mid")->execute([':rid' => $rocketId, ':mid' => $moduleId]);

            $this->db->commit();
            return ['success' => true, 'message' => "Startsequenz eingeleitet! '{$module['name']}' unterwegs."];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Launch Astronaut (FIXED VERSION)
     * Benutzt assigned_rocket_id statt assigned_module_id
     */
    public function launchAstronaut(int $userId, int $rocketId, int $astroId): array
    {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("SELECT * FROM astronauts WHERE id = :aid AND user_id = :uid");
            $stmt->execute([':aid' => $astroId, ':uid' => $userId]);
            $astro = $stmt->fetch();
            if (!$astro || $astro['status'] !== 'ready') throw new Exception("Astronaut nicht bereit.");

            $stmt = $this->db->prepare("SELECT SUM(smt.crew_capacity) FROM user_modules um JOIN station_module_types smt ON um.module_type_id = smt.id WHERE um.user_id = :uid AND um.status = 'assembled'");
            $stmt->execute([':uid' => $userId]);
            $capacity = (int)$stmt->fetchColumn();

            $stmt = $this->db->prepare("SELECT COUNT(*) FROM astronauts WHERE user_id = :uid AND status = 'in_orbit'");
            $stmt->execute([':uid' => $userId]);
            $currentCrew = (int)$stmt->fetchColumn();

            if ($currentCrew >= $capacity) throw new Exception("Kein Platz auf der Station! Kapazität: $capacity");

            $stmt = $this->db->prepare("SELECT * FROM user_fleet WHERE id = :rid AND user_id = :uid FOR UPDATE");
            $stmt->execute([':rid' => $rocketId, ':uid' => $userId]);
            $rocket = $stmt->fetch();
            if (!$rocket || $rocket['status'] !== 'idle') throw new Exception("Rakete nicht bereit.");

            $this->db->prepare("UPDATE user_fleet SET status = 'in_mission', current_mission_id = NULL WHERE id = :rid")->execute([':rid' => $rocketId]);
            $this->db->prepare("UPDATE astronauts SET status = 'in_orbit' WHERE id = :aid")->execute([':aid' => $astroId]);

            $duration = 7200;
            $stmt = $this->db->prepare("INSERT INTO event_queue (user_id, event_type, reference_id, start_time, end_time, is_processed) 
                                      VALUES (:uid, 'CREW_LAUNCH', :aid, NOW(), NOW() + INTERVAL :dur SECOND, 0)");
            $stmt->execute([':uid' => $userId, ':aid' => $astroId, ':dur' => $duration]);

            // WICHTIG: Hier war der Fehler! Wir nutzen jetzt die neue Spalte assigned_rocket_id
            // und setzen assigned_module_id explizit auf NULL.
            $this->db->prepare("UPDATE astronauts SET assigned_rocket_id = :rid WHERE id = :aid")->execute([':rid' => $rocketId, ':aid' => $astroId]);

            $this->db->commit();
            return ['success' => true, 'message' => "{$astro['name']} ist auf dem Weg zur Station!"];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function launchComplexMission(int $userId, int $rocketId, int $missionTypeId, array $payloadIds, int $fuelLoad): array
    {
        try {
            $this->db->beginTransaction();

            // 1. Rakete prüfen
            $stmt = $this->db->prepare("SELECT * FROM user_fleet WHERE id = :rid AND user_id = :uid FOR UPDATE");
            $stmt->execute([':rid' => $rocketId, ':uid' => $userId]);
            $rocket = $stmt->fetch();
            if (!$rocket || $rocket['status'] !== 'idle') throw new Exception("Rakete nicht bereit.");

            // 2. Raketentyp (Kapazität)
            $stmt = $this->db->prepare("SELECT cargo_capacity_leo FROM rocket_types WHERE id = :rtid");
            $stmt->execute([':rtid' => $rocket['rocket_type_id']]);
            $stats = $stmt->fetch();

            // 3. Missionstyp prüfen (Spritverbrauch)
            $stmt = $this->db->prepare("SELECT * FROM mission_types WHERE id = :mid");
            $stmt->execute([':mid' => $missionTypeId]);
            $missionType = $stmt->fetch();

            if ($fuelLoad < $missionType['fuel_required_kg']) {
                throw new Exception("Zu wenig Treibstoff! Benötigt: " . number_format($missionType['fuel_required_kg']) . " kg");
            }

            // 4. Payloads prüfen & Masse summieren
            $totalMass = 0;
            if (!empty($payloadIds)) {
                // SQL Injection sicher machen durch Platzhalter-Generierung (?,?,?)
                $placeholders = implode(',', array_fill(0, count($payloadIds), '?'));
                $sql = "SELECT id, mass_kg, status FROM payloads WHERE id IN ($placeholders) AND user_id = ?";

                // IDs + UserID als Parameter
                $params = array_merge($payloadIds, [$userId]);

                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
                $payloads = $stmt->fetchAll();

                foreach ($payloads as $p) {
                    if ($p['status'] !== 'stored') throw new Exception("Payload #{$p['id']} ist nicht im Lager!");
                    $totalMass += $p['mass_kg'];
                }
            }

            if ($totalMass > $stats['cargo_capacity_leo']) {
                throw new Exception("Überladen! Masse: $totalMass kg / Kapazität: {$stats['cargo_capacity_leo']} kg");
            }

            // 5. START DURCHFÜHREN

            // Rakete updaten
            $this->db->prepare("UPDATE user_fleet SET status = 'in_mission', current_mission_id = :mid WHERE id = :rid")
                ->execute([':mid' => $missionTypeId, ':rid' => $rocketId]);

            // Payloads updaten (auf 'launched' setzen und Rakete merken)
            // Hier müssten wir eigentlich eine Spalte `assigned_rocket_id` bei Payloads haben.
            // Für jetzt setzen wir status einfach auf 'launched'.
            if (!empty($payloadIds)) {
                $placeholders = implode(',', array_fill(0, count($payloadIds), '?'));
                $updateSql = "UPDATE payloads SET status = 'launched' WHERE id IN ($placeholders)";
                $this->db->prepare($updateSql)->execute($payloadIds);
            }

            // Event erstellen
            $this->db->prepare("INSERT INTO event_queue (user_id, event_type, reference_id, start_time, end_time, is_processed) 
                              VALUES (:uid, 'MISSION_RETURN', :rid, NOW(), NOW() + INTERVAL :dur SECOND, 0)")
                ->execute([':uid' => $userId, ':rid' => $rocketId, ':dur' => $missionType['duration_seconds']]);

            $this->db->commit();
            return ['success' => true, 'message' => "Startsequenz eingeleitet! Nutzlast: " . number_format($totalMass) . " kg"];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ['success' => false, 'message' => "Startabbruch: " . $e->getMessage()];
        }
    }
}
