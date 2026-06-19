USE mtghub;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS email_verified_at DATETIME NULL AFTER email,
    ADD COLUMN IF NOT EXISTS account_status ENUM('pending','active','suspended','banned') NOT NULL DEFAULT 'pending' AFTER role,
    ADD COLUMN IF NOT EXISTS seller_bio VARCHAR(500) NULL AFTER account_status,
    ADD COLUMN IF NOT EXISTS suspension_reason VARCHAR(500) NULL AFTER seller_bio,
    ADD COLUMN IF NOT EXISTS moderation_notes TEXT NULL AFTER suspension_reason,
    ADD INDEX IF NOT EXISTS idx_users_account_status (account_status);

UPDATE users
SET email_verified_at = COALESCE(email_verified_at, NOW()),
    account_status = 'active'
WHERE email_verified_at IS NULL OR account_status = 'pending';

CREATE TABLE IF NOT EXISTS email_verification_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email_verification_user (user_id),
    INDEX idx_email_verification_expiry (expires_at),
    CONSTRAINT fk_email_verification_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reporter_id INT UNSIGNED NOT NULL,
    subject_type ENUM('user','listing') NOT NULL,
    subject_id INT UNSIGNED NOT NULL,
    reason ENUM('suspected_scam','counterfeit','harassment','inappropriate','other') NOT NULL,
    details TEXT NOT NULL,
    status ENUM('open','reviewing','resolved','dismissed') NOT NULL DEFAULT 'open',
    resolution_notes TEXT NULL,
    reviewed_by INT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_reports_status_created (status, created_at),
    INDEX idx_reports_subject (subject_type, subject_id),
    INDEX idx_reports_reporter (reporter_id),
    CONSTRAINT fk_reports_reporter FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_reports_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id INT UNSIGNED NOT NULL,
    action VARCHAR(100) NOT NULL,
    target_type VARCHAR(50) NOT NULL,
    target_id BIGINT UNSIGNED NULL,
    metadata_json LONGTEXT NULL,
    ip_hash CHAR(64) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_admin_created (admin_id, created_at),
    INDEX idx_audit_target (target_type, target_id),
    CONSTRAINT fk_audit_admin FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
