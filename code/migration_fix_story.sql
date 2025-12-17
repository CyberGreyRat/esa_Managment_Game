-- Fix Story Steps
DELETE FROM story_steps;

INSERT INTO story_steps (step_id, title, description, unlocks_pages, required_condition_type, required_condition_value, next_step_id) VALUES
('intro', 'Willkommen CEO', 'Begrüßung und System-Initialisierung.', '["overview"]', 'MANUAL', 1, 'hire_hr'),

('hire_hr', 'Die Personalabteilung', 'Die HR-Abteilung ist freigeschaltet. Stellen Sie einen HR-Leiter ein, um Personal zu verwalten.', '["overview", "hr"]', 'HIRE_HR_MANAGER', 1, 'hire_research'),

('hire_research', 'Forschungsleitung', 'Wir benötigen einen wissenschaftlichen Leiter, um das Labor zu betreiben.', '["overview", "hr"]', 'HIRE_RESEARCH_HEAD', 1, 'build_lab'),

('build_lab', 'Forschung & Entwicklung', 'Bauen Sie ein Forschungslabor (Level 1), um neue Technologien zu entwickeln.', '["overview", "hr", "research"]', 'BUILD_LAB', 1, 'research_fuel'),

('research_fuel', 'Treibstoff-Forschung', 'Erforschen Sie den ersten Treibstoff.', '["overview", "hr", "research"]', 'RESEARCH_TECH', 1, 'build_pad'),

('build_pad', 'Startbahnbau', 'Bauen Sie eine Startbahn für Ihre Raketen.', '["overview", "hr", "research", "station"]', 'BUILD_PAD', 1, 'first_launch');

-- Reset user progression to intro for testing
UPDATE user_progression SET current_step_id = 'intro';
