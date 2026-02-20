-- Database: adaptive_scheduling_db

-- Create database
CREATE DATABASE IF NOT EXISTS adaptive_scheduling_db;
USE adaptive_scheduling_db;

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
-- Insert default admin user (password: admin123 - hashed)
INSERT INTO users (full_name, phone_number, password, role, work_type, is_active)
VALUES ('System Admin', '+254700000000', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'other', TRUE);


