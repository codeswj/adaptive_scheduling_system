USE adaptive_scheduling_db;

CREATE TABLE IF NOT EXISTS admin_dispatch_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    from_user_id INT NOT NULL,
    to_user_id INT NOT NULL,
    admin_id INT NOT NULL,
    reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_dispatch_task (task_id),
    INDEX idx_dispatch_created_at (created_at),
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (from_user_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (to_user_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
