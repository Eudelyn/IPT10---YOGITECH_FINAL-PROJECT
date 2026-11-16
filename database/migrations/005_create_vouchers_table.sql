CREATE TABLE IF NOT EXISTS vouchers (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code         VARCHAR(30) NOT NULL UNIQUE,
    description  VARCHAR(160) NOT NULL,
    type         ENUM('percent','fixed','shipping') NOT NULL,
    value        DECIMAL(10,2) NOT NULL,
    min_spend    DECIMAL(12,2) NOT NULL DEFAULT 0,
    max_discount DECIMAL(10,2) NULL,
    expires_at   DATETIME NULL,
    is_active    TINYINT(1) NOT NULL DEFAULT 1,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO vouchers (code, description, type, value, min_spend, max_discount, expires_at) VALUES
 ('YOGI10',   '10% off, up to ₱1,000',                'percent',  10,  3000, 1000, NULL),
 ('CYBER500', '₱500 off orders ₱15,000 and up',        'fixed',    500, 15000, NULL, NULL),
 ('FREESHIP', 'Free shipping, up to ₱200',             'shipping', 200, 0,    NULL, NULL),
 ('NEON2077', '20% off, up to ₱3,000 (min ₱20,000)',   'percent',  20,  20000, 3000, NULL);
