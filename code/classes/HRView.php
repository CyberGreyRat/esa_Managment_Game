<?php
require_once 'View.php';
require_once __DIR__ . '/../HRManager.php';
require_once __DIR__ . '/../AstronautManager.php';
require_once __DIR__ . '/../MissionControl.php';

class HRView extends View {
    
    public function handleAction(): ?array {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $hrManager = new HRManager();
            $astroManager = new AstronautManager();
            $missionControl = new MissionControl();

            if (isset($_POST['action'])) {
                if ($_POST['action'] === 'recruit_astro') {
                    return $astroManager->recruitAstronaut($this->userId, $_POST['name']);
                } elseif ($_POST['action'] === 'hire_spec') {
                    return $hrManager->hireSpecialist($this->userId, (int)$_POST['spec_id']);
                } elseif ($_POST['action'] === 'launch_astro') {
                    return $missionControl->launchAstronaut($this->userId, (int)$_POST['rocket_id'], (int)$_POST['astro_id']);
                }
            }
        }
        return null;
    }

    public function render(): void {
        $astroManager = new AstronautManager();
        $hrManager = new HRManager();
        
        $astronauts = $astroManager->getAstronauts($this->userId);
        $employees = $hrManager->getMyEmployees($this->userId);
        $applicants = $hrManager->getApplicants();
        $myFleet = $this->player->getFleet();
        ?>
        
        <h1 class="page-title">Personalwesen</h1>
        <div class="grid-2">
            <div class="card">
                <div class="card-header">Astronauten Corps</div>
                <div class="card-body">
                    <table>
                        <?php foreach ($astronauts as $astro): ?>
                        <tr>
                            <td>
                                <i class="fas fa-user-astronaut"></i> <strong><?= htmlspecialchars($astro['name']) ?></strong><br>
                                <small><?= strtoupper($astro['status']) ?></small>
                            </td>
                            <td style="text-align: right;">
                                <?php if ($astro['status'] == 'ready'): ?>
                                    <form method="POST" style="display: flex; gap: 5px; justify-content: flex-end;">
                                        <input type="hidden" name="action" value="launch_astro">
                                        <input type="hidden" name="astro_id" value="<?= $astro['id'] ?>">
                                        <select name="rocket_id" style="width: 100px; padding: 5px; border-radius: 4px; border: 1px solid #444; background: #222; color: #fff;">
                                            <option value="">Rakete...</option>
                                            <?php foreach ($myFleet as $ship): if ($ship['status'] == 'idle'): ?>
                                                <option value="<?= $ship['id'] ?>"><?= htmlspecialchars($ship['rocket_name']) ?></option>
                                            <?php endif; endforeach; ?>
                                        </select>
                                        <button class="btn btn-sm btn-action"><i class="fas fa-upload"></i></button>
                                    </form>
                                <?php elseif ($astro['status'] == 'in_orbit'): ?>
                                    <i class="fas fa-satellite" style="color: var(--accent);"></i> Im All
                                <?php else: ?>
                                    <i class="fas fa-clock"></i> Training...
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                    <hr style="border-color: #333; margin: 15px 0;">
                    <form method="POST" style="display: flex; gap: 10px;">
                        <input type="hidden" name="action" value="recruit_astro">
                        <input type="text" name="name" placeholder="Name..." style="flex-grow: 1; padding: 8px; background: #222; border: 1px solid #444; color: #fff; border-radius: 4px;">
                        <button class="btn btn-sm">Rekrutieren (1M)</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Management Stab</div>
                <div class="card-body">
                    <table>
                        <?php foreach ($employees as $emp): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($emp['name']) ?></strong><br>
                                <span class="badge" style="background: #333;"><?= $emp['type'] ?></span>
                                <small>(+<?= $emp['skill_value'] ?>)</small>
                            </td>
                            <td style="text-align: right;">
                                <?php if (!empty($emp['busy_until']) && strtotime($emp['busy_until']) > time()): ?>
                                    <span class="badge badge-warn">Beschäftigt</span>
                                <?php else: ?>
                                    <span class="badge badge-success">Verfügbar</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                    
                    <h3 style="margin-top: 20px;">Bewerber</h3>
                    <table>
                        <?php foreach ($applicants as $app): ?>
                        <tr>
                            <td><?= htmlspecialchars($app['name']) ?> <small>(<?= $app['type'] ?>)</small></td>
                            <td style="text-align: right;">
                                <form method="POST">
                                    <input type="hidden" name="action" value="hire_spec">
                                    <input type="hidden" name="spec_id" value="<?= $app['id'] ?>">
                                    <button class="btn btn-sm btn-action"><?= number_format($app['salary_cost']) ?> €</button>
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