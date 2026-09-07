<?php 
// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header('Location: /ORD-Check-acts/login');
    exit;
}

// Получаем данные из сессии
$actsData = isset($_SESSION['acts_data']) ? $_SESSION['acts_data'] : [];
$uploadedFileName = isset($_SESSION['uploaded_file_name']) ? $_SESSION['uploaded_file_name'] : '';

// Для отладки - можно посмотреть что в сессии
// error_log('acts_data count: ' . count($actsData));
// error_log('uploaded_file_name: ' . $uploadedFileName);
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
        .compare-wrapper {
            padding: 0 0 32px 0;
        }
        .compare-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .compare-header h2 {
            font-size: 22px;
            font-weight: 700;
            color: var(--gray-900);
            margin: 0;
        }
        .compare-header .badge-info {
            background: var(--gray-100);
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 14px;
            color: var(--gray-600);
        }
        .compare-table-wrap {
            overflow-x: auto;
            margin-bottom: 20px;
            max-height: 600px;
            overflow-y: auto;
        }
        .compare-table-wrap table {
            width: 100%;
            min-width: 1400px;
            font-size: 13px;
            border-collapse: collapse;
        }
        .compare-table-wrap table th {
            background: var(--gray-50);
            color: var(--gray-700);
            font-weight: 600;
            padding: 10px 12px;
            border: 1px solid var(--gray-200);
            white-space: nowrap;
            text-align: left;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .compare-table-wrap table td {
            padding: 8px 12px;
            border: 1px solid var(--gray-200);
            vertical-align: middle;
        }
        .compare-table-wrap table tr:hover td {
            background: var(--gray-50);
        }
        .compare-table-wrap .table-empty {
            text-align: center;
            color: var(--gray-400);
            padding: 40px 0;
        }
        .compare-table-wrap .table-empty i {
            font-size: 40px;
            display: block;
            margin-bottom: 12px;
            color: var(--gray-300);
        }
        .col-header {
            font-weight: 600;
            font-size: 15px;
            color: var(--gray-800);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .col-header i {
            color: var(--primary-color);
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--gray-600);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }
        .back-link:hover {
            color: var(--primary-color);
        }
        .data-count {
            font-size: 13px;
            color: var(--gray-500);
            margin-left: 10px;
        }
        @media (max-width: 768px) {
            .compare-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
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
                    <!-- Левая колонка: Акты -->
                    <div class="col-md-6">
                        <div class="card" style="margin-top:0;">
                            <div class="card-header" style="background:var(--gray-50);">
                                <div class="col-header">
                                    <i class="fas fa-file-invoice"></i> Данные из актов
                                    <span class="badge bg-primary" style="font-size:12px;margin-left:8px;">
                                        <?= count($actsData) ?> записей
                                    </span>
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
                                                <th>ID договора</th>
                                                <th>Номер договора</th>
                                                <th>Дата договора</th>
                                                <th>ИНН заказчика</th>
                                                <th>Сумма без НДС</th>
                                                <th>Ставка НДС</th>
                                                <th>Сумма НДС</th>
                                                <th>Сумма с НДС</th>
                                                <th>ID пункта</th>
                                                <th>Показы факт.</th>
                                                <th>Показы по акту</th>
                                                <th>Сумма без НДС (стат.)</th>
                                                <th>Сумма НДС (стат.)</th>
                                                <th>Сумма с НДС (стат.)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($actsData) > 0): ?>
                                                <?php foreach ($actsData as $index => $row): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($row['Номер акта'] ?? $row['Номер акта'] ?? '') ?></td>
                                                        <td><?= htmlspecialchars($row['Дата акта'] ?? $row['Дата акта'] ?? '') ?></td>
                                                        <td><?= htmlspecialchars($row['Период оказания услуг по акту'] ?? $row['Период оказания услуг по акту'] ?? '') ?></td>
                                                        <td><?= htmlspecialchars($row['Id конечного договора'] ?? $row['Id конечного договора'] ?? '') ?></td>
                                                        <td><?= htmlspecialchars($row['Номер конечного договора'] ?? $row['Номер конечного договора'] ?? '') ?></td>
                                                        <td><?= htmlspecialchars($row['Дата конечного договора'] ?? $row['Дата конечного договора'] ?? '') ?></td>
                                                        <td><?= htmlspecialchars($row['ИНН заказчика КД'] ?? $row['ИНН заказчика КД'] ?? '') ?></td>
                                                        <td style="text-align:right;"><?= htmlspecialchars($row['Сумма без НДС'] ?? $row['Сумма без НДС'] ?? '') ?></td>
                                                        <td><?= htmlspecialchars($row['Ставка НДС'] ?? $row['Ставка НДС'] ?? '') ?></td>
                                                        <td style="text-align:right;"><?= htmlspecialchars($row['Сумма НДС'] ?? $row['Сумма НДС'] ?? '') ?></td>
                                                        <td style="text-align:right;"><?= htmlspecialchars($row['Сумма с НДС'] ?? $row['Сумма с НДС'] ?? '') ?></td>
                                                        <td><?= htmlspecialchars($row['ID пункта акта'] ?? $row['ID пункта акта'] ?? '') ?></td>
                                                        <td style="text-align:right;"><?= htmlspecialchars($row['Общее число фактических показов'] ?? $row['Общее число фактических показов'] ?? '') ?></td>
                                                        <td style="text-align:right;"><?= htmlspecialchars($row['Общее число показов по акту'] ?? $row['Общее число показов по акту'] ?? '') ?></td>
                                                        <td style="text-align:right;"><?= htmlspecialchars($row['Общая сумма без НДС статистики'] ?? $row['Общая сумма без НДС статистики'] ?? '') ?></td>
                                                        <td style="text-align:right;"><?= htmlspecialchars($row['Общая сумма НДС статистики'] ?? $row['Общая сумма НДС статистики'] ?? '') ?></td>
                                                        <td style="text-align:right;"><?= htmlspecialchars($row['Общая сумма с НДС статистики'] ?? $row['Общая сумма с НДС статистики'] ?? '') ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="17" class="table-empty">
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

                    <!-- Правая колонка: API (заглушка) -->
                    <div class="col-md-6">
                        <div class="card" style="margin-top:0;">
                            <div class="card-header" style="background:var(--gray-50);">
                                <div class="col-header">
                                    <i class="fas fa-database"></i> Данные из API (Заказы + Медиапланы)
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
                                                <th>ID договора</th>
                                                <th>Номер договора</th>
                                                <th>Дата договора</th>
                                                <th>ИНН заказчика</th>
                                                <th>Сумма без НДС</th>
                                                <th>Ставка НДС</th>
                                                <th>Сумма НДС</th>
                                                <th>Сумма с НДС</th>
                                                <th>ID пункта</th>
                                                <th>Показы факт.</th>
                                                <th>Показы по акту</th>
                                                <th>Сумма без НДС (стат.)</th>
                                                <th>Сумма НДС (стат.)</th>
                                                <th>Сумма с НДС (стат.)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td colspan="17" class="table-empty">
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