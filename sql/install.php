<?php
// Файл для установки БД - запускаем через браузер или консоль
try {
    // Подключаемся без БД
    $pdo = new PDO("mysql:host=localhost;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Читаем SQL файл
    $sql = file_get_contents(__DIR__ . '/schema.sql');
    
    // Выполняем запросы
    $pdo->exec($sql);
    
    echo "База данных успешно создана!\n";
    echo "Пользователь admin с паролем 12345 создан.\n";
    
} catch(PDOException $e) {
    die("Ошибка установки БД: " . $e->getMessage() . "\n");
}
?>