USE adaptive_scheduling_db;

CREATE TABLE IF NOT EXISTS admin_incidents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fingerprint VARCHAR(64) UNIQUE,
    type ENUM('sync_failure', 'overdue_spike', 'login_risk', 'reminder_backlog', 'manual') NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    title VARCHAR(180) NOT NULL,
    description TEXT,
    context_json JSON NULL,
    status ENUM('open', 'investigating', 'resolved', 'dismissed') DEFAULT 'open',
    created_by INT NULL,
    resolved_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    INDEX idx_incident_status (status),
    INDEX idx_incident_type (type),
    INDEX idx_incident_created_at (created_at),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
