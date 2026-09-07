<?php
session_start();

// Включаем отображение ошибок для отладки
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Определяем базовый путь
$base_path = '/ORD-Check-acts';

// Подключаем конфиг
require_once __DIR__ . '/config/database.php';

// Подключаем модули
require_once __DIR__ . '/modules/auth/Auth.php';

// Обработка выхода (ДОЛЖНА БЫТЬ ПЕРВОЙ!)
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $auth = new Auth();
    $auth->logout();
    // Перенаправляем на страницу входа
    header('Location: ' . $base_path . '/login');
    exit;
}

// Обработка POST запросов (логин)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $auth = new Auth();
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($auth->login($username, $password)) {
        // После успешного входа перенаправляем на дашборд
        header('Location: ' . $base_path . '/dashboard');
        exit;
    } else {
        $_SESSION['error'] = 'Неверный логин или пароль';
        header('Location: ' . $base_path . '/login');
        exit;
    }
}

// Определяем путь запроса
$request_uri = $_SERVER['REQUEST_URI'];
$script_name = $_SERVER['SCRIPT_NAME'];

// Убираем базовый путь из URI
$path = str_replace($base_path, '', $request_uri);
$path = strtok($path, '?'); // Убираем GET параметры
$path = trim($path, '/');

// Если путь пустой - это главная страница
if (empty($path)) {
    $path = 'login';
}

// Роутинг
switch ($path) {
    case 'login':
        // Если уже авторизован - перенаправляем на дашборд
        if (isset($_SESSION['user_id'])) {
            header('Location: ' . $base_path . '/dashboard');
            exit;
        }
        require_once __DIR__ . '/templates/login.php';
        break;

    case 'dashboard':
        // Проверяем авторизацию
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . $base_path . '/login');
            exit;
        }
        require_once __DIR__ . '/templates/dashboard.php';
        break;

    case 'check':
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . $base_path . '/login');
            exit;
        }
        require_once __DIR__ . '/templates/check.php';
        break;

    case 'compare':
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . $base_path . '/login');
            exit;
        }
        require_once __DIR__ . '/templates/compare.php';
        break;

    default:
        // Если путь не найден - 404
        header('HTTP/1.0 404 Not Found');
        echo '404 Not Found - Path: "' . $path . '"';
        break;
}


?>