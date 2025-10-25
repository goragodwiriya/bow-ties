<?php
/**
 * Initialize settings table
 * Run this once to create settings table and default data
 */

require_once __DIR__ . '/../config.php';

try {
    $conn = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create settings table
    $createTable = "CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        shop_name VARCHAR(255) NOT NULL DEFAULT 'โบว์ไว้อาลัย',
        shop_phone VARCHAR(50) DEFAULT '02-xxx-xxxx',
        shop_email VARCHAR(255) DEFAULT 'info@bowties.com',
        shop_address TEXT,
        telegram_token VARCHAR(255),
        telegram_chat_id VARCHAR(255),
        promptpay_number VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";

    $conn->exec($createTable);
    echo "✅ Settings table created successfully\n";

    // Check if settings exist
    $checkQuery = "SELECT COUNT(*) as count FROM settings";
    $stmt = $conn->prepare($checkQuery);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['count'] == 0) {
        // Insert default settings
        $insertQuery = "INSERT INTO settings (shop_name, shop_phone, shop_email)
                        VALUES ('โบว์ไว้อาลัย', '02-xxx-xxxx', 'info@bowties.com')";
        $conn->exec($insertQuery);
        echo "✅ Default settings inserted successfully\n";
    } else {
        echo "ℹ️  Settings already exist\n";
    }

    // Add stock column to products if not exists
    $alterProducts = "ALTER TABLE products ADD COLUMN IF NOT EXISTS stock INT DEFAULT 100";
    try {
        $conn->exec($alterProducts);
        echo "✅ Stock column added to products table\n";
    } catch(PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "ℹ️  Stock column already exists\n";
        } else {
            throw $e;
        }
    }

    echo "\n🎉 Initialization completed successfully!\n";

} catch(PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
