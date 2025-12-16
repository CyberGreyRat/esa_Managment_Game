<?php
session_start(); 

// --- 1. BOOTSTRAP & MANAGER LADEN ---
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

// Manager initialisieren
$engine = new GameEngine();
$missionControl = new MissionControl();
$buildingManager = new BuildingManager(); 
$marketplace = new Marketplace(); 
$researchManager = new ResearchManager();
$hrManager = new HRManager(); 
$politicsManager = new PoliticsManager(); 
$stationManager = new StationManager(); 
$astroManager = new AstronautManager(); 

// Engine Update (Lazy Eval)
$neuigkeiten = $engine->processQueue($userId);
$activeEvents = $engine->getActiveEvents($userId);

// --- 2. CONTROLLER LOGIK (POST REQUESTS) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $result = ['success' => false, 'message' => 'Unbekannte Aktion'];
    
    try {
        // FLOTTE & MISSIONEN
        if ($_POST['action'] === 'start_mission') {
            $result = $missionControl->startMission($userId, (int)$_POST['rocket_id'], (int)$_POST['mission_id']);
        } elseif ($_POST['action'] === 'launch_module') { 
            $result = $missionControl->launchModule($userId, (int)$_POST['rocket_id'], (int)$_POST['module_id']);
        } elseif ($_POST['action'] === 'launch_astro') { 
            $result = $missionControl->launchAstronaut($userId, (int)$_POST['rocket_id'], (int)$_POST['astro_id']);
        } 
        // MARKT & BAU
        elseif ($_POST['action'] === 'buy_rocket') {
            $result = $marketplace->buyRocket($userId, (int)$_POST['rocket_type_id']);
        } elseif ($_POST['action'] === 'upgrade_building') {
            $result = $buildingManager->startUpgrade($userId, (int)$_POST['building_type_id']);
        } elseif ($_POST['action'] === 'build_module') { 
            $result = $stationManager->constructModule($userId, (int)$_POST['module_type_id']);
        }
        // HR & POLITIK
        elseif ($_POST['action'] === 'recruit_astro') { 
            $result = $astroManager->recruitAstronaut($userId, $_POST['name']);
        } elseif ($_POST['action'] === 'hire_spec') {
            $result = $hrManager->hireSpecialist($userId, (int)$_POST['spec_id']);
        } elseif ($_POST['action'] === 'research_tech') {
            $result = $researchManager->research($userId, (int)$_POST['tech_id']);
        } elseif ($_POST['action'] === 'negotiate') { 
            $result = $politicsManager->startNegotiation($userId, (int)$_POST['country_id'], (int)$_POST['specialist_id'], $_POST['topic']);
        }
    } catch (Exception $e) {
        $result = ['success' => false, 'message' => 'Fehler: ' . $e->getMessage()];
    }

    $_SESSION[($result['success'] ? 'flash_success' : 'flash_error')] = $result['message'];
    // Redirect zur aktuellen Seite, um F5-Resubmit zu verhindern
    $page = $_GET['page'] ?? 'overview';
    header("Location: " . $_SERVER['PHP_SELF'] . "?page=" . $page);
    exit;
}

// --- 3. VIEW LOGIC (Daten laden) ---
$player = new Player($userId);
$page = $_GET['page'] ?? 'overview'; // Standard-Seite ist Übersicht

