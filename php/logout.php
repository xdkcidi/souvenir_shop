<?php
// php/logout.php
session_start();

// Очищаем все куки
if (isset($_SERVER['HTTP_COOKIE'])) {
    $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
    foreach($cookies as $cookie) {
        $parts = explode('=', $cookie);
        $name = trim($parts[0]);
        setcookie($name, '', time() - 3600, '/');
        setcookie($name, '', time() - 3600);
    }
}

// Уничтожаем сессию
$_SESSION = array();

// Если используется session_name()
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Редирект на главную
header('Location: ../index.php');
exit;
?>