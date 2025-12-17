<?php
require_once 'Database.php';

class ContractManager
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAvailableContracts(int $userId): array
    {
        // For now, user sees all available contracts assigned to them (or generic pool)
        // Here we assume row-level permission: user_id indicates who CAN see/take it.
        return $this->db->query("SELECT c.*, p.name as product_name, co.name as country_name 
                                 FROM contracts c
                                 JOIN products p ON c.product_id = p.id
                                 LEFT JOIN countries co ON c.country_id = co.id
                                 WHERE c.user_id = $userId AND c.status = 'available'
                                 ORDER BY c.deadline ASC")->fetchAll();
    }

    public function getActiveContracts(int $userId): array
    {
        return $this->db->query("SELECT c.*, p.name as product_name, co.name as country_name 
                                 FROM contracts c
                                 JOIN products p ON c.product_id = p.id
                                 LEFT JOIN countries co ON c.country_id = co.id
                                 WHERE c.user_id = $userId AND c.status = 'accepted'
                                 ORDER BY c.deadline ASC")->fetchAll();
    }

    public function acceptContract(int $contractId, int $userId): array
    {
        // 1. Check if valid
        $stmt = $this->db->prepare("SELECT * FROM contracts WHERE id = :id AND user_id = :uid AND status = 'available'");
        $stmt->execute([':id' => $contractId, ':uid' => $userId]);
        $contract = $stmt->fetch();

        if (!$contract) return ['success' => false, 'message' => 'Auftrag nicht verfügbar oder nicht gefunden.'];

        // 2. Update Status
        $stmt = $this->db->prepare("UPDATE contracts SET status = 'accepted' WHERE id = :id");
        $stmt->execute([':id' => $contractId]);

        return ['success' => true, 'message' => "Auftrag angenommen!"];
    }

    public function deliverContract(int $contractId, int $userId): array
    {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("SELECT c.*, p.name as product_name FROM contracts c JOIN products p ON c.product_id = p.id WHERE c.id = :id AND c.user_id = :uid AND c.status = 'accepted' FOR UPDATE");
            $stmt->execute([':id' => $contractId, ':uid' => $userId]);
            $contract = $stmt->fetch();

            if (!$contract) throw new Exception("Auftrag nicht gefunden oder nicht aktiv.");

            // Check Inventory
            $stmt = $this->db->prepare("SELECT amount FROM user_inventory WHERE user_id = :uid AND product_id = :pid");
            $stmt->execute([':uid' => $userId, ':pid' => $contract['product_id']]);
            $stock = $stmt->fetchColumn() ?: 0;

            if ($stock < $contract['amount_needed']) {
                throw new Exception("Nicht genug Ware im Lager! Benötigt: {$contract['amount_needed']}x {$contract['product_name']}, Vorhanden: $stock.");
            }

            // Remove Items
            $this->db->prepare("UPDATE user_inventory SET amount = amount - :amt WHERE user_id = :uid AND product_id = :pid")
                ->execute([':amt' => $contract['amount_needed'], ':uid' => $userId, ':pid' => $contract['product_id']]);

            // Grant Rewards
            $this->db->prepare("UPDATE user_resources SET money = money + :money WHERE user_id = :uid")
                ->execute([':money' => $contract['reward_money'], ':uid' => $userId]);

            if ($contract['country_id'] && $contract['reward_reputation'] > 0) {
                 $this->db->prepare("INSERT INTO user_reputation (user_id, country_id, reputation) VALUES (:uid, :cid, :rep) ON DUPLICATE KEY UPDATE reputation = LEAST(100, reputation + :rep)")
                    ->execute([':uid' => $userId, ':cid' => $contract['country_id'], ':rep' => $contract['reward_reputation']]);
            }

            // Mark Completed
            $this->db->prepare("UPDATE contracts SET status = 'completed' WHERE id = :id")->execute([':id' => $contractId]);

            $this->db->commit();
            return ['success' => true, 'message' => "Auftrag geliefert! +{$contract['reward_money']} € erhalten."];

        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
?>
