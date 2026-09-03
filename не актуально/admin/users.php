<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

// Проверяем авторизацию и права администратора
if (!isLoggedIn()) {
    redirect('../login.php');
}
if (!isAdmin()) {
    die('Доступ запрещён. Только для администраторов.');
}

$message = '';
$error = '';

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create' && isset($_POST['login'], $_POST['password'])) {
        $result = createUser($pdo, $_POST['login'], $_POST['password'], $_POST['full_name'] ?? '', isset($_POST['is_admin']));
        if ($result['success']) {
            $message = "Пользователь создан успешно";
        } else {
            $error = $result['error'];
        }
    }
    
    if ($action === 'update' && isset($_POST['user_id'])) {
        $data = [];
        if (isset($_POST['full_name'])) $data['full_name'] = $_POST['full_name'];
        if (isset($_POST['is_active'])) $data['is_active'] = (bool)$_POST['is_active'];
        if (isset($_POST['is_admin'])) $data['is_admin'] = (bool)$_POST['is_admin'];
        if (!empty($_POST['password'])) $data['password'] = $_POST['password'];
        
        $result = updateUser($pdo, (int)$_POST['user_id'], $data);
        if ($result['success']) {
            $message = "Пользователь обновлён";
        } else {
            $error = $result['error'];
        }
    }
    
    if ($action === 'delete' && isset($_POST['user_id'])) {
        $result = deleteUser($pdo, (int)$_POST['user_id']);
        if ($result['success']) {
            $message = "Пользователь удалён";
        } else {
            $error = $result['error'];
        }
    }
}

$users = getUsers($pdo);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление пользователями</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <span class="navbar-brand">Администрирование</span>
            <div class="ms-auto">
                <a href="../index.php" class="btn btn-outline-light btn-sm me-2">На главную</a>
                <a href="../logout.php" class="btn btn-outline-light btn-sm">Выйти</a>
            </div>
        </div>
    </nav>
    
    <div class="container mt-4">
        <h2>Управление пользователями</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?= h($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= h($error) ?></div>
        <?php endif; ?>
        
        <!-- Форма создания пользователя -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Создать нового пользователя</h5>
            </div>
            <div class="card-body">
                <form method="post" class="row g-3">
                    <input type="hidden" name="action" value="create">
                    <div class="col-md-4">
                        <label class="form-label">Логин *</label>
                        <input type="text" class="form-control" name="login" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Пароль *</label>
                        <input type="text" class="form-control" name="password" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">ФИО</label>
                        <input type="text" class="form-control" name="full_name">
                    </div>
                    <div class="col-md-6">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="is_admin" id="isAdmin">
                            <label class="form-check-label" for="isAdmin">Администратор</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <button type="submit" class="btn btn-success">Создать пользователя</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Список пользователей -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Список пользователей</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Логин</th>
                                <th>ФИО</th>
                                <th>Админ</th>
                                <th>Активен</th>
                                <th>Создан</th>
                                <th>Последний вход</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= $user['id'] ?></td>
                                    <td><?= h($user['login']) ?></td>
                                    <td><?= h($user['full_name']) ?></td>
                                    <td><?= $user['is_admin'] ? '✅' : '❌' ?></td>
                                    <td><?= $user['is_active'] ? '✅' : '❌' ?></td>
                                    <td><?= date('d.m.Y', strtotime($user['created_at'])) ?></td>
                                    <td><?= $user['last_login'] ? date('d.m.Y H:i', strtotime($user['last_login'])) : '-' ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="editUser(<?= $user['id'] ?>)">Изменить</button>
                                        <form method="post" style="display:inline;" onsubmit="return confirm('Удалить пользователя?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Удалить</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Модальное окно для редактирования пользователя -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post">
                    <div class="modal-header">
                        <h5 class="modal-title">Редактирование пользователя</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="user_id" id="editUserId">
                        <div class="mb-3">
                            <label class="form-label">ФИО</label>
                            <input type="text" class="form-control" name="full_name" id="editFullName">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Новый пароль (оставьте пустым, чтобы не менять)</label>
                            <input type="text" class="form-control" name="password" placeholder="Новый пароль">
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="is_admin" id="editIsAdmin">
                                <label class="form-check-label" for="editIsAdmin">Администратор</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="is_active" id="editIsActive">
                                <label class="form-check-label" for="editIsActive">Активен</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary">Сохранить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editUser(userId) {
            // Получаем данные пользователя из таблицы
            const row = event.target.closest('tr');
            const cells = row.querySelectorAll('td');
            
            document.getElementById('editUserId').value = userId;
            document.getElementById('editFullName').value = cells[2].textContent.trim();
            document.getElementById('editIsAdmin').checked = cells[3].textContent.trim() === '✅';
            document.getElementById('editIsActive').checked = cells[4].textContent.trim() === '✅';
            
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }
    </script>
</body>
</html>