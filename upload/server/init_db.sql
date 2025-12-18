CREATE DATABASE IF NOT EXISTS activity_monitor;
USE activity_monitor;

-- Create user 'tanaka' with password 'tanaka' if not exists
-- Note: 'IF NOT EXISTS' for CREATE USER is available in MySQL 5.7.6+
CREATE USER IF NOT EXISTS 'tanaka'@'localhost' IDENTIFIED BY 'tanaka';
GRANT ALL PRIVILEGES ON activity_monitor.* TO 'tanaka'@'localhost';
FLUSH PRIVILEGES;

CREATE TABLE IF NOT EXISTS activity_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uptime DATETIME NOT NULL,
    username VARCHAR(255),
    machine VARCHAR(255),
    task TEXT,
    process VARCHAR(255),
    sec INT,
    mouse INT,
    keyboard INT,
    session VARCHAR(255),
    memory BIGINT,
    isRemote TINYINT(1),
    switch_count INT DEFAULT 0,
    idle_seconds INT DEFAULT 0,
    lock_seconds INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_uptime (uptime),
    INDEX idx_username (username),
    INDEX idx_machine (machine)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
