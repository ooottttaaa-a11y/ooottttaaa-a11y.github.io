CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin user if not exists
-- Password is 'admin123' hashed with DEFAULT (likely BCRYPT)
-- Note: In a real script we generate the hash dynamically, but for this SQL file we might need a known hash or handle it in PHP.
-- Let's just create the table here. The PHP setup script will handle the insertion to ensure correct hashing.
