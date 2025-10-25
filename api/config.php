<?php

// Load environment variables from project .env if the process has not set them already
$envFile = dirname(__DIR__).'/.env';
if (is_readable($envFile)) {
    $envLines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($envLines as $line) {
        $trimmed = ltrim($line);
        if ($trimmed === '' || $trimmed[0] === '#') {
            continue;
        }

        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $name = trim($parts[0]);
        $value = trim($parts[1]);

        if ($value !== '' && ($value[0] === '"' || $value[0] === "'")) {
            $quote = $value[0];
            if (substr($value, -1) === $quote) {
                $value = substr($value, 1, -1);
            }
        }

        if ($name !== '' && getenv($name) === false) {
            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Database configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'monochrome_bowties');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

// Telegram Bot configuration
define('TELEGRAM_BOT_TOKEN', getenv('TELEGRAM_BOT_TOKEN') ?: '');
define('TELEGRAM_CHAT_ID', getenv('TELEGRAM_CHAT_ID') ?: '');

// Site configuration
define('SITE_URL', rtrim(getenv('SITE_URL') ?: 'https://example.com', '/'));
define('SITE_NAME', getenv('SITE_NAME') ?: 'โบว์ไว้อาลัย');
define('SHOP_PHONE', getenv('SHOP_PHONE') ?: '');
define('SHOP_EMAIL', getenv('SHOP_EMAIL') ?: '');

// PromptPay configuration
define('PROMPTPAY_ID', getenv('PROMPTPAY_ID') ?: ''); // เบอร์โทรศัพท์หรือเลขประจำตัวผู้เสียภาษี 13 หลัก
define('PROMPTPAY_NAME', getenv('PROMPTPAY_NAME') ?: 'โบว์ไว้อาลัย');

// Admin configuration
define('ADMIN_SESSION_NAME', 'monochrome_admin');
define('ADMIN_SESSION_TIMEOUT', 3600); // 1 hour

// CSRF Token configuration
define('CSRF_TOKEN_NAME', 'csrf_token');
define('CSRF_TOKEN_EXPIRY', 3600); // 1 hour

// Error reporting (Production settings)
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__.'/logs/error.log');

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Timezone
date_default_timezone_set('UTC');

// Start session
session_start();
