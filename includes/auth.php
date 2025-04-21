<?php
class Auth
{
    private $db;

    public function __construct()
    {
        $this->db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($this->db->connect_error) {
            die("Connection failed: " . $this->db->connect_error);
        }
    }

    public function login($username, $password)
    {
        $stmt = $this->db->prepare("SELECT id, username, password_hash, is_admin FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (hash('sha256', $password) === $user['password_hash']) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['is_admin'] = $user['is_admin'];
                return true;
            }
        }
        return false;
    }

    public function logout()
    {
        session_unset();
        session_destroy();
    }

    public function isLoggedIn()
    {
        return isset($_SESSION['user_id']);
    }

    public function isAdmin()
    {
        return $this->isLoggedIn() && $_SESSION['is_admin'];
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
