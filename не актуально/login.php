<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Начало загрузки login.php<br>\n";

require_once __DIR__ . '/includes/functions.php';
echo "functions.php загружен<br>\n";

require_once __DIR__ . '/includes/db.php';
echo "db.php загружен<br>\n";

if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "POST запрос получен<br>\n";
    // ... остальной код
}

echo "Отображение формы входа...<br>\n";
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в систему проверки актов ОРД</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5" style="max-width: 400px;">
        <h2 class="text-center mb-4">Вход в систему</h2>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= h($error) ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="mb-3">
                <label for="login" class="form-label">Логин</label>
                <input type="text" class="form-control" id="login" name="login" value="admin" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Пароль</label>
                <input type="password" class="form-control" id="password" name="password" value="12345" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Войти</button>
        </form>
        <div class="mt-3 text-center">
            <small class="text-muted">Данные для входа выдаются администратором</small>
        </div>
    </div>
</body>
</html>