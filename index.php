<?php
session_start();
$isAuth = isset($_SESSION['user_id']);
$hasAuthError = !empty($_SESSION['auth_error']);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/php/db.php';

$isAuth = isset($_SESSION['user_id']);

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function productImageUrl(?string $path): string
{
    $path = trim((string)$path);

    if ($path === '') {
        return '/souvenir_shop/img/placeholder.webp';
    }

    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        return $path;
    }

    $path = preg_replace('#^(\./|\.\./)+#', '', $path);
    $path = ltrim($path, '/');

    return '/souvenir_shop/' . $path;
}

$giftCategoryMeta = [
    'ceramics' => ['title' => 'Керамика', 'icon' => '🏺'],
    'postcards' => ['title' => 'Открытки', 'icon' => '💌'],
    'candles' => ['title' => 'Свечи', 'icon' => '🕯️'],
    'textile' => ['title' => 'Текстиль', 'icon' => '🧵'],
    'decor' => ['title' => 'Декор', 'icon' => '🪵'],
];

$giftCategories = array_keys($giftCategoryMeta);
$giftPlaceholders = implode(',', array_fill(0, count($giftCategories), '?'));

$sqlGift = "
    SELECT product_code, category, name, price, meta, in_stock
    FROM products
    WHERE category IN ($giftPlaceholders)
      AND in_stock > 0
    ORDER BY category, name
";
$stmtGift = $pdo->prepare($sqlGift);
$stmtGift->execute($giftCategories);
$giftProductsRaw = $stmtGift->fetchAll(PDO::FETCH_ASSOC);

$giftProducts = [];
foreach ($giftProductsRaw as $item) {
    $giftProducts[$item['category']][] = $item;
}
?>
<!doctype html>
<html lang="ru" data-auth="<?php echo $isAuth ? '1' : '0'; ?>" data-base="../">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Лавка — сувениры ручной работы</title>
  <meta name="description" content="Сувениры ручной работы: керамика, свечи, текстиль, декор, открытки и подарочные наборы." />
  <link rel="stylesheet" href="css/main.css"/>
  <link rel="stylesheet" href="css/cart.css"/>
  <link rel="stylesheet" href="css/style.css"/>
</head>
<body>

<header class="nav" role="banner">
  <div class="container nav__inner">
    <a class="brand" href="index.php" aria-label="Лавка - вернуться на главную страницу">
      <div class="brand__mark" aria-hidden="true"><img src="img/placeholder.webp" alt="Логотип"></div>
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
      <a class="nav__link" href="index.php">Главная</a>
      <a class="nav__link" href="pages/catalog.php">Каталог</a>

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
                <a class="mega__card" href="pages/catalog.php#group-candles" role="menuitem" data-close-mega>
                  <div class="mega__cardTitle">Свечи</div>
                  <div class="mega__cardText">Интерьерные, ароматные, необычные</div>
                </a>

                <a class="mega__card" href="pages/catalog.php#group-ceramics" role="menuitem" data-close-mega>
                  <div class="mega__cardTitle">Керамика</div>
                  <div class="mega__cardText">Кружки, тарелки, миски, фигурки</div>
                </a>

                <a class="mega__card" href="pages/catalog.php#group-decor" role="menuitem" data-close-mega>
                  <div class="mega__cardTitle">Декор</div>
                  <div class="mega__cardText">Фигурки, вазы, подсвечники</div>
                </a>

                <a class="mega__card" href="pages/catalog.php#group-textile" role="menuitem" data-close-mega>
                  <div class="mega__cardTitle">Текстиль</div>
                  <div class="mega__cardText">Игрушки, мешочки, панно, шарфы</div>
                </a>

                <a class="mega__card" href="pages/catalog.php#group-postcards" role="menuitem" data-close-mega>
                  <div class="mega__cardTitle">Открытки</div>
                  <div class="mega__cardText">Авторские, минимал, наборы</div>
                </a>

                <a class="mega__card" href="pages/catalog.php#group-sets" role="menuitem" data-close-mega>
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
                <a class="btn btn--dark btn--sm" href="pages/catalog.php#collectionsNav">Открыть</a>
              </div>

              <div class="mega__preview"
                   role="img"
                   aria-label="Подарочный набор из свечи и керамической кружки"
                   data-bg="img/mega-preview.webp">
              </div>

              <div class="mega__note">Быстрая навигация и фильтры — сверху каталога.</div>
            </div>
          </div>
        </div>
      </div>

      <a class="nav__link" href="pages/about.php">О компании</a>

      <div class="nav__actions">
        <!-- 🔑 ИКОНКА АККАУНТА -->
        <?php if ($isAuth): ?>
          <a class="iconBtn iconBtn--auth"
             href="pages/account.php"
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

        <a class="btn btn--dark btn--sm hide-sm" href="pages/cart.php">Корзина</a>
      </div>
    </nav>
  </div>
