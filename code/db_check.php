<?php
require_once 'Database.php';

echo "<h1>Datenbank Diagnose & Auto-Repair</h1>";
$db = Database::getInstance()->getConnection();

function checkTable($db, $tableName) {
    try {
        $db->query("SELECT 1 FROM $tableName LIMIT 1");
        echo "<div style='color:green'>✔ Tabelle '$tableName' existiert.</div>";
        return true;
    } catch (Exception $e) {
        echo "<div style='color:red'>✘ Tabelle '$tableName' fehlt: " . $e->getMessage() . "</div>";
        return false;
    }
}

function checkColumn($db, $tableName, $columnName, $definition) {
    try {
        $stmt = $db->query("SHOW COLUMNS FROM $tableName LIKE '$columnName'");
        if ($stmt->fetch()) {
            echo "<div style='color:green'> - Spalte '$columnName' in '$tableName' OK.</div>";
        } else {
            echo "<div style='color:orange'>⚠ Spalte '$columnName' fehlt in '$tableName'. Versuche anzulegen...</div>";
            $db->exec("ALTER TABLE $tableName ADD COLUMN $columnName $definition");
            echo "<div style='color:green'> - Spalte '$columnName' erfolgreich angelegt!</div>";
        }
    } catch (Exception $e) {
        echo "<div style='color:red'>✘ Fehler bei Spalte '$columnName': " . $e->getMessage() . "</div>";
    }
}

// 1. Check Rocket Types Table
if (!checkTable($db, 'rocket_types')) {
    echo "Lege Tabelle 'rocket_types' an...<br>";
    $sql = "CREATE TABLE rocket_types (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        cargo_capacity_leo INT NOT NULL,
        fuel_capacity INT DEFAULT 0,
        cost INT
    )";
    $db->exec($sql);
    
    $db->exec("INSERT INTO rocket_types (name, cargo_capacity_leo, fuel_capacity, cost) VALUES
        ('Ariane 6', 21600, 1000, 15000000),
        ('Falcon 9', 22800, 1200, 62000000),
        ('Starship', 150000, 5000, 100000000)");
    echo "Tabelle 'rocket_types' erstellt und befüllt.<br>";
}

// 2. Check User Fleet Columns
if (checkTable($db, 'user_fleet')) {
    checkColumn($db, 'user_fleet', 'rocket_type_id', 'INT NOT NULL DEFAULT 1');
    checkColumn($db, 'user_fleet', 'rocket_name', 'VARCHAR(100) DEFAULT "Rocket"');
    
    // Check Foreign Key
    try {
        // Simple try to add FK, if it fails it presumably exists
        $db->exec("ALTER TABLE user_fleet ADD CONSTRAINT fk_fleet_type FOREIGN KEY (rocket_type_id) REFERENCES rocket_types(id)");
        echo "Foreign Key für user_fleet angelegt.<br>";
    } catch (Exception $e) {
        // FK likely exists or error
    }
}

// 3. User Table Last Active
checkColumn($db, 'users', 'last_active', 'DATETIME DEFAULT CURRENT_TIMESTAMP');

// 4. Astronauts
if (checkTable($db, 'astronauts')) {
    checkColumn($db, 'astronauts', 'assigned_rocket_id', 'INT DEFAULT NULL');
    
    // Remove obsolete if exists
    try {
        $db->exec("ALTER TABLE astronauts DROP COLUMN assigned_module_id");
        echo "Veraltete Spalte 'assigned_module_id' entfernt.<br>";
    } catch (Exception $e) {}
}

// 5. Product System
if (!checkTable($db, 'products')) {
    echo "Lege Tabelle 'products' an...<br>";
    // Produkte, die hergestellt werden können
    $sql = "CREATE TABLE products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        type VARCHAR(50) DEFAULT 'component', -- component, end_product
        base_sale_value INT DEFAULT 100,      -- Geldwert bei Verkauf
        reputation_value INT DEFAULT 0,       -- Ruf bei Verkauf/Lieferung
        xp_on_create INT DEFAULT 10           -- XP für den Ersteller
    )";
    $db->exec($sql);
    
    // Initial Products
    $db->exec("INSERT INTO products (name, type, base_sale_value, reputation_value, xp_on_create) VALUES
        ('Drohnen-Sensor A1', 'component', 5000, 1, 25),
        ('Navigations-Chip X', 'component', 12000, 2, 50),
        ('Satelliten-Modul Beta', 'end_product', 50000, 10, 200)");
}

