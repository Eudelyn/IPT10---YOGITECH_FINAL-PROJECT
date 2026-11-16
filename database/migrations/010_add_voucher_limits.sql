-- Voucher usage limits. NULL = unlimited.
ALTER TABLE vouchers
    ADD COLUMN usage_limit    INT UNSIGNED NULL AFTER max_discount,
    ADD COLUMN per_user_limit INT UNSIGNED NULL AFTER usage_limit;

-- One row per checkout that used a voucher. released_at is set when every order of
-- that checkout is cancelled, which gives the use back.
CREATE TABLE IF NOT EXISTS voucher_redemptions (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    voucher_id   INT UNSIGNED NOT NULL,
    user_id      INT UNSIGNED NOT NULL,
    checkout_ref VARCHAR(30)  NOT NULL UNIQUE,
    discount     DECIMAL(10,2) NOT NULL DEFAULT 0,
    released_at  DATETIME NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_redemptions_voucher (voucher_id, released_at),
    INDEX idx_redemptions_user (user_id, voucher_id, released_at),
    CONSTRAINT fk_redemptions_voucher FOREIGN KEY (voucher_id) REFERENCES vouchers(id) ON DELETE CASCADE,
    CONSTRAINT fk_redemptions_user    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

UPDATE vouchers SET usage_limit = 1000, per_user_limit = 3 WHERE code = 'YOGI10';
UPDATE vouchers SET usage_limit = 200,  per_user_limit = 1 WHERE code = 'CYBER500';
UPDATE vouchers SET usage_limit = NULL, per_user_limit = 5 WHERE code = 'FREESHIP';
UPDATE vouchers SET usage_limit = 100,  per_user_limit = 1 WHERE code = 'NEON2077';

-- Count vouchers already used in existing orders.
INSERT IGNORE INTO voucher_redemptions (voucher_id, user_id, checkout_ref, discount, released_at, created_at)
SELECT v.id, o.buyer_id, o.checkout_ref, SUM(o.discount),
       CASE WHEN SUM(o.status <> 'cancelled') = 0 THEN MAX(o.cancelled_at) ELSE NULL END,
       MIN(o.created_at)
  FROM orders o JOIN vouchers v ON v.code = o.voucher_code
 WHERE o.voucher_code IS NOT NULL
 GROUP BY v.id, o.buyer_id, o.checkout_ref;
