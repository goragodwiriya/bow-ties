<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/models/Notification.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Simple rate limiting (max 5 submissions per hour per IP)
$ip = $_SERVER['REMOTE_ADDR'];
$rateKey = 'contact_rate_' . md5($ip);

if (!isset($_SESSION[$rateKey])) {
    $_SESSION[$rateKey] = ['count' => 0, 'time' => time()];
}

// Reset counter after 1 hour
if (time() - $_SESSION[$rateKey]['time'] > 3600) {
    $_SESSION[$rateKey] = ['count' => 0, 'time' => time()];
}

// Check rate limit
if ($_SESSION[$rateKey]['count'] >= 5) {
    http_response_code(429);
    echo json_encode(['error' => 'ส่งข้อความบ่อยเกินไป กรุณารอสักครู่']);
    exit;
}

// Get posted data
$data = json_decode(file_get_contents('php://input'), true);

// Validate JSON
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON data']);
    exit;
}

// Validate required fields
if (
    empty($data['name']) ||
    empty($data['email']) ||
    empty($data['subject']) ||
    empty($data['message'])
) {
    http_response_code(400);
    echo json_encode(['error' => 'กรุณากรอกข้อมูลให้ครบถ้วน']);
    exit;
}

// Validate email
if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'รูปแบบอีเมลไม่ถูกต้อง']);
    exit;
}

// Sanitize and validate input length
$name = htmlspecialchars(trim($data['name']), ENT_QUOTES, 'UTF-8');
$email = htmlspecialchars(trim($data['email']), ENT_QUOTES, 'UTF-8');
$subject = htmlspecialchars(trim($data['subject']), ENT_QUOTES, 'UTF-8');
$message = htmlspecialchars(trim($data['message']), ENT_QUOTES, 'UTF-8');
$phone = isset($data['phone']) ? htmlspecialchars(trim($data['phone']), ENT_QUOTES, 'UTF-8') : '-';

// Validate length
if (strlen($name) > 100 || strlen($subject) > 200 || strlen($message) > 2000) {
    http_response_code(400);
    echo json_encode(['error' => 'ข้อมูลยาวเกินไป']);
    exit;
}

// Validate phone format (if provided)
if ($phone !== '-' && !preg_match('/^[0-9\-\s\+\(\)]+$/', $phone)) {
    http_response_code(400);
    echo json_encode(['error' => 'รูปแบบเบอร์โทรไม่ถูกต้อง']);
    exit;
}

// Create message for Telegram
$telegramMessage = "📬 <b>ข้อความติดต่อใหม่</b>\n\n";
$telegramMessage .= "👤 <b>ชื่อ:</b> {$name}\n";
$telegramMessage .= "📧 <b>อีเมล:</b> {$email}\n";
if ($phone !== '-') {
    $telegramMessage .= "📱 <b>เบอร์โทร:</b> {$phone}\n";
}
$telegramMessage .= "📋 <b>หัวข้อ:</b> {$subject}\n\n";
$telegramMessage .= "💬 <b>ข้อความ:</b>\n{$message}\n\n";
$telegramMessage .= "⏰ <b>เวลา:</b> " . date('d/m/Y H:i:s');

// Send to Telegram
try {
    $notification = new Notification();
    $sent = $notification->sendTelegram($telegramMessage);

    // Increment rate limit counter
    $_SESSION[$rateKey]['count']++;

    if ($sent) {
        error_log("Contact form submitted: {$name} ({$email}) - {$subject}");
    } else {
        error_log("Failed to send Telegram notification for contact form");
    }

    // Always return success to user
    echo json_encode([
        'success' => true,
        'message' => 'ส่งข้อความเรียบร้อยแล้ว เราจะติดต่อกลับโดยเร็วที่สุด'
    ]);
} catch (Exception $e) {
    error_log("Contact form error: " . $e->getMessage());

    // Increment rate limit counter even on error
    $_SESSION[$rateKey]['count']++;

    // Return success to user even if Telegram fails
    echo json_encode([
        'success' => true,
        'message' => 'ส่งข้อความเรียบร้อยแล้ว เราจะติดต่อกลับโดยเร็วที่สุด'
    ]);
}
