<?php
if (!isset($_SESSION['user_id'])) {
    header('Location: /ORD-Check-acts/login');
    exit;
}

// Используем класс Database, который уже загружен в index.php
// $db = new Database();
// $pdo = $db->getConnection(); // Если метод называется иначе (например, connect() или getPdo()), замените на свой

$pdo = Database::getInstance()->getConnection();
// Обработка сохранения
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_impressions'])) {
    $stmt = $pdo->prepare("INSERT INTO fact_impressions (act_number, impressions) VALUES (:act, :imp) ON DUPLICATE KEY UPDATE impressions = :imp");
    foreach ($_POST['impressions'] as $act => $val) {
        $clean = (int)preg_replace('/\D+/', '', $val);
        $stmt->execute([':act' => $act, ':imp' => $clean]);
    }
    $success = "Данные успешно обновлены!";
}

// Получаем данные
$impressions = $pdo->query("SELECT * FROM fact_impressions ORDER BY act_number DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Фактические показы - ORD Check-Acts</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/ORD-Check-acts/assets/css/style.css">
</head>
<body>
    <div class="dashboard-wrapper">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <div class="brand-icon"><i class="fas fa-check-double"></i></div>
                <div class="brand-text">ORD<span>Check</span></div>
            </div>
            <ul class="sidebar-nav">
                <li><a href="/ORD-Check-acts/dashboard"><span class="nav-icon"><i class="fas fa-th-large"></i></span> Дашборд</a></li>
                <li><a href="/ORD-Check-acts/impressions" class="active"><span class="nav-icon"><i class="fas fa-chart-bar"></i></span> Факт. показы</a></li>
                <li><a href="/ORD-Check-acts/login?action=logout" class="logout"><span class="nav-icon"><i class="fas fa-sign-out-alt"></i></span> Выйти</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <header class="main-header">
                <h1><i class="fas fa-chart-bar" style="color:var(--primary-color);"></i> Фактические показы</h1>
                <div class="user-info">
                    <div class="user-avatar"><?= strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)) ?></div>
                    <span class="user-name"><?= htmlspecialchars($_SESSION['username'] ?? 'Пользователь') ?></span>
                </div>
            </header>

            <div class="main-body">
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h5>База фактических показов</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Номер акта</th>
                                        <th>Показы по факту</th>
                                        <th>Последнее изменение</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($impressions)): ?>
                                        <tr><td colspan="3" class="text-center text-muted">Нет сохраненных данных</td></tr>
                                    <?php endif; ?>
                                    <?php foreach ($impressions as $row): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($row['act_number']) ?></strong></td>
                                            <td>
                                                <input type="text" name="impressions[<?= htmlspecialchars($row['act_number']) ?>]" 
                                                       value="<?= htmlspecialchars($row['impressions']) ?>" 
                                                       class="form-control" style="max-width:200px;">
                                            </td>
                                            <td class="text-muted"><?= date('d.m.Y H:i', strtotime($row['updated_at'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            
                            <?php if (!empty($impressions)): ?>
                                <button type="submit" name="save_impressions" class="btn-primary mt-3">
                                    <i class="fas fa-save"></i> Сохранить изменения
                                </button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>