</header>

<main>

<!-- HERO -->
<section class="container section hero">
  <div class="hero__wrap reveal" id="hero">
    <div class="hero__slides" id="heroSlides" aria-live="polite">

      <!-- SLIDE 1 — О КОМПАНИИ -->
      <article class="hero__slide is-active">
        <div class="hero__bg" style="background-image:url('img/slide1.webp');"></div>
        <div class="hero__veil"></div>

        <button class="hero__tap hero__tap--prev" type="button" aria-label="Предыдущий слайд"></button>
        <button class="hero__tap hero__tap--next" type="button" aria-label="Следующий слайд"></button>

        <div class="hero__content">
          <div class="hero__badge hero__badge--hit">🤍 О мастерской</div>
          <p class="kicker">Небольшие партии • натуральные материалы • ручная работа</p>

          <h1 class="h1">Лавка — сувениры, которые хочется дарить</h1>

          <p class="lead">
            Мы создаём авторские сувениры вручную: керамику, свечи, деревянный декор, текстиль и открытки.
            Каждая вещь продумана до мелочей и создаётся с заботой.
          </p>

          <div class="rowBtns">
            <a class="btn btn--dark" href="pages/about.php">О мастерской</a>
            <a class="btn btn--outline" href="#materials">Материалы и уход</a>
          </div>

          <div class="hero__stats">
            <div class="hero__stat">
              <span class="hero__stat-number">100%</span>
              <span class="hero__stat-label">ручная работа</span>
            </div>
            <div class="hero__stat">
              <span class="hero__stat-number">12 лет</span>
              <span class="hero__stat-label">работы</span>
            </div>
          </div>
        </div>
      </article>

      <!-- SLIDE 2 — ПОДАРОЧНЫЕ НАБОРЫ -->
      <article class="hero__slide">
        <div class="hero__bg" style="background-image:url('img/slide2.webp');"></div>
        <div class="hero__veil"></div>

        <button class="hero__tap hero__tap--prev" type="button" aria-label="Предыдущий слайд"></button>
        <button class="hero__tap hero__tap--next" type="button" aria-label="Следующий слайд"></button>

        <div class="hero__content">
          <div class="hero__badge">🎁 Подарочные наборы</div>
          <p class="kicker">Готовые боксы • красивая упаковка • открытка в комплекте</p>

          <h1 class="h1">Подарки уже собраны — остаётся выбрать</h1>

          <p class="lead">
            Мы собрали подарочные наборы для дома, уюта и особых случаев.
            Каждый бокс аккуратно упакован и готов к вручению.
          </p>

          <div class="rowBtns">
            <a class="btn btn--dark" href="pages/catalog.php#group-sets">Смотреть наборы</a>
            <a class="btn btn--outline" href="index.php#gift">Собрать свой набор</a>
          </div>

          <div class="hero__stats">
            <div class="hero__stat">
              <span class="hero__stat-number">4</span>
              <span class="hero__stat-label">готовых варианта</span>
            </div>
            <div class="hero__stat">
              <span class="hero__stat-number">50+</span>
              <span class="hero__stat-label">кастомных наборов</span>
            </div>
          </div>
        </div>
      </article>

      <!-- SLIDE 3 — ПЕРСОНАЛИЗАЦИЯ -->
      <article class="hero__slide">
        <div class="hero__bg" style="background-image:url('img/slide3.webp');"></div>
        <div class="hero__veil"></div>

        <button class="hero__tap hero__tap--prev" type="button" aria-label="Предыдущий слайд"></button>
        <button class="hero__tap hero__tap--next" type="button" aria-label="Следующий слайд"></button>

        <div class="hero__content">
          <div class="hero__badge">✍️ Персонализация</div>
          <p class="kicker">Гравировка • имя • дата • пожелание</p>

          <h1 class="h1">Сделайте подарок по-настоящему личным</h1>

          <p class="lead">
            На некоторых сувенирах можно добавить имя, дату или короткое сообщение.
            Мы аккуратно наносим гравировку и согласовываем детали перед изготовлением.
          </p>

          <div class="rowBtns">
            <a class="btn btn--dark" href="pages/catalog.php#personalGift">Выбрать с гравировкой</a>
            <a class="btn btn--outline" href="pages/catalog.php#personalGift">Как это работает</a>
          </div>

          <div class="hero__stats">
            <div class="hero__stat">
              <span class="hero__stat-number">20+</span>
              <span class="hero__stat-label">вариантов</span>
            </div>
            <div class="hero__stat">
              <span class="hero__stat-number">1–2</span>
              <span class="hero__stat-label">дня изготовления</span>
            </div>
          </div>
        </div>
      </article>

    </div>

    <!-- НИЖНЯЯ ПАНЕЛЬ -->
    <div class="hero__controls">
      <div class="hero__dots" id="heroDots" aria-label="Переключение слайдов"></div>
    </div>
  </div>
