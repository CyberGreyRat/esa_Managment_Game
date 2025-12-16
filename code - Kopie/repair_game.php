<?php
require_once 'Database.php';

echo "<h1>🛠️ System-Reparatur-Tool</h1>";

try {
    $db = Database::getInstance()->getConnection();

    // 1. DATENBANK-SPALTE REPARIEREN
    // Wir versuchen, den Spaltentyp von ENUM auf VARCHAR zu erzwingen.
    echo "<p>🔧 Ändere 'event_type' zu VARCHAR...</p>";
    try {
        $db->query("ALTER TABLE event_queue MODIFY COLUMN event_type VARCHAR(50) NOT NULL");
        echo "<span style='color:green'>✅ Datenbank-Struktur erfolgreich angepasst.</span><br>";
    } catch (PDOException $e) {
        echo "<span style='color:orange'>⚠️ Warnung: " . $e->getMessage() . " (Eventuell war es schon korrigiert)</span><br>";
    }

    // 2. KAPUTTE EVENTS LÖSCHEN
    // Events ohne Typ sind nutzlos und verstopfen das System.
    $stmt = $db->query("DELETE FROM event_queue WHERE event_type = '' OR event_type IS NULL");
    $deleted = $stmt->rowCount();
    echo "<p>🗑️ Gelöschte korrupte Events: <strong>$deleted</strong></p>";

    // 3. STECKENGEBLIEBENE MODULE RETTEN
    // Wir suchen Module, die 'constructing' sind, aber kein aktives Event mehr haben.
    // Wir setzen sie einfach auf 'stored' (fertig).
    echo "<p>🔧 Prüfe hängende Module...</p>";
    $sqlFixModules = "UPDATE user_modules 
                      SET status = 'stored' 
                      WHERE status = 'constructing' 
                      AND id NOT IN (SELECT reference_id FROM event_queue WHERE event_type = 'MODULE_CONSTRUCTION')";
    $stmt = $db->query($sqlFixModules);
    $fixedModules = $stmt->rowCount();
    echo "<span style='color:green'>✅ $fixedModules Module aus der Bauschleife befreit und ins Lager gelegt.</span><br>";

    // 4. STECKENGEBLIEBENE ASTRONAUTEN RETTEN
    // Das gleiche für Astronauten im Training.
    echo "<p>🔧 Prüfe hängende Astronauten...</p>";
    $sqlFixAstros = "UPDATE astronauts 
                     SET status = 'ready' 
                     WHERE status = 'training' 
                     AND id NOT IN (SELECT reference_id FROM event_queue WHERE event_type = 'ASTRO_TRAINING')";
    $stmt = $db->query($sqlFixAstros);
    $fixedAstros = $stmt->rowCount();
    echo "<span style='color:green'>✅ $fixedAstros Astronauten haben ihr Training sofort abgeschlossen.</span><br>";

    // 5. PERSONAL-KALENDER BEREINIGEN
    // Falls HR-Mitarbeiter noch als 'beschäftigt' markiert sind, obwohl das Event weg ist.
    $db->query("UPDATE specialists SET busy_until = NULL WHERE busy_until < NOW()");
    echo "<span style='color:green'>✅ Personalkalender bereinigt.</span><br>";

    echo "<hr><h2>🎉 Reparatur abgeschlossen!</h2>";
    echo "<a href='dashboard.php' style='background:#4ecca3; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>Zurück zum Dashboard</a>";

} catch (Exception $e) {
    echo "<h2 style='color:red'>Kritischer Fehler:</h2>";
    echo $e->getMessage();
}
?>