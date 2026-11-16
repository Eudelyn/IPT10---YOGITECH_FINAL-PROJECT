CREATE TABLE IF NOT EXISTS reviews (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id    INT UNSIGNED NOT NULL,
    user_id       INT UNSIGNED NULL COMMENT 'NULL for reviews imported from the API',
    order_id      INT UNSIGNED NULL,
    reviewer_name VARCHAR(120) NOT NULL,
    rating        TINYINT UNSIGNED NOT NULL,
    comment       VARCHAR(1000) NOT NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_review_user_product (user_id, product_id),
    INDEX idx_reviews_product (product_id),
    CONSTRAINT fk_reviews_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_reviews_user    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE SET NULL,
    CONSTRAINT fk_reviews_order   FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
