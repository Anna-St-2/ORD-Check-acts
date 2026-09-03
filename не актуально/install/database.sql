-- Файл install/database.sql
-- Создание базы данных и таблиц для системы проверки актов ОРД

-- Создаём базу данных (если не существует)
CREATE DATABASE IF NOT EXISTS ord_check CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ord_check;

-- Таблица пользователей
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    login VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) DEFAULT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_login (login)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица проверок
CREATE TABLE IF NOT EXISTS checks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(50) DEFAULT 'new',
    orders_file VARCHAR(255),
    mediaplan_file VARCHAR(255),
    acts_file VARCHAR(255),
    result_data JSON DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_date (user_id, date_created)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Вставляем администратора с паролем в открытом виде (будет захеширован при установке)
-- ВНИМАНИЕ: Пароль будет захеширован через PHP скрипт install.php
-- Вставляем пока заглушку, реальный хеш добавит install.php
INSERT INTO users (login, password_hash, full_name, is_admin) 
VALUES ('admin', 'temp_hash_will_be_replaced', 'Главный администратор', TRUE);