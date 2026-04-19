<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isAuth = isset($_SESSION['user_id']);
$hasAuthError = !empty($_SESSION['auth_error']);
?>
<!doctype html>
<html lang="ru" data-auth="<?php echo $isAuth ? '1' : '0'; ?>">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Политика обработки персональных данных — Лавка</title>
  <meta name="description" content="Политика обработки персональных данных интернет-магазина Лавка." />
  <link rel="stylesheet" href="../css/style.css" />
  <link rel="stylesheet" href="../css/main.css" />
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

<main class="container section auth-page" id="main-content" role="main" tabindex="-1">
  <div class="auth-page__inner">
    <nav class="breadcrumbs" aria-label="Хлебные крошки">
      <ol>
        <li><a href="../index.php">Главная</a></li>
        <li><span aria-current="page">Политика обработки персональных данных</span></li>
      </ol>
    </nav>

    <h1 class="auth-title">Политика обработки персональных данных</h1>
    <p class="auth-lead">
      На этой странице описано, какие персональные данные собирает сайт «Лавка»,
      для каких целей они используются и какие права есть у пользователя.
    </p>

    <section class="auth-card" aria-label="Политика обработки персональных данных">
      <div style="display:grid; gap:18px; line-height:1.75; color:var(--text,#222);">
        <div>
          <h2 class="footer__title" style="margin-bottom:8px;">1. Общие положения</h2>
          <p>
            Настоящая Политика определяет порядок обработки и защиты персональных данных,
            которые пользователь предоставляет при использовании сайта «Лавка».
          </p>
          <p>
            Оператором персональных данных в рамках данного сайта является администрация сайта «Лавка».
            Для связи по вопросам обработки персональных данных пользователь может использовать
            контакты, указанные на странице <a href="about.php#contacts">«Контакты»</a>.
          </p>
        </div>

        <div>
          <h2 class="footer__title" style="margin-bottom:8px;">2. Какие данные могут обрабатываться</h2>
          <p>Сайт может обрабатывать следующие данные пользователя:</p>
          <ul style="padding-left:18px;">
            <li>логин;</li>
            <li>адрес электронной почты;</li>
            <li>номер телефона;</li>
            <li>адрес доставки;</li>
            <li>имя и иные сведения, которые пользователь сам указывает в формах сайта;</li>
            <li>данные, содержащиеся в заказах, заявках на персонализацию, отзывах и сообщениях через формы сайта.</li>
          </ul>
        </div>

        <div>
          <h2 class="footer__title" style="margin-bottom:8px;">3. Цели обработки персональных данных</h2>
          <p>Персональные данные обрабатываются в следующих целях:</p>
          <ul style="padding-left:18px;">
            <li>регистрация и авторизация пользователя на сайте;</li>
            <li>оформление, оплата и сопровождение заказов;</li>
            <li>доставка товаров и связь с пользователем по вопросам заказа;</li>
            <li>обработка заявок на персонализацию и обратной связи;</li>
            <li>публикация и модерация отзывов;</li>
            <li>улучшение качества работы сайта и сервиса.</li>
          </ul>
        </div>

        <div>
          <h2 class="footer__title" style="margin-bottom:8px;">4. Правовые основания обработки</h2>
          <p>
            Обработка персональных данных осуществляется на основании согласия пользователя,
            а также в случаях, когда обработка необходима для исполнения действий,
            связанных с регистрацией, оформлением и исполнением заказа, обработкой заявки
            или иного обращения пользователя.
          </p>
        </div>

        <div>
          <h2 class="footer__title" style="margin-bottom:8px;">5. Способы обработки персональных данных</h2>
          <p>
            Обработка персональных данных осуществляется с использованием средств автоматизации,
            а также без их использования, если это необходимо для достижения целей обработки.
          </p>
          <p>
            При обработке персональных данных могут совершаться следующие действия:
            сбор, запись, систематизация, накопление, хранение, уточнение, использование,
            передача в случаях, предусмотренных законодательством и необходимых для выполнения заказа,
            обезличивание, блокирование, удаление и уничтожение.
          </p>
        </div>

        <div>
          <h2 class="footer__title" style="margin-bottom:8px;">6. Сроки хранения персональных данных</h2>
          <p>
            Персональные данные хранятся не дольше, чем этого требуют цели обработки,
            если более длительный срок хранения не предусмотрен законодательством Российской Федерации.
          </p>
          <p>
            После достижения целей обработки или отзыва согласия персональные данные подлежат
            удалению либо обезличиванию, если иное не требуется по закону.
          </p>
        </div>

        <div>
          <h2 class="footer__title" style="margin-bottom:8px;">7. Права пользователя</h2>
          <p>Пользователь имеет право:</p>
          <ul style="padding-left:18px;">
            <li>получать сведения о своих персональных данных и порядке их обработки;</li>
            <li>требовать уточнения, блокирования или удаления своих данных;</li>
            <li>отозвать согласие на обработку персональных данных;</li>
            <li>обратиться к оператору по вопросам, связанным с обработкой персональных данных.</li>
          </ul>
        </div>

        <div>
          <h2 class="footer__title" style="margin-bottom:8px;">8. Меры по защите персональных данных</h2>
          <p>
            Оператор принимает необходимые правовые, организационные и технические меры
            для защиты персональных данных от неправомерного доступа, изменения, распространения,
            уничтожения и иных неправомерных действий.
          </p>
          <p>
            Доступ к персональным данным предоставляется только тем лицам, которым он необходим
            для выполнения соответствующих задач в рамках работы сайта.
          </p>
        </div>

        <div>
          <h2 class="footer__title" style="margin-bottom:8px;">9. Использование файлов cookie и технических данных</h2>
          <p>
            Сайт может использовать cookie и иные технические данные, необходимые для корректной
            работы пользовательских сценариев, авторизации, хранения предпочтений и повышения
            удобства использования сайта.
          </p>
        </div>

        <div>
          <h2 class="footer__title" style="margin-bottom:8px;">10. Заключительные положения</h2>
          <p>
            Настоящая Политика действует бессрочно до замены новой редакцией.
            Актуальная версия Политики всегда доступна на данной странице сайта.
          </p>
          <p>
            Пользователь, предоставляя свои персональные данные через формы сайта,
            подтверждает, что ознакомлен с настоящей Политикой.
          </p>
        </div>
      </div>
    </section>
  </div>
</main>

<footer class="footer" role="contentinfo">
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
          <li><a class="footer__link" href="privacy.php">Политика обработки данных</a></li>
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
document.addEventListener('DOMContentLoaded', function() {
  const toTopBtn = document.getElementById('toTopBtn');

  if (toTopBtn) {
    window.addEventListener('scroll', function() {
      if (window.pageYOffset > 300) {
        toTopBtn.style.display = 'flex';
      } else {
        toTopBtn.style.display = 'none';
      }
    });

    toTopBtn.addEventListener('click', function() {
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    });
  }

  const newsletterForm = document.querySelector('[data-newsletter-form]');
  if (newsletterForm) {
    newsletterForm.addEventListener('submit', function(e) {
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
          <?= htmlspecialchars($_SESSION['auth_error'], ENT_QUOTES, 'UTF-8') ?>
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