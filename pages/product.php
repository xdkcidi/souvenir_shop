<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../php/db.php';

$isAuth = isset($_SESSION['user_id']);
$hasAuthError = !empty($_SESSION['auth_error']);

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

function ruPlural(int $n, string $one, string $few, string $many): string
{
    $n = abs($n) % 100;
    $n1 = $n % 10;

    if ($n > 10 && $n < 20) return $many;
    if ($n1 > 1 && $n1 < 5) return $few;
    if ($n1 == 1) return $one;
    return $many;
}

function stockText(int $count, bool $withIcon = false): string
{
    if ($count <= 0) {
        return $withIcon ? '✗ Нет в наличии' : 'Нет в наличии';
    }

    $text = 'В наличии: ' . $count . ' ' . ruPlural($count, 'шт.', 'шт.', 'шт.');
    return $withIcon ? '✓ ' . $text : $text;
}

function badgeText(?string $badge): string
{
    return match ($badge) {
        'hit' => 'Хит продаж',
        'new' => 'Новинка',
        default => '',
    };
}

$product_code = $_GET['id'] ?? null;
if (!$product_code) {
    http_response_code(400);
    exit('Не передан id товара');
}

/* ===== ТОВАР ===== */
$stmt = $pdo->prepare("SELECT * FROM products WHERE product_code = ? LIMIT 1");
$stmt->execute([$product_code]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    http_response_code(404);
    exit('Товар не найден');
}

$stockCount = max(0, (int)($product['in_stock'] ?? 0));
$isAvailable = $stockCount > 0;
$stockTextMain = stockText($stockCount, true);
$stockTextPlain = stockText($stockCount, false);
$stockClass = $isAvailable ? 'is-in' : 'is-out';

$img1 = productImageUrl($product['image'] ?? '');
$img2 = productImageUrl($product['image2'] ?? '');
$gallery = array_values(array_unique(array_filter([$img1, $img2])));
$mainSrc = $gallery[0] ?? '/souvenir_shop/img/placeholder.webp';

