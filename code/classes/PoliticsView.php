<?php
require_once 'View.php';
require_once __DIR__ . '/../PoliticsManager.php';
require_once __DIR__ . '/../HRManager.php';

class PoliticsView extends View {
    
    public function handleAction(): ?array {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $politicsManager = new PoliticsManager();
            if (isset($_POST['action']) && $_POST['action'] === 'negotiate') {
                return $politicsManager->startNegotiation($this->userId, (int)$_POST['country_id'], (int)$_POST['specialist_id'], $_POST['topic']);
            }
        }
        return null;
    }

    public function render(): void {
        $politicsManager = new PoliticsManager();
        $hrManager = new HRManager();
        $countries = $politicsManager->getCountries($this->userId);
        $myEmployees = $hrManager->getMyEmployees($this->userId);
        ?>
        
        <h1 class="page-title">Internationale Beziehungen</h1>
        
        <div class="card">
            <div class="card-header">Diplomatie</div>
            <div class="card-body">
                <table>
                    <?php foreach ($countries as $country): $flagClass = 'flag-' . $country['flag_code']; ?>
                    <tr>
                        <td>
                            <span class="flag-icon <?= $flagClass ?>"></span>
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
                                    <option value="LOBBYING">Lobby</option>
                                </select>
                                
                                <select name="specialist_id" style="width: 100px; padding: 2px; background: #333; color: white; border: none; border-radius: 4px;">
                                    <?php foreach ($myEmployees as $emp): 
                                        if (!empty($emp['busy_until']) && strtotime($emp['busy_until']) > time()) continue;
                                    ?>
                                        <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                
                                <button class="btn btn-sm btn-action">Go</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
        <?php
    }
}
?>