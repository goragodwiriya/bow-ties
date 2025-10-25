<?php
require_once __DIR__.'/../config.php';

class SettingsModel
{
    /**
     * @var mixed
     */
    private $conn;

    public function __construct()
    {
        $this->connect();
    }

    /**
     * @return mixed
     */
    public function getSettings(): array
    {
        try {
            $row = $this->fetchSettingsRow();
            return $this->normalizeSettings($row);
        } catch (PDOException $e) {
            error_log('Settings fetch error: '.$e->getMessage());
            return $this->normalizeSettings(null);
        }
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function saveSettings(array $data): bool
    {
        try {
            $row = $this->fetchSettingsRow();
            $payload = [
                'shop_name' => $data['shop_name'] ?? null,
                'shop_phone' => $data['shop_phone'] ?? null,
                'shop_email' => $data['shop_email'] ?? null,
                'shop_address' => $data['shop_address'] ?? null,
                'telegram_token' => $data['telegram_token'] ?? null,
                'telegram_chat_id' => $data['telegram_chat_id'] ?? null,
                'promptpay_number' => $data['promptpay_id'] ?? ($data['promptpay_number'] ?? null),
                'promptpay_name' => $data['promptpay_name'] ?? null
            ];

            if ($row) {
                $query = "UPDATE settings SET
                            shop_name = :shop_name,
                            shop_phone = :shop_phone,
                            shop_email = :shop_email,
                            shop_address = :shop_address,
                            telegram_token = :telegram_token,
                            telegram_chat_id = :telegram_chat_id,
                            promptpay_number = :promptpay_number,
                            updated_at = CURRENT_TIMESTAMP
                          WHERE id = :id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindValue(':id', $row['id'], PDO::PARAM_INT);
            } else {
                $query = "INSERT INTO settings (
                            shop_name, shop_phone, shop_email, shop_address,
                            telegram_token, telegram_chat_id, promptpay_number
                          ) VALUES (
                            :shop_name, :shop_phone, :shop_email, :shop_address,
                            :telegram_token, :telegram_chat_id, :promptpay_number
                          )";
                $stmt = $this->conn->prepare($query);
            }

            $stmt->bindValue(':shop_name', $payload['shop_name']);
            $stmt->bindValue(':shop_phone', $payload['shop_phone']);
            $stmt->bindValue(':shop_email', $payload['shop_email']);
            $stmt->bindValue(':shop_address', $payload['shop_address']);
            $stmt->bindValue(':telegram_token', $payload['telegram_token']);
            $stmt->bindValue(':telegram_chat_id', $payload['telegram_chat_id']);
            $stmt->bindValue(':promptpay_number', $payload['promptpay_number']);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Settings save error: '.$e->getMessage());
            return false;
        }
    }

    private function connect(): void
    {
        $this->conn = new PDO(
            "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",
            DB_USER,
            DB_PASS
        );
        $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->initializeSchema();
    }

    /**
     * @return mixed
     */
    private function fetchSettingsRow(): ?array
    {
        $query = "SELECT * FROM settings ORDER BY id ASC LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    private function initializeSchema(): void
    {
        $createTable = "CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            shop_name VARCHAR(255) NOT NULL DEFAULT 'โบว์ไว้อาลัย',
            shop_phone VARCHAR(50) DEFAULT NULL,
            shop_email VARCHAR(255) DEFAULT NULL,
            shop_address TEXT NULL,
            telegram_token VARCHAR(255) NULL,
            telegram_chat_id VARCHAR(255) NULL,
            promptpay_number VARCHAR(50) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";

        $this->conn->exec($createTable);
    }

    /**
     * @param array $row
     * @return mixed
     */
    private function normalizeSettings(?array $row): array
    {
        $defaults = [
            'shop_name' => SITE_NAME,
            'shop_phone' => SHOP_PHONE,
            'shop_email' => SHOP_EMAIL,
            'shop_address' => '',
            'telegram_token' => TELEGRAM_BOT_TOKEN,
            'telegram_chat_id' => TELEGRAM_CHAT_ID,
            'promptpay_number' => PROMPTPAY_ID,
            'promptpay_id' => PROMPTPAY_ID,
            'promptpay_name' => PROMPTPAY_NAME
        ];

        if (!$row) {
            return $defaults;
        }

        foreach ($defaults as $key => $value) {
            if (!array_key_exists($key, $row) || $row[$key] === null) {
                $row[$key] = $value;
            }
        }

        if (isset($row['promptpay_number']) && !isset($row['promptpay_id'])) {
            $row['promptpay_id'] = $row['promptpay_number'];
        }

        return $row;
    }
}
