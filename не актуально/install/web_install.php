<?php
/**
 * Веб-установщик системы
 * Запускается через браузер для удобной установки
 */

require_once __DIR__ . '/../includes/config.php';

// Проверяем, установлена ли система
if (file_exists(__DIR__ . '/../.installed')) {
    die('Система уже установлена. Для переустановки удалите файл .installed');
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            }
        }
        
        // Подключаемся к созданной БД
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Обновляем пароль администратора (берём из формы)
        $admin_password = $_POST['admin_password'] ?? '12345';
        $admin_hash = password_hash($admin_password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE login = 'admin'");
        $stmt->execute([$admin_hash]);
        
        // Создаём файл установки
        file_put_contents(__DIR__ . '/../.installed', date('Y-m-d H:i:s') . "\n");
        
        $message = "Установка завершена успешно!<br>Логин: admin<br>Пароль: " . htmlspecialchars($admin_password);
        
    } catch (PDOException $e) {
        $error = "Ошибка БД: " . $e->getMessage();
    } catch (Exception $e) {
        $error = "Ошибка: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Установка системы проверки актов ОРД</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5" style="max-width: 600px;">
        <h2 class="text-center mb-4">Установка системы</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?= $message ?></div>
            <div class="mt-3 text-center">
                <a href="../login.php" class="btn btn-primary">Перейти к входу</a>
            </div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php else: ?>
            <div class="card">
                <div class="card-body">
                    <p>Будет создана база данных и таблицы для системы проверки актов ОРД.</p>
                    <p class="text-warning"><strong>Внимание!</strong> Все существующие данные будут удалены.</p>
                    
                    <form method="post">
                        <div class="mb-3">
                            <label for="admin_password" class="form-label">Пароль администратора</label>
                            <input type="text" class="form-control" id="admin_password" name="admin_password" value="12345" required>
                            <small class="text-muted">Пароль для входа под логином admin</small>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Установить систему</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>