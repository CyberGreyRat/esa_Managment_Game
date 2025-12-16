<?php
session_start();
require_once 'Database.php';

$userId = 1; // Wir nehmen an, du bist User 1
$db = Database::getInstance()->getConnection();

// Zeitreise: Alle zukünftigen Events dieses Users auf "jetzt fertig" setzen
// Wir datieren sie auf "jetzt minus 1 Sekunde", damit sie beim nächsten Check als vergangen gelten.
$sql = "UPDATE event_queue 
        SET end_time = NOW() - INTERVAL 1 SECOND 
        WHERE user_id = :uid 
        AND is_processed = 0 
        AND end_time > NOW()";

try {
    $stmt = $db->prepare($sql);
    $stmt->execute([':uid' => $userId]);
    $count = $stmt->rowCount();

    if ($count > 0) {
        $_SESSION['flash_success'] = "🚀 ZEITREISE: $count Ereignisse wurden sofort beendet!";
    } else {
        $_SESSION['flash_error'] = "Keine laufenden Ereignisse gefunden, die übersprungen werden könnten.";
    }

} catch (Exception $e) {
    $_SESSION['flash_error'] = "Fehler bei der Zeitreise: " . $e->getMessage();
}

// Sofort zurück zum Dashboard
header("Location: dashboard.php");
exit;
?>