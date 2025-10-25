<?php
require_once __DIR__.'/../config.php';

class Auth
{

    public function requireLogin()
    {
        if (!$this->isLoggedIn()) {
            $this->denyRequest(401, "Unauthorized. Please login.");
        }

        $_SESSION['last_activity'] = time();
    }

    /**
     * @param $username
     * @param $password
     */
    public function login($username, $password)
    {
        try {
            $conn = new PDO(
                "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",
                DB_USER,
                DB_PASS
            );
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $query = "SELECT * FROM admin_users WHERE username = :username";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->execute();

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $this->regenerateSession();
                $_SESSION[ADMIN_SESSION_NAME] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                $_SESSION['last_activity'] = time();
                $this->refreshCsrfToken(true);
                return true;
            }

            return false;
        } catch (PDOException $e) {
            error_log("Login error: ".$e->getMessage());
            return false;
        }
    }

    public function logout()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            $this->regenerateSession();
            session_destroy();
        }
    }

    public function isLoggedIn()
    {
        if (!isset($_SESSION[ADMIN_SESSION_NAME]) || empty($_SESSION[ADMIN_SESSION_NAME])) {
            return false;
        }

        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > ADMIN_SESSION_TIMEOUT)) {
            $this->logout();
            return false;
        }

        return true;
    }

    /**
     * @return mixed
     */
    public function getCsrfToken()
    {
        if (!$this->isLoggedIn()) {
            return null;
        }

        return $this->refreshCsrfToken();
    }

    /**
     * @param $token
     */
    public function validateCsrfToken($token = null)
    {
        if (!$this->isLoggedIn()) {
            $this->denyRequest(401, "Unauthorized. Please login.");
        }

        $expected = $_SESSION[CSRF_TOKEN_NAME] ?? null;
        $expiresAt = $_SESSION['csrf_token_expiry'] ?? 0;

        if (!$expected || time() >= $expiresAt) {
            $this->denyRequest(419, "CSRF token expired. Please refresh and try again.");
        }

        if ($token === null) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        }

        if (empty($token) || !hash_equals($expected, $token)) {
            $this->denyRequest(403, "Invalid CSRF token.");
        }

        $_SESSION['csrf_token_expiry'] = time() + CSRF_TOKEN_EXPIRY;
    }

    private function regenerateSession()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    /**
     * @param $force
     * @return mixed
     */
    private function refreshCsrfToken($force = false)
    {
        $token = $_SESSION[CSRF_TOKEN_NAME] ?? null;
        $expiresAt = $_SESSION['csrf_token_expiry'] ?? 0;

        if ($force || empty($token) || time() >= $expiresAt) {
            try {
                $token = bin2hex(random_bytes(32));
            } catch (Exception $e) {
                error_log('CSRF token generation error: '.$e->getMessage());
                $this->denyRequest(500, "Unable to generate CSRF token.");
            }
        }

        $_SESSION[CSRF_TOKEN_NAME] = $token;
        $_SESSION['csrf_token_expiry'] = time() + CSRF_TOKEN_EXPIRY;

        return $token;
    }

    /**
     * @param $statusCode
     * @param $message
     */
    private function denyRequest($statusCode, $message)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode(['error' => $message]);
        exit;
    }
}
