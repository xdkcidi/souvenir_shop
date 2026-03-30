<?php
session_start();
require_once __DIR__ . '/../php/admin_guard.php';
require_once __DIR__ . '/../php/db.php';

$stmt = $pdo->query("
    SELECT
        id,
        product_code,
        category,
        name,
        price,
        in_stock,
        image,
        badge,
        is_personalizable
    FROM products
    ORDER BY
      FIELD(category, 'ceramics', 'postcards', 'candles', 'textile', 'decor', 'sets'),
      id ASC
");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function adminBadgeText(?string $badge): string
{
    return match ((string)$badge) {
        'hit' => 'Хит',
        'new' => 'Новинка',
        default => '—',
    };
}

function adminCategoryText(?string $category): string
{
    return match ((string)$category) {
        'ceramics'  => 'Керамика',
        'postcards' => 'Открытки',
        'candles'   => 'Свечи',
        'textile'   => 'Текстиль',
        'decor'     => 'Декор',
        'sets'      => 'Наборы',
        default     => (string)$category,
    };
}
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Товары — Админка</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
<main class="admin-wrap">
  <div class="admin-head">
    <div>
      <h1 style="margin:0 0 8px;">Управление товарами</h1>
      <p style="margin:0; color:#666;">Добавление, редактирование и удаление товаров.</p>
    </div>

    <div class="admin-top-actions">
      <a class="btn btn--dark" href="/souvenir_shop/pages/admin_product_form.php">Добавить товар</a>
      <a class="btn btn--outline" href="/souvenir_shop/pages/admin.php">Назад</a>
    </div>
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

  <section class="admin-panel">
    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Фото</th>
            <th>Код</th>
            <th>Название</th>
            <th>Категория</th>
            <th>Цена</th>
            <th>Остаток</th>
            <th>Персонализация</th>
            <th>Бейдж</th>
            <th>Действия</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!$products): ?>
          <tr>
            <td colspan="10">Товаров пока нет.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($products as $product): ?>
            <tr>
              <td><?= (int)$product['id'] ?></td>
              <td>
                <img
                  class="admin-thumb"
                  src="<?= h((string)$product['image']) ?>"
                  alt="<?= h((string)$product['name']) ?>"
                  onerror="this.src='../img/placeholder.webp';"
                >
              </td>
              <td><?= h((string)$product['product_code']) ?></td>
              <td><?= h((string)$product['name']) ?></td>
              <td><?= h(adminCategoryText($product['category'] ?? '')) ?></td>
              <td><?= number_format((int)$product['price'], 0, '', ' ') ?> ₽</td>
              <td class="<?= ((int)$product['in_stock'] <= 0 ? 'admin-stock--zero' : '') ?>">
                <?= (int)$product['in_stock'] ?>
              </td>
              <td>
                <span class="admin-pill">
                  <?= ((int)$product['is_personalizable'] === 1 ? 'Да' : 'Нет') ?>
                </span>
              </td>
              <td><?= h(adminBadgeText($product['badge'] ?? null)) ?></td>
              <td>
                <div class="admin-actions">
                  <a class="btn btn--outline btn--sm" href="/souvenir_shop/pages/admin_product_form.php?id=<?= (int)$product['id'] ?>">
                    Изменить
                  </a>

<form class="inline" action="/souvenir_shop/php/admin_product_action.php" method="post" onsubmit="return confirm('Удалить товар?');">
  <input type="hidden" name="action" value="delete">
  <input type="hidden" name="id" value="<?= (int)$product['id'] ?>">
  <button class="btn btn--sm" type="submit">Удалить</button>
</form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
</main>
</body>
</html>