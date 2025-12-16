<?php
session_start(); 

require_once 'GameEngine.php';
require_once 'Player.php';
require_once 'MissionControl.php';
require_once 'BuildingManager.php'; 
require_once 'Marketplace.php'; 

$userId = 1; 

$engine = new GameEngine();
$missionControl = new MissionControl();
$buildingManager = new BuildingManager(); 
$marketplace = new Marketplace(); 

// 1. Erst Queue abarbeiten (Vergangenes)
$neuigkeiten = $engine->processQueue($userId);

// 2. Dann laufende Events holen (Zukünftiges)
$activeEvents = $engine->getActiveEvents($userId);

// Flash Messages
$errorMsg = null;
$successMsg = null;
if (isset($_SESSION['flash_success'])) { $successMsg = $_SESSION['flash_success']; unset($_SESSION['flash_success']); }
if (isset($_SESSION['flash_error'])) { $errorMsg = $_SESSION['flash_error']; unset($_SESSION['flash_error']); }

// POST Verarbeitung
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'start_mission') {
        $result = $missionControl->startMission($userId, (int)$_POST['rocket_id'], (int)$_POST['mission_id']);
    } elseif ($_POST['action'] === 'upgrade_building') {
        $result = $buildingManager->startUpgrade($userId, (int)$_POST['building_type_id']);
    } elseif ($_POST['action'] === 'buy_rocket') {
        $result = $marketplace->buyRocket($userId, (int)$_POST['rocket_type_id']);
    }

    if (isset($result)) {
        $_SESSION[($result['success'] ? 'flash_success' : 'flash_error')] = $result['message'];
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$player = new Player($userId);
$availableMissions = $missionControl->getAvailableMissions();
$myBuildings = $buildingManager->getBuildings($userId); 
$rocketModels = $marketplace->getRocketTypes(); 
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
        .resources { display: flex; gap: 20px; }
        .res-item { background: #0f3460; padding: 5px 15px; border-radius: 20px; font-weight: bold; }
        .container { padding: 20px; max-width: 1200px; margin: 0 auto; }
        .grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
        .card { background: #16213e; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .card h2 { margin-top: 0; color: #4ecca3; border-bottom: 1px solid #0f3460; padding-bottom: 10px; }
        
        /* Listen Styles */
        .fleet-list, .building-list, .market-list { list-style: none; padding: 0; }
        .fleet-item, .building-item { background: #1a1a2e; padding: 15px; margin-bottom: 15px; border-left: 4px solid #4ecca3; }
        
        /* Timer Liste */
        .timer-list { list-style: none; padding: 0; display: flex; gap: 10px; flex-wrap: wrap; }
        .timer-item { 
            background: #0f3460; 
            padding: 10px 15px; 
            border-radius: 5px; 
            border: 1px solid #4ecca3;
            flex: 1;
            min-width: 200px;
            /* Leichter Glow Effekt für aktive Timer */
            box-shadow: 0 0 5px rgba(78, 204, 163, 0.2);
        }
        .timer-time { font-size: 1.2em; font-weight: bold; color: #fff; font-family: 'Courier New', monospace; }
        .timer-label { font-size: 0.9em; color: #aaa; margin-bottom: 5px; }

        .market-item { 
            background: #1a1a2e; 
            padding: 15px; 
            margin-bottom: 10px; 
            border-left: 4px solid #f39c12; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        }

        .fleet-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .status-idle { color: #4ecca3; font-weight: bold; }
        .status-mission { color: #e94560; font-weight: bold; }
        .status-upgrading { color: #f1c40f; font-weight: bold; }
        
        .btn { background: #0f3460; color: white; border: none; padding: 8px 12px; cursor: pointer; border-radius: 4px; transition: 0.2s; }
        .btn:hover { background: #4ecca3; color: #1a1a2e; }
        .btn-upgrade { width: 100%; margin-top: 10px; background: #2c3e50; }
        .btn-upgrade:hover { background: #4ecca3; }
        .btn-buy { background: #d35400; }
        .btn-buy:hover { background: #e67e22; }
        
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 5px; font-weight: bold; }
        .alert-info { background: #4ecca3; color: #1a1a2e; }
        .alert-error { background: #e94560; color: white; }
        
        select { background: #0f3460; color: white; border: 1px solid #4ecca3; padding: 5px; border-radius: 4px; }
        .mission-control { display: flex; gap: 10px; align-items: center; margin-top: 10px; padding-top: 10px; border-top: 1px solid #333; }
        
        .building-meta, .market-meta { font-size: 0.85em; color: #aaa; margin-bottom: 5px; }
        .level-badge { background: #0f3460; padding: 2px 6px; border-radius: 4px; font-size: 0.8em; margin-left: 5px; }
        .price-tag { color: #f1c40f; font-weight: bold; }
    </style>
</head>
<body>

    <div class="navbar">
        <div class="brand">🚀 Terrae Novae Tycoon</div>
        <div class="resources">
            <div class="res-item">👨‍💼 <?= htmlspecialchars($player->username) ?></div>
            <div class="res-item">💶 <?= number_format($player->money, 0, ',', '.') ?> €</div>
            <div class="res-item">🔬 <?= $player->sciencePoints ?> SP</div>
        </div>
    </div>

    <div class="container">
        
        <?php foreach($neuigkeiten as $msg): ?>
            <div class="alert alert-info">🔔 <?= $msg ?></div>
        <?php endforeach; ?>
        <?php if ($successMsg): ?> <div class="alert alert-info">✅ <?= htmlspecialchars($successMsg) ?></div> <?php endif; ?>
        <?php if ($errorMsg): ?> <div class="alert alert-error">⚠️ <?= htmlspecialchars($errorMsg) ?></div> <?php endif; ?>


        <!-- AKTIVE PROZESSE ANZEIGE -->
        <?php if (count($activeEvents) > 0): ?>
            <div class="card" style="border: 1px solid #4ecca3;">
                <h2 style="margin-bottom: 15px;">⏳ Aktive Prozesse</h2>
                <ul class="timer-list">
                    <?php foreach ($activeEvents as $event): ?>
                        <!-- 
                           WICHTIG: data-seconds-left speichert die Zeit für JavaScript.
                           JavaScript holt sich diesen Wert und zählt ihn runter.
                        -->
                        <li class="timer-item" data-seconds-left="<?= $event['seconds_remaining'] ?>">
                            <div class="timer-label">
                                <?php 
                                    if ($event['event_type'] === 'MISSION_RETURN') echo "✈️ Mission: " . htmlspecialchars($event['rocket_name'] ?? 'Unbekannt');
                                    elseif ($event['event_type'] === 'BUILDING_UPGRADE') echo "🏗️ Ausbau: " . htmlspecialchars($event['building_name'] ?? 'Unbekannt');
                                ?>
                            </div>
                            <!-- Hier schreibt JS die Zeit rein -->
                            <div class="timer-time">Berechne...</div>
                            <div style="font-size:0.8em; color:#888;">Fertig um: <?= date('H:i', strtotime($event['end_time'])) ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>


        <div class="grid">
            <!-- LINKE SPALTE: FLOTTE & MARKT -->
            <div>
                <div class="card">
                    <h2>Raumflotte</h2>
                    <ul class="fleet-list">
                        <?php foreach ($player->getFleet() as $ship): ?>
                            <li class="fleet-item">
                                <div class="fleet-header">
                                    <div>
                                        <strong style="font-size: 1.1em"><?= htmlspecialchars($ship['rocket_name']) ?></strong> 
                                        <span style="color:#888">• <?= htmlspecialchars($ship['type_name']) ?></span>
                                        <br>
                                        <small>Kapazität: <?= number_format($ship['cargo_capacity_leo'],0,',','.') ?> kg LEO</small>
                                    </div>
                                    <div>
                                        <?php if ($ship['status'] == 'idle'): ?>
                                            <span class="status-idle">● Bereit</span>
                                        <?php else: ?>
                                            <span class="status-mission">✈️ Im Einsatz</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if ($ship['status'] == 'idle'): ?>
                                    <form method="POST" class="mission-control">
                                        <input type="hidden" name="action" value="start_mission">
                                        <input type="hidden" name="rocket_id" value="<?= $ship['id'] ?>">
                                        <label>Mission:</label>
                                        <select name="mission_id">
                                            <?php foreach ($availableMissions as $mission): ?>
                                                <option value="<?= $mission['id'] ?>">
                                                    <?= htmlspecialchars($mission['name']) ?> (<?= number_format($mission['reward_money']) ?>€)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn">🚀 Start</button>
                                    </form>
                                <?php else: ?>
                                    <div style="font-style: italic; color: #888; margin-top:5px;">Mission läuft... (Siehe oben)</div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                        <?php if (count($player->getFleet()) == 0): ?> <p>Keine Raketen im Hangar.</p> <?php endif; ?>
                    </ul>
                </div>

                <div class="card">
                    <h2 style="color: #f39c12; border-color: #f39c12;">🛒 Raumschiff-Markt</h2>
                    <ul class="market-list">
                        <?php foreach ($rocketModels as $model): ?>
                            <li class="market-item">
                                <div>
                                    <strong style="font-size: 1.1em"><?= htmlspecialchars($model['name']) ?></strong>
                                    <div class="market-meta">
                                        Hersteller: <?= htmlspecialchars($model['manufacturer']) ?><br>
                                        Nutzlast: <?= number_format($model['cargo_capacity_leo'], 0, ',', '.') ?> kg
                                    </div>
                                </div>
                                <div style="text-align: right;">
                                    <div class="price-tag"><?= number_format($model['cost'], 0, ',', '.') ?> €</div>
                                    <form method="POST" style="margin-top: 5px;">
                                        <input type="hidden" name="action" value="buy_rocket">
                                        <input type="hidden" name="rocket_type_id" value="<?= $model['id'] ?>">
                                        <button type="submit" class="btn btn-buy">Kaufen</button>
                                    </form>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <!-- RECHTE SPALTE: GEBÄUDE -->
            <div>
                <div class="card">
                    <h2>Basis Infrastruktur</h2>
                    <ul class="building-list">
                        <?php foreach ($myBuildings as $b): ?>
                            <li class="building-item">
                                <div style="display:flex; justify-content:space-between;">
                                    <strong><?= htmlspecialchars($b['name']) ?></strong>
                                    <span class="level-badge">Lvl <?= $b['current_level'] ?? 0 ?></span>
                                </div>
                                <div class="building-meta"><?= htmlspecialchars($b['description']) ?></div>
                                <?php if (isset($b['status']) && $b['status'] === 'upgrading'): ?>
                                    <div class="status-upgrading">🚧 Wird ausgebaut... (Siehe oben)</div>
                                <?php else: ?>
                                    <div style="margin-top: 8px; font-size: 0.9em;">
                                        Nächstes Level: <strong><?= number_format($b['next_cost'], 0, ',', '.') ?> €</strong><br>
                                        <span style="color:#888">Dauer: <?= gmdate("H:i:s", $b['next_time']) ?></span>
                                    </div>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="upgrade_building">
                                        <input type="hidden" name="building_type_id" value="<?= $b['type_id'] ?>">
                                        <button type="submit" class="btn btn-upgrade">⬆️ Ausbauen</button>
                                    </form>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT FÜR DIE LIVE-TIMER -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const timerElements = document.querySelectorAll('.timer-item');
            
            // Wir speichern für jeden Timer den Ziel-Zeitpunkt im Browser (Jetzt + Restsekunden)
            const timers = [];
            
            timerElements.forEach(el => {
                const secondsLeft = parseInt(el.getAttribute('data-seconds-left'));
                const display = el.querySelector('.timer-time');
                
                // Ziel-Zeitpunkt in Millisekunden
                const targetTime = new Date().getTime() + (secondsLeft * 1000);
                
                timers.push({
                    element: display,
                    target: targetTime
                });
            });

            function updateTimers() {
                const now = new Date().getTime();
                let needsReload = false;

                timers.forEach(timer => {
                    const distance = timer.target - now;

                    if (distance < 0) {
                        timer.element.innerHTML = "FERTIG!";
                        timer.element.style.color = "#4ecca3";
                        needsReload = true; // Seite neu laden, um Event zu verarbeiten
                    } else {
                        // Zeit formatieren
                        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                        
                        // Führende Nullen hinzufügen (z.B. 05s statt 5s)
                        const mStr = minutes < 10 ? "0" + minutes : minutes;
                        const sStr = seconds < 10 ? "0" + seconds : seconds;
                        
                        timer.element.innerHTML = mStr + "m " + sStr + "s";
                    }
                });

                if (needsReload) {
                    // Kurze Verzögerung, damit der User "FERTIG!" sehen kann, dann Reload
                    setTimeout(() => location.reload(), 2000);
                }
            }

            // Timer sofort einmal updaten und dann jede Sekunde
            if (timers.length > 0) {
                updateTimers();
                setInterval(updateTimers, 1000);
            }
        });
    </script>

</body>
</html>