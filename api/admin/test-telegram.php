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

if (!isset($data['token']) || !isset($data['chat_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing token or chat_id']);
    exit;
}

$token = $data['token'];
$chatId = $data['chat_id'];

if (!preg_match('/^[0-9A-Za-z:_-]+$/', $token)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid token format']);
    exit;
}

if (!preg_match('/^(@?[A-Za-z0-9_-]+)$/', $chatId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid chat ID format']);
    exit;
}

$message = "🔔 ทดสอบการส่งข้อความจากระบบแอดมิน\n\nการเชื่อมต่อ Telegram ทำงานปกติ ✅";

$url = "https://api.telegram.org/bot{$token}/sendMessage";
$postData = [
    'chat_id' => $chatId,
    'text' => $message,
    'parse_mode' => 'HTML'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo json_encode(['success' => true, 'message' => 'ส่งข้อความทดสอบสำเร็จ']);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'ไม่สามารถส่งข้อความได้ กรุณาตรวจสอบ Token และ Chat ID']);
}
