<?php
session_start();
require_once __DIR__ . '/../php/admin_guard.php';
require_once __DIR__ . '/../php/db.php';

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$stmt = $pdo->query("
    SELECT
        pr.id,
        pr.product_code,
        pr.user_id,
        pr.author_name,
        pr.rating,
        pr.body,
        pr.created_at,
        pr.is_approved,
        p.name AS product_name
    FROM product_reviews pr
    LEFT JOIN products p ON p.product_code = pr.product_code
    ORDER BY pr.created_at DESC, pr.id DESC
");
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Отзывы — Админка</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
<main class="admin-wrap admin-wrap--md">
  <div class="admin-head">
    <div>
<h1 class="admin-head__title">Отзывы</h1>
<p class="admin-head__text">Просмотр и удаление комментариев пользователей.</p>
    </div>
    <a class="btn btn--outline" href="/souvenir_shop/pages/admin.php">Назад</a>
  </div>

  <?php if (!empty($_SESSION['admin_success'])): ?>
    <div style="margin-bottom:16px; padding:12px 14px; border-radius:14px; background:#eef8f0; color:#1f7a43;">
      <?= h($_SESSION['admin_success']) ?>
    </div>
    <?php unset($_SESSION['admin_success']); ?>
  <?php endif; ?>

  <?php if (!empty($_SESSION['admin_error'])): ?>
    <div style="margin-bottom:16px; padding:12px 14px; border-radius:14px; background:#fff0f0; color:#b00020;">
      <?= h($_SESSION['admin_error']) ?>
    </div>
    <?php unset($_SESSION['admin_error']); ?>
  <?php endif; ?>

  <?php if (!$reviews): ?>
    <div class="empty">Отзывов пока нет.</div>
  <?php else: ?>
    <section class="reviews-list">
      <?php foreach ($reviews as $review): ?>
        <?php
          $rating = max(1, min(5, (int)$review['rating']));
          $stars = str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
        ?>
        <article class="review-card">
          <div class="review-top">
            <div>
              <h2 class="review-title">
                <?= h($review['product_name'] ?: 'Товар не найден') ?>
              </h2>

              <p class="review-meta">Код товара: <?= h($review['product_code']) ?: '—' ?></p>
              <p class="review-meta">Автор: <?= h($review['author_name']) ?: '—' ?></p>
              <p class="review-meta">ID пользователя: <?= !empty($review['user_id']) ? (int)$review['user_id'] : '—' ?></p>
              <p class="review-meta">Дата: <?= h($review['created_at']) ?: '—' ?></p>
              <p class="review-meta">
                Оценка:
                <span class="stars"><?= $stars ?></span>
                (<?= $rating ?>/5)
              </p>
            </div>

            <div class="review-side">
              <span class="pill <?= ((int)$review['is_approved'] === 1 ? 'pill--ok' : 'pill--off') ?>">
                <?= ((int)$review['is_approved'] === 1 ? 'Опубликован' : 'Скрыт') ?>
              </span>

              <form class="inline" action="/souvenir_shop/php/admin_review_delete.php" method="post" onsubmit="return confirm('Удалить отзыв?');">
                <input type="hidden" name="id" value="<?= (int)$review['id'] ?>">
                <button class="btn btn--sm" type="submit">Удалить</button>
              </form>
            </div>
          </div>

          <div class="review-text">
            <?= nl2br(h($review['body'])) ?>
          </div>
        </article>
      <?php endforeach; ?>
    </section>
  <?php endif; ?>
</main>
</body>
</html>