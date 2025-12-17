-- Tabelle für den aktuellen Fortschritt des Spielers
CREATE TABLE IF NOT EXISTS user_progression (
    user_id INT PRIMARY KEY,
    current_step_id VARCHAR(50) NOT NULL DEFAULT 'intro',
    completed_steps TEXT, -- JSON array of completed step IDs
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabelle für die Story-Steps und deren Unlocks
CREATE TABLE IF NOT EXISTS story_steps (
    step_id VARCHAR(50) PRIMARY KEY,
    title VARCHAR(100),
    description TEXT,
    unlocks_pages TEXT, -- JSON array, e.g. ["hr", "research"]
    required_condition_type VARCHAR(50), -- e.g. "HIRE_COUNT", "BUILDING_LEVEL"
    required_condition_value INT,
    next_step_id VARCHAR(50)
);

-- Initiale Story-Daten
INSERT INTO story_steps (step_id, title, description, unlocks_pages, required_condition_type, required_condition_value, next_step_id) VALUES
('intro', 'Willkommen CEO', 'Begrüßung und Einführung. Stellen Sie Ihren ersten HR-Manager ein.', '["overview"]', 'HIRE_HR_MANAGER', 1, 'setup_hr'),
('setup_hr', 'Die Personalabteilung', 'Wir brauchen Personal. Stellen Sie einen Forschungsleiter ein.', '["overview", "hr"]', 'HIRE_RESEARCH_HEAD', 1, 'build_lab'),
('build_lab', 'Forschung & Entwicklung', 'Bauen Sie ein Forschungslabor, um neue Technologien zu entwickeln.', '["overview", "hr", "research"]', 'BUILD_LAB', 1, 'research_fuel'),
('research_fuel', 'Treibstoff-Forschung', 'Erforschen Sie den ersten Treibstoff.', '["overview", "hr", "research"]', 'RESEARCH_TECH', 1, 'build_pad'),
('build_pad', 'Startbahnbau', 'Bauen Sie eine Startbahn für Ihre Raketen.', '["overview", "hr", "research", "station"]', 'BUILD_PAD', 1, 'first_launch');

-- Update users table if needed (optional, using user_progression instead)
