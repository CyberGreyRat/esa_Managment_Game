-- DATENBANK RESET SCRIPT (KOMPLETT + ROCKET TYPES)
-- ACHTUNG: LÖSCHT ALLE DATEN!

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS users;

DROP TABLE IF EXISTS user_resources;

DROP TABLE IF EXISTS user_buildings;

DROP TABLE IF EXISTS specialists;

DROP TABLE IF EXISTS user_progression;

DROP TABLE IF EXISTS story_steps;

DROP TABLE IF EXISTS user_fleet;

DROP TABLE IF EXISTS fleet;

DROP TABLE IF EXISTS rocket_types;

DROP TABLE IF EXISTS astronauts;

DROP TABLE IF EXISTS user_modules;

DROP TABLE IF EXISTS user_reputation;

DROP TABLE IF EXISTS event_queue;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. USERS
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    last_active DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. RESOURCES
CREATE TABLE user_resources (
    user_id INT PRIMARY KEY,
    money BIGINT DEFAULT 10000000, -- 10 Mio Startkapital
    science_points INT DEFAULT 0,
    fuel INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

-- 3. BUILDINGS
CREATE TABLE user_buildings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    building_type_id INT NOT NULL,
    name VARCHAR(100),
    current_level INT DEFAULT 1,
    status VARCHAR(20) DEFAULT 'active', -- active, upgrading
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

-- 4. SPECIALISTS (HR & STAFF)
CREATE TABLE specialists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type VARCHAR(50) NOT NULL, -- Scientist, Engineer, HR_Head, etc.
    salary_cost INT DEFAULT 0,
    skill_value INT DEFAULT 0,
    avatar_image VARCHAR(50) DEFAULT 'avatar_1.png',
    user_id INT DEFAULT NULL, -- NULL = Auf dem Markt
    busy_until DATETIME DEFAULT NULL,
    xp INT DEFAULT 0,
    level INT DEFAULT 1,
    budget INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

-- 5. ROCKET TYPES (NEU)
CREATE TABLE rocket_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    cargo_capacity_leo INT NOT NULL,
    fuel_capacity INT DEFAULT 0,
    cost INT
);

INSERT INTO
    rocket_types (
        name,
        cargo_capacity_leo,
        fuel_capacity,
        cost
    )
VALUES (
        'Ariane 6',
        21600,
        1000,
        15000000
    ),
    (
        'Falcon 9',
        22800,
        1200,
        62000000
    ),
    (
        'Starship',
        150000,
        5000,
        100000000
    );

-- 6. FLEET (RAKETEN)
CREATE TABLE user_fleet (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    rocket_name VARCHAR(100),
    rocket_type_id INT NOT NULL DEFAULT 1, -- Default Ariane 6
    status VARCHAR(20) DEFAULT 'idle', -- idle, mission, maintenance
    current_mission_id INT DEFAULT NULL,
    flights_completed INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    FOREIGN KEY (rocket_type_id) REFERENCES rocket_types (id)
);

-- 7. ASTRONAUTS
CREATE TABLE astronauts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100),
    status VARCHAR(20) DEFAULT 'ready', -- ready, training, in_orbit
    assigned_rocket_id INT DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

-- 8. MODULES
CREATE TABLE user_modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    module_type_id INT NOT NULL,
    status VARCHAR(20) DEFAULT 'stored', -- stored, assembled
    condition_percent INT DEFAULT 100,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

-- 9. REPUTATION
CREATE TABLE user_reputation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    country_id INT NOT NULL,
    reputation INT DEFAULT 50,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

-- 10. EVENT QUEUE
CREATE TABLE event_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    event_type VARCHAR(50) NOT NULL,
    reference_id INT,
    end_time DATETIME NOT NULL,
    is_processed TINYINT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

-- 11. PROGRESSION SYSTEM
CREATE TABLE user_progression (
    user_id INT PRIMARY KEY,
    current_step_id VARCHAR(50) NOT NULL DEFAULT 'intro',
    completed_steps TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

CREATE TABLE story_steps (
    step_id VARCHAR(50) PRIMARY KEY,
    title VARCHAR(100),
    description TEXT,
    unlocks_pages TEXT, -- JSON Array
    required_condition_type VARCHAR(50),
    required_condition_value INT,
    next_step_id VARCHAR(50)
);

-- 12. INITIAL DATA (STORY)
INSERT INTO
    story_steps (
        step_id,
        title,
        description,
        unlocks_pages,
        required_condition_type,
        required_condition_value,
        next_step_id
    )
VALUES (
        'intro',
        'Willkommen CEO',
        'Begrüßung und System-Initialisierung.',
        '["overview"]',
        'MANUAL',
        1,
        'hire_hr'
    ),
    (
        'hire_hr',
        'Die Personalabteilung',
        'Die HR-Abteilung ist freigeschaltet. Stellen Sie einen HR-Leiter ein, um Personal zu verwalten.',
        '["overview", "hr"]',
        'HIRE_HR_MANAGER',
        1,
        'hire_research'
    ),
    (
        'hire_research',
        'Forschungsleitung',
        'Wir benötigen einen wissenschaftlichen Leiter, um das Labor zu betreiben.',
        '["overview", "hr"]',
        'HIRE_RESEARCH_HEAD',
        1,
        'build_lab'
    ),
    (
        'build_lab',
        'Forschung & Entwicklung',
        'Bauen Sie ein Forschungslabor (Level 1), um neue Technologien zu entwickeln.',
        '["overview", "hr", "research"]',
        'BUILD_LAB',
        1,
        'research_fuel'
    ),
    (
        'research_fuel',
        'Treibstoff-Forschung',
        'Erforschen Sie den ersten Treibstoff.',
        '["overview", "hr", "research"]',
        'RESEARCH_TECH',
        1,
        'build_pad'
    ),
    (
        'build_pad',
        'Startbahnbau',
        'Bauen Sie eine Startbahn für Ihre Raketen.',
        '["overview", "hr", "research", "station"]',
        'BUILD_PAD',
        1,
        'first_launch'
    );

-- 13. INITIAL DATA (MARKET SPECIALISTS)
INSERT INTO
    specialists (
        name,
        type,
        skill_value,
        salary_cost,
        user_id
    )
VALUES (
        'Dr. Anna Schmidt',
        'HR_Head',
        50,
        50000,
        NULL
    ),
    (
        'Prof. John Doe',
        'Research_Head',
        60,
        75000,
        NULL
    ),
    (
        'Ing. Bob Builder',
        'Construction_Head',
        55,
        60000,
        NULL
    ),
    (
        'Dr. Sheldon Cooper',
        'Scientist',
        80,
        40000,
        NULL
    ),
    (
        'Howard Wolowitz',
        'Engineer',
        70,
        35000,
        NULL
    );