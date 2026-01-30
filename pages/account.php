<?php
// pages/account.php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../php/db.php'; // $pdo

$userId = (int)$_SESSION['user_id'];
$errors = [];
$success = '';

// Загрузка данных пользователя
$stmt = $pdo->prepare("
    SELECT id, login, email, phone, delivery_address
    FROM users
    WHERE id = :id
    LIMIT 1
");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();

if (!$user) {
    // если вдруг в сессии мусор — выходим
    session_destroy();
    header('Location: login.php');
    exit;
}

// Обновление профиля (телефон, адрес)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone   = trim($_POST['phone'] ?? '');
    $address = trim($_POST['delivery_address'] ?? '');

    // тут можно добавить валидацию телефона/адреса, если хочется
    $stmt = $pdo->prepare("
        UPDATE users
        SET phone = :phone,
            delivery_address = :address
        WHERE id = :id
    ");
    $ok = $stmt->execute([
        ':phone'   => $phone,
        ':address' => $address,
        ':id'      => $userId,
    ]);

    if ($ok) {
        $success = 'Данные профиля обновлены.';
        $user['phone'] = $phone;
        $user['delivery_address'] = $address;
    } else {
        $errors[] = 'Не удалось обновить данные. Попробуйте ещё раз.';
    }
}

$isAuth = true;
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Личный кабинет — Лавка</title>
  <meta name="description" content="Личный кабинет Лавка: ваши данные, адрес доставки, избранное и купоны." />
  <link rel="stylesheet" href="../css/style.css" />
  <link rel="stylesheet" href="../css/reg.css" />
</head>
<body>
  <div id="screen-reader-announcer" class="visually-hidden" aria-live="assertive" aria-atomic="true"></div>
  
  <!-- ШАПКА -->
  <header class="nav" role="banner">
    <div class="container nav__inner">
      <a class="brand" href="../index.php" aria-label="Лавка - вернуться на главную страницу">
        <div class="brand__mark" aria-hidden="true"><img src="../img/placeholder.webp" alt="Логотип"></div>
        <div class="brand__name">Лавка</div>
      </a>

      <button class="nav__burger" type="button" 
              aria-label="Открыть меню навигации" 
              aria-expanded="false"
              aria-controls="main-menu"
              data-burger>
        <span aria-hidden="true"></span>
        <span aria-hidden="true"></span>
        <span aria-hidden="true"></span>
      </button>

      <nav class="nav__menu" id="main-menu" data-menu role="navigation" aria-label="Основное меню">
        <a class="nav__link" href="../index.php">Главная</a>
        <a class="nav__link" href="catalog.php">Каталог</a>
        
        <div class="nav__drop" data-dropdown>
          <button class="nav__link nav__link--btn" 
                  type="button" 
                  aria-expanded="false"
                  aria-haspopup="true"
                  aria-controls="mega-menu"
                  data-dropdown-btn>
            Категории
            <svg class="chev" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M7 10l5 5 5-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>

          <div class="mega" id="mega-menu" data-dropdown-menu role="menu" aria-label="Категории товаров">
            <div class="mega__grid">
              <div>
                <h2 class="mega__title" id="mega-title">Основные категории</h2>

                <div class="mega__cards" role="group" aria-labelledby="mega-title">
                  <a class="mega__card" href="catalog.php#group-candles" role="menuitem" data-close-mega>
                    <div class="mega__cardTitle">Свечи</div>
                    <div class="mega__cardText">Интерьерные, ароматные, необычные</div>
                  </a>
                  <a class="mega__card" href="catalog.php#group-ceramics" role="menuitem" data-close-mega>
                    <div class="mega__cardTitle">Керамика</div>
                    <div class="mega__cardText">Кружки, тарелки, миски, фигурки</div>
                  </a>
                  <a class="mega__card" href="catalog.php#group-decor" role="menuitem" data-close-mega>
                    <div class="mega__cardTitle">Декор</div>
                    <div class="mega__cardText">Фигурки, вазы, подсвечники</div>
                  </a>
                  <a class="mega__card" href="catalog.php#group-textile" role="menuitem" data-close-mega>
                    <div class="mega__cardTitle">Текстиль</div>
                    <div class="mega__cardText">Игрушки, мешочки, панно, шарфы</div>
                  </a>
                  <a class="mega__card" href="catalog.php#group-postcards" role="menuitem" data-close-mega>
                    <div class="mega__cardTitle">Открытки</div>
                    <div class="mega__cardText">Авторские, минимал, наборы</div>
                  </a>
                  <a class="mega__card" href="catalog.php#group-sets" role="menuitem" data-close-mega>
                    <div class="mega__cardTitle">Подарочные наборы</div>
                    <div class="mega__cardText">Готовые боксы для подарка</div>
                  </a>
                </div>
              </div>

              <div class="mega__feature">
                <div class="mega__featureTop">
                  <div>
                    <div class="mega__featureTitle">Подбор по случаю</div>
                    <div class="mega__featureText">Для дома, "просто так", знак внимания</div>
                  </div>
                  <a class="btn btn--dark btn--sm" href="catalog.php#collectionsNav">Открыть</a>
                </div>

                <div class="mega__preview" 
                     role="img" 
                     aria-label="Подарочный набор из свечи и керамической кружки"
                     data-bg="../img/mega-preview.png">
                </div>
                <div class="mega__note">Быстрая навигация и фильтры — в каталоге.</div>
              </div>
            </div>
          </div>
        </div>

        <a class="nav__link" href="about.php">О компании</a>

        <div class="nav__actions">
          <button class="iconBtn iconBtn--rel" 
                  type="button" 
                  aria-label="Избранное"
                  aria-describedby="favorites-count-desc"
                  data-open-sheet="favoritesSheet">
            <span class="badge" id="favoritesCount" aria-hidden="true">0</span>
            <span id="favorites-count-desc" class="visually-hidden">Товаров в избранном: 0</span>
            <svg viewBox="0 0 24 24" aria-hidden="true" class="favorites-icon">
              <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" 
                    fill="none" 
                    stroke="currentColor" 
                    stroke-width="1.6"/>
            </svg>
          </button>

          <!-- Иконка кабинета (мы уже авторизованы) -->
          <a class="iconBtn" href="account.php" aria-label="Личный кабинет">
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <circle cx="12" cy="8" r="3.2" fill="none" stroke="currentColor" stroke-width="1.6"></circle>
              <path d="M5 19c1.2-3 3.5-4.5 7-4.5s5.8 1.5 7 4.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"></path>
            </svg>
          </a>

          <a class="btn btn--dark btn--sm hide-sm" href="cart.php">Корзина</a>
        </div>
      </nav>
    </div>
  </header>

  <main class="container section auth-page" id="main-content" role="main" tabindex="-1">
    <div class="auth-page__inner">
      <nav class="breadcrumbs" aria-label="Хлебные крошки">
        <ol>
          <li><a href="../index.php">Главная</a></li>
          <li><span aria-current="page">Личный кабинет</span></li>
        </ol>
      </nav>

      <h1 class="auth-title">Личный кабинет</h1>
      <p class="auth-lead">
        Здесь вы можете посмотреть и обновить свои данные, а также следить за избранным и купонами.
      </p>

      <?php if (!empty($success)): ?>
        <div class="auth-success" aria-live="polite">
          <?php echo htmlspecialchars($success, ENT_QUOTES); ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($errors)): ?>
        <div class="auth-errors" aria-live="polite">
          <ul>
            <?php foreach ($errors as $e): ?>
              <li><?php echo htmlspecialchars($e, ENT_QUOTES); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <section class="auth-card" aria-label="Данные профиля">
        <h2 class="auth-subtitle">Профиль</h2>

        <div class="profile-summary">
          <p><strong>Логин:</strong> <?php echo htmlspecialchars($user['login'], ENT_QUOTES); ?></p>
          <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email'], ENT_QUOTES); ?></p>
        </div>

        <form method="post" class="auth-form" novalidate>
          <div class="auth-form__group">
            <label class="auth-form__label" for="phone">Телефон</label>
            <input
              class="input auth-input"
              type="tel"
              id="phone"
              name="phone"
              placeholder="+7 (999) 000-00-00"
              value="<?php echo htmlspecialchars($user['phone'] ?? '', ENT_QUOTES); ?>"
            />
          </div>

          <div class="auth-form__group">
            <label class="auth-form__label" for="delivery_address">Адрес доставки</label>
            <textarea
              class="input auth-input auth-input--area"
              id="delivery_address"
              name="delivery_address"
              rows="3"
              placeholder="Город, улица, дом, квартира"
            ><?php echo htmlspecialchars($user['delivery_address'] ?? '', ENT_QUOTES); ?></textarea>
          </div>

          <button type="submit" class="btn btn--dark auth-btn">Сохранить изменения</button>
        </form>
        <form action="../php/logout.php" method="post" style="margin-top: 16px;">
  <button type="submit" class="btn btn--outline auth-btn">
    Выйти из аккаунта
  </button>
