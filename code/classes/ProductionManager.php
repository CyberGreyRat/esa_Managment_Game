<?php
require_once 'Database.php';

class ProductionManager
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getProducts(): array
    {
        return $this->db->query("SELECT * FROM products ORDER BY base_sale_value ASC")->fetchAll();
    }

    public function getInventory(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT ui.*, p.name, p.type, p.base_sale_value 
            FROM user_inventory ui 
            JOIN products p ON ui.product_id = p.id 
            WHERE ui.user_id = :uid AND ui.amount > 0
        ");
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll();
    }

    public function getActiveProduction(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT pl.*, p.name as product_name, s.name as specialist_name 
            FROM production_lines pl
            JOIN products p ON pl.product_id = p.id
            JOIN specialists s ON pl.specialist_id = s.id
            WHERE pl.user_id = :uid AND pl.is_completed = 0
        ");
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll();
    }

    public function startProduction(int $userId, int $specialistId, int $productId): array
    {
        try {
            $this->db->beginTransaction();

            // 1. Specialist check
            $stmt = $this->db->prepare("SELECT * FROM specialists WHERE id = :sid AND user_id = :uid FOR UPDATE");
            $stmt->execute([':sid' => $specialistId, ':uid' => $userId]);
            $spec = $stmt->fetch();

            if (!$spec) throw new Exception("Mitarbeiter nicht gefunden.");
            if ($spec['assignment_id']) throw new Exception("Mitarbeiter ist bereits beschäftigt.");

            // 2. Duration Logic
            $baseSeconds = 3600; 
            $reduction = $spec['skill_value'] * 30; 
            $duration = max(300, $baseSeconds - $reduction);

            $endTime = date('Y-m-d H:i:s', time() + $duration);

            // 3. Create Production Line
            $sql = "INSERT INTO `production_lines` (`user_id`, `specialist_id`, `product_id`, `start_time`, `end_time`, `is_completed`) 
                    VALUES (:uid, :sid, :pid, NOW(), :endtime, 0)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':uid' => $userId, ':sid' => $specialistId, ':pid' => $productId, ':endtime' => $endTime]);
            $lineId = $this->db->lastInsertId();

            // 4. Assign Specialist
            $this->db->prepare("UPDATE specialists SET assignment_id = :lid WHERE id = :sid")
                ->execute([':lid' => $lineId, ':sid' => $specialistId]);

            // 5. Create Event for completion
            $this->db->prepare("INSERT INTO event_queue (user_id, event_type, reference_id, start_time, end_time, is_processed) 
                                VALUES (:uid, 'PRODUCTION_FINISH', :lid, NOW(), :endtime, 0)")
                ->execute([':uid' => $userId, ':lid' => $lineId, ':endtime' => $endTime]);

            $this->db->commit();
            return ['success' => true, 'message' => "Produktion gestartet! Dauer: " . round($duration / 60) . " Min."];

        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function sellProduct(int $userId, int $productId, int $amount): array
    {
        try {
            $this->db->beginTransaction();

            // Check Inventory
            $stmt = $this->db->prepare("SELECT amount FROM user_inventory WHERE user_id = :uid AND product_id = :pid FOR UPDATE");
            $stmt->execute([':uid' => $userId, ':pid' => $productId]);
            $currentAmount = $stmt->fetchColumn();

            if ($currentAmount < $amount) throw new Exception("Nicht genug Ware auf Lager.");

            // Get Price info
            $stmt = $this->db->prepare("SELECT base_sale_value, reputation_value FROM products WHERE id = :pid");
            $stmt->execute([':pid' => $productId]);
            $product = $stmt->fetch();

            $totalMoney = $product['base_sale_value'] * $amount;
            $totalRep = $product['reputation_value'] * $amount;

            // Update Inventory
            $this->db->prepare("UPDATE user_inventory SET amount = amount - :amt WHERE user_id = :uid AND product_id = :pid")
                ->execute([':amt' => $amount, ':uid' => $userId, ':pid' => $productId]);

            // Update Resources
            $this->db->prepare("UPDATE user_resources SET money = money + :money WHERE user_id = :uid")
                ->execute([':money' => $totalMoney, ':uid' => $userId]);

            // Update Reputation (Random country? Or global?) Let's do Global/Random for now
            // Simply picking country 1 (Germany/ESA Host) for simplicity or random
            $countryId = rand(1, 4); // Assuming 4 countries
            $this->db->prepare("INSERT INTO user_reputation (user_id, country_id, reputation) VALUES (:uid, :cid, :rep) ON DUPLICATE KEY UPDATE reputation = LEAST(100, reputation + :rep)")
                ->execute([':uid' => $userId, ':cid' => $countryId, ':rep' => $totalRep]);

            $this->db->commit();
            return ['success' => true, 'message' => "$amount Stück verkauft für " . number_format($totalMoney) . " €."];

        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
?>
