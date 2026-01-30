<?php
// pages/registration.php
session_start();

// если уже авторизован — сразу в личный кабинет
if (isset($_SESSION['user_id'])) {
    header('Location: account.php');
    exit;
}

require_once __DIR__ . '/../php/db.php'; // $pdo

$errors = [];
$login   = trim($_POST['login'] ?? '');
$email   = trim($_POST['email'] ?? '');
$phone   = trim($_POST['phone'] ?? '');
$address = trim($_POST['delivery_address'] ?? '');
$password = $_POST['password'] ?? '';
$password_confirm = $_POST['password_confirm'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ВАЛИДАЦИЯ
    if ($login === '') {
        $errors[] = 'Введите логин.';
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Введите корректный email.';
    }

    if (mb_strlen($password) < 6) {
        $errors[] = 'Пароль должен быть не короче 6 символов.';
    }

    if ($password !== $password_confirm) {
        $errors[] = 'Пароли не совпадают.';
    }

    // ПРОВЕРКА ЛОГИНА / EMAIL
    if (empty($errors)) {
        $stmt = $pdo->prepare("
            SELECT id, login, email 
            FROM users 
            WHERE login = :login OR email = :email 
            LIMIT 1
        ");
        $stmt->execute([
            ':login' => $login,
            ':email' => $email
        ]);
        $row = $stmt->fetch();

        if ($row) {
            if (mb_strtolower($row['login']) === mb_strtolower($login)) {
                $errors[] = 'Пользователь с таким логином уже существует.';
            }
            if (mb_strtolower($row['email']) === mb_strtolower($email)) {
                $errors[] = 'Пользователь с таким email уже существует.';
            }
        }
    }

    // СОХРАНЕНИЕ ПОЛЬЗОВАТЕЛЯ
    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            INSERT INTO users (login, email, password_hash, phone, delivery_address)
            VALUES (:login, :email, :password_hash, :phone, :address)
        ");

        $ok = $stmt->execute([
            ':login'         => $login,
            ':email'         => $email,
            ':password_hash' => $hash,
            ':phone'         => $phone,
            ':address'       => $address,
        ]);

        if ($ok) {
            $newUserId = (int)$pdo->lastInsertId();
            $_SESSION['user_id']    = $newUserId;
            $_SESSION['user_login'] = $login;

            header('Location: account.php');
            exit;
        } else {
            $errors[] = 'Не удалось сохранить данные. Попробуйте ещё раз.';
        }
    }
}

$isAuth = isset($_SESSION['user_id']);
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Регистрация — Лавка</title>
  <meta name="description" content="Регистрация в магазине Лавка: создайте аккаунт, чтобы сохранять избранное и быстрее оформлять заказы." />
  <link rel="stylesheet" href="../css/style.css" />
  <link rel="stylesheet" href="../css/reg.css" />
</head>
<body>
  <!-- Область для объявлений скринридеру -->
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

          <!-- MEGA MENU -->
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
          <!-- Избранное -->
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

          <!-- Иконка: вход / кабинет -->
          <?php if ($isAuth): ?>
            <a class="iconBtn" href="account.php" aria-label="Личный кабинет">
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="12" cy="8" r="3.2" fill="none" stroke="currentColor" stroke-width="1.6"></circle>
                <path d="M5 19c1.2-3 3.5-4.5 7-4.5s5.8 1.5 7 4.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"></path>
              </svg>
            </a>
          <?php else: ?>
            <a class="iconBtn" href="login.php" aria-label="Войти">
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="12" cy="8" r="3.2" fill="none" stroke="currentColor" stroke-width="1.6"></circle>
                <path d="M5 19c1.2-3 3.5-4.5 7-4.5s5.8 1.5 7 4.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"></path>
              </svg>
            </a>
          <?php endif; ?>

          <a class="btn btn--dark btn--sm hide-sm" href="cart.php">Корзина</a>
        </div>
      </nav>
    </div>
  </header>

  <!-- ОСНОВНОЕ СОДЕРЖИМОЕ -->
  <main class="container section auth-page" id="main-content" role="main" tabindex="-1">
    <div class="auth-page__inner">
      <!-- Хлебные крошки -->
      <nav class="breadcrumbs" aria-label="Хлебные крошки">
        <ol>
          <li><a href="../index.php">Главная</a></li>
          <li><span aria-current="page">Регистрация</span></li>
        </ol>
      </nav>

      <h1 class="auth-title">Регистрация</h1>
      <p class="auth-lead">
        Создайте аккаунт, чтобы сохранять избранное и быстрее оформлять заказы.
      </p>

      <section class="auth-card" aria-label="Форма регистрации">
        <?php if (!empty($errors)): ?>
          <div class="auth-errors" aria-live="polite">
            <ul>
              <?php foreach ($errors as $e): ?>
                <li><?php echo htmlspecialchars($e, ENT_QUOTES); ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form method="post" class="auth-form" novalidate>
          <div class="auth-form__group">
            <label class="auth-form__label" for="login">Логин</label>
            <input
              class="input auth-input"
              type="text"
              id="login"
              name="login"
              value="<?php echo htmlspecialchars($login, ENT_QUOTES); ?>"
              required
            />
          </div>

          <div class="auth-form__group">
            <label class="auth-form__label" for="email">Email</label>
            <input
              class="input auth-input"
              type="email"
              id="email"
              name="email"
              value="<?php echo htmlspecialchars($email, ENT_QUOTES); ?>"
              required
            />
          </div>

          <div class="auth-form__group">
            <label class="auth-form__label" for="password">Пароль</label>
            <input
              class="input auth-input"
              type="password"
              id="password"
              name="password"
              minlength="6"
              required
            />
            <p class="auth-hint">Минимум 6 символов.</p>
          </div>

          <div class="auth-form__group">
            <label class="auth-form__label" for="password_confirm">Повторите пароль</label>
            <input
              class="input auth-input"
              type="password"
              id="password_confirm"
              name="password_confirm"
              required
            />
          </div>

          <div class="auth-form__group">
            <label class="auth-form__label" for="phone">Телефон (необязательно)</label>
            <input
              class="input auth-input"
              type="tel"
              id="phone"
              name="phone"
              placeholder="+7 (999) 000-00-00"
              value="<?php echo htmlspecialchars($phone, ENT_QUOTES); ?>"
            />
          </div>

          <div class="auth-form__group">
            <label class="auth-form__label" for="delivery_address">Адрес доставки (необязательно)</label>
            <textarea
              class="input auth-input auth-input--area"
              id="delivery_address"
              name="delivery_address"
              rows="3"
              placeholder="Город, улица, дом, квартира"
            ><?php echo htmlspecialchars($address, ENT_QUOTES); ?></textarea>
          </div>

          <button type="submit" class="btn btn--dark auth-btn">Зарегистрироваться</button>

          <div class="auth-bottom">
            <span class="auth-bottom__text">Уже есть аккаунт?</span>
            <a href="login.php" class="auth-bottom__link">Войти</a>
          </div>
        </form>
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

  <!-- Избранное (sheet) -->
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