</form>

      </section>

      <section class="auth-card" aria-label="Избранное и купоны">
        <h2 class="auth-subtitle">Избранное</h2>
        <p class="muted small">
          Здесь можно будет показать товары из избранного для авторизованного пользователя.
          Пока — заглушка. 🙂
        </p>

        <h2 class="auth-subtitle" style="margin-top: 24px;">Купоны и бонусы</h2>
        <p class="muted small">
          В будущем сюда можно добавить список купонов, промокодов или бонусных баллов.
        </p>
      </section>
    </div>
  </main>

  <!-- ПОДВАЛ -->
  <footer class="footer" role="contentinfo">
    <div class="container">
      <div class="footer__grid">
        <div>
          <div class="footer__brand">
            <div class="brand__mark" aria-hidden="true"><img src="../img/placeholder.webp" alt="Логотип"></div>
            <div class="brand__name">Лавка</div>
          </div>
          <p class="muted">Сувениры ручной работы. Упаковка, доставка, забота о деталях.</p>
        </div>

        <div>
          <h3 class="footer__title">Навигация</h3>
          <ul class="footer__list">
            <li><a class="footer__link" href="../index.php">Главная</a></li>
            <li><a class="footer__link" href="catalog.php">Каталог</a></li>
            <li><a class="footer__link" href="about.php">О компании</a></li>
          </ul>
        </div>

        <div>
          <h3 class="footer__title">Информация</h3>
          <ul class="footer__list">
            <li><a class="footer__link" href="about.php#delivery">Доставка</a></li>
            <li><a class="footer__link" href="about.php#returns">Возврат</a></li>
            <li><a class="footer__link" href="about.php#warranty">Гарантия</a></li>
          </ul>
        </div>

        <div>
          <h3 class="footer__title">Рассылка</h3>
          <p class="muted small">Новости и новые коллекции без спама.</p>
          <form class="sub" data-newsletter-form>
            <label for="newsletter-email" class="visually-hidden">Email для рассылки</label>
            <input id="newsletter-email" class="input" type="email" placeholder="Email" required />
            <button class="btn btn--dark" type="submit">Подписаться</button>
          </form>
        </div>
      </div>
      
      <div class="footer__bottom">
        <p class="muted small">&copy; 2026 «Лавка». Все права защищены.</p>
        <div class="footer__social">
          <a href="#" aria-label="Лавка в Instagram"><span aria-hidden="true">Instagram</span></a>
          <a href="#" aria-label="Лавка во ВКонтакте"><span aria-hidden="true">VK</span></a>
          <a href="#" aria-label="Лавка в Telegram"><span aria-hidden="true">Telegram</span></a>
        </div>
      </div>
    </div>
  </footer>

  <div class="sheet" id="favoritesSheet" aria-hidden="true" role="dialog" aria-modal="false" aria-labelledby="favorites-title-sheet">
    <div class="sheet__backdrop" data-close-sheet></div>
    <div class="sheet__panel">
      <div class="sheet__head">
        <h2 id="favorites-title-sheet" class="sheet__title">Избранное</h2>
        <button class="iconBtn" type="button" aria-label="Закрыть избранное" data-close-sheet>✕</button>
      </div>

      <div id="favorites-content" aria-live="polite">
        <p class="muted">В избранном пока ничего нет.</p>
      </div>

      <div class="favorites-actions" style="display: none;">
        <button class="btn btn--dark btn--full" id="add-all-to-cart">
          Добавить все в корзину
        </button>
        <button class="btn btn--outline btn--full" id="clear-favorites">
          Очистить избранное
        </button>
      </div>
    </div>
  </div>

  <script src="../js/script.js" defer></script>
</body>
</html>