/* ===== СТАТИСТИКА ОТЗЫВОВ ===== */
$stmt = $pdo->prepare("
    SELECT 
      COUNT(*) AS cnt,
      COALESCE(AVG(rating), 0) AS avg_rating
    FROM product_reviews
    WHERE product_code = ? AND is_approved = 1
");
$stmt->execute([$product_code]);
$revStats = $stmt->fetch(PDO::FETCH_ASSOC);

$product['reviews_count'] = (int)($revStats['cnt'] ?? 0);
$product['rating'] = (float)($revStats['avg_rating'] ?? 0);

/* ===== СПИСОК ОТЗЫВОВ ===== */
$stmt = $pdo->prepare("
    SELECT author_name, rating, body, created_at
    FROM product_reviews
    WHERE product_code = ? AND is_approved = 1
    ORDER BY created_at DESC
    LIMIT 50
");
$stmt->execute([$product_code]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ===== ПРОВЕРКА, УЖЕ ОСТАВЛЯЛ ЛИ ПОЛЬЗОВАТЕЛЬ ОТЗЫВ ===== */
$userHasReview = false;
if ($isAuth) {
    $chk = $pdo->prepare("SELECT 1 FROM product_reviews WHERE product_code = ? AND user_id = ? LIMIT 1");
    $chk->execute([$product_code, (int)$_SESSION['user_id']]);
    $userHasReview = (bool)$chk->fetchColumn();
}

/* ===== FLASH-СООБЩЕНИЯ ===== */
$reviewError = $_SESSION['review_error'] ?? '';
$reviewSuccess = $_SESSION['review_success'] ?? '';
unset($_SESSION['review_error'], $_SESSION['review_success']);

/* ===== ПОХОЖИЕ ТОВАРЫ ===== */
$stmt = $pdo->prepare("SELECT * FROM products WHERE category = ? AND product_code != ? LIMIT 4");
$stmt->execute([$product['category'], $product_code]);
$related = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($related) < 4) {
    $need = 4 - count($related);
    $exclude = array_merge([$product_code], array_column($related, 'product_code'));
    $placeholders = implode(',', array_fill(0, count($exclude), '?'));

    $sql = "SELECT * FROM products WHERE product_code NOT IN ($placeholders) ORDER BY RAND() LIMIT $need";
    $stmt2 = $pdo->prepare($sql);
    $stmt2->execute($exclude);
    $more = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    $related = array_merge($related, $more);
}

$related = array_slice($related, 0, 4);
?>
<!doctype html>
<html lang="ru" data-auth="<?php echo $isAuth ? '1' : '0'; ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo h($product['name']); ?> | Магазин сувениров</title>
  <meta name="description" content="<?php echo h($product['meta'] ?? ''); ?>">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/product.css">
  <link rel="stylesheet" href="../css/cart.css">
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
                   data-bg="../img/mega-preview.png">
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
          <button class="iconBtn" type="button" aria-label="Войти" data-open-modal="authModal">
            <svg viewBox="0 0 24 24" aria-hidden="true" class="iconUser">
              <circle cx="12" cy="8" r="3.2" fill="none" stroke="currentColor" stroke-width="1.7"/>
              <path d="M5 19c1.4-3 3.6-4.5 7-4.5s5.6 1.5 7 4.5"
                    fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
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
                  fill="none" stroke="currentColor" stroke-width="1.6"/>
          </svg>
        </button>

        <a class="btn btn--dark btn--sm hide-sm" href="cart.php">Корзина</a>
      </div>
    </nav>
  </div>
</header>

<main class="pMain">
  <div class="breadcrumbs">
    <div class="container">
      <a href="/souvenir_shop/">Главная</a>
      <span class="sep">›</span>
      <a href="/souvenir_shop/pages/catalog.php">Каталог</a>
      <span class="sep">›</span>
      <span><?php echo h($product['name']); ?></span>
    </div>
  </div>

  <div class="container">

    <section class="pHero" aria-label="Карточка товара">
      <div class="pHero__media">
        <div class="pMedia">
          <div class="pMedia__main">
            <?php if (!empty($product['badge'])): ?>
              <span class="pbadge pbadge--<?php echo h($product['badge']); ?>">
                <?php echo h(badgeText($product['badge'])); ?>
              </span>
            <?php endif; ?>

            <img src="<?php echo h($mainSrc); ?>"
                 alt="<?php echo h($product['name']); ?>"
                 loading="eager"
                 id="mainImage"
                 data-zoomable>
          </div>

          <?php if (count($gallery) > 1): ?>
            <div class="pMedia__thumbs" aria-label="Миниатюры">
              <?php foreach ($gallery as $i => $src): ?>
                <button class="pThumb <?php echo $i === 0 ? 'is-active' : ''; ?>"
                        type="button"
                        aria-label="Фото <?php echo $i + 1; ?>"
                        data-thumb
                        data-src="<?php echo h($src); ?>">
                  <img src="<?php echo h($src); ?>" alt="" loading="lazy">
                </button>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="pHero__buy">
        <h1 class="pTitle"><?php echo h($product['name']); ?></h1>

        <div class="pRating">
          <div class="stars" aria-label="Рейтинг товара">
            <?php
              $ratingInt = (int)round((float)$product['rating']);
              $ratingInt = max(0, min(5, $ratingInt));
              for ($i = 1; $i <= 5; $i++):
            ?>
              <span class="star <?php echo $i <= $ratingInt ? 'filled' : ''; ?>">★</span>
            <?php endfor; ?>
          </div>

          <a class="pRating__link" href="#reviews">
            <?php echo (int)$product['reviews_count']; ?> отзывов
          </a>
        </div>

        <?php if (!empty($product['meta'])): ?>
          <p class="pSubtitle"><?php echo h($product['meta']); ?></p>
        <?php endif; ?>

        <div class="pPriceBox">
          <div class="pPriceBox__price" aria-label="Цена">
            <span class="price-amount"><?php echo number_format((float)$product['price'], 0, ',', ' '); ?></span> ₽
          </div>

          <div class="pPriceBox__stock <?php echo $stockClass; ?>">
            <?php echo h($stockTextMain); ?>
          </div>
        </div>

<div class="card" style="background:none;border:none;box-shadow:none;padding:0;">
  <div style="display:flex; align-items:center; width:100%; gap:16px;">
    <?php if ($isAvailable): ?>
      <button class="btn btn--dark btn--large"
              type="button"
              data-add-to-cart
              data-product-id="<?php echo h($product['product_code']); ?>"
              data-product-name="<?php echo h($product['name']); ?>"
              data-product-price="<?php echo (int)$product['price']; ?>"
              data-product-img="<?php echo h($img1); ?>"
              style="flex:1 1 auto;">
        В корзину
      </button>

      <div class="qty qty--card"
           data-qty-wrap="<?php echo h($product['product_code']); ?>"
           style="display:none; align-items:center; justify-content:flex-start; gap:16px; margin-right:auto;">
        <button class="qty__btn" type="button" aria-label="Уменьшить количество" data-qty-minus="<?php echo h($product['product_code']); ?>">−</button>
        <span class="qty__val">1</span>
        <button class="qty__btn" type="button" aria-label="Увеличить количество" data-qty-plus="<?php echo h($product['product_code']); ?>">+</button>
      </div>
    <?php else: ?>
      <button class="btn btn--dark btn--large" type="button" disabled style="flex:1 1 auto;">
        Нет в наличии
      </button>
    <?php endif; ?>

    <button class="iconBtn iconBtn--large"
            type="button"
            aria-label="Добавить в избранное"
            data-fav-btn
            data-product-id="<?php echo h($product['product_code']); ?>"
            data-product-name="<?php echo h($product['name']); ?>"
            data-product-price="<?php echo (int)$product['price']; ?>"
            data-product-img="<?php echo h($img1); ?>"
            style="margin-left:0;">
      <svg class="favorites-icon" viewBox="0 0 24 24" aria-hidden="true">
        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"
              fill="none" stroke="currentColor" stroke-width="1.6"/>
      </svg>
    </button>
  </div>
</div>

        <div class="pFacts" aria-label="Короткие характеристики">
          <?php if (!empty($product['material'])): ?>
            <div class="pFact"><span>Материал</span><strong><?php echo h($product['material']); ?></strong></div>
          <?php endif; ?>
          <?php if (!empty($product['color'])): ?>
            <div class="pFact"><span>Цвет</span><strong><?php echo h($product['color']); ?></strong></div>
          <?php endif; ?>
          <?php if (!empty($product['dimensions'])): ?>
            <div class="pFact"><span>Размеры</span><strong><?php echo h($product['dimensions']); ?></strong></div>
          <?php endif; ?>
          <div class="pFact"><span>Артикул</span><strong><?php echo h($product['product_code']); ?></strong></div>
        </div>

        <div class="pPerks" aria-label="Условия покупки">
          <div class="pPerk">
            <span class="pPerk__i">🚚</span>
            <div>
              <strong>Доставка</strong>
              <div class="pPerk__t">По городу 1–2 дня, по РФ 3–7 дней</div>
            </div>
          </div>

          <?php if (!empty($product['is_personalizable'])): ?>
            <div class="pPerk pPerk--accent">
              <span class="pPerk__i">✨</span>
              <div>
                <strong>Персонализация</strong>
                <div class="pPerk__t">Можно добавить надпись/бирку</div>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <section class="pSection">
      <div class="pSection__grid">
        <article class="pCardBox" aria-label="Описание товара">
          <h2 class="pH2">Описание</h2>
          <div class="pText">
            <?php
              $description = !empty($product['description_full']) ? $product['description_full'] : ($product['meta'] ?? '');
              echo nl2br(h($description));
            ?>
          </div>
        </article>

        <article class="pCardBox" aria-label="Характеристики товара">
          <h2 class="pH2">Характеристики</h2>
          <dl class="pSpecs">
            <?php if (!empty($product['material'])): ?>
              <div class="pSpec"><dt>Материал</dt><dd><?php echo h($product['material']); ?></dd></div>
            <?php endif; ?>
            <?php if (!empty($product['color'])): ?>
              <div class="pSpec"><dt>Цвет</dt><dd><?php echo h($product['color']); ?></dd></div>
            <?php endif; ?>
            <?php if (!empty($product['dimensions'])): ?>
              <div class="pSpec"><dt>Размеры</dt><dd><?php echo h($product['dimensions']); ?></dd></div>
            <?php endif; ?>

            <div class="pSpec"><dt>Артикул</dt><dd><?php echo h($product['product_code']); ?></dd></div>

            <div class="pSpec">
              <dt>Наличие</dt>
              <dd class="<?php echo $stockClass; ?>">
                <?php echo h($stockTextPlain); ?>
              </dd>
            </div>
          </dl>
        </article>
      </div>
    </section>

    <section class="hits reveal" aria-label="Похожие товары" data-filter-exclude>
      <div class="catalog-head">
        <div><h2 class="h2">Похожие товары</h2></div>
        <a class="btn btn--ghost" href="catalog.php">Смотреть каталог →</a>
      </div>

      <div class="grid4" role="list">
        <?php if (!empty($related)): ?>
          <?php foreach ($related as $rel): ?>
            <?php
              $relImg = productImageUrl($rel['image'] ?? '');
              $relStock = max(0, (int)($rel['in_stock'] ?? 0));
              $relAvailable = $relStock > 0;
            ?>
            <div class="reveal"
                 data-product
                 data-id="<?php echo h($rel['product_code']); ?>"
                 data-category="<?php echo h($rel['category'] ?? ''); ?>"
                 data-name="<?php echo h($rel['name']); ?>"
                 role="listitem">
              <div class="card">
                <div class="card__img"
                     role="img"
                     aria-label="<?php echo h($rel['name']); ?>"
                     data-bg="<?php echo h($relImg); ?>">
                  <?php if (!empty($rel['badge'])): ?>
                    <span class="pbadge pbadge--<?php echo h($rel['badge']); ?>">
                      <?php echo h($rel['badge'] === 'hit' ? 'Хит' : 'Новинка'); ?>
                    </span>
                  <?php endif; ?>
                </div>

                <div class="card__body">
                  <div class="card__top">
                    <div>
                      <h3 class="card__title"><?php echo h($rel['name']); ?></h3>
                      <div class="card__meta"><?php echo h($rel['meta'] ?? ''); ?></div>
                    </div>

                    <div class="card__price">
                      <span class="price-amount"><?php echo number_format((float)$rel['price'], 0, ',', ' '); ?></span> ₽
                    </div>
                  </div>

                  <div class="card__actions">
                    <?php if ($relAvailable): ?>
                      <button class="btn btn--dark btn--full"
                              type="button"
                              data-add-to-cart
                              data-product-id="<?php echo h($rel['product_code']); ?>"
                              data-product-name="<?php echo h($rel['name']); ?>"
                              data-product-price="<?php echo (int)$rel['price']; ?>"
                              data-product-img="<?php echo h($relImg); ?>">
                        В корзину
                      </button>

                      <div class="qty qty--card"
                           data-qty-wrap="<?php echo h($rel['product_code']); ?>"
                           style="display:none;">
                        <button class="qty__btn" type="button" aria-label="Уменьшить количество"
                                data-qty-minus="<?php echo h($rel['product_code']); ?>">−</button>
                        <span class="qty__val">1</span>
                        <button class="qty__btn" type="button" aria-label="Увеличить количество"
                                data-qty-plus="<?php echo h($rel['product_code']); ?>">+</button>
                      </div>
                    <?php else: ?>
                      <button class="btn btn--dark btn--full" type="button" disabled>
                        Нет в наличии
                      </button>
                    <?php endif; ?>

                    <button class="iconBtn"
                            type="button"
                            aria-label="Добавить в избранное"
                            aria-pressed="false"
                            data-fav-btn
                            data-product-id="<?php echo h($rel['product_code']); ?>"
                            data-product-name="<?php echo h($rel['name']); ?>"
                            data-product-price="<?php echo (int)$rel['price']; ?>"
                            data-product-img="<?php echo h($relImg); ?>">
                      <svg class="favorites-icon" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"
                              fill="none" stroke="currentColor" stroke-width="1.6"/>
                      </svg>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="pMuted">Похожие товары пока не найдены.</p>
        <?php endif; ?>
      </div>
    </section>

    <section class="pSection" id="reviews" aria-label="Отзывы покупателей">
      <div class="pSection__head">
        <h2 class="pH2">Отзывы покупателей</h2>

        <?php if (!$isAuth): ?>
          <button class="btn btn--outline" type="button" data-open-modal="authModal">Войти, чтобы оставить отзыв</button>
        <?php elseif ($userHasReview): ?>
          <button class="btn btn--outline" type="button" disabled>Отзыв уже оставлен</button>
        <?php else: ?>
          <button class="btn btn--outline" type="button" data-open-modal="reviewModal">Написать отзыв</button>
        <?php endif; ?>
      </div>

      <?php if ($reviewError): ?>
        <div class="alert alert--error" style="color:#b00020; margin: 10px 0;">
          <?php echo h($reviewError); ?>
        </div>
      <?php endif; ?>

      <?php if ($reviewSuccess): ?>
        <div class="alert" style="color: var(--green, #1f8a4c); margin: 10px 0;">
          <?php echo h($reviewSuccess); ?>
        </div>
      <?php endif; ?>

      <div class="pReviews">
        <div class="pReviews__summary">
          <div class="pBigRate"><?php echo number_format((float)$product['rating'], 1, ',', ''); ?></div>
          <div class="pMuted"><?php echo (int)$product['reviews_count']; ?> отзывов</div>
        </div>

        <div class="pReviews__list">
          <?php if (empty($reviews)): ?>
            <p class="pMuted">Пока нет отзывов. Будьте первым!</p>
          <?php else: ?>
            <?php foreach ($reviews as $r): ?>
              <article class="revItem">
                <div class="revItem__top">
                  <strong class="revItem__name"><?php echo h($r['author_name']); ?></strong>
                  <span class="revItem__date"><?php echo h(date('d.m.Y', strtotime($r['created_at']))); ?></span>
                </div>

                <div class="revItem__stars" aria-label="Оценка <?php echo (int)$r['rating']; ?> из 5">
                  <?php
                    $rr = (int)$r['rating'];
                    $rr = max(1, min(5, $rr));
                    echo str_repeat('★', $rr) . str_repeat('☆', 5 - $rr);
                  ?>
                  <span class="revItem__score">(<?php echo $rr; ?>/5)</span>
                </div>

                <div class="revItem__text">
                  <?php
                    if (!empty($r['body'])) {
                        echo nl2br(h($r['body']));
                    } else {
                        echo '<span class="pMuted">Без комментария</span>';
                    }
                  ?>
                </div>
              </article>
            <?php endforeach; ?>
          <?php endif; ?>
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

<div class="modal" id="authModal" aria-hidden="true" <?php if (!empty($_SESSION['auth_error'])) echo 'data-autoshow="1"'; ?>>
  <div class="modal__backdrop" data-close></div>
  <div class="modal__dialog" role="dialog" aria-modal="true" aria-label="Авторизация">
    <div class="modal__head">
      <div class="modal__title">Вход в аккаунт</div>
      <button class="iconBtn" type="button" data-close aria-label="Закрыть">✕</button>
    </div>
    <div class="modal__body">
      <?php if (!empty($_SESSION['auth_error'])): ?>
        <div class="alert alert--error" style="color:#b00020; margin-bottom:10px;">
          <?php echo h($_SESSION['auth_error']); ?>
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

<div class="modal" id="reviewModal" aria-hidden="true">
  <div class="modal__backdrop" data-close></div>

  <div class="modal__dialog" role="dialog" aria-modal="true" aria-label="Отзыв">
    <div class="modal__head">
      <div class="modal__title">Оставить отзыв</div>
      <button class="iconBtn" type="button" data-close aria-label="Закрыть">✕</button>
    </div>

    <div class="modal__body">
      <?php if (!$isAuth): ?>
        <p class="muted">Чтобы оставить отзыв, нужно войти в аккаунт.</p>
        <button class="btn btn--dark btn--full" type="button" data-open-modal="authModal">Войти</button>

      <?php elseif (!empty($userHasReview)): ?>
        <p class="muted">Вы уже оставили отзыв на этот товар.</p>

      <?php else: ?>
        <form action="../php/add_review.php" method="post">
          <input type="hidden" name="product_code" value="<?php echo h($product_code); ?>">

          <div class="mb-3 revField">
            <label class="small revLabel">Оценка</label>

            <div class="rate rate--nums" aria-label="Выбор оценки">
              <input class="rate__inp" type="radio" name="rating" id="rate-1" value="1">
              <label class="rate__opt" for="rate-1" title="1">
                <span class="rate__star">★</span>
                <span class="rate__num">1</span>
              </label>

              <input class="rate__inp" type="radio" name="rating" id="rate-2" value="2">
              <label class="rate__opt" for="rate-2" title="2">
                <span class="rate__star">★</span>
                <span class="rate__num">2</span>
              </label>

              <input class="rate__inp" type="radio" name="rating" id="rate-3" value="3">
              <label class="rate__opt" for="rate-3" title="3">
                <span class="rate__star">★</span>
                <span class="rate__num">3</span>
              </label>

              <input class="rate__inp" type="radio" name="rating" id="rate-4" value="4">
              <label class="rate__opt" for="rate-4" title="4">
                <span class="rate__star">★</span>
                <span class="rate__num">4</span>
              </label>

              <input class="rate__inp" type="radio" name="rating" id="rate-5" value="5" checked>
              <label class="rate__opt" for="rate-5" title="5">
                <span class="rate__star">★</span>
                <span class="rate__num">5</span>
              </label>
            </div>
          </div>

          <div class="mb-3">
            <label class="small" for="revBody">Комментарий (необязательно)</label>
            <textarea id="revBody"
                      class="input input--lg"
                      name="body"
                      rows="5"
                      maxlength="2000"
                      placeholder="Можно оставить пустым"></textarea>
          </div>

          <button class="btn btn--dark btn--full" type="submit">Отправить</button>
        </form>
      <?php endif; ?>
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

<div class="imgModal" id="imgModal" aria-hidden="true">
  <div class="imgModal__backdrop" data-close></div>
  <div class="imgModal__toolbar">
    <button type="button" class="imgModal__btn" data-zoom-out aria-label="Уменьшить">−</button>
    <button type="button" class="imgModal__btn" data-zoom-in aria-label="Увеличить">+</button>
    <button type="button" class="imgModal__btn" data-zoom-reset aria-label="Сброс">100%</button>
    <button type="button" class="imgModal__btn imgModal__btn--close" data-close aria-label="Закрыть">✕</button>
  </div>
  <div class="imgModal__stage">
    <img id="imgModalImg" alt="">
  </div>
</div>

<script src="../js/script.js"></script>
<script src="../js/cart.js"></script>
<script src="../js/product.js"></script>

</body>
</html>