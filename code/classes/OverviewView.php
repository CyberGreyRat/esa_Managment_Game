<?php
require_once 'View.php';
require_once __DIR__ . '/../BuildingManager.php';
require_once __DIR__ . '/../StationManager.php';
require_once __DIR__ . '/AdvisorService.php';

class OverviewView extends View {
    
    public function handleAction(): ?array {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $buildingManager = new BuildingManager();
            if (isset($_POST['action']) && $_POST['action'] === 'upgrade_building') {
                return $buildingManager->startUpgrade($this->userId, (int)$_POST['building_type_id']);
            }
        }
        return null;
    }

    public function render(): void {
        $buildingManager = new BuildingManager();
        $stationManager = new StationManager();
        $advisor = new AdvisorService($this->userId);
        
        $myBuildings = $buildingManager->getBuildings($this->userId);
        $stationStats = $stationManager->getStationStats($this->userId);
        $briefing = $advisor->getBriefing(); // Elena Vance ist zurück!
        ?>
        
        <h1 class="page-title">Executive Summary</h1>
        
        <!-- ELENA VANCE BRIEFING -->
        <div class="card" style="border-left: 4px solid var(--warning); margin-bottom: 20px;">
            <div class="card-header" style="background: rgba(245, 158, 11, 0.1);">
                <span><i class="fas fa-user-tie"></i> Elena Vance (Chief of Staff)</span>
            </div>
            <div class="card-body">
                <?php foreach($briefing as $note): ?>
                    <div style="margin-bottom: 10px;">
                        <p><strong>"<?= htmlspecialchars($note['message']) ?>"</strong></p>
                        <?php if(isset($note['details']) && !empty($note['details'])): ?>
                            <ul style="background: rgba(0,0,0,0.2); padding: 10px 20px; border-radius: 5px;">
                                <?php foreach($note['details'] as $item): ?>
                                    <li style="color: var(--danger);">Fehlt: <?= $item['missing'] ?>x <?= htmlspecialchars($item['component']) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <?php if(empty($briefing)): ?>
                    <p>"Alles ruhig, Director. Wir sind auf Kurs."</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="grid-2">
            <!-- Events -->
            <div class="card">
                <div class="card-header">
                    <span><i class="fas fa-chart-line"></i> Status Bericht</span>
                </div>
                <div class="card-body">
                    <p>Willkommen zurück, Director.</p>
                    <p>Die Station <strong>Gateway Earth</strong> besteht aus <?= $stationStats['module_count'] ?> Modulen.</p>
                    <p>Aktuelle Crew im Orbit: <?= $stationStats['current_crew'] ?> / <?= $stationStats['total_crew_slots'] ?></p>
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
        <?php
    }
}
?>