// 6. User Inventory
if (!checkTable($db, 'user_inventory')) {
    echo "Lege Tabelle 'user_inventory' an...<br>";
    $sql = "CREATE TABLE user_inventory (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        product_id INT NOT NULL,
        amount INT DEFAULT 0,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )";
    $db->exec($sql);
}

// 7. Production Lines (Active Jobs)
// Safety: If table exists but is missing 'start_time', drop it to recreate.
if (checkTable($db, 'production_lines')) {
    $stmt = $db->query("SHOW COLUMNS FROM production_lines LIKE 'start_time'");
    if (!$stmt->fetch()) {
        $db->exec("DROP TABLE production_lines");
        echo "Tabelle 'production_lines' war beschädigt und wurde gelöscht.<br>";
    }
}

if (!checkTable($db, 'production_lines')) {
    echo "Lege Tabelle 'production_lines' an...<br>";
    $sql = "CREATE TABLE production_lines (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        specialist_id INT NOT NULL, -- Wer baut es?
        product_id INT NOT NULL,    -- Was wird gebaut?
        start_time DATETIME NOT NULL,
        end_time DATETIME NOT NULL,
        is_completed TINYINT DEFAULT 0,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (specialist_id) REFERENCES specialists(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )";
    $db->exec($sql);
}

// 7b. Safety Check for columns (Production & Event Queue)
checkColumn($db, 'production_lines', 'start_time', 'DATETIME NOT NULL');
checkColumn($db, 'production_lines', 'end_time', 'DATETIME NOT NULL');
checkColumn($db, 'production_lines', 'is_completed', 'TINYINT DEFAULT 0');

checkColumn($db, 'event_queue', 'start_time', 'DATETIME DEFAULT CURRENT_TIMESTAMP');

// 8. Add Current Assignment to Specialists
checkColumn($db, 'specialists', 'assignment_id', 'INT DEFAULT NULL'); // Link to production_lines or mission

// 9. Contracts System & Countries
if (!checkTable($db, 'countries')) {
    echo "Lege Tabelle 'countries' an...<br>";
    $db->exec("CREATE TABLE countries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        code VARCHAR(3) NOT NULL
    )");
    $db->exec("INSERT INTO countries (name, code) VALUES ('ESA', 'ESA'), ('Deutschland', 'DE'), ('Frankreich', 'FR'), ('USA', 'US')");
}

if (!checkTable($db, 'contracts')) {
    echo "Lege Tabelle 'contracts' an...<br>";
    $sql = "CREATE TABLE contracts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        product_id INT NOT NULL,
        country_id INT,
        amount_needed INT NOT NULL,
        reward_money INT NOT NULL,
        reward_reputation INT DEFAULT 0,
        deadline DATETIME,
        status ENUM('available', 'accepted', 'completed', 'failed') DEFAULT 'available',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )";
    $db->exec($sql);
}



// 10. Seed Initial Contracts
$stmt = $db->query("SELECT COUNT(*) FROM contracts");
if ($stmt->fetchColumn() == 0) {
    // Falls keine Contracts da, 2 erstellen
    $p = $db->query("SELECT id FROM products LIMIT 1")->fetch();
    if ($p) {
        $db->exec("INSERT INTO contracts (user_id, product_id, country_id, amount_needed, reward_money, reward_reputation, deadline, status) VALUES 
                   (1, {$p['id']}, 1, 5, 30000, 3, NOW() + INTERVAL 7 DAY, 'available')");
        $db->exec("INSERT INTO contracts (user_id, product_id, country_id, amount_needed, reward_money, reward_reputation, deadline, status) VALUES 
                   (1, {$p['id']}, 2, 3, 18000, 2, NOW() + INTERVAL 5 DAY, 'available')");
        echo "✔ Initial-Aufträge (Contracts) erstellt.<br>";
    }
}


// 9. Check if Market Specialists exist
$stmt = $db->query("SELECT COUNT(*) FROM specialists WHERE user_id IS NULL");
if ($stmt->fetchColumn() == 0) {
    echo "Markt ist leer. Füge Spezialisten hinzu...<br>";
    $sql = "INSERT INTO specialists (name, type, skill_value, salary_cost, user_id) VALUES 
        ('Dr. Anna Schmidt', 'HR_Head', 50, 50000, NULL),
        ('Prof. John Doe', 'Research_Head', 60, 75000, NULL),
        ('Ing. Bob Builder', 'Construction_Head', 55, 60000, NULL),
        ('Dr. Sheldon Cooper', 'Scientist', 80, 40000, NULL),
        ('Howard Wolowitz', 'Engineer', 70, 35000, NULL)";
    $db->exec($sql);
}


echo "<h3>Diagnose abgeschlossen. Bitte Dashboard neu laden!</h3>";
?>
