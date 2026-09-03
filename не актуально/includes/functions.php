<?php
// Подключаем config.php
require_once __DIR__ . '/config.php';

// Проверка авторизации
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Проверка прав администратора
function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

// Перенаправление
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

// Безопасный вывод
function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// Генерация уникального имени файла
function generateFileName($originalName) {
    $ext = pathinfo($originalName, PATHINFO_EXTENSION);
    $name = pathinfo($originalName, PATHINFO_FILENAME);
    $safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $name);
    return date('Ymd_His') . '_' . uniqid() . '.' . $ext;
}

// Сохранение загруженного файла
function saveUploadedFile($file, $subdir = '') {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    $targetDir = UPLOAD_DIR . $subdir;
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    $newName = generateFileName($file['name']);
    $dest = $targetDir . $newName;
    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return $subdir . $newName;
    }
    return false;
}

// Создание нового пользователя
function createUser($pdo, $login, $password, $fullName = '', $isAdmin = false) {
    // Проверяем, существует ли пользователь
    $stmt = $pdo->prepare("SELECT id FROM users WHERE login = ?");
    $stmt->execute([$login]);
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'Пользователь с таким логином уже существует'];
    }
    
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (login, password_hash, full_name, is_admin) VALUES (?, ?, ?, ?)");
    $result = $stmt->execute([$login, $hash, $fullName, $isAdmin ? 1 : 0]);
    
    if ($result) {
        return ['success' => true, 'id' => $pdo->lastInsertId()];
    }
    return ['success' => false, 'error' => 'Ошибка создания пользователя'];
}

// Получение списка пользователей
function getUsers($pdo) {
    $stmt = $pdo->query("SELECT id, login, full_name, is_admin, created_at, last_login, is_active FROM users ORDER BY id");
    return $stmt->fetchAll();
}

// Обновление пользователя
function updateUser($pdo, $userId, $data) {
    $set = [];
    $params = [];
    
    if (isset($data['full_name'])) {
        $set[] = "full_name = ?";
        $params[] = $data['full_name'];
    }
    if (isset($data['is_active'])) {
        $set[] = "is_active = ?";
        $params[] = $data['is_active'] ? 1 : 0;
    }
    if (isset($data['is_admin'])) {
        $set[] = "is_admin = ?";
        $params[] = $data['is_admin'] ? 1 : 0;
    }
    if (isset($data['password']) && !empty($data['password'])) {
        $set[] = "password_hash = ?";
        $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
    }
    
    if (empty($set)) {
        return ['success' => false, 'error' => 'Нет данных для обновления'];
    }
    
    $params[] = $userId;
    $sql = "UPDATE users SET " . implode(', ', $set) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($params);
    
    return ['success' => $result];
}

// Удаление пользователя
function deleteUser($pdo, $userId) {
    // Проверяем, не пытаемся ли удалить последнего администратора
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE is_admin = 1 AND id != ?");
    $stmt->execute([$userId]);
    $adminCount = $stmt->fetch()['count'];
    
    if ($adminCount == 0) {
        return ['success' => false, 'error' => 'Нельзя удалить последнего администратора'];
    }
    
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $result = $stmt->execute([$userId]);
    return ['success' => $result];
}
?>