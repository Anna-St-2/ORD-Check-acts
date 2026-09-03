<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';

// Если не авторизован - на логин
if (!isLoggedIn()) {
    redirect('login.php');
}

// Показываем дашборд
require_once 'dashboard.php';