</section>

<!-- COLLECTIONS -->
<section id="collections" class="container section">
  <div class="headRow reveal">
    <div>
      <h2 class="h2">Категории</h2>
      <p class="muted">Подборки авторских сувениров для дома и подарков.</p>
    </div>
    <a class="btn" href="pages/catalog.php">Смотреть все</a>
  </div>

  <div class="grid3">

    <!-- Керамика -->
    <a class="tile reveal" href="pages/catalog.php#c#group-ceramics">
      <div class="tile__img" style="background-image:url('img/ceramic.webp');"></div>
      <div class="tile__overlay">
        <div class="tile__title">Керамика</div>
        <div class="tile__sub">кружки • тарелки • фигурки</div>
      </div>
    </a>

    <!-- Открытки -->
    <a class="tile reveal" href="pages/catalog.php#group-postcards">
      <div class="tile__img" style="background-image:url('img/letter.webp');"></div>
      <div class="tile__overlay">
        <div class="tile__title">Открытки</div>
        <div class="tile__sub">акварель • авторские иллюстрации</div>
      </div>
    </a>

    <!-- Свечи -->
    <a class="tile reveal" href="pages/catalog.php#group-candles">
      <div class="tile__img" style="background-image:url('img/candle.webp');"></div>
      <div class="tile__overlay">
        <div class="tile__title">Свечи</div>
        <div class="tile__sub">соевые • ароматические • декор</div>
      </div>
    </a>

    <!-- Текстиль -->
    <a class="tile reveal" href="pages/catalog.php#group-textile">
      <div class="tile__img" style="background-image:url('img/textile.webp');"></div>
      <div class="tile__overlay">
        <div class="tile__title">Текстиль</div>
        <div class="tile__sub">игрушки • вышивка • аксессуары</div>
      </div>
    </a>

    <!-- Декор -->
    <a class="tile reveal" href="pages/catalog.php#group-decor">
      <div class="tile__img" style="background-image:url('img/decor.webp');"></div>
      <div class="tile__overlay">
        <div class="tile__title">Декор</div>
        <div class="tile__sub">фигурки • вазы • интерьер</div>
      </div>
    </a>

    <!-- Подарочные наборы -->
    <a class="tile reveal" href="pages/catalog.php#group-sets">
      <div class="tile__img" style="background-image:url('img/box.webp');"></div>
      <div class="tile__overlay">
        <div class="tile__title">Подарочные наборы</div>
        <div class="tile__sub">свечи • керамика • открытки</div>
      </div>
    </a>

  </div>
</section>

