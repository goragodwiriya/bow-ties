# โบว์ไว้อาลัย - E-commerce

ระบบขายโบว์ออนไลน์ พร้อมระบบจัดการคำสั่งซื้อและแจ้งเตือนผ่าน Telegram

## 📋 คุณสมบัติ

### หน้าร้าน (Frontend)
- 🛍️ หน้าร้านออนไลน์ที่ใช้งานง่าย
- 📱 Responsive Design รองรับทุกอุปกรณ์
- 🛒 ระบบตะกร้าสินค้าพร้อม LocalStorage
- 📦 หน้าแสดงรายละเอียดสินค้าแบบ Modal
- � ระบบ Checkout พร้อม QR Code PromptPay
- �📞 ฟอร์มติดต่อพร้อม Telegram notification
- ✅ หน้ายืนยันการชำระเงินด้วยอีเมล

### ระบบจัดการ (Backend)
- 🔐 ระบบเข้าสู่ระบบพร้อม CSRF protection
- 📊 Dashboard แสดงสถิติและกราฟยอดขาย 7 วัน
- 🛍️ จัดการสินค้า (เพิ่ม/แก้ไข/ลบ) พร้อม JSON details
- 📋 จัดการคำสั่งซื้อ (ดู/อัปเดตสถานะ/ลบ)
- 📈 รายงานยอดขาย (รายวัน/รายเดือน)
- ⚙️ ตั้งค่าร้าน/Telegram/PromptPay
- 🔑 เปลี่ยนรหัสผ่านผู้ดูแล

### การแจ้งเตือน
- 📨 แจ้งเตือนคำสั่งซื้อใหม่ผ่าน Telegram Bot
- 📧 แจ้งเตือนจากฟอร์มติดต่อผ่าน Telegram

## 🛠️ เทคโนโลยีที่ใช้

### Frontend
- HTML5 (Semantic markup)
- CSS3 (Custom styling with Flexbox/Grid)
- JavaScript ES6+ (Vanilla JS, no frameworks)
- Canvas API (สำหรับกราฟยอดขาย)
- LocalStorage (เก็บตะกร้าสินค้า)
- Google Fonts (Sarabun, Prompt)

### Backend
- PHP 8.0+
- MySQL 8.0+ (PDO with prepared statements)
- RESTful API (JSON responses)
- Session-based authentication
- CSRF Token protection
- Environment variable configuration

### External Services
- Telegram Bot API (แจ้งเตือนคำสั่งซื้อและข้อความ)
- PromptPay QR Code generation

## 📁 โครงสร้างโปรเจค

```
bow-ties/
├── .env                    # Environment configuration (ห้าม commit)
├── .gitignore             # Git ignore rules
├── index.html             # หน้าหลักของร้าน
├── app.js                 # JavaScript หลัก (frontend)
├── styles.css             # CSS สำหรับหน้าร้าน
├── schema.sql             # โครงสร้างฐานข้อมูล MySQL
├── README.md              # เอกสารโปรเจค
├── SECURITY.md            # รายงานความปลอดภัย
├── admin/                 # ระบบจัดการผู้ดูแล
│   ├── index.html         # Dashboard, Orders, Products, Reports, Settings
│   ├── login.html         # หน้าเข้าสู่ระบบ
│   ├── check-auth.php     # ตรวจสอบสถานะการล็อกอิน
│   ├── admin.js           # JavaScript สำหรับ Admin
│   └── admin.css          # CSS สำหรับ Admin
├── api/                   # REST API Endpoints
│   ├── .htaccess          # Apache configuration
│   ├── config.php         # การตั้งค่า + .env loader
│   ├── products.php       # GET สินค้าทั้งหมด (public)
│   ├── contact.php        # POST ฟอร์มติดต่อ (public)
│   ├── admin/             # Admin API endpoints
│   │   ├── login.php      # POST เข้าสู่ระบบ
│   │   ├── logout.php     # POST ออกจากระบบ
│   │   ├── change-password.php  # POST เปลี่ยนรหัสผ่าน
│   │   ├── orders.php     # GET รายการออเดอร์
│   │   ├── products.php   # POST/GET/PUT/DELETE สินค้า
│   │   ├── reports.php    # GET รายงานยอดขาย
│   │   ├── settings.php   # GET/POST ตั้งค่าร้าน
│   │   ├── test-telegram.php  # POST ทดสอบ Telegram
│   │   ├── init-settings.php  # สร้างตาราง settings
│   │   ├── orders/        # Order operations
│   │   │   ├── update.php # POST อัปเดตสถานะ
│   │   │   └── delete.php # DELETE ลบออเดอร์
│   │   └── products/      # Product operations
│   │       ├── update.php # POST อัปเดตสินค้า
│   │       └── delete.php # DELETE ลบสินค้า
│   ├── models/            # Backend Model Classes
│   │   ├── Auth.php       # Authentication & CSRF
│   │   ├── OrderModel.php # คำสั่งซื้อ CRUD + stats
│   │   ├── ProductModel.php  # สินค้า CRUD
│   │   ├── Notification.php  # Telegram integration
│   │   └── SettingsModel.php # ตั้งค่าร้าน
│   ├── orders/            # Public order endpoints
│   │   ├── create.php     # POST สร้างออเดอร์ใหม่
│   │   └── {id}.php       # GET ดูออเดอร์ / POST ยืนยันชำระเงิน
│   └── logs/              # Error logs (auto-created)
│       └── error.log
├── models/                # Frontend Model Classes
│   ├── Database.php       # PDO connection wrapper
│   ├── Auth.php           # Legacy auth (ใช้ api/models/Auth.php แทน)
│   ├── ProductModel.php   # Legacy product model
│   ├── OrderModel.php     # Legacy order model
│   └── Notification.php   # Legacy notification
└── images/                # รูปภาพสินค้า
    └── bow-*.png          # Product images
```