// Daten laden, die wir überall brauchen
$stationStats = $stationManager->getStationStats($userId); 
$stationColor = ($stationStats['module_count'] > 0 && $stationStats['total_power'] >= 0) ? '#27ae60' : '#e74c3c';

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
    <title>Terrae Novae ERP</title>
    <!-- Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #0f172a;
            --bg-panel: #1e293b;
            --text-main: #e2e8f0;
            --text-muted: #94a3b8;
            --primary: #3b82f6;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --border: #334155;
        }
        
        body { font-family: 'Segoe UI', sans-serif; background-color: var(--bg-dark); color: var(--text-main); margin: 0; display: flex; height: 100vh; overflow: hidden; }
        
        /* SIDEBAR */
        .sidebar { width: 260px; background: #0f172a; border-right: 1px solid var(--border); display: flex; flex-direction: column; }
        .logo-area { padding: 20px; font-size: 1.2em; font-weight: bold; color: var(--primary); border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 10px; }
        .nav-links { list-style: none; padding: 20px 0; margin: 0; flex-grow: 1; }
        .nav-links li a { display: flex; align-items: center; gap: 15px; padding: 12px 25px; color: var(--text-muted); text-decoration: none; transition: 0.2s; border-left: 3px solid transparent; }
        .nav-links li a:hover, .nav-links li a.active { background: var(--bg-panel); color: #fff; border-left-color: var(--primary); }
        .nav-links li a i { width: 20px; text-align: center; }
        
        /* MAIN CONTENT */
        .main-content { flex-grow: 1; display: flex; flex-direction: column; overflow-y: auto; background: #0b1120; }
        
        /* TOP HEADER */
        .header { background: var(--bg-panel); padding: 15px 30px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        .kpi-bar { display: flex; gap: 30px; }
        .kpi-item { display: flex; flex-direction: column; }
        .kpi-label { font-size: 0.75em; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; }
        .kpi-value { font-size: 1.1em; font-weight: bold; }
        
        /* PAGE CONTENT */
        .page-container { padding: 30px; max-width: 1400px; margin: 0 auto; width: 100%; box-sizing: border-box; }
        .page-title { font-size: 1.8em; margin-bottom: 20px; color: #fff; border-bottom: 2px solid var(--border); padding-bottom: 10px; }
        
        /* CARDS & GRID */
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .grid-3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 20px; }
        
        .card { background: var(--bg-panel); border-radius: 8px; border: 1px solid var(--border); overflow: hidden; }
        .card-header { padding: 15px 20px; background: rgba(0,0,0,0.2); border-bottom: 1px solid var(--border); font-weight: bold; display: flex; justify-content: space-between; align-items: center; }
        .card-body { padding: 20px; }
        
        /* TABLES & LISTS */
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; color: var(--text-muted); font-weight: 600; padding: 10px; border-bottom: 1px solid var(--border); font-size: 0.9em; }
        td { padding: 12px 10px; border-bottom: 1px solid var(--border); font-size: 0.95em; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        
        /* UI ELEMENTS */
        .badge { padding: 3px 8px; border-radius: 12px; font-size: 0.8em; font-weight: bold; }
        .badge-success { background: rgba(16, 185, 129, 0.2); color: var(--success); }
        .badge-warn { background: rgba(245, 158, 11, 0.2); color: var(--warning); }
        .badge-danger { background: rgba(239, 68, 68, 0.2); color: var(--danger); }
        
        .btn { background: var(--primary); color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; transition: 0.2s; font-size: 0.9em; text-decoration: none; display: inline-block;}
        .btn:hover { opacity: 0.9; }
        .btn-outline { background: transparent; border: 1px solid var(--border); color: var(--text-muted); }
        .btn-outline:hover { border-color: var(--text-main); color: var(--text-main); }
        .btn-sm { padding: 4px 10px; font-size: 0.8em; }
        
        .progress-bar { height: 6px; background: #333; border-radius: 3px; overflow: hidden; margin-top: 5px; }
        .progress-fill { height: 100%; background: var(--primary); }
        
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 5px; display: flex; align-items: center; gap: 10px; }
        .alert-info { background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.3); color: #60a5fa; }
        .alert-success { background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); color: #34d399; }
        .alert-error { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #f87171; }

        /* DEBUG BUTTON */
        .debug-btn { position: fixed; bottom: 20px; right: 20px; background: #e67e22; padding: 10px; border-radius: 50px; box-shadow: 0 4px 10px rgba(0,0,0,0.5); z-index: 1000; color: white; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

    <!-- 1. SIDEBAR NAVIGATION -->
    <aside class="sidebar">
        <div class="logo-area">
            <i class="fas fa-globe"></i> TERRAE NOVAE
        </div>
        <ul class="nav-links">
            <li><a href="?page=overview" class="<?= $page=='overview'?'active':'' ?>"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="?page=fleet" class="<?= $page=='fleet'?'active':'' ?>"><i class="fas fa-rocket"></i> Flotten-Management</a></li>
            <li><a href="?page=station" class="<?= $page=='station'?'active':'' ?>"><i class="fas fa-satellite"></i> Gateway Station</a></li>
            <li><a href="?page=research" class="<?= $page=='research'?'active':'' ?>"><i class="fas fa-microscope"></i> F&E Labor</a></li>
            <li><a href="?page=hr" class="<?= $page=='hr'?'active':'' ?>"><i class="fas fa-users"></i> HR & Personal</a></li>
            <li><a href="?page=politics" class="<?= $page=='politics'?'active':'' ?>"><i class="fas fa-handshake"></i> Politik & Budget</a></li>
        </ul>
        <div style="padding: 20px; font-size: 0.8em; color: var(--text-muted);">
            ESA ERP System v0.9<br>User: <?= htmlspecialchars($player->username) ?>
        </div>
    </aside>

    <!-- 2. MAIN CONTENT -->
    <main class="main-content">
        
        <!-- HEADER -->
        <header class="header">
            <div class="kpi-bar">
                <div class="kpi-item">
                    <span class="kpi-label">Budget</span>
                    <span class="kpi-value" style="color: var(--success);"><?= number_format($player->money, 0, ',', '.') ?> €</span>
                </div>
                <div class="kpi-item">
                    <span class="kpi-label">Wissenschaft</span>
                    <span class="kpi-value" style="color: #a855f7;"><?= number_format($player->sciencePoints, 0, ',', '.') ?> SP</span>
                </div>
                <div class="kpi-item">
                    <span class="kpi-label">Station Status</span>
                    <span class="kpi-value">
                        <span class="badge" style="background: <?= $stationColor ?>; color: #fff;">
                            <?= ($stationStats['total_power'] >= 0 && $stationStats['module_count'] > 0) ? 'ONLINE' : 'OFFLINE' ?>
                        </span>
                    </span>
                </div>
            </div>
            <div>
                <?php if (count($activeEvents) > 0): ?>
                    <span class="badge badge-warn"><i class="fas fa-clock"></i> <?= count($activeEvents) ?> Operationen aktiv</span>
                <?php endif; ?>
            </div>
        </header>

        <div class="page-container">
            
            <!-- ALERTS -->
            <?php foreach($neuigkeiten as $msg): ?> 
                <div class="alert alert-info"><i class="fas fa-info-circle"></i> <?= $msg ?></div>
            <?php endforeach; ?>
            <?php if ($successMsg): ?><div class="alert alert-success"><?= $successMsg ?></div><?php endif; ?>
            <?php if ($errorMsg): ?><div class="alert alert-error"><?= $errorMsg ?></div><?php endif; ?>


            <!-- ========================================================================= -->
            <!-- VIEW: OVERVIEW (Das "Alte" Dashboard, aber aufgeräumt) -->
            <!-- ========================================================================= -->
            <?php if ($page === 'overview'): 
                $myBuildings = $buildingManager->getBuildings($userId);
            ?>
                <h1 class="page-title">Executive Summary</h1>
                
                <div class="grid-2">
                    <!-- Events -->
                    <div class="card">
                        <div class="card-header">
                            <span><i class="fas fa-history"></i> Laufende Operationen</span>
                        </div>
                        <div class="card-body">
                            <?php if(empty($activeEvents)): ?>
                                <p style="color: var(--text-muted); text-align: center;">Keine aktiven Prozesse. Die Agentur schläft.</p>
                            <?php else: ?>
                                <table>
                                    <?php foreach($activeEvents as $ev): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($ev['event_type']) ?></strong><br>
                                            <small><?= htmlspecialchars($ev['rocket_name'] ?? $ev['building_name'] ?? $ev['country_name'] ?? $ev['module_name'] ?? 'Unbekannt') ?></small>
                                        </td>
                                        <td style="text-align: right;">
                                            <?= floor($ev['seconds_remaining'] / 60) ?> min
                                            <div class="progress-bar"><div class="progress-fill" style="width: 50%"></div></div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Gebäude -->
                    <div class="card">
                        <div class="card-header">
                            <span><i class="fas fa-city"></i> Standort Kourou (Basis)</span>
                        </div>
                        <div class="card-body">
                            <table>
                                <?php foreach ($myBuildings as $b): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($b['name']) ?></strong>
                                        <span class="badge" style="background: #333;">Lvl <?= $b['current_level'] ?></span>
                                    </td>
                                    <td style="text-align: right;">
                                        <?php if ($b['status'] == 'active'): ?>
                                            <form method="POST">
                                                <input type="hidden" name="action" value="upgrade_building">
                                                <input type="hidden" name="building_type_id" value="<?= $b['type_id'] ?>">
                                                <button class="btn btn-sm btn-outline">Ausbau (<?= number_format($b['next_cost']/1000000, 1) ?>M)</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="badge badge-warn">Wird gebaut</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>


            <!-- ========================================================================= -->
            <!-- VIEW: FLEET (Raketen Konfiguration) -->
            <!-- ========================================================================= -->
            <?php if ($page === 'fleet'): 
                $myFleet = $player->getFleet();
                $rocketModels = $marketplace->getRocketTypes();
                $availableMissions = $missionControl->getAvailableMissions();
            ?>
                <h1 class="page-title">Flotten-Kommando</h1>
                
                <div class="grid-2">
                    <!-- Aktive Flotte -->
                    <div class="card">
                        <div class="card-header">Hangar Bestand</div>
                        <div class="card-body">
                            <table>
                                <thead><tr><th>Name</th><th>Status</th><th>Aktion</th></tr></thead>
                                <?php foreach ($myFleet as $ship): ?>
                                <tr>
                                    <td>
                                        <strong style="color: var(--primary);"><?= htmlspecialchars($ship['rocket_name']) ?></strong><br>
                                        <small><?= htmlspecialchars($ship['type_name']) ?> (<?= number_format($ship['cargo_capacity_leo']) ?>kg)</small>
                                    </td>
                                    <td>
                                        <?php if ($ship['status'] == 'idle'): ?><span class="badge badge-success">BEREIT</span>
                                        <?php else: ?><span class="badge badge-warn">MISSION</span><?php endif; ?>
                                    </td>
                                    <td style="text-align: right;">
                                        <?php if ($ship['status'] == 'idle'): ?>
                                            <!-- Einfaches Missions-Menu -->
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="start_mission">
                                                <input type="hidden" name="rocket_id" value="<?= $ship['id'] ?>">
                                                <select name="mission_id" style="padding: 5px; background: #333; color: white; border: none; border-radius: 4px;">
                                                    <option value="">Kommerzielle Mission...</option>
                                                    <?php foreach ($availableMissions as $m): ?>
                                                        <option value="<?= $m['id'] ?>"><?= $m['name'] ?> (+<?= number_format($m['reward_money']/1000000,1) ?>M)</option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button class="btn btn-sm">Start</button>
                                            </form>
                                        <?php else: ?>
                                            <small class="text-muted">Im Orbit</small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    </div>

                    <!-- Beschaffung -->
                    <div class="card">
                        <div class="card-header">Beschaffung (Markt)</div>
                        <div class="card-body">
                            <table>
                                <?php foreach ($rocketModels as $model): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($model['name']) ?></strong><br>
                                        <small><?= htmlspecialchars($model['manufacturer']) ?></small>
                                    </td>
                                    <td><?= number_format($model['cargo_capacity_leo']) ?> kg</td>
                                    <td style="text-align: right;">
                                        <form method="POST">
                                            <input type="hidden" name="action" value="buy_rocket">
                                            <input type="hidden" name="rocket_type_id" value="<?= $model['id'] ?>">
                                            <button class="btn btn-sm">Kaufen (<?= number_format($model['cost']/1000000, 1) ?>M)</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>


            <!-- ========================================================================= -->
            <!-- VIEW: STATION (Orbit Management) -->
            <!-- ========================================================================= -->
            <?php if ($page === 'station'): 
                $inventory = $stationManager->getInventory($userId);
                $blueprints = $stationManager->getBlueprints($userId);
                $myFleet = $player->getFleet();
            ?>
                <h1 class="page-title">Gateway Earth Operationen</h1>
                
                <div class="grid-3">
                    <!-- Status -->
                    <div class="card">
                        <div class="card-header">Telemetrie</div>
                        <div class="card-body" style="text-align: center;">
                            <div style="font-size: 3em; margin-bottom: 10px;"><?= $stationStats['module_count'] ?></div>
                            <div style="color: var(--text-muted);">Installierte Module</div>
                            <hr style="border-color: var(--border); margin: 20px 0;">
                            <div style="display: flex; justify-content: space-between;">
                                <span>Energie:</span>
                                <span style="color: <?= $stationStats['total_power'] >= 0 ? 'var(--warning)' : 'var(--danger)' ?>">
                                    <?= $stationStats['total_power'] ?> kW
                                </span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-top: 10px;">
                                <span>Lebenserhaltung:</span>
                                <span><?= $stationStats['current_crew'] ?> / <?= $stationStats['total_crew_slots'] ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Lager / Start -->
                    <div class="card" style="grid-column: span 2;">
                        <div class="card-header">Integrations-Halle & Startvorbereitung</div>
                        <div class="card-body">
                            <table>
                                <thead><tr><th>Modul</th><th>Masse</th><th>Status</th><th>Start-Konfiguration</th></tr></thead>
                                <?php foreach ($inventory as $item): 
                                    if ($item['status'] == 'assembled') continue; 
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['name']) ?></td>
                                    <td><?= number_format($item['mass_kg']) ?> kg</td>
                                    <td><span class="badge" style="background: #444;"><?= strtoupper($item['status']) ?></span></td>
                                    <td style="text-align: right;">
                                        <?php if ($item['status'] == 'stored'): ?>
                                            <form method="POST" style="display: flex; gap: 10px; justify-content: flex-end;">
                                                <input type="hidden" name="action" value="launch_module">
                                                <input type="hidden" name="module_id" value="<?= $item['id'] ?>">
                                                
                                                <select name="rocket_id" style="padding: 5px; background: #333; color: white; border: none; border-radius: 4px;">
                                                    <option value="">Trägerrakete wählen...</option>
                                                    <?php foreach ($myFleet as $ship): 
                                                        if ($ship['status'] == 'idle' && $ship['cargo_capacity_leo'] >= $item['mass_kg']): ?>
                                                        <option value="<?= $ship['id'] ?>"><?= $ship['rocket_name'] ?></option>
                                                    <?php endif; endforeach; ?>
                                                </select>
                                                <button class="btn btn-sm" style="background: var(--danger);">LAUNCH</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    </div>

                    <!-- Fabrik -->
                    <div class="card" style="grid-column: span 3;">
                        <div class="card-header">Modul-Fertigung</div>
                        <div class="card-body">
                            <div class="grid-3">
                                <?php foreach ($blueprints as $bp): ?>
                                    <div style="background: rgba(255,255,255,0.05); padding: 15px; border-radius: 5px;">
                                        <strong><?= htmlspecialchars($bp['name']) ?></strong>
                                        <p style="font-size: 0.8em; color: var(--text-muted);"><?= htmlspecialchars($bp['description']) ?></p>
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px;">
                                            <span><?= number_format($bp['cost']/1000000, 1) ?>M €</span>
                                            <?php if ($bp['is_unlocked']): ?>
                                                <form method="POST">
                                                    <input type="hidden" name="action" value="build_module">
                                                    <input type="hidden" name="module_type_id" value="<?= $bp['id'] ?>">
                                                    <button class="btn btn-sm">Auftrag erteilen</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="badge"><i class="fas fa-lock"></i> Forschung fehlt</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>


            <!-- ========================================================================= -->
            <!-- VIEW: HR (Astronauten) -->
            <!-- ========================================================================= -->
            <?php if ($page === 'hr'): 
                $astronauts = $astroManager->getAstronauts($userId);
                $employees = $hrManager->getMyEmployees($userId);
                $myFleet = $player->getFleet();
            ?>
                <h1 class="page-title">Personalwesen</h1>
                <div class="grid-2">
                    <div class="card">
                        <div class="card-header">Astronauten</div>
                        <div class="card-body">
                            <table>
                                <?php foreach ($astronauts as $astro): ?>
                                <tr>
                                    <td>
                                        <i class="fas fa-user-astronaut"></i> <strong><?= htmlspecialchars($astro['name']) ?></strong><br>
                                        <small><?= strtoupper($astro['status']) ?></small>
                                    </td>
                                    <td style="text-align: right;">
                                        <?php if ($astro['status'] == 'ready'): ?>
                                            <form method="POST" style="display: flex; gap: 5px; justify-content: flex-end;">
                                                <input type="hidden" name="action" value="launch_astro">
                                                <input type="hidden" name="astro_id" value="<?= $astro['id'] ?>">
                                                <select name="rocket_id" style="padding: 5px; background: #333; color: white; border: none; border-radius: 4px;">
                                                    <option value="">Rakete...</option>
                                                    <?php foreach ($myFleet as $ship): if ($ship['status'] == 'idle'): ?>
                                                        <option value="<?= $ship['id'] ?>"><?= $ship['rocket_name'] ?></option>
                                                    <?php endif; endforeach; ?>
                                                </select>
                                                <button class="btn btn-sm">Starten</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid var(--border);">
                                <form method="POST" style="display: flex; gap: 10px;">
                                    <input type="hidden" name="action" value="recruit_astro">
                                    <input type="text" name="name" placeholder="Neuer Kandidat..." style="flex-grow: 1; padding: 8px; background: #333; border: 1px solid #555; color: white; border-radius: 4px;">
                                    <button class="btn btn-sm">Rekrutieren (1M)</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">Management Stab</div>
                        <div class="card-body">
                            <table>
                                <?php foreach ($employees as $emp): ?>
                                <tr>
                                    <td><?= htmlspecialchars($emp['name']) ?> <small>(<?= $emp['type'] ?>)</small></td>
                                    <td style="text-align: right;">
                                        <?php if (!empty($emp['busy_until']) && strtotime($emp['busy_until']) > time()): ?>
                                            <span class="badge badge-warn">Beschäftigt</span>
                                        <?php else: ?>
                                            <span class="badge badge-success">Verfügbar</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- ========================================================================= -->
            <!-- VIEW: POLITICS & RESEARCH -->
            <!-- ========================================================================= -->
            <?php if ($page === 'politics' || $page === 'research'): 
                $techTree = $researchManager->getTechTree($userId);
                $countries = $politicsManager->getCountries($userId);
                $myEmployees = $hrManager->getMyEmployees($userId);
            ?>
                <div class="grid-2">
                    <!-- Forschung -->
                    <div class="card">
                        <div class="card-header">Forschung & Entwicklung</div>
                        <div class="card-body">
                            <table>
                                <?php foreach ($techTree as $tech): ?>
                                <tr style="opacity: <?= $tech['is_researched'] ? 0.5 : 1 ?>">
                                    <td>
                                        <strong><?= htmlspecialchars($tech['name']) ?></strong><br>
                                        <small><?= htmlspecialchars($tech['description']) ?></small>
                                    </td>
                                    <td style="text-align: right;">
                                        <?php if ($tech['is_researched']): ?>
                                            <i class="fas fa-check" style="color: var(--success);"></i>
                                        <?php elseif ($tech['is_unlockable']): ?>
                                            <form method="POST">
                                                <input type="hidden" name="action" value="research_tech">
                                                <input type="hidden" name="tech_id" value="<?= $tech['id'] ?>">
                                                <button class="btn btn-sm" style="background: #9b59b6;"><?= $tech['cost_science_points'] ?> SP</button>
                                            </form>
                                        <?php else: ?>
                                            <i class="fas fa-lock"></i>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    </div>

                    <!-- Politik -->
                    <div class="card">
                        <div class="card-header">Diplomatie</div>
                        <div class="card-body">
                            <table>
                                <?php foreach ($countries as $country): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($country['name']) ?></strong><br>
                                        <small>Ruf: <?= $country['reputation'] ?>/100</small>
                                    </td>
                                    <td style="text-align: right;">
                                        <form method="POST" style="display: flex; gap: 5px; justify-content: flex-end;">
                                            <input type="hidden" name="action" value="negotiate">
                                            <input type="hidden" name="country_id" value="<?= $country['id'] ?>">
                                            
                                            <select name="topic" style="width: 80px; padding: 2px; background: #333; color: white; border: none; border-radius: 4px;">
                                                <option value="MONEY">Budget</option>
                                                <option value="SCIENCE">Wissen</option>
                                            </select>
                                            
                                            <select name="specialist_id" style="width: 100px; padding: 2px; background: #333; color: white; border: none; border-radius: 4px;">
                                                <?php foreach ($myEmployees as $emp): 
                                                    if (!empty($emp['busy_until']) && strtotime($emp['busy_until']) > time()) continue;
                                                ?>
                                                    <option value="<?= $emp['id'] ?>"><?= $emp['name'] ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            
                                            <button class="btn btn-sm">Go</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <a href="debug_skip.php" class="debug-btn"><i class="fas fa-fast-forward"></i> SKIP</a>

</body>
</html>