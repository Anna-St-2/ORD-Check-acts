-- Создаем БД
CREATE DATABASE IF NOT EXISTS ord_check_acts;
USE ord_check_acts;

-- Таблица пользователей
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(32) NOT NULL,
    full_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Таблица проверок
CREATE TABLE IF NOT EXISTS checks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    check_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('new', 'processing', 'completed', 'error') DEFAULT 'new',
    results_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Таблица загруженных файлов
CREATE TABLE IF NOT EXISTS uploaded_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    check_id INT NOT NULL,
    file_type ENUM('orders', 'mediaplans', 'acts') NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (check_id) REFERENCES checks(id)
);

-- Вставляем тестового пользователя
INSERT INTO users (username, password, full_name) VALUES 
('admin', MD5('12345'), 'Администратор');