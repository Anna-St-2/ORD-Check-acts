<?php
class Auth {
    private $pdo;
    
    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }
    
    public function login($username, $password) {
        $hashed_password = md5($password);
        
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
        $stmt->execute([$username, $hashed_password]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Очищаем старую сессию
            $_SESSION = array();
            
            // Устанавливаем новые данные
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['login_time'] = time();
            
            return true;
        }
        return false;
    }
    
    public function check() {
        return isset($_SESSION['user_id']) && isset($_SESSION['username']);
    }
    
    public function logout() {
        // Очищаем все данные сессии
        $_SESSION = array();
        
        // Удаляем куки сессии
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Уничтожаем сессию
        session_destroy();
        
        return true;
    }
    
    public function getUser() {
        if ($this->check()) {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            return $stmt->fetch();
        }
        return null;
    }
}
?>