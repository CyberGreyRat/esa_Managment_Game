<?php
session_start(); 

require_once 'GameEngine.php';
require_once 'Player.php';
require_once 'MissionControl.php';
require_once 'BuildingManager.php'; 
require_once 'Marketplace.php'; 
require_once 'ResearchManager.php';
require_once 'HRManager.php'; 
require_once 'PoliticsManager.php'; 

$userId = 1; 

$engine = new GameEngine();
$missionControl = new MissionControl();
$buildingManager = new BuildingManager(); 
$marketplace = new Marketplace(); 
$researchManager = new ResearchManager();
$hrManager = new HRManager(); 
$politicsManager = new PoliticsManager(); 

// 1. Engine
$neuigkeiten = $engine->processQueue($userId);
$activeEvents = $engine->getActiveEvents($userId);

// 2. Pending Money
$pendingMoney = 0;
foreach ($activeEvents as $ev) {
    if ($ev['event_type'] === 'MISSION_RETURN') $pendingMoney += ($ev['reward_money'] ?? 0); 
    if ($ev['event_type'] === 'NEGOTIATION_MONEY') $pendingMoney += 3000000; 
}

// 3. POST Handling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $result = ['success' => false, 'message' => 'Unbekannte Aktion'];
    
    if ($_POST['action'] === 'start_mission') {
        $result = $missionControl->startMission($userId, (int)$_POST['rocket_id'], (int)$_POST['mission_id']);
    } elseif ($_POST['action'] === 'upgrade_building') {
        $result = $buildingManager->startUpgrade($userId, (int)$_POST['building_type_id']);
    } elseif ($_POST['action'] === 'buy_rocket') {
        $result = $marketplace->buyRocket($userId, (int)$_POST['rocket_type_id']);
    } elseif ($_POST['action'] === 'research_tech') {
        $result = $researchManager->research($userId, (int)$_POST['tech_id']);
    } elseif ($_POST['action'] === 'hire_spec') {
        $result = $hrManager->hireSpecialist($userId, (int)$_POST['spec_id']);
    } elseif ($_POST['action'] === 'negotiate') { 
        $result = $politicsManager->startNegotiation($userId, (int)$_POST['country_id'], (int)$_POST['specialist_id'], $_POST['topic']);
    }

    $_SESSION[($result['success'] ? 'flash_success' : 'flash_error')] = $result['message'];
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// 4. Daten laden
$player = new Player($userId);
$availableMissions = $missionControl->getAvailableMissions();
$myBuildings = $buildingManager->getBuildings($userId); 
$rocketModels = $marketplace->getRocketTypes(); 
$techTree = $researchManager->getTechTree($userId);
$myEmployees = $hrManager->getMyEmployees($userId);
$applicants = $hrManager->getApplicants(); 
$countries = $politicsManager->getCountries($userId); 

