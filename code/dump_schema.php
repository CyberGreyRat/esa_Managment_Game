<?php
require_once 'Database.php';
$db = Database::getInstance()->getConnection();
$stmt = $db->query('DESCRIBE specialists');
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo $row['Field'] . " : " . $row['Type'] . "\n";
}
