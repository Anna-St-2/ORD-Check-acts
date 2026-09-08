<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['user_id'])) {
    die('⛔ Требуется авторизация');
}

require_once __DIR__ . '/../ApiClient.php';
require_once __DIR__ . '/../ApiDataParser.php';

$api = new ApiClient();
$parser = new ApiDataParser();

// Фиксированный период - июль 2026
$dateFrom = '2026-07-01';
$dateTo = '2026-07-31';

// Для теста - пустой массив департаментов (все)
$departmentIds = [];

echo "<h2>🧪 Получение данных за {$dateFrom} — {$dateTo}</h2>";

// Получаем данные
$fullData = $api->getFullData($dateFrom, $dateTo, $departmentIds);

// Выводим ошибки
if (!empty($fullData['errors'])) {
    echo "<div style='color:red;background:#fee;padding:10px;border-radius:6px;'>";
    echo "<h3>⚠️ Ошибки:</h3>";
    echo "<ul>";
    foreach ($fullData['errors'] as $error) {
        echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ul>";
    echo "</div>";
}

if (empty($fullData['orders'])) {
    echo "<p>❌ Нет данных за указанный период</p>";
    exit;
}

// Преобразуем данные в табличный формат
$tableData = [];
foreach ($fullData['orders'] as $item) {
    $row = $parser->parseOrderData(
        $item['order'],
        $item['legal_entity'],
        $item['mediaplans']
    );
    $tableData[] = $row;
}

// Выводим таблицу
echo "<h3>📋 Данные для сравнения (" . count($tableData) . " записей)</h3>";

echo "<div style='overflow-x:auto;max-height:600px;overflow-y:auto;'>";
echo "<table border='1' cellpadding='8' style='border-collapse:collapse;font-size:13px;min-width:1400px;'>";
echo "<thead style='position:sticky;top:0;background:#f0f0f0;'>";
echo "<tr>";
$headers = ['Номер акта', 'Дата акта', 'Период оказания', 'Номер договора', 'Дата договора', 
            'ИНН заказчика', 'Сумма без НДС', 'Ставка НДС', 'Сумма НДС', 'Сумма с НДС',
            'ID пункта акта', 'Показы факт.', 'Показы по акту',
            'Сумма без НДС (стат.)', 'Сумма НДС (стат.)', 'Сумма с НДС (стат.)'];
foreach ($headers as $h) {
    echo "<th>" . htmlspecialchars($h) . "</th>";
}
echo "</tr>";
echo "</thead>";
echo "<tbody>";

foreach ($tableData as $row) {
    echo "<tr>";
    $fields = ['Номер акта', 'Дата акта', 'Период оказания', 'Номер договора', 'Дата договора', 
               'ИНН заказчика', 'Сумма без НДС', 'Ставка НДС', 'Сумма НДС', 'Сумма с НДС',
               'ID пункта акта', 'Показы факт.', 'Показы по акту',
               'Сумма без НДС (стат.)', 'Сумма НДС (стат.)', 'Сумма с НДС (стат.)'];
    foreach ($fields as $field) {
        $value = $row[$field] ?? '';
        echo "<td>" . htmlspecialchars($value) . "</td>";
    }
    echo "</tr>";
}

echo "</tbody>";
echo "</table>";
echo "</div>";

// Детальный просмотр первого заказа
if (!empty($tableData)) {
    echo "<div style='margin-top:20px;'>";
    echo "<details>";
    echo "<summary style='cursor:pointer;font-weight:bold;'>📄 Показать сырые данные (первый заказ)</summary>";
    echo "<pre style='background:#1a1a1a;color:#00ff00;padding:15px;border-radius:6px;overflow:auto;max-height:400px;font-size:12px;'>";
    echo htmlspecialchars(json_encode($fullData['orders'][0] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "</pre>";
    echo "</details>";
    echo "</div>";
}
?>