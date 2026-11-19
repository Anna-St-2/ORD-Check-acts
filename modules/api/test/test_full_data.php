<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(300);

session_start();

if (!isset($_SESSION['user_id'])) {
    die('⛔ Требуется авторизация');
}

require_once __DIR__ . '/../ApiClient.php';
require_once __DIR__ . '/../ApiDataParser.php';
require_once __DIR__ . '/../ActNumberNormalizer.php';

$api = new ApiClient();
$parser = new ApiDataParser();

$dateFrom = $_GET['dateFrom'] ?? '2026-07-01';
$dateTo   = $_GET['dateTo']   ?? '2026-07-31';
$limit    = (int)($_GET['limit'] ?? 10);
if ($limit <= 0 || $limit > 50) $limit = 10;

echo "<h2>🧪 Данные за {$dateFrom} — {$dateTo}</h2>";
echo "<p>Сэмпл: <strong>{$limit}</strong> (параметр <code>limit</code>)</p>";

// ===== Шаг 1: заказы =====
$orders = $api->getOrders($dateFrom, $dateTo);
if ($orders === null) {
    echo "<p style='color:red'>Ошибка API: " . htmlspecialchars($api->getLastError()) . "</p>";
    exit;
}
echo "<p>Всего заказов: <strong>" . count($orders) . "</strong></p>";

// ===== Шаг 2: ordSubmit='Да' =====
$filtered = array_values(array_filter($orders, fn($o) => ($o['ordSubmit'] ?? '') === 'Да'));
echo "<p>С ordSubmit='Да': <strong>" . count($filtered) . "</strong></p>";

if (empty($filtered)) {
    echo "<p>❌ Нет заказов с ordSubmit='Да'</p>";
    exit;
}

// ===== Шаг 3: обогащение + parseOrder (4 аргумента!) =====
$sample = array_slice($filtered, 0, $limit);
$rows = [];
$errors = [];

foreach ($sample as $order) {
    $actNumber = ActNumberNormalizer::fromOrder($order);
    try {
        $contract    = !empty($order['contractId'])    ? $api->getContract($order['contractId'])       : null;
        $legalEntity = !empty($order['legalEntityId']) ? $api->getLegalEntity($order['legalEntityId']) : null;
        $mediaplans  = !empty($order['id'])            ? ($api->getMediaplans($order['id']) ?? [])     : [];

        $rows[] = $parser->parseOrder($order, $contract, $legalEntity, $mediaplans);
    } catch (\Throwable $e) {
        $errors[] = "act={$actNumber}: " . $e->getMessage();
    }
}

if (!empty($errors)) {
    echo "<div style='color:red;background:#fee;padding:10px;border-radius:6px'>";
    echo "<h3>⚠️ Ошибки:</h3><ul>";
    foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>";
    echo "</ul></div>";
}

if (empty($rows)) {
    echo "<p>❌ Нет строк для отображения</p>";
    exit;
}

// ===== Шаг 4: таблица (колонки из parseOrder) =====
$headers = array_keys($rows[0]);
echo "<h3>📋 Данные (" . count($rows) . " записей)</h3>";
echo "<div style='overflow-x:auto;max-height:600px;overflow-y:auto'>";
echo "<table border='1' cellpadding='8' style='border-collapse:collapse;font-size:13px'>";
echo "<thead style='position:sticky;top:0;background:#f0f0f0'><tr>";
foreach ($headers as $h) echo "<th>" . htmlspecialchars($h) . "</th>";
echo "</tr></thead><tbody>";
foreach ($rows as $row) {
    echo "<tr>";
    foreach ($headers as $h) {
        $v = $row[$h] ?? '';
        if (is_array($v)) $v = json_encode($v, JSON_UNESCAPED_UNICODE);
        echo "<td>" . htmlspecialchars((string)$v) . "</td>";
    }
    echo "</tr>";
}
echo "</tbody></table></div>";

// ===== Сырой заказ =====
if (!empty($sample)) {
    echo "<details style='margin-top:20px'><summary style='cursor:pointer'>📄 Сырой заказ #1</summary>";
    echo "<pre style='background:#1a1a1a;color:#0f0;padding:15px;overflow:auto;max-height:400px;font-size:12px'>";
    echo htmlspecialchars(json_encode($sample[0], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "</pre></details>";
}