<?php
// php/auth.php
session_start();
require_once __DIR__ . '/db.php'; // здесь должен появиться $pdo

// Берём данные из формы
$loginOrEmail = trim($_POST['login'] ?? '');
$password     = $_POST['password'] ?? '';

// 1. Проверка на заполненность
if ($loginOrEmail === '' || $password === '') {
    $_SESSION['auth_error'] = 'Заполните логин (или email) и пароль.';
    header('Location: /index.php'); // обратно на главную, где модалка
    exit;
}

try {
    // 2. Ищем пользователя по логину или email
    $stmt = $pdo->prepare("
        SELECT id, login, email, password_hash, role
        FROM users
        WHERE login = :loginOrEmail
           OR email = :loginOrEmail
        LIMIT 1
    ");
    $stmt->execute([':loginOrEmail' => $loginOrEmail]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // нет такого пользователя
    if (!$user || !password_verify($password, $user['password_hash'])) {
        $_SESSION['auth_error'] = 'Неверный логин/email или пароль.';
        header('Location: /index.php');
        exit;
    }

    // 3. Всё ок — ставим куки и сессию
    setcookie('id',   $user['id'],           time() + 3600, '/');
    setcookie('role', $user['role'] ?? 'user', time() + 3600, '/');

    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_login'] = $user['login'];

    // 4. Редирект в личный кабинет
    header('Location: ../pages/account.php');
    exit;

} catch (PDOException $e) {
    // на проде лучше логировать в файл, а не показывать
    $_SESSION['auth_error'] = 'Ошибка при входе. Попробуйте ещё раз позже.';
    header('Location: /index.php');
    exit;
}
