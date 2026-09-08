<?php
session_start();

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    die('⛔ Требуется авторизация');
}

// Проверяем, есть ли данные в сессии
if (!isset($_SESSION['acts_data']) || empty($_SESSION['acts_data'])) {
    die('⛔ Нет данных. Сначала загрузите файл актов.');
}

$data = $_SESSION['acts_data'];

echo "<h2>Диагностика данных из файла Акты</h2>";

// Показываем все ключи (названия столбцов) из первой строки
$firstRow = $data[0] ?? [];
echo "<h3>Все столбцы в данных (из первой строки):</h3>";
echo "<ul>";
foreach ($firstRow as $key => $value) {
    echo "<li><strong>" . htmlspecialchars($key) . "</strong> = " . htmlspecialchars($value) . "</li>";
}
echo "</ul>";

// Показываем структуру данных
echo "<h3>Структура данных (первые 3 строки):</h3>";
echo "<pre>";
for ($i = 0; $i < min(3, count($data)); $i++) {
    echo "Строка " . ($i+1) . ":\n";
    print_r($data[$i]);
    echo "\n";
}
echo "</pre>";

// Проверяем наличие статистических полей
echo "<h3>Проверка наличия статистических полей:</h3>";
$statFields = [
    'Общая сумма без НДС статистики',
    'Общая сумма НДС статистики',
    'Общая сумма с НДС статистики',
    'Сумма без НДС (стат.)',
    'Сумма НДС (стат.)',
    'Сумма с НДС (стат.)',
];

foreach ($statFields as $field) {
    if (isset($firstRow[$field])) {
        echo "✅ <strong>" . htmlspecialchars($field) . "</strong> - ЕСТЬ. Значение: " . htmlspecialchars($firstRow[$field]) . "<br>";
    } else {
        echo "❌ <strong>" . htmlspecialchars($field) . "</strong> - НЕТ<br>";
    }
}

echo "<br><a href='/ORD-Check-acts/dashboard'>Вернуться на дашборд</a>";
?>