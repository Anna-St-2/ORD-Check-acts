<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';

// Проверка авторизации
if (!isLoggedIn()) {
    redirect('login.php');
}

$userId = $_SESSION['user_id'];
$errors = [];
$uploaded = [];

// Проверяем, что все файлы загружены
$fileKeys = ['orders_file', 'mediaplan_file', 'acts_file'];
foreach ($fileKeys as $key) {
    if (!isset($_FILES[$key]) || $_FILES[$key]['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = "Файл для поля '$key' не загружен";
    } elseif ($_FILES[$key]['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Ошибка загрузки файла '$key' (код " . $_FILES[$key]['error'] . ")";
    }
}

if (empty($errors)) {
    // Сохраняем файлы
    foreach ($fileKeys as $key) {
        $file = $_FILES[$key];
        $savedPath = saveUploadedFile($file, 'check_' . date('Ymd_His') . '/');
        if ($savedPath === false) {
            $errors[] = "Не удалось сохранить файл '$key'";
        } else {
            $uploaded[$key] = $savedPath;
        }
    }
}

if (empty($errors)) {
    // Создаём запись в БД
    try {
        $stmt = $pdo->prepare("INSERT INTO checks (user_id, orders_file, mediaplan_file, acts_file, status) VALUES (?, ?, ?, ?, 'uploaded')");
        $stmt->execute([
            $userId,
            $uploaded['orders_file'] ?? null,
            $uploaded['mediaplan_file'] ?? null,
            $uploaded['acts_file'] ?? null
        ]);
        $checkId = $pdo->lastInsertId();

        // Перенаправляем на дашборд с сообщением об успехе
        $_SESSION['flash'] = "Проверка #$checkId успешно создана! Файлы загружены.";
        redirect('index.php');
    } catch (PDOException $e) {
        $errors[] = "Ошибка БД: " . $e->getMessage();
        // Удаляем загруженные файлы при ошибке (можно реализовать)
    }
}

// Если были ошибки, показываем их и возвращаем на форму
if (!empty($errors)) {
    $_SESSION['flash_error'] = implode('<br>', $errors);
    redirect('index.php#new-tab'); // переходим на вкладку новой проверки
}
?>