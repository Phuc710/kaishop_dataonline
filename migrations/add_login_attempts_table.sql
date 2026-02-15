-- ============================================================================
-- LOGIN ATTEMPTS TABLE - Theo dõi các lần đăng nhập thất bại
-- ============================================================================
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
