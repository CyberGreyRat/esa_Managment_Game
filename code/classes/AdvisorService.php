<?php
require_once __DIR__ . '/../Database.php';

class AdvisorService {
    private $db;
    private $userId;

    public function __construct(int $userId) {
        $this->db = Database::getInstance()->getConnection();
        $this->userId = $userId;
    }

    /**
     * Elenas Morgen-Briefing.
     * Prüft Lagerbestände und Finanzen.
     */
    public function getBriefing(): array {
        $alerts = [];

        // 1. Prüfen: Haben wir Teile für eine Ariane 64?
        $missingParts = $this->checkBlueprintReadiness(1); // ID 1 = Ariane 64

        if (!empty($missingParts)) {
            $alerts[] = [
                'type' => 'warning',
                'speaker' => 'Elena Vance',
                'message' => 'Wir können die Ariane 64 nicht bauen! Es fehlen Teile im Lager.',
                'details' => $missingParts
            ];
        } else {
            $alerts[] = [
                'type' => 'success',
                'speaker' => 'Elena Vance',
                'message' => 'Alle Teile für eine Ariane 64 sind auf Lager. Wir können die Integration (Montage) starten!'
            ];
        }

        return $alerts;
    }

    /**
     * Die komplexe SQL-Abfrage aus dem Design-Dokument
     */
    private function checkBlueprintReadiness(int $blueprintId): array {
        $sql = "SELECT 
                    c.name AS component,
                    bi.quantity AS required_qty,
                    COUNT(w.id) AS in_stock,
                    (bi.quantity - COUNT(w.id)) AS missing
                FROM blueprints b
                JOIN blueprint_items bi ON b.id = bi.blueprint_id
                JOIN components c ON bi.component_id = c.id
                -- WICHTIG: LEFT JOIN auf das Warehouse des Users mit Status IN_STOCK
                LEFT JOIN warehouse w ON w.component_id = c.id 
                                      AND w.user_id = :uid 
                                      AND w.status = 'IN_STOCK'
                WHERE b.id = :bid
                GROUP BY c.id, bi.quantity
                HAVING missing > 0";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':uid' => $this->userId, ':bid' => $blueprintId]);
        
        return $stmt->fetchAll();
    }
}
?>