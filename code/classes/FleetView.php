<?php
require_once 'View.php';
require_once __DIR__ . '/../MissionControl.php';
require_once __DIR__ . '/../Marketplace.php';

class FleetView extends View {
    
    // Logik: Was passiert, wenn man hier einen Knopf drückt?
    public function handleAction(): ?array {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $marketplace = new Marketplace();

            // Nur noch Kauf-Aktionen hier, Starts gehen über den MissionPlanner
            if (isset($_POST['action'])) {
                if ($_POST['action'] === 'buy_rocket') {
                    return $marketplace->buyRocket($this->userId, (int)$_POST['rocket_type_id']);
                }
            }
        }
        return null;
    }

    // Darstellung: Das HTML für diese Seite
    public function render(): void {
        $myFleet = $this->player->getFleet();
        $marketplace = new Marketplace();
        $rocketModels = $marketplace->getRocketTypes();
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
                                <?php else: ?><span class="badge badge-warn"><?= strtoupper($ship['status']) ?></span><?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <?php if ($ship['status'] == 'idle'): ?>
                                    <!-- Link zum neuen Mission Planner -->
                                    <a href="?page=planner&rocket_id=<?= $ship['id'] ?>" class="btn btn-sm btn-action">Mission Planen</a>
                                <?php else: ?>
                                    <small class="text-muted">Unterwegs</small>
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
        <?php
    }
}
?>