ALTER TABLE system_events
    ADD COLUMN IF NOT EXISTS resolved_at DATETIME NULL AFTER occurred_at,
    ADD COLUMN IF NOT EXISTS resolution VARCHAR(1000) NULL AFTER resolved_at,
    ADD COLUMN IF NOT EXISTS resolved_by INT UNSIGNED NULL AFTER resolution,
    ADD INDEX IF NOT EXISTS idx_system_events_unresolved (resolved_at, severity, occurred_at),
    ADD CONSTRAINT fk_system_events_resolver FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL;
