<?php 
// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header('Location: /ORD-Check-acts/login');
    exit;
}

// Получаем данные из сессии
$actsData = isset($_SESSION['acts_data']) ? $_SESSION['acts_data'] : [];
$uploadedFileName = isset($_SESSION['uploaded_file_name']) ? $_SESSION['uploaded_file_name'] : '';

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
                    <a href="/ORD-Check-acts/dashboard#history-section">
                        <span class="nav-icon"><i class="fas fa-history"></i></span>
                        История проверок
                    </a>
                </li>
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
                                                <th style="min-width:120px;text-align:right;">Общее число фактич. показов</th>
                                                <th style="min-width:120px;text-align:right;">Общее число показов по акту</th>
                                                <th style="min-width:100px;">Номер ИД</th>
                                                <th style="min-width:100px;">Дата ИД</th>
                                                <th style="min-width:110px;text-align:right;">Сумма без НДС (пункт)</th>
                                                <th style="min-width:80px;">Ставка НДС (пункт)</th>
                                                <th style="min-width:110px;text-align:right;">Сумма НДС (пункт)</th>
                                                <th style="min-width:110px;text-align:right;">Сумма с НДС (пункт)</th>
                                                <th style="min-width:130px;text-align:right;">Общая сумма без НДС статистики</th>
                                                <th style="min-width:120px;text-align:right;">Общая сумма НДС статистики</th>
                                                <th style="min-width:130px;text-align:right;">Общая сумма с НДС статистики</th>
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
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="23" class="table-empty">
                                                        <i class="fas fa-inbox"></i>
                                                        Нет данных для отображения
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ===== ПРАВАЯ КОЛОНКА: Данные из API (заглушка) ===== -->
                    <div class="col-md-6">
                        <div class="card" style="margin-top:0;">
                            <div class="card-header" style="background:var(--gray-50);">
                                <div class="col-header">
                                    <i class="fas fa-database"></i> Данные из API
                                    <span class="badge bg-secondary" style="font-size:12px;margin-left:8px;">ожидание</span>
                                </div>
                            </div>
                            <div class="card-body" style="padding:12px;">
                                <div class="compare-table-wrap">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Номер акта</th>
                                                <th>Дата акта</th>
                                                <th>Период оказания</th>
                                                <th>Номер ДД</th>
                                                <th>Дата ДД</th>
                                                <th>ИНН заказчика</th>
                                                <th style="text-align:right;">Сумма без НДС</th>
                                                <th>Ставка НДС</th>
                                                <th style="text-align:right;">Сумма НДС</th>
                                                <th style="text-align:right;">Сумма с НДС</th>
                                                <th>ID пункта</th>
                                                <th style="text-align:right;">Общее число фактич. показов</th>
                                                <th style="text-align:right;">Общее число показов по акту</th>
                                                <th>Номер ИД</th>
                                                <th>Дата ИД</th>
                                                <th style="text-align:right;">Сумма без НДС (пункт)</th>
                                                <th>Ставка НДС (пункт)</th>
                                                <th style="text-align:right;">Сумма НДС (пункт)</th>
                                                <th style="text-align:right;">Сумма с НДС (пункт)</th>
                                                <th style="text-align:right;">Общая сумма без НДС статистики</th>
                                                <th style="text-align:right;">Общая сумма НДС статистики</th>
                                                <th style="text-align:right;">Общая сумма с НДС статистики</th>
                                                <th style="text-align:center;">ИД</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td colspan="23" class="table-empty">
                                                    <i class="fas fa-cloud-upload-alt"></i>
                                                    Данные из API будут загружены позже
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Кнопка для возврата -->
                <div class="mt-4">
                    <a href="/ORD-Check-acts/dashboard" class="btn-primary" style="text-decoration:none;display:inline-flex;align-items:center;gap:8px;">
                        <i class="fas fa-arrow-left"></i> На главную
                    </a>
                </div>
            </div>
        </main>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
        }
    </script>
    <script src="/ORD-Check-acts/assets/js/app.js"></script>
</body>
</html>