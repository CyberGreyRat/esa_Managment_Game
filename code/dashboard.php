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
// require_once 'classes/AdvisorService.php'; // Falls AdvisorService noch nicht existiert, auskommentieren

// VIEW KLASSEN LADEN
if (file_exists('classes/View.php')) require_once 'classes/View.php';
if (file_exists('classes/FleetView.php')) require_once 'classes/FleetView.php';
if (file_exists('classes/StationView.php')) require_once 'classes/StationView.php';
if (file_exists('classes/ResearchView.php')) require_once 'classes/ResearchView.php';
if (file_exists('classes/HRView.php')) require_once 'classes/HRView.php';
if (file_exists('classes/PoliticsView.php')) require_once 'classes/PoliticsView.php';
if (file_exists('classes/OverviewView.php')) require_once 'classes/OverviewView.php';
// if (file_exists('classes/MissionPlannerView.php')) require_once 'classes/MissionPlannerView.php'; // Falls vorhanden

$userId = 1; 

// Zentrale Manager
$engine = new GameEngine();
$stationManager = new StationManager(); 
// $advisor = new AdvisorService($userId); // Später aktivieren

// Engine Update
$neuigkeiten = $engine->processQueue($userId);
$activeEvents = $engine->getActiveEvents($userId);
$player = new Player($userId);

// --- ROUTING ---
$page = $_GET['page'] ?? 'overview';
$view = null;

switch ($page) {
    case 'fleet': if (class_exists('FleetView')) $view = new FleetView($userId); break;
    case 'station': if (class_exists('StationView')) $view = new StationView($userId); break;
    case 'research': if (class_exists('ResearchView')) $view = new ResearchView($userId); break;
    case 'hr': if (class_exists('HRView')) $view = new HRView($userId); break;
    case 'politics': if (class_exists('PoliticsView')) $view = new PoliticsView($userId); break;
    // case 'planner': ... (Falls MissionPlannerView existiert)
    default: if (class_exists('OverviewView')) $view = new OverviewView($userId); break;
}

// --- 2. CONTROLLER LOGIK (POST REQUESTS) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $result = ['success' => false, 'message' => 'Unbekannte Aktion'];
    
    // Zuerst View fragen
    if ($view) {
        $viewResult = $view->handleAction();
        if ($viewResult) {
            $result = $viewResult;
        }
    } 
    
    // Fallback: Globale Actions (für Views die noch nicht vollständig integriert sind oder globale Buttons)
    if ($result['message'] === 'Unbekannte Aktion') {
        // ... (Hier könnte man die alten if/else Blöcke lassen oder komplett auf Views umstellen) ...
        // Der Einfachheit halber verlassen wir uns darauf, dass die Views ihre Actions selbst handlen,
        // oder fügen hier wieder die globale Logik ein, falls nötig.
        // Für den Moment nehmen wir an, die Views machen das (siehe FleetView, etc.).
    }

    $_SESSION[($result['success'] ? 'flash_success' : 'flash_error')] = $result['message'];
    
    header("Location: " . $_SERVER['PHP_SELF'] . "?page=" . $page);
    exit;
}

// Daten für Header
$stationStats = $stationManager->getStationStats($userId); 
$stationClass = ($stationStats['module_count'] > 0 && $stationStats['total_power'] >= 0) ? 'status-online' : 'status-offline';
$stationText = ($stationStats['module_count'] > 0 && $stationStats['total_power'] >= 0) ? 'ONLINE' : 'OFFLINE';

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
    <!-- Externe CSS Datei -->
    <link rel="stylesheet" href="styles.css">
</head>
<body>

    <!-- SIDEBAR -->
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
        <div class="sidebar-footer">
            ESA ERP System v1.2<br>User: <?= htmlspecialchars($player->username) ?>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        
        <!-- HEADER -->
        <header class="header">
            <div class="kpi-bar">
                <div class="kpi-item">
                    <span class="kpi-label">Budget</span>
                    <span class="kpi-value text-success"><?= number_format($player->money, 0, ',', '.') ?> €</span>
                </div>
                <div class="kpi-item">
                    <span class="kpi-label">Wissenschaft</span>
                    <span class="kpi-value text-purple"><?= number_format($player->sciencePoints, 0, ',', '.') ?> SP</span>
                </div>
                <div class="kpi-item">
                    <span class="kpi-label">Station Status</span>
                    <span class="kpi-value">
                        <span class="badge <?= $stationClass ?>">
                            <?= $stationText ?>
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
        
        <!-- EVENTS TICKER -->
        <?php if (count($activeEvents) > 0): ?>
        <div class="events-bar">
            <span class="ticker-label">AKTIV:</span>
            <div class="event-ticker">
                <?php foreach ($activeEvents as $ev): ?>
                    <span class="ticker-item">
                        <i class="fas fa-spinner fa-spin"></i> 
                        <?php
                            // FIX: Hier nutzen wir jetzt korrekt $ev statt $event
                            $type = $ev['event_type'] ?? 'Unbekannt';
                            if ($type === 'MISSION_RETURN') echo "✈️ Mission";
                            elseif ($type === 'BUILDING_UPGRADE') echo "🏗️ Ausbau";
                            elseif (strpos($type, 'NEGOTIATION') !== false) echo "💼 Verhandlung";
                            elseif ($type === 'MODULE_CONSTRUCTION') echo "🏭 Bau";
                            elseif ($type === 'MODULE_LAUNCH') echo "🚀 START";
                            elseif ($type === 'ASTRO_TRAINING') echo "🎓 Training";
                            elseif ($type === 'CREW_LAUNCH') echo "🧑‍🚀 Flug zur Station";
                            else echo htmlspecialchars($type);
                        ?>
                        (<?= floor(($ev['seconds_remaining'] ?? 0)/60) ?>m)
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="page-container">
            
            <!-- ALERTS -->
            <?php foreach($neuigkeiten as $msg): ?> 
                <div class="alert alert-info"><i class="fas fa-info-circle"></i> <?= $msg ?></div>
            <?php endforeach; ?>
            <?php if ($successMsg): ?><div class="alert alert-success"><?= $successMsg ?></div><?php endif; ?>
            <?php if ($errorMsg): ?><div class="alert alert-error"><?= $errorMsg ?></div><?php endif; ?>

            <!-- DYNAMIC CONTENT -->
            <?php 
                if ($view) {
                    $view->render();
                } else {
                    echo "<div class='alert alert-error'>Fehler: View für '$page' konnte nicht geladen werden oder Klasse existiert nicht.</div>";
                    // Fallback Link
                    echo "<a href='?page=overview' class='btn'>Zurück zur Übersicht</a>";
                }
            ?>
        </div>
    </main>

    <a href="debug_skip.php" class="debug-btn"><i class="fas fa-fast-forward"></i> SKIP</a>

    <script>
        // Einfacher Refresh nach 60 Sekunden für "Lazy Updates"
        setTimeout(function(){ location.reload(); }, 60000);
    </script>
</body>
</html>