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
$hasAuthError = !empty($_SESSION['auth_error']);
?>
<!doctype html>
<html lang="ru" data-auth="<?php echo $isAuth ? '1' : '0'; ?>">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Регистрация — Лавка</title>
  <meta name="description" content="Регистрация в магазине Лавка: создайте аккаунт, чтобы сохранять избранное и быстрее оформлять заказы." />
  <!-- стили подключаем ОТНОСИТЕЛЬНО, без слеша в начале -->
  <link rel="stylesheet" href="../css/style.css"/>
  <link rel="stylesheet" href="../css/main.css"/>
  <link rel="stylesheet" href="../css/reg.css" />
</head>
<body>

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
                   data-bg="../img/mega-preview.webp">
              </div>

              <div class="mega__note">Быстрая навигация и фильтры — сверху каталога.</div>
            </div>
          </div>
        </div>
      </div>

      <a class="nav__link" href="about.php">О компании</a>

      <div class="nav__actions">
        <!-- 🔑 ИКОНКА АККАУНТА - показываем кнопку входа для неавторизованных -->
        <?php if ($isAuth): ?>
          <a class="iconBtn iconBtn--auth"
             href="account.php"
             aria-label="Личный кабинет">
            <svg viewBox="0 0 24 24" aria-hidden="true" class="iconUser">
              <circle cx="12" cy="8" r="3.2" />
              <path d="M5 19c1.4-3 3.6-4.5 7-4.5s5.6 1.5 7 4.5" />
            </svg>
          </a>
        <?php else: ?>
          <button class="iconBtn"
                  type="button"
                  aria-label="Войти"
                  data-open-modal="authModal">
            <svg viewBox="0 0 24 24" aria-hidden="true" class="iconUser">
              <circle cx="12" cy="8" r="3.2" fill="none" stroke="currentColor" stroke-width="1.7"/>
              <path d="M5 19c1.4-3 3.6-4.5 7-4.5s5.6 1.5 7 4.5"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.7"
                    stroke-linecap="round"/>
            </svg>
          </button>
        <?php endif; ?>

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
            <a href="#" class="auth-bottom__link" data-open-modal="authModal">Войти</a>
          </div>
        </form>
      </section>
    </div>
  </main>

<!-- FOOTER -->
<footer class="footer" role="contentinfo">
  <!-- Кнопка "Наверх" -->
  <button class="to-top" id="toTopBtn" aria-label="Вернуться наверх" style="display: none;">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <polyline points="18 15 12 9 6 15"></polyline>
    </svg>
  </button>

  <div class="container">
    <div class="footer__grid">
      <div>
        <a href="../index.php" class="footer__brand-link">
          <div class="footer__brand">
            <div class="brand__mark" aria-hidden="true">
              <img src="../img/placeholder.webp" alt="Логотип Лавка">
            </div>
            <div class="brand__name">Лавка</div>
          </div>
        </a>
        <p class="muted">Сувениры ручной работы и забота о деталях.</p>
        
        <div class="footer__social-icons">
          <div class="social-icons">
            <a href="#" class="social-icon" aria-label="ВКонтакте" title="ВКонтакте">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                <path d="M21.579 6.855c.14-.465 0-.806-.662-.806h-2.193c-.558 0-.815.295-.956.619 0 0-1.118 2.719-2.695 4.482-.51.513-.743.675-1.021.675-.139 0-.341-.162-.341-.627V6.855c0-.558-.161-.806-.626-.806H9.642c-.348 0-.558.258-.558.504 0 .528.79.65.87 2.138v3.228c0 .707-.127.836-.407.836-.743 0-2.551-2.729-3.624-5.853-.209-.607-.42-.853-.98-.853H2.752c-.627 0-.752.295-.752.619 0 .582.743 3.462 3.461 7.271 1.812 2.601 4.363 4.011 6.687 4.011 1.393 0 1.565-.313 1.565-.853v-1.966c0-.626.133-.752.57-.752.324 0 .882.164 2.183 1.417 1.486 1.486 1.732 2.153 2.567 2.153h2.192c.626 0 .939-.313.759-.931-.197-.615-.907-1.51-1.849-2.569-.512-.604-1.277-1.254-1.51-1.579-.325-.419-.231-.604 0-.976.001.001 2.672-3.761 2.95-5.04z"/>
              </svg>
            </a>
            <a href="#" class="social-icon" aria-label="Telegram" title="Telegram">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                <path d="M20.665 3.717l-17.73 6.837c-1.21.486-1.203 1.161-.222 1.462l4.552 1.42 10.532-6.645c.498-.303.953-.14.579.192l-8.533 7.701h-.002l.002.001-.314 4.692c.46 0 .663-.211.921-.46l2.211-2.15 4.599 3.397c.848.467 1.457.227 1.668-.785l3.019-14.228c.309-1.239-.473-1.8-1.282-1.434z"/>
              </svg>
            </a>
            <a href="#" class="social-icon" aria-label="YouTube" title="YouTube">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                <path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/>
              </svg>
            </a>
          </div>
        </div>
      </div>

      <div>
        <h3 class="footer__title">Навигация</h3>
        <ul class="footer__list">
          <li><a class="footer__link" href="../index.php">Главная</a></li>
          <li><a class="footer__link" href="about.php">О компании</a></li>
          <li><a class="footer__link" href="catalog.php">Каталог</a></li>
          <li><a class="footer__link" href="registration.php">Регистрация</a></li>
        </ul>
      </div>

      <div>
        <h3 class="footer__title">Информация</h3>
        <ul class="footer__list">
          <li><a class="footer__link" href="about.php#delivery">Доставка</a></li>
          <li><a class="footer__link" href="about.php#returns">Возврат</a></li>
          <li><a class="footer__link" href="about.php#materials">Материалы</a></li>
          <li><a class="footer__link" href="about.php#contacts">Контакты</a></li>
        </ul>
      </div>

      <div>
        <h3 class="footer__title">Рассылка</h3>
        <p class="muted small">Новости и новые коллекции без спама. Первым узнавайте о скидках!</p>
        <form class="sub" data-newsletter-form>
          <label for="newsletter-email" class="visually-hidden">Email для рассылки</label>
          <input id="newsletter-email" class="input" type="email" placeholder="Ваш email" required />
          <button class="btn btn--dark" type="submit">Подписаться</button>
        </form>
      </div>
    </div>

    <div class="footer__bottom">
      <p class="muted small">&copy; 2026 «Лавка». Все права защищены.</p>
    </div>
  </div>
