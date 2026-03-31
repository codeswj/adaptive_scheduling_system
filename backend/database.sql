-- Database: adaptive_scheduling_db
-- This script creates every table required for the adaptive scheduling system so it can be imported once.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS adaptive_scheduling_db;
USE adaptive_scheduling_db;

DROP TABLE IF EXISTS
    admin_dispatch_logs,
    admin_incidents,
    task_reminders,
    reminders,
    task_templates,
    user_availability,
    login_logs,
    user_metrics,
    sync_queue,
    user_preferences,
    task_logs,
    tasks,
    users;

SET FOREIGN_KEY_CHECKS = 1;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    phone_number VARCHAR(20) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    work_type ENUM('boda_boda', 'market_vendor', 'artisan', 'domestic_worker', 'plumber', 'other') DEFAULT 'other',
    location VARCHAR(100),
    device_type VARCHAR(50),
    connectivity_profile ENUM('2G', '3G', '4G', '5G', 'unstable') DEFAULT 'unstable',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_phone (phone_number),
    INDEX idx_role (role),
    INDEX idx_work_type (work_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tasks table
CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    task_type ENUM('delivery', 'pickup', 'service', 'purchase', 'meeting', 'other') DEFAULT 'other',
    priority_score DECIMAL(5,2) DEFAULT 0,
    urgency ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    deadline DATETIME,
    estimated_duration INT COMMENT 'Duration in minutes',
    location VARCHAR(200),
    distance DECIMAL(8,2) COMMENT 'Distance in kilometers',
    safety_indicator ENUM('safe', 'caution', 'unsafe') DEFAULT 'safe',
    client_name VARCHAR(100),
    client_phone VARCHAR(20),
    payment_amount DECIMAL(10,2) DEFAULT 0,
    payment_status ENUM('unpaid', 'partial', 'paid') DEFAULT 'unpaid',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    synced_at TIMESTAMP NULL COMMENT 'Last time synced from offline storage',
    device_created BOOLEAN DEFAULT FALSE COMMENT 'Created on device offline',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_deadline (deadline),
    INDEX idx_priority (priority_score DESC),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Task history/logs table (for analytics)
CREATE TABLE IF NOT EXISTS task_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    user_id INT NOT NULL,
    action ENUM('created', 'updated', 'completed', 'cancelled', 'synced') NOT NULL,
    old_status VARCHAR(50),
    new_status VARCHAR(50),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    details JSON COMMENT 'Additional change details',
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_task_id (task_id),
    INDEX idx_user_id (user_id),
    INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User preferences table
CREATE TABLE IF NOT EXISTS user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    default_task_duration INT DEFAULT 30 COMMENT 'Default duration in minutes',
    priority_weights JSON COMMENT 'Custom priority calculation weights',
    notification_enabled BOOLEAN DEFAULT TRUE,
    language VARCHAR(10) DEFAULT 'en',
    theme VARCHAR(20) DEFAULT 'light',
    sync_frequency ENUM('manual', 'wifi_only', 'always') DEFAULT 'wifi_only',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sync queue table (for conflict resolution)
CREATE TABLE IF NOT EXISTS sync_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    device_id VARCHAR(100),
    task_data JSON NOT NULL,
    sync_type ENUM('create', 'update', 'delete') NOT NULL,
    client_timestamp TIMESTAMP NOT NULL,
    server_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'processed', 'conflict', 'failed') DEFAULT 'pending',
    conflict_reason TEXT,
    processed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_client_timestamp (client_timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Performance metrics table (for analytics)
CREATE TABLE IF NOT EXISTS user_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    tasks_created INT DEFAULT 0,
    tasks_completed INT DEFAULT 0,
    tasks_cancelled INT DEFAULT 0,
    total_idle_minutes INT DEFAULT 0,
    total_active_minutes INT DEFAULT 0,
    completion_rate DECIMAL(5,2) DEFAULT 0,
    average_task_duration INT DEFAULT 0,
    total_earnings DECIMAL(10,2) DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_date (user_id, date),
    INDEX idx_date (date),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Login logs table
CREATE TABLE IF NOT EXISTS login_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    phone_number VARCHAR(20) NOT NULL,
    login_status ENUM('success', 'failed') NOT NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_phone_number (phone_number),
    INDEX idx_login_status (login_status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User availability table
CREATE TABLE IF NOT EXISTS user_availability (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    day_of_week TINYINT NOT NULL COMMENT '0=Sunday, 1=Monday, ..., 6=Saturday',
    start_time TIME NOT NULL DEFAULT '08:00:00',
    end_time TIME NOT NULL DEFAULT '18:00:00',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_day (user_id, day_of_week),
    INDEX idx_user_id (user_id),
    INDEX idx_day_of_week (day_of_week)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Task templates table
CREATE TABLE IF NOT EXISTS task_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    task_type ENUM('delivery', 'pickup', 'service', 'purchase', 'meeting', 'other') DEFAULT 'other',
    urgency ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    estimated_duration INT DEFAULT 30 COMMENT 'Duration in minutes',
    priority_score DECIMAL(5,2) DEFAULT 0,
    location VARCHAR(200),
    payment_amount DECIMAL(10,2) DEFAULT 0,
    notes TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Reminders table
CREATE TABLE IF NOT EXISTS reminders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NULL,
    user_id INT NOT NULL,
    task_title VARCHAR(200),
    remind_at DATETIME NOT NULL,
    channel ENUM('in_app', 'sms', 'email', 'push') DEFAULT 'in_app',
    message TEXT,
    status ENUM('pending', 'sent', 'dismissed', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    sent_at TIMESTAMP NULL,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_task_id (task_id),
    INDEX idx_remind_at (remind_at),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Task reminders (used by admin monitoring)
CREATE TABLE IF NOT EXISTS task_reminders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    user_id INT NOT NULL,
    remind_at DATETIME NOT NULL,
    channel ENUM('in_app', 'sms') DEFAULT 'in_app',
    status ENUM('pending', 'sent', 'dismissed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sent_at TIMESTAMP NULL,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_remind_at (remind_at),
    INDEX idx_user_status (user_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admin incidents table
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

-- Admin dispatch logs
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

-- Seed data / helpers
INSERT IGNORE INTO users (full_name, phone_number, password, role, work_type, is_active)
VALUES ('System Admin', '+254700000000', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'other', TRUE);

INSERT IGNORE INTO user_availability (user_id, day_of_week, start_time, end_time)
VALUES
    (1, 1, '08:00:00', '18:00:00'),
    (1, 2, '08:00:00', '18:00:00'),
    (1, 3, '08:00:00', '18:00:00'),
    (1, 4, '08:00:00', '18:00:00'),
    (1, 5, '08:00:00', '18:00:00');

INSERT IGNORE INTO task_templates (user_id, name, title, description, task_type, urgency, estimated_duration, payment_amount)
VALUES
    (1, 'Delivery Package', 'Standard Package Delivery', 'Deliver package within Nairobi CBD', 'delivery', 'medium', 45, 300.00),
    (1, 'Pickup Service', 'Document Pickup', 'Pick up documents from client location', 'pickup', 'low', 30, 150.00),
    (1, 'Maintenance', 'Vehicle Maintenance', 'Regular vehicle maintenance service', 'service', 'high', 120, 2000.00);
