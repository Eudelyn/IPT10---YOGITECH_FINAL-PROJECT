-- Online payments (GCash, card). One row per payment attempt for a checkout.
-- status: pending → authorized (approved on the gateway page) → paid (orders updated).
-- gateway: sandbox (built-in GCash/card simulator) or paymongo (PayMongo test mode).
CREATE TABLE IF NOT EXISTS payments (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    public_id     CHAR(24) NOT NULL UNIQUE,
    checkout_ref  VARCHAR(30) NOT NULL,
    buyer_id      INT UNSIGNED NOT NULL,
    gateway       VARCHAR(20) NOT NULL,
    method        ENUM('gcash','card') NOT NULL,
    amount        DECIMAL(12,2) NOT NULL,
    status        ENUM('pending','authorized','paid','failed','cancelled') NOT NULL DEFAULT 'pending',
    gateway_ref   VARCHAR(120) NULL,
    payer         VARCHAR(60) NULL,
    failure       VARCHAR(255) NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    paid_at       DATETIME NULL,
    INDEX idx_payments_checkout (checkout_ref, status),
    CONSTRAINT fk_payments_buyer FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
