<?php
session_start();
$isAuth = isset($_SESSION['user_id']);
$hasAuthError = !empty($_SESSION['auth_error']);

require_once '../php/db.php';

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function getDbConnection(): PDO|mysqli|null
{
    foreach (['pdo', 'db', 'conn', 'mysqli', 'link'] as $key) {
        if (!isset($GLOBALS[$key])) {
            continue;
        }

        $candidate = $GLOBALS[$key];

        if ($candidate instanceof PDO || $candidate instanceof mysqli) {
            return $candidate;
        }
    }

    return null;
}

function dbFetchAll(string $sql): array
{
    $db = getDbConnection();

    if ($db instanceof PDO) {
        $stmt = $db->query($sql);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    if ($db instanceof mysqli) {
        $result = $db->query($sql);
        if (!$result) {
            return [];
        }

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    return [];
}

function productImageUrl(?string $path): string
{
    $path = trim((string)$path);

    if ($path === '') {
        return '../img/placeholder.webp';
    }

    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        return $path;
    }

    if (str_starts_with($path, '../img/')) {
        return $path;
    }

    if (str_starts_with($path, './img/')) {
        return '../' . substr($path, 2);
    }

    if (str_starts_with($path, 'img/')) {
        return '../' . $path;
    }

    if (str_starts_with($path, '/')) {
        return $path;
    }

    return '../' . ltrim($path, '/');
}

function badgeClass(?string $badge): ?string
{
    return match ($badge) {
        'hit' => 'pbadge pbadge--hit',
        'new' => 'pbadge pbadge--new',
        default => null,
    };
}

function badgeText(?string $badge): ?string
{
    return match ($badge) {
        'hit' => 'Хит',
        'new' => 'Новинка',
        default => null,
    };
}

function formatRubles(int|string|null $price): string
{
    return number_format((int)$price, 0, '', ' ');
}

function renderProductCard(array $product): void
{
    $productCode = e((string)($product['product_code'] ?? ''));
    $category = e((string)($product['category'] ?? ''));
    $name = e((string)($product['name'] ?? ''));
    $meta = e((string)($product['meta'] ?? ''));
    $price = (int)($product['price'] ?? 0);
    $image = e(productImageUrl($product['image'] ?? ''));
    $badgeCls = badgeClass($product['badge'] ?? null);
    $badgeLbl = badgeText($product['badge'] ?? null);
    $ariaLabel = trim((string)($product['name'] ?? '') . ' ' . (string)($product['meta'] ?? ''));
    $ariaLabel = e($ariaLabel !== '' ? $ariaLabel : (string)($product['name'] ?? 'Товар'));

    ?>
<div class="reveal" data-product data-category="<?= $category ?>"
  data-id="<?= $productCode ?>"
  data-name="<?= $name ?>" role="listitem">
  <div class="card">
    <div class="card__img" role="img" aria-label="<?= $ariaLabel ?>"
      data-bg="<?= $image ?>">
      <?php if ($badgeCls && $badgeLbl): ?>
      <span
        class="<?= e($badgeCls) ?>"><?= e($badgeLbl) ?></span>
      <?php endif; ?>
    </div>

    <div class="card__body">
      <div class="card__top">
        <div>
          <h3 class="card__title"><?= $name ?></h3>
          <div class="card__meta"><?= $meta ?></div>
        </div>

        <div class="card__price">
          <span
            class="price-amount"><?= formatRubles($price) ?></span> ₽
        </div>
      </div>

      <div class="card__actions">
        <button class="btn btn--dark btn--full" type="button" data-add-to-cart
          data-product-id="<?= $productCode ?>"
          data-product-name="<?= $name ?>"
          data-product-price="<?= $price ?>"
          data-product-img="<?= $image ?>">
          В корзину
        </button>

        <div class="qty qty--card"
          data-qty-wrap="<?= $productCode ?>" style="display:none;">
          <button class="qty__btn" type="button" aria-label="Уменьшить количество"
            data-qty-minus="<?= $productCode ?>">−</button>
          <span class="qty__val">1</span>
          <button class="qty__btn" type="button" aria-label="Увеличить количество"
            data-qty-plus="<?= $productCode ?>">+</button>
        </div>

        <button class="iconBtn" type="button"
          aria-label="Добавить <?= $name ?> в избранное"
          aria-pressed="false" data-fav-btn
          data-product-id="<?= $productCode ?>"
          data-product-name="<?= $name ?>"
          data-product-price="<?= $price ?>"
          data-product-img="<?= $image ?>">
          <svg class="favorites-icon" viewBox="0 0 24 24" aria-hidden="true">
            <path
              d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"
              fill="none" stroke="currentColor" stroke-width="1.6" />
          </svg>
        </button>
      </div>
    </div>
  </div>
</div>
<?php
}

function renderCategorySection(string $categoryCode, string $title, string $desc, array $products): void
{
    ?>
<section class="group-block group-block--hero reveal"
  id="group-<?= e($categoryCode) ?>"
  data-group="<?= e($categoryCode) ?>"
  aria-labelledby="<?= e($categoryCode) ?>-title">
  <div class="group-head">
    <div>
      <h3 id="<?= e($categoryCode) ?>-title" class="group-title">
        <?= e($title) ?></h3>
      <p class="group-desc"><?= e($desc) ?></p>
    </div>
  </div>

  <div class="grid4" role="list"
    id="<?= e($categoryCode) ?>-products">
    <?php if (!empty($products)): ?>
    <?php foreach ($products as $product): ?>
    <?php renderProductCard($product); ?>
    <?php endforeach; ?>
    <?php else: ?>
    <div class="muted" style="grid-column:1/-1;">Пока нет товаров в этой категории.</div>
    <?php endif; ?>
  </div>
</section>
<?php
}

$categoryTitles = [
    'ceramics' => [
        'title' => 'Керамика',
        'desc'  => 'Ручная лепка, приятные формы и спокойные оттенки — для ежедневных маленьких ритуалов.',
    ],
    'postcards' => [
        'title' => 'Открытки',
        'desc'  => 'Тёплые слова в красивой форме — идеально как самостоятельный подарок или дополнение к набору.',
    ],
    'candles' => [
        'title' => 'Свечи',
        'desc'  => 'Интерьерные и ароматные свечи — для настроения и уюта.',
    ],
    'textile' => [
        'title' => 'Текстиль',
        'desc'  => 'Мягкие и тёплые вещи ручной работы: игрушки, мешочки, панно и шарфы.',
    ],
    'decor' => [
        'title' => 'Декор',
        'desc'  => 'Небольшие элементы для полок и столов: фигурки, вазы и подсвечники.',
    ],
    'sets' => [
        'title' => 'Подарочные наборы',
        'desc'  => 'Красиво упакованные боксы — можно дарить сразу, без лишних забот.',
    ],
];

$allProducts = dbFetchAll("
    SELECT *
    FROM products
    ORDER BY
      FIELD(category, 'ceramics', 'postcards', 'candles', 'textile', 'decor', 'sets'),
      CASE
        WHEN badge = 'hit' THEN 0
        WHEN badge = 'new' THEN 1
        ELSE 2
      END,
      id ASC
");

$groupedProducts = [];
foreach ($allProducts as $product) {
    $category = (string)($product['category'] ?? '');
    if ($category !== '') {
        $groupedProducts[$category][] = $product;
    }
}

$hitProducts = array_values(array_filter(
    $allProducts,
    fn ($product) => ($product['badge'] ?? null) === 'hit'
));
$hitProducts = array_slice($hitProducts, 0, 4);
?>
<?php
$basePath = '..';
require_once __DIR__ . '/../includes/layout.php';

renderHead(
    'Каталог сувениров ручной работы — Лавка',
    'Каталог сувениров ручной работы: керамика, свечи, текстиль, декор и открытки. Подарочные наборы и персонализация.',
    [
        'css/style.css',
        'css/main.css',
        'css/catalog.css',
        'css/cart.css'
    ]
);

renderHeader();
?>
<main class="container section" id="main-content" role="main" tabindex="-1">

  <nav class="breadcrumbs" aria-label="Хлебные крошки">
    <ol>
      <li><a href="../index.php">Главная</a></li>
      <li><span aria-current="page">Каталог</span></li>
    </ol>
  </nav>

  <header class="page-header">
    <h1 class="h1">Каталог сувениров ручной работы</h1>
    <p class="lead">Уникальные подарки и предметы интерьера для вашего уюта</p>
  </header>

  <section id="home" class="section section--sm" aria-labelledby="for-home-title">
    <div class="grid3 mb-14" role="list">
      <a class="tile reveal" href="#group-ceramics" role="listitem">
        <div class="tile__img" role="img" aria-label="Керамические изделия ручной работы" data-bg="../img/ceramic.webp">
        </div>
        <div class="tile__overlay">
          <div class="tile__title">Керамика</div>
          <div class="tile__sub">кружки • тарелки • фигурки</div>
        </div>
      </a>

      <a class="tile reveal" href="#group-postcards" role="listitem">
        <div class="tile__img" role="img" aria-label="Открытки ручной работы с акварельными рисунками"
          data-bg="../img/letter.webp"></div>
        <div class="tile__overlay">
          <div class="tile__title">Открытки</div>
          <div class="tile__sub">акварель • авторские</div>
        </div>
      </a>

      <a class="tile reveal" href="#group-candles" role="listitem">
        <div class="tile__img" role="img" aria-label="Ароматические свечи из соевого воска"
          data-bg="../img/candle.webp"></div>
        <div class="tile__overlay">
          <div class="tile__title">Свечи</div>
          <div class="tile__sub">соевые • ароматные</div>
        </div>
      </a>

      <a class="tile reveal" href="#group-textile" role="listitem">
        <div class="tile__img" role="img" aria-label="Текстильные изделия и мягкие игрушки"
          data-bg="../img/textile.webp"></div>
        <div class="tile__overlay">
          <div class="tile__title">Текстиль</div>
          <div class="tile__sub">игрушки • вышивка</div>
        </div>
      </a>

      <a class="tile reveal" href="#group-decor" role="listitem">
        <div class="tile__img" role="img" aria-label="Декор для интерьера и фигурки" data-bg="../img/decor.webp"></div>
        <div class="tile__overlay">
          <div class="tile__title">Декор</div>
          <div class="tile__sub">фигурки • вазы</div>
        </div>
      </a>

      <a class="tile reveal" href="#group-sets" role="listitem">
        <div class="tile__img" role="img" aria-label="Подарочные наборы в красивой упаковке" data-bg="../img/box.webp">
        </div>
        <div class="tile__overlay">
          <div class="tile__title">Подарочные наборы</div>
          <div class="tile__sub">свечи • керамика • открытки</div>
        </div>
      </a>
    </div>

    <section class="hits reveal" id="hits" aria-labelledby="hits-title" data-filter-exclude>
      <div class="catalog-head">
        <div>
          <h2 id="hits-title" class="h2">Хиты продаж</h2>
          <p class="lead">Самые популярные товары — чаще всего выбирают в подарок.</p>
        </div>
      </div>

      <div class="grid4" role="list">
        <?php if (!empty($hitProducts)): ?>
        <?php foreach ($hitProducts as $product): ?>
        <?php renderProductCard($product); ?>
        <?php endforeach; ?>
        <?php else: ?>
        <div class="muted" style="grid-column:1/-1;">Хиты пока не добавлены.</div>
        <?php endif; ?>
      </div>
    </section>

    <section class="personal-gift reveal" id="personalGift" aria-labelledby="personal-gift-title">
      <div class="personal-gift__bg" role="img" aria-label="Персонализированный подарок с гравировкой имени"
        data-bg="../img/personal-gift.webp"></div>

      <div class="personal-gift__veil" aria-hidden="true"></div>

      <div class="personal-gift__inner container">
        <div class="personal-gift__content">
          <p class="personal-gift__top">ПЕРСОНАЛИЗАЦИЯ • ИМЯ • ДАТА</p>

          <h2 id="personal-gift-title" class="personal-gift__title">
            Персональный подарок:<br />
            добавим имя или пожелание
          </h2>

          <p class="personal-gift__text">
            Можно оставить заявку на индивидуальное оформление,
            а мы свяжемся с вами и подскажем, что лучше подойдёт.
            Срок изготовления — <strong>от 1 до 5 дней</strong>.
          </p>

          <ul class="personal-gift__bullets" aria-label="Что можно сделать">
            <li>Имя или короткая фраза</li>
            <li>Дата и инициалы</li>
            <li>Пожелание на открытке</li>
          </ul>
        </div>

        <div class="personal-gift__formCard" role="region" aria-label="Заявка на персонализацию">
          <div class="pgForm__head">
            <div>
              <div class="pgForm__kicker">Заявка за 1 минуту</div>
              <h3 class="pgForm__title">Хочу гравировку</h3>
            </div>
            <div class="pgForm__badge" aria-hidden="true">1–5 дней</div>
          </div>

          <?php if (!empty($_SESSION['personalization_success'])): ?>
          <div class="alert" style="margin: 0 0 14px; color: var(--green, #1f8a4c);">
            <?= htmlspecialchars($_SESSION['personalization_success'], ENT_QUOTES, 'UTF-8') ?>
          </div>
          <?php unset($_SESSION['personalization_success']); ?>
          <?php endif; ?>

          <?php if (!empty($_SESSION['personalization_error'])): ?>
          <div class="alert alert--error" style="margin: 0 0 14px; color:#b00020;">
            <?= htmlspecialchars($_SESSION['personalization_error'], ENT_QUOTES, 'UTF-8') ?>
          </div>
          <?php unset($_SESSION['personalization_error']); ?>
          <?php endif; ?>

          <form class="pgForm" id="engraveForm" action="../php/create_personalization_request.php"
            method="post">
            <div class="pgForm__grid">
              <label class="pgField" for="engraveName">
                <span class="pgField__label">Ваше имя</span>
                <input class="input" id="engraveName" type="text" name="customer_name" maxlength="100"
                  placeholder="Например: Анна" required />
              </label>

              <label class="pgField" for="engraveContact">
                <span class="pgField__label">Телефон</span>
                <input class="input" id="engraveContact" type="tel" name="phone" maxlength="30"
                  placeholder="+7 (999) 000-00-00" required />
              </label>

              <label class="pgField" for="engraveEmail">
                <span class="pgField__label">Email</span>
                <input class="input" id="engraveEmail" type="email" name="email" maxlength="120"
                  placeholder="example@mail.ru" />
              </label>

              <label class="pgField" for="preferredContact">
                <span class="pgField__label">Как с вами связаться?</span>
                <div class="select-wrap">
                  <select class="input" id="preferredContact" name="preferred_contact">
                    <option value="">Выберите способ связи</option>
                    <option value="phone">По телефону</option>
                    <option value="telegram">В Telegram</option>
                    <option value="whatsapp">В WhatsApp</option>
                    <option value="email">По email</option>
                  </select>
                </div>
              </label>

              <label class="pgField pgField--full" for="engraveText">
                <span class="pgField__label">Текст гравировки</span>
                <input class="input" id="engraveText" type="text" name="engraving_text" maxlength="100"
                  placeholder="Например: &quot;Дорогой Ане&quot;" required />
                <span class="pgField__hint">от 2 до 100 символов</span>
              </label>

              <label class="pgField" for="engraveOn">
                <span class="pgField__label">На каком изделии?</span>
                <div class="select-wrap">
                  <select class="input" id="engraveOn" name="item_type" required>
                    <option value="">Выберите изделие</option>
                    <option value="Открытка">Открытка</option>
                    <option value="Кружка">Кружка</option>
                    <option value="Тарелка">Тарелка</option>
                    <option value="Фигурка «Кот»">Фигурка «Кот»</option>
                    <option value="Игрушка «Мишка»">Игрушка «Мишка»</option>
                    <option value="Другое">Другое</option>
                  </select>
                </div>
              </label>

              <label class="pgField" for="engraveDeadline">
                <span class="pgField__label">Срок</span>
                <div class="select-wrap">
                  <select class="input" id="engraveDeadline" name="urgency">
                    <option value="Не срочно">Не срочно (1–5 дней)</option>
                    <option value="Срочно">Как можно быстрее</option>
                    <option value="К определённой дате">К конкретной дате</option>
                  </select>
                </div>
              </label>

              <label class="pgField pgField--full" for="engraveDate" id="engraveDateWrap" style="display:none;">
                <span class="pgField__label">Нужная дата</span>
                <input class="input" id="engraveDate" type="date" name="target_date" />
                <span class="pgField__hint">Заполните, если нужен подарок к определённому дню</span>
              </label>

              <label class="pgField pgField--full" for="engraveComment">
                <span class="pgField__label">Комментарий</span>
                <textarea class="input pgTextarea" id="engraveComment" name="comment" rows="3" maxlength="1000"
                  placeholder="Например: &quot;Нужна надпись на донышке&quot;"></textarea>
              </label>
            </div>

            <div class="pgForm__actions">
              <div class="pgForm__consent" style="margin-bottom:12px;">
                <label style="display:flex; align-items:flex-start; gap:10px; line-height:1.5;">
                  <input type="checkbox" name="privacy_consent" value="1" required style="margin-top:4px;">
                  <span>
                    Я соглашаюсь на
                    <a href="../pages/privacy.php" target="_blank" rel="noopener noreferrer">
                      обработку персональных данных
                    </a>
                  </span>
                </label>
              </div>

              <button type="submit" class="btn btn--dark btn--full" id="engraveSubmitBtn">
                Отправить заявку
              </button>

              <p class="muted small pgForm__note">
                Мы используем указанные данные только для связи по вашей заявке.
              </p>
            </div>
          </form>
        </div>
      </div>
    </section>

    <section class="section section--sm" id="productGroups" aria-label="Группы товаров">
      <section class="filters-bar reveal" id="collectionsNav" aria-labelledby="filters-title">
        <div class="filters-left">
          <h2 id="filters-title" class="visually-hidden">Фильтры каталога</h2>
          <div class="filters-row" id="categoryFilters" role="tablist" aria-label="Категории товаров">
            <button class="chip chip--filter is-active" type="button" role="tab" aria-selected="true"
              aria-controls="all-products" data-filter="all" id="filter-all">Показать все</button>
            <button class="chip chip--filter" type="button" role="tab" aria-selected="false"
              aria-controls="candles-products" data-filter="candles" id="filter-candles">Свечи</button>
            <button class="chip chip--filter" type="button" role="tab" aria-selected="false"
              aria-controls="ceramics-products" data-filter="ceramics" id="filter-ceramics">Керамика</button>
            <button class="chip chip--filter" type="button" role="tab" aria-selected="false"
              aria-controls="decor-products" data-filter="decor" id="filter-decor">Декор</button>
            <button class="chip chip--filter" type="button" role="tab" aria-selected="false"
              aria-controls="textile-products" data-filter="textile" id="filter-textile">Текстиль</button>
            <button class="chip chip--filter" type="button" role="tab" aria-selected="false"
              aria-controls="postcards-products" data-filter="postcards" id="filter-postcards">Открытки</button>
            <button class="chip chip--filter" type="button" role="tab" aria-selected="false"
              aria-controls="sets-products" data-filter="sets" id="filter-sets">Наборы</button>
          </div>
        </div>

        <div class="search-wrap">
          <label for="searchInput" class="visually-hidden">Поиск по каталогу</label>
          <svg class="search-ico" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="11" cy="11" r="6.5" fill="none" stroke="currentColor" stroke-width="1.6"></circle>
            <path d="M16.2 16.2L21 21" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round">
            </path>
          </svg>

          <input id="searchInput" class="input input--lg" type="search" placeholder="Поиск по названию…"
            aria-label="Поиск по названию товара" autocomplete="off" data-search-input />
          <button class="search-clear visually-hidden" type="button" aria-label="Очистить поиск"
            data-search-clear>✕</button>
        </div>
      </section>

      <span id="filtersAnchor"></span>

      <div class="results-info" aria-live="polite" aria-atomic="true" style="display: none;">
        <p id="results-count">Найдено <span id="results-number">0</span> товаров</p>
        <button class="btn btn--text btn--sm" id="clear-filters" style="display: none;">Сбросить фильтры</button>
      </div>

      <div id="all-products">
        <?php
        $renderOrder = ['ceramics', 'postcards', 'candles', 'textile', 'decor', 'sets'];
foreach ($renderOrder as $categoryCode) {
    $title = $categoryTitles[$categoryCode]['title'] ?? $categoryCode;
    $desc = $categoryTitles[$categoryCode]['desc'] ?? '';
    renderCategorySection($categoryCode, $title, $desc, $groupedProducts[$categoryCode] ?? []);
}
?>
      </div>
    </section>
  </section>
</main>

<?php
renderFooter();
renderAuthModal();
renderFavoritesSheet();

renderScripts([
    'js/script.js',
    'js/cart.js',
    'js/favorites.js',
    'js/catalog-filters.js',
    'js/product.js'
]);
?>