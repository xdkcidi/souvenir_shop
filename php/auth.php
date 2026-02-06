<?php
// php/auth.php
session_start();
require_once __DIR__ . '/db.php';

// Берём данные из формы
$loginOrEmail = trim($_POST['login'] ?? '');
$password     = $_POST['password'] ?? '';

$back = $_SERVER['HTTP_REFERER'] ?? '/souvenir_shop/index.php';

// 1) Проверка на заполненность
if ($loginOrEmail === '' || $password === '') {
    $_SESSION['auth_error'] = 'Заполните логин (или email) и пароль.';
    header('Location: ' . $back);
    exit;
}

try {
    // 2) Ищем пользователя по логину или email (role УБРАЛИ — его нет в таблице)
    $stmt = $pdo->prepare("
        SELECT id, login, email, password_hash
        FROM users
        WHERE login = :v
           OR email = :v
        LIMIT 1
    ");
    $stmt->execute([':v' => $loginOrEmail]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 3) Нет пользователя или пароль не совпал
    if (!$user || !password_verify($password, $user['password_hash'])) {
        $_SESSION['auth_error'] = 'Неверный логин/email или пароль.';
        header('Location: ' . $back);
        exit;
    }

    // 4) Всё ок — ставим сессию
    $_SESSION['user_id']    = (int)$user['id'];
    $_SESSION['user_login'] = $user['login'];

    // (необязательно) кука id — можно оставить
    setcookie('id', (string)$user['id'], time() + 3600, '/souvenir_shop/');

    // 5) Редирект в личный кабинет
    header('Location: /souvenir_shop/pages/account.php');
    exit;

} catch (PDOException $e) {
    $_SESSION['auth_error'] = 'Ошибка при входе. Попробуйте ещё раз позже.';
    header('Location: ' . $back);
    exit;
}
