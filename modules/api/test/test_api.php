<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['user_id'])) {
    die('⛔ Требуется авторизация');
}

require_once __DIR__ . '/../ApiClient.php';

$api = new ApiClient();

// ===== ТЕСТ 1: PING =====
$pingResult = $api->ping();

// ===== ТЕСТ 2: ORDERS за июль 2026 =====
$orders = $api->getOrders('2026-07-01', '2026-08-01');

// Фильтруем по ordSubmit = 'Да'
$filteredOrders = [];
if ($orders) {
    foreach ($orders as $order) {
        if (($order['ordSubmit'] ?? '') === 'Да') {
            $filteredOrders[] = $order;
        }
    }
}

// ===== ТЕСТ 3: CONTRACT для первого заказа =====
$firstContract = null;
if (!empty($filteredOrders)) {
    $contractId = $filteredOrders[0]['contractId'] ?? null;
    if ($contractId) {
        $firstContract = $api->getContract($contractId);
    }
}

// ===== ТЕСТ 4: LEGAL ENTITY для первого заказа =====
$firstLegalEntity = null;
if (!empty($filteredOrders)) {
    $legalEntityId = $filteredOrders[0]['legalEntityId'] ?? null;
    if ($legalEntityId) {
        $firstLegalEntity = $api->getLegalEntity($legalEntityId);
    }
}

// ===== ТЕСТ 5: MEDIAPLANS для первого заказа =====
$firstMediaplans = null;
if (!empty($filteredOrders)) {
    $orderId = $filteredOrders[0]['id'] ?? null;
    if ($orderId) {
        $firstMediaplans = $api->getMediaplans($orderId);
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Тест API</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; background: #f5f5f5; font-family: monospace; }
        .card { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .ok { color: #10B981; font-weight: bold; }
        .err { color: #EF4444; font-weight: bold; }
        .badge { display: inline-block; padding: 2px 10px; border-radius: 12px; font-size: 12px; }
        .badge-ok { background: #D1FAE5; color: #065F46; }
        .badge-err { background: #FEE2E2; color: #991B1B; }
        pre { background: #1a1a1a; color: #00ff00; padding: 15px; border-radius: 6px; overflow: auto; max-height: 400px; font-size: 12px; }
    </style>
</head>
<body>
    <h1>🧪 Тест API MonkeyCopilot</h1>
    
    <!-- PING -->
    <div class="card">
        <h3>1. Ping (проверка токена)</h3>
        <?php if ($pingResult && isset($pingResult['ok'])): ?>
            <span class="ok">✅ Токен работает</span>
            <pre><?= htmlspecialchars(json_encode($pingResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
        <?php else: ?>
            <span class="err">❌ Ошибка: <?= htmlspecialchars($api->getLastError()) ?></span>
        <?php endif; ?>
    </div>
    
    <!-- ORDERS -->
    <div class="card">
        <h3>2. Orders за июль 2026</h3>
        <?php if ($orders !== null): ?>
            <p>Всего заказов: <strong><?= count($orders) ?></strong></p>
            <p>С ordSubmit = "Да": <strong><?= count($filteredOrders) ?></strong></p>
            
            <?php if (!empty($filteredOrders)): ?>
                <p>Первый заказ (полностью):</p>
                <pre><?= htmlspecialchars(json_encode($filteredOrders[0], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
            <?php endif; ?>
        <?php else: ?>
            <span class="err">❌ Ошибка: <?= htmlspecialchars($api->getLastError()) ?></span>
        <?php endif; ?>
    </div>
    
    <!-- CONTRACT -->
    <div class="card">
        <h3>3. Contract (первый заказ)</h3>
        <?php if ($firstContract): ?>
            <p>Ключевые поля:</p>
            <ul>
                <li>contractNumber: <strong><?= htmlspecialchars($firstContract['contractNumber'] ?? '—') ?></strong></li>
                <li>startDate: <strong><?= htmlspecialchars($firstContract['startDate'] ?? '—') ?></strong></li>
            </ul>
            <details>
                <summary>Весь ответ</summary>
                <pre><?= htmlspecialchars(json_encode($firstContract, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
            </details>
        <?php else: ?>
            <span class="err">❌ Нет данных</span>
        <?php endif; ?>
    </div>
    
    <!-- LEGAL ENTITY -->
    <div class="card">
        <h3>4. Legal Entity (первый заказ)</h3>
        <?php if ($firstLegalEntity): ?>
            <p>Ключевые поля:</p>
            <ul>
                <li>name: <strong><?= htmlspecialchars($firstLegalEntity['name'] ?? '—') ?></strong></li>
                <li>inn: <strong><?= htmlspecialchars($firstLegalEntity['inn'] ?? '—') ?></strong></li>
            </ul>
            <details>
                <summary>Весь ответ</summary>
                <pre><?= htmlspecialchars(json_encode($firstLegalEntity, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
            </details>
        <?php else: ?>
            <span class="err">❌ Нет данных</span>
        <?php endif; ?>
    </div>
    
    <!-- MEDIAPLANS -->
    <div class="card">
        <h3>5. Mediaplans (первый заказ)</h3>
        <?php if ($firstMediaplans !== null): ?>
            <p>Количество медиапланов: <strong><?= count($firstMediaplans) ?></strong></p>
            <?php if (!empty($firstMediaplans)): ?>
                <p>unitName-ы:</p>
                <ul>
                    <?php foreach ($firstMediaplans as $mp): ?>
                        <li>unitName: <strong><?= htmlspecialchars($mp['unitName'] ?? '—') ?></strong>, volume: <?= htmlspecialchars($mp['volume'] ?? '—') ?></li>
                    <?php endforeach; ?>
                </ul>
                <details>
                    <summary>Первый медиаплан полностью</summary>
                    <pre><?= htmlspecialchars(json_encode($firstMediaplans[0], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                </details>
            <?php endif; ?>
        <?php else: ?>
            <span class="err">❌ Нет данных</span>
        <?php endif; ?>
    </div>
    
    <div class="card">
        <a href="/ORD-Check-acts/dashboard" class="btn btn-primary">← На дашборд</a>
    </div>
</body>
</html>