<!-- ХИТЫ ПРОДАЖ -->
<?php
$stmtHits = $pdo->prepare("
    SELECT *
    FROM products
    WHERE badge = 'hit'
    ORDER BY id ASC
");
$stmtHits->execute();
$hits = $stmtHits->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="hits reveal" id="hits" aria-labelledby="hits-title" data-filter-exclude>
  <div class="container">
    <div class="catalog-head">
      <div>
        <h2 id="hits-title" class="h2">Хиты продаж</h2>
        <p class="lead">Самые популярные товары — чаще всего выбирают в подарок.</p>
      </div>
    </div>

    <div class="grid4" role="list">
      <?php foreach ($hits as $hit): ?>
        <?php
          $hitImg = productImageUrl($hit['image'] ?? '');
          $hitAvailable = ((int)($hit['in_stock'] ?? 0)) > 0;
          $hitCode = $hit['product_code'] ?? '';
        ?>
        <div class="reveal"
             data-product
             data-category="<?php echo h($hit['category'] ?? ''); ?>"
             data-id="<?php echo h($hitCode); ?>"
             data-name="<?php echo h($hit['name'] ?? ''); ?>"
             role="listitem">
          <div class="card">
            <div class="card__img"
                 role="img"
                 aria-label="<?php echo h($hit['name'] ?? 'Товар'); ?>"
                 data-bg="<?php echo h($hitImg); ?>">
              <span class="pbadge pbadge--hit">Хит</span>
            </div>

            <div class="card__body">
              <div class="card__top">
                <div>
                  <h3 class="card__title"><?php echo h($hit['name'] ?? ''); ?></h3>
                  <div class="card__meta"><?php echo h($hit['meta'] ?? ''); ?></div>
                </div>

                <div class="card__price">
                  <span class="price-amount"><?php echo number_format((float)($hit['price'] ?? 0), 0, ',', ' '); ?></span> ₽
                </div>
              </div>

              <div class="card__actions">
                <?php if ($hitAvailable): ?>
                  <button class="btn btn--dark btn--full"
                          type="button"
                          data-add-to-cart
                          data-product-id="<?php echo h($hitCode); ?>"
                          data-product-name="<?php echo h($hit['name'] ?? ''); ?>"
                          data-product-price="<?php echo (int)($hit['price'] ?? 0); ?>"
                          data-product-img="<?php echo h($hitImg); ?>">
                    В корзину
                  </button>

                  <div class="qty qty--card" data-qty-wrap="<?php echo h($hitCode); ?>" style="display:none;">
                    <button class="qty__btn" type="button" aria-label="Уменьшить количество" data-qty-minus="<?php echo h($hitCode); ?>">−</button>
                    <span class="qty__val" id="cardQty-<?php echo h($hitCode); ?>">1</span>
                    <button class="qty__btn" type="button" aria-label="Увеличить количество" data-qty-plus="<?php echo h($hitCode); ?>">+</button>
                  </div>
                <?php else: ?>
                  <button class="btn btn--dark btn--full" type="button" disabled>
                    Нет в наличии
                  </button>
                <?php endif; ?>

                <button class="iconBtn"
                        type="button"
                        aria-label="Добавить <?php echo h($hit['name'] ?? ''); ?> в избранное"
                        aria-pressed="false"
                        data-fav-btn
                        data-product-id="<?php echo h($hitCode); ?>"
                        data-product-name="<?php echo h($hit['name'] ?? ''); ?>"
                        data-product-price="<?php echo (int)($hit['price'] ?? 0); ?>"
                        data-product-img="<?php echo h($hitImg); ?>">
                  <svg class="favorites-icon" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"
                          fill="none"
                          stroke="currentColor"
                          stroke-width="1.6"/>
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- BANNER -->
<section id="materials" class="container section">
  <div class="banner reveal">
    <div class="banner__img" style="background-image:url('img/materials-banner.webp');"></div>
    <div class="banner__body">
      <p class="kicker">материалы и уход</p>
      <h2 class="h2">Натуральные фактуры — и понятные правила ухода</h2>
      <p class="lead">
        Дерево покрываем воском, керамику обжигаем и глазуруем, свечи льём из соевого воска.
        В карточках товара — рекомендации, чтобы подарок радовал дольше.
      </p>
      <div class="rowBtns">
        <a class="btn btn--dark" href="pages/about.php">О мастерской</a>
        <a class="btn" href="pages/catalog.php">Выбрать подарок</a>
      </div>
    </div>
  </div>
</section>

<!-- TRUST -->
<section id="delivery" class="container section section--sm">
  <div class="trust reveal" aria-label="Преимущества сервиса">
    <div class="trust__item">
      <div class="trust__icon" aria-hidden="true">
        <svg viewBox="0 0 24 24">
          <path d="M12 20v-6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
          <path d="M8 10v6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
          <path d="M16 10v6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
          <path d="M6 10c0-2 2-4 6-4s6 2 6 4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
        </svg>
      </div>
      <div>
        <div class="trust__title">Ручная работа</div>
        <div class="trust__text">Каждый сувенир создаётся вручную, без массового производства</div>
      </div>
    </div>

    <div class="trust__item">
      <div class="trust__icon" aria-hidden="true">
        <svg viewBox="0 0 24 24">
          <path d="M20 7v13H4V7" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
          <path d="M2 7h20" fill="none" stroke="currentColor" stroke-width="1.8"/>
          <path d="M12 7v13" fill="none" stroke="currentColor" stroke-width="1.8"/>
          <path d="M12 7c-1.6 0-3-1-3-2.6S10.6 3 12 5c1.4-2 3-1.6 3 0S13.6 7 12 7z"
                fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
        </svg>
      </div>
      <div>
        <div class="trust__title">Наборы</div>
        <div class="trust__text">Готовые боксы или наборы, собранные специально под повод</div>
      </div>
    </div>

    <div class="trust__item">
      <div class="trust__icon" aria-hidden="true">
        <svg viewBox="0 0 24 24">
          <path d="M3 7h12v10H3z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
          <path d="M15 10h4l2 2v5h-6z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
          <circle cx="7" cy="19" r="1.7" fill="none" stroke="currentColor" stroke-width="1.8"/>
          <circle cx="18" cy="19" r="1.7" fill="none" stroke="currentColor" stroke-width="1.8"/>
        </svg>
      </div>
      <div>
        <div class="trust__title">Доставка</div>
        <div class="trust__text">Доставляем по городу и отправляем в другие регионы</div>
      </div>
    </div>

    <div class="trust__item">
      <div class="trust__icon" aria-hidden="true">
        <svg viewBox="0 0 24 24">
          <path d="M12 20h9" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
          <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"
                fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
        </svg>
      </div>
      <div>
        <div class="trust__title">Персонализация</div>
        <div class="trust__text">Имя, дата или короткое пожелание на изделии</div>
      </div>
    </div>
  </div>
</section>

<section id="gift" class="giftHero">
  <div class="giftHero__bg" aria-hidden="true"></div>
  <div class="giftHero__veil" aria-hidden="true"></div>

  <div class="giftHero__inner container section">
    <div class="giftHero__card reveal">
      <p class="kicker">Лавка / подарочные наборы</p>

      <h2 class="h2">Соберите подарок за 2 минуты</h2>

      <p class="lead">
        Выберите <strong>2–4 позиции</strong> из списка — мы красиво упакуем набор и добавим открытку.
        Подойдёт для дня рождения или уюта для дома.
      </p>

      <form id="giftForm" class="giftForm" action="#" method="post" novalidate>
        <div class="giftForm__head">
          <div class="giftForm__title">Что можно добавить в набор:</div>
          <div class="giftForm__counter">
            Выбрано: <strong><span id="giftPicked">0</span>/4</strong>
          </div>
        </div>

        <div class="giftLists" role="group" aria-label="Выбор позиций для подарка">
          <?php
            $hasGiftItems = false;
            foreach ($giftCategoryMeta as $catKey => $catMeta):
              $items = $giftProducts[$catKey] ?? [];
              if (empty($items)) continue;
              $hasGiftItems = true;
          ?>
            <details class="giftList" <?php echo $catKey === 'ceramics' ? 'open' : ''; ?>>
              <summary class="giftList__summary">
                <span class="giftList__icon" aria-hidden="true"><?php echo h($catMeta['icon']); ?></span>
                <span class="giftList__name"><?php echo h($catMeta['title']); ?></span>
                <span class="giftList__hint"><?php echo count($items); ?> позиций</span>
              </summary>

              <div class="giftList__body">
                <?php foreach ($items as $item): ?>
                  <label class="giftOption">
                    <input
                      type="checkbox"
                      name="giftItems[]"
                      value="<?php echo h($item['product_code']); ?>"
                      data-code="<?php echo h($item['product_code']); ?>"
                      data-name="<?php echo h($item['name']); ?>"
                      data-price="<?php echo (int)$item['price']; ?>"
                    />
                    <span class="giftOption__ui">
                      <span class="giftOption__title">
                        <?php echo h($item['name']); ?>
                        <span class="giftOption__price">
                          <?php echo number_format((float)$item['price'], 0, ',', ' '); ?> ₽
                        </span>
                      </span>
                      <span class="giftOption__meta"><?php echo h($item['meta'] ?? ''); ?></span>
                    </span>
                  </label>
                <?php endforeach; ?>
              </div>
            </details>
          <?php endforeach; ?>

          <?php if (!$hasGiftItems): ?>
            <div class="muted small">Сейчас нет доступных товаров для подарочного набора.</div>
          <?php endif; ?>
        </div>

        <div class="giftPicked">
          <div class="giftPicked__head">
            <div class="giftPicked__title">Вы выбрали:</div>
            <button id="giftClearAll" type="button" class="btn btn--outline btn--sm">Очистить</button>
          </div>

          <div id="giftPickedTags" class="giftPicked__tags">
            <span class="muted small">Пока ничего не выбрано.</span>
          </div>

          <div class="giftPicked__footer">
            <div class="giftPicked__summary">
              <div id="giftNote" class="giftPicked__note">Выберите минимум 2 позиции</div>

              <div id="giftTotals" class="giftPicked__totals" style="display:none;">
                <span id="giftFullSum" class="giftPicked__full"></span>
                <strong id="giftDiscountSum" class="giftPicked__discount"></strong>
              </div>
            </div>

            <button id="giftSubmit" type="button" class="btn btn--dark" disabled>
              Оформить заказ
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</section>
</main>
<!-- FOOTER -->
<footer class="footer" role="contentinfo">
  <button class="to-top" id="toTopBtn" aria-label="Вернуться наверх" style="display: none;">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <polyline points="18 15 12 9 6 15"></polyline>
    </svg>
  </button>

  <div class="container">
    <div class="footer__grid">
      <div>
        <a href="index.php" class="footer__brand-link">
          <div class="footer__brand">
            <div class="brand__mark" aria-hidden="true">
              <img src="img/placeholder.webp" alt="Логотип Лавка">
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
          <li><a class="footer__link" href="index.php">Главная</a></li>
          <li><a class="footer__link" href="pages/about.php">О компании</a></li>
          <li><a class="footer__link" href="pages/catalog.php">Каталог</a></li>
          <li><a class="footer__link" href="pages/registration.php">Регистрация</a></li>
        </ul>
      </div>

      <div>
        <h3 class="footer__title">Информация</h3>
        <ul class="footer__list">
          <li><a class="footer__link" href="pages/about.php#delivery">Доставка</a></li>
          <li><a class="footer__link" href="pages/about.php#returns">Возврат</a></li>
          <li><a class="footer__link" href="pages/about.php#materials">Материалы</a></li>
          <li><a class="footer__link" href="pages/about.php#contacts">Контакты</a></li>
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

    window.addEventListener('scroll', function() {
      if (window.pageYOffset > 300) {
        toTopBtn.style.display = 'flex';
      } else {
        toTopBtn.style.display = 'none';
      }
    });

    toTopBtn.addEventListener('click', function() {
      window.scrollTo({ top: 0, behavior: 'smooth' });
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

      <div class="authNote" id="authNote" hidden></div>

      <?php if (!empty($_SESSION['auth_error'])): ?>
        <div class="alert alert--error" style="color:#b00020; margin-bottom:10px;">
          <?= htmlspecialchars($_SESSION['auth_error']) ?>
        </div>
        <?php unset($_SESSION['auth_error']); ?>
      <?php endif; ?>

      <form action="php/auth.php" method="post" class="needs-validation" novalidate>
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
        <a href="pages/registration.php">Зарегистрироваться</a>
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

<script src="js/script.js" defer></script>
<script src="js/cart.js" defer></script>
<script src="js/product.js" defer></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('giftForm');
  if (!form) return;

  const checkboxes = Array.from(form.querySelectorAll('input[name="giftItems[]"]'));
  const pickedCount = document.getElementById('giftPicked');
  const pickedTags = document.getElementById('giftPickedTags');
  const giftNote = document.getElementById('giftNote');
  const giftTotals = document.getElementById('giftTotals');
  const giftFullSum = document.getElementById('giftFullSum');
  const giftDiscountSum = document.getElementById('giftDiscountSum');
  const clearBtn = document.getElementById('giftClearAll');
  const submitBtn = document.getElementById('giftSubmit');

  const CREATE_GIFT_CHECKOUT_URL = '/souvenir_shop/php/create_gift_checkout.php';

  function formatPrice(value) {
    return new Intl.NumberFormat('ru-RU').format(value) + ' ₽';
  }

  function getSelected() {
    return checkboxes
      .filter(cb => cb.checked)
      .map(cb => ({
        code: cb.dataset.code,
        name: cb.dataset.name,
        price: parseInt(cb.dataset.price || '0', 10)
      }));
  }

  function syncLimitState(selectedCount) {
    const limitReached = selectedCount >= 4;

    checkboxes.forEach(cb => {
      if (!cb.checked) {
        cb.disabled = limitReached;
      }
    });
  }

  function renderTags(selected) {
    if (!selected.length) {
      pickedTags.innerHTML = '<span class="muted small">Пока ничего не выбрано.</span>';
      return;
    }

    pickedTags.innerHTML = selected.map(item => `
      <span class="giftPicked__tag">
        <span>${item.name}</span>
        <span class="giftPicked__tagPrice">${formatPrice(item.price)}</span>
        <button type="button" class="giftPicked__tagRemove" data-remove-gift="${item.code}" aria-label="Удалить ${item.name}">×</button>
      </span>
    `).join('');
  }

  function updateGiftBlock() {
    const selected = getSelected();
    const count = selected.length;

    pickedCount.textContent = count;
    renderTags(selected);
    syncLimitState(count);

    if (count < 2) {
      giftNote.textContent = 'Выберите минимум 2 позиции';
      giftTotals.style.display = 'none';
      submitBtn.disabled = true;
      return;
    }

    const fullSum = selected.reduce((sum, item) => sum + item.price, 0);
    const discountSum = Math.round(fullSum * 0.95);

    giftNote.textContent = 'Скидка на набор 5% и подарочная коробка включена';
    giftFullSum.textContent = formatPrice(fullSum);
    giftDiscountSum.textContent = formatPrice(discountSum);
    giftTotals.style.display = 'flex';
    submitBtn.disabled = false;
  }

  checkboxes.forEach(cb => {
    cb.addEventListener('change', function () {
      const selected = getSelected();

      if (selected.length > 4) {
        this.checked = false;
        return;
      }

      updateGiftBlock();
    });
  });

  clearBtn.addEventListener('click', function () {
    checkboxes.forEach(cb => {
      cb.checked = false;
      cb.disabled = false;
    });
    updateGiftBlock();
  });

  pickedTags.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-remove-gift]');
    if (!btn) return;

    const code = btn.getAttribute('data-remove-gift');
    const checkbox = checkboxes.find(cb => cb.dataset.code === code);
    if (checkbox) {
      checkbox.checked = false;
      checkbox.disabled = false;
      updateGiftBlock();
    }
  });

  submitBtn.addEventListener('click', async function () {
    const selected = getSelected();

    if (selected.length < 2 || selected.length > 4) {
      updateGiftBlock();
      return;
    }

    if (document.documentElement.dataset.auth !== '1') {
      if (typeof window.openAuthModalWithMessage === 'function') {
        window.openAuthModalWithMessage('Чтобы оформить подарочный набор, сначала войдите в аккаунт.');
      } else {
        const authBtn = document.querySelector('[data-open-modal="authModal"]');
        if (authBtn) authBtn.click();
      }
      return;
    }

    submitBtn.disabled = true;
    const oldText = submitBtn.textContent;
    submitBtn.textContent = 'Формируем заказ...';

    try {
      const res = await fetch(CREATE_GIFT_CHECKOUT_URL, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin',
        body: JSON.stringify({
          items: selected.map(item => item.code)
        })
      });

      const json = await res.json().catch(() => ({}));

      if (!res.ok || !json.success) {
        throw new Error(json.error || 'CREATE_GIFT_CHECKOUT_FAILED');
      }

      window.location.href = json.redirect || '/souvenir_shop/pages/checkout.php?mode=gift';
    } catch (error) {
      console.error(error);
      alert('Не удалось оформить подарочный набор. Попробуйте ещё раз.');
      submitBtn.disabled = false;
      submitBtn.textContent = oldText;
    }
  });

  updateGiftBlock();
});
</script>

</body>
</html>