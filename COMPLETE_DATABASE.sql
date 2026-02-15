
-- Tắt foreign key checks để drop tables
SET FOREIGN_KEY_CHECKS = 0;

-- Drop database hoàn toàn
DROP DATABASE IF EXISTS kaishop;

-- Tạo database mới
CREATE DATABASE kaishop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE kaishop;

-- Bật lại foreign key checks
SET FOREIGN_KEY_CHECKS = 1;
-- ============================================================================
-- 1. USERS TABLE - Bảng người dùng
-- ============================================================================
CREATE TABLE users (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT COMMENT 'User ID',
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NULL COMMENT 'NULL for OAuth users (Google login)',
    full_name VARCHAR(100),
    phone VARCHAR(20),
    balance_vnd DECIMAL(15, 0) DEFAULT 0,
    balance_usd DECIMAL(10, 2) DEFAULT 0.00,
    role ENUM('user', 'admin') DEFAULT 'user',
    avatar VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    last_read_notifications TIMESTAMP NULL,
    reset_token VARCHAR(255) NULL COMMENT 'Password reset token',
    reset_expires TIMESTAMP NULL COMMENT 'Reset token expiration time',
    INDEX idx_email (email),
    INDEX idx_username (username),
    INDEX idx_role (role),
    INDEX idx_is_active (is_active),
    INDEX idx_reset_token (reset_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 2. CATEGORIES TABLE - Danh mục sản phẩm
-- ============================================================================
CREATE TABLE categories (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT COMMENT 'Category ID',
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    icon_value VARCHAR(255) DEFAULT '🎮' COMMENT 'Icon value: emoji character, fontawesome class name, or image filename',
    icon_type ENUM('emoji', 'fontawesome', 'image') DEFAULT 'emoji' COMMENT 'Type of icon: emoji, fontawesome class, or uploaded image',
    icon_url VARCHAR(500) NULL DEFAULT NULL COMMENT 'URL for uploaded icon',
    display_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Danh mục sản phẩm với icon hỗ trợ emoji/fontawesome/image';

-- ============================================================================
-- 3. PRODUCTS TABLE - Sản phẩm (HOÀN CHỈNH VỚI DISCOUNT COLUMNS)
-- ============================================================================
CREATE TABLE products (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT COMMENT 'Product ID',
    category_id BIGINT UNSIGNED NULL DEFAULT NULL,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(200) UNIQUE NOT NULL,
    description TEXT,
    content LONGTEXT,
    price_vnd DECIMAL(15, 0) NOT NULL,
    price_usd DECIMAL(10, 2) NOT NULL,
    discount_percent INT DEFAULT 0,
    discount_amount_vnd DECIMAL(15,0) DEFAULT 0,
    discount_amount_usd DECIMAL(10,2) DEFAULT 0,
    final_price_vnd DECIMAL(15,0) DEFAULT 0,
    final_price_usd DECIMAL(10,2) DEFAULT 0,
    stock INT DEFAULT 0,
    min_purchase INT DEFAULT 1,
    max_purchase INT DEFAULT 999,
    image VARCHAR(255),
    images TEXT COMMENT 'JSON array',
    label VARCHAR(50) DEFAULT 'NORMAL',
    label_color VARCHAR(7) DEFAULT '#8b5cf6',
    label_bg_color VARCHAR(7) DEFAULT '#ffffff',
    label_text_color VARCHAR(7) DEFAULT '#ffffff',
    label_id INT UNSIGNED NULL DEFAULT NULL COMMENT 'ID nhãn sản phẩm (tham chiếu product_labels)',
    sold_count INT DEFAULT 0,
    view_count INT DEFAULT 0,
    views INT DEFAULT 0,
    likes INT DEFAULT 0,
    rating_avg DECIMAL(2, 1) DEFAULT 5.0,
    rating_count INT DEFAULT 0,
    is_featured TINYINT(1) DEFAULT 0,
    is_pinned TINYINT(1) DEFAULT 0 COMMENT 'Sản phẩm được ghim lên đầu',
    is_hidden TINYINT(1) DEFAULT 0 COMMENT 'Hiển thị/Ẩn sản phẩm',
    is_active TINYINT(1) DEFAULT 1,
    requires_customer_info TINYINT(1) DEFAULT 0 COMMENT 'Requires customer to provide info (email, phone, etc.)',
    customer_info_label VARCHAR(500) DEFAULT NULL COMMENT 'Label/prompt for customer info field',
    product_type VARCHAR(50) DEFAULT 'account' COMMENT 'Loại sản phẩm: account, source, book',
    delivery_content TEXT NULL COMMENT 'Link download cho Source/Book (Account thì dùng product_stock_pool)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_slug (slug),
    INDEX idx_category (category_id),
    INDEX idx_featured (is_featured),
    INDEX idx_pinned (is_pinned),
    INDEX idx_label (label),
    INDEX idx_label_id (label_id),
    INDEX idx_stock (stock),
    INDEX idx_is_active (is_active),
    INDEX idx_product_type (product_type),
    INDEX idx_category_active (category_id, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Sản phẩm hỗ trợ 3 loại: Account, Source Code, Book';

-- ============================================================================
-- 4. PRODUCT_VARIANTS TABLE - Biến thể sản phẩm (MỖI VARIANT = 1 SẢN PHẨM ĐỘC LẬP)
-- ============================================================================
CREATE TABLE IF NOT EXISTS product_variants (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT COMMENT 'Variant ID',
    product_id BIGINT UNSIGNED NOT NULL,
    variant_name VARCHAR(100) NOT NULL COMMENT 'Tên variant: Gói 1 Tháng, Gói Premium, etc',
    price_vnd DECIMAL(15,0) NOT NULL DEFAULT 0 COMMENT 'Giá gốc VND',
    price_usd DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Giá gốc USD',
    discount_percent INT NOT NULL DEFAULT 0 COMMENT 'Phần trăm giảm giá (0-100)',
    discount_amount_vnd DECIMAL(15,0) NOT NULL DEFAULT 0 COMMENT 'Số tiền giảm VND',
    discount_amount_usd DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Số tiền giảm USD',
    final_price_vnd DECIMAL(15,0) NOT NULL DEFAULT 0 COMMENT 'Giá cuối VND',
    final_price_usd DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Giá cuối USD',
    stock INT NOT NULL DEFAULT 0 COMMENT 'Số lượng tồn kho',
    min_purchase INT NOT NULL DEFAULT 1 COMMENT 'Số lượng mua tối thiểu',
    max_purchase INT NOT NULL DEFAULT 999 COMMENT 'Số lượng mua tối đa',
    requires_customer_info TINYINT(1) DEFAULT 0 COMMENT 'Yêu cầu khách hàng nhập thông tin (0=Upload TK, 1=Nhập info)',
    customer_info_label VARCHAR(500) DEFAULT NULL COMMENT 'Label/prompt cho trường thông tin khách hàng',
    account_data TEXT COMMENT 'Dữ liệu tài khoản (deprecated - dùng product_stock_pool)',
    is_default TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = variant mặc định được chọn',
    sort_order INT NOT NULL DEFAULT 0 COMMENT 'Thứ tự sắp xếp',
    is_active TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = active, 0 = inactive',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product_id (product_id),
    INDEX idx_active (is_active),
    INDEX idx_default (is_default),
    INDEX idx_product_active (product_id, is_active),
    INDEX idx_product_default (product_id, is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Product variants - Mỗi variant như 1 sản phẩm độc lập';

-- ============================================================================
-- 4B. PRODUCT_STOCK_POOL TABLE - Kho tài khoản cho sản phẩm loại Account
-- ============================================================================
CREATE TABLE IF NOT EXISTS product_stock_pool (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  product_id BIGINT UNSIGNED NOT NULL COMMENT 'Product ID',
  variant_id BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Variant ID (NULL cho sản phẩm không có variant)',
  content TEXT NOT NULL COMMENT 'Account content format: username|password',
  is_used TINYINT(1) DEFAULT 0 COMMENT '0=Chưa bán, 1=Đã bán',
  used_by_order_id BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Order ID đã mua tài khoản này',
  used_at TIMESTAMP NULL DEFAULT NULL COMMENT 'Thời gian bán',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_product (product_id),
  KEY idx_variant (variant_id),
  KEY idx_used (is_used),
  KEY idx_order (used_by_order_id),
  KEY idx_product_variant_used (product_id, variant_id, is_used),
  CONSTRAINT fk_stock_pool_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Kho tài khoản cho sản phẩm loại Account';

-- ============================================================================
-- 5. ORDERS TABLE - Đơn hàng
-- ============================================================================
CREATE TABLE orders (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT COMMENT 'Order ID',
    user_id BIGINT UNSIGNED NOT NULL,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    total_amount_vnd DECIMAL(15, 0) NOT NULL,
    total_amount_usd DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'VND',
    status ENUM('pending', 'processing', 'completed', 'cancelled', 'refunded') DEFAULT 'pending',
    payment_method VARCHAR(50),
    payment_status ENUM('unpaid', 'paid', 'refunded') DEFAULT 'unpaid',
    note TEXT,
    cancellation_reason TEXT COMMENT 'Lý do hủy đơn',
    ip_address VARCHAR(45),
    voucher_code VARCHAR(50) NULL COMMENT 'Voucher code',
    voucher_id BIGINT UNSIGNED NULL COMMENT 'Voucher ID',
    discount_amount DECIMAL(15, 2) DEFAULT 0.00 COMMENT 'Số tiền giảm giá',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_order_number (order_number),
    INDEX idx_voucher_code (voucher_code),
    INDEX idx_created_at (created_at),
    INDEX idx_user_status (user_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 6. ORDER_ITEMS TABLE - Chi tiết đơn hàng
-- ============================================================================
CREATE TABLE order_items (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT COMMENT 'Order Item ID',
    order_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    variant_id BIGINT UNSIGNED NULL COMMENT 'Product Variant ID (nếu có)',
    product_name VARCHAR(200) NOT NULL,
    product_image VARCHAR(255),
    quantity INT NOT NULL,
    price DECIMAL(15, 2) NOT NULL,
    price_vnd DECIMAL(15, 0) NOT NULL,
    price_usd DECIMAL(10, 2) NOT NULL,
    subtotal_vnd DECIMAL(15, 0) NOT NULL,
    subtotal_usd DECIMAL(10, 2) NOT NULL,
    account_data TEXT COMMENT 'Dữ liệu tài khoản',
    customer_info TEXT COMMENT 'Thông tin khách hàng',
    delivery_content TEXT NULL COMMENT 'Nội dung giao hàng: Link download cho Source/Book, hoặc tài khoản cho Account',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_order (order_id),
    INDEX idx_product (product_id),
    INDEX idx_variant (variant_id),
    INDEX idx_product_variant (product_id, variant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Chi tiết đơn hàng với hỗ trợ delivery_content';

-- ============================================================================
-- 6B. ORDER_ITEM_ACCOUNTS TABLE - Liên kết order với accounts đã giao
-- ============================================================================
CREATE TABLE IF NOT EXISTS order_item_accounts (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_item_id BIGINT UNSIGNED NOT NULL COMMENT 'Order Item ID',
  stock_pool_id BIGINT UNSIGNED NOT NULL COMMENT 'ID tài khoản từ product_stock_pool',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_order_item (order_item_id),
  KEY idx_stock_pool (stock_pool_id),
  CONSTRAINT fk_order_item_accounts_order_item FOREIGN KEY (order_item_id) REFERENCES order_items (id) ON DELETE CASCADE,
  CONSTRAINT fk_order_item_accounts_stock_pool FOREIGN KEY (stock_pool_id) REFERENCES product_stock_pool (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Liên kết order_items với accounts đã giao từ stock pool';

-- ============================================================================
-- 7. REVIEWS TABLE - Đánh giá sản phẩm
-- ============================================================================
CREATE TABLE reviews (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT COMMENT 'Review ID',
    product_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    order_id BIGINT UNSIGNED,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    is_verified_purchase TINYINT(1) DEFAULT 0,
    is_approved TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    INDEX idx_product (product_id),
    INDEX idx_user (user_id),
    INDEX idx_rating (rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 8. PRODUCT_LIKES TABLE - Yêu thích sản phẩm
-- ============================================================================
CREATE TABLE product_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_like (product_id, user_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_product (product_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 10. SYSTEM_LOGS TABLE - Nhật ký hệ thống
-- ============================================================================
CREATE TABLE system_logs (
    id VARCHAR(20) PRIMARY KEY,
    log_type ENUM('user_login', 'admin_action', 'payment', 'balance', 'order', 'product', 'system') NOT NULL,
    user_id BIGINT UNSIGNED,
    admin_id BIGINT UNSIGNED,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    old_value TEXT,
    new_value TEXT,
    ip_address VARCHAR(45),
    fingerprint VARCHAR(64) DEFAULT NULL COMMENT 'Browser fingerprint hash',
    country VARCHAR(10) DEFAULT NULL COMMENT 'Country code: VN, US, CN, etc.',
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_log_type (log_type),
    INDEX idx_user_id (user_id),
    INDEX idx_admin_id (admin_id),
    INDEX idx_created_at (created_at),
    INDEX idx_country (country),
    INDEX idx_fingerprint (fingerprint)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 11. PAYMENT_STATISTICS TABLE - Thống kê thanh toán
-- ============================================================================
CREATE TABLE payment_statistics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    total_orders INT DEFAULT 0,
    total_revenue_vnd DECIMAL(15, 0) DEFAULT 0,
    total_revenue_usd DECIMAL(10, 2) DEFAULT 0,
    completed_orders INT DEFAULT 0,
    cancelled_orders INT DEFAULT 0,
    refunded_orders INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 13. TRANSACTIONS TABLE - Giao dịch chung
-- ============================================================================
CREATE TABLE transactions (
    id VARCHAR(20) PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    order_id BIGINT UNSIGNED,
    type ENUM('deposit', 'withdraw', 'purchase', 'refund', 'admin_adjust') NOT NULL,
    amount_vnd DECIMAL(15,2) DEFAULT 0,
    amount_usd DECIMAL(15,2) DEFAULT 0,
    currency VARCHAR(3) NOT NULL,
    balance_before_vnd DECIMAL(15, 2),
    balance_after_vnd DECIMAL(15, 2),
    balance_before_usd DECIMAL(15, 2),
    balance_after_usd DECIMAL(15, 2),
    description TEXT,
    payment_method VARCHAR(50),
    transaction_ref VARCHAR(100),
    status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_type (type),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_user_type (user_id, type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 13B. BALANCE_TRANSACTIONS TABLE - Chi tiết biến động số dư
-- ============================================================================
CREATE TABLE balance_transactions (
    id VARCHAR(20) PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    admin_id BIGINT UNSIGNED NULL COMMENT 'Admin thực hiện (nếu admin_add hoặc admin_deduct)',
    type ENUM('deposit', 'purchase', 'refund', 'admin_add', 'admin_deduct', 'withdraw') NOT NULL COMMENT 'Loại giao dịch',
    currency VARCHAR(3) NOT NULL COMMENT 'VND hoặc USD',
    amount DECIMAL(15,2) NOT NULL COMMENT 'Số tiền biến động',
    balance_before DECIMAL(15,2) NOT NULL COMMENT 'Số dư trước giao dịch',
    balance_after DECIMAL(15,2) NOT NULL COMMENT 'Số dư sau giao dịch',
    note TEXT COMMENT 'Ghi chú',
    ip_address VARCHAR(45) COMMENT 'IP thực hiện giao dịch',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_type (type),
    INDEX idx_created_at (created_at),
    INDEX idx_user_type (user_id, type),
    INDEX idx_admin (admin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Chi tiết biến động số dư user';


-- ============================================================================
-- 13. NOTIFICATION_BANNERS TABLE - Thông báo banner
-- ============================================================================
CREATE TABLE notification_banners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message TEXT NOT NULL,
    bg_color VARCHAR(20) DEFAULT '#7c3aed',
    bg_color_2 VARCHAR(20) DEFAULT '#f97316',
    text_color VARCHAR(20) DEFAULT '#ffffff',
    icon VARCHAR(50) DEFAULT '',
    is_active TINYINT(1) DEFAULT 1,
    display_order INT DEFAULT 0,
    speed INT DEFAULT 50 COMMENT 'Animation speed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Banner notifications with gradient support. Default gradient: linear-gradient(90deg, #7c3aed, #f97316, #7c3aed)';

-- ============================================================================
-- 16. CART TABLE - Giỏ hàng
-- ============================================================================
CREATE TABLE cart (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT COMMENT 'Cart ID',
    user_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    variant_id BIGINT UNSIGNED NULL,
    quantity INT NOT NULL DEFAULT 1,
    customer_info TEXT DEFAULT NULL COMMENT 'Temporary storage for customer info before checkout',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_product_variant (user_id, product_id, variant_id),
    INDEX idx_user (user_id),
    INDEX idx_variant (variant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 16B. CHECKOUT_SESSIONS TABLE - Phiên thanh toán
-- ============================================================================
CREATE TABLE checkout_sessions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    session_id VARCHAR(64) UNIQUE NOT NULL COMMENT 'Session ID duy nhất',
    user_id BIGINT UNSIGNED NOT NULL,
    cart_ids JSON NOT NULL COMMENT 'Danh sách cart IDs trong session',
    created_at DATETIME NOT NULL,
    expires_at DATETIME NOT NULL COMMENT 'Thời gian hết hạn session',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_session_id (session_id),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 17. TICKETS TABLE - Hỗ trợ
-- ============================================================================
CREATE TABLE tickets (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT COMMENT 'Ticket ID',
    user_id BIGINT UNSIGNED NOT NULL,
    order_id BIGINT UNSIGNED,
    ticket_number VARCHAR(50) UNIQUE NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    attachment VARCHAR(255) NULL COMMENT 'Image attachment path',
    status ENUM('open', 'answered', 'closed') DEFAULT 'open',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_ticket_number (ticket_number),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 18. TICKET_MESSAGES TABLE - Tin nhắn ticket
-- ============================================================================
CREATE TABLE ticket_messages (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT COMMENT 'Ticket Message ID',
    ticket_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    message TEXT NOT NULL,
    image VARCHAR(500) NULL COMMENT 'Image attachment path for message',
    is_admin TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_ticket (ticket_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 19. TICKET_ONLINE_USERS TABLE - User online trong ticket
-- ============================================================================
CREATE TABLE ticket_online_users (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    ticket_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    role ENUM('USER', 'ADMIN') NOT NULL DEFAULT 'USER',
    is_online TINYINT(1) NOT NULL DEFAULT 1,
    joined_at DATETIME NOT NULL,
    last_seen DATETIME NOT NULL,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_ticket_user (ticket_id, user_id),
    INDEX idx_online_users (ticket_id, is_online, last_seen),
    INDEX idx_ticket (ticket_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 20. COMMENTS TABLE - Bình luận
-- ============================================================================
CREATE TABLE comments (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT COMMENT 'Comment ID',
    product_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    parent_id BIGINT UNSIGNED NULL,
    content TEXT NOT NULL,
    is_approved TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE,
    INDEX idx_product (product_id),
    INDEX idx_user (user_id),
    INDEX idx_parent (parent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 20. VOUCHERS TABLE - Mã giảm giá/Voucher
-- ============================================================================
CREATE TABLE vouchers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL COMMENT 'Mã voucher',
    discount_type ENUM('percentage', 'fixed') DEFAULT 'percentage' COMMENT 'Loại giảm: % hoặc cố định',
    discount_value DECIMAL(10,2) NOT NULL COMMENT 'Giá trị giảm',
    min_amount DECIMAL(15,0) DEFAULT 0 COMMENT 'Giá trị đơn hàng tối thiểu',
    max_discount DECIMAL(15,0) DEFAULT 0 COMMENT 'Giảm tối đa (cho %)',
    usage_limit INT DEFAULT 0 COMMENT 'Số lần sử dụng tối đa (0 = không giới hạn)',
    used_count INT DEFAULT 0 COMMENT 'Số lần đã sử dụng',
    valid_from DATETIME NULL COMMENT 'Có hiệu lực từ',
    valid_until DATETIME NULL COMMENT 'Có hiệu lực đến',
    is_active TINYINT(1) DEFAULT 1 COMMENT '1 = active, 0 = inactive',
    applicable_products TEXT NULL COMMENT 'JSON array of product IDs (NULL = áp dụng tất cả)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_active (is_active),
    INDEX idx_valid (valid_from, valid_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 23. POPUP_NOTIFICATIONS TABLE - Thông báo popup
-- ============================================================================
CREATE TABLE popup_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title LONGTEXT NOT NULL COMMENT 'Nội dung popup (hỗ trợ HTML/CSS)',
    description TEXT COMMENT 'Mô tả popup (deprecated)',
    image VARCHAR(500) DEFAULT NULL COMMENT 'URL ảnh popup',
    image_width INT DEFAULT 800 COMMENT 'Chiều rộng ảnh',
    image_height INT DEFAULT 500 COMMENT 'Chiều cao ảnh',
    content_mode VARCHAR(20) DEFAULT 'text' COMMENT 'Mode: text hoặc html',
    background_code TEXT DEFAULT NULL COMMENT 'CSS code cho background (khi không có ảnh)',
    is_active TINYINT(1) DEFAULT 0 COMMENT 'Chỉ 1 popup active tại 1 thời điểm',
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_active (is_active),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Popup notifications với hỗ trợ HTML/CSS và custom background';

-- ============================================================================
-- 24. IMPORTANT_NOTICES TABLE - Thông báo quan trọng
-- ============================================================================
CREATE TABLE important_notices (
    id INT NOT NULL AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL COMMENT 'Tiêu đề thông báo',
    content TEXT NOT NULL COMMENT 'Nội dung thông báo',
    type ENUM('info', 'warning', 'danger', 'success') DEFAULT 'info' COMMENT 'Loại: info, warning, danger, success',
    target_user_id BIGINT UNSIGNED NULL COMMENT 'NULL = gửi tất cả users, hoặc user cụ thể',
    is_active TINYINT(1) DEFAULT 1 COMMENT '1 = hiện, 0 = ẩn',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (target_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_is_active (is_active),
    INDEX idx_target_user (target_user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1;

-- ============================================================================
-- 25. SETTINGS TABLE - Cấu hình hệ thống
-- ============================================================================
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type VARCHAR(20) DEFAULT 'text',
    description VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 26. PASSWORD_RESET_LOGS TABLE - Logs password reset attempts
-- ============================================================================
CREATE TABLE IF NOT EXISTS `password_reset_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL COMMENT 'IPv4 or IPv6 address',
  `success` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = successful, 0 = failed',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_email_created` (`email`, `created_at`),
  INDEX `idx_ip_created` (`ip_address`, `created_at`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Logs password reset attempts for rate limiting';

-- ============================================================================
-- PAYMENT SYSTEM TABLES
-- ============================================================================

-- Table for payment transactions
CREATE TABLE IF NOT EXISTS `payment_transactions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `transaction_code` VARCHAR(50) NOT NULL UNIQUE,
  `amount` DECIMAL(15,2) NOT NULL,
  `currency` ENUM('VND', 'USD') NOT NULL DEFAULT 'VND',
  `payment_method` ENUM('mbbank', 'momo', 'paypal') NOT NULL,
  `status` ENUM('pending', 'completed', 'expired', 'failed') NOT NULL DEFAULT 'pending',
  `bank_transaction_id` VARCHAR(100) NULL,
  `payment_info` TEXT NULL,
  `expires_at` DATETIME NOT NULL,
  `completed_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_transaction_code` (`transaction_code`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_expires_at` (`expires_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for Sepay webhook logs
CREATE TABLE IF NOT EXISTS `sepay_webhook_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `transaction_id` VARCHAR(100) NOT NULL,
  `amount` DECIMAL(15,2) NOT NULL,
  `content` TEXT NOT NULL,
  `account_number` VARCHAR(50) NOT NULL,
  `transfer_type` VARCHAR(20) NULL,
  `gate` VARCHAR(20) NULL,
  `transaction_date` DATETIME NOT NULL,
  `raw_data` TEXT NOT NULL,
  `processed` TINYINT(1) NOT NULL DEFAULT 0,
  `processed_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_transaction_id` (`transaction_id`),
  INDEX `idx_content` (`content`(100)),
  INDEX `idx_processed` (`processed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for payment settings
CREATE TABLE IF NOT EXISTS `payment_settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT NOT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- SECURITY SYSTEM TABLES
-- ============================================================================

-- Security Logs Table
CREATE TABLE IF NOT EXISTS `security_logs` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip` VARCHAR(45) NOT NULL,
  `user_agent` TEXT,
  `request_uri` VARCHAR(500),
  `request_method` VARCHAR(10),
  `user_id` INT(11) DEFAULT NULL,
  `fingerprint` VARCHAR(64),
  `is_blocked` TINYINT(1) DEFAULT 0,
  `threat_level` ENUM('low', 'medium', 'high', 'critical') DEFAULT 'low',
  `attack_type` VARCHAR(50),
  `country_code` VARCHAR(2),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ip` (`ip`),
  KEY `idx_created` (`created_at`),
  KEY `idx_threat` (`threat_level`),
  KEY `idx_blocked` (`is_blocked`),
  KEY `idx_threat_blocked` (`threat_level`, `is_blocked`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- IP Blocklist Table
CREATE TABLE IF NOT EXISTS `ip_blocklist` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip` VARCHAR(45) NOT NULL,
  `reason` VARCHAR(255),
  `blocked_by` INT(11) DEFAULT NULL COMMENT 'Admin user ID or NULL for auto-block',
  `blocked_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `expires_at` TIMESTAMP NULL DEFAULT NULL,
  `is_permanent` TINYINT(1) DEFAULT 0,
  `violation_count` INT(11) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_ip` (`ip`),
  KEY `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fingerprint Blocklist Table
CREATE TABLE IF NOT EXISTS `fingerprint_blocklist` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `fingerprint` VARCHAR(64) NOT NULL COMMENT 'Browser fingerprint hash',
  `reason` VARCHAR(255),
  `blocked_by` INT(11) DEFAULT NULL COMMENT 'Admin user ID or NULL for auto-block',
  `blocked_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `expires_at` TIMESTAMP NULL DEFAULT NULL,
  `is_permanent` TINYINT(1) DEFAULT 0,
  `violation_count` INT(11) DEFAULT 1,
  `last_seen_ip` VARCHAR(45) DEFAULT NULL COMMENT 'Last known IP of this fingerprint',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_fingerprint` (`fingerprint`),
  KEY `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Block users by browser fingerprint - persists across IP changes';

-- Rate Limits Table
CREATE TABLE IF NOT EXISTS `rate_limits` (
  `ip` VARCHAR(45) NOT NULL,
  `endpoint` VARCHAR(100) DEFAULT 'global',
  `request_count` INT(11) DEFAULT 1,
  `first_request` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `last_request` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `violations` INT(11) DEFAULT 0,
  PRIMARY KEY (`ip`, `endpoint`),
  KEY `idx_last_request` (`last_request`)
) ENGINE=MEMORY DEFAULT CHARSET=utf8mb4;

-- CSRF Tokens Table
CREATE TABLE IF NOT EXISTS `csrf_tokens` (
  `token` VARCHAR(64) NOT NULL,
  `user_id` INT(11) DEFAULT NULL,
  `session_id` VARCHAR(128),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `expires_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`token`),
  KEY `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Failed Login Attempts Table
CREATE TABLE IF NOT EXISTS `failed_logins` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip` VARCHAR(45) NOT NULL,
  `username` VARCHAR(100),
  `attempt_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ip_time` (`ip`, `attempt_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Webhook Logs Table
CREATE TABLE IF NOT EXISTS `webhook_logs` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` VARCHAR(50) NOT NULL,
  `data` TEXT,
  `verified` TINYINT(1) DEFAULT 0,
  `ip_address` VARCHAR(45),
  `user_agent` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Login Attempts Table - Track failed login attempts for brute-force protection
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(100) NOT NULL COMMENT 'Username hoặc email được thử',
  `ip_address` VARCHAR(45) NOT NULL COMMENT 'IP address',
  `fingerprint` VARCHAR(64) DEFAULT NULL COMMENT 'Browser fingerprint',
  `attempt_count` INT NOT NULL DEFAULT 1 COMMENT 'Số lần thử',
  `last_attempt` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `locked_until` TIMESTAMP NULL DEFAULT NULL COMMENT 'Khóa đến khi nào',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_username_ip` (`username`, `ip_address`),
  KEY `idx_username` (`username`),
  KEY `idx_ip` (`ip_address`),
  KEY `idx_locked_until` (`locked_until`),
  KEY `idx_last_attempt` (`last_attempt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Theo dõi các lần đăng nhập thất bại để chống brute-force';

-- ============================================================================
-- PRODUCT LABELS TABLE - Nhãn sản phẩm với ảnh
-- ============================================================================
-- PRODUCT LABELS TABLE - Nhãn sản phẩm với ảnh
-- ============================================================================
CREATE TABLE IF NOT EXISTS product_labels (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT COMMENT 'Label ID',
    name VARCHAR(100) NOT NULL UNIQUE COMMENT 'Tên nhãn (VD: HOT, NEW, SALE)',
    image_url VARCHAR(255) NOT NULL COMMENT 'Đường dẫn ảnh nhãn',
    display_order INT DEFAULT 0 COMMENT 'Thứ tự hiển thị',
    is_active TINYINT(1) DEFAULT 1 COMMENT '1 = active, 0 = inactive',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_active (is_active),
    INDEX idx_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Nhãn sản phẩm với ảnh';

-- Add foreign key constraint for product labels (products table already has label_id column)
-- Note: MariaDB doesn't support IF NOT EXISTS for constraints, so we check manually
SET @constraint_exists = (
    SELECT COUNT(*) 
    FROM information_schema.TABLE_CONSTRAINTS 
    WHERE CONSTRAINT_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'products' 
    AND CONSTRAINT_NAME = 'fk_products_label'
);

SET @sql = IF(@constraint_exists = 0, 
    'ALTER TABLE products ADD CONSTRAINT fk_products_label FOREIGN KEY (label_id) REFERENCES product_labels(id) ON DELETE SET NULL',
    'SELECT "Constraint fk_products_label already exists" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================================
-- SESSIONS TABLE - User session management for instant ban detection
-- ============================================================================
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` VARCHAR(128) NOT NULL PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `ip_address` VARCHAR(45),
  `user_agent` TEXT,
  `last_activity` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_last_activity` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- USER BANS TABLE - Real-time ban detection
-- ============================================================================
CREATE TABLE IF NOT EXISTS `user_bans` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL UNIQUE,
  `banned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `banned_by` BIGINT UNSIGNED,
  `reason` TEXT,
  INDEX `idx_user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`banned_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- SECURITY SYSTEM ENHANCEMENTS - Add tracking columns
-- ============================================================================

-- Add columns to security_logs for enhanced tracking
ALTER TABLE `security_logs` 
ADD COLUMN IF NOT EXISTS `isp` VARCHAR(100) DEFAULT NULL COMMENT 'ISP provider name' AFTER `country_code`,
ADD COLUMN IF NOT EXISTS `city` VARCHAR(100) DEFAULT NULL COMMENT 'City name' AFTER `isp`,
ADD COLUMN IF NOT EXISTS `region` VARCHAR(100) DEFAULT NULL COMMENT 'Region/State' AFTER `city`;

-- Add indexes for security_logs
ALTER TABLE `security_logs`
ADD INDEX IF NOT EXISTS `idx_isp` (`isp`),
ADD INDEX IF NOT EXISTS `idx_city` (`city`);

-- Add columns to ip_blocklist for enhanced tracking
ALTER TABLE `ip_blocklist`
ADD COLUMN IF NOT EXISTS `last_seen_fingerprint` VARCHAR(64) DEFAULT NULL COMMENT 'Last fingerprint from this IP' AFTER `violation_count`,
ADD COLUMN IF NOT EXISTS `country_code` VARCHAR(2) DEFAULT NULL COMMENT 'Country code' AFTER `last_seen_fingerprint`;

-- Add indexes for ip_blocklist
ALTER TABLE `ip_blocklist`
ADD INDEX IF NOT EXISTS `idx_fingerprint` (`last_seen_fingerprint`),
ADD INDEX IF NOT EXISTS `idx_country` (`country_code`);

-- Add columns to fingerprint_blocklist for enhanced tracking
ALTER TABLE `fingerprint_blocklist`
ADD COLUMN IF NOT EXISTS `country_code` VARCHAR(2) DEFAULT NULL COMMENT 'Country code' AFTER `last_seen_ip`,
ADD COLUMN IF NOT EXISTS `last_seen_city` VARCHAR(100) DEFAULT NULL COMMENT 'Last known city' AFTER `country_code`;

-- Add indexes for fingerprint_blocklist
ALTER TABLE `fingerprint_blocklist`
ADD INDEX IF NOT EXISTS `idx_country` (`country_code`),
ADD INDEX IF NOT EXISTS `idx_city` (`last_seen_city`);

-- ============================================================================
-- INSERT DEFAULT DATA
-- ============================================================================

INSERT INTO notification_banners (message, bg_color, bg_color_2, text_color, icon, is_active, display_order, speed) 
VALUES ('🎉 Chào mừng đến với Kai Shop - Website bán tài khoản uy tín #1 Việt Nam! 🎉', '#7c3aed', '#f97316', '#ffffff', '🎉', 1, 0, 50);

INSERT INTO settings (setting_key, setting_value, description) VALUES
('site_name', 'Kai Shop', 'Tên website'),
('site_description', 'Hệ thống chia sẽ kho mã nguồn cung cấp nền tảng mã nguồn miễn phí cho những người đam mê lập trình, mã nguồn trang web, mã nguồn phần mềm, công nghệ mạng, các tiện ích khác', 'Mô tả website'),
('site_email', 'kaishop365@gmail.com', 'Email website'),
('contact_phone', '', 'Số điện thoại liên hệ'),
('site_slogan', 'Nơi có All thứ bạn cần uy tín, chất lượng, giá rẻ nhất thị trường.', 'Slogan chân trang'),
('social_zalo', 'https://zalo.me/0812420710', 'Link Zalo'),
('social_tiktok', 'https://www.tiktok.com/', 'Link TikTok'),
('social_youtube', 'https://youtube.com/', 'Link YouTube'),
('maintenance_mode', '0', 'Chế độ bảo trì (0: tắt, 1: bật)'),
('allow_registration', '1', 'Cho phép đăng ký (0: tắt, 1: bật)'),
('theme_mode', 'dark', 'Chế độ giao diện (dark/light)'),
('exchange_rate', '25000', 'Tỷ giá VND/USD'),
('email_recipient', 'admin@kaishop.com', 'Email nhận thông báo'),
('email_sender', 'noreply@kaishop.com', 'Email gửi'),
('email_password', '', 'Mật khẩu email'),
('header_logo', 'assets/images/kaishop.gif', 'Logo website (header)'),
('footer_logo', 'assets/images/footer.gif', 'Logo website (footer)'),
('tab_logo', 'assets/images/kaishop.gif', 'Logo website (tab/favicon)'),
('site_logo', 'images/kaishop.gif', 'Logo website'),
('site_favicon', 'images/kaishop.gif', 'Favicon website'),
('telegram_link', 'https://t.me/Biinj', 'Telegram contact link')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- Insert default payment settings
INSERT INTO `payment_settings` (`setting_key`, `setting_value`) VALUES
('mbbank_account_number', '09696969690'),
('mbbank_account_name', 'NGUYEN THANH PHUC'),
('mbbank_bank_code', 'MB'),
('momo_phone', '0812420710'),
('momo_name', 'NGUYEN THANH PHUC'),
('sepay_webhook_secret', 'YOUR_WEBHOOK_SECRET_HERE'),
('transaction_expire_minutes', '5'),
('min_deposit_vnd', '10000'),
('min_deposit_usd', '5')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

-- Insert security settings
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('security_enabled', '1', 'boolean', 'Bật/tắt bảo mật hệ thống'),
('rate_limit_enabled', '1', 'boolean', 'Bật/tắt giới hạn tốc độ'),
('rate_limit_requests', '100', 'integer', 'Số request tối đa mỗi phút'),
('rate_limit_window', '60', 'integer', 'Cửa sổ thời gian (giây)'),
('auto_ban_enabled', '1', 'boolean', 'Tự động chặn IP vi phạm'),
('auto_ban_threshold', '3', 'integer', 'Số lần vi phạm để ban'),
('ban_duration', '3600', 'integer', 'Thời gian ban (giây)'),
('waf_enabled', '1', 'boolean', 'Bật/tắt Web Application Firewall'),
('bot_protection_enabled', '1', 'boolean', 'Bật/tắt chống bot'),
('content_protection_enabled', '1', 'boolean', 'Bật/tắt bảo vệ nội dung');

INSERT INTO system_logs (id, log_type, action, description, created_at) 
VALUES ('log_init_001', 'system', 'Database Setup', 'Khởi tạo cơ sở dữ liệu hoàn chỉnh', NOW());

-- Initialize discount amounts for new products (if any exist)
UPDATE products 
SET 
    discount_amount_vnd = ROUND(price_vnd * discount_percent / 100),
    discount_amount_usd = ROUND(price_usd * discount_percent / 100, 2),
    final_price_vnd = price_vnd - ROUND(price_vnd * discount_percent / 100),
    final_price_usd = price_usd - ROUND(price_usd * discount_percent / 100, 2)
WHERE discount_percent > 0;

-- For products with no discount
UPDATE products 
SET 
    final_price_vnd = price_vnd,
    final_price_usd = price_usd
WHERE discount_percent = 0 OR discount_percent IS NULL;

-- ============================================================================
-- CLEANUP EVENT FOR PASSWORD RESET LOGS
-- ============================================================================
DELIMITER $$

CREATE EVENT IF NOT EXISTS `cleanup_password_reset_logs`
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO
BEGIN
  DELETE FROM `password_reset_logs`
  WHERE `created_at` < DATE_SUB(NOW(), INTERVAL 30 DAY);
END$$

DELIMITER ;