// Flash Messages
$errorMsg = null; $successMsg = null;
if (isset($_SESSION['flash_success'])) { $successMsg = $_SESSION['flash_success']; unset($_SESSION['flash_success']); }
if (isset($_SESSION['flash_error'])) { $errorMsg = $_SESSION['flash_error']; unset($_SESSION['flash_error']); }
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terrae Novae Tycoon</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #1a1a2e; color: #e0e0e0; margin: 0; }
        .navbar { background: #16213e; padding: 15px; display: flex; justify-content: space-between; border-bottom: 2px solid #0f3460; }
        .brand { font-weight: bold; font-size: 1.2em; color: #4ecca3; }
        .resources { display: flex; gap: 20px; align-items: center; }
        .res-item { background: #0f3460; padding: 5px 15px; border-radius: 20px; font-weight: bold; display: flex; flex-direction: column; align-items: center; justify-content: center; min-width: 100px; }
        .res-label { font-size: 0.7em; color: #aaa; text-transform: uppercase; letter-spacing: 1px; }
        .res-val { font-size: 1.1em; }
        .res-pending { font-size: 0.8em; color: #f1c40f; animation: pulse 2s infinite; }
        @keyframes pulse { 0% { opacity: 0.7; } 50% { opacity: 1; } 100% { opacity: 0.7; } }

        .container { padding: 20px; max-width: 1200px; margin: 0 auto; }
        .grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
        .card { background: #16213e; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.3); }
        .card h2 { margin-top: 0; color: #4ecca3; border-bottom: 1px solid #0f3460; padding-bottom: 10px; }
        
        .btn { background: #0f3460; color: white; border: none; padding: 8px 12px; cursor: pointer; border-radius: 4px; transition: 0.2s; }
        .btn:hover { background: #4ecca3; color: #1a1a2e; }
        .btn-buy { background: #d35400; } .btn-buy:hover { background: #e67e22; }
        .btn-research { background: #8e44ad; } .btn-research:hover { background: #9b59b6; }
        .btn-hire { background: #27ae60; } .btn-hire:hover { background: #2ecc71; }
        .btn-neg { background: #2980b9; } .btn-neg:hover { background: #3498db; }
        .btn-debug { background: #e67e22; font-size: 0.8em; padding: 5px 10px; }

        .timer-list { list-style: none; padding: 0; display: flex; gap: 10px; flex-wrap: wrap; }
        .timer-item { background: #0f3460; padding: 10px 15px; border-radius: 5px; border: 1px solid #4ecca3; flex: 1; min-width: 200px; box-shadow: 0 0 5px rgba(78, 204, 163, 0.2); }
        .timer-time { font-size: 1.2em; font-weight: bold; color: #fff; font-family: 'Courier New', monospace; }
        
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 5px; font-weight: bold; }
        .alert-info { background: #4ecca3; color: #1a1a2e; }
        .alert-error { background: #e94560; color: white; }
        
        select { background: #0f3460; color: white; border: 1px solid #4ecca3; padding: 5px; border-radius: 4px; width: 100%; margin-bottom: 5px;}
        .list-style-none { list-style: none; padding: 0; }
        .list-item { background: #1a1a2e; padding: 15px; margin-bottom: 10px; border-left: 4px solid #4ecca3; }
        
        .role-badge { font-size: 0.8em; padding: 2px 5px; border-radius: 4px; margin-left: 5px; }
        .role-scientist { background: #8e44ad; color: white; }
        .role-engineer { background: #e67e22; color: white; }
        
        .flag-icon { width: 24px; height: 16px; display: inline-block; margin-right: 8px; vertical-align: middle; border: 1px solid #555; }
        .flag-de { background: linear-gradient(to bottom, #000 33%, #D00 33%, #D00 66%, #FFCE00 66%); }
        .flag-fr { background: linear-gradient(to right, #0055A4 33%, #FFF 33%, #FFF 66%, #EF4135 66%); }
        .flag-it { background: linear-gradient(to right, #009246 33%, #FFF 33%, #FFF 66%, #CE2B37 66%); }
        .flag-us { background: #3C3B6E; } 
    </style>
</head>
<body>

    <div class="navbar">
        <div class="brand">🚀 Terrae Novae Tycoon</div>
        <div class="resources">
            <div class="res-item">
                <span class="res-label">CEO</span>
                <span class="res-val"><?= htmlspecialchars($player->username) ?></span>
            </div>
            <div class="res-item">
                <span class="res-label">Vermögen</span>
                <span class="res-val"><?= number_format($player->money, 0, ',', '.') ?> €</span>
                <?php if($pendingMoney > 0): ?>
                    <span class="res-pending">(+ <?= number_format($pendingMoney/1000000, 1, ',', '.') ?> Mio)</span>
                <?php endif; ?>
            </div>
            <div class="res-item" style="border-bottom: 3px solid #8e44ad;">
                <span class="res-label">Forschung</span>
                <span class="res-val"><?= $player->sciencePoints ?> SP</span>
            </div>
        </div>
    </div>

    <div class="container">
        
        <?php foreach($neuigkeiten as $msg): ?> <div class="alert alert-info">🔔 <?= $msg ?></div> <?php endforeach; ?>
        <?php if ($successMsg): ?> <div class="alert alert-info">✅ <?= htmlspecialchars($successMsg) ?></div> <?php endif; ?>
        <?php if ($errorMsg): ?> <div class="alert alert-error">⚠️ <?= htmlspecialchars($errorMsg) ?></div> <?php endif; ?>

        <!-- AKTIVE PROZESSE -->
        <?php if (count($activeEvents) > 0): ?>
            <div class="card" style="border: 1px solid #4ecca3;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 15px;">
                    <h2 style="margin:0; border:none; padding:0;">⏳ Aktive Operationen</h2>
                    <a href="debug_skip.php" class="btn btn-debug">⏩ Alles fertigstellen (Debug)</a>
                </div>
                <ul class="timer-list">
                    <?php foreach ($activeEvents as $event): ?>
                        <li class="timer-item" data-seconds-left="<?= $event['seconds_remaining'] ?>">
                            <div style="font-size:0.9em; color:#aaa; margin-bottom: 5px;">
                                <?php 
                                    if ($event['event_type'] === 'MISSION_RETURN') echo "✈️ Mission: " . htmlspecialchars($event['rocket_name'] ?? 'Unbekannt');
                                    elseif ($event['event_type'] === 'BUILDING_UPGRADE') echo "🏗️ Ausbau: " . htmlspecialchars($event['building_name'] ?? 'Unbekannt');
                                    elseif (strpos($event['event_type'], 'NEGOTIATION') !== false) echo "💼 Verhandlung (" . htmlspecialchars($event['country_name'] ?? '?') . ")";
                                ?>
                            </div>
                            <div class="timer-time">Berechne...</div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="grid">
            <!-- LINKE SPALTE: MANAGEMENT -->
            <div>
                <!-- FORSCHUNG (Jetzt oben links für bessere Sichtbarkeit) -->
                <div class="card" style="border-top: 4px solid #8e44ad;">
                    <h2 style="color: #9b59b6; border-color: #8e44ad;">🧬 Forschung</h2>
                    <ul class="list-style-none">
                        <?php foreach ($techTree as $tech): 
                            $statusClass = ''; $btn = '';
                            if ($tech['is_researched']) {
                                $btn = '<span style="color:#4ecca3">✅ Erforscht</span>';
                            } elseif ($tech['is_unlockable']) {
                                $btn = '<form method="POST"><input type="hidden" name="action" value="research_tech"><input type="hidden" name="tech_id" value="'.$tech['id'].'"><button class="btn btn-research">Forschen ('.$tech['cost_science_points'].' SP)</button></form>';
                            } else {
                                $btn = '<span style="color:#aaa">🔒 Benötigt: '.htmlspecialchars($tech['parent_name']).'</span>';
                            }
                        ?>
                            <li class="list-item" style="background:#0f3460; opacity:<?= $tech['is_unlockable'] || $tech['is_researched'] ? 1 : 0.6 ?>">
                                <div style="display:flex; justify-content:space-between;">
                                    <div><strong><?= htmlspecialchars($tech['name']) ?></strong><br><small><?= htmlspecialchars($tech['description']) ?></small></div>
                                    <div><?= $btn ?></div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- POLITIK & FINANZIERUNG -->
                <div class="card" style="border-top: 4px solid #2980b9;">
                    <h2 style="color: #3498db; border-color: #2980b9;">🌍 Politik & Finanzierung</h2>
                    <ul class="list-style-none">
                        <?php foreach ($countries as $country): 
                            $flagClass = 'flag-' . $country['flag_code'];
                            $availableStaff = 0;
                            foreach ($myEmployees as $emp) {
                                if (empty($emp['busy_until']) || strtotime($emp['busy_until']) < time()) $availableStaff++;
                            }
                        ?>
                            <li class="list-item" style="border-color: #3498db; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap;">
                                <div style="min-width:150px;">
                                    <span class="flag-icon <?= $flagClass ?>"></span>
                                    <strong><?= htmlspecialchars($country['name']) ?></strong>
                                    <br><small>Ruf: <?= $country['reputation'] ?>/100</small>
                                </div>
                                <div style="flex-grow:1; text-align:right; margin-top:5px;">
                                    <?php if ($availableStaff > 0): ?>
                                    <form method="POST" style="display:inline-flex; gap:5px; flex-wrap:wrap; justify-content:flex-end;">
                                        <input type="hidden" name="action" value="negotiate">
                                        <input type="hidden" name="country_id" value="<?= $country['id'] ?>">
                                        <select name="topic" style="width:auto; padding:5px;">
                                            <option value="MONEY">💰 Fördergelder</option>
                                            <option value="SCIENCE">🔬 Forschung</option>
                                            <option value="LOBBYING">🤝 Beziehungen (Ruf)</option>
                                        </select>
                                        <select name="specialist_id" style="width:auto; padding:5px;">
                                            <?php foreach ($myEmployees as $emp): 
                                                if (!empty($emp['busy_until']) && strtotime($emp['busy_until']) > time()) continue;
                                            ?>
                                                <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['name']) ?> (<?= $emp['skill_value'] ?>)</option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="btn btn-neg">Go</button>
                                    </form>
                                    <?php elseif (count($myEmployees) > 0): ?>
                                        <small style="color:#e94560;">Alle Mitarbeiter beschäftigt</small>
                                    <?php else: ?>
                                        <small style="color:#e94560;">Mitarbeiter benötigt</small>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- PERSONAL -->
                <div class="card">
                    <h2>👨‍🚀 HR & Personal</h2>
                    <ul class="list-style-none">
                        <?php foreach ($myEmployees as $emp): 
                            $isBusy = !empty($emp['busy_until']) && strtotime($emp['busy_until']) > time();
                        ?>
                            <li class="list-item" style="display:flex; justify-content:space-between; align-items:center;">
                                <div>
                                    <strong><?= htmlspecialchars($emp['name']) ?></strong>
                                    <span class="role-badge <?= $emp['type'] == 'Scientist' ? 'role-scientist' : 'role-engineer' ?>"><?= $emp['type'] ?></span>
                                    <br><small>Effekt: +<?= $emp['skill_value'] ?> <?= $emp['type']=='Scientist'?'SP/h':'Tempo' ?></small>
                                </div>
                                <div>
                                    <?php if ($isBusy): ?>
                                        <span style="color:#f1c40f; font-size:0.9em;">⏳ Beschäftigt bis <?= date('H:i', strtotime($emp['busy_until'])) ?></span>
                                    <?php else: ?>
                                        <span style="color:#4ecca3; font-size:0.9em;">● Verfügbar</span>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                        
                        <?php foreach ($applicants as $app): ?>
                            <li class="list-item" style="border-color: #27ae60; opacity: 0.8;">
                                <div style="display:flex; justify-content:space-between;">
                                    <div>
                                        <strong><?= htmlspecialchars($app['name']) ?></strong> (Bewerber)
                                        <br><small>Skill: <?= $app['skill_value'] ?> | Kosten: <?= number_format($app['salary_cost']) ?> €</small>
                                    </div>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="hire_spec">
                                        <input type="hidden" name="spec_id" value="<?= $app['id'] ?>">
                                        <button class="btn btn-hire">Einstellen</button>
                                    </form>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <!-- RECHTE SPALTE: OPERATIONEN -->
            <div>
                <!-- GEBÄUDE (Wieder da!) -->
                <div class="card">
                    <h2>Basis Infrastruktur</h2>
                    <ul class="list-style-none">
                        <?php foreach ($myBuildings as $b): ?>
                            <li class="list-item">
                                <div style="display:flex; justify-content:space-between;">
                                    <strong><?= htmlspecialchars($b['name']) ?></strong>
                                    <span>Lvl <?= $b['current_level'] ?? 0 ?></span>
                                </div>
                                <div style="font-size:0.85em; color:#aaa; margin-bottom:5px;"><?= htmlspecialchars($b['description']) ?></div>
                                
                                <?php if (isset($b['status']) && $b['status'] === 'upgrading'): ?>
                                    <div style="color:#f1c40f; font-weight:bold; margin-top:5px;">🚧 Wird ausgebaut...</div>
                                <?php else: ?>
                                    <form method="POST" style="margin-top:5px">
                                        <input type="hidden" name="action" value="upgrade_building">
                                        <input type="hidden" name="building_type_id" value="<?= $b['type_id'] ?>">
                                        <button class="btn" style="width:100%; font-size:0.8em; background:#2c3e50;">
                                            ⬆️ Ausbauen (<?= number_format($b['next_cost'],0,',','.') ?>€)
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- FLOTTE -->
                <div class="card">
                    <h2>Raumflotte & Missionen</h2>
                    <ul class="list-style-none">
                        <?php foreach ($player->getFleet() as $ship): ?>
                            <li class="list-item">
                                <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                                    <strong><?= htmlspecialchars($ship['rocket_name']) ?></strong> 
                                    <span><?php if ($ship['status'] == 'idle'): ?><span style="color:#4ecca3">● Bereit</span><?php else: ?><span style="color:#e94560">✈️ Im Einsatz</span><?php endif; ?></span>
                                </div>
                                <?php if ($ship['status'] == 'idle'): ?>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="start_mission">
                                        <input type="hidden" name="rocket_id" value="<?= $ship['id'] ?>">
                                        <select name="mission_id">
                                            <?php foreach ($availableMissions as $mission): 
                                                $spLabel = $mission['reward_science'] > 0 ? " + {$mission['reward_science']} SP" : "";
                                            ?>
                                                <option value="<?= $mission['id'] ?>">
                                                    <?= htmlspecialchars($mission['name']) ?> (<?= number_format($mission['reward_money']/1000000, 1) ?>M €<?= $spLabel ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn" style="width:100%">🚀 Starten</button>
                                    </form>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- MARKT -->
                <div class="card">
                    <h3 style="color:#f39c12; margin-top:0;">🛒 Raketen kaufen</h3>
                    <ul class="list-style-none">
                        <?php foreach ($rocketModels as $model): ?>
                            <li class="list-item" style="border-color:#f39c12">
                                <strong><?= htmlspecialchars($model['name']) ?></strong><br>
                                <small>Nutzlast: <?= $model['cargo_capacity_leo'] ?>kg</small>
                                <form method="POST" style="margin-top:5px">
                                    <input type="hidden" name="action" value="buy_rocket">
                                    <input type="hidden" name="rocket_type_id" value="<?= $model['id'] ?>">
                                    <button class="btn btn-buy" style="width:100%">Kaufen (<?= number_format($model['cost']/1000000, 1) ?>M €)</button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const timerElements = document.querySelectorAll('.timer-item');
            const timers = [];
            timerElements.forEach(el => {
                timers.push({
                    element: el.querySelector('.timer-time'),
                    target: new Date().getTime() + (parseInt(el.getAttribute('data-seconds-left')) * 1000)
                });
            });
            setInterval(() => {
                const now = new Date().getTime();
                let reload = false;
                timers.forEach(t => {
                    const dist = t.target - now;
                    if (dist < 0) { t.element.innerHTML = "FERTIG!"; reload = true; }
                    else {
                        const h = Math.floor((dist % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                        const m = Math.floor((dist % (1000 * 60 * 60)) / (1000 * 60));
                        const s = Math.floor((dist % (1000 * 60)) / 1000);
                        t.element.innerHTML = (h>0?h+'h ':'') + m + "m " + s + "s";
                    }
                });
                if (reload) setTimeout(() => location.reload(), 2000);
            }, 1000);
        });
    </script>
</body>
</html>