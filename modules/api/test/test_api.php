<?php
// modules/api/test/test_api.php
// ВНИМАНИЕ: Этот файл ТОЛЬКО для разработки!

// Доступ только с локального компьютера (безопасность)
if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1') {
    die('⛔ Доступ только с локального хоста');
}

session_start();
if (!isset($_SESSION['user_id'])) {
    die('⛔ Требуется авторизация');
}

// Ваш API ключ (временно здесь, потом вынесем в конфиг)
$API_KEY = 'ВАШ_API_КЛЮЧ_СЮДА';
$BASE_URL = 'https://api.monkeycopilot.com/'; // Замените на реальный URL

// Обработка запроса
$result = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $endpoint = $_POST['endpoint'] ?? '';
    $params = $_POST['params'] ?? '';
    
    // Разбираем параметры (формат: key1=value1&key2=value2)
    parse_str($params, $paramsArray);
    
    // Формируем URL
    $url = $BASE_URL . $endpoint . '?' . http_build_query($paramsArray);
    
    // Отправляем запрос
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $API_KEY,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    $result = [
        'url' => $url,
        'http_code' => $httpCode,
        'response' => json_decode($response, true),
        'raw_response' => $response,
        'curl_error' => $curlError
    ];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Тест API MonkeyCopilot</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; }
        .card { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .request-form { display: flex; flex-direction: column; gap: 10px; }
        .request-form input, .request-form textarea { padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-family: monospace; }
        .request-form button { padding: 10px 20px; background: #4F46E5; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .request-form button:hover { background: #4338CA; }
        .response { background: #1a1a1a; color: #00ff00; padding: 15px; border-radius: 4px; overflow: auto; max-height: 500px; white-space: pre-wrap; }
        .error { background: #ffebee; color: #c62828; padding: 15px; border-radius: 4px; }
        .success { background: #e8f5e9; color: #2e7d32; padding: 15px; border-radius: 4px; }
        .info { background: #e3f2fd; color: #0d47a1; padding: 15px; border-radius: 4px; }
        .row { display: flex; gap: 20px; }
        .col { flex: 1; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 12px; }
        .badge-success { background: #4CAF50; color: white; }
        .badge-error { background: #f44336; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Тест API MonkeyCopilot</h1>
        <p>Используйте эту страницу для тестирования запросов к API</p>
        
        <div class="card">
            <h3>📝 Настройка запроса</h3>
            <form method="POST" class="request-form">
                <div class="row">
                    <div class="col">
                        <label>Эндпоинт (часть URL после базового)</label>
                        <input type="text" name="endpoint" value="orders" placeholder="например: orders, acts, mediaplans">
                    </div>
                    <div class="col">
                        <label>Параметры (key=value&key2=value2)</label>
                        <input type="text" name="params" value="start_date=2026-09-01&end_date=2026-09-07" placeholder="start_date=2026-09-01&end_date=2026-09-07">
                    </div>
                </div>
                <button type="submit">🚀 Отправить запрос</button>
            </form>
        </div>
        
        <?php if ($result !== null): ?>
            <div class="card">
                <h3>📊 Результат запроса</h3>
                
                <div class="info">
                    <strong>URL:</strong> <?= htmlspecialchars($result['url']) ?><br>
                    <strong>HTTP Код:</strong> 
                    <?php if ($result['http_code'] >= 200 && $result['http_code'] < 300): ?>
                        <span class="badge badge-success">✅ <?= $result['http_code'] ?></span>
                    <?php else: ?>
                        <span class="badge badge-error">❌ <?= $result['http_code'] ?></span>
                    <?php endif; ?>
                </div>
                
                <?php if ($result['curl_error']): ?>
                    <div class="error">
                        <strong>⚠️ Ошибка CURL:</strong> <?= htmlspecialchars($result['curl_error']) ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($result['response']): ?>
                    <h4>📄 Ответ (JSON):</h4>
                    <div class="response"><?= htmlspecialchars(json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></div>
                    
                    <h4>📋 Структура данных:</h4>
                    <div class="response" style="background: #f5f5f5; color: #333; max-height: 300px;">
                        <?php
                        // Выводим ключи первого элемента, если есть данные
                        if (isset($result['response']['data'][0])) {
                            echo "Ключи в первом элементе:\n";
                            print_r(array_keys($result['response']['data'][0]));
                        } elseif (isset($result['response'][0])) {
                            echo "Ключи в первом элементе:\n";
                            print_r(array_keys($result['response'][0]));
                        } else {
                            echo "Ключи ответа:\n";
                            print_r(array_keys($result['response']));
                        }
                        ?>
                    </div>
                <?php else: ?>
                    <div class="error">
                        <strong>⚠️ Не удалось распарсить JSON ответ</strong>
                        <div class="response" style="background: #f5f5f5; color: #333;"><?= htmlspecialchars($result['raw_response']) ?></div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h3>💡 Примеры запросов</h3>
            <div class="row">
                <div class="col">
                    <h4>Получить акты</h4>
                    <pre style="background:#f5f5f5;padding:10px;border-radius:4px;">
endpoint: acts
params: start_date=2026-09-01&end_date=2026-09-07
                    </pre>
                </div>
                <div class="col">
                    <h4>Получить заказы</h4>
                    <pre style="background:#f5f5f5;padding:10px;border-radius:4px;">
endpoint: orders
params: start_date=2026-09-01&end_date=2026-09-07
                    </pre>
                </div>
                <div class="col">
                    <h4>Получить медиапланы</h4>
                    <pre style="background:#f5f5f5;padding:10px;border-radius:4px;">
endpoint: mediaplans
params: start_date=2026-09-01&end_date=2026-09-07
                    </pre>
                </div>
            </div>
            <div class="info" style="margin-top:10px;">
                <strong>⚠️ Важно:</strong> Подставьте реальные названия эндпоинтов из документации!
            </div>
        </div>
        
        <div class="card">
            <h3>📚 Полезные ссылки</h3>
            <ul>
                <li>Документация API: [ссылка на документацию]</li>
                <li>Ваш API ключ: <code><?= substr($API_KEY, 0, 10) ?>... (скрыто)</code></li>
            </ul>
        </div>
    </div>
</body>
</html>