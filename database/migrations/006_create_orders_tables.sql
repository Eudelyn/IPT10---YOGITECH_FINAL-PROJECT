-- One checkout creates one order per seller (each ships separately).
CREATE TABLE IF NOT EXISTS orders (
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_number       VARCHAR(30) NOT NULL UNIQUE,
    tracking_number    VARCHAR(30) NOT NULL UNIQUE,
    checkout_ref       VARCHAR(30) NOT NULL COMMENT 'Groups orders placed in one checkout',
    buyer_id           INT UNSIGNED NOT NULL,
    seller_id          INT UNSIGNED NOT NULL,
    status             ENUM('pending','confirmed','packed','shipped','in_transit','out_for_delivery','delivered','cancelled') NOT NULL DEFAULT 'pending',
    payment_method     ENUM('cod','gcash','card') NOT NULL DEFAULT 'cod',
    payment_status     ENUM('unpaid','paid','refunded') NOT NULL DEFAULT 'unpaid',
    subtotal           DECIMAL(12,2) NOT NULL,
    shipping_fee       DECIMAL(10,2) NOT NULL DEFAULT 0,
    discount           DECIMAL(10,2) NOT NULL DEFAULT 0,
    total              DECIMAL(12,2) NOT NULL,
    voucher_code       VARCHAR(30) NULL,
    recipient_name     VARCHAR(120) NOT NULL,
    phone              VARCHAR(20)  NOT NULL,
    address_line       VARCHAR(255) NOT NULL,
    city               VARCHAR(100) NOT NULL,
    province           VARCHAR(100) NOT NULL,
    region             ENUM('NCR','Luzon','Visayas','Mindanao') NOT NULL,
    postal_code        VARCHAR(10)  NULL,
    notes              VARCHAR(255) NULL,
    courier            VARCHAR(60)  NULL,
    estimated_from     DATE NULL,
    estimated_to       DATE NULL,
    shipped_at         DATETIME NULL,
    delivered_at       DATETIME NULL,
    cancelled_at       DATETIME NULL,
    cancel_reason      VARCHAR(255) NULL,
    created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_orders_buyer (buyer_id, created_at),
    INDEX idx_orders_seller (seller_id, status),
    INDEX idx_orders_checkout (checkout_ref),
    CONSTRAINT fk_orders_buyer  FOREIGN KEY (buyer_id)  REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_orders_seller FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id     INT UNSIGNED NOT NULL,
    product_id   INT UNSIGNED NULL,
    product_name VARCHAR(180) NOT NULL COMMENT 'Snapshot at time of purchase',
    thumbnail    VARCHAR(500) NULL,
    unit_price   DECIMAL(12,2) NOT NULL,
    quantity     SMALLINT UNSIGNED NOT NULL,
    line_total   DECIMAL(12,2) NOT NULL,
    CONSTRAINT fk_items_order   FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
    CONSTRAINT fk_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Every status change becomes a timeline entry on the tracking page.
CREATE TABLE IF NOT EXISTS shipment_events (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id    INT UNSIGNED NOT NULL,
    status      VARCHAR(30)  NOT NULL,
    title       VARCHAR(120) NOT NULL,
    description VARCHAR(255) NULL,
    location    VARCHAR(120) NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_events_order (order_id, created_at),
    CONSTRAINT fk_events_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
