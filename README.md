# Kai Shop - Website Bán Tài Khoản

Website bán tài khoản game, app, dịch vụ trực tuyến với giao diện hiện đại và tính năng đầy đủ.

## 🚀 Cài Đặt

### Yêu Cầu
- PHP 7.4 hoặc cao hơn
- MySQL 5.7 hoặc cao hơn
- XAMPP/WAMP/LAMP
- Composer (tùy chọn)

### Các Bước Cài Đặt

1. **Clone hoặc Copy project vào thư mục htdocs**
   ```bash
   cd C:\xampp\htdocs
   ```

2. **Tạo file cấu hình môi trường**
   - Copy file `.env.example` thành `.env`
   - Mở file `.env` và cấu hình thông tin của bạn:
   
   ```env
   # Thay đổi URL theo domain của bạn
   APP_URL=http://localhost/kaishop
   
   # Cấu hình database
   DB_HOST=localhost
   DB_NAME=kaishop
   DB_USER=root
   DB_PASS=
   
   # Cấu hình email (nếu cần)
   SMTP_USER=your-email@gmail.com
   SMTP_PASS=your-app-password
   ```

3. **Import Database**
   - Mở phpMyAdmin
   - Tạo database mới tên `kaishop`
   - Import file `COMPLETE_DATABASE.sql`

4. **Cấu hình Apache (nếu cần)**
   - Bật `mod_rewrite` trong Apache
   - File `.htaccess` đã được cấu hình sẵn

5. **Truy cập website**
   ```
   http://localhost/kaishop
   ```

## 📁 Cấu Trúc Thư Mục

```
kaishop/
├── .env                    # File cấu hình môi trường (không commit)
├── .env.example           # Template cấu hình
├── .htaccess              # Cấu hình Apache
├── admin/                 # Trang quản trị
├── api/                   # API endpoints
├── assets/                # CSS, JS, Images
├── auth/                  # Đăng nhập/Đăng ký
├── config/                # File cấu hình
│   ├── config.php        # Cấu hình chính (đọc từ .env)
│   └── database.php      # Kết nối database
├── giohang/              # Giỏ hàng
├── includes/             # Components & Helpers
├── sanpham/              # Trang sản phẩm
├── user/                 # Trang người dùng
└── index.php             # Trang chủ
```

## ⚙️ Cấu Hình

### File .env

Tất cả cấu hình quan trọng được đặt trong file `.env`:

- **APP_URL**: URL của website (VD: `http://localhost/kaishop` hoặc `https://yourdomain.com`)
- **APP_NAME**: Tên website
- **DB_***: Thông tin database
- **SMTP_***: Cấu hình email
- **DEFAULT_CURRENCY**: Đơn vị tiền tệ (VND/USD)
- **EXCHANGE_RATE**: Tỷ giá quy đổi

### Thay Đổi Domain

Khi chuyển domain, chỉ cần sửa file `.env`:

```env
APP_URL=https://your-new-domain.com
```

**Lưu ý quan trọng:**
- Tất cả URL trong code đã sử dụng biến `BASE_URL` từ file `.env`
- Không hard-code domain trực tiếp trong code PHP/HTML
- Khi cần sử dụng URL trong code, dùng: `<?= BASE_URL ?>` hoặc hàm `url()`
- Trong JavaScript, dùng: `window.APP_URL` (đã được define trong HeaderComponent)

**Ví dụ sử dụng:**
```php
// ✅ ĐÚNG - Dùng biến môi trường
<a href="<?= BASE_URL ?>/sanpham">Sản phẩm</a>
<img src="<?= BASE_URL ?>/assets/images/logo.png">

// ❌ SAI - Hard-code domain
<a href="https://kaishop.id.vn/sanpham">Sản phẩm</a>
```

## 🔒 Bảo Mật

- File `.env` được bảo vệ bởi `.htaccess` (không thể truy cập từ browser)
- Không commit file `.env` lên Git (đã thêm vào `.gitignore`)
- Chỉ commit file `.env.example` để làm template

## 🛠️ Tính Năng

- ✅ Quản lý sản phẩm/tài khoản
- ✅ Giỏ hàng & Thanh toán
- ✅ Quản lý user & Admin panel
- ✅ Nạp tiền qua nhiều phương thức
- ✅ Hệ thống thông báo
- ✅ Chế độ bảo trì
- ✅ Responsive design
- ✅ Bình luận & Đánh giá

## 📞 Hỗ Trợ

Nếu gặp vấn đề, vui lòng kiểm tra:

1. PHP version >= 7.4
2. MySQL đã chạy
3. Database đã import đúng
4. File `.env` đã cấu hình đúng
5. Apache `mod_rewrite` đã bật

## 📝 License

Copyright © 2024 Kai Shop
