<?php 
// Если есть старая сессия - очищаем её
if (isset($_SESSION['user_id'])) {
    session_destroy();
    session_start();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход - ORD Check-Acts</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/ORD-Check-acts/assets/css/style.css">
</head>
<body>
    <div class="auth-page">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-logo">
                    <div class="logo-icon">
                        <i class="fas fa-check-double"></i>
                    </div>
                    <h1>ORD Check-Acts</h1>
                    <p>Система проверки актов ОРД</p>
                </div>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <span class="alert-icon"><i class="fas fa-exclamation-circle"></i></span>
                        <?= htmlspecialchars($_SESSION['error']) ?>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['logout_success'])): ?>
                    <div class="alert alert-success">
                        <span class="alert-icon"><i class="fas fa-check-circle"></i></span>
                        <?= htmlspecialchars($_SESSION['logout_success']) ?>
                    </div>
                    <?php unset($_SESSION['logout_success']); ?>
                <?php endif; ?>
                
                <form class="auth-form" method="POST" action="/ORD-Check-acts/login">
                    <input type="hidden" name="action" value="login">
                    
                    <div class="form-group">
                        <label for="username">Логин</label>
                        <div class="input-group">
                            <span class="input-icon"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="username" name="username" 
                                   placeholder="Введите логин" value="admin" required autofocus>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Пароль</label>
                        <div class="input-group">
                            <span class="input-icon"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="Введите пароль" value="12345" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Войти в систему
                    </button>
                </form>
                
                <div class="auth-footer">
                    <p class="text-muted">
                        <i class="fas fa-info-circle"></i> 
                        Для входа используйте:<br>
                        <strong>Логин:</strong> admin &bull; <strong>Пароль:</strong> 12345
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>