<?php
require_once 'GameEngine.php';
require_once 'Database.php';

// Hilfsfunktion für schöne Ausgabe
function printLog($msg) { echo "<div>" . date('H:i:s') . " - $msg</div>"; }

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Engine Test</title>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        .btn { padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin-right: 10px; }
        .btn-check { background: #28a745; }
        .log { background: #f8f9fa; padding: 15px; border: 1px solid #ddd; margin-top: 20px; }
    </style>
</head>
<body>

<h1>🛠️ Game Engine Kontrollraum</h1>

<div style="margin-bottom: 20px;">
    <!-- Wir nutzen URL-Parameter (?mode=...), um das Skript zu steuern -->
    <a href="?mode=sim" class="btn">🚀 Neue Mission simulieren (Zeitreise)</a>
    <a href="?mode=check" class="btn btn-check">🔄 Status prüfen (Nur Engine)</a>
</div>

<div class="log">
<?php
try {
    $db = Database::getInstance()->getConnection();
    $engine = new GameEngine();

    // 1. Setup (User & Rakete sicherstellen)
    $db->query("INSERT IGNORE INTO users (id, username, password_hash, email) VALUES (1, 'Elon', 'hash123', 'elon@mars.com')");
    $db->query("INSERT IGNORE INTO user_resources (user_id, money) VALUES (1, 50000)");
    $db->query("INSERT IGNORE INTO user_fleet (id, user_id, rocket_type_id, name, status) VALUES (99, 1, 1, 'Test-Rakete', 'idle')");

    // Welchen Modus haben wir?
    $mode = $_GET['mode'] ?? 'check'; // Standard ist 'check'

    if ($mode === 'sim') {
        // --- NUR WENN WIR AUF DEN BLAUEN KNOPF DRÜCKEN ---
        printLog("📝 <strong>MODUS: Simulation</strong>");
        
        // 1. Rakete auf "in_mission" setzen (Simulation)
        $db->query("UPDATE user_fleet SET status = 'in_mission' WHERE id = 99");

        // 2. Gefälschtes Event einfügen (Mission endete vor 1 Stunde)
        $stmt = $db->prepare("INSERT INTO event_queue (user_id, event_type, reference_id, start_time, end_time, is_processed) 
                              VALUES (1, 'MISSION_RETURN', 99, NOW() - INTERVAL 5 HOUR, NOW() - INTERVAL 1 HOUR, 0)");
        $stmt->execute();
        $eventId = $db->lastInsertId();
        
        printLog("Event ID $eventId erstellt. Die Rakete ist theoretisch schon zurück.");
    } else {
        printLog("🔍 <strong>MODUS: Nur Prüfung</strong> (Keine neuen Events erstellt)");
    }

    echo "<hr>";

    // --- IMMER AUSFÜHREN: ENGINE LAUFEN LASSEN ---
    printLog("⚙️ Starte GameEngine::processQueue(1)...");
    
    $nachrichten = $engine->processQueue(1);

    // ERGEBNIS PRÜFEN
    if (count($nachrichten) > 0) {
        echo "<h2 style='color:green'>Neue Ereignisse verarbeitet:</h2>";
        foreach ($nachrichten as $msg) {
            echo "<div style='background:#d4edda; color:#155724; padding:10px; border-left:5px solid #28a745; margin:5px;'>$msg</div>";
        }
    } else {
        echo "<h2 style='color:#6c757d'>Keine neuen Ereignisse.</h2>";
        echo "<p>Alles ruhig im Orbit. Klicke auf 'Neue Mission', um etwas zu starten.</p>";
    }

    echo "<hr>";

    // Aktuellen Status anzeigen
    $stmt = $db->query("SELECT money FROM user_resources WHERE user_id = 1");
    $money = $stmt->fetchColumn();
    
    $stmt = $db->query("SELECT status FROM user_fleet WHERE id = 99");
    $status = $stmt->fetchColumn();

    printLog("💰 Aktueller Kontostand: <strong>" . number_format($money, 2) . " €</strong>");
    printLog("🚀 Status Rakete #99: <strong>" . $status . "</strong>");

} catch (Exception $e) {
    echo "<p style='color:red'>Fehler: " . $e->getMessage() . "</p>";
}
?>
</div>

</body>
</html>