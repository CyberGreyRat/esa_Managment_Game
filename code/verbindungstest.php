<?php
// Wir binden die Klasse ein
require_once 'Database.php';

echo "<h1>System-Check: Terrae Novae Tycoon</h1>";

try {
    // 1. Instanz holen (Hier wird die Verbindung im Hintergrund aufgebaut)
    $db = Database::getInstance()->getConnection();
    
    echo "<p style='color:green'>✅ Datenbank-Verbindung hergestellt.</p>";

    // 2. Ein Test-SQL-Befehl senden
    // Wir zählen, wie viele Raketentypen wir in der DB haben (sollten 3 sein aus dem Setup)
    $stmt = $db->query("SELECT COUNT(*) as anzahl FROM rocket_types");
    $result = $stmt->fetch();

    echo "<p>Anzahl definierter Raketentypen: <strong>" . $result['anzahl'] . "</strong></p>";
    
    if ($result['anzahl'] > 0) {
        echo "<p style='color:green'>✅ Zugriff auf Tabellen funktioniert.</p>";
    } else {
        echo "<p style='color:red'>❌ Tabelle leer oder nicht gefunden?</p>";
    }

} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Fehler beim Verbinden:</p>";
    echo "<pre>" . $e->getMessage() . "</pre>";
    echo "<p><em>Tipp: Prüfe \$user und \$pass in der Datei Database.php</em></p>";
}
?>