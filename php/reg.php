<?php
session_start();
// регистрация
$name    = trim(filter_var($_POST['name']   ?? '', FILTER_SANITIZE_STRING));
$surname = trim(filter_var($_POST['surname']?? '', FILTER_SANITIZE_STRING));
$email   = trim(filter_var($_POST['email']  ?? '', FILTER_SANITIZE_EMAIL));
$login   = trim(filter_var($_POST['login']  ?? '', FILTER_SANITIZE_STRING));
$pass    = trim($_POST['pass'] ?? '');

// Проверка заполненности всех полей
if ($name === '' || $surname === '' || $email === '' || $login === '' || $pass === '') {
    $_SESSION['reg_error'] = 'Заполните все поля формы регистрации.';
    header('Location: ../pages/registration.php');
    exit;
}

include 'db.php';

// Проверим, есть ли пользователь с таким логином
$check = $mysql->prepare("SELECT id FROM users WHERE login = ?");
$check->bind_param('s', $login);
$check->execute();
$res = $check->get_result();
if ($res->num_rows > 0) {
    $mysql->close();
    $_SESSION['reg_error'] = 'Такой логин уже существует.';
    header('Location: ../pages/registration.php');
    exit;
}
$check->close();

// хэшируем пароль
$passHash = password_hash($pass, PASSWORD_DEFAULT);

// роль 2 — обычный пользователь
$role = 2;

// Сохраняем нового пользователя в базе
$stmt = $mysql->prepare("INSERT INTO users (name, surname, email, login, password_hash, role) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param('sssssi', $name, $surname, $email, $login, $passHash, $role);
$stmt->execute();
$stmt->close();

// после регистрации сразу авторизуем
$userId = $mysql->insert_id;
setcookie('id', $userId, time() + 3600, '/');
setcookie('role', $role, time() + 3600, '/');
$_SESSION['user_id'] = $userId;
$_SESSION['user_login'] = $login;
$mysql->close();
// Перенаправляем в личный кабинет
header('Location: /pages/account.php');
exit;
?>
