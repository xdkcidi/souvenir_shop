<?php
// php/reg.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mail_helper.php';

// Проверяем, что запрос пришёл методом POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/registration.php');
    exit;
}

$errors = [];

// Получаем данные
$login            = trim($_POST['login'] ?? '');
$email            = trim($_POST['email'] ?? '');
$phone            = trim($_POST['phone'] ?? '');
$address          = trim($_POST['delivery_address'] ?? '');
$password         = $_POST['password'] ?? '';
$password_confirm = $_POST['password_confirm'] ?? '';
$privacyConsent   = !empty($_POST['privacy_consent']);

// Валидация логина
if ($login === '') {
    $errors[] = 'Введите логин.';
} elseif (mb_strlen($login) < 3) {
    $errors[] = 'Логин должен содержать минимум 3 символа.';
} elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $login)) {
    $errors[] = 'Логин может содержать только латинские буквы, цифры, дефис и подчёркивание.';
}

// Валидация email
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Введите корректный email.';
}

// Валидация пароля
if ($password === '') {
    $errors[] = 'Введите пароль.';
} else {
    $passwordErrors = [];
    
    if (mb_strlen($password) < 8) {
        $passwordErrors[] = 'минимум 8 символов';
    }
    if (!preg_match('/[A-ZА-ЯЁ]/u', $password)) {
        $passwordErrors[] = 'хотя бы одну заглавную букву';
    }
    if (!preg_match('/[a-zа-яё]/u', $password)) {
        $passwordErrors[] = 'хотя бы одну строчную букву';
    }
    if (!preg_match('/[0-9]/', $password)) {
        $passwordErrors[] = 'хотя бы одну цифру';
    }
    
    if (!empty($passwordErrors)) {
        $errors[] = 'Пароль должен содержать: ' . implode(', ', $passwordErrors) . '.';
    }
}

// Проверка совпадения паролей
if ($password !== $password_confirm) {
    $errors[] = 'Пароли не совпадают.';
}

// Проверка согласия на обработку данных
if (!$privacyConsent) {
    $errors[] = 'Необходимо согласиться на обработку персональных данных.';
}

// Проверка уникальности логина и email
if (empty($errors)) {
    $stmt = $pdo->prepare("
        SELECT id, login, email
        FROM users
        WHERE login = :login OR email = :email
        LIMIT 1
    ");
    $stmt->execute([
        ':login' => $login,
        ':email' => $email,
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        if (mb_strtolower((string)$row['login']) === mb_strtolower($login)) {
            $errors[] = 'Пользователь с таким логином уже существует.';
        }
        if (mb_strtolower((string)$row['email']) === mb_strtolower($email)) {
            $errors[] = 'Пользователь с таким email уже существует.';
        }
    }
}

// Валидация телефона 
if ($phone !== '') {
    $cleanPhone = preg_replace('/\D/', '', $phone);
    
    if (strlen($cleanPhone) !== 11 || $cleanPhone[0] !== '7') {
        $errors[] = 'Введите корректный номер телефона в формате +7 (___) ___-__-__';
    } else {
        $phone = '+7 (' . substr($cleanPhone, 1, 3) . ') ' . 
                 substr($cleanPhone, 4, 3) . '-' . 
                 substr($cleanPhone, 7, 2) . '-' . 
                 substr($cleanPhone, 9, 2);
    }
}

// Если есть ошибки — на страницу регистрации
if (!empty($errors)) {
    $_SESSION['reg_errors'] = $errors;
    $_SESSION['reg_form_data'] = [
        'login' => $login,
        'email' => $email,
        'phone' => $phone,
        'address' => $address,
    ];
    header('Location: ../pages/registration.php');
    exit;
}

// Регистрация пользователя
$hash = password_hash($password, PASSWORD_DEFAULT);
$token = bin2hex(random_bytes(32));
$tokenHash = hash('sha256', $token);
$expiresAt = (new DateTime('+24 hours'))->format('Y-m-d H:i:s');

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO users (
            login,
            email,
            password_hash,
            phone,
            delivery_address,
            is_email_verified,
            email_verify_token,
            email_verify_expires_at
        )
        VALUES (
            :login,
            :email,
            :password_hash,
            :phone,
            :address,
            0,
            :token,
            :expires_at
        )
    ");

    $ok = $stmt->execute([
        ':login'         => $login,
        ':email'         => $email,
        ':password_hash' => $hash,
        ':phone'         => ($phone !== '' ? $phone : null),
        ':address'       => ($address !== '' ? $address : null),
        ':token'         => $tokenHash,
        ':expires_at'    => $expiresAt,
    ]);

    if (!$ok) {
        throw new RuntimeException('Не удалось сохранить пользователя.');
    }

    $mailSent = sendVerificationEmail($email, $login, $token);
    $pdo->commit();

    if ($mailSent) {
        $_SESSION['registration_success'] = 'Регистрация почти завершена. Мы отправили письмо на ваш email. Перейдите по ссылке из письма, чтобы подтвердить аккаунт.';
    } else {
        $_SESSION['registration_success'] = 'Регистрация почти завершена. На локальном сервере письмо не отправилось, поэтому ссылка для подтверждения сохранена в файле storage/mail_debug.log.';
    }

    header('Location: ../pages/registration.php');
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['reg_errors'] = ['Не удалось завершить регистрацию. Попробуйте ещё раз.'];
    header('Location: ../pages/registration.php');
    exit;
}