</footer>

<script>
  // Скрипт для кнопки "Наверх"
  document.addEventListener('DOMContentLoaded', function() {
    const toTopBtn = document.getElementById('toTopBtn');
    
    // Показываем кнопку при прокрутке
    window.addEventListener('scroll', function() {
      if (window.pageYOffset > 300) {
        toTopBtn.style.display = 'flex';
      } else {
        toTopBtn.style.display = 'none';
      }
    });
    
    // Плавная прокрутка наверх
    toTopBtn.addEventListener('click', function() {
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    });
    
    const newsletterForm = document.querySelector('[data-newsletter-form]');
    if (newsletterForm) {
      newsletterForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const emailInput = this.querySelector('#newsletter-email');
        const email = emailInput.value.trim();
        
        if (email && email.includes('@')) {
          console.log('Подписка на рассылку:', email);
          alert('Спасибо за подписку! На ' + email + ' отправлено письмо с подтверждением.');
          emailInput.value = '';
        }
      });
    }
  });
</script>

<div class="modal" id="authModal" aria-hidden="true"
     <?php if (!empty($_SESSION['auth_error'])) echo 'data-autoshow="1"'; ?>>
  <div class="modal__backdrop" data-close></div>

  <div class="modal__dialog" role="dialog" aria-modal="true" aria-label="Авторизация">
    <div class="modal__head">
      <div class="modal__title">Вход в аккаунт</div>
      <button class="iconBtn" type="button" data-close aria-label="Закрыть">✕</button>
    </div>

    <div class="modal__body">

      <?php if (!empty($_SESSION['auth_error'])): ?>
        <div class="alert alert--error" style="color:#b00020; margin-bottom:10px;">
          <?= htmlspecialchars($_SESSION['auth_error']) ?>
        </div>
        <?php unset($_SESSION['auth_error']); ?>
      <?php endif; ?>

      <form action="../php/auth.php" method="post" class="needs-validation" novalidate>
        <div class="mb-3">
          <label for="authLogin" class="small">Логин или email</label>
          <input id="authLogin" class="input input--lg" type="text" name="login" required>
        </div>

        <div class="mb-3">
          <label for="authPass" class="small">Пароль</label>
          <input id="authPass" class="input input--lg" type="password" name="password" required>
        </div>

        <button class="btn btn--dark btn--full" style="margin-top:20px;" type="submit">Войти</button>
      </form>

      <p class="muted small" style="margin-top:12px;">
        Нет аккаунта?
        <a href="registration.php">Зарегистрироваться</a>
      </p>
    </div>
  </div>
</div>

<!-- FAVORITES SHEET -->
<aside class="sheet" id="favoritesSheet" aria-hidden="true">
  <div class="sheet__backdrop" data-close></div>

  <div class="sheet__panel" role="dialog" aria-modal="true" aria-label="Избранное">
    <div class="sheet__head">
      <div class="sheet__title">Избранное</div>
      <button class="iconBtn" type="button" data-close aria-label="Закрыть">✕</button>
    </div>

    <div id="favorites-content"></div>

    <div class="favorites-actions">
      <button class="btn btn--dark btn--full" type="button" id="add-all-to-cart">Добавить всё в корзину</button>
      <button class="btn btn--full" type="button" id="clear-favorites">Очистить избранное</button>
    </div>
  </div>
</aside>

<script src="../js/script.js" defer></script>

</body>
</html>