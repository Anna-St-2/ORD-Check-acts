<?php
// Отключаем вывод ошибок в браузер, чтобы они не ломали JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Буферизация вывода на случай, если что-то выведется до JSON
ob_start();

session_start();
header('Content-Type: application/json; charset=utf-8');

// В compare_check.php, после require_once __DIR__ . '/ActComparator.php';
// Подключение к БД (адаптируйте под вашу конфигурацию)
// В API мы не проходим через index.php, поэтому подключаем БД один раз
require_once __DIR__ . '/../../config/database.php';
$pdo = Database::getInstance()->getConnection();
// Безопасная отправка JSON
function sendJson($data)
{
    ob_clean(); // Очищаем буфер от случайного мусора
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    if (!isset($_SESSION['user_id'])) {
        sendJson(['success' => false, 'error' => 'Не авторизован']);
    }

    require_once __DIR__ . '/ActComparator.php';

    $input = json_decode(file_get_contents('php://input'), true);
    $manual = $input['manual_impressions'] ?? [];
    if (!is_array($manual)) {
        $manual = [];
    }

    // Сохраняем ручной ввод в сессию
    $_SESSION['manual_impressions'] = $manual;
    // ДОБАВЛЯЕМ СОХРАНЕНИЕ В БД:
    $stmt = $pdo->prepare("INSERT INTO fact_impressions (act_number, impressions) 
                           VALUES (:act, :imp) 
                           ON DUPLICATE KEY UPDATE impressions = :imp");
    foreach ($manual as $actNum => $impVal) {
        if (trim($impVal) === '')
            continue;
        // Очищаем от пробелов и берем только цифры
        $cleanImp = (int) preg_replace('/\D+/', '', $impVal);
        $stmt->execute([':act' => $actNum, ':imp' => $cleanImp]);
    }

    $actsData = $_SESSION['acts_data'] ?? [];
    $apiData = $_SESSION['api_data'] ?? [];

    if (empty($actsData)) {
        sendJson(['success' => false, 'error' => 'Нет данных из файла. Загрузите файл.']);
    }
    if (empty($apiData)) {
        sendJson(['success' => false, 'error' => 'Нет данных из API. Нажмите «Загрузить».']);
    }

    $grouped = [];
    foreach ($actsData as $row) {
        $actNumber = $row['Номер акта'] ?? '';
        if ($actNumber === '')
            continue;
        if (!isset($grouped[$actNumber]))
            $grouped[$actNumber] = ['items' => []];
        $grouped[$actNumber]['items'][] = $row;
    }

    $comparator = new ActComparator($apiData, $manual);
    $results = $comparator->compare($grouped);

    $html = renderComparisonTable($results);

    sendJson(['success' => true, 'html' => $html]);
} catch (Throwable $e) {
    sendJson(['success' => false, 'error' => 'Ошибка сервера: ' . $e->getMessage() . ' в ' . basename($e->getFile()) . ':' . $e->getLine()]);
}

function renderComparisonTable(array $results): string
{
    $labels = [
        'date' => 'Дата акта',
        'period' => 'Период',
        'contractNumber' => 'Номер ДД',
        'contractDate' => 'Дата ДД',
        'inn' => 'ИНН',
        'amountWithoutVat' => 'Сумма без НДС',
        'vatPct' => 'Ставка НДС',
        'vat' => 'Сумма НДС',
        'amountWithVat' => 'Сумма с НДС',
        'itemsAmountWithoutVat' => 'Пункты б/НДС',
        'itemsVat' => 'Пункты НДС',
        'itemsAmountWithVat' => 'Пункты с НДС',
        'stats' => 'Статистика',
        'impressions' => 'Показы',
    ];

    ob_start();
    ?>
    <div class="card" style="margin-top:20px;">
        <div class="card-header">
            <h5><i class="fas fa-check-double"></i> Проверка данных</h5>
        </div>
        <div class="card-body" style="padding:12px;">
            <div class="compare-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th style="min-width:90px;">Номер акта</th>
                            <th>Дата акта</th>
                            <th>Период</th>
                            <th>Номер ДД</th>
                            <th>Дата ДД</th>
                            <th>ИНН</th>
                            <th>Сумма без НДС</th>
                            <th>Ставка НДС</th>
                            <th>Сумма НДС</th>
                            <th>Сумма с НДС</th>
                            <th>Пункты б/НДС</th>
                            <th>Пункты НДС</th>
                            <th>Пункты с НДС</th>
                            <th>Стат. без НДС</th>
                            <th>Стат. с НДС</th>
                            <th style="min-width:120px;text-align:right;">Показы по факту</th>
                            <th style="min-width:120px;text-align:right;">Показы по акту</th>
                            <th style="min-width:180px;">Проверка показов</th>
                            <th>Проверка акта</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $r): ?>
                            <tr class="<?= $r['overall'] ? '' : 'row-error' ?>">
                                <td><strong><?= htmlspecialchars($r['actNumber']) ?></strong></td>
                                <?php if ($r['status'] === 'not_found'): ?>
                                    <td colspan="18" style="color:#EF4444;background:#FEE2E2;font-weight:bold;">
                                        <?= htmlspecialchars($r['message']) ?>
                                    </td>
                                <?php else: ?>
                                    <td><?= checkIcon($r['checks']['date']) ?></td>
                                    <td><?= checkIcon($r['checks']['period']) ?></td>
                                    <td><?= checkIcon($r['checks']['contractNumber']) ?></td>
                                    <td><?= checkIcon($r['checks']['contractDate']) ?></td>
                                    <td><?= checkIcon($r['checks']['inn']) ?></td>
                                    <td><?= checkIcon($r['checks']['amountWithoutVat']) ?></td>
                                    <td><?= checkIcon($r['checks']['vatPct']) ?></td>
                                    <td><?= checkIcon($r['checks']['vat']) ?></td>
                                    <td><?= checkIcon($r['checks']['amountWithVat']) ?></td>
                                    <td><?= checkIcon($r['checks']['itemsAmountWithoutVat']) ?></td>
                                    <td><?= checkIcon($r['checks']['itemsVat']) ?></td>
                                    <td><?= checkIcon($r['checks']['itemsAmountWithVat']) ?></td>
                                    <td><?= statCell($r['checks']['statWithoutVat']) ?></td>
                                    <td><?= statCell($r['checks']['statWithVat']) ?></td>
                                    <td style="text-align:right;"><?= htmlspecialchars($r['checks']['impressions']['fact'] ?? '') ?>
                                    </td>
                                    <td style="text-align:right;"><?= htmlspecialchars($r['checks']['impressions']['plan'] ?? '') ?>
                                    </td>
                                    <td><?= impressionsCell($r['checks']['impressions']) ?></td>
                                    <?php
                                    $failedLabels = [];
                                    foreach ($r['failed'] as $f) {
                                        if (isset($labels[$f]))
                                            $failedLabels[] = $labels[$f];
                                    }
                                    $title = $failedLabels ? 'Не совпадает: ' . implode(', ', $failedLabels) : 'Все проверки пройдены';
                                    ?>
                                    <td title="<?= htmlspecialchars($title) ?>"><?= checkIcon($r['overall']) ?></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function checkIcon($ok): string
{
    return $ok
        ? '<span style="color:#10B981;font-size:18px;">✓</span>'
        : '<span style="color:#EF4444;font-size:18px;">✗</span>';
}

function statCell(array $check): string
{
    $diff = $check['diff'];
    $color = $check['ok'] ? '#10B981' : '#EF4444';
    $icon = $check['ok'] ? '✓' : '✗';
    $diffText = number_format($diff, 2, '.', ' ');
    return '<span style="color:' . $color . ';white-space:nowrap;">' . $icon . ' ' . $diffText . '</span>';
}

function impressionsCell(array $check): string
{
    $color = $check['ok'] ? '#10B981' : '#EF4444';
    return '<span style="color:' . $color . ';">' . htmlspecialchars($check['message']) . '</span>';
}