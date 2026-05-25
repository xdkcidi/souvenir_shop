<?php

session_start();
require_once __DIR__ . '/db.php';

$loginOrEmail = trim($_POST['login'] ?? '');
$password     = $_POST['password'] ?? '';

$back = $_SERVER['HTTP_REFERER'] ?? '../index.php';

if ($loginOrEmail === '' || $password === '') {
    $_SESSION['auth_error'] = 'Заполните логин (или email) и пароль.';
    header('Location: ' . $back);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, login, email, password_hash, role, is_email_verified
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

    if (empty($user['is_email_verified'])) {
        $_SESSION['auth_error'] = 'Сначала подтвердите email по ссылке из письма.';
        header('Location: ../pages/registration.php');
        exit;
    }

    $_SESSION['user_id']    = (int)$user['id'];
    $_SESSION['user_login'] = $user['login'];
    $_SESSION['user_role']  = (int)$user['role'];

    setcookie('id', (string)$user['id'], time() + 3600, '/souvenir-shop/');
    setcookie('role', (string)$user['role'], time() + 3600, '/souvenir-shop/');

    header('Location: ../pages/account.php');
    exit;

} catch (PDOException $e) {
    $_SESSION['auth_error'] = 'Ошибка при входе. Попробуйте ещё раз позже.';
    header('Location: ' . $back);
    exit;
}
