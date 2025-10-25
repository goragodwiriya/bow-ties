<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../models/Auth.php';
require_once __DIR__.'/../models/SettingsModel.php';

header('Content-Type: application/json');

$auth = new Auth();
$auth->requireLogin();

try {
    $settingsModel = new SettingsModel();
} catch (Throwable $e) {
    error_log('Settings model init error: '.$e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load settings']);
    exit;
}

// GET - Load settings
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $settings = $settingsModel->getSettings();

        // Harmonize keys for front-end compatibility
        $response = [
            'shop_name' => $settings['shop_name'] ?? SITE_NAME,
            'shop_phone' => $settings['shop_phone'] ?? SHOP_PHONE,
            'shop_email' => $settings['shop_email'] ?? SHOP_EMAIL,
            'telegram_token' => $settings['telegram_token'] ?? TELEGRAM_BOT_TOKEN,
            'telegram_chat_id' => $settings['telegram_chat_id'] ?? TELEGRAM_CHAT_ID,
            'promptpay_id' => $settings['promptpay_id'] ?? $settings['promptpay_number'] ?? PROMPTPAY_ID,
            'promptpay_name' => $settings['promptpay_name'] ?? PROMPTPAY_NAME
        ];

        echo json_encode(['settings' => $response]);
    } catch (Exception $e) {
        error_log("Settings load error: ".$e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to load settings']);
    }
    exit;
}

// POST - Save settings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth->validateCsrfToken();

    $payload = file_get_contents('php://input');
    $data = json_decode($payload, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON payload']);
        exit;
    }

    try {
        $sanitized = [
            'shop_name' => trim($data['shop_name'] ?? SITE_NAME),
            'shop_phone' => trim($data['shop_phone'] ?? ''),
            'shop_email' => trim($data['shop_email'] ?? ''),
            'telegram_token' => trim($data['telegram_token'] ?? ''),
            'telegram_chat_id' => trim($data['telegram_chat_id'] ?? ''),
            'promptpay_id' => trim($data['promptpay_id'] ?? ''),
            'promptpay_name' => trim($data['promptpay_name'] ?? PROMPTPAY_NAME)
        ];

        if (!empty($sanitized['shop_email']) && !filter_var($sanitized['shop_email'], FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid email address']);
            exit;
        }

        if (!empty($sanitized['telegram_token']) && !preg_match('/^[0-9A-Za-z:_-]+$/', $sanitized['telegram_token'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid Telegram token format']);
            exit;
        }

        if (!empty($sanitized['telegram_chat_id']) && !preg_match('/^(@?[A-Za-z0-9_-]+)$/', $sanitized['telegram_chat_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid Telegram chat ID format']);
            exit;
        }

        if (!empty($sanitized['promptpay_id']) && !preg_match('/^[0-9]{10,13}$/', $sanitized['promptpay_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid PromptPay ID']);
            exit;
        }

        $saved = $settingsModel->saveSettings($sanitized);

        if (!$saved) {
            throw new Exception('Failed to persist settings');
        }

        echo json_encode([
            'success' => true,
            'message' => 'Settings saved successfully'
        ]);
    } catch (Exception $e) {
        error_log("Settings save error: ".$e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save settings']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
