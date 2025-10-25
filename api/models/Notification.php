<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/SettingsModel.php';

class Notification
{
    /**
     * @var mixed
     */
    private $settingsModel;

    public function __construct()
    {
        try {
            $this->settingsModel = new SettingsModel();
        } catch (Exception $e) {
            $this->settingsModel = null;
            error_log('Notification settings init error: '.$e->getMessage());
        }
    }

    /**
     * Send new order notification to Telegram
     */
    public function sendNewOrderNotification($order)
    {
        $orderNumber = $order['order_number'] ?? 'N/A';
        $customerName = $order['customer_name'] ?? 'N/A';
        $customerPhone = $order['customer_phone'] ?? 'N/A';
        $total = number_format($order['total'] ?? 0, 2);
        $items = json_decode($order['items'] ?? '[]', true);

        $itemsList = "";
        if (is_array($items)) {
            foreach ($items as $item) {
                $itemName = $item['name'] ?? 'Unknown';
                $itemQty = $item['quantity'] ?? 0;
                $itemPrice = number_format($item['price'] ?? 0, 2);
                $itemsList .= "• {$itemName} x{$itemQty} (฿{$itemPrice})\n";
            }
        }

        $message = "🛍️ *คำสั่งซื้อใหม่*\n\n";
        $message .= "📋 เลขที่: `{$orderNumber}`\n";
        $message .= "👤 ชื่อ: {$customerName}\n";
        $message .= "📱 เบอร์: {$customerPhone}\n\n";
        $message .= "🛒 *รายการสินค้า:*\n{$itemsList}\n";
        $message .= "💰 *ยอดรวม:* ฿{$total}\n\n";
        $message .= "⏰ ".date('d/m/Y H:i:s');

        return $this->sendTelegramMessage($message);
    }

    /**
     * Send payment confirmation notification to Telegram
     */
    public function sendPaymentConfirmationNotification($order)
    {
        $orderNumber = $order['order_number'] ?? 'N/A';
        $customerName = $order['customer_name'] ?? 'N/A';
        $total = number_format($order['total'] ?? 0, 2);

        $message = "✅ *ยืนยันการชำระเงิน*\n\n";
        $message .= "📋 เลขที่: `{$orderNumber}`\n";
        $message .= "👤 ชื่อ: {$customerName}\n";
        $message .= "💰 ยอดเงิน: ฿{$total}\n\n";
        $message .= "📦 สถานะ: กำลังดำเนินการจัดส่ง\n\n";
        $message .= "⏰ ".date('d/m/Y H:i:s');

        return $this->sendTelegramMessage($message);
    }

    /**
     * Send message to Telegram (public method for general use)
     */
    public function sendTelegram($message, $parseMode = 'HTML')
    {
        return $this->sendTelegramMessage($message, $parseMode);
    }

    /**
     * Send message to Telegram
     */
    private function sendTelegramMessage($message, $parseMode = 'Markdown')
    {
        [$botToken, $chatId] = $this->getTelegramCredentials();

        if (empty($botToken) || empty($chatId)) {
            error_log('Telegram credentials not configured');
            return false;
        }

        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";

        $data = [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => $parseMode
        ];

        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data)
            ]
        ];

        $context = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);

        if ($result === false) {
            error_log('Failed to send Telegram notification');
            return false;
        }

        return true;
    }

    private function getTelegramCredentials(): array
    {
        $token = TELEGRAM_BOT_TOKEN;
        $chatId = TELEGRAM_CHAT_ID;

        if ($this->settingsModel) {
            $settings = $this->settingsModel->getSettings();
            if (!empty($settings['telegram_token'])) {
                $token = $settings['telegram_token'];
            }
            if (!empty($settings['telegram_chat_id'])) {
                $chatId = $settings['telegram_chat_id'];
            }
        }

        return [$token, $chatId];
    }
}
