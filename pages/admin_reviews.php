<?php
session_start();
require_once __DIR__ . '/../php/admin_guard.php';
require_once __DIR__ . '/../php/db.php';

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/* ===== ОБРАБОТКА ДЕЙСТВИЙ ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $action = trim((string)($_POST['action'] ?? ''));

    if ($id <= 0) {
        $_SESSION['admin_error'] = 'Некорректный ID отзыва.';
        header('Location: /souvenir_shop/pages/admin_reviews.php');
        exit;
    }

    try {
        if ($action === 'approve') {
            $stmt = $pdo->prepare("
                UPDATE product_reviews
                SET is_approved = 1,
                    moderated_at = NOW()
                WHERE id = ?
                LIMIT 1
            ");
            $stmt->execute([$id]);

            $_SESSION['admin_success'] = 'Отзыв опубликован.';
        } elseif ($action === 'hide') {
            $stmt = $pdo->prepare("
                UPDATE product_reviews
                SET is_approved = 0,
                    moderated_at = NOW()
                WHERE id = ?
                LIMIT 1
            ");
            $stmt->execute([$id]);

            $_SESSION['admin_success'] = 'Отзыв скрыт.';
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("
                DELETE FROM product_reviews
                WHERE id = ?
                LIMIT 1
            ");
            $stmt->execute([$id]);

            $_SESSION['admin_success'] = 'Отзыв удалён.';
        } else {
            $_SESSION['admin_error'] = 'Неизвестное действие.';
        }
    } catch (PDOException $e) {
        $_SESSION['admin_error'] = 'Не удалось выполнить действие с отзывом.';
    }

    header('Location: /souvenir_shop/pages/admin_reviews.php');
    exit;
}

/* ===== СПИСОК ОТЗЫВОВ ===== */
$stmt = $pdo->query("
    SELECT
        pr.id,
        pr.product_code,
        pr.user_id,
        pr.author_name,
        pr.rating,
        pr.body,
        pr.created_at,
        pr.updated_at,
        pr.moderated_at,
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
      <p class="admin-head__text">Просмотр, публикация, скрытие и удаление отзывов пользователей.</p>
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
          $isApproved = ((int)$review['is_approved'] === 1);
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
              <p class="review-meta">Дата создания: <?= h($review['created_at']) ?: '—' ?></p>

              <?php if (!empty($review['updated_at'])): ?>
                <p class="review-meta">Изменён: <?= h($review['updated_at']) ?></p>
              <?php endif; ?>

              <?php if (!empty($review['moderated_at'])): ?>
                <p class="review-meta">Модерация: <?= h($review['moderated_at']) ?></p>
              <?php endif; ?>

              <p class="review-meta">
                Оценка:
                <span class="stars"><?= $stars ?></span>
                (<?= $rating ?>/5)
              </p>
            </div>

            <div class="review-side">
              <span class="pill <?= $isApproved ? 'pill--ok' : 'pill--off' ?>">
                <?= $isApproved ? 'Опубликован' : 'На модерации / скрыт' ?>
              </span>

              <?php if (!$isApproved): ?>
                <form class="inline" action="/souvenir_shop/pages/admin_reviews.php" method="post">
                  <input type="hidden" name="id" value="<?= (int)$review['id'] ?>">
                  <input type="hidden" name="action" value="approve">
                  <button class="btn btn--sm" type="submit">Одобрить</button>
                </form>
              <?php endif; ?>

              <?php if ($isApproved): ?>
                <form class="inline" action="/souvenir_shop/pages/admin_reviews.php" method="post">
                  <input type="hidden" name="id" value="<?= (int)$review['id'] ?>">
                  <input type="hidden" name="action" value="hide">
                  <button class="btn btn--sm" type="submit">Скрыть</button>
                </form>
              <?php endif; ?>

              <form class="inline" action="/souvenir_shop/pages/admin_reviews.php" method="post" onsubmit="return confirm('Удалить отзыв?');">
                <input type="hidden" name="id" value="<?= (int)$review['id'] ?>">
                <input type="hidden" name="action" value="delete">
                <button class="btn btn--sm" type="submit">Удалить</button>
              </form>
            </div>
          </div>

          <div class="review-text">
            <?php if (!empty($review['body'])): ?>
              <?= nl2br(h($review['body'])) ?>
            <?php else: ?>
              <span class="empty">Без комментария</span>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </section>
  <?php endif; ?>
</main>
</body>
</html>