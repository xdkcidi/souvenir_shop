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

$productsCount = (int)($stats['products_count'] ?? 0);
$ordersCount = (int)($stats['orders_count'] ?? 0);
$reviewsCount = (int)($stats['reviews_count'] ?? 0);
$newOrdersCount = (int)($stats['new_orders_count'] ?? 0);
$personalizationCount = (int)($stats['personalization_count'] ?? 0);
$newPersonalizationCount = (int)($stats['new_personalization_count'] ?? 0);

$dashboardMax = max(1, $productsCount, $ordersCount, $reviewsCount, $personalizationCount);
$newOrdersPct = $ordersCount > 0 ? (int)round($newOrdersCount / $ordersCount * 100) : 0;
$newPersonalizationPct = $personalizationCount > 0 ? (int)round($newPersonalizationCount / $personalizationCount * 100) : 0;
$attentionCount = $newOrdersCount + $newPersonalizationCount;
?>
<?php
$basePath = '..';
require_once __DIR__ . '/../includes/layout.php';

renderHead(
    'Админ-панель — Лавка',
    'Панель администратора Лавки: статистика, товары, заказы, отзывы и заявки на персонализацию.',
    [
        'css/style.css',
        'css/admin.css'
    ]
);

renderHeader();
?>

<main class="admin-wrap admin-wrap--lg admin-dashboard">
  <div class="admin-head admin-head--dashboard">
    <div>
      <h1 class="admin-head__title">Админ-панель</h1>
      <p class="admin-head__text">
        Вы вошли как администратор: <?= htmlspecialchars($_SESSION['user_login']) ?>
      </p>
    </div>

    <div class="admin-head__actions">
      <a class="btn btn--outline" href="../pages/account.php">Личный кабинет</a>
      <a class="btn btn--dark" href="../index.php">На Главную</a>
    </div>
  </div>

  <section class="admin-overview">
    <div class="admin-overview__hero admin-panel">
      <div class="admin-kicker">Обзор</div>
      <h2 class="admin-overview__title">Главная сводка по магазину</h2>
      <p class="admin-overview__text">
        Здесь собраны основные показатели, текущая нагрузка и быстрый доступ к ключевым разделам админ-панели.
      </p>

      <div class="admin-overview__chips">
        <span class="summary-chip summary-chip--neutral">Товаров: <b><?= $productsCount ?></b></span>
        <span class="summary-chip summary-chip--info">Заказов: <b><?= $ordersCount ?></b></span>
        <span class="summary-chip summary-chip--success">Отзывов: <b><?= $reviewsCount ?></b></span>
      </div>
    </div>

    <div class="admin-overview__alert admin-panel">
      <div class="admin-kicker">Требует внимания</div>
      <div class="admin-overview__alertCount"><?= $attentionCount ?></div>
      <div class="admin-overview__alertText">Новых действий для проверки</div>

      <div class="admin-overview__list">
        <div class="admin-miniStat">
          <span class="admin-miniStat__label">Новые заказы</span>
          <strong><?= $newOrdersCount ?></strong>
        </div>
        <div class="admin-miniStat">
          <span class="admin-miniStat__label">Новые заявки</span>
          <strong><?= $newPersonalizationCount ?></strong>
        </div>
      </div>
    </div>
  </section>

    <section class="admin-sectionBlock">
    <div class="admin-sectionHead admin-sectionHead--withAction">
      <div>
        <h2 class="admin-sectionTitle">Быстрый доступ</h2>
        <p class="admin-sectionText">Основные разделы управления магазином</p>
      </div>
    </div>

    <div class="admin-links admin-links--dashboard">
      <a class="admin-link" href="../pages/admin_products.php">
        <div class="admin-link__eyebrow">Каталог</div>
        <h3>Управление товарами</h3>
        <p>Добавление, редактирование и удаление товаров.</p>
      </a>

      <a class="admin-link" href="../pages/admin_orders.php">
        <div class="admin-link__eyebrow">Продажи</div>
        <h3>Заказы</h3>
        <p>Просмотр всех заказов и изменение их статуса.</p>
      </a>

      <a class="admin-link" href="../pages/admin_reviews.php">
        <div class="admin-link__eyebrow">Контент</div>
        <h3>Отзывы</h3>
        <p>Просмотр, публикация, скрытие и удаление отзывов.</p>
      </a>

      <a class="admin-link" href="../pages/admin_personalization_requests.php">
        <div class="admin-link__eyebrow">Сервисы</div>
        <h3>Заявки на персонализацию</h3>
        <p>Просмотр заявок на гравировку и изменение их статуса.</p>
      </a>
    </div>
  </section>

  <section class="admin-grid admin-grid--stats">
    <div class="admin-card admin-card--stat">
      <div class="admin-card__label">Товаров</div>
      <div class="admin-card__value"><?= $productsCount ?></div>
      <div class="admin-card__hint">Каталог магазина</div>
    </div>

    <div class="admin-card admin-card--stat admin-card--accent3">
      <div class="admin-card__label">Отзывов</div>
      <div class="admin-card__value"><?= $reviewsCount ?></div>
      <div class="admin-card__hint">Обратная связь клиентов</div>
    </div>

    <div class="admin-card admin-card--stat admin-card--accent">
      <div class="admin-card__label">Новых заказов</div>
      <div class="admin-card__value"><?= $newOrdersCount ?></div>
      <div class="admin-card__hint">Ожидают обработки</div>
    </div>

    <div class="admin-card admin-card--stat admin-card--accent2">
      <div class="admin-card__label">Новых заявок</div>
      <div class="admin-card__value"><?= $newPersonalizationCount ?></div>
      <div class="admin-card__hint">Нужно посмотреть</div>
    </div>
  </section>
  
</main>
<?php
renderFooter();
renderAuthModal();
renderFavoritesSheet();

renderScripts([
    'js/script.js',
    'js/cart.js',
    'js/favorites.js'
]);
?>
