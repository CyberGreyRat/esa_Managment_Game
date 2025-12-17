<?php
require_once 'Database.php';

try {
    $db = Database::getInstance()->getConnection();
    $sql = file_get_contents('migration_progression.sql');

    // Split by semicolon to execute multiple statements if PDO doesn't support multi-query directly in all drivers
    // But usually for DDL it's better to run one by one or use a robust parser. 
    // For this simple file, we can try executing it whole or splitting.
    // Let's split by semicolon but be careful about triggers/procedures (we don't have any yet).

    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $stmt) {
        if (!empty($stmt)) {
            $db->exec($stmt);
            echo "Executed: " . substr($stmt, 0, 50) . "...\n";
        }
    }
    echo "Migration applied successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
