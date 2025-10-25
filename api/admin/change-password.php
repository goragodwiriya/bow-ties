<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../models/Auth.php';

header('Content-Type: application/json');

$auth = new Auth();
$auth->requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$auth->validateCsrfToken();

$payload = file_get_contents('php://input');
$data = json_decode($payload, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON payload']);
    exit;
}

if (!isset($data['old_password']) || !isset($data['new_password'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$oldPassword = (string) $data['old_password'];
$newPassword = (string) $data['new_password'];
if (strlen($newPassword) < 6) {
    http_response_code(400);
    echo json_encode(['error' => 'รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร']);
    exit;
}
$data['old_password'] = $oldPassword;
$data['new_password'] = $newPassword;

try {
    $conn = new PDO(
        "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",
        DB_USER,
        DB_PASS
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get current user
    $username = $_SESSION['admin_username'];
    $query = "SELECT * FROM admin_users WHERE username = :username";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    // Verify old password
    if (!password_verify($data['old_password'], $user['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'รหัสผ่านเดิมไม่ถูกต้อง']);
        exit;
    }

    // Update password
    $newPasswordHash = password_hash($data['new_password'], PASSWORD_DEFAULT);
    $query = "UPDATE admin_users SET password = :password WHERE username = :username";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':password', $newPasswordHash);
    $stmt->bindParam(':username', $username);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'เปลี่ยนรหัสผ่านสำเร็จ']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update password']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
