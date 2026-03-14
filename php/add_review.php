<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';

$product_code = $_POST['product_code'] ?? '';
$rating       = (int)($_POST['rating'] ?? 0);
$body         = trim($_POST['body'] ?? ''); // можно пусто

if (empty($_SESSION['user_id'])) {
  $_SESSION['review_error'] = 'Чтобы оставить отзыв, нужно войти в аккаунт.';
  header('Location: ../pages/product.php?id=' . urlencode($product_code) . '#reviews');
  exit;
}
$user_id = (int)$_SESSION['user_id'];

if ($product_code === '') {
  http_response_code(400);
  exit('Не передан товар.');
}
if ($rating < 1 || $rating > 5) {
  $_SESSION['review_error'] = 'Некорректная оценка.';
  header('Location: ../pages/product.php?id=' . urlencode($product_code) . '#reviews');
  exit;
}

// 1 отзыв на товар
$chk = $pdo->prepare("SELECT 1 FROM product_reviews WHERE product_code = ? AND user_id = ? LIMIT 1");
$chk->execute([$product_code, $user_id]);
if ($chk->fetchColumn()) {
  $_SESSION['review_error'] = 'Вы уже оставляли отзыв на этот товар.';
  header('Location: ../pages/product.php?id=' . urlencode($product_code) . '#reviews');
  exit;
}

// логин из users.login
$u = $pdo->prepare("SELECT login FROM users WHERE id = ? LIMIT 1");
$u->execute([$user_id]);
$author_name = (string)($u->fetchColumn() ?: 'Пользователь');
$author_name = mb_substr($author_name, 0, 80);

// комментарий необязателен
$body = ($body === '') ? null : mb_substr($body, 0, 2000);

try {
  $stmt = $pdo->prepare("
    INSERT INTO product_reviews (product_code, user_id, author_name, rating, body, is_approved)
    VALUES (?, ?, ?, ?, ?, 1)
  ");
  $stmt->execute([$product_code, $user_id, $author_name, $rating, $body]);
  $_SESSION['review_success'] = 'Спасибо! Отзыв добавлен.';
} catch (PDOException $e) {
  $_SESSION['review_error'] = 'Вы уже оставляли отзыв на этот товар.';
}

header('Location: ../pages/product.php?id=' . urlencode($product_code) . '#reviews');
exit;