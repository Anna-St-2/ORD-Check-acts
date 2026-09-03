<?php
require_once __DIR__ . '/Auth.php';

$auth = new Auth();

// Определяем базовый путь
$base_path = '/ORD-Check-acts'; // ИЗМЕНЕНО!

// Обработка логина
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($auth->login($username, $password)) {
        header('Location: ' . $base_path . '/dashboard');
        exit;
    } else {
        $_SESSION['error'] = 'Неверный логин или пароль';
        header('Location: ' . $base_path . '/login');
        exit;
    }
}

// Выход
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $auth->logout();
    header('Location: ' . $base_path . '/login');
    exit;
}
?>