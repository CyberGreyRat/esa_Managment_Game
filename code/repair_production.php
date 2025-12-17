<?php
require_once 'Database.php';
$db = Database::getInstance()->getConnection();

echo "<h1>Production Repair Tool</h1>";

try {
    // 1. Drop Table
    echo "Dropping table 'production_lines'...<br>";
    $db->exec("DROP TABLE IF EXISTS production_lines");
    echo "Table dropped.<br>";

    // 2. Create Table
    echo "Creating table 'production_lines'...<br>";
    $sql = "CREATE TABLE production_lines (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        specialist_id INT NOT NULL,
        product_id INT NOT NULL,
        start_time DATETIME NOT NULL,
        end_time DATETIME NOT NULL,
        is_completed TINYINT DEFAULT 0,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (specialist_id) REFERENCES specialists(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )";
    $db->exec($sql);
    echo "Table created.<br>";

    // 2b. Reset Specialists to ensure testing is possible
    echo "Resetting specialist assignments...<br>";
    $db->exec("UPDATE specialists SET assignment_id = NULL");
    echo "Specialists reset.<br>";

    // 3. Verify Columns
    echo "<h3>Verifying Columns:</h3>";
    $stmt = $db->query("SHOW COLUMNS FROM production_lines");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($columns);
    echo "</pre>";

    $found = false;
    foreach ($columns as $col) {
        if ($col['Field'] === 'start_time') {
            $found = true;
            echo "<div style='color:green'>✔ start_time FOUND!</div>";
        }
    }

    if (!$found) {
        echo "<div style='color:red'>✘ start_time MISSING after create!</div>";
    }

} catch (Exception $e) {
    echo "<div style='color:red'>ERROR: " . $e->getMessage() . "</div>";
}
?>
