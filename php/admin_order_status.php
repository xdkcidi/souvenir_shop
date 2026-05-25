<?php

session_start();
require_once __DIR__ . '/admin_guard.php';
require_once __DIR__ . '/db.php';

$id = (int)($_POST['id'] ?? 0);
$status = trim($_POST['status'] ?? '');

$allowedStatuses = ['new', 'processing', 'shipped', 'completed', 'cancelled'];

if ($id <= 0) {
    $_SESSION['admin_error'] = 'Некорректный ID заказа.';
    header('Location: ../pages/admin_orders.php');
    exit;
}

if (!in_array($status, $allowedStatuses, true)) {
    $_SESSION['admin_error'] = 'Некорректный статус заказа.';
    header('Location: ../pages/admin_orders.php');
    exit;
}

try {
    $check = $pdo->prepare("SELECT id FROM orders WHERE id = ? LIMIT 1");
    $check->execute([$id]);

    if (!$check->fetch()) {
        $_SESSION['admin_error'] = 'Заказ не найден.';
        header('Location: ../pages/admin_orders.php');
        exit;
    }

    $stmt = $pdo->prepare("UPDATE orders SET status = :status WHERE id = :id LIMIT 1");
    $stmt->execute([
        ':status' => $status,
        ':id' => $id,
    ]);

    $_SESSION['admin_success'] = 'Статус заказа обновлён.';
    header('Location: ../pages/admin_orders.php');
    exit;

} catch (PDOException $e) {
    $_SESSION['admin_error'] = 'Не удалось обновить статус заказа.';
    header('Location: ../pages/admin_orders.php');
    exit;
}
