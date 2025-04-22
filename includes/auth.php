<?php
class Auth
{
    private $db;

    public function __construct()
    {
        $this->db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($this->db->connect_error) {
            error_log("Database connection failed: " . $this->db->connect_error);
            die("Database connection error. Please try again later.");
        }
    }

    public function login($username, $password)
    {
        $stmt = $this->db->prepare("SELECT id, username, password_hash, is_admin FROM users WHERE username = ? LIMIT 1");
        if (!$stmt) {
            error_log("Prepare failed: " . $this->db->error);
            return false;
        }

        $stmt->bind_param("s", $username);
        if (!$stmt->execute()) {
            error_log("Execute failed: " . $stmt->error);
            return false;
        }

        $result = $stmt->get_result();
        if ($result->num_rows !== 1) {
            return false;
        }

        $user = $result->fetch_assoc();
        if (hash('sha256', $password) === $user['password_hash']) {
            // Regenerate session ID to prevent fixation
            session_regenerate_id(true);

            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = (bool)$user['is_admin'];
            $_SESSION['last_activity'] = time();

            return true;
        }

        return false;
    }

    public function validateSession()
    {
        // Check required session variables
        if (!isset($_SESSION['user_id'], $_SESSION['last_activity'], $_SESSION['is_admin'])) {
            return false;
        }

        // Check session timeout (30 minutes)
        if (time() - $_SESSION['last_activity'] > 1800) {
            $this->logout();
            return false;
        }

        // Update last activity
        $_SESSION['last_activity'] = time();
        return true;
    }

    public function isLoggedIn()
    {
        return $this->validateSession();
    }

    public function isAdmin()
    {
        return $this->validateSession() && $_SESSION['is_admin'];
    }

    public function logout()
    {
        $_SESSION = array();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        session_destroy();
    }

    public function getCurrentUserId()
    {
        return $_SESSION['user_id'] ?? null;
    }

    public function createUser($username, $password, $isAdmin = false)
    {
        if (!$this->isAdmin()) {
            return false;
        }

        $passwordHash = hash('sha256', $password);
        $stmt = $this->db->prepare("INSERT INTO users (username, password_hash, is_admin) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $username, $passwordHash, $isAdmin);
        return $stmt->execute();
    }

    public function grantAccess($userId, $websiteId)
    {
        if (!$this->isAdmin()) {
            return false;
        }

        $stmt = $this->db->prepare("INSERT INTO user_website_access (user_id, website_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $userId, $websiteId);
        return $stmt->execute();
    }

    public function revokeAccess($userId, $websiteId)
    {
        if (!$this->isAdmin()) {
            return false;
        }

        $stmt = $this->db->prepare("DELETE FROM user_website_access WHERE user_id = ? AND website_id = ?");
        $stmt->bind_param("ii", $userId, $websiteId);
        return $stmt->execute();
    }
}
