<?php

class ProgressionManager
{
    private PDO $db;
    private int $userId;

    public function __construct(int $userId)
    {
        $this->db = Database::getInstance()->getConnection();
        $this->userId = $userId;
        $this->ensureProgressionRecord();
    }

    private function ensureProgressionRecord()
    {
        $stmt = $this->db->prepare("SELECT user_id FROM user_progression WHERE user_id = :uid");
        $stmt->execute([':uid' => $this->userId]);
        if (!$stmt->fetch()) {
            $this->db->prepare("INSERT INTO user_progression (user_id, current_step_id) VALUES (:uid, 'intro')")
                ->execute([':uid' => $this->userId]);
        }
    }

    public function getCurrentStep(): array
    {
        $stmt = $this->db->prepare("
            SELECT up.current_step_id, ss.title, ss.description, ss.unlocks_pages 
            FROM user_progression up
            JOIN story_steps ss ON up.current_step_id = ss.step_id
            WHERE up.user_id = :uid
        ");
        $stmt->execute([':uid' => $this->userId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            $data['unlocks_pages'] = json_decode($data['unlocks_pages'], true) ?? [];
        } else {
            // Fallback
            $data = [
                'current_step_id' => 'intro',
                'title' => 'Willkommen',
                'description' => 'Lade System...',
                'unlocks_pages' => ['overview']
            ];
        }
        return $data;
    }

    public function getUnlockedPages(): array
    {
        $step = $this->getCurrentStep();
        return $step['unlocks_pages'] ?? ['overview'];
    }

    public function checkProgression()
    {
        $stmt = $this->db->prepare("
            SELECT up.current_step_id, ss.required_condition_type, ss.required_condition_value, ss.next_step_id
            FROM user_progression up
            JOIN story_steps ss ON up.current_step_id = ss.step_id
            WHERE up.user_id = :uid
        ");
        $stmt->execute([':uid' => $this->userId]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$current || !$current['next_step_id'])
            return; // End of story or error

        $conditionMet = false;

        switch ($current['required_condition_type']) {
            case 'HIRE_HR_MANAGER':
                // Check if user has an HR Manager (Specialist type 'HR_Head' or similar)
                // For now, let's assume we check the 'specialists' table for a specific type
                $stmt = $this->db->prepare("SELECT COUNT(*) FROM specialists WHERE user_id = :uid AND type = 'HR_Head'");
                $stmt->execute([':uid' => $this->userId]);
                $cnt = $stmt->fetchColumn();
                if ($cnt >= $current['required_condition_value'])
                    $conditionMet = true;
                break;

            case 'HIRE_RESEARCH_HEAD':
                $stmt = $this->db->prepare("SELECT COUNT(*) FROM specialists WHERE user_id = :uid AND type = 'Research_Head'");
                $stmt->execute([':uid' => $this->userId]);
                if ($stmt->fetchColumn() >= $current['required_condition_value'])
                    $conditionMet = true;
                break;

            case 'BUILD_LAB':
                // Building type 2 is usually Lab, need to verify in DB or BuildingManager
                $stmt = $this->db->prepare("SELECT current_level FROM user_buildings WHERE user_id = :uid AND building_type_id = 2");
                $stmt->execute([':uid' => $this->userId]);
                $lvl = $stmt->fetchColumn();
                if ($lvl && $lvl >= $current['required_condition_value'])
                    $conditionMet = true;
                break;

            // Add more cases here
        }

        if ($conditionMet) {
            $this->advanceTo($current['next_step_id']);
        }
    }

    public function completeStep(string $stepId)
    {
        $current = $this->getCurrentStep();
        if ($current['current_step_id'] === $stepId) {
            // Get next step
            $stmt = $this->db->prepare("SELECT next_step_id FROM story_steps WHERE step_id = :id");
            $stmt->execute([':id' => $stepId]);
            $next = $stmt->fetchColumn();
            if ($next) {
                $this->advanceTo($next);
            }
        }
    }

    private function advanceTo(string $nextStepId)
    {
        $this->db->prepare("UPDATE user_progression SET current_step_id = :next WHERE user_id = :uid")
            ->execute([':next' => $nextStepId, ':uid' => $this->userId]);

        // Optional: Add a notification/flash message
        $_SESSION['flash_success'] = "🎉 Nächster Meilenstein erreicht!";
    }
}
