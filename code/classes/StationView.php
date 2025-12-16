<?php
require_once 'View.php';
require_once __DIR__ . '/../StationManager.php';
require_once __DIR__ . '/../MissionControl.php';

class StationView extends View {
    
    public function handleAction(): ?array {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $stationManager = new StationManager();
            $missionControl = new MissionControl();

            if (isset($_POST['action'])) {
                if ($_POST['action'] === 'build_module') {
                    return $stationManager->constructModule($this->userId, (int)$_POST['module_type_id']);
                } elseif ($_POST['action'] === 'launch_module') {
                    return $missionControl->launchModule($this->userId, (int)$_POST['rocket_id'], (int)$_POST['module_id']);
                }
            }
        }
        return null;
    }

    public function render(): void {
        $stationManager = new StationManager();
        $stationStats = $stationManager->getStationStats($this->userId);
        $inventory = $stationManager->getInventory($this->userId);
        $blueprints = $stationManager->getBlueprints($this->userId);
        $myFleet = $this->player->getFleet();
        
        $stationColor = '#3498db'; 
        if ($stationStats['module_count'] > 0) {
            if ($stationStats['total_power'] < 0) $stationColor = '#e74c3c'; 
            else $stationColor = '#27ae60'; 
        }
        ?>
        
        <h1 class="page-title">Gateway Earth Operationen</h1>
        
        <div class="grid-3">
            <!-- Status -->
            <div class="card" style="border-top: 4px solid <?= $stationColor ?>;">
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
                                        
                                        <select name="rocket_id" style="width: 120px; padding: 5px; border-radius: 4px; border: 1px solid #444; background: #222; color: #fff;">
                                            <option value="">Trägerrakete wählen...</option>
                                            <?php foreach ($myFleet as $ship): 
                                                if ($ship['status'] == 'idle' && $ship['cargo_capacity_leo'] >= $item['mass_kg']): ?>
                                                <option value="<?= $ship['id'] ?>"><?= htmlspecialchars($ship['rocket_name']) ?></option>
                                            <?php endif; endforeach; ?>
                                        </select>
                                        <button class="btn btn-sm btn-action"><i class="fas fa-space-shuttle"></i></button>
                                    </form>
                                <?php else: ?>
                                    <span class="status-busy"><?= strtoupper($item['status']) ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>

            <!-- Konstruktion -->
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
                                            <button class="btn btn-sm btn-action">Auftrag erteilen</button>
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
        <?php
    }
}
?>