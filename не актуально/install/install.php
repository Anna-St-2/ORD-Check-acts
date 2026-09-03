<?php
/**
 * Установочный скрипт системы
 * Запускается один раз для инициализации БД
 */

// Подключаем конфигурацию
require_once __DIR__ . '/../includes/config.php';

// Проверяем, что скрипт запускается из командной строки или с локального IP
$allowed_ips = ['127.0.0.1', '::1', 'localhost'];
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', $allowed_ips) && php_sapi_name() !== 'cli') {
    die('Доступ запрещён. Скрипт можно запускать только локально.');
}

// Проверяем, установлена ли уже система
if (file_exists(__DIR__ . '/../.installed')) {
    die('Система уже установлена. Для переустановки удалите файл .installed');
}

echo "=== Установка системы проверки актов ОРД ===\n\n";

try {
    // Подключаемся к MySQL без выбора БД
    $pdo_temp = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo_temp->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Читаем SQL-файл
    $sql = file_get_contents(__DIR__ . '/database.sql');
    if ($sql === false) {
        throw new Exception("Не удалось прочитать файл database.sql");
    }
    
    // Разбиваем SQL на отдельные запросы
    $queries = array_filter(array_map('trim', explode(';', $sql)));
    
    // Выполняем запросы
    foreach ($queries as $query) {
        if (!empty($query)) {
            $pdo_temp->exec($query);
            echo "✓ Выполнен запрос\n";
        }
    }
    
    echo "✓ База данных создана\n";
    
    // Теперь подключаемся к созданной БД
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Обновляем пароль администратора
    $admin_password = '12345';
    $admin_hash = password_hash($admin_password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE login = 'admin'");
    $stmt->execute([$admin_hash]);
    
    echo "✓ Пароль администратора установлен: 12345\n";
    
    // Создаём файл, сигнализирующий об установке
    file_put_contents(__DIR__ . '/../.installed', date('Y-m-d H:i:s') . "\n");
    
    echo "\n=== УСТАНОВКА ЗАВЕРШЕНА УСПЕШНО ===\n";
    echo "Логин: admin\n";
    echo "Пароль: 12345\n";
    echo "Теперь вы можете удалить папку install/ или защитить её доступом\n";
    
} catch (PDOException $e) {
    die("Ошибка БД: " . $e->getMessage() . "\n");
} catch (Exception $e) {
    die("Ошибка: " . $e->getMessage() . "\n");
}
?>