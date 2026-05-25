<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isAuth = isset($_SESSION['user_id']);

if (!function_exists('e')) {
    function e($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('h')) {
    function h($value): string
    {
        return e($value);
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        global $basePath;

        $base = $basePath ?? '';

        if ($base === '.' || $base === './') {
            return './' . ltrim($path, '/');
        }

        if ($base === '..' || $base === '../') {
            return '../' . ltrim($path, '/');
        }

        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('pageUrl')) {
    function pageUrl(string $path): string
    {
        global $basePath;

        $base = $basePath ?? '';

        if ($base === '.' || $base === './') {
            return './' . ltrim($path, '/');
        }

        if ($base === '..' || $base === '../') {
            return '../' . ltrim($path, '/');
        }

        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }
}

function renderHead(
    string $title,
    string $description,
    array $cssFiles = []
): void {
    global $isAuth;
    ?>
<!doctype html>
<html lang="ru" data-auth="<?= $isAuth ? '1' : '0'; ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title><?= e($title); ?></title>
  <meta name="description" content="<?= e($description); ?>">

  <meta name="robots" content="index, follow">
  <meta property="og:title" content="<?= e($title); ?>">
  <meta property="og:description" content="<?= e($description); ?>">
  <meta property="og:type" content="website">
  <meta property="og:locale" content="ru_RU">

  <?php foreach ($cssFiles as $css): ?>
    <link rel="stylesheet" href="<?= asset($css); ?>">
  <?php endforeach; ?>
</head>
<body>
<?php
}

function renderHeader(): void
{
    global $isAuth;
    ?>
<header class="nav" role="banner">
  <div class="container nav__inner">
    <a class="brand" href="<?= pageUrl('index.php'); ?>" aria-label="Лавка - вернуться на главную страницу">
      <div class="brand__mark" aria-hidden="true">
        <img src="<?= asset('img/placeholder.webp'); ?>" alt="Логотип">
      </div>
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
      <a class="nav__link" href="<?= pageUrl('index.php'); ?>">Главная</a>
      <a class="nav__link" href="<?= pageUrl('pages/catalog.php'); ?>">Каталог</a>

      <div class="nav__drop" data-dropdown>
        <button class="nav__link nav__link--btn"
                type="button"
                aria-expanded="false"
                aria-haspopup="true"
                aria-controls="mega-menu"
                data-dropdown-btn>
          Категории
          <svg class="chev" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M7 10l5 5 5-5"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.8"
                  stroke-linecap="round"
                  stroke-linejoin="round"/>
          </svg>
        </button>

        <div class="mega" id="mega-menu" data-dropdown-menu role="menu" aria-label="Категории товаров">
          <div class="mega__grid">
            <div>
              <h2 class="mega__title" id="mega-title">Основные категории</h2>

              <div class="mega__cards" role="group" aria-labelledby="mega-title">
                <a class="mega__card" href="<?= pageUrl('pages/catalog.php#group-candles'); ?>" role="menuitem" data-close-mega>
                  <div class="mega__cardTitle">Свечи</div>
                  <div class="mega__cardText">Интерьерные, ароматные, необычные</div>
                </a>

                <a class="mega__card" href="<?= pageUrl('pages/catalog.php#group-ceramics'); ?>" role="menuitem" data-close-mega>
                  <div class="mega__cardTitle">Керамика</div>
                  <div class="mega__cardText">Кружки, тарелки, миски, фигурки</div>
                </a>

                <a class="mega__card" href="<?= pageUrl('pages/catalog.php#group-decor'); ?>" role="menuitem" data-close-mega>
                  <div class="mega__cardTitle">Декор</div>
                  <div class="mega__cardText">Фигурки, вазы, подсвечники</div>
                </a>

                <a class="mega__card" href="<?= pageUrl('pages/catalog.php#group-textile'); ?>" role="menuitem" data-close-mega>
                  <div class="mega__cardTitle">Текстиль</div>
                  <div class="mega__cardText">Игрушки, мешочки, панно, шарфы</div>
                </a>

                <a class="mega__card" href="<?= pageUrl('pages/catalog.php#group-postcards'); ?>" role="menuitem" data-close-mega>
                  <div class="mega__cardTitle">Открытки</div>
                  <div class="mega__cardText">Авторские, минимал, наборы</div>
                </a>

                <a class="mega__card" href="<?= pageUrl('pages/catalog.php#group-sets'); ?>" role="menuitem" data-close-mega>
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
                <a class="btn btn--dark btn--sm" href="<?= pageUrl('pages/catalog.php#collectionsNav'); ?>">Открыть</a>
              </div>

              <div class="mega__preview"
                   role="img"
                   aria-label="Подарочный набор из свечи и керамической кружки"
                   data-bg="<?= asset('img/mega-preview.webp'); ?>">
              </div>

              <div class="mega__note">Быстрая навигация и фильтры — сверху каталога.</div>
            </div>
          </div>
        </div>
      </div>

      <a class="nav__link" href="<?= pageUrl('pages/about.php'); ?>">О компании</a>

      <div class="nav__actions">
        <?php if ($isAuth): ?>
          <a class="iconBtn iconBtn--auth"
             href="<?= pageUrl('pages/account.php'); ?>"
             aria-label="Личный кабинет">
            <svg viewBox="0 0 24 24" aria-hidden="true" class="iconUser">
              <circle cx="12" cy="8" r="3.2"></circle>
              <path d="M5 19c1.4-3 3.6-4.5 7-4.5s5.6 1.5 7 4.5"></path>
            </svg>
          </a>
        <?php else: ?>
          <button class="iconBtn"
                  type="button"
                  aria-label="Войти"
                  data-open-modal="authModal">
            <svg viewBox="0 0 24 24" aria-hidden="true" class="iconUser">
              <circle cx="12" cy="8" r="3.2"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="1.7"/>
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
            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5
                     2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09
                     C13.09 3.81 14.76 3 16.5 3
                     19.58 3 22 5.42 22 8.5
                     c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.6"/>
          </svg>
        </button>

        <a class="btn btn--dark btn--sm hide-sm" href="<?= pageUrl('pages/cart.php'); ?>">Корзина</a>
      </div>
    </nav>
  </div>
</header>
<?php
}

function renderFooter(): void
{
    ?>
<footer class="footer" role="contentinfo">
  <button class="to-top" id="toTopBtn" aria-label="Вернуться наверх" style="display: none;">
    <svg xmlns="http://www.w3.org/2000/svg"
         width="20"
         height="20"
         viewBox="0 0 24 24"
         fill="none"
         stroke="currentColor"
         stroke-width="2"
         stroke-linecap="round"
         stroke-linejoin="round">
      <polyline points="18 15 12 9 6 15"></polyline>
    </svg>
  </button>

  <div class="container">
    <div class="footer__grid">
      <div>
        <a href="<?= pageUrl('index.php'); ?>" class="footer__brand-link">
          <div class="footer__brand">
            <div class="brand__mark" aria-hidden="true">
              <img src="<?= asset('img/placeholder.webp'); ?>" alt="Логотип Лавка">
            </div>
            <div class="brand__name">Лавка</div>
          </div>
        </a>

        <p class="muted">Сувениры ручной работы и забота о деталях.</p>

<div class="footer__social-icons">
  <div class="social-icons">
    <a class="c2Chat" href="#" aria-label="WhatsApp" title="WhatsApp">
      <img class="c2Chat__img" src="<?= asset('img/whatsapp.webp'); ?>" alt="WhatsApp">
    </a>

    <a class="c2Chat" href="#" aria-label="Telegram" title="Telegram">
      <img class="c2Chat__img" src="<?= asset('img/telegram.webp'); ?>" alt="Telegram">
    </a>

    <a class="c2Chat" href="#" aria-label="VK" title="VK">
      <img class="c2Chat__img" src="<?= asset('img/vk.webp'); ?>" alt="VK">
    </a>
  </div>
</div>
      </div>

      <div>
        <h3 class="footer__title">Навигация</h3>
        <ul class="footer__list">
          <li><a class="footer__link" href="<?= pageUrl('index.php'); ?>">Главная</a></li>
          <li><a class="footer__link" href="<?= pageUrl('pages/about.php'); ?>">О компании</a></li>
          <li><a class="footer__link" href="<?= pageUrl('pages/catalog.php'); ?>">Каталог</a></li>
          <li><a class="footer__link" href="<?= pageUrl('pages/registration.php'); ?>">Регистрация</a></li>
        </ul>
      </div>

      <div>
        <h3 class="footer__title">Информация</h3>
        <ul class="footer__list">
          <li><a class="footer__link" href="<?= pageUrl('pages/about.php#delivery'); ?>">Доставка</a></li>
          <li><a class="footer__link" href="<?= pageUrl('pages/about.php#returns'); ?>">Возврат</a></li>
          <li><a class="footer__link" href="<?= pageUrl('pages/about.php#materials'); ?>">Материалы</a></li>
          <li><a class="footer__link" href="<?= pageUrl('pages/about.php#contacts'); ?>">Контакты</a></li>
        </ul>
      </div>

      <div>
        <h3 class="footer__title">Рассылка</h3>
        <p class="muted small">Новости и новые коллекции без спама. Первым узнавайте о скидках!</p>

        <form class="sub" data-newsletter-form>
          <label for="newsletter-email" class="visually-hidden">Email для рассылки</label>
          <input id="newsletter-email" class="input" type="email" placeholder="Ваш email" required>
          <button class="btn btn--dark" type="submit">Подписаться</button>
        </form>
      </div>
    </div>

    <div class="footer__bottom">
      <p class="muted small">&copy; 2026 «Лавка». Все права защищены.</p>
    </div>
  </div>
</footer>
<?php
}

function renderAuthModal(): void
{
    ?>
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
          <?= e($_SESSION['auth_error']); ?>
        </div>
        <?php unset($_SESSION['auth_error']); ?>
      <?php endif; ?>

      <form action="<?= pageUrl('php/auth.php'); ?>" method="post" class="needs-validation" novalidate>
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
        <a href="<?= pageUrl('pages/registration.php'); ?>">Зарегистрироваться</a>
      </p>
    </div>
  </div>
</div>
<?php
}

function renderFavoritesSheet(): void
{
    ?>
<aside class="sheet" id="favoritesSheet" aria-hidden="true">
  <div class="sheet__backdrop" data-close></div>

  <div class="sheet__panel" role="dialog" aria-modal="true" aria-label="Избранное">
    <div class="sheet__head">
      <div class="sheet__title">Избранное</div>
      <button class="iconBtn" type="button" data-close aria-label="Закрыть">✕</button>
    </div>

    <div id="favorites-content"></div>

    <div class="favorites-actions">
      <button class="btn btn--dark btn--full" type="button" id="add-all-to-cart">
        Добавить всё в корзину
      </button>
      <button class="btn btn--full" type="button" id="clear-favorites">
        Очистить избранное
      </button>
    </div>
  </div>
</aside>
<?php
}

function renderScripts(array $jsFiles = []): void
{
    ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const toTopBtn = document.getElementById('toTopBtn');

  if (toTopBtn) {
    window.addEventListener('scroll', function () {
      toTopBtn.style.display = window.pageYOffset > 300 ? 'flex' : 'none';
    });

    toTopBtn.addEventListener('click', function () {
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    });
  }

  const newsletterForm = document.querySelector('[data-newsletter-form]');

  if (newsletterForm) {
    newsletterForm.addEventListener('submit', function (e) {
      e.preventDefault();

      const emailInput = this.querySelector('#newsletter-email');
      const email = emailInput.value.trim();

      if (email && email.includes('@')) {
        alert('Спасибо за подписку! На ' + email + ' отправлено письмо с подтверждением.');
        emailInput.value = '';
      }
    });
  }
});
</script>

<?php foreach ($jsFiles as $js): ?>
<script src="<?= asset($js); ?>" defer></script>
<?php endforeach; ?>

</body>
</html>
<?php
}