-- Create database
CREATE DATABASE IF NOT EXISTS monochrome_bowties;
USE monochrome_bowties;

-- Create products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    category VARCHAR(50) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    images TEXT,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `stock` int(11) DEFAULT 100
);

-- Create orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) NOT NULL UNIQUE,
    customer_name VARCHAR(255) NOT NULL,
    customer_email VARCHAR(255) NOT NULL,
    customer_phone VARCHAR(50) NOT NULL,
    customer_address VARCHAR(255) NOT NULL,
    customer_city VARCHAR(100) NOT NULL,
    customer_postal VARCHAR(20) NOT NULL,
    customer_country VARCHAR(100) NOT NULL,
    items TEXT NOT NULL,
    shipping_method VARCHAR(50) NOT NULL,
    shipping_cost DECIMAL(10, 2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    payment_status VARCHAR(50) NOT NULL DEFAULT 'pending',
    subtotal DECIMAL(10, 2) NOT NULL,
    total DECIMAL(10, 2) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'pending',
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create admin_users table
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert sample admin user (password: admin123)
INSERT INTO admin_users (username, password) VALUES
('admin', '$2y$12$fn4x18d.CaFlt9ZpJTE4KO5RXTnk3nwqnBUjl/DfWRzPAHRAsklYm');

-- Insert sample products
INSERT INTO `products` (`id`, `name`, `price`, `category`, `description`, `image`, `images`, `details`, `created_at`, `updated_at`, `stock`) VALUES
(1, 'โบว์ผ้าซาตินเรียบง่าย ดูสุภาพและสง่างาม', 45.00, 'classic', 'เสริมความสุภาพและความสง่างามด้วยโบว์ผ้าซาตินสีดำเนื้อเนียนละเอียด ออกแบบมาอย่างเรียบง่ายแต่เปี่ยมด้วยรสนิยม เหมาะสำหรับแสดงความเคารพในทุกโอกาสสำคัญ', 'images/bow-1.png', '[\"images/bow-1.png\"]', '[\"100% Silk\", \"Self-tie design\", \"Adjustable neckband\", \"Dry clean only\"]', '2025-10-25 07:56:22', '2025-10-25 07:56:22', 100),
(2, 'โบว์ผ้ากำมะหยี่ ให้ความรู้สึกหรูหราและนุ่มนวล', 38.00, 'premium', 'เติมเต็มชุดของคุณด้วยโบว์ผ้ากำมะหยี่สีดำที่ให้สัมผัสนุ่มนวลและดูหรูหราอย่างมีระดับ สะท้อนถึงความประณีตและความใส่ใจในรายละเอียด เหมาะสำหรับผู้ที่ต้องการความพิเศษในวันสำคัญ', 'images/bow-2.png', '[\"images/bow-2.png\"]', '[\"100% Wool\", \"Pre-tied design\", \"Adjustable neckband\", \"Spot clean\"]', '2025-10-25 07:56:22', '2025-10-25 07:56:22', 100),
(3, 'โบว์ผ้าลูกไม้สีดำ เพิ่มความละเอียดอ่อนและความประณีต', 32.00, 'luxury', 'แสดงความรู้สึกด้วยโบว์ผ้าลูกไม้สีดำ ดีไซน์ละเอียดอ่อน เพิ่มความประณีตและความสง่างามให้กับเครื่องแต่งกายของคุณอย่างลงตัว เหมาะสำหรับผู้ที่ต้องการความแตกต่างอย่างมีรสนิยม', 'images/bow-3.png', '[\"images/bow-3.png\"]', '[\"100% Cotton\", \"Self-tie design\", \"Adjustable neckband\", \"Machine washable\"]', '2025-10-25 07:56:22', '2025-10-25 07:56:22', 100),
(4, 'โบว์ผ้าก้างปลา มีพื้นผิวสัมผัสที่น่าสนใจ ไม่เรียบแบนจนเกินไป', 55.00, 'classic', 'สร้างความโดดเด่นอย่างมีสไตล์ด้วยโบว์ผ้าก้างปลาสีดำ ที่มีพื้นผิวสัมผัสไม่เรียบแบนจนเกินไป เพิ่มมิติและความน่าสนใจให้กับลุคของคุณ แสดงถึงความพิถีพิถันในการแต่งกาย', 'images/bow-4.png', '[\"images/bow-4.png\"]', '[\"100% Velvet\", \"Self-tie design\", \"Adjustable neckband\", \"Dry clean only\"]', '2025-10-25 07:56:22', '2025-10-25 07:56:22', 100),
(5, 'โบว์แบบมีจีบเล็กน้อย เพิ่มมิติและความอ่อนช้อย', 42.00, 'premium', 'เติมความอ่อนช้อยและมีมิติให้กับชุดของคุณด้วยโบว์สีดำแบบมีจีบเล็กน้อย ให้ความรู้สึกนุ่มนวลและสง่างามอย่างเป็นธรรมชาติ เหมาะสำหรับผู้ที่ต้องการความเรียบง่ายแต่แฝงด้วยรายละเอียด', 'images/bow-5.png', '[\"images/bow-5.png\"]', '[\"100% Linen\", \"Pre-tied design\", \"Adjustable neckband\", \"Hand wash\"]', '2025-10-25 07:56:22', '2025-10-25 07:56:22', 100),
(6, 'โบว์แบบริบบิ้นสองชั้น ให้ความรู้สึกแน่นหนาและสง่างามเป็นพิเศษ', 35.00, 'luxury', 'แสดงความเคารพอย่างเต็มที่ด้วยโบว์ริบบิ้นสองชั้นสีดำ ที่ให้ความรู้สึกแน่นหนาและสง่างามเป็นพิเศษ สะท้อนถึงความมุ่งมั่นและความจริงใจในโอกาสสำคัญอย่างสมบูรณ์แบบ', 'images/bow-6.png', '[\"images/bow-6.png\"]', '[\"Polyester blend\", \"Self-tie design\", \"Adjustable neckband\", \"Spot clean\"]', '2025-10-25 07:56:22', '2025-10-25 07:56:22', 100);

--
-- Create settings table
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shop_name VARCHAR(255) NOT NULL DEFAULT 'โบว์ไว้อาลัย',
    shop_phone VARCHAR(50) DEFAULT '081-234-5678',
    shop_email VARCHAR(255) DEFAULT 'admin@localhost.com',
    shop_address TEXT,
    telegram_token VARCHAR(255),
    telegram_chat_id VARCHAR(255),
    promptpay_number VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default settings
INSERT INTO settings (shop_name, shop_phone, shop_email) VALUES
('โบว์ไว้อาลัย', '081-234-5678', 'admin@localhost.com');
