<?php
session_start();
require_once 'Database.php';

$userId = 1; // Wir nehmen an, du bist User 1
$db = Database::getInstance()->getConnection();

try {
    // 1. Events in der Queue überspringen (Raketen, Bau, Verhandlungen)
    $sqlEvents = "UPDATE event_queue 
                  SET end_time = NOW() - INTERVAL 1 SECOND 
                  WHERE user_id = :uid 
                  AND is_processed = 0 
                  AND end_time > NOW()";
    
    $stmt = $db->prepare($sqlEvents);
    $stmt->execute([':uid' => $userId]);
    $countEvents = $stmt->rowCount();

    // 2. Mitarbeiter sofort freigeben (Kalender löschen)
    // Das hat vorher gefehlt! Wir setzen den Timer auf die Vergangenheit.
    $sqlHR = "UPDATE specialists 
              SET busy_until = NOW() - INTERVAL 1 SECOND 
              WHERE user_id = :uid 
              AND busy_until > NOW()";
              
    $stmtHR = $db->prepare($sqlHR);
    $stmtHR->execute([':uid' => $userId]);
    $countHR = $stmtHR->rowCount();

    if ($countEvents > 0 || $countHR > 0) {
        $_SESSION['flash_success'] = "🚀 ZEITREISE: $countEvents Ereignisse beendet und $countHR Mitarbeiter aus Meetings geholt!";
    } else {
        $_SESSION['flash_error'] = "Nichts zu tun: Keine laufenden Ereignisse oder beschäftigten Mitarbeiter.";
    }

} catch (Exception $e) {
    $_SESSION['flash_error'] = "Fehler bei der Zeitreise: " . $e->getMessage();
}

// Sofort zurück zum Dashboard
header("Location: dashboard.php");
exit;
?>