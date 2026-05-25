<?php
session_start();
require_once __DIR__ . '/../php/admin_guard.php';
require_once __DIR__ . '/../php/db.php';

function h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;

$product = [
    'id' => 0,
    'product_code' => '',
    'category' => 'candles',
    'name' => '',
    'image' => '../img/placeholder.webp',
    'image2' => '',
    'price' => '',
    'in_stock' => 0,
    'material' => '',
    'color' => '',
    'dimensions' => '',
    'is_personalizable' => 0,
    'meta' => '',
    'description_full' => '',
    'badge' => '',
];

if ($isEdit) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $found = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$found) {
        $_SESSION['admin_error'] = 'Товар не найден.';
        header('Location: ../pages/admin_products.php');
        exit;
    }

    $product = array_merge($product, $found);
}

$categories = [
    'candles' => 'Свечи',
    'ceramics' => 'Керамика',
    'decor' => 'Декор',
    'textile' => 'Текстиль',
    'postcards' => 'Открытки',
    'sets' => 'Наборы',
];
?>
<?php
$basePath = '..';
require_once __DIR__ . '/../includes/layout.php';

renderHead(
    ($isEdit ? 'Редактирование товара — Админка' : 'Добавление товара — Админка'),
    'Форма администратора для добавления и редактирования товаров Лавки.',
    [
        'css/style.css',
        'css/admin.css'
    ]
);

renderHeader();
?>

<main class="admin-wrap">
  <div class="admin-head">
    <div>
      <h1 style="margin:0 0 8px;"><?= $isEdit ? 'Редактирование товара' : 'Добавление товара' ?></h1>
      <p style="margin:0; color:#666;">Заполни поля и сохрани изменения.</p>
    </div>
    <a class="btn btn--outline" href="../pages/admin_products.php">Назад</a>
  </div>

  <section class="admin-panel">
    <form action="../php/admin_product_action.php" method="post">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= (int)$product['id'] ?>">

      <div class="admin-grid">
        <div class="field">
          <label for="product_code">Код товара</label>
          <input class="input" id="product_code" name="product_code" type="text" required value="<?= h($product['product_code']) ?>">
        </div>

        <div class="field">
          <label for="category">Категория</label>
          <select class="input" id="category" name="category" required>
            <?php foreach ($categories as $value => $label): ?>
              <option value="<?= h($value) ?>" <?= $product['category'] === $value ? 'selected' : '' ?>>
                <?= h($label) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field full">
          <label for="name">Название</label>
          <input class="input" id="name" name="name" type="text" required value="<?= h($product['name']) ?>">
        </div>

        <div class="field">
          <label for="image">Главная картинка</label>
          <input class="input" id="image" name="image" type="text" value="<?= h($product['image']) ?>">
        </div>

        <div class="field">
          <label for="image2">Доп. картинка</label>
          <input class="input" id="image2" name="image2" type="text" value="<?= h($product['image2']) ?>">
        </div>

        <div class="field">
          <label for="price">Цена</label>
          <input class="input" id="price" name="price" type="number" min="0" required value="<?= h((string)$product['price']) ?>">
        </div>

        <div class="field">
          <label for="in_stock">Количество на складе</label>
          <input class="input" id="in_stock" name="in_stock" type="number" min="0" required value="<?= h((string)$product['in_stock']) ?>">
        </div>

        <div class="field">
          <label for="material">Материал</label>
          <input class="input" id="material" name="material" type="text" value="<?= h($product['material']) ?>">
        </div>

        <div class="field">
          <label for="color">Цвет</label>
          <input class="input" id="color" name="color" type="text" value="<?= h($product['color']) ?>">
        </div>

        <div class="field">
          <label for="dimensions">Размеры</label>
          <input class="input" id="dimensions" name="dimensions" type="text" value="<?= h($product['dimensions']) ?>">
        </div>

        <div class="field">
          <label for="badge">Бейдж</label>
          <select class="input" id="badge" name="badge">
            <option value="" <?= $product['badge'] === '' || $product['badge'] === null ? 'selected' : '' ?>>Без бейджа</option>
            <option value="hit" <?= $product['badge'] === 'hit' ? 'selected' : '' ?>>Хит</option>
            <option value="new" <?= $product['badge'] === 'new' ? 'selected' : '' ?>>Новинка</option>
          </select>
        </div>

        <div class="field">
          <label for="is_personalizable">Персонализация</label>
          <select class="input" id="is_personalizable" name="is_personalizable">
            <option value="0" <?= (int)$product['is_personalizable'] === 0 ? 'selected' : '' ?>>Нет</option>
            <option value="1" <?= (int)$product['is_personalizable'] === 1 ? 'selected' : '' ?>>Да</option>
          </select>
        </div>

        <div class="field full">
          <label for="meta">Краткое описание</label>
          <input class="input" id="meta" name="meta" type="text" value="<?= h($product['meta']) ?>">
        </div>

        <div class="field full">
          <label for="description_full">Полное описание</label>
          <textarea class="input" id="description_full" name="description_full"><?= h($product['description_full']) ?></textarea>
        </div>
      </div>

      <div class="actions">
        <button class="btn btn--dark" type="submit"><?= $isEdit ? 'Сохранить изменения' : 'Добавить товар' ?></button>
        <a class="btn btn--outline" href="../pages/admin_products.php">Отмена</a>
      </div>
    </form>
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
