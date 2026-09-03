<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Проверка config.php ===<br><br>";

// Проверяем существование файла
if (file_exists('config.php')) {
    echo "✅ config.php существует<br>";
} else {
    die("❌ config.php не найден!");
}

// Проверяем синтаксис config.php
echo "<br>Проверка синтаксиса config.php:<br>";
try {
    include 'config.php';
    echo "✅ config.php загружен без ошибок<br>";
} catch (ParseError $e) {
    die("❌ Ошибка синтаксиса в config.php: " . $e->getMessage());
}

// Проверяем, что все константы определены
echo "<br>Проверка констант:<br>";
$required = ['UPLOAD_DIR', 'BASE_URL'];
foreach ($required as $const) {
    if (defined($const)) {
        echo "✅ $const = " . constant($const) . "<br>";
    } else {
        echo "❌ $const НЕ определена<br>";
    }
}

// Проверяем, что сессия стартовала
echo "<br>Проверка сессии:<br>";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "✅ Сессия активна<br>";
} else {
    echo "❌ Сессия НЕ активна<br>";
}

// Проверяем подключение к БД
echo "<br>Проверка БД:<br>";
if (isset($pdo)) {
    echo "✅ Объект PDO существует<br>";
    try {
        $pdo->query("SELECT 1");
        echo "✅ Подключение к БД работает<br>";
    } catch (Exception $e) {
        echo "❌ Ошибка БД: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ Объект PDO не создан<br>";
}
?>