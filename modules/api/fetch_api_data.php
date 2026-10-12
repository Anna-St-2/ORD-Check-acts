<?php
error_reporting(0);
ini_set('display_errors', 0);
ini_set('memory_limit', '512M');

session_start();
header('Content-Type: application/json; charset=utf-8');
set_time_limit(600);

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Не авторизован']);
    exit;
}

require_once __DIR__ . '/ApiClient.php';
require_once __DIR__ . '/ApiDataParser.php';

// ===== Параметры =====
$dateFrom = $_GET['dateFrom'] ?? $_POST['dateFrom'] ?? '';
$dateTo = $_GET['dateTo'] ?? $_POST['dateTo'] ?? '';

if (empty($dateFrom) || empty($dateTo)) {
    echo json_encode(['success' => false, 'error' => 'Не указан период']);
    exit;
}

// ===== Номера актов из файла =====
$actNumbersFromFile = [];
if (isset($_SESSION['acts_data']) && !empty($_SESSION['acts_data'])) {
    foreach ($_SESSION['acts_data'] as $row) {
        $num = trim($row['Номер акта'] ?? '');
        if (!empty($num)) {
            $actNumbersFromFile[$num] = true;
        }
    }
}

$api = new ApiClient();
$parser = new ApiDataParser();

$log = [];
$errors = [];

// ===== Шаг 1: Получаем все заказы за период =====
$orders = $api->getOrders($dateFrom, $dateTo);

if ($orders === null) {
    echo json_encode([
        'success' => false,
        'error' => 'Не удалось получить список заказов: ' . $api->getLastError()
    ]);
    exit;
}

$log[] = "API вернул: " . count($orders) . " заказов";

// ===== Шаг 2: Фильтр ordSubmit = 'Да' =====
$filteredByOrd = [];
foreach ($orders as $order) {
    if (($order['ordSubmit'] ?? '') === 'Да') {
        $filteredByOrd[] = $order;
    }
}
$log[] = "С ordSubmit='Да': " . count($filteredByOrd);

// ===== Шаг 3: Фильтр по номерам актов из файла =====
$filtered = [];
if (!empty($actNumbersFromFile)) {
    // Нормализуем номера из файла: только цифры
    $normalizedFileNumbers = [];
    foreach ($actNumbersFromFile as $num => $v) {
        $normalized = preg_replace('/\D+/', '', $num);
        if (!empty($normalized)) {
            $normalizedFileNumbers[$normalized] = true;
        }
    }

    $log[] = "Уникальных номеров из файла: " . count($normalizedFileNumbers);

    // Пробегаем по заказам API
    foreach ($filteredByOrd as $order) {
        // ⚠️ ИСПОЛЬЗУЕМ orderNumber: "ORD - 28144" → "28144"
        $orderNumber = trim($order['orderNumber'] ?? '');
        $normalizedApi = preg_replace('/\D+/', '', $orderNumber);

        if (isset($normalizedFileNumbers[$normalizedApi])) {
            $filtered[] = $order;
        }
    }

    $log[] = "Совпало с файлом актов: " . count($filtered);
} else {
    $filtered = $filteredByOrd;
    $log[] = "Файл актов не загружен — берём все";
}

// ===== Шаг 4: Обогащение данными (contract + legal-entity + mediaplans) =====
$parsedData = [];
$processedCount = 0;

foreach ($filtered as $order) {
    $orderId = $order['id'] ?? '';
    $contractId = $order['contractId'] ?? '';
    $legalEntityId = $order['legalEntityId'] ?? '';

    // Договор (для даты)
    $contract = null;
    if (!empty($contractId)) {
        $contract = $api->getContract($contractId);
    }

    // Юрлицо (name + inn)
    $legalEntity = null;
    if (!empty($legalEntityId)) {
        $legalEntity = $api->getLegalEntity($legalEntityId);
    }

    // Медиапланы (volume)
    $mediaplans = [];
    if (!empty($orderId)) {
        $mp = $api->getMediaplans($orderId);
        if ($mp !== null)
            $mediaplans = $mp;
    }

    $parsedData[] = $parser->parseOrder($order, $contract, $legalEntity, $mediaplans);
    $processedCount++;
}

$log[] = "Обогащено: {$processedCount}";

// ===== Сохраняем в сессию =====
$_SESSION['api_data'] = $parsedData;
$_SESSION['api_data_meta'] = [
    'date_from' => $dateFrom,
    'date_to' => $dateTo,
    'loaded_at' => date('Y-m-d H:i:s'),
    'log' => $log,
];

echo json_encode([
    'success' => true,
    'count' => count($parsedData),
    'log' => $log,
]);