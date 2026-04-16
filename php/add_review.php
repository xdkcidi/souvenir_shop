<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';

$product_code = trim($_POST['product_code'] ?? '');
$rating       = (int)($_POST['rating'] ?? 0);
$body         = trim($_POST['body'] ?? '');

function redirectToProduct(string $productCode): void
{
    header('Location: /souvenir_shop/pages/product.php?id=' . urlencode($productCode) . '#reviews');
    exit;
}

if (empty($_SESSION['user_id'])) {
    $_SESSION['review_error'] = 'Чтобы оставить отзыв, нужно войти в аккаунт.';
    redirectToProduct($product_code);
}

$user_id = (int)$_SESSION['user_id'];

if ($product_code === '') {
    http_response_code(400);
    exit('Не передан товар.');
}

if ($rating < 1 || $rating > 5) {
    $_SESSION['review_error'] = 'Некорректная оценка.';
    redirectToProduct($product_code);
}

$body = ($body === '') ? null : mb_substr($body, 0, 2000);

$u = $pdo->prepare("SELECT login FROM users WHERE id = ? LIMIT 1");
$u->execute([$user_id]);
$author_name = (string)($u->fetchColumn() ?: 'Пользователь');
$author_name = mb_substr($author_name, 0, 80);

function userBoughtProduct(PDO $pdo, int $userId, string $productCode): bool
{
    $sql = "
        SELECT 1
        FROM orders o
        INNER JOIN order_items oi ON oi.order_id = o.id
        WHERE o.user_id = ?
          AND oi.product_code = ?
          AND o.status = 'completed'
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId, $productCode]);
    return (bool)$stmt->fetchColumn();
}

if (!userBoughtProduct($pdo, $user_id, $product_code)) {
    $_SESSION['review_error'] = 'Оставить отзыв можно только после завершённой покупки товара.';
    redirectToProduct($product_code);
}

$chk = $pdo->prepare("
    SELECT id
    FROM product_reviews
    WHERE product_code = ? AND user_id = ?
    LIMIT 1
");
$chk->execute([$product_code, $user_id]);
$existingId = (int)($chk->fetchColumn() ?: 0);

try {
    if ($existingId > 0) {
        $stmt = $pdo->prepare("
            UPDATE product_reviews
            SET author_name = ?,
                rating = ?,
                body = ?,
                is_approved = 0,
                updated_at = NOW(),
                moderated_at = NULL
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->execute([$author_name, $rating, $body, $existingId]);

        $_SESSION['review_success'] = 'Отзыв обновлён и отправлен на модерацию.';
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO product_reviews
                (product_code, user_id, author_name, rating, body, created_at, updated_at, moderated_at, is_approved)
            VALUES
                (?, ?, ?, ?, ?, NOW(), NULL, NULL, 0)
        ");
        $stmt->execute([$product_code, $user_id, $author_name, $rating, $body]);

        $_SESSION['review_success'] = 'Отзыв отправлен на модерацию.';
    }
} catch (PDOException $e) {
    $_SESSION['review_error'] = 'Не удалось сохранить отзыв.';
}

redirectToProduct($product_code);