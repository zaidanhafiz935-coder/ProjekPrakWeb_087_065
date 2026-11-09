-- Siaga Masyarakat: database minimal untuk menguji login
-- Akun uji:
-- Email    : tester@siaga.test
-- Password : Password123!

CREATE DATABASE IF NOT EXISTS siaga_masyarakat
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE siaga_masyarakat;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- Buat akun uji jika email tersebut belum ada.
-- Hash dibuat menggunakan PHP password_hash() (PASSWORD_DEFAULT).
INSERT INTO users (username, email, password)
VALUES (
    'tester',
    'tester@siaga.test',
    '$2y$12$x4OPQJ5vMjC4qaog33PUk.Di13r61iifJ0lzXvefOkge8Wj1I1pEq'
)
ON DUPLICATE KEY UPDATE
    username = VALUES(username);
