<?php
require_once 'View.php';
require_once 'MissionControl.php';
require_once 'Marketplace.php';

class FleetView extends View {
    
    // Logik: Was passiert, wenn man hier einen Knopf drückt?
    public function handleAction(): ?array {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $missionControl = new MissionControl();
            $marketplace = new Marketplace();

            if ($_POST['action'] === 'start_mission') {
                return $missionControl->startMission($this->userId, (int)$_POST['rocket_id'], (int)$_POST['mission_id']);
            } elseif ($_POST['action'] === 'buy_rocket') {
                return $marketplace->buyRocket($this->userId, (int)$_POST['rocket_type_id']);
            }
        }
        return null;
    }

    // Darstellung: Das HTML für diese Seite
    public function render(): void {
        $myFleet = $this->player->getFleet();
        $marketplace = new Marketplace();
        $rocketModels = $marketplace->getRocketTypes();
        $missionControl = new MissionControl();
        $availableMissions = $missionControl->getAvailableMissions();
        ?>
        
        <h1 class="page-title">Flotten-Kommando</h1>
        <div class="grid-2">
            <!-- Eigene Flotte -->
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
                                <?php else: ?><span class="badge badge-warn"><?= strtoupper($ship['status']) ?></span><?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <?php if ($ship['status'] == 'idle'): ?>
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
                                    <small class="text-muted">Unterwegs</small>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>

            <!-- Markt -->
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
        <?php
    }
}
?>