## 🚀 การติดตั้ง

### 1. ข้อกำหนดของระบบ
- PHP 8.0 หรือสูงกว่า
- MySQL 8.0 หรือสูงกว่า
- Apache/Nginx (รองรับ .htaccess)
- PHP Extensions: PDO, PDO_MySQL, cURL, JSON, mbstring

### 2. Clone Repository
```bash
git clone <repository-url> bow-ties
cd bow-ties
```

### 3. ติดตั้งฐานข้อมูล
```bash
# เข้าสู่ MySQL
mysql -u root -p

# นำเข้าโครงสร้างฐานข้อมูล
mysql -u root -p < schema.sql
```

หรือ import ผ่าน phpMyAdmin โดยเปิดไฟล์ `schema.sql`

### 4. ตั้งค่า Environment Variables
สร้างไฟล์ `.env` ในโฟลเดอร์หลัก:

```bash
cp .env.example .env
```

แก้ไข `.env`:

```properties
# Database configuration
DB_HOST=localhost
DB_NAME=monochrome_bowties
DB_USER=root
DB_PASS=your_secure_password

# Site configuration
SITE_URL=https://yourdomain.com
SITE_NAME=โบว์ไว้อาลัย
SHOP_PHONE=086-xxx-xxxx
SHOP_EMAIL=info@yourdomain.com

# PromptPay settings
PROMPTPAY_ID=0861234567  # เบอร์โทร 10 หลัก หรือ เลขประจำตัวผู้เสียภาษี 13 หลัก
PROMPTPAY_NAME=โบว์ไว้อาลัย

# Telegram bot configuration
TELEGRAM_BOT_TOKEN=123456789:ABCdefGHIjklMNOpqrsTUVwxyz
TELEGRAM_CHAT_ID=@your_channel_or_chat_id
```

### 5. ตั้งค่าสิทธิ์ไฟล์
```bash
# สร้างโฟลเดอร์ logs
mkdir -p api/logs

# ตั้งค่าสิทธิ์
chmod 755 images/ api/logs/
chown www-data:www-data images/ api/logs/
chmod 644 .env
```

### 6. ตั้งค่า Admin User
เข้าสู่ระบบที่ `/admin/login.html` ด้วย:
- Username: `admin`
- Password: `admin123`

**⚠️ สำคัญ:** เปลี่ยนรหัสผ่านทันทีหลังเข้าสู่ระบบครั้งแรก!

### 7. ตั้งค่า Telegram Bot (ถ้าต้องการ)
1. สร้าง Bot ผ่าน [@BotFather](https://t.me/BotFather)
2. คัดลอก Bot Token
3. หา Chat ID หรือสร้าง Channel
4. ใส่ข้อมูลใน `.env`
5. ทดสอบผ่านหน้า Settings ในระบบ Admin

## 📱 การใช้งาน

### สำหรับลูกค้า
1. เข้าไปที่หน้าเว็บหลัก
2. เลือกดูสินค้าในหมวด "สินค้า"
3. เพิ่มสินค้าลงตะกร้า
4. กรอกข้อมูลการสั่งซื้อ
5. ชำระเงินผ่าน PromptPay

### สำหรับผู้ดูแล
1. เข้าสู่ระบบที่ `/admin/login.html`
2. จัดการสินค้าในระบบ
3. ดูและจัดการคำสั่งซื้อ
4. ดูรายงานยอดขาย

## 🔧 API Endpoints

### Public Endpoints (ไม่ต้องล็อกอิน)

#### สินค้า
- `GET /api/products.php` - ดึงรายการสินค้าทั้งหมด
- `GET /api/products.php?category={category}` - ดึงสินค้าตามหมวดหมู่

#### คำสั่งซื้อ
- `POST /api/orders/create.php` - สร้างคำสั่งซื้อใหม่
- `GET /api/orders/{id}.php` - ดูข้อมูลออเดอร์ (ต้องล็อกอินเป็น admin)
- `POST /api/orders/{id}.php` - ยืนยันการชำระเงิน (ต้องระบุ email)

#### อื่นๆ
- `POST /api/contact.php` - ส่งข้อความติดต่อ (แจ้งเตือนผ่าน Telegram)

### Admin Endpoints (ต้อง Authentication + CSRF Token)

#### Authentication
- `POST /api/admin/login.php` - เข้าสู่ระบบ
  - Body: `{"username": "admin", "password": "admin123"}`
  - Response: `{"success": true, "token": "csrf_token_here"}`
- `POST /api/admin/logout.php` - ออกจากระบบ
- `POST /api/admin/change-password.php` - เปลี่ยนรหัสผ่าน
  - Body: `{"current_password": "old", "new_password": "new"}`

#### สินค้า
- `GET /api/admin/products.php` - ดึงรายการสินค้าทั้งหมด
- `POST /api/admin/products.php` - เพิ่มสินค้าใหม่
- `POST /api/admin/products/update.php` - แก้ไขสินค้า
- `POST /api/admin/products/delete.php` - ลบสินค้า

#### คำสั่งซื้อ
- `GET /api/admin/orders.php` - ดึงรายการคำสั่งซื้อทั้งหมด
- `POST /api/admin/orders/update.php` - อัปเดตสถานะ
  - Body: `{"id": 1, "status": "completed"}`
  - Statuses: `pending`, `processing`, `completed`, `cancelled`
- `DELETE /api/admin/orders/delete.php` - ลบออเดอร์

#### รายงาน
- `GET /api/admin/reports.php?type=overview` - สถิติรวมและยอดขาย 7 วัน
- `GET /api/admin/reports.php?type=daily&days=30` - ยอดขายรายวัน

#### ตั้งค่า
- `GET /api/admin/settings.php` - ดึงการตั้งค่าร้าน
- `POST /api/admin/settings.php` - บันทึกการตั้งค่า
- `POST /api/admin/test-telegram.php` - ทดสอบส่ง Telegram

**หมายเหตุ:** ทุก Admin endpoint ต้องส่ง Header:
```
X-CSRF-Token: {token_from_login}
Credentials: same-origin
```

## 🔐 ความปลอดภัย

### มาตรการความปลอดภัยที่ใช้
- ✅ **CSRF Token Protection** - ทุก admin mutation ต้องมี token
- ✅ **SQL Injection Prevention** - PDO prepared statements ทั้งหมด
- ✅ **XSS Protection** - `htmlspecialchars()` และ security headers
- ✅ **Session Management** - regeneration, timeout (1 ชั่วโมง)
- ✅ **Input Validation** - email, numeric, regex patterns
- ✅ **Password Hashing** - bcrypt (`PASSWORD_DEFAULT`)
- ✅ **Environment Variables** - credentials ใน `.env` (ไม่ commit)
- ✅ **Error Logging** - ไม่แสดง error ให้ user, log ลง file
- ✅ **Security Headers** - X-Frame-Options, X-XSS-Protection, etc.
- ✅ **No Mock Data** - ใช้ข้อมูลจริงจาก database เท่านั้น

### ⚠️ สิ่งที่ต้องทำก่อน Production
1. เปลี่ยนรหัสผ่าน admin เริ่มต้น
2. จำกัด CORS ใน `api/products.php` และ `api/orders/create.php`
3. เปิดใช้ HTTPS และบังคับ redirect
4. ตรวจสอบว่า `.env` เข้าถึงผ่าน web ไม่ได้
5. ตั้งค่า rate limiting สำหรับ login และ order creation
6. สำรองข้อมูลสม่ำเสมอ

## 📞 การติดต่อ

- **เว็บไซต์**: [https://kotchasan.com](https://kotchasan.com)
- **โทรศัพท์**: 086-814-2004
- **อีเมล**: admin@goragod.com

## 📄 License

This project is proprietary software. All rights reserved.