<?php
session_start();
require_once 'Database.php';
require_once 'GameEngine.php';

// Force use of session user ID, fallback to 1 only if really needed (or error)
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
// Debug: Log the user ID being used
error_log("debug_skip.php running for User ID: " . $userId);

$db = Database::getInstance()->getConnection();
$engine = new GameEngine();

try {
    // 1. ZEITREISE: Alle Events in die Vergangenheit schicken
    // UPDATE all active events for this user to happen 1 second ago
    $sqlEvents = "UPDATE event_queue 
                  SET end_time = NOW() - INTERVAL 1 SECOND 
                  WHERE user_id = :uid 
                  AND is_processed = 0 
                  AND end_time > NOW()";
    
    $stmt = $db->prepare($sqlEvents);
    $stmt->execute([':uid' => $userId]);
    $countEvents = $stmt->rowCount();

    // 1b. PRODUKTION BESCHLEUNIGEN (Production Lines Table)
    // Also move production_lines end_time to the past
    $stmtProd = $db->prepare("UPDATE production_lines SET end_time = NOW() - INTERVAL 1 SECOND WHERE user_id = :uid AND is_completed = 0 AND end_time > NOW()");
    $stmtProd->execute([':uid' => $userId]);
    $countProd = $stmtProd->rowCount();

    // 2. HR FREIGEBEN (Sofort)
    $sqlHR = "UPDATE specialists 
              SET busy_until = NOW() - INTERVAL 1 SECOND 
              WHERE user_id = :uid 
              AND busy_until > NOW()";
              
    $stmtHR = $db->prepare($sqlHR);
    $stmtHR->execute([':uid' => $userId]);
    $countHR = $stmtHR->rowCount();

    // 3. FORCE PROCESSING: Engine jetzt sofort laufen lassen!
    $processedMessages = $engine->processQueue($userId);

    // Build Success Message
    if ($countEvents > 0 || $countProd > 0 || $countHR > 0 || !empty($processedMessages)) {
        $msg = "🚀 ZEITREISE ERFOLGREICH! (User $userId)<br>";
        if ($countEvents > 0) $msg .= "- $countEvents Events beschleunigt<br>";
        if ($countProd > 0) $msg .= "- $countProd Produktionen abgeschlossen<br>";
        if ($countHR > 0) $msg .= "- $countHR Mitarbeiter befreit<br>";
        
        if (!empty($processedMessages)) {
            $msg .= "<strong>Ergebnisse:</strong><br>" . implode("<br>", $processedMessages);
        }
        
        $_SESSION['flash_success'] = $msg;
    } else {
        $_SESSION['flash_error'] = "Nichts zu tun: Keine laufenden Ereignisse für User $userId.";
    }

} catch (Exception $e) {
    $_SESSION['flash_error'] = "Fehler bei der Zeitreise: " . $e->getMessage();
}

// Redirect explicitly to PRODUCTION page to see results
header("Location: dashboard.php?page=production");
exit;
?>