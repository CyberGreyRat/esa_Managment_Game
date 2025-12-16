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
require_once 'StationManager.php'; 
require_once 'AstronautManager.php'; 

$userId = 1; 

$engine = new GameEngine();
$missionControl = new MissionControl();
$buildingManager = new BuildingManager(); 
$marketplace = new Marketplace(); 
$researchManager = new ResearchManager();
$hrManager = new HRManager(); 
$politicsManager = new PoliticsManager(); 
$stationManager = new StationManager(); 
$astroManager = new AstronautManager(); 

$neuigkeiten = $engine->processQueue($userId);
$activeEvents = $engine->getActiveEvents($userId);

// POST Handling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $result = ['success' => false, 'message' => 'Unbekannte Aktion'];
    
    try {
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
        } elseif ($_POST['action'] === 'build_module') { 
            $result = $stationManager->constructModule($userId, (int)$_POST['module_type_id']);
        } elseif ($_POST['action'] === 'launch_module') { 
            $result = $missionControl->launchModule($userId, (int)$_POST['rocket_id'], (int)$_POST['module_id']);
        } elseif ($_POST['action'] === 'recruit_astro') { 
            $result = $astroManager->recruitAstronaut($userId, $_POST['name']);
        } elseif ($_POST['action'] === 'launch_astro') { 
            $result = $missionControl->launchAstronaut($userId, (int)$_POST['rocket_id'], (int)$_POST['astro_id']);
        }
    } catch (Exception $e) {
        $result = ['success' => false, 'message' => 'Fehler: ' . $e->getMessage()];
    }

    $_SESSION[($result['success'] ? 'flash_success' : 'flash_error')] = $result['message'];
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Daten laden
$player = new Player($userId);
$availableMissions = $missionControl->getAvailableMissions();
$myBuildings = $buildingManager->getBuildings($userId); 
$rocketModels = $marketplace->getRocketTypes(); 
$techTree = $researchManager->getTechTree($userId);
$myEmployees = $hrManager->getMyEmployees($userId);
$applicants = $hrManager->getApplicants(); 
$countries = $politicsManager->getCountries($userId); 
$blueprints = $stationManager->getBlueprints($userId); 
$inventory = $stationManager->getInventory($userId); 
$astronauts = $astroManager->getAstronauts($userId); 
$myFleet = $player->getFleet();
$stationStats = $stationManager->getStationStats($userId); 

// Status Farbe
$stationColor = '#3498db'; 
if ($stationStats['module_count'] > 0) {
    if ($stationStats['total_power'] < 0) $stationColor = '#e74c3c'; 
    else $stationColor = '#27ae60'; 
}

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
        
        .container { padding: 20px; max-width: 1200px; margin: 0 auto; }
        .grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
        .card { background: #16213e; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.3); }
        .card h2 { margin-top: 0; color: #4ecca3; border-bottom: 1px solid #0f3460; padding-bottom: 10px; }
        
        .btn { background: #0f3460; color: white; border: none; padding: 8px 12px; cursor: pointer; border-radius: 4px; transition: 0.2s; }
        .btn:hover { background: #4ecca3; color: #1a1a2e; }
        .btn-launch { background: #e94560; font-weight: bold; } .btn-launch:hover { background: #c0392b; }
        .btn-build { background: #e74c3c; } 
        .btn-neg { background: #2980b9; } 
        .btn-hire { background: #27ae60; }
        .btn-research { background: #8e44ad; }
        .btn-buy { background: #d35400; }
        .btn-astro { background: #16a085; } .btn-astro:hover { background: #1abc9c; }
        .btn-debug { background: #e67e22; font-size: 0.8em; padding: 5px 10px; }

        .timer-list { list-style: none; padding: 0; display: flex; gap: 10px; flex-wrap: wrap; }
        .timer-item { background: #0f3460; padding: 10px 15px; border-radius: 5px; border: 1px solid #4ecca3; flex: 1; min-width: 200px; }
        .timer-time { font-size: 1.2em; font-weight: bold; color: #fff; font-family: 'Courier New', monospace; }
        
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 5px; font-weight: bold; }
        .alert-info { background: #4ecca3; color: #1a1a2e; }
        .alert-error { background: #e94560; color: white; }
        
        select, input[type=text] { background: #0f3460; color: white; border: 1px solid #4ecca3; padding: 5px; border-radius: 4px; width: auto; margin-bottom: 5px;}
        .list-style-none { list-style: none; padding: 0; }
        .list-item { background: #1a1a2e; padding: 15px; margin-bottom: 10px; border-left: 4px solid #4ecca3; }
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
            <div class="res-item"><span class="res-label">Vermögen</span><span class="res-val"><?= number_format($player->money, 0, ',', '.') ?> €</span></div>
            <div class="res-item" style="border-bottom: 3px solid #8e44ad;"><span class="res-label">Forschung</span><span class="res-val"><?= $player->sciencePoints ?> SP</span></div>
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
                    <a href="debug_skip.php" class="btn" style="background:#e67e22; font-size:0.8em; padding:5px 10px;">⏩ Debug Skip</a>
                </div>
                <ul class="timer-list">
                    <?php foreach ($activeEvents as $event): ?>
                        <li class="timer-item" data-seconds-left="<?= $event['seconds_remaining'] ?>">
                            <div style="font-size:0.9em; color:#aaa; margin-bottom: 5px;">
                                <?php 
                                    if ($event['event_type'] === 'MISSION_RETURN') echo "✈️ Mission";
                                    elseif ($event['event_type'] === 'BUILDING_UPGRADE') echo "🏗️ Ausbau";
                                    elseif (strpos($event['event_type'], 'NEGOTIATION') !== false) echo "💼 Verhandlung";
                                    elseif ($event['event_type'] === 'MODULE_CONSTRUCTION') echo "🏭 Bau: " . htmlspecialchars($event['module_name'] ?? '');
                                    elseif ($event['event_type'] === 'MODULE_LAUNCH') echo "🚀 START: " . htmlspecialchars($event['module_name'] ?? '');
                                    elseif ($event['event_type'] === 'ASTRO_TRAINING') echo "🎓 Training: " . htmlspecialchars($event['astronaut_name'] ?? '');
                                    elseif ($event['event_type'] === 'CREW_LAUNCH') echo "🧑‍🚀 Flug zur Station";
                                ?>
                            </div>
                            <div class="timer-time">Berechne...</div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- STATION STATUS -->
        <div class="card" style="border-top: 4px solid <?= $stationColor ?>; background: linear-gradient(to right, #16213e, #0f3460);">
            <div style="display:flex; justify-content:space-between; align-items:center; border-bottom: 1px solid #333; padding-bottom:10px; margin-bottom:15px;">
                <h2 style="color: <?= $stationColor ?>; border:none; margin:0;">🛰️ Raumstation "Gateway Earth"</h2>
                <div style="font-weight:bold; color: <?= $stationColor ?>">
                    <?= $stationStats['module_count'] > 0 ? ($stationStats['total_power'] >= 0 ? "✅ ONLINE" : "⚠️ OFFLINE (Strom!)") : "🚧 Noch nicht gebaut" ?>
                </div>
            </div>
            
            <div style="display:flex; justify-content:space-around; text-align:center;">
                <div>
                    <div style="font-size:2em; font-weight:bold; color:white;"><?= $stationStats['module_count'] ?></div>
                    <div style="color:#aaa;">Module</div>
                </div>
                <div>
                    <div style="font-size:2em; font-weight:bold; color:<?= $stationStats['total_power'] >= 0 ? '#f1c40f' : '#e74c3c' ?>;"><?= $stationStats['total_power'] ?> kW</div>
                    <div style="color:#aaa;">Energie</div>
                </div>
                <div>
                    <div style="font-size:2em; font-weight:bold; color:<?= $stationStats['current_crew'] <= $stationStats['total_crew_slots'] ? '#e74c3c' : '#f1c40f' ?>;">
                        <?= $stationStats['current_crew'] ?> / <?= $stationStats['total_crew_slots'] ?>
                    </div>
                    <div style="color:#aaa;">Crew Belegung</div>
                </div>
            </div>
        </div>

        <div class="grid">
            <!-- LINKE SPALTE -->
            <div>
                <!-- ASTRONAUTEN -->
                <div class="card" style="border-top: 4px solid #16a085;">
                    <h2 style="color: #1abc9c; border-color: #16a085;">👩‍🚀 Astronauten-Corps</h2>
                    <ul class="list-style-none">
                        <?php foreach ($astronauts as $astro): ?>
                            <li class="list-item" style="border-color: #16a085; display:flex; justify-content:space-between; align-items:center;">
                                <div>
                                    <strong><?= htmlspecialchars($astro['name']) ?></strong>
                                    <br><small>Status: <?= strtoupper($astro['status']) ?></small>
                                </div>
                                <div>
                                    <?php if ($astro['status'] == 'in_orbit'): ?> <span style="color:#4ecca3">🛰️ Im All</span>
                                    <?php elseif ($astro['status'] == 'training'): ?> <span style="color:#f1c40f">🎓 Training</span>
                                    <?php elseif ($astro['status'] == 'ready'): ?>
                                        <form method="POST" style="display:inline-flex; gap:5px;">
                                            <input type="hidden" name="action" value="launch_astro">
                                            <input type="hidden" name="astro_id" value="<?= $astro['id'] ?>">
                                            <select name="rocket_id" style="width:120px; padding:2px;">
                                                <option value="">-- Rakete --</option>
                                                <?php foreach ($myFleet as $ship): if ($ship['status'] == 'idle'): ?>
                                                    <option value="<?= $ship['id'] ?>"><?= htmlspecialchars($ship['rocket_name']) ?></option>
                                                <?php endif; endforeach; ?>
                                            </select>
                                            <button class="btn btn-astro">Starten</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <form method="POST" style="margin-top:15px; border-top:1px solid #333; padding-top:10px;">
                        <input type="hidden" name="action" value="recruit_astro">
                        <input type="text" name="name" placeholder="Name des Kandidaten" required>
                        <button class="btn btn-astro">Rekrutieren (1 Mio €)</button>
                    </form>
                </div>

                <!-- FABRIK & LAGER -->
                <div class="card" style="border-top: 4px solid #e74c3c;">
                    <h2 style="color: #e74c3c; border-color: #c0392b;">🏭 Modul-Fabrik</h2>
                    <ul class="list-style-none">
                        <?php foreach ($blueprints as $bp): ?>
                            <li class="list-item" style="border-color: #e74c3c; display:flex; justify-content:space-between; align-items:center;">
                                <div><strong><?= htmlspecialchars($bp['name']) ?></strong><br><small>Masse: <?= number_format($bp['mass_kg']) ?> kg</small></div>
                                <div style="text-align:right">
                                    <div style="font-weight:bold;"><?= number_format($bp['cost']/1000000, 1) ?>M €</div>
                                    <?php if ($bp['is_unlocked']): ?>
                                        <form method="POST"><input type="hidden" name="action" value="build_module"><input type="hidden" name="module_type_id" value="<?= $bp['id'] ?>"><button class="btn btn-build">Bauen</button></form>
                                    <?php else: ?><span style="color:#aaa;">🔒</span><?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <?php if (count($inventory) > 0): ?>
                    <h3 style="margin-top:20px; border-top:1px solid #333; padding-top:10px;">📦 Lager & Station</h3>
                    <ul class="list-style-none">
                        <?php foreach ($inventory as $item): ?>
                            <li class="list-item" style="border-color: #95a5a6;">
                                <div style="display:flex; flex-direction:column;">
                                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                                        <div>
                                            <strong><?= htmlspecialchars($item['name']) ?></strong>
                                            <br><small>Status: <span style="color:white; font-weight:bold;"><?= strtoupper($item['status']) ?></span> | <?= number_format($item['mass_kg']) ?> kg</small>
                                        </div>
                                        
                                        <?php if ($item['status'] === 'assembled'): ?>
                                            <span style="color:#4ecca3; font-size:1.5em;">🛰️</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- LAUNCH BEREICH -->
                                    <?php if ($item['status'] === 'stored'): ?>
                                        <?php 
                                            $compatibleRockets = [];
                                            foreach ($myFleet as $ship) {
                                                if ($ship['status'] == 'idle' && $ship['cargo_capacity_leo'] >= $item['mass_kg']) {
                                                    $compatibleRockets[] = $ship;
                                                }
                                            }
                                        ?>
                                        
                                        <?php if (count($compatibleRockets) > 0): ?>
                                            <form method="POST" style="display:flex; gap:5px; margin-top:5px; background:#0f3460; padding:10px; border-radius:5px;">
                                                <input type="hidden" name="action" value="launch_module">
                                                <input type="hidden" name="module_id" value="<?= $item['id'] ?>">
                                                <select name="rocket_id" style="width:100%; padding:5px; border:1px solid #4ecca3;">
                                                    <?php foreach ($compatibleRockets as $ship): ?>
                                                        <option value="<?= $ship['id'] ?>"><?= htmlspecialchars($ship['rocket_name']) ?> (<?= number_format($ship['cargo_capacity_leo']) ?>kg)</option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button class="btn btn-launch">STARTEN</button>
                                            </form>
                                        <?php else: ?>
                                            <div style="color:#e94560; font-size:0.9em; background:#300; padding:5px; border-radius:3px;">
                                                ⚠️ Keine passende Rakete bereit! 
                                                <br>Benötigt: <?= number_format($item['mass_kg']) ?>kg Kapazität.
                                            </div>
                                        <?php endif; ?>
                                        
                                    <?php elseif ($item['status'] === 'constructing'): ?>
                                        <div style="color:#f1c40f; font-size:0.8em;">🚧 Wird gefertigt... Bitte warten.</div>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>

            <!-- RECHTE SPALTE -->
            <div>
                <!-- POLITIK -->
                <div class="card" style="border-top: 4px solid #2980b9;">
                    <h2 style="color: #3498db; border-color: #2980b9;">🌍 Politik</h2>
                    <ul class="list-style-none">
                        <?php foreach ($countries as $country): $flagClass = 'flag-' . $country['flag_code']; ?>
                            <li class="list-item" style="border-color: #3498db; display:flex; justify-content:space-between; align-items:center;">
                                <div><span class="flag-icon <?= $flagClass ?>"></span> <strong><?= htmlspecialchars($country['name']) ?></strong></div>
                                <div>
                                    <form method="POST" style="display:inline-flex; gap:5px;">
                                        <input type="hidden" name="action" value="negotiate">
                                        <input type="hidden" name="country_id" value="<?= $country['id'] ?>">
                                        <select name="topic" style="width:auto; padding:5px;"><option value="MONEY">💰</option><option value="SCIENCE">🔬</option></select>
                                        <select name="specialist_id" style="width:auto; padding:5px;">
                                            <?php foreach ($myEmployees as $emp): if (!empty($emp['busy_until']) && strtotime($emp['busy_until']) > time()) continue; ?>
                                                <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="btn btn-neg">Go</button>
                                    </form>
                                </div>
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
                                            <?php foreach ($availableMissions as $mission): ?>
                                                <option value="<?= $mission['id'] ?>"><?= htmlspecialchars($mission['name']) ?> (<?= number_format($mission['reward_money']/1000000, 1) ?>M €)</option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn" style="width:100%">🚀 Starten</button>
                                    </form>
                                <?php endif; ?>
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