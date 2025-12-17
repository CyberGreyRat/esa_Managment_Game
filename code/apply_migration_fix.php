<?php
require_once 'Database.php';

try {
    $db = Database::getInstance()->getConnection();
    $sql = file_get_contents('migration_fix_story.sql');

    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $stmt) {
        if (!empty($stmt)) {
            $db->exec($stmt);
            echo "Executed: " . substr($stmt, 0, 50) . "...\n";
        }
    }
    echo "Story Fix Migration applied successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
