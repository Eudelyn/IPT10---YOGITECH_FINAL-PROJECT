-- Every notification email (sent, failed or only logged) is recorded here.
CREATE TABLE IF NOT EXISTS email_logs (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NULL,
    order_id   INT UNSIGNED NULL,
    to_email   VARCHAR(190) NOT NULL,
    subject    VARCHAR(255) NOT NULL,
    type       VARCHAR(40)  NOT NULL,
    driver     VARCHAR(20)  NOT NULL,
    status     ENUM('sent','failed','logged') NOT NULL,
    error      VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email_logs_user (user_id),
    INDEX idx_email_logs_order (order_id),
    CONSTRAINT fk_email_logs_user  FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE SET NULL,
    CONSTRAINT fk_email_logs_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
