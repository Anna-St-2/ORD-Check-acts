<?php
// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header('Location: /ORD-Check-acts/login');
    exit;
}


// Подключение к БД
$pdo = Database::getInstance()->getConnection();

$dbImpressions = [];
$stmt = $pdo->query("SELECT act_number, impressions FROM fact_impressions");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $dbImpressions[$row['act_number']] = $row['impressions'];
}

$manualImpressions = array_merge($_SESSION['manual_impressions'] ?? [], $dbImpressions);

// Получаем все сохраненные показы
$dbImpressions = [];
$stmt = $pdo->query("SELECT act_number, impressions FROM fact_impressions");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $dbImpressions[$row['act_number']] = $row['impressions'];
}

// Объединяем с сессией (БД имеет приоритет, если в сессии пусто)
$manualImpressions = array_merge($_SESSION['manual_impressions'] ?? [], $dbImpressions);

// Получаем данные из сессии
$actsData = isset($_SESSION['acts_data']) ? $_SESSION['acts_data'] : [];
$uploadedFileName = isset($_SESSION['uploaded_file_name']) ? $_SESSION['uploaded_file_name'] : '';

// ===== Данные из API =====
$apiData = isset($_SESSION['api_data']) ? $_SESSION['api_data'] : [];
$apiMeta = isset($_SESSION['api_data_meta']) ? $_SESSION['api_data_meta'] : [];
$manualImpressions = $_SESSION['manual_impressions'] ?? [];

// ===== ГРУППИРУЕМ ДАННЫЕ ПО НОМЕРУ АКТА =====
$groupedData = [];

foreach ($actsData as $row) {
    $actNumber = $row['Номер акта'] ?? '';
    if (empty($actNumber)) {
        continue;
    }

    if (!isset($groupedData[$actNumber])) {
        $groupedData[$actNumber] = [
            'items' => [],
            'contracts' => []
        ];
    }

    $groupedData[$actNumber]['items'][] = $row;

    // Собираем уникальные ИД
    $idNumber = $row['Номер изначального договора'] ?? '';
    if (!empty($idNumber) && !in_array($idNumber, $groupedData[$actNumber]['contracts'])) {
        $groupedData[$actNumber]['contracts'][] = $idNumber;
    }
}

// Подсчитываем количество ИД для каждого акта
foreach ($groupedData as $actNumber => &$group) {
    $group['contract_count'] = count($group['contracts']);
}
unset($group);
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Сравнение данных - ORD Check-Acts</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/ORD-Check-acts/assets/css/style.css">



    <style>
        .compare-table-wrap th {
            position: relative !important;
            user-select: none;
        }

        .compare-table-wrap th .col-resizer {
            position: absolute !important;
            top: 0 !important;
            right: -3px !important;
            bottom: 0 !important;
            width: 8px !important;
            cursor: col-resize !important;
            z-index: 100 !important;
            background: transparent;
        }

        .compare-table-wrap th .col-resizer::after {
            content: '';
            position: absolute;
            top: 25%;
            bottom: 25%;
            left: 50%;
            width: 1px;
            background: #cbd5e1;
            transform: translateX(-50%);
        }

        .compare-table-wrap th .col-resizer:hover::after,
        .compare-table-wrap th .col-resizer.dragging::after {
            background: #3b82f6;
            width: 2px;
        }
    </style>
</head>

