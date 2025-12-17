-- Add XP and Level to specialists
ALTER TABLE specialists ADD COLUMN xp INT DEFAULT 0;
ALTER TABLE specialists ADD COLUMN level INT DEFAULT 1;
ALTER TABLE specialists ADD COLUMN budget INT DEFAULT 0; -- For Dept Heads

-- Insert initial Department Heads (if not exists)
INSERT INTO specialists (name, type, skill_value, salary_cost, user_id)
SELECT 'Dr. Anna Schmidt', 'HR_Head', 50, 50000, NULL
WHERE NOT EXISTS (SELECT 1 FROM specialists WHERE type = 'HR_Head');

INSERT INTO specialists (name, type, skill_value, salary_cost, user_id)
SELECT 'Prof. John Doe', 'Research_Head', 60, 75000, NULL
WHERE NOT EXISTS (SELECT 1 FROM specialists WHERE type = 'Research_Head');

INSERT INTO specialists (name, type, skill_value, salary_cost, user_id)
SELECT 'Ing. Bob Builder', 'Construction_Head', 55, 60000, NULL
WHERE NOT EXISTS (SELECT 1 FROM specialists WHERE type = 'Construction_Head');
