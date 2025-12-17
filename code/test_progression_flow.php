<?php
require_once 'classes/ProgressionManager.php';
require_once 'HRManager.php';
require_once 'Database.php';

file_put_contents('debug_log.txt', "--- START ---\n");

function logg($msg)
{
    file_put_contents('debug_log.txt', $msg . "\n", FILE_APPEND);
    echo $msg . "\n";
}

$userId = 1;
$db = Database::getInstance()->getConnection();

// 1. RESET
logg("1. Resetting...");
$db->query("UPDATE user_progression SET current_step_id = 'intro' WHERE user_id = $userId");
$db->query("DELETE FROM specialists WHERE user_id = $userId");
$db->query("UPDATE user_resources SET money = 10000000 WHERE user_id = $userId");

$pm = new ProgressionManager($userId);
$step = $pm->getCurrentStep();
logg("Current Step: " . $step['current_step_id']);

// 2. ADVANCE INTRO
logg("2. Completing Intro...");
$pm->completeStep('intro');
$step = $pm->getCurrentStep();
logg("Current Step after intro: " . $step['current_step_id']);

// 3. HIRE HR HEAD
logg("3. Hiring HR Head...");
$stmt = $db->query("SELECT id FROM specialists WHERE type = 'HR_Head' AND user_id IS NULL LIMIT 1");
$hrHeadId = $stmt->fetchColumn();
if (!$hrHeadId) {
    logg("Creating new HR Head...");
    $db->query("INSERT INTO specialists (name, type, salary_cost) VALUES ('Test HR', 'HR_Head', 100)");
    $hrHeadId = $db->lastInsertId();
}
logg("Target HR Head ID: $hrHeadId");

$hrManager = new HRManager();
$res = $hrManager->hireSpecialist($userId, $hrHeadId);
logg("Hire Result: " . json_encode($res));

// DB CHECK
$stmt = $db->query("SELECT * FROM specialists WHERE id = $hrHeadId");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
logg("DB Row for ID $hrHeadId: " . json_encode($row));

// 4. CHECK PROGRESSION
logg("4. Checking Progression...");
$pm->checkProgression();
$step = $pm->getCurrentStep();
logg("Current Step after check: " . $step['current_step_id']);

if ($step['current_step_id'] === 'hire_research') {
    logg("SUCCESS");
} else {
    logg("FAIL");
}
