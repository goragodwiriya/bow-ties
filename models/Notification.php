<?php
require_once 'config.php';

class Notification {
    private $botToken;
    private $chatId;

    public function __construct() {
        $this->botToken = TELEGRAM_BOT_TOKEN;
        $this->chatId = TELEGRAM_CHAT_ID;
    }

    public function sendTelegramMessage($message) {
        if (empty($this->botToken) || empty($this->chatId)) {
            error_log("Telegram bot token or chat ID not configured");
            return false;
        }

        $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";

        $data = [
            'chat_id' => $this->chatId,
            'text' => $message,
            'parse_mode' => 'HTML'
        ];

        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data)
            ]
        ];

        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        if ($result === false) {
            error_log("Failed to send Telegram message");
            return false;
        }

        $response = json_decode($result, true);

        return $response['ok'] ?? false;
    }

    public function sendNewOrderNotification($order) {
        $message = "<b>New Order Received!</b>\n\n";
        $message .= "<b>Order Number:</b> {$order['order_number']}\n";
        $message .= "<b>Customer:</b> {$order['customer_name']}\n";
        $message .= "<b>Email:</b> {$order['customer_email']}\n";
        $message .= "<b>Phone:</b> {$order['customer_phone']}\n";
        $message .= "<b>Total:</b> ${$order['total']}\n";
        $message .= "<b>Payment Method:</b> {$order['payment_method']}\n";
        $message .= "<b>Payment Status:</b> {$order['payment_status']}\n\n";

        $message .= "<b>Items:</b>\n";
        foreach ($order['items'] as $item) {
            $message .= "- {$item['name']} x {$item['quantity']} = ${$item['price'] * $item['quantity']}\n";
        }

        $message .= "\n<b>Shipping Address:</b>\n";
        $message .= "{$order['customer_address']}\n";
        $message .= "{$order['customer_city']}, {$order['customer_postal']}\n";
        $message .= "{$order['customer_country']}\n\n";

        $adminUrl = SITE_URL . "/admin/orders.php?id={$order['id']}";
        $message .= "<a href='{$adminUrl}'>View Order in Admin Panel</a>";

        return $this->sendTelegramMessage($message);
    }

    public function sendPaymentConfirmationNotification($order) {
        $message = "<b>Payment Confirmed!</b>\n\n";
        $message .= "<b>Order Number:</b> {$order['order_number']}\n";
        $message .= "<b>Customer:</b> {$order['customer_name']}\n";
        $message .= "<b>Total:</b> ${$order['total']}\n";
        $message .= "<b>Payment Method:</b> {$order['payment_method']}\n";
        $message .= "<b>Payment Status:</b> {$order['payment_status']}\n\n";

        $adminUrl = SITE_URL . "/admin/orders.php?id={$order['id']}";
        $message .= "<a href='{$adminUrl}'>View Order in Admin Panel</a>";

        return $this->sendTelegramMessage($message);
    }

    public function testConnection() {
        $message = "<b>Test Message</b>\n\n";
        $message .= "This is a test message from " . SITE_NAME . "\n";
        $message .= "Sent at: " . date('Y-m-d H:i:s');

        return $this->sendTelegramMessage($message);
    }
}