<body>
    <div class="dashboard-wrapper">
        <!-- Боковая панель -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <div class="brand-icon">
                    <i class="fas fa-check-double"></i>
                </div>
                <div class="brand-text">ORD<span>Check</span></div>
            </div>
            <ul class="sidebar-nav">
                <li>
                    <a href="/ORD-Check-acts/dashboard">
                        <span class="nav-icon"><i class="fas fa-th-large"></i></span>
                        Дашборд
                    </a>
                </li>
                <li>
                    <a href="/ORD-Check-acts/dashboard#new-check-section">
                        <span class="nav-icon"><i class="fas fa-plus-circle"></i></span>
                        Новая проверка
                    </a>
                </li>
                <li>
                    <a href="/ORD-Check-acts/impressions">
                        <span class="nav-icon"><i class="fas fa-chart-bar"></i></span>
                        Факт. показы
                    </a>
                </li>
                <!-- <li>
                    <a href="/ORD-Check-acts/dashboard#history-section">
                        <span class="nav-icon"><i class="fas fa-history"></i></span>
                        История проверок
                    </a>
                </li> -->
                <li>
                    <a href="/ORD-Check-acts/login?action=logout" class="logout">
                        <span class="nav-icon"><i class="fas fa-sign-out-alt"></i></span>
                        Выйти
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Основной контент -->
        <main class="main-content">
            <!-- Шапка -->
            <header class="main-header">
                <div style="display:flex;align-items:center;gap:16px;">
                    <button class="mobile-toggle" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1><i class="fas fa-check-circle" style="color:var(--primary-color);"></i> ORD Check-Acts</h1>
                </div>
                <div class="user-info">
                    <div class="user-avatar">
                        <?= strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)) ?>
                    </div>
                    <span class="user-name"><?= htmlspecialchars($_SESSION['username'] ?? 'Пользователь') ?></span>
                </div>
            </header>

            <!-- Тело -->
            <div class="main-body compare-wrapper">
                <!-- Навигация назад -->
                <div class="mb-3">
                    <a href="/ORD-Check-acts/dashboard" class="back-link">
                        <i class="fas fa-arrow-left"></i> Вернуться на дашборд
                    </a>
                </div>

                <div class="compare-header">
                    <h2><i class="fas fa-table" style="color:var(--primary-color);"></i> Сравнение данных</h2>
                    <span class="badge-info">
                        <i class="far fa-calendar-alt"></i>
                        <?= date('d.m.Y', strtotime($_GET['start'] ?? date('Y-m-01'))) ?> —
                        <?= date('d.m.Y', strtotime($_GET['end'] ?? date('Y-m-d'))) ?>
                        <?php if (!empty($uploadedFileName)): ?>
                            <span style="margin-left:12px;padding-left:12px;border-left:1px solid var(--gray-300);">
                                <i class="fas fa-file"></i> <?= htmlspecialchars($uploadedFileName) ?>
                            </span>
                        <?php endif; ?>
                    </span>
                </div>

                <!-- Две колонки -->
                <div class="row g-4">
                    <!-- ===== ЛЕВАЯ КОЛОНКА: Данные из файла ===== -->
                    <div class="col-md-6">
                        <div class="card" style="margin-top:0;">
                            <div class="card-header" style="background:var(--gray-50);">
                                <div class="col-header">
                                    <i class="fas fa-file-invoice"></i> Данные из актов
                                    <span class="badge bg-primary" style="font-size:12px;margin-left:8px;">
                                        <?= count($groupedData) ?> актов, <?= count($actsData) ?> строк
                                    </span>
                                </div>
                            </div>
                            <div class="card-body" style="padding:12px;">
                                <div class="compare-table-wrap">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th style="min-width:100px;">Номер акта</th>
                                                <th style="min-width:100px;">Дата акта</th>
                                                <th style="min-width:140px;">Период оказания</th>
                                                <th style="min-width:100px;">Номер ДД</th>
                                                <th style="min-width:100px;">Дата ДД</th>
                                                <th style="min-width:110px;">ИНН заказчика</th>
                                                <th style="min-width:110px;text-align:right;">Сумма без НДС</th>
                                                <th style="min-width:80px;">Ставка НДС</th>
                                                <th style="min-width:100px;text-align:right;">Сумма НДС</th>
                                                <th style="min-width:110px;text-align:right;">Сумма с НДС</th>
                                                <th style="min-width:80px;">ID пункта</th>
                                                <th style="min-width:120px;text-align:right;">Общее число фактич.
                                                    показов</th>
                                                <th style="min-width:120px;text-align:right;">Общее число показов по
                                                    акту</th>
                                                <th style="min-width:100px;">Номер ИД</th>
                                                <th style="min-width:100px;">Дата ИД</th>
                                                <th style="min-width:110px;text-align:right;">Сумма без НДС (пункт)</th>
                                                <th style="min-width:80px;">Ставка НДС (пункт)</th>
                                                <th style="min-width:110px;text-align:right;">Сумма НДС (пункт)</th>
                                                <th style="min-width:110px;text-align:right;">Сумма с НДС (пункт)</th>
                                                <th style="min-width:130px;text-align:right;">Общая сумма без НДС
                                                    статистики</th>
                                                <th style="min-width:120px;text-align:right;">Общая сумма НДС статистики
                                                </th>
                                                <th style="min-width:130px;text-align:right;">Общая сумма с НДС
                                                    статистики</th>
                                                <th style="min-width:70px;text-align:center;">ИД</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($groupedData) > 0): ?>
                                                <?php foreach ($groupedData as $actNumber => $group): ?>
                                                    <?php
                                                    $contractCount = $group['contract_count'] ?? 1;
                                                    $isFirst = true;
                                                    ?>

                                                    <?php foreach ($group['items'] as $index => $row): ?>
                                                        <tr class="<?= $isFirst ? 'row-group-first' : 'row-group-child' ?>">

                                                            <!-- ===== ОБЩИЕ ДАННЫЕ АКТА (только в первой строке) ===== -->

                                                            <!-- Номер акта -->
                                                            <td>
                                                                <?php if ($isFirst): ?>
                                                                    <strong><?= htmlspecialchars($actNumber) ?></strong>
                                                                <?php endif; ?>
                                                            </td>

                                                            <!-- Дата акта -->
                                                            <td>
                                                                <?php if ($isFirst): ?>
                                                                    <?= htmlspecialchars($row['Дата акта'] ?? '') ?>
                                                                <?php endif; ?>
                                                            </td>

                                                            <!-- Период оказания -->
                                                            <td>
                                                                <?php if ($isFirst): ?>
                                                                    <?= htmlspecialchars($row['Период оказания услуг по акту'] ?? '') ?>
                                                                <?php endif; ?>
                                                            </td>

                                                            <!-- Номер ДД -->
                                                            <td>
                                                                <?php if ($isFirst): ?>
                                                                    <?= htmlspecialchars($row['Номер конечного договора'] ?? '') ?>
                                                                <?php endif; ?>
                                                            </td>

                                                            <!-- Дата ДД -->
                                                            <td>
                                                                <?php if ($isFirst): ?>
                                                                    <?= htmlspecialchars($row['Дата конечного договора'] ?? '') ?>
                                                                <?php endif; ?>
                                                            </td>

                                                            <!-- ИНН заказчика -->
                                                            <td>
                                                                <?php if ($isFirst): ?>
                                                                    <?= htmlspecialchars($row['ИНН заказчика КД'] ?? '') ?>
                                                                <?php endif; ?>
                                                            </td>

                                                            <!-- Сумма без НДС (общая по акту) -->
                                                            <td style="text-align:right;">
                                                                <?php if ($isFirst): ?>
                                                                    <?= htmlspecialchars($row['Сумма без НДС (акт)'] ?? '') ?>
                                                                <?php endif; ?>
                                                            </td>

                                                            <!-- Ставка НДС (общая) -->
                                                            <td>
                                                                <?php if ($isFirst): ?>
                                                                    <?= htmlspecialchars($row['Ставка НДС (акт)'] ?? '') ?>
                                                                <?php endif; ?>
                                                            </td>

                                                            <!-- Сумма НДС (общая) -->
                                                            <td style="text-align:right;">
                                                                <?php if ($isFirst): ?>
                                                                    <?= htmlspecialchars($row['Сумма НДС (акт)'] ?? '') ?>
                                                                <?php endif; ?>
                                                            </td>

                                                            <!-- Сумма с НДС (общая) -->
                                                            <td style="text-align:right;">
                                                                <?php if ($isFirst): ?>
                                                                    <?= htmlspecialchars($row['Сумма с НДС (акт)'] ?? '') ?>
                                                                <?php endif; ?>
                                                            </td>

                                                            <!-- ID пункта акта -->
                                                            <td>
                                                                <?php if ($isFirst): ?>
                                                                    <?= htmlspecialchars($row['ID пункта акта'] ?? '') ?>
                                                                <?php endif; ?>
                                                            </td>

                                                            <!-- Общее число фактических показов -->
                                                            <td style="text-align:right;">
                                                                <?php if ($isFirst): ?>
                                                                    <?= htmlspecialchars($row['Общее число фактических показов'] ?? '') ?>
                                                                <?php endif; ?>
                                                            </td>

                                                            <!-- Общее число показов по акту -->
                                                            <td style="text-align:right;">
                                                                <?php if ($isFirst): ?>
                                                                    <?= htmlspecialchars($row['Общее число показов по акту'] ?? '') ?>
                                                                <?php endif; ?>
                                                            </td>

                                                            <!-- ===== ДАННЫЕ ПУНКТА АКТА (в каждой строке) ===== -->

                                                            <!-- Номер ИД -->
                                                            <td>
                                                                <?= htmlspecialchars($row['Номер изначального договора'] ?? '') ?>
                                                            </td>

                                                            <!-- Дата ИД -->
                                                            <td>
                                                                <?= htmlspecialchars($row['Дата заключения ИД'] ?? '') ?>
                                                            </td>

                                                            <!-- Сумма без НДС (пункт) -->
                                                            <td style="text-align:right;">
                                                                <?= htmlspecialchars($row['Сумма без НДС (пункт)'] ?? '') ?>
                                                            </td>

                                                            <!-- Ставка НДС (пункт) -->
                                                            <td>
                                                                <?= htmlspecialchars($row['Ставка НДС (пункт)'] ?? '') ?>
                                                            </td>

                                                            <!-- Сумма НДС (пункт) -->
                                                            <td style="text-align:right;">
                                                                <?= htmlspecialchars($row['Сумма НДС (пункт)'] ?? '') ?>
                                                            </td>

                                                            <!-- Сумма с НДС (пункт) -->
                                                            <td style="text-align:right;">
                                                                <?= htmlspecialchars($row['Сумма с НДС (пункт)'] ?? '') ?>
                                                            </td>

                                                            <!-- ===== СТАТИСТИКА (в каждой строке) ===== -->

                                                            <!-- Общая сумма без НДС статистики -->
                                                            <td style="text-align:right;">
                                                                <?= htmlspecialchars($row['Общая сумма без НДС статистики'] ?? '') ?>
                                                            </td>

                                                            <!-- Общая сумма НДС статистики -->
                                                            <td style="text-align:right;">
                                                                <?= htmlspecialchars($row['Общая сумма НДС статистики'] ?? '') ?>
                                                            </td>

                                                            <!-- Общая сумма с НДС статистики -->
                                                            <td style="text-align:right;">
                                                                <?= htmlspecialchars($row['Общая сумма с НДС статистики'] ?? '') ?>
                                                            </td>

                                                            <!-- Количество ИД (только в первой строке) -->
                                                            <td style="text-align:center;">
                                                                <?php if ($isFirst): ?>
                                                                    <span class="badge-contract-count"><?= $contractCount ?></span>
                                                                <?php else: ?>
                                                                    <span style="color:#9ca3af;">↳</span>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                        <?php $isFirst = false; ?>
                                                    <?php endforeach; ?>
                                                <?php endforeach; ?>

                                                <tr>
                                                <?php else: ?>
                                                <tr>
                                                    <td colspan="23" class="table-empty">
                                                        <i class="fas fa-inbox"></i>
                                                        Нет данных из файла актов
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                            </tr>

                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ===== ПРАВАЯ КОЛОНКА: Данные из API (заглушка) ===== -->
                    <!-- Правая колонка: API -->
                    <div class="col-md-6">
                        <div class="card" style="margin-top:0;">
                            <div class="card-header" style="background:var(--gray-50);">
                                <div class="col-header">
                                    <i class="fas fa-database"></i> Данные из API
                                    <span class="badge bg-primary" style="font-size:12px;margin-left:8px;">
                                        <?= count($apiData) ?> записей
                                    </span>
                                    <?php if (empty($apiData) && !empty($uploadedFileName)): ?>
                                        <span class="badge bg-warning" style="font-size:11px;margin-left:8px;">
                                            <i class="fas fa-exclamation-triangle"></i> не загружено
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-body" style="padding:12px;">
                                <div class="compare-table-wrap">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th style="min-width:100px;">Номер акта</th>
                                                <th style="min-width:100px;">Дата акта</th>
                                                <th style="min-width:140px;">Период оказания</th>
                                                <th style="min-width:130px;">Наша компания</th>
                                                <th style="min-width:100px;">Номер ДД</th>
                                                <th style="min-width:100px;">Дата ДД</th>
                                                <th style="min-width:150px;">Наименование контрагента</th>
                                                <th style="min-width:110px;">ИНН заказчика</th>
                                                <th style="min-width:110px;text-align:right;">Сумма без НДС</th>
                                                <th style="min-width:80px;">Ставка НДС</th>
                                                <th style="min-width:100px;text-align:right;">Сумма НДС</th>
                                                <th style="min-width:110px;text-align:right;">Сумма с НДС</th>
                                                <th style="min-width:80px;">ID пункта</th>
                                                <th style="min-width:130px;text-align:right;">Общее число фактич.
                                                    показов</th>
                                                <th style="min-width:120px;text-align:right;">Общее число показов по
                                                    акту</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($apiData) > 0): ?>
                                                <?php foreach ($apiData as $row): ?>
                                                    <tr>
                                                        <td><strong><?= htmlspecialchars($row['Номер акта'] ?? '') ?></strong>
                                                        </td>
                                                        <td><?= htmlspecialchars($row['Дата акта'] ?? '') ?></td>
                                                        <td><?= htmlspecialchars($row['Период оказания'] ?? '') ?></td>
                                                        <td><?= htmlspecialchars($row['Наша компания'] ?? '') ?></td>
                                                        <td><?= htmlspecialchars($row['Номер ДД'] ?? '') ?></td>
                                                        <td><?= htmlspecialchars($row['Дата ДД'] ?? '') ?></td>
                                                        <td><?= htmlspecialchars($row['Наименование контрагента'] ?? '') ?></td>
                                                        <td><?= htmlspecialchars($row['ИНН заказчика'] ?? '') ?></td>
                                                        <td style="text-align:right;">
                                                            <?= htmlspecialchars($row['Сумма без НДС'] ?? '') ?>
                                                        </td>
                                                        <td><?= htmlspecialchars($row['Ставка НДС'] ?? '') ?></td>
                                                        <td style="text-align:right;">
                                                            <?= htmlspecialchars($row['Сумма НДС'] ?? '') ?>
                                                        </td>
                                                        <td style="text-align:right;">
                                                            <?= htmlspecialchars($row['Сумма с НДС'] ?? '') ?>
                                                        </td>
                                                        <td>
                                                            <code
                                                                class="order-id"><?= htmlspecialchars($row['ID пункта'] ?? '') ?></code>
                                                        </td>
                                                        <td style="text-align:right;">

                                                            <input type="text" class="impressions-input"
                                                                data-act="<?= htmlspecialchars($row['Номер акта'] ?? '') ?>"
                                                                value="<?= htmlspecialchars($manualImpressions[$row['Номер акта'] ?? ''] ?? '') ?>"
                                                                placeholder="введите">
                                                            <span class="cpm-label">CPM</span>

                                                            <?= htmlspecialchars($row['Общее число фактич. показов'] ?? '') ?>

                                                        </td>
                                                        <td style="text-align:right;">
                                                            <?= htmlspecialchars($row['Общее число показов по акту'] ?? '') ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="15" class="table-empty">
                                                        <i class="fas fa-cloud-upload-alt"></i>
                                                        <?php if (!empty($uploadedFileName)): ?>
                                                            Данные из API не загружены. Нажмите "Загрузить" на дашборде.
                                                        <?php else: ?>
                                                            Загрузите сначала файл актов
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Кнопка для возврата -->
                    <div class="mt-4" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                        <button id="btnCheck" class="btn-primary" onclick="runCheck()">
                            <i class="fas fa-check-double"></i> Проверить
                        </button>
                        <a href="/ORD-Check-acts/dashboard" class="btn-primary"
                            style="text-decoration:none;display:inline-flex;align-items:center;gap:8px;">
                            <i class="fas fa-arrow-left"></i> На главную
                        </a>
                    </div>
                    <div id="checkResult"></div>
                </div>
        </main>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
        }

        // ===== Изменение ширины столбцов (через colgroup) =====
        function makeColumnsResizable(table) {
            if (table.dataset.resizable === '1') return;
            table.dataset.resizable = '1';

            const thead = table.querySelector('thead');
            if (!thead) return;
            const headerCells = thead.querySelectorAll('th');
            if (!headerCells.length) return;

            // 1. Убираем inline min-width, чтобы они не мешали сжатию
            headerCells.forEach(function (th) {
                th.style.minWidth = '';
                th.style.width = '';
            });

            // 2. Фиксируем текущие ширины колонок
            const widths = Array.from(headerCells).map(function (th) {
                return th.offsetWidth;
            });

            // 3. Создаём colgroup и применяем ширины
            let colgroup = table.querySelector('colgroup');
            if (!colgroup) {
                colgroup = document.createElement('colgroup');
                widths.forEach(function () {
                    colgroup.appendChild(document.createElement('col'));
                });
                table.insertBefore(colgroup, table.firstChild);
            }
            const cols = colgroup.querySelectorAll('col');
            widths.forEach(function (w, i) {
                if (cols[i]) cols[i].style.width = w + 'px';
            });

            // 4. table-layout: fixed — обязателен, чтобы col.width работал
            table.style.tableLayout = 'fixed';

            // 5. Навешиваем ресайзеры
            headerCells.forEach(function (th, i) {
                if (th.querySelector('.col-resizer')) return;

                const resizer = document.createElement('div');
                resizer.className = 'col-resizer';
                th.appendChild(resizer);

                let startX, startWidth;

                resizer.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    startX = e.pageX;
                    startWidth = th.offsetWidth;
                    resizer.classList.add('dragging');

                    function onMouseMove(ev) {
                        const newWidth = Math.max(50, startWidth + (ev.pageX - startX));
                        if (cols[i]) cols[i].style.width = newWidth + 'px';
                    }
                    function onMouseUp() {
                        resizer.classList.remove('dragging');
                        document.removeEventListener('mousemove', onMouseMove);
                        document.removeEventListener('mouseup', onMouseUp);
                    }

                    document.addEventListener('mousemove', onMouseMove);
                    document.addEventListener('mouseup', onMouseUp);
                });
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.compare-table-wrap table').forEach(makeColumnsResizable);
        });



        function runCheck() {
            const inputs = document.querySelectorAll('.impressions-input');
            const data = {};
            inputs.forEach(inp => {
                const act = inp.dataset.act;
                if (act) data[act] = inp.value.trim();
            });

            const btn = document.getElementById('btnCheck');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Проверка...';

            fetch('/ORD-Check-acts/modules/api/compare_check.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({manual_impressions: data})
            })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        document.getElementById('checkResult').innerHTML = res.html;
                        document.querySelectorAll('#checkResult table').forEach(makeColumnsResizable);
                    } else {
                        alert('Ошибка: ' + (res.error || 'неизвестная'));
                    }
                })
                .catch(err => alert('Ошибка соединения: ' + err.message))
                .finally(() => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-check-double"></i> Проверить';
                });
        }
    </script>
</body>

</html>