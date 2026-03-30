<?php
session_start();
require_once __DIR__ . '/admin_guard.php';
require_once __DIR__ . '/db.php';

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    $_SESSION['admin_error'] = 'Некорректный ID отзыва.';
    header('Location: /souvenir_shop/pages/admin_reviews.php');
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM product_reviews WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);

    $_SESSION['admin_success'] = 'Отзыв удалён.';
    header('Location: /souvenir_shop/pages/admin_reviews.php');
    exit;
} catch (PDOException $e) {
    $_SESSION['admin_error'] = 'Не удалось удалить отзыв.';
    header('Location: /souvenir_shop/pages/admin_reviews.php');
    exit;
}