CREATE DATABASE sample_php_pdo;

USE sample_php_pdo;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'manager', 'user') DEFAULT 'user',

    -- Email verification fields
    is_verified TINYINT(1) DEFAULT 0,
    verification_token VARCHAR(64),
    email_verification_expires DATETIME NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


-- Insert sample users (password is 'password123' for all)
INSERT INTO users (email, password, role, is_verified) VALUES
('admin@example.com', '$2y$10$HNfhClczEWBxcFuJwP53iu2Y75Tba7IEtmX8vX.1tp0dZ5EVt9CbO', 'admin', 1),
('manager@example.com', '$2y$10$HNfhClczEWBxcFuJwP53iu2Y75Tba7IEtmX8vX.1tp0dZ5EVt9CbO', 'manager', 1),
('user@example.com', '$2y$10$HNfhClczEWBxcFuJwP53iu2Y75Tba7IEtmX8vX.1tp0dZ5EVt9CbO', 'user', 1);

CREATE TABLE email_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient_email VARCHAR(255) NOT NULL,
    recipient_name VARCHAR(255) NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    sent TINYINT(1) DEFAULT 0,
    created_at DATETIME NOT NULL
);

