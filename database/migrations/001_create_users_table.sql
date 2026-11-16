CREATE TABLE IF NOT EXISTS users (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role              ENUM('buyer','seller') NOT NULL DEFAULT 'buyer',
    name              VARCHAR(120) NOT NULL,
    email             VARCHAR(190) NOT NULL UNIQUE,
    password          VARCHAR(255) NOT NULL,
    phone             VARCHAR(20)  NULL,
    store_name        VARCHAR(120) NULL,
    store_description VARCHAR(500) NULL,
    address_line      VARCHAR(255) NULL,
    city              VARCHAR(100) NULL,
    province          VARCHAR(100) NULL,
    region            ENUM('NCR','Luzon','Visayas','Mindanao') NULL,
    postal_code       VARCHAR(10)  NULL,
    last_login_at     DATETIME NULL,
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
