-- 1. NEUE TABELLEN FÜR PROGRESSION
CREATE TABLE IF NOT EXISTS user_progression (
    user_id INT PRIMARY KEY,
    current_step_id VARCHAR(50) NOT NULL DEFAULT 'intro',
    completed_steps TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS story_steps (
    step_id VARCHAR(50) PRIMARY KEY,
    title VARCHAR(100),
    description TEXT,
    unlocks_pages TEXT,
    required_condition_type VARCHAR(50),
    required_condition_value INT,
    next_step_id VARCHAR(50)
);

-- 2. SPECIALISTS TABELLE ERWEITERN (Sicher)
-- Wir nutzen eine temporäre Prozedur, um Fehler zu vermeiden, falls Spalten schon existieren.

-- 8. Add last_active to users if not exists
CALL AddColumnIfNotExists('users', 'last_active', 'DATETIME DEFAULT CURRENT_TIMESTAMP');

DROP PROCEDURE IF EXISTS AddColumnIfNotExists;
DELIMITER //
CREATE PROCEDURE AddColumnIfNotExists(
    IN tableName VARCHAR(255),
    IN colName VARCHAR(255),
    IN colDef VARCHAR(255)
)
BEGIN
    SET @dbname = DATABASE();
    SET @count = (
        SELECT COUNT(*)
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @dbname
          AND TABLE_NAME = tableName
          AND COLUMN_NAME = colName
    );
    
    IF @count = 0 THEN
        SET @ddl = CONCAT('ALTER TABLE ', tableName, ' ADD COLUMN ', colName, ' ', colDef);
        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END //
DELIMITER ;

-- Spalten hinzufügen
CALL AddColumnIfNotExists('specialists', 'xp', 'INT DEFAULT 0');
CALL AddColumnIfNotExists('specialists', 'level', 'INT DEFAULT 1');
CALL AddColumnIfNotExists('specialists', 'budget', 'INT DEFAULT 0');

-- Aufräumen
DROP PROCEDURE AddColumnIfNotExists;

-- Typ ändern (ist meistens sicher von ENUM zu VARCHAR)
ALTER TABLE specialists MODIFY COLUMN type VARCHAR(50) NOT NULL;


-- 3. STORY DATEN EINFÜGEN
DELETE FROM story_steps;

INSERT INTO story_steps (step_id, title, description, unlocks_pages, required_condition_type, required_condition_value, next_step_id) VALUES
('intro', 'Willkommen CEO', 'Begrüßung und System-Initialisierung.', '["overview"]', 'MANUAL', 1, 'hire_hr'),
('hire_hr', 'Die Personalabteilung', 'Die HR-Abteilung ist freigeschaltet. Stellen Sie einen HR-Leiter ein, um Personal zu verwalten.', '["overview", "hr"]', 'HIRE_HR_MANAGER', 1, 'hire_research'),
('hire_research', 'Forschungsleitung', 'Wir benötigen einen wissenschaftlichen Leiter, um das Labor zu betreiben.', '["overview", "hr"]', 'HIRE_RESEARCH_HEAD', 1, 'build_lab'),
('build_lab', 'Forschung & Entwicklung', 'Bauen Sie ein Forschungslabor (Level 1), um neue Technologien zu entwickeln.', '["overview", "hr", "research"]', 'BUILD_LAB', 1, 'research_fuel'),
('research_fuel', 'Treibstoff-Forschung', 'Erforschen Sie den ersten Treibstoff.', '["overview", "hr", "research"]', 'RESEARCH_TECH', 1, 'build_pad'),
('build_pad', 'Startbahnbau', 'Bauen Sie eine Startbahn für Ihre Raketen.', '["overview", "hr", "research", "station"]', 'BUILD_PAD', 1, 'first_launch');

-- 4. INITIALE ABTEILUNGSLEITER (Markt auffüllen)
INSERT INTO specialists (name, type, skill_value, salary_cost, user_id)
SELECT 'Dr. Anna Schmidt', 'HR_Head', 50, 50000, NULL
WHERE NOT EXISTS (SELECT 1 FROM specialists WHERE type = 'HR_Head');

INSERT INTO specialists (name, type, skill_value, salary_cost, user_id)
SELECT 'Prof. John Doe', 'Research_Head', 60, 75000, NULL
WHERE NOT EXISTS (SELECT 1 FROM specialists WHERE type = 'Research_Head');

INSERT INTO specialists (name, type, skill_value, salary_cost, user_id)
SELECT 'Ing. Bob Builder', 'Construction_Head', 55, 60000, NULL
WHERE NOT EXISTS (SELECT 1 FROM specialists WHERE type = 'Construction_Head');

-- 5. USER RESET
UPDATE user_progression SET current_step_id = 'intro';
