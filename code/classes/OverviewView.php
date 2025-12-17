<?php
require_once 'View.php';
require_once __DIR__ . '/../BuildingManager.php';
require_once __DIR__ . '/../StationManager.php';
require_once __DIR__ . '/AdvisorService.php';
require_once __DIR__ . '/../classes/ProgressionManager.php';

class OverviewView extends View
{

    public function handleAction(): ?array
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $buildingManager = new BuildingManager();
            $progression = new ProgressionManager($this->userId);

            if (isset($_POST['action'])) {
                if ($_POST['action'] === 'upgrade_building') {
                    return $buildingManager->startUpgrade($this->userId, (int) $_POST['building_type_id']);
                } elseif ($_POST['action'] === 'advance_intro') {
                    $progression->completeStep('intro');
                    return ['success' => true, 'message' => 'System initialisiert. Zugriff auf HR gewährt.'];
                }
            }
        }
        return null;
    }

    public function render(): void
    {
        $progression = new ProgressionManager($this->userId);
        $step = $progression->getCurrentStep();

        // --- INTRO VIEW ---
        if ($step['current_step_id'] === 'intro') {
            ?>
            <div style="max-width: 800px; margin: 50px auto; text-align: center;">
                <h1 style="font-size: 3em; margin-bottom: 20px;">Willkommen, CEO.</h1>
                <div class="card">
                    <div class="card-body" style="padding: 40px;">
                        <p style="font-size: 1.2em; line-height: 1.6;">
                            Die <strong>Terrae Novae Agency</strong> wurde gegründet, um die europäische Präsenz im All zu
                            sichern.<br>
                            Ihre Aufgabe ist es, eine vollwertige Raumstation zu errichten und die Forschung voranzutreiben.
                        </p>
                        <hr style="border-color: #444; margin: 30px 0;">
                        <p>
                            Wir haben Ihnen ein Startkapital zur Verfügung gestellt.<br>
                            Ihr erster Schritt ist der Aufbau einer funktionierenden Verwaltung.
                        </p>
                        <form method="POST" style="margin-top: 30px;">
                            <input type="hidden" name="action" value="advance_intro">
                            <button class="btn btn-lg" style="padding: 15px 40px; font-size: 1.2em;">
                                <i class="fas fa-power-off"></i> Systeme hochfahren
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php
            return;
        }

        // --- NORMAL DASHBOARD ---
        $buildingManager = new BuildingManager();
        $stationManager = new StationManager();
        $advisor = new AdvisorService($this->userId);

        $myBuildings = $buildingManager->getBuildings($this->userId);
        $stationStats = $stationManager->getStationStats($this->userId);
        $briefing = $advisor->getBriefing();
        ?>

        <h1 class="page-title">Executive Summary</h1>

        <!-- ELENA VANCE BRIEFING -->
        <div class="card" style="border-left: 4px solid var(--warning); margin-bottom: 20px;">
            <div class="card-header" style="background: rgba(245, 158, 11, 0.1);">
                <span><i class="fas fa-user-tie"></i> Elena Vance (Chief of Staff)</span>
            </div>
            <div class="card-body">
                <?php foreach ($briefing as $note): ?>
                    <div style="margin-bottom: 10px;">
                        <p><strong>"<?= htmlspecialchars($note['message']) ?>"</strong></p>
                        <?php if (isset($note['details']) && !empty($note['details'])): ?>
                            <ul style="background: rgba(0,0,0,0.2); padding: 10px 20px; border-radius: 5px;">
                                <?php foreach ($note['details'] as $item): ?>
                                    <li style="color: var(--danger);">Fehlt: <?= $item['missing'] ?>x
                                        <?= htmlspecialchars($item['component']) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($briefing)): ?>
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
                    <p>Aktuelle Crew im Orbit: <?= $stationStats['current_crew'] ?> / <?= $stationStats['total_crew_slots'] ?>
                    </p>
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
                                            <button class="btn btn-sm btn-outline">Ausbau
                                                (<?= number_format($b['next_cost'] / 1000000, 1) ?>M)</button>
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