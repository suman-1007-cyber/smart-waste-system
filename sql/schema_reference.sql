-- REFERENCE ONLY: the tables this code expects, inferred from the original files.
-- Your live Cloud SQL database already has them. Do NOT run this against it.
-- Useful for setting up a fresh local copy for testing.

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,                -- password_hash() output
    role VARCHAR(20) NOT NULL DEFAULT 'citizen'    -- 'citizen' or 'officer'
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS complaints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    location VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'Pending', -- Pending | In Progress | Resolved
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_user (user_id)
) ENGINE=InnoDB;
