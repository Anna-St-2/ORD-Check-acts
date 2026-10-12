<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(300);

session_start();

if (!isset($_SESSION['user_id'])) {
    die('⛔ Требуется авторизация');
}

require_once __DIR__ . '/../ApiClient.php';

$api = new ApiClient();

// Ищем конкретный акт
$searchNumber = $_GET['num'] ?? '28144';
$dateFrom = $_GET['dateFrom'] ?? '2026-07-01';
$dateTo = $_GET['dateTo'] ?? '2026-07-31';

echo "<h2>🔍 Поиск акта: {$searchNumber}</h2>";
echo "<p>Период: {$dateFrom} — {$dateTo}</p>";

// Получаем заказы
$orders = $api->getOrders($dateFrom, $dateTo);

if ($orders === null) {
    die("Ошибка API: " . $api->getLastError());
}

echo "<p>Всего заказов из API: <strong>" . count($orders) . "</strong></p>";

// Ищем по разным полям
$found = [];
foreach ($orders as $order) {
    $orderNumber = trim($order['orderNumber'] ?? '');
    $appNumber = trim($order['applicationNumber'] ?? '');
    
    $normalizedSearch = preg_replace('/\D+/', '', $searchNumber);
    $normalizedOrder = preg_replace('/\D+/', '', $orderNumber);
    $normalizedApp = preg_replace('/\D+/', '', $appNumber);
    
    if ($normalizedSearch === $normalizedOrder || $normalizedSearch === $normalizedApp) {
        $found[] = $order;
    }
}

echo "<h3>Результат поиска</h3>";
echo "<p>Найдено: <strong>" . count($found) . "</strong></p>";

if (empty($found)) {
    echo "<p style='color:red;'>❌ Акт не найден в API</p>";
    echo "<p>Проверьте:</p>";
    echo "<ul>";
    echo "<li>Попадает ли заказ в диапазон дат (dateFrom/dateTo)</li>";
    echo "<li>Правильно ли указан номер</li>";
    echo "</ul>";
} else {
    foreach ($found as $order) {
        echo "<h4>Заказ найден:</h4>";
        echo "<table border='1' cellpadding='8' style='border-collapse:collapse;'>";
        echo "<tr><td><strong>orderNumber</strong></td><td>" . htmlspecialchars($order['orderNumber'] ?? '—') . "</td></tr>";
        echo "<tr><td><strong>applicationNumber</strong></td><td>" . htmlspecialchars($order['applicationNumber'] ?? '—') . "</td></tr>";
        echo "<tr><td><strong>ordSubmit</strong></td><td>" . htmlspecialchars($order['ordSubmit'] ?? '—') . "</td></tr>";
        echo "<tr><td><strong>startDate</strong></td><td>" . htmlspecialchars($order['startDate'] ?? '—') . "</td></tr>";
        echo "<tr><td><strong>endDate</strong></td><td>" . htmlspecialchars($order['endDate'] ?? '—') . "</td></tr>";
        echo "<tr><td><strong>reportingDate</strong></td><td>" . htmlspecialchars($order['reportingDate'] ?? '—') . "</td></tr>";
        echo "<tr><td><strong>status</strong></td><td>" . htmlspecialchars($order['status'] ?? '—') . "</td></tr>";
        echo "<tr><td><strong>businessUnitName</strong></td><td>" . htmlspecialchars($order['businessUnitName'] ?? '—') . "</td></tr>";
        echo "</table>";
    }
}

// Показать несколько примеров номеров из API
echo "<h3>Примеры номеров из API (первые 10)</h3>";
echo "<ul>";
$i = 0;
foreach ($orders as $order) {
    if ($i >= 10) break;
    echo "<li>orderNumber: <strong>" . htmlspecialchars($order['orderNumber'] ?? '—') . "</strong> | ";
    echo "applicationNumber: <strong>" . htmlspecialchars($order['applicationNumber'] ?? '—') . "</strong> | ";
    echo "ordSubmit: <strong>" . htmlspecialchars($order['ordSubmit'] ?? '—') . "</strong></li>";
    $i++;
}
echo "</ul>";

echo "<br><a href='/ORD-Check-acts/dashboard'>← На дашборд</a>";