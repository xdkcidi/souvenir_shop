<?php
session_start();

$isAuth = isset($_SESSION['user_id']);
$hasAuthError = !empty($_SESSION['auth_error']);
?>
<?php
$basePath = '..';
require_once __DIR__ . '/../includes/layout.php';

renderHead(
    'Корзина — Лавка',
    'Корзина магазина Лавка: проверьте товары, количество и перейдите к оформлению заказа.',
    [
        'css/main.css',
        'css/style.css',
        'css/cart.css'
    ]
);

renderHeader();
?>
<main class="container section" id="main-content" role="main" tabindex="-1">
  <nav class="breadcrumbs" aria-label="Хлебные крошки">
    <ol>
      <li><a href="../index.php">Главная</a></li>
      <li><span aria-current="page">Корзина</span></li>
    </ol>
  </nav>

  <div class="headRow">
    <div>
      <h1 class="h2">Корзина</h1>
      <p class="muted">Проверьте товары и количество перед оформлением.</p>
    </div>

    <div class="headBtn">
      <a class="btn" href="catalog.php">В каталог</a>
      <?php if ($isAuth): ?>
        <button class="btn" id="cartClearBtn" type="button">Очистить</button>
      <?php endif; ?>
    </div>
  </div>

  <?php if (!$isAuth): ?>
    <div class="banner">
      <div class="banner__body">
        <h2 class="h2">Чтобы добавить товар в корзину, войдите в аккаунт</h2>
        <p class="lead">
          Добавление и просмотр корзины доступны только после авторизации.
        </p>
        <button class="btn btn--dark"
                type="button"
                data-open-modal="authModal">
          Войти
        </button>
      </div>
    </div>
  <?php else: ?>

    <div id="cartEmpty" class="banner" style="display:none;">
      <div class="banner__body">
        <p class="kicker">Лавка / корзина</p>
        <h2 class="h2">Корзина пустая</h2>
        <p class="lead">Добавьте товары из каталога или хитов — и они появятся здесь.</p>
        <div class="rowBtns">
          <a class="btn btn--dark" href="catalog.php">Перейти в каталог</a>
          <a class="btn" href="../index.php#hits">Посмотреть хиты</a>
        </div>
      </div>
    </div>

    <div class="cartLayout" id="cartLayout" style="display:none;">
      <div class="cartList" id="cartList"></div>

      <aside class="cartSummary">
        <div class="card" style="padding:16px;">
          <div class="muted small">Итого</div>
          <div class="h2" style="margin:6px 0;">
            <span id="cartTotalSum">0</span> ₽
          </div>
          <div class="muted small">Товаров: <span id="cartTotalQty">0</span></div>

          <a class="btn btn--dark btn--full" href="checkout.php?mode=cart" style="margin-top:12px;">
            Оформить заказ
          </a>
        </div>
      </aside>
    </div>

  <?php endif; ?>
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
