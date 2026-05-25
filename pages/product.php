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
        return '../img/placeholder.webp';
    }

    if (filter_var($path, FILTER_VALIDATE_URL)) {
        return $path;
    }

    $path = preg_replace('#^\.\.?/#', '', $path);
    $path = ltrim($path, '/');

    if (strpos($path, 'img/') === 0) {
        return '../' . $path;
    }

    return '../img/' . $path;
}

function ruPlural(int $n, string $one, string $few, string $many): string
{
    $n = abs($n) % 100;
    $n1 = $n % 10;

    if ($n > 10 && $n < 20) {
        return $many;
    }
    if ($n1 > 1 && $n1 < 5) {
        return $few;
    }
    if ($n1 == 1) {
        return $one;
    }
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

/* Товар */
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
$mainSrc = $gallery[0] ?? '../img/placeholder.webp';

/* Статистика отзывов */
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

$stmt = $pdo->prepare("
    SELECT author_name, rating, body, created_at
    FROM product_reviews
    WHERE product_code = ? AND is_approved = 1
    ORDER BY created_at DESC
    LIMIT 50
");
$stmt->execute([$product_code]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Отзыв пользователя */
$userReview = null;
$userCanReview = false;

function userBoughtProduct(PDO $pdo, int $userId, string $productCode): bool
{
    $sql = "
        SELECT 1
        FROM orders o
        INNER JOIN order_items oi ON oi.order_id = o.id
        WHERE o.user_id = ?
          AND oi.product_code = ?
          AND o.status = 'completed'
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId, $productCode]);
    return (bool)$stmt->fetchColumn();
}

if ($isAuth) {
    $uid = (int)$_SESSION['user_id'];

    $stmt = $pdo->prepare("
        SELECT id, rating, body, created_at, updated_at, moderated_at, is_approved
        FROM product_reviews
        WHERE product_code = ? AND user_id = ?
        LIMIT 1
    ");
    $stmt->execute([$product_code, $uid]);
    $userReview = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

    $userCanReview = userBoughtProduct($pdo, $uid, $product_code);
}

$reviewError = $_SESSION['review_error'] ?? '';
$reviewSuccess = $_SESSION['review_success'] ?? '';
unset($_SESSION['review_error'], $_SESSION['review_success']);

/* Похожие товары */
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
<?php
$basePath = '..';
require_once __DIR__ . '/../includes/layout.php';

renderHead(
    h($product['name']) . ' | Магазин сувениров',
    h($product['meta'] ?? ''),
    [
        'css/style.css',
        'css/product.css',
        'css/cart.css'
    ]
);

renderHeader();
?>
<main class="pMain">
  <div class="breadcrumbs">
    <div class="container">
      <a href="../index.php">Главная</a>
      <span class="sep">›</span>
      <a href="catalog.php">Каталог</a>
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
            <span
              class="pbadge pbadge--<?php echo h($product['badge']); ?>">
              <?php echo h(badgeText($product['badge'])); ?>
            </span>
            <?php endif; ?>

            <img src="<?php echo h($mainSrc); ?>"
              alt="<?php echo h($product['name']); ?>"
              loading="eager" id="mainImage" data-zoomable>
          </div>

          <?php if (count($gallery) > 1): ?>
          <div class="pMedia__thumbs" aria-label="Миниатюры">
            <?php foreach ($gallery as $i => $src): ?>
            <button
              class="pThumb <?php echo $i === 0 ? 'is-active' : ''; ?>"
              type="button" aria-label="Фото <?php echo $i + 1; ?>"
              data-thumb data-src="<?php echo h($src); ?>">
              <img src="<?php echo h($src); ?>" alt=""
                loading="lazy">
            </button>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="pHero__buy">
        <h1 class="pTitle">
          <?php echo h($product['name']); ?>
        </h1>

        <div class="pRating">
          <div class="stars" aria-label="Рейтинг товара">
            <?php
              $ratingInt = (int)round((float)$product['rating']);
$ratingInt = max(0, min(5, $ratingInt));
for ($i = 1; $i <= 5; $i++):
    ?>
            <span
              class="star <?php echo $i <= $ratingInt ? 'filled' : ''; ?>">★</span>
            <?php endfor; ?>
          </div>

          <a class="pRating__link" href="#reviews">
            <?php echo (int)$product['reviews_count']; ?>
            отзывов
          </a>
        </div>

        <?php if (!empty($product['meta'])): ?>
        <p class="pSubtitle">
          <?php echo h($product['meta']); ?>
        </p>
        <?php endif; ?>

        <div class="pPriceBox">
          <div class="pPriceBox__price" aria-label="Цена">
            <span
              class="price-amount"><?php echo number_format((float)$product['price'], 0, ',', ' '); ?></span>
            ₽
          </div>

          <div class="pPriceBox__stock <?php echo $stockClass; ?>">
            <?php echo h($stockTextMain); ?>
          </div>
        </div>

        <div class="card" style="background:none;border:none;box-shadow:none;padding:0;">
          <div style="display:flex; align-items:center; width:100%; gap:16px;">
            <?php if ($isAvailable): ?>
            <button class="btn btn--dark btn--large" type="button" data-add-to-cart
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
              <button class="qty__btn" type="button" aria-label="Уменьшить количество"
                data-qty-minus="<?php echo h($product['product_code']); ?>">−</button>
              <span class="qty__val">1</span>
              <button class="qty__btn" type="button" aria-label="Увеличить количество"
                data-qty-plus="<?php echo h($product['product_code']); ?>">+</button>
            </div>
            <?php else: ?>
            <button class="btn btn--dark btn--large" type="button" disabled style="flex:1 1 auto;">
              Нет в наличии
            </button>
            <?php endif; ?>

            <button class="iconBtn iconBtn--large" type="button" aria-label="Добавить в избранное" data-fav-btn
              data-product-id="<?php echo h($product['product_code']); ?>"
              data-product-name="<?php echo h($product['name']); ?>"
              data-product-price="<?php echo (int)$product['price']; ?>"
              data-product-img="<?php echo h($img1); ?>"
              style="margin-left:0;">
              <svg class="favorites-icon" viewBox="0 0 24 24" aria-hidden="true">
                <path
                  d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"
                  fill="none" stroke="currentColor" stroke-width="1.6" />
              </svg>
            </button>
          </div>
        </div>

        <div class="pFacts" aria-label="Короткие характеристики">
          <?php if (!empty($product['material'])): ?>
          <div class="pFact">
            <span>Материал</span><strong><?php echo h($product['material']); ?></strong>
          </div>
          <?php endif; ?>
          <?php if (!empty($product['color'])): ?>
          <div class="pFact">
            <span>Цвет</span><strong><?php echo h($product['color']); ?></strong>
          </div>
          <?php endif; ?>
          <?php if (!empty($product['dimensions'])): ?>
          <div class="pFact">
            <span>Размеры</span><strong><?php echo h($product['dimensions']); ?></strong>
          </div>
          <?php endif; ?>
          <div class="pFact">
            <span>Артикул</span><strong><?php echo h($product['product_code']); ?></strong>
          </div>
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
              <div class="pPerk__t">Можно оставить заявку на индивидуальное оформление</div>
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
            <div class="pSpec">
              <dt>Материал</dt>
              <dd>
                <?php echo h($product['material']); ?>
              </dd>
            </div>
            <?php endif; ?>
            <?php if (!empty($product['color'])): ?>
            <div class="pSpec">
              <dt>Цвет</dt>
              <dd>
                <?php echo h($product['color']); ?>
              </dd>
            </div>
            <?php endif; ?>
            <?php if (!empty($product['dimensions'])): ?>
            <div class="pSpec">
              <dt>Размеры</dt>
              <dd>
                <?php echo h($product['dimensions']); ?>
              </dd>
            </div>
            <?php endif; ?>

            <div class="pSpec">
              <dt>Артикул</dt>
              <dd>
                <?php echo h($product['product_code']); ?>
              </dd>
            </div>

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
        <div>
          <h2 class="h2">Похожие товары</h2>
        </div>
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
        <div class="reveal" data-product
          data-id="<?php echo h($rel['product_code']); ?>"
          data-category="<?php echo h($rel['category'] ?? ''); ?>"
          data-name="<?php echo h($rel['name']); ?>"
          role="listitem">
          <div class="card">
            <div class="card__img" role="img"
              aria-label="<?php echo h($rel['name']); ?>"
              data-bg="<?php echo h($relImg); ?>">
              <?php if (!empty($rel['badge'])): ?>
              <span
                class="pbadge pbadge--<?php echo h($rel['badge']); ?>">
                <?php echo h($rel['badge'] === 'hit' ? 'Хит' : 'Новинка'); ?>
              </span>
              <?php endif; ?>
            </div>

            <div class="card__body">
              <div class="card__top">
                <div>
                  <h3 class="card__title">
                    <?php echo h($rel['name']); ?>
                  </h3>
                  <div class="card__meta">
                    <?php echo h($rel['meta'] ?? ''); ?>
                  </div>
                </div>

                <div class="card__price">
                  <span
                    class="price-amount"><?php echo number_format((float)$rel['price'], 0, ',', ' '); ?></span>
                  ₽
                </div>
              </div>

              <div class="card__actions">
                <?php if ($relAvailable): ?>
                <button class="btn btn--dark btn--full" type="button" data-add-to-cart
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

                <button class="iconBtn" type="button" aria-label="Добавить в избранное" aria-pressed="false"
                  data-fav-btn
                  data-product-id="<?php echo h($rel['product_code']); ?>"
                  data-product-name="<?php echo h($rel['name']); ?>"
                  data-product-price="<?php echo (int)$rel['price']; ?>"
                  data-product-img="<?php echo h($relImg); ?>">
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
        <?php else: ?>
        <p class="pMuted">Похожие товары пока не найдены.</p>
        <?php endif; ?>
      </div>
    </section>

    <section class="pSection" id="reviews" aria-label="Отзывы покупателей">
      <div class="pSection__head">
        <h2 class="pH2">Отзывы покупателей</h2>

        <?php if (!$isAuth): ?>
        <button class="btn btn--outline" type="button" data-open-modal="authModal">
          Войти, чтобы оставить отзыв
        </button>

        <?php elseif (!$userCanReview): ?>
        <button class="btn btn--outline" type="button" disabled>
          Отзыв доступен после покупки
        </button>

        <?php elseif ($userReview): ?>
        <button class="btn btn--outline" type="button" data-open-modal="reviewModal">
          Редактировать отзыв
        </button>

        <?php else: ?>
        <button class="btn btn--outline" type="button" data-open-modal="reviewModal">
          Написать отзыв
        </button>
        <?php endif; ?>
      </div>
      <?php if ($isAuth && $userReview): ?>
      <div class="alert" style="margin: 10px 0;">
        <?php if ((int)$userReview['is_approved'] === 1): ?>
        Ваш отзыв опубликован.
        <?php else: ?>
        Ваш отзыв находится на модерации.
        <?php endif; ?>
      </div>
      <?php endif; ?>

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
          <div class="pBigRate">
            <?php echo number_format((float)$product['rating'], 1, ',', ''); ?>
          </div>
          <div class="pMuted">
            <?php echo (int)$product['reviews_count']; ?>
            отзывов
          </div>
        </div>

        <div class="pReviews__list">
          <?php if (empty($reviews)): ?>
          <p class="pMuted">Пока нет отзывов. Будьте первым!</p>
          <?php else: ?>
          <?php foreach ($reviews as $r): ?>
          <article class="revItem">
            <div class="revItem__top">
              <strong
                class="revItem__name"><?php echo h($r['author_name']); ?></strong>
              <span
                class="revItem__date"><?php echo h(date('d.m.Y', strtotime($r['created_at']))); ?></span>
            </div>

            <div class="revItem__stars"
              aria-label="Оценка <?php echo (int)$r['rating']; ?> из 5">
              <?php
                    $rr = (int)$r['rating'];
              $rr = max(1, min(5, $rr));
              echo str_repeat('★', $rr) . str_repeat('☆', 5 - $rr);
              ?>
              <span
                class="revItem__score">(<?php echo $rr; ?>/5)</span>
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

<?php
renderFooter();
renderAuthModal();
?>

<div class="modal" id="reviewModal" aria-hidden="true">
  <div class="modal__backdrop" data-close></div>

  <div class="modal__dialog" role="dialog" aria-modal="true" aria-label="Отзыв">
    <div class="modal__head">
      <div class="modal__title">
        <?php echo $userReview ? 'Редактировать отзыв' : 'Оставить отзыв'; ?>
      </div>
      <button class="iconBtn" type="button" data-close aria-label="Закрыть">✕</button>
    </div>

    <div class="modal__body">
      <?php if (!$isAuth): ?>
      <p class="muted">Чтобы оставить отзыв, нужно войти в аккаунт.</p>
      <button class="btn btn--dark btn--full" type="button" data-open-modal="authModal">Войти</button>

      <?php elseif (!$userCanReview): ?>
      <p class="muted">Оставить отзыв можно только после завершённой покупки товара.</p>

      <?php else: ?>
      <form action="../php/add_review.php" method="post">
        <input type="hidden" name="product_code"
          value="<?php echo h($product_code); ?>">

        <div class="mb-3 revField">
          <label class="small revLabel">Оценка</label>

          <div class="rate rate--nums" aria-label="Выбор оценки">
            <?php $currentRating = (int)($userReview['rating'] ?? 5); ?>

            <?php for ($i = 1; $i <= 5; $i++): ?>
            <input class="rate__inp" type="radio" name="rating"
              id="rate-<?php echo $i; ?>"
              value="<?php echo $i; ?>"
              <?php echo $currentRating === $i ? 'checked' : ''; ?>>

            <label class="rate__opt" for="rate-<?php echo $i; ?>"
              title="<?php echo $i; ?>">
              <span class="rate__star">★</span>
              <span class="rate__num"><?php echo $i; ?></span>
            </label>
            <?php endfor; ?>
          </div>
        </div>

        <div class="mb-3">
          <label class="small" for="revBody">Комментарий (необязательно)</label>
          <textarea id="revBody" class="input input--lg" name="body" rows="5" maxlength="2000"
            placeholder="Можно оставить пустым"><?php echo h($userReview['body'] ?? ''); ?></textarea>
        </div>

        <?php if ($userReview): ?>
        <p class="muted small" style="margin-bottom:12px;">
          После редактирования отзыв снова отправится на модерацию.
        </p>
        <?php endif; ?>

        <button class="btn btn--dark btn--full" type="submit">
          <?php echo $userReview ? 'Сохранить изменения' : 'Отправить'; ?>
        </button>
      </form>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php
renderFavoritesSheet();
?>

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

<?php
renderScripts([
    'js/script.js',
    'js/cart.js',
    'js/favorites.js',
    'js/product.js'
]);
?>