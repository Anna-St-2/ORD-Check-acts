<?php
// Получаем список старых проверок для текущего пользователя
$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM checks WHERE user_id = ? ORDER BY date_created DESC");
$stmt->execute([$userId]);
$checks = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Проверка актов ОРД</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .nav-tabs .nav-link.active {
            font-weight: bold;
        }

        .file-upload-preview {
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <span class="navbar-brand">Проверка актов ОРД</span>
            <div class="ms-auto">
                <?php if (isAdmin()): ?>
                    <a href="admin/users.php" class="btn btn-outline-info btn-sm me-2">👥 Пользователи</a>
                <?php endif; ?>
                <span class="navbar-text me-3">Пользователь: <?= h($_SESSION['login']) ?></span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">Выйти</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($_SESSION['flash'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= h($_SESSION['flash']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['flash']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['flash_error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $_SESSION['flash_error'] // уже содержит HTML-теги, поэтому не экранируем ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>
        <ul class="nav nav-tabs" id="mainTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="old-tab" data-bs-toggle="tab" data-bs-target="#old" type="button"
                    role="tab">Старые проверки</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="new-tab" data-bs-toggle="tab" data-bs-target="#new" type="button"
                    role="tab">Новая проверка</button>
            </li>
        </ul>
        <div class="tab-content mt-3">
            <!-- Вкладка "Старые проверки" -->
            <div class="tab-pane fade show active" id="old" role="tabpanel">
                <h4>Список проверок</h4>
                <?php if (empty($checks)): ?>
                    <p class="text-muted">Нет сохранённых проверок.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Дата создания</th>
                                    <th>Статус</th>
                                    <th>Файлы</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($checks as $check): ?>
                                    <tr>
                                        <td><?= $check['id'] ?></td>
                                        <td><?= date('d.m.Y H:i', strtotime($check['date_created'])) ?></td>
                                        <td><?= h($check['status']) ?></td>
                                        <td>
                                            <?php if ($check['orders_file']): ?>
                                                <span class="badge bg-secondary">Заказы</span>
                                            <?php endif; ?>
                                            <?php if ($check['mediaplan_file']): ?>
                                                <span class="badge bg-secondary">Медиапланы</span>
                                            <?php endif; ?>
                                            <?php if ($check['acts_file']): ?>
                                                <span class="badge bg-secondary">Акты</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="#" class="btn btn-sm btn-primary"
                                                onclick="alert('Просмотр проверки #<?= $check['id'] ?> (пока не реализован)')">Просмотр</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Вкладка "Новая проверка" -->
            <div class="tab-pane fade" id="new" role="tabpanel">
                <h4>Создание новой проверки</h4>
                <p>Загрузите файлы: Заказы, Медиапланы, Акты (все поля обязательны).</p>
                <form action="upload.php" method="post" enctype="multipart/form-data" id="uploadForm">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="ordersFile" class="form-label">Файл Заказов</label>
                            <input type="file" class="form-control" id="ordersFile" name="orders_file"
                                accept=".xlsx,.xls,.csv,.txt" required>
                        </div>
                        <div class="col-md-4">
                            <label for="mediaplanFile" class="form-label">Файл Медиапланов</label>
                            <input type="file" class="form-control" id="mediaplanFile" name="mediaplan_file"
                                accept=".xlsx,.xls,.csv,.txt" required>
                        </div>
                        <div class="col-md-4">
                            <label for="actsFile" class="form-label">Файл Актов</label>
                            <input type="file" class="form-control" id="actsFile" name="acts_file"
                                accept=".xlsx,.xls,.csv,.txt,.docx" required>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-success">Загрузить и создать проверку</button>
                    </div>
                </form>
                <div id="uploadStatus" class="mt-3"></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Можно добавить валидацию или отображение имён файлов
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', function (e) {
                const fileName = this.files[0] ? this.files[0].name : 'Файл не выбран';
                const label = this.closest('.col-md-4').querySelector('.form-label');
                if (label) {
                    label.textContent = label.textContent.replace(/\(.*\)/, '(' + fileName + ')');
                }
            });
        });
    </script>
</body>

</html>