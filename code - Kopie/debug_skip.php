<?php
session_start();
require_once 'Database.php';
require_once 'GameEngine.php'; // NEU: Engine einbinden

$userId = 1; 
$db = Database::getInstance()->getConnection();
$engine = new GameEngine(); // NEU: Engine instanziieren

try {
    // 1. ZEITREISE: Alle Events in die Vergangenheit schicken
    $sqlEvents = "UPDATE event_queue 
                  SET end_time = NOW() - INTERVAL 1 SECOND 
                  WHERE user_id = :uid 
                  AND is_processed = 0 
                  AND end_time > NOW()";
    
    $stmt = $db->prepare($sqlEvents);
    $stmt->execute([':uid' => $userId]);
    $countEvents = $stmt->rowCount();

    // 2. HR FREIGEBEN (Sofort)
    $sqlHR = "UPDATE specialists 
              SET busy_until = NOW() - INTERVAL 1 SECOND 
              WHERE user_id = :uid 
              AND busy_until > NOW()";
              
    $stmtHR = $db->prepare($sqlHR);
    $stmtHR->execute([':uid' => $userId]);
    $countHR = $stmtHR->rowCount();

    // 3. FORCE PROCESSING: Engine jetzt sofort laufen lassen!
    // Damit werden Module sofort auf 'stored' gesetzt und Missionen ausgezahlt.
    $processedMessages = $engine->processQueue($userId);

    if ($countEvents > 0 || $countHR > 0) {
        $msg = "🚀 ZEITREISE ERFOLGREICH!<br>";
        $msg .= "- $countEvents Events beschleunigt<br>";
        $msg .= "- $countHR Mitarbeiter befreit<br>";
        $msg .= "<strong>Verarbeitungsergebnisse:</strong><br>" . implode("<br>", $processedMessages);
        
        $_SESSION['flash_success'] = $msg;
    } else {
        $_SESSION['flash_error'] = "Nichts zu tun: Keine laufenden Ereignisse in der Zukunft.";
    }

} catch (Exception $e) {
    $_SESSION['flash_error'] = "Fehler bei der Zeitreise: " . $e->getMessage();
}

// Zurück zum Dashboard
header("Location: dashboard.php");
exit;
?>