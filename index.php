<?php
session_start();

$basePath = '.';

require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/php/db.php';

function productImageUrl(?string $path): string
{
    $path = trim((string)$path);

    if ($path === '') {
        return asset('img/placeholder.webp');
    }

    if (preg_match('~^(https?:)?//~i', $path)) {
        return $path;
    }

    if (str_starts_with($path, 'data:')) {
        return $path;
    }

    $path = str_replace('\\', '/', $path);
    $path = preg_replace('#^(\./|\.\./)+#', '', $path);
    $path = ltrim($path, '/');

    if (!str_contains($path, '/')) {
        $path = 'img/' . $path;
    }

    return asset($path);
}

renderHead(
    'Лавка — сувениры ручной работы',
    'Сувениры ручной работы: керамика, свечи, текстиль, декор, открытки и подарочные наборы.',
    [
        'css/style.css',
        'css/main.css'
    ]
);

renderHeader();
?>

<main>

  <section class="container section hero">
    <div class="hero__wrap" id="hero">
      <div class="hero__slides" id="heroSlides" aria-live="polite">

        <!-- Слайд 1 -->
        <article class="hero__slide is-active">
          <div class="hero__bg" data-hero-bg="img/slide1.webp"></div>
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

        <!-- Слайд 2 -->
        <article class="hero__slide">
          <div class="hero__bg" data-hero-bg="img/slide2.webp"></div>
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

        <!-- Слайд 3 -->
        <article class="hero__slide">
          <div class="hero__bg" data-hero-bg="img/slide3.webp"></div>
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

      <div class="hero__controls">
        <div class="hero__dots" id="heroDots" aria-label="Переключение слайдов"></div>
      </div>
    </div>
  </section>

  <!-- Категории -->
  <section id="collections" class="container section">
    <div class="headRow reveal">
      <div>
        <h2 class="h2">Категории</h2>
        <p class="muted">Подборки авторских сувениров для дома и подарков.</p>
      </div>
      <a class="btn" href="pages/catalog.php">Смотреть все</a>
    </div>

    <div class="grid3">

      <a class="tile reveal" href="pages/catalog.php#c#group-ceramics">
        <img class="tile__img" src="img/ceramic.webp" alt="Керамика ручной работы" width="360" height="360"
          fetchpriority="high" decoding="async">
        <div class="tile__overlay">
          <div class="tile__title">Керамика</div>
          <div class="tile__sub">кружки • тарелки • фигурки</div>
        </div>
      </a>

      <a class="tile reveal" href="pages/catalog.php#group-postcards">
        <img class="tile__img" src="img/letter.webp" alt="Авторские открытки" width="360" height="360" loading="lazy"
          decoding="async">
        <div class="tile__overlay">
          <div class="tile__title">Открытки</div>
          <div class="tile__sub">акварель • авторские иллюстрации</div>
        </div>
      </a>

      <a class="tile reveal" href="pages/catalog.php#group-candles">
        <img class="tile__img" src="img/candle.webp" alt="Ароматические свечи" width="360" height="360" loading="lazy"
          decoding="async">
        <div class="tile__overlay">
          <div class="tile__title">Свечи</div>
          <div class="tile__sub">соевые • ароматические • декор</div>
        </div>
      </a>

      <a class="tile reveal" href="pages/catalog.php#group-textile">
        <img class="tile__img" src="img/textile.webp" alt="Текстильные сувениры" width="360" height="360" loading="lazy"
          decoding="async">
        <div class="tile__overlay">
          <div class="tile__title">Текстиль</div>
          <div class="tile__sub">игрушки • вышивка • аксессуары</div>
        </div>
      </a>

      <a class="tile reveal" href="pages/catalog.php#group-decor">
        <img class="tile__img" src="img/decor.webp" alt="Декор для дома" width="360" height="360" loading="lazy"
          decoding="async">
        <div class="tile__overlay">
          <div class="tile__title">Декор</div>
          <div class="tile__sub">фигурки • вазы • интерьер</div>
        </div>
      </a>

      <a class="tile reveal" href="pages/catalog.php#group-sets">
        <img class="tile__img" src="img/box.webp" alt="Подарочные наборы" width="360" height="360" loading="lazy"
          decoding="async">
        <div class="tile__overlay">
          <div class="tile__title">Подарочные наборы</div>
          <div class="tile__sub">свечи • керамика • открытки</div>
        </div>
      </a>

    </div>
  </section>

  <!-- Хиты продаж -->
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
    $hitAvailable = ((int)($hit['in_stock'] ?? 0)) > 0;
            $hitCode = $hit['product_code'] ?? $hit['code'] ?? '';
            $hitImg = productImageUrl($hit['image'] ?? $hit['img'] ?? '');

            $hitUrl = pageUrl('pages/product.php?id=' . urlencode((string)$hitCode));
            ?>

        <div class="reveal" data-product
          data-category="<?php echo h($hit['category'] ?? ''); ?>"
          data-id="<?php echo h($hitCode); ?>"
          data-name="<?php echo h($hit['name'] ?? ''); ?>"
          role="listitem">

          <div class="card">
            <a class="card__img" href="<?php echo h($hitUrl); ?>"
              aria-label="Открыть товар <?php echo h($hit['name'] ?? 'Товар'); ?>">
              <img src="<?php echo h($hitImg); ?>"
                alt="<?php echo h($hit['name'] ?? 'Товар'); ?>"
                width="280" height="360" loading="lazy" decoding="async">
              <span class="pbadge pbadge--hit">Хит</span>
            </a>

            <div class="card__body">
              <div class="card__top">
                <div>
                  <h3 class="card__title">
                    <a href="<?php echo h($hitUrl); ?>">
                      <?php echo h($hit['name'] ?? ''); ?>
                    </a>
                  </h3>

                  <div class="card__meta">
                    <?php echo h($hit['meta'] ?? ''); ?>
                  </div>
                </div>

                <div class="card__price">
                  <span class="price-amount">
                    <?php echo number_format((float)($hit['price'] ?? 0), 0, ',', ' '); ?>
                  </span> ₽
                </div>
              </div>

              <div class="card__actions">
                <?php if ($hitAvailable): ?>
                <button class="btn btn--dark btn--full" type="button" data-add-to-cart
                  data-product-id="<?php echo h($hitCode); ?>"
                  data-product-name="<?php echo h($hit['name'] ?? ''); ?>"
                  data-product-price="<?php echo (int)($hit['price'] ?? 0); ?>"
                  data-product-img="<?php echo h($hitImg); ?>">
                  В корзину
                </button>

                <div class="qty qty--card"
                  data-qty-wrap="<?php echo h($hitCode); ?>"
                  style="display:none;">
                  <button class="qty__btn" type="button" aria-label="Уменьшить количество"
                    data-qty-minus="<?php echo h($hitCode); ?>">−</button>
                  <span class="qty__val"
                    id="cardQty-<?php echo h($hitCode); ?>">1</span>
                  <button class="qty__btn" type="button" aria-label="Увеличить количество"
                    data-qty-plus="<?php echo h($hitCode); ?>">+</button>
                </div>
                <?php else: ?>
                <button class="btn btn--dark btn--full" type="button" disabled>
                  Нет в наличии
                </button>
                <?php endif; ?>

                <button class="iconBtn" type="button"
                  aria-label="Добавить <?php echo h($hit['name'] ?? ''); ?> в избранное"
                  aria-pressed="false" data-fav-btn
                  data-product-id="<?php echo h($hitCode); ?>"
                  data-product-name="<?php echo h($hit['name'] ?? ''); ?>"
                  data-product-price="<?php echo (int)($hit['price'] ?? 0); ?>"
                  data-product-img="<?php echo h($hitImg); ?>">
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
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- Материалы -->
  <section id="materials" class="container section">
    <div class="banner reveal">
      <div class="banner__img" data-bg="img/materials-banner.webp"></div>
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

  <?php
$giftCategoryMeta = [
              'candles' => [
                  'title' => 'Свечи',
                  'icon' => '🕯️',
              ],
              'ceramics' => [
                  'title' => 'Керамика',
                  'icon' => '☕',
              ],
              'decor' => [
                  'title' => 'Декор',
                  'icon' => '🏺',
              ],
              'textile' => [
                  'title' => 'Текстиль',
                  'icon' => '🧵',
              ],
              'postcards' => [
                  'title' => 'Открытки',
                  'icon' => '💌',
              ],
];

$giftProducts = [];

$stmtGift = $pdo->prepare("
    SELECT 
        id,
        product_code,
        name,
        price,
        meta,
        category,
        in_stock
    FROM products
    WHERE in_stock > 0
    ORDER BY category ASC, name ASC
");

$stmtGift->execute();
$giftRows = $stmtGift->fetchAll(PDO::FETCH_ASSOC);

foreach ($giftRows as $product) {
    $category = $product['category'] ?? '';

    if ($category === '') {
        continue;
    }

    if (!isset($giftProducts[$category])) {
        $giftProducts[$category] = [];
    }

    $giftProducts[$category][] = $product;
}
?>
  <section id="gift" class="giftHero">
    <div class="giftHero__bg" data-bg="img/parallax.webp" aria-hidden="true"></div>
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
    if (empty($items)) {
        continue;
    }
    $hasGiftItems = true;
    ?>
            <details class="giftList" <?php echo $catKey === 'ceramics' ? 'open' : ''; ?>>
              <summary class="giftList__summary">
                <span class="giftList__icon"
                  aria-hidden="true"><?php echo h($catMeta['icon']); ?></span>
                <span
                  class="giftList__name"><?php echo h($catMeta['title']); ?></span>
                <span
                  class="giftList__hint"><?php echo count($items); ?>
                  позиций</span>
              </summary>

              <div class="giftList__body">
                <?php foreach ($items as $item): ?>
                <label class="giftOption">
                  <input type="checkbox" name="giftItems[]"
                    value="<?php echo h($item['product_code']); ?>"
                    data-code="<?php echo h($item['product_code']); ?>"
                    data-name="<?php echo h($item['name']); ?>"
                    data-price="<?php echo (int)$item['price']; ?>" />
                  <span class="giftOption__ui">
                    <span class="giftOption__title">
                      <?php echo h($item['name']); ?>
                      <span class="giftOption__price">
                        <?php echo number_format((float)$item['price'], 0, ',', ' '); ?>
                        ₽
                      </span>
                    </span>
                    <span
                      class="giftOption__meta"><?php echo h($item['meta'] ?? ''); ?></span>
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

<?php
renderFooter();
renderAuthModal();
renderFavoritesSheet();

renderScripts([
    'js/script.js',
    'js/cart.js',
    'js/favorites.js',
    'js/home.js',
    'js/gift-builder.js'
]);
?>