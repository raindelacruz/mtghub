ALTER TABLE users
    ADD COLUMN IF NOT EXISTS deletion_requested_at DATETIME NULL AFTER moderation_notes,
    ADD COLUMN IF NOT EXISTS deletion_scheduled_for DATETIME NULL AFTER deletion_requested_at,
    ADD COLUMN IF NOT EXISTS anonymized_at DATETIME NULL AFTER deletion_scheduled_for;

CREATE TABLE IF NOT EXISTS account_deletion_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    status ENUM('pending','cancelled','completed','blocked') NOT NULL DEFAULT 'pending',
    requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    scheduled_for DATETIME NOT NULL,
    completed_at DATETIME NULL,
    blocked_reason VARCHAR(500) NULL,
    request_ip_hash CHAR(64) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_deletion_status_schedule (status, scheduled_for),
    INDEX idx_deletion_user (user_id),
    CONSTRAINT fk_deletion_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS system_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    request_id VARCHAR(40) NULL,
    severity ENUM('info','warning','error','critical') NOT NULL,
    event_type VARCHAR(100) NOT NULL,
    message VARCHAR(1000) NOT NULL,
    context_json LONGTEXT NULL,
    occurred_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_system_events_severity_time (severity, occurred_at),
    INDEX idx_system_events_type_time (event_type, occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS backup_runs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    status ENUM('started','completed','failed','restored') NOT NULL DEFAULT 'started',
    file_size BIGINT UNSIGNED NULL,
    checksum_sha256 CHAR(64) NULL,
    details VARCHAR(1000) NULL,
    started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    INDEX idx_backup_status_started (status, started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
