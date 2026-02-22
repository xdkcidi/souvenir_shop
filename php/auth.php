<?php
session_start();
require_once __DIR__ . '/db.php';

$loginOrEmail = trim($_POST['login'] ?? '');
$password     = $_POST['password'] ?? '';

$back = $_SERVER['HTTP_REFERER'] ?? '/souvenir_shop/index.php';

if ($loginOrEmail === '' || $password === '') {
    $_SESSION['auth_error'] = 'Заполните логин (или email) и пароль.';
    header('Location: ' . $back);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, login, email, password_hash
        FROM users
        WHERE login = :v
           OR email = :v
        LIMIT 1
    ");
    $stmt->execute([':v' => $loginOrEmail]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        $_SESSION['auth_error'] = 'Неверный логин/email или пароль.';
        header('Location: ' . $back);
        exit;
    }

    $_SESSION['user_id']    = (int)$user['id'];
    $_SESSION['user_login'] = $user['login'];

    setcookie('id', (string)$user['id'], time() + 3600, '/souvenir_shop/');

    header('Location: /souvenir_shop/pages/account.php');
    exit;

} catch (PDOException $e) {
    $_SESSION['auth_error'] = 'Ошибка при входе. Попробуйте ещё раз позже.';
    header('Location: ' . $back);
    exit;
}
