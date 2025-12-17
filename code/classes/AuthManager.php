<?php
require_once __DIR__ . '/../Database.php';

class AuthManager
{
    private PDO $db;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->db = Database::getInstance()->getConnection();
    }

    public function register(string $username, string $password, string $email = ''): array
    {
        // Check if user exists
        $stmt = $this->db->prepare("SELECT id FROM users WHERE username = :name");
        $stmt->execute([':name' => $username]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Benutzername bereits vergeben.'];
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $this->db->beginTransaction();

            // Create User
            $stmt = $this->db->prepare("INSERT INTO users (username, password_hash, email) VALUES (:name, :hash, :email)");
            $stmt->execute([':name' => $username, ':hash' => $hash, ':email' => $email]);
            $userId = $this->db->lastInsertId();

            // Init Resources
            $stmt = $this->db->prepare("INSERT INTO user_resources (user_id) VALUES (:uid)");
            $stmt->execute([':uid' => $userId]);

            // Init Progression
            $stmt = $this->db->prepare("INSERT INTO user_progression (user_id, current_step_id) VALUES (:uid, 'intro')");
            $stmt->execute([':uid' => $userId]);

            $this->db->commit();
            return ['success' => true, 'message' => 'Registrierung erfolgreich! Bitte einloggen.'];

        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Datenbankfehler: ' . $e->getMessage()];
        }
    }

    public function login(string $username, string $password): array
    {
        $stmt = $this->db->prepare("SELECT id, username, password_hash FROM users WHERE username = :name");
        $stmt->execute([':name' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            return ['success' => true, 'message' => 'Login erfolgreich.'];
        }

        return ['success' => false, 'message' => 'Ungültige Zugangsdaten.'];
    }

    public function logout()
    {
        session_destroy();
    }

    public function isLoggedIn(): bool
    {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        // Verify user actually exists in DB (in case of DB reset)
        $stmt = $this->db->prepare("SELECT id FROM users WHERE id = :id");
        $stmt->execute([':id' => $_SESSION['user_id']]);
        if (!$stmt->fetch()) {
            $this->logout(); // Invalid session, kill it
            return false;
        }

        return true;
    }

    public function getCurrentUserId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    public function requireLogin()
    {
        if (!$this->isLoggedIn()) {
            header("Location: login.php");
            exit;
        }
    }
}
