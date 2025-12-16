<?php
require_once 'View.php';
require_once __DIR__ . '/../ResearchManager.php';

class ResearchView extends View {
    
    public function handleAction(): ?array {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $researchManager = new ResearchManager();
            if (isset($_POST['action']) && $_POST['action'] === 'research_tech') {
                return $researchManager->research($this->userId, (int)$_POST['tech_id']);
            }
        }
        return null;
    }

    public function render(): void {
        $researchManager = new ResearchManager();
        $techTree = $researchManager->getTechTree($this->userId);
        ?>
        
        <h1 class="page-title">Forschung & Entwicklung</h1>
        
        <div class="card">
            <div class="card-header">Technologiebaum</div>
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
                                <i class="fas fa-lock" style="color: var(--text-muted);"></i>
                            <?php endif; ?>
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