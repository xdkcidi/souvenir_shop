<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

function redirectBack(): void
{
    header('Location: ../pages/catalog.php#personalGift');
    exit;
}

$customerName     = trim($_POST['customer_name'] ?? '');
$phone            = trim($_POST['phone'] ?? '');
$email            = trim($_POST['email'] ?? '');
$preferredContact = trim($_POST['preferred_contact'] ?? '');
$itemType         = trim($_POST['item_type'] ?? '');
$engravingText    = trim($_POST['engraving_text'] ?? '');
$urgency          = trim($_POST['urgency'] ?? '');
$targetDate       = trim($_POST['target_date'] ?? '');
$comment          = trim($_POST['comment'] ?? '');
$privacyConsent   = !empty($_POST['privacy_consent']);

$userId = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

if ($customerName === '' || mb_strlen($customerName) < 2) {
    $_SESSION['personalization_error'] = 'Введите имя.';
    redirectBack();
}

if ($phone === '') {
    $_SESSION['personalization_error'] = 'Введите телефон.';
    redirectBack();
}

if ($itemType === '') {
    $_SESSION['personalization_error'] = 'Выберите изделие.';
    redirectBack();
}

if ($engravingText === '' || mb_strlen($engravingText) < 2) {
    $_SESSION['personalization_error'] = 'Введите текст для гравировки.';
    redirectBack();
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['personalization_error'] = 'Введите корректный email.';
    redirectBack();
}

if ($targetDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $targetDate)) {
    $_SESSION['personalization_error'] = 'Некорректная дата.';
    redirectBack();
}

if (!$privacyConsent) {
    $_SESSION['personalization_error'] = 'Необходимо согласиться на обработку персональных данных.';
    redirectBack();
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO personalization_requests
        (
            user_id,
            customer_name,
            phone,
            email,
            preferred_contact,
            item_type,
            engraving_text,
            urgency,
            target_date,
            comment,
            status
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'new')
    ");

    $stmt->execute([
        $userId,
        mb_substr($customerName, 0, 100),
        mb_substr($phone, 0, 30),
        $email !== '' ? mb_substr($email, 0, 120) : null,
        $preferredContact !== '' ? mb_substr($preferredContact, 0, 30) : null,
        mb_substr($itemType, 0, 100),
        mb_substr($engravingText, 0, 100),
        $urgency !== '' ? mb_substr($urgency, 0, 50) : null,
        $targetDate !== '' ? $targetDate : null,
        $comment !== '' ? mb_substr($comment, 0, 1000) : null,
    ]);

    $_SESSION['personalization_success'] = 'Заявка отправлена. Мы свяжемся с вами для уточнения деталей.';
} catch (PDOException $e) {
    $_SESSION['personalization_error'] = 'Не удалось отправить заявку. Попробуйте ещё раз.';
}

redirectBack();
