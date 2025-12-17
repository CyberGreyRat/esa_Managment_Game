<?php
require_once 'Database.php';
try {
    $db = Database::getInstance()->getConnection();
    $sql = file_get_contents('migration_fix_type.sql');
    $db->exec($sql);
    echo "Type column fixed successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
