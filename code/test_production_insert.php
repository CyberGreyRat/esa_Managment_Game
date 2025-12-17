<?php
require_once 'Database.php';
$db = Database::getInstance()->getConnection();

echo "<h1>Production Insert Test</h1>";

try {
    $userId = 1; 
    $specId = 1;
    $prodId = 1;

    echo "Attempting INSERT...<br>";
    $sql = "INSERT INTO `production_lines` (`user_id`, `specialist_id`, `product_id`, `start_time`, `end_time`, `is_completed`) 
            VALUES ($userId, $specId, $prodId, NOW(), NOW(), 0)";
    
    $db->exec($sql);
    echo "<div style='color:green'>✔ INSERT SUCCESSFUL! ID: " . $db->lastInsertId() . "</div>";

} catch (Exception $e) {
    echo "<div style='color:red'>✘ INSERT FAILED: " . $e->getMessage() . "</div>";
    
    // Debug Columns again
    $stmt = $db->query("SHOW COLUMNS FROM production_lines");
    echo "<pre>";
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    echo "</pre>";
}
?>
