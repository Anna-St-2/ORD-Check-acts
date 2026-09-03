<?php 
// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header('Location: /ORD-Check-acts/login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Дашборд - ORD Check-Acts</title>
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
                    <a href="/ORD-Check-acts/dashboard" class="active">
                        <span class="nav-icon"><i class="fas fa-th-large"></i></span>
                        Дашборд
                    </a>
                </li>
                <li>
                    <a href="#" onclick="switchTab('new-check')">
                        <span class="nav-icon"><i class="fas fa-plus-circle"></i></span>
                        Новая проверка
                    </a>
                </li>
                <li>
                    <a href="#" onclick="switchTab('history')">
                        <span class="nav-icon"><i class="fas fa-history"></i></span>
                        История
                    </a>
                </li>
                <li>
                    <a href="/ORD-Check-acts/login?action=logout" class="logout">
                        <span class="nav-icon"><i class="fas fa-sign-out-alt"></i></span>
                        Выйти из системы
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
            <div class="main-body">
                <!-- Вкладки -->
                <ul class="nav-tabs" id="mainTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="history-tab" data-bs-toggle="tab" 
                                data-bs-target="#history" type="button" role="tab">
                            <i class="fas fa-history"></i> История проверок
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="new-check-tab" data-bs-toggle="tab" 
                                data-bs-target="#new-check" type="button" role="tab">
                            <i class="fas fa-plus-circle"></i> Новая проверка
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="mainTabContent">
                    <!-- Вкладка "История проверок" -->
                    <div class="tab-pane fade show active" id="history" role="tabpanel">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-list"></i> История проверок</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Дата</th>
                                                <th>Статус</th>
                                                <th>Файлов</th>
                                                <th>Действия</th>
                                            </tr>
                                        </thead>
                                        <tbody id="historyBody">
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-4">
                                                    <i class="fas fa-inbox" style="font-size:24px;display:block;margin-bottom:8px;"></i>
                                                    Нет выполненных проверок
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Вкладка "Новая проверка" -->
                    <div class="tab-pane fade" id="new-check" role="tabpanel">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-upload"></i> Загрузка файлов для проверки</h5>
                            </div>
                            <div class="card-body">
                                <form id="uploadForm" enctype="multipart/form-data">
                                    <div class="row g-4">
                                        <div class="col-md-4">
                                            <label class="form-label">
                                                <i class="fas fa-file-invoice"></i> Файл с Заказами
                                            </label>
                                            <div class="file-upload-wrapper" onclick="document.getElementById('ordersFile').click()">
                                                <div class="file-icon"><i class="fas fa-file-excel"></i></div>
                                                <div class="file-text">
                                                    <strong>Выберите файл</strong><br>
                                                    <span id="ordersFileName">Файл не выбран</span>
                                                </div>
                                                <input type="file" id="ordersFile" name="ordersFile" 
                                                       accept=".xlsx,.xls,.csv" style="display:none;" required>
                                            </div>
                                            <small class="text-muted"><i class="fas fa-info-circle"></i> .xlsx, .xls, .csv</small>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <label class="form-label">
                                                <i class="fas fa-file-invoice"></i> Файл с Медиапланами
                                            </label>
                                            <div class="file-upload-wrapper" onclick="document.getElementById('mediaplansFile').click()">
                                                <div class="file-icon"><i class="fas fa-file-excel"></i></div>
                                                <div class="file-text">
                                                    <strong>Выберите файл</strong><br>
                                                    <span id="mediaplansFileName">Файл не выбран</span>
                                                </div>
                                                <input type="file" id="mediaplansFile" name="mediaplansFile" 
                                                       accept=".xlsx,.xls,.csv" style="display:none;" required>
                                            </div>
                                            <small class="text-muted"><i class="fas fa-info-circle"></i> .xlsx, .xls, .csv</small>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <label class="form-label">
                                                <i class="fas fa-file-invoice"></i> Файл с Актами
                                            </label>
                                            <div class="file-upload-wrapper" onclick="document.getElementById('actsFile').click()">
                                                <div class="file-icon"><i class="fas fa-file-excel"></i></div>
                                                <div class="file-text">
                                                    <strong>Выберите файл</strong><br>
                                                    <span id="actsFileName">Файл не выбран</span>
                                                </div>
                                                <input type="file" id="actsFile" name="actsFile" 
                                                       accept=".xlsx,.xls,.csv" style="display:none;" required>
                                            </div>
                                            <small class="text-muted"><i class="fas fa-info-circle"></i> .xlsx, .xls, .csv</small>
                                        </div>
                                    </div>

                                    <div class="row mt-4">
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="autoTranslate" checked>
                                                <label class="form-check-label" for="autoTranslate">
                                                    <i class="fas fa-language"></i> Автоматический перевод заголовков
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="filterORD" checked>
                                                <label class="form-check-label" for="filterORD">
                                                    <i class="fas fa-filter"></i> Удалить строки с ORD = "Нет"
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-4">
                                        <button type="submit" class="btn-primary" style="width:auto;padding:12px 32px;">
                                            <i class="fas fa-upload"></i> Загрузить и проверить
                                        </button>
                                    </div>

                                    <div id="uploadProgress" class="mt-4" style="display: none;">
                                        <div class="progress">
                                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                                 role="progressbar" style="width: 0%"></div>
                                        </div>
                                        <p class="mt-2 text-muted" id="progressText" style="font-size:14px;">
                                            <i class="fas fa-spinner fa-spin"></i> Загрузка файлов...
                                        </p>
                                    </div>
                                </form>

                                <div id="fileStatus" class="mt-3"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Наш JS -->
    <script src="/ORD-Check-acts/assets/js/app.js"></script>
    
    <script>
    // Переключение боковой панели на мобильных
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('open');
    }
    
    // Переключение вкладок
    function switchTab(tabId) {
        const tabs = document.querySelectorAll('.nav-link');
        const panes = document.querySelectorAll('.tab-pane');
        
        tabs.forEach(tab => tab.classList.remove('active'));
        panes.forEach(pane => pane.classList.remove('show', 'active'));
        
        const targetTab = document.querySelector(`#${tabId}-tab`);
        const targetPane = document.getElementById(tabId);
        
        if (targetTab) targetTab.classList.add('active');
        if (targetPane) {
            targetPane.classList.add('show', 'active');
        }
        
        // Закрываем мобильное меню
        document.getElementById('sidebar').classList.remove('open');
    }
    
    // Отображение имени выбранного файла
    document.querySelectorAll('input[type="file"]').forEach(input => {
        input.addEventListener('change', function() {
            const labelId = this.id + 'Name';
            const label = document.getElementById(labelId);
            if (label) {
                label.textContent = this.files[0] ? this.files[0].name : 'Файл не выбран';
            }
        });
    });
    </script>
</body>
</html>