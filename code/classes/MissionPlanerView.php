<?php
require_once 'View.php';
require_once __DIR__ . '/../MissionControl.php';

class MissionPlannerView extends View {
    
    private $rocketId;

    public function __construct($userId, $rocketId) {
        parent::__construct($userId);
        $this->rocketId = $rocketId;
    }

    public function handleAction(): ?array {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $missionControl = new MissionControl();
            if ($_POST['action'] === 'launch_mission_complex') {
                // Hier kommt die komplexe Launch-Logik hin
                // payload_ids ist ein Array von IDs
                return $missionControl->launchComplexMission(
                    $this->userId, 
                    $this->rocketId, 
                    $_POST['mission_type_id'], 
                    $_POST['payload_ids'] ?? [], 
                    (int)$_POST['fuel_load']
                );
            }
        }
        return null;
    }

    public function render(): void {
        // Daten laden
        $stmt = $this->db->prepare("SELECT * FROM user_fleet WHERE id = :rid AND user_id = :uid");
        $stmt->execute([':rid' => $this->rocketId, ':uid' => $this->userId]);
        $rocket = $stmt->fetch();

        // Raketen-Typ Infos (für Max Nutzlast)
        $stmt = $this->db->prepare("SELECT * FROM rocket_types WHERE id = :rtid");
        $stmt->execute([':rtid' => $rocket['rocket_type_id']]);
        $type = $stmt->fetch();

        // Verfügbare Payloads
        $stmt = $this->db->prepare("SELECT * FROM payloads WHERE user_id = :uid AND status = 'stored'");
        $stmt->execute([':uid' => $this->userId]);
        $payloads = $stmt->fetchAll();

        // Verfügbare Missions-Profile
        $stmt = $this->db->query("SELECT * FROM mission_types");
        $missionTypes = $stmt->fetchAll();

        ?>
        
        <div style="display:flex; align-items:center; gap:15px; margin-bottom:20px;">
            <a href="?page=fleet" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Zurück</a>
            <h1 class="page-title" style="margin:0; border:none;">Mission Control: <?= htmlspecialchars($rocket['name']) ?></h1>
        </div>

        <form method="POST" class="grid-2">
            <input type="hidden" name="action" value="launch_mission_complex">
            <input type="hidden" name="rocket_id" value="<?= $this->rocketId ?>">

            <!-- RAKETEN STATUS -->
            <div class="card">
                <div class="card-header">Raketen Status</div>
                <div class="card-body">
                    <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                        <span>Modell:</span> <strong><?= htmlspecialchars($type['name']) ?></strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                        <span>Max. Nutzlast (LEO):</span> <strong><?= number_format($type['cargo_capacity_leo']) ?> kg</strong>
                    </div>
                    
                    <div style="margin-top:20px;">
                        <label style="display:block; color:var(--text-muted); margin-bottom:5px;">Treibstoff-Tank (kg)</label>
                        <input type="range" name="fuel_load" min="0" max="<?= $rocket['fuel_capacity_kg'] ?>" value="<?= $rocket['fuel_capacity_kg'] ?>" style="width:100%" oninput="document.getElementById('fuelVal').innerText = this.value">
                        <div style="text-align:right; font-weight:bold; color:var(--warning);"><span id="fuelVal"><?= $rocket['fuel_capacity_kg'] ?></span> kg</div>
                        <small style="color:var(--text-muted);">Weniger Treibstoff = Mehr Nutzlast, aber Reichweite sinkt.</small>
                    </div>
                </div>
            </div>

            <!-- MISSIONS PROFIL -->
            <div class="card">
                <div class="card-header">Ziel-Orbit & Profil</div>
                <div class="card-body">
                    <select name="mission_type_id" style="width:100%; padding:10px; background:#222; color:white; border:1px solid #444; border-radius:4px;" required>
                        <?php foreach($missionTypes as $mt): ?>
                            <option value="<?= $mt['id'] ?>">
                                <?= htmlspecialchars($mt['name']) ?> 
                                (Min. Fuel: <?= number_format($mt['fuel_required_kg']) ?> kg)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div style="margin-top:15px; padding:10px; background:rgba(59,130,246,0.1); border-left:3px solid var(--primary); font-size:0.9em;">
                        Wähle das Ziel sorgfältig. Ein geostationärer Transfer (GTO) benötigt deutlich mehr Delta-V (Treibstoff) als ein niedriger Erdorbit (LEO).
                    </div>
                </div>
            </div>

            <!-- NUTZLAST INTEGRATION -->
            <div class="card" style="grid-column: span 2;">
                <div class="card-header">Nutzlast Integration (Payload Fairing)</div>
                <div class="card-body">
                    <table id="payloadTable">
                        <thead>
                            <tr>
                                <th>Auswahl</th>
                                <th>Bezeichnung</th>
                                <th>Typ</th>
                                <th>Masse</th>
                                <th>Wert</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($payloads as $p): ?>
                            <tr>
                                <td><input type="checkbox" name="payload_ids[]" value="<?= $p['id'] ?>" data-mass="<?= $p['mass_kg'] ?>" onclick="updateMass()"></td>
                                <td><?= htmlspecialchars($p['name']) ?></td>
                                <td><span class="badge badge-info"><?= $p['type'] ?></span></td>
                                <td><?= number_format($p['mass_kg']) ?> kg</td>
                                <td><?= number_format($p['value']) ?> €</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr style="background:rgba(255,255,255,0.05); font-weight:bold;">
                                <td colspan="3" style="text-align:right;">Gesamtmasse:</td>
                                <td colspan="2" id="totalMass" style="color:var(--success);">0 kg</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- LAUNCH BUTTON -->
            <div style="grid-column: span 2; text-align:right;">
                <button class="btn btn-launch" style="padding:15px 40px; font-size:1.2em;">
                    <i class="fas fa-rocket"></i> GO FOR LAUNCH
                </button>
            </div>
        </form>

        <script>
            function updateMass() {
                let total = 0;
                const checkboxes = document.querySelectorAll('input[name="payload_ids[]"]:checked');
                const max = <?= $type['cargo_capacity_leo'] ?>;
                const massDisplay = document.getElementById('totalMass');

                checkboxes.forEach(cb => {
                    total += parseInt(cb.getAttribute('data-mass'));
                });

                massDisplay.innerText = new Intl.NumberFormat('de-DE').format(total) + " kg";
                
                if (total > max) {
                    massDisplay.style.color = "var(--danger)";
                    massDisplay.innerText += " (ÜBERLADEN!)";
                } else {
                    massDisplay.style.color = "var(--success)";
                }
            }
        </script>
        <?php
    }
}
?>