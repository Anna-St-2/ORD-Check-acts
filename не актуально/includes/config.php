<?php
// Включаем отображение ошибок для отладки
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Настройки БД
define('DB_HOST', 'localhost');
define('DB_NAME', 'ord_check');
define('DB_USER', 'root');
define('DB_PASS', ''); // Ваш пароль

// Подключение к БД
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Ошибка подключения к БД: ' . $e->getMessage());
}

// Сессия
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Пути
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('BASE_URL', '/ORD_Check/');

// Создаем папку для загрузок если её нет
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// Проверяем, установлена ли система
// Для первого запуска создадим таблицы автоматически
try {
    $pdo->query("SELECT 1 FROM users LIMIT 1");
} catch (PDOException $e) {
    // Если таблицы нет - создаём
    $sql = "
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            login VARCHAR(50) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) DEFAULT NULL,
            is_admin BOOLEAN DEFAULT FALSE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_login DATETIME DEFAULT NULL,
            is_active BOOLEAN DEFAULT TRUE
        );
        
        CREATE TABLE IF NOT EXISTS checks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
            status VARCHAR(50) DEFAULT 'new',
            orders_file VARCHAR(255),
            mediaplan_file VARCHAR(255),
            acts_file VARCHAR(255),
            result_data JSON DEFAULT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );
        
        INSERT IGNORE INTO users (login, password_hash, full_name, is_admin) 
        VALUES (
            'admin', 
            '" . password_hash('12345', PASSWORD_DEFAULT) . "',
            'Главный администратор',
            1
        );
    ";
    $pdo->exec($sql);
}
?>