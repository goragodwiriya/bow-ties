<?php
require_once 'config.php';

class Auth {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function login($username, $password) {
        $query = "SELECT id, username, password FROM admin_users WHERE username = :username";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Set session
            $_SESSION[ADMIN_SESSION_NAME] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'login_time' => time()
            ];

            return true;
        }

        return false;
    }

    public function logout() {
        unset($_SESSION[ADMIN_SESSION_NAME]);
        session_destroy();
    }

    public function isLoggedIn() {
        if (!isset($_SESSION[ADMIN_SESSION_NAME])) {
            return false;
        }

        $session = $_SESSION[ADMIN_SESSION_NAME];

        // Check session timeout
        if (time() - $session['login_time'] > ADMIN_SESSION_TIMEOUT) {
            $this->logout();
            return false;
        }

        // Refresh login time
        $_SESSION[ADMIN_SESSION_NAME]['login_time'] = time();

        return true;
    }

    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('HTTP/1.1 401 Unauthorized');
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Authentication required']);
            exit;
        }
    }

    public function createUser($username, $password) {
        $query = "INSERT INTO admin_users (username, password) VALUES (:username, :password)";

        $stmt = $this->conn->prepare($query);

        // Clean data
        $username = htmlspecialchars(strip_tags($username));
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // Bind data
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $passwordHash);

        // Execute query
        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    public function updateUserPassword($userId, $newPassword) {
        $query = "UPDATE admin_users SET password = :password WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Clean data
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

        // Bind data
        $stmt->bindParam(':password', $passwordHash);
        $stmt->bindParam(':id', $userId);

        // Execute query
        if($stmt->execute()) {
            return true;
        }

        return false;
    }
}