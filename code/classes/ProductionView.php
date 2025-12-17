<?php
require_once 'View.php';
require_once 'ProductionManager.php';
require_once 'ContractManager.php';
require_once __DIR__ . '/../HRManager.php';

class ProductionView extends View
{
    public function handleAction(): ?array
    {
        $pm = new ProductionManager();
        $cm = new ContractManager();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            if ($_POST['action'] === 'start_production') {
                return $pm->startProduction($this->userId, (int)$_POST['specialist_id'], (int)$_POST['product_id']);
            } elseif ($_POST['action'] === 'sell_product') {
                return $pm->sellProduct($this->userId, (int)$_POST['product_id'], (int)$_POST['amount']);
            } elseif ($_POST['action'] === 'accept_contract') {
                return $cm->acceptContract((int)$_POST['contract_id'], $this->userId);
            } elseif ($_POST['action'] === 'deliver_contract') {
                return $cm->deliverContract((int)$_POST['contract_id'], $this->userId);
            }
        }
        return null;
    }

    public function render(): void
    {
        $pm = new ProductionManager();
        $hr = new HRManager();

        $employees = $hr->getMyEmployees($this->userId);
        $products = $pm->getProducts();
        $inventory = $pm->getInventory($this->userId);
        $activeLines = $pm->getActiveProduction($this->userId);
        
        // Filter idle specialists
        $idleStaff = array_filter($employees, fn($e) => empty($e['assignment_id']));

        ?>
        <h1 class="page-title">Fertigung & Produktion</h1>

        <div class="grid-2">
            <!-- ACTIVE PRODUCTION -->
            <div class="card">
                <div class="card-header">Laufende Produktion</div>
                <div class="card-body">
                    <?php if (empty($activeLines)): ?>
                        <p>Keine aktive Fertigung.</p>
                    <?php else: ?>
                        <table class="table">
                            <thead><tr><th>Produkt</th><th>Mitarbeiter</th><th>Ende</th></tr></thead>
                            <tbody>
                            <?php foreach ($activeLines as $line): ?>
                                <tr>
                                    <td><?= htmlspecialchars($line['product_name']) ?></td>
                                    <td><?= htmlspecialchars($line['specialist_name']) ?></td>
                                    <td><?= $line['end_time'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- INVENTORY -->
            <div class="card">
                <div class="card-header">Lagerbestand</div>
                <div class="card-body">
                    <?php if (empty($inventory)): ?>
                        <p>Lager leer.</p>
                    <?php else: ?>
                        <table class="table">
                            <thead><tr><th>Produkt</th><th>Menge</th><th>Aktion</th></tr></thead>
                            <tbody>
                            <?php foreach ($inventory as $item): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($item['name']) ?></strong><br>
                                        <small><?= $item['type'] ?></small>
                                    </td>
                                    <td><?= $item['amount'] ?> Stk.</td>
                                    <td>
                                        <form method="POST" style="display:flex; gap:5px;">
                                            <input type="hidden" name="action" value="sell_product">
                                            <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                            <input type="number" name="amount" value="1" min="1" max="<?= $item['amount'] ?>" style="width:50px; background:#222; border:1px solid #444; color:#fff;">
                                            <button class="btn btn-sm">Verkaufen (<?= number_format($item['base_sale_value']) ?>€)</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- NEW PRODUCTION -->
        <div class="card mt-3">
            <div class="card-header">Neue Produktion starten</div>
            <div class="card-body">
                <form method="POST" class="row">
                    <input type="hidden" name="action" value="start_production">
                    
                    <div class="col" style="flex:1; padding: 5px;">
                        <label>Mitarbeiter wählen:</label>
                        <select name="specialist_id" style="width:100%; padding:10px; background:#222; border:1px solid #444; color:#fff;">
                            <?php foreach ($idleStaff as $staff): ?>
                                <option value="<?= $staff['id'] ?>"><?= htmlspecialchars($staff['name']) ?> (Skill: <?= $staff['skill_value'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col" style="flex:1; padding: 5px;">
                        <label>Produkt wählen:</label>
                        <select name="product_id" style="width:100%; padding:10px; background:#222; border:1px solid #444; color:#fff;">
                            <?php foreach ($products as $prod): ?>
                                <option value="<?= $prod['id'] ?>"><?= htmlspecialchars($prod['name']) ?> - Zeitbasis: 1h</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col" style="flex:0; padding: 5px; display:flex; align-items:flex-end;">
                        <button class="btn">Starten</button>
                    </div>
                </form>
            </div>
        </div>

        <?php 
            $cm = new ContractManager();
            $availableContracts = $cm->getAvailableContracts($this->userId);
            $activeContracts = $cm->getActiveContracts($this->userId);
        ?>

        <!-- CONTRACTS SYSTEM -->
        <h2 class="mt-3">Auftragsbörse & Verträge</h2>
        <div class="grid-2">
            <!-- AVAILABLE CONTRACTS -->
            <div class="card">
                <div class="card-header">Verfügbare Ausschreibungen</div>
                <div class="card-body">
                    <?php if (empty($availableContracts)): ?>
                        <p>Keine öffentlichen Ausschreibungen.</p>
                    <?php else: ?>
                        <table class="table">
                            <thead><tr><th>Kunde</th><th>Bedarf</th><th>Belohnung</th><th>Aktion</th></tr></thead>
                            <tbody>
                            <?php foreach ($availableContracts as $c): ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars($c['country_name'] ?? 'ESA') ?><br>
                                        <small class="text-error">Deadline: <?= $c['deadline'] ?></small>
                                    </td>
                                    <td><?= $c['amount_needed'] ?>x <?= htmlspecialchars($c['product_name']) ?></td>
                                    <td>
                                        <span class="text-success"><?= number_format($c['reward_money']) ?> €</span><br>
                                        <?php if($c['reward_reputation']): ?><small>+<?= $c['reward_reputation'] ?> Ruf</small><?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST">
                                            <input type="hidden" name="action" value="accept_contract">
                                            <input type="hidden" name="contract_id" value="<?= $c['id'] ?>">
                                            <button class="btn btn-sm">Annehmen</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ACTIVE CONTRACTS -->
            <div class="card">
                <div class="card-header">Laufende Verträge</div>
                <div class="card-body">
                    <?php if (empty($activeContracts)): ?>
                        <p>Keine aktiven Verträge.</p>
                    <?php else: ?>
                        <table class="table">
                            <thead><tr><th>Auftrag</th><th>Status</th><th>Liefern</th></tr></thead>
                            <tbody>
                            <?php foreach ($activeContracts as $c): ?>
                                <?php 
                                    // Check if we have enough
                                    $hasEnough = false;
                                    foreach($inventory as $inv) {
                                        if ($inv['product_id'] == $c['product_id'] && $inv['amount'] >= $c['amount_needed']) {
                                            $hasEnough = true;
                                            break;
                                        }
                                    }
                                ?>
                                <tr>
                                    <td>
                                        <strong><?= $c['amount_needed'] ?>x <?= htmlspecialchars($c['product_name']) ?></strong><br>
                                        <small><?= htmlspecialchars($c['country_name'] ?? 'ESA') ?></small>
                                    </td>
                                    <td>
                                        <div class="progress-bar-bg" style="width:100px; height:10px; display:inline-block;">
                                            <div class="progress-bar-fill" style="width:<?= $hasEnough ? '100' : '50' ?>%; background:<?= $hasEnough ? '#0f0' : '#fa0' ?>;"></div>
                                        </div>
                                    </td>
                                    <td>
                                        <form method="POST">
                                            <input type="hidden" name="action" value="deliver_contract">
                                            <input type="hidden" name="contract_id" value="<?= $c['id'] ?>">
                                            <?php if ($hasEnough): ?>
                                                <button class="btn btn-sm btn-primary">Liefern!</button>
                                            <?php else: ?>
                                                <button class="btn btn-sm" disabled style="opacity:0.5">Warten...</button>
                                            <?php endif; ?>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
}
?>
