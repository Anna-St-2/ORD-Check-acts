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
                    <a href="#new-check-section"
                        onclick="document.getElementById('new-check-section').scrollIntoView({behavior:'smooth'})">
                        <span class="nav-icon"><i class="fas fa-plus-circle"></i></span>
                        Новая проверка
                    </a>
                </li>
                <li>
                    <a href="#history-section"
                        onclick="document.getElementById('history-section').scrollIntoView({behavior:'smooth'})">
                        <span class="nav-icon"><i class="fas fa-history"></i></span>
                        История проверок
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
                <!-- БЛОК ИСТОРИИ ПРОВЕРОК (сверху) -->
                <div id="history-section" class="card">
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
                                    </tr>
                                </thead>
                                <tbody id="historyBody">
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            <i class="fas fa-inbox"
                                                style="font-size:24px;display:block;margin-bottom:8px;"></i>
                                            Нет выполненных проверок
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- БЛОК НОВОЙ ПРОВЕРКИ -->
                <div id="new-check-section" class="card" style="margin-top:20pt;">
                    <div class="card-header">
                        <h5><i class="fas fa-upload"></i> Новая проверка</h5>
                    </div>
                    <div class="card-body">
                        <!-- Блок загрузки файла актов (уменьшенный) -->
                        <div class="file-upload-small">
                            <label class="form-label">
                                <i class="fas fa-file-invoice"></i> Файл с Актами
                            </label>
                            <div class="file-upload-wrapper" onclick="document.getElementById('actsFile').click()">
                                <div class="file-icon"><i class="fas fa-file-excel"></i></div>
                                <div class="file-text">
                                    <strong>Выберите файл актов</strong><br>
                                    <span id="actsFileName">Файл не выбран</span>
                                </div>
                                <input type="file" id="actsFile" name="actsFile" accept=".xlsx,.xls,.csv"
                                    style="display:none;" required>
                            </div>
                            <small class="text-muted"><i class="fas fa-info-circle"></i> .xlsx, .xls, .csv</small>
                        </div>

                        <!-- Блок API для получения данных из MonkeyCopilot -->
                        <div class="api-section mt-4">
                            <div class="api-label" style="margin-bottom:12px;">
                                <i class="fas fa-robot"></i>
                                Получение данных из MonkeyCopilot
                            </div>

                            <div class="row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label">Начало периода</label>
                                    <input type="date" class="form-control" id="periodStart"
                                        value="<?= date('Y-m-01') ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Окончание периода</label>
                                    <input type="date" class="form-control" id="periodEnd" value="<?= date('Y-m-d') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Юниты</label>
                                    <div class="dropdown-checkboxes" id="unitDropdown">
                                        <div class="dropdown-toggle" onclick="toggleUnitDropdown()">
                                            <span id="selectedUnitsLabel">Выберите юниты</span>
                                            <span class="arrow"><i class="fas fa-chevron-down"></i></span>
                                        </div>
                                        <div class="dropdown-menu" id="unitDropdownMenu">
                                            <div id="unitCheckboxes"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <button class="btn-primary" style="width:100%;padding:10px 16px;font-size:14px;"
                                        onclick="loadData()">
                                        <i class="fas fa-cloud-upload-alt"></i> Загрузить
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Прогресс загрузки -->
                        <div id="uploadProgress" class="mt-4" style="display: none;">
                            <div class="progress">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar"
                                    style="width: 0%"></div>
                            </div>
                            <p class="mt-2 text-muted" id="progressText" style="font-size:14px;">
                                <i class="fas fa-spinner fa-spin"></i> Загрузка данных...
                            </p>
                        </div>

                        <div id="fileStatus" class="mt-3"></div>

                        <!-- Область для отображения таблиц (пока скрыта) -->
                        <!-- <div id="tablesContainer" class="mt-4" style="display: none;">
                            <hr>
                            <h6><i class="fas fa-table"></i> Сравнение данных</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card" style="margin-top:10px;">
                                        <div class="card-header" style="background:#f8f9fa;">
                                            <small><i class="fas fa-file-invoice"></i> Данные из актов</small>
                                        </div>
                                        <div class="card-body" style="padding:10px;">
                                            <div id="actsTable"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card" style="margin-top:10px;">
                                        <div class="card-header" style="background:#f8f9fa;">
                                            <small><i class="fas fa-database"></i> Данные из API (Заказы +
                                                Медиапланы)</small>
                                        </div>
                                        <div class="card-body" style="padding:10px;">
                                            <div id="apiTable"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div> -->
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

        // Отображение имени выбранного файла
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', function () {
                const labelId = this.id + 'Name';
                const label = document.getElementById(labelId);
                if (label) {
                    label.textContent = this.files[0] ? this.files[0].name : 'Файл не выбран';
                }
            });
        });

        // ---------- DROPDOWN ДЛЯ ЮНИТОВ ----------
        function toggleUnitDropdown() {
            const menu = document.getElementById('unitDropdownMenu');
            const arrow = document.querySelector('.dropdown-toggle .arrow');
            if (menu) {
                menu.classList.toggle('open');
            }
            if (arrow) {
                arrow.classList.toggle('open');
            }
        }

        // Закрывать dropdown при клике вне его
        document.addEventListener('click', function (event) {
            const dropdown = document.getElementById('unitDropdown');
            if (dropdown && !dropdown.contains(event.target)) {
                const menu = document.getElementById('unitDropdownMenu');
                const arrow = document.querySelector('.dropdown-toggle .arrow');
                if (menu) menu.classList.remove('open');
                if (arrow) arrow.classList.remove('open');
            }
        });

        // Генерация чекбоксов юнитов внутри dropdown
        function renderUnitCheckboxes() {
            const units = [
                'Deaz.io', 'HPlatformFull', 'HPlatformSelf', 'Hybe',
                'India', 'Indonesia', 'INSEA', 'KidsProject', 'Mapps',
                'MetaverseGlobal', 'MetaverseRu', 'Poland', 'Tambov',
                'Thailand', 'Trackadero', 'Ukraine', 'Vietnam', 'VoxRussia'
            ];
            const container = document.getElementById('unitCheckboxes');
            let html = `
        <div class="dropdown-item select-all">
            <input type="checkbox" id="unit_all" checked>
            <label for="unit_all"><strong>Все юниты</strong></label>
        </div>
    `;
            units.forEach(unit => {
                const id = 'unit_' + unit.replace(/[\.\-]/g, '_');
                html += `
            <div class="dropdown-item">
                <input type="checkbox" id="${id}" value="${unit}" class="unit-checkbox" checked>
                <label for="${id}">${unit}</label>
            </div>
        `;
            });
            container.innerHTML = html;

            // Логика "Выбрать все"
            const allCheckbox = document.getElementById('unit_all');
            const unitCheckboxes = document.querySelectorAll('.unit-checkbox');

            if (allCheckbox) {
                allCheckbox.addEventListener('change', function () {
                    const isChecked = this.checked;
                    unitCheckboxes.forEach(cb => cb.checked = isChecked);
                    updateSelectedLabel();
                });
            }

            unitCheckboxes.forEach(cb => {
                cb.addEventListener('change', function () {
                    const allChecked = Array.from(unitCheckboxes).every(c => c.checked);
                    if (allCheckbox) allCheckbox.checked = allChecked;
                    updateSelectedLabel();
                });
            });

            updateSelectedLabel();
        }

        function updateSelectedLabel() {
            const checked = document.querySelectorAll('.unit-checkbox:checked');
            const count = checked.length;
            const total = document.querySelectorAll('.unit-checkbox').length;
            const label = document.getElementById('selectedUnitsLabel');
            if (!label) return;

            if (count === total) {
                label.textContent = 'Все юниты (' + total + ')';
            } else if (count === 0) {
                label.textContent = 'Выберите юниты';
            } else {
                const names = Array.from(checked).slice(0, 3).map(cb => cb.value);
                const remainder = count - 3;
                label.textContent = names.join(', ') + (remainder > 0 ? ` и еще ${remainder}` : '');
            }
        }

        // Инициализация
        document.addEventListener('DOMContentLoaded', function () {
            renderUnitCheckboxes();
            loadHistory();
        });


        // Инициализация чекбоксов
        document.addEventListener('DOMContentLoaded', function () {
            renderUnitCheckboxes();
            loadHistory();

            // Обработчики для чекбоксов
            const allCheckbox = document.getElementById('unit_all');
            const unitCheckboxes = document.querySelectorAll('.unit-checkbox');

            if (allCheckbox) {
                allCheckbox.addEventListener('change', function () {
                    const isChecked = this.checked;
                    unitCheckboxes.forEach(cb => cb.checked = isChecked);
                    updateSelectedLabel();
                });
            }

            unitCheckboxes.forEach(cb => {
                cb.addEventListener('change', function () {
                    const allChecked = Array.from(unitCheckboxes).every(c => c.checked);
                    if (allCheckbox) allCheckbox.checked = allChecked;
                    updateSelectedLabel();
                });
            });
        });

        // ---------- ОСНОВНАЯ ФУНКЦИЯ ЗАГРУЗКИ ----------
        function loadData() {
            console.log('loadData() вызвана');

            // Проверяем файл
            const fileInput = document.getElementById('actsFile');
            if (!fileInput || !fileInput.files[0]) {
                showStatus('error', 'Пожалуйста, выберите файл с актами');
                return;
            }

            // Проверяем период
            const start = document.getElementById('periodStart').value;
            const end = document.getElementById('periodEnd').value;
            if (!start || !end) {
                showStatus('error', 'Пожалуйста, выберите период');
                return;
            }

            // Проверяем выбор юнитов
            const selectedUnits = [];
            document.querySelectorAll('.unit-checkbox:checked').forEach(function (cb) {
                selectedUnits.push(cb.value);
            });
            if (selectedUnits.length === 0) {
                showStatus('error', 'Пожалуйста, выберите хотя бы один юнит');
                return;
            }

            // Показываем прогресс
            const progressDiv = document.getElementById('uploadProgress');
            const progressBar = progressDiv.querySelector('.progress-bar');
            const progressText = document.getElementById('progressText');
            progressDiv.style.display = 'block';
            progressBar.style.width = '0%';
            progressText.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Загрузка и обработка файла...';

            // Отправляем файл через AJAX
            const formData = new FormData();
            formData.append('actsFile', fileInput.files[0]);

            // ПРАВИЛЬНЫЙ ПУТЬ - файл в modules/api/
            fetch('modules/api/upload_acts.php', {
                method: 'POST',
                body: formData
            })
                .then(function (response) {
                    return response.text();
                })
                .then(function (text) {
                    console.log('Ответ сервера:', text);
                    try {
                        const data = JSON.parse(text);
                        if (data.success) {
                            progressBar.style.width = '100%';
                            progressText.innerHTML = '<i class="fas fa-check-circle" style="color:#10B981;"></i> Файл обработан! Найдено записей: ' + data.count;
                            progressBar.classList.remove('progress-bar-animated');
                            progressBar.classList.add('bg-success');

                            setTimeout(function () {
                                const params = new URLSearchParams({
                                    start: start,
                                    end: end,
                                    units: selectedUnits.join(','),
                                    file: encodeURIComponent(fileInput.files[0].name)
                                });
                                window.location.href = '/ORD-Check-acts/compare?' + params.toString();
                            }, 1000);
                        } else {
                            progressBar.classList.remove('progress-bar-animated');
                            progressBar.classList.add('bg-danger');
                            progressText.innerHTML = '<i class="fas fa-times-circle" style="color:#EF4444;"></i> Ошибка: ' + data.error;
                            showStatus('error', data.error);
                        }
                    } catch (e) {
                        console.error('Ошибка парсинга JSON:', e, 'Текст:', text);
                        progressBar.classList.remove('progress-bar-animated');
                        progressBar.classList.add('bg-danger');
                        progressText.innerHTML = '<i class="fas fa-times-circle" style="color:#EF4444;"></i> Ошибка обработки ответа';
                        showStatus('error', 'Ошибка на сервере. Проверьте консоль.');
                    }
                })
                .catch(function (error) {
                    console.error('Ошибка запроса:', error);
                    progressBar.classList.remove('progress-bar-animated');
                    progressBar.classList.add('bg-danger');
                    progressText.innerHTML = '<i class="fas fa-times-circle" style="color:#EF4444;"></i> Ошибка соединения';
                    showStatus('error', 'Ошибка при загрузке файла: ' + error.message);
                });
        }

        // Функция отображения статуса
        function showStatus(type, message) {
            const statusDiv = document.getElementById('fileStatus');
            if (!statusDiv) return;

            const alertClass = type === 'success' ? 'alert-success' :
                type === 'error' ? 'alert-danger' : 'alert-info';
            const icon = type === 'success' ? 'fa-check-circle' :
                type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle';

            statusDiv.innerHTML = `
            <div class="alert ${alertClass}">
                <span class="alert-icon"><i class="fas ${icon}"></i></span>
                ${message}
            </div>
        `;
        }

        // Загрузка истории (заглушка)
        function loadHistory() {
            const tbody = document.getElementById('historyBody');
            if (!tbody) return;

            tbody.innerHTML = `
            <tr>
                <td colspan="4" class="text-center text-muted py-4">
                    <i class="fas fa-spinner fa-spin" style="font-size:24px;display:block;margin-bottom:8px;"></i>
                    Загрузка истории...
                </td>
            </tr>
        `;

            setTimeout(() => {
                tbody.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">
                        <i class="fas fa-inbox" style="font-size:24px;display:block;margin-bottom:8px;"></i>
                        Нет выполненных проверок
                    </td>
                </tr>
            `;
            }, 1000);
        }

        // Инициализация
        document.addEventListener('DOMContentLoaded', function () {
            renderUnitCheckboxes();
            loadHistory();
        });
    </script>
</body>

</html>