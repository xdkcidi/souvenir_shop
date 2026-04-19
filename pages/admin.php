<?php
session_start();
require_once __DIR__ . '/../php/admin_guard.php';
require_once __DIR__ . '/../php/db.php';

$stmt = $pdo->query("
    SELECT
        (SELECT COUNT(*) FROM products) AS products_count,
        (SELECT COUNT(*) FROM orders) AS orders_count,
        (SELECT COUNT(*) FROM product_reviews) AS reviews_count,
        (SELECT COUNT(*) FROM orders WHERE status = 'new') AS new_orders_count,
        (SELECT COUNT(*) FROM personalization_requests) AS personalization_count,
        (SELECT COUNT(*) FROM personalization_requests WHERE status = 'new') AS new_personalization_count
");
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Админ-панель — Лавка</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/admin.css">
</head>
<body>

<main class="admin-wrap admin-wrap--md">
  <div class="admin-head">
    <div>
      <h1 class="admin-head__title">Админ-панель</h1>
      <p class="admin-head__text">
        Вы вошли как администратор: <?= htmlspecialchars($_SESSION['user_login']) ?>
      </p>
    </div>

    <div style="display:flex; gap:10px; flex-wrap:wrap;">
      <a class="btn btn--outline" href="/souvenir_shop/pages/account.php">Личный кабинет</a>
      <a class="btn btn--dark" href="/souvenir_shop/index.php">На сайт</a>
    </div>
  </div>

  <section class="admin-grid">
    <div class="admin-card">
      <div class="admin-card__label">Товаров</div>
      <div class="admin-card__value"><?= (int)$stats['products_count'] ?></div>
    </div>

    <div class="admin-card">
      <div class="admin-card__label">Всего заказов</div>
      <div class="admin-card__value"><?= (int)$stats['orders_count'] ?></div>
    </div>

    <div class="admin-card">
      <div class="admin-card__label">Отзывов</div>
      <div class="admin-card__value"><?= (int)$stats['reviews_count'] ?></div>
    </div>

    <div class="admin-card">
      <div class="admin-card__label">Новых заказов</div>
      <div class="admin-card__value"><?= (int)$stats['new_orders_count'] ?></div>
    </div>

    <div class="admin-card">
      <div class="admin-card__label">Заявок на персонализацию</div>
      <div class="admin-card__value"><?= (int)$stats['personalization_count'] ?></div>
    </div>

    <div class="admin-card">
      <div class="admin-card__label">Новых заявок</div>
      <div class="admin-card__value"><?= (int)$stats['new_personalization_count'] ?></div>
    </div>
  </section>

  <section class="admin-links">
    <a class="admin-link" href="/souvenir_shop/pages/admin_products.php">
      <h3>Управление товарами</h3>
      <p>Добавление, редактирование и удаление товаров.</p>
    </a>

    <a class="admin-link" href="/souvenir_shop/pages/admin_orders.php">
      <h3>Заказы</h3>
      <p>Просмотр всех заказов и изменение их статуса.</p>
    </a>

    <a class="admin-link" href="/souvenir_shop/pages/admin_reviews.php">
      <h3>Отзывы</h3>
      <p>Просмотр, публикация, скрытие и удаление отзывов.</p>
    </a>

    <a class="admin-link" href="/souvenir_shop/pages/admin_personalization_requests.php">
      <h3>Заявки на персонализацию</h3>
      <p>Просмотр заявок на гравировку и изменение их статуса.</p>
    </a>
  </section>
</main>

</body>
</html>