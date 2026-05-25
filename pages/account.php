<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: registration.php');
    exit;
}

require_once __DIR__ . '/../php/db.php';

$userId  = (int)$_SESSION['user_id'];
$errors  = [];
$success = '';

$showVerificationSuccess = isset($_SESSION['verification_success']);
unset($_SESSION['verification_success']);

// Загружаем пользователя
$stmt = $pdo->prepare("
  SELECT id, login, email, phone, avatar
  FROM users
  WHERE id = :id
  LIMIT 1
");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf_token'];

// Функция статуса заказа
function orderStatusMeta(string $status): array
{
    $s = strtolower(trim($status));
    return match ($s) {
        'new' => ['НОВЫЙ', 'status-processing'],
        'processing' => ['В ОБРАБОТКЕ', 'status-processing'],
        'delivered' => ['ДОСТАВЛЕН', 'status-delivered'],
        'canceled' => ['ОТМЕНЁН', 'status-canceled'],
        default => ['В ОБРАБОТКЕ', 'status-processing'],
    };
}

// Профиль
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['profile_action'])) {
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'], $token)) {
        $errors[] = 'Ошибка безопасности. Обновите страницу и попробуйте снова.';
    } else {
        $action = (string)($_POST['profile_action'] ?? '');

        if ($action === 'save_profile') {
            $phone = trim($_POST['phone'] ?? '');

            $stmt = $pdo->prepare("UPDATE users SET phone = :phone WHERE id = :id");
            $ok = $stmt->execute([':phone' => $phone, ':id' => $userId]);

            if ($ok) {
                $success = 'Данные профиля обновлены.';
                $user['phone'] = $phone;
            } else {
                $errors[] = 'Не удалось обновить данные. Попробуйте ещё раз.';
            }
        }

        if ($action === 'upload_avatar') {
            if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'Не удалось загрузить файл.';
            } else {
                $file = $_FILES['avatar'];

                if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
                    $errors[] = 'Файл слишком большой (макс. 2MB).';
                } else {
                    $tmp = $file['tmp_name'];

                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $tmp);
                    finfo_close($finfo);

                    $allowed = [
                      'image/jpeg' => 'jpg',
                      'image/png'  => 'png',
                      'image/webp' => 'webp',
                    ];

                    if (!isset($allowed[$mime])) {
                        $errors[] = 'Можно загрузить только JPG / PNG / WEBP.';
                    } else {
                        $ext = $allowed[$mime];

                        $dirFs  = __DIR__ . '/../img/avatars';
                        $dirWeb = '../img/avatars';

                        if (!is_dir($dirFs)) {
                            @mkdir($dirFs, 0775, true);
                        }

                        $name = 'u' . $userId . '_' . time() . '.' . $ext;
                        $pathFs  = $dirFs . '/' . $name;
                        $pathWeb = $dirWeb . '/' . $name;

                        if (!move_uploaded_file($tmp, $pathFs)) {
                            $errors[] = 'Не удалось сохранить файл.';
                        } else {
                            $old = trim((string)($user['avatar'] ?? ''));
                            if ($old && str_contains($old, '/img/avatars/')) {
                                $oldFs = __DIR__ . '/../' . ltrim($old, '/');
                                if (is_file($oldFs)) {
                                    @unlink($oldFs);
                                }
                            }

                            $upd = $pdo->prepare("UPDATE users SET avatar = :p WHERE id = :id");
                            $upd->execute([':p' => $pathWeb, ':id' => $userId]);

                            $user['avatar'] = $pathWeb;
                            $success = 'Фото профиля обновлено.';
                        }
                    }
                }
            }
        }
    }
}

// Избранное
$stmt = $pdo->prepare("
  SELECT
    f.id AS fav_row_id,
    p.id AS product_db_id,
    p.product_code AS product_code,
    p.name AS name,
    p.image AS image,
    p.price AS price,
    p.meta AS meta
  FROM favorites f
  JOIN products p ON f.product_id = p.id
  WHERE f.user_id = :uid
  ORDER BY f.created_at DESC, f.id DESC
");
$stmt->execute([':uid' => $userId]);
$favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
$favoritesCount = count($favorites);

foreach ($favorites as &$f) {
    $img = $f['image'] ?? null;
    if ($img) {
        $img = ltrim((string)$img, './');
        $img = '../' . $img;
    } else {
        $img = '../img/placeholder.webp';
    }
    $f['img'] = $img;
}
unset($f);

// Заказы
$stmt = $pdo->prepare("
SELECT id, total_sum, status, created_at, delivery_type, delivery_fee,
delivery_address, pickup_address, delivery_date, delivery_slot, promo_code, is_gift, discount_percent, discount_sum, items_sum
FROM orders
WHERE user_id = :uid
ORDER BY id DESC
LIMIT 5
");
$stmt->execute([':uid' => $userId]);
$ordersTop = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
SELECT id, total_sum, status, created_at, delivery_type, delivery_fee, delivery_address,
pickup_address, delivery_date, delivery_slot, promo_code, is_gift, discount_percent, discount_sum, items_sum
FROM orders
WHERE user_id = :uid
ORDER BY id DESC
");
$stmt->execute([':uid' => $userId]);
$ordersAll = $stmt->fetchAll(PDO::FETCH_ASSOC);

$topIds = array_map(fn ($o) => (int)$o['id'], $ordersTop);
$restOrders = array_values(array_filter($ordersAll, fn ($o) => !in_array((int)$o['id'], $topIds, true)));

$orderItems = [];
if (!empty($ordersAll)) {
    $allIds = array_map(fn ($o) => (int)$o['id'], $ordersAll);
    $in = implode(',', array_fill(0, count($allIds), '?'));

    $stmt = $pdo->prepare("
    SELECT oi.order_id, oi.product_code, oi.name, oi.qty,
           p.image
    FROM order_items oi
    LEFT JOIN products p ON p.product_code = oi.product_code
    WHERE oi.order_id IN ($in)
    ORDER BY oi.order_id DESC, oi.id ASC
  ");
    $stmt->execute($allIds);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $r) {
        $oid = (int)$r['order_id'];
        if (!isset($orderItems[$oid])) {
            $orderItems[$oid] = [];
        }

        $img = $r['image'] ?? null;
        if ($img) {
            $img = ltrim((string)$img, './');
            $img = '../' . $img;
        } else {
            $img = '../img/placeholder.webp';
        }

        $orderItems[$oid][] = [
          'name' => (string)$r['name'],
          'qty'  => (int)$r['qty'],
          'img'  => $img,
        ];
    }
}

// Статистика
$stmt = $pdo->prepare("
  SELECT
    COUNT(*) AS orders_count,
    COALESCE(SUM(items_sum), 0) AS items_total,
    COALESCE(SUM(discount_sum), 0) AS discounts_total
  FROM orders
  WHERE user_id = :uid
");
$stmt->execute([':uid' => $userId]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

$ordersCount = (int)($stats['orders_count'] ?? 0);
$itemsTotal  = (int)($stats['items_total'] ?? 0);
$discountsTotal = (int)($stats['discounts_total'] ?? 0);

$BONUS_RATE = 3;
$bonusBalance = (int)round($itemsTotal * ($BONUS_RATE / 100));

if ($itemsTotal >= 30000) {
    $statusName = 'VIP';
    $nextName = null;
    $nextGoal = null;
    $statusGoal = max(30000, $itemsTotal);
} elseif ($itemsTotal >= 10000) {
    $statusName = 'Постоянный';
    $nextName = 'VIP';
    $nextGoal = 30000;
    $statusGoal = 30000;
} else {
    $statusName = 'Новичок';
    $nextName = 'Постоянный';
    $nextGoal = 10000;
    $statusGoal = 10000;
}

$progressPct = $nextGoal
    ? (int)round(min(100, ($itemsTotal / $nextGoal) * 100))
    : 100;

$leftToNext = $nextGoal
    ? max(0, $nextGoal - $itemsTotal)
    : 0;

// Какие промокоды использованы
$stmt = $pdo->prepare("SELECT promo_code FROM promo_redemptions WHERE user_id = :uid");
$stmt->execute([':uid' => $userId]);
$usedPromoCodes = array_flip(array_map(
    'strtoupper',
    array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'promo_code')
));

$notUsed = fn (string $code): bool => !isset($usedPromoCodes[strtoupper($code)]);

$personalCoupons = [];

if ($ordersCount === 0 && $notUsed('WELCOME10')) {
    $personalCoupons[] = ['title' => 'На первый заказ', 'code' => 'WELCOME10', 'discount' => '10%', 'expiry' => '—'];
}

if ($statusName === 'Постоянный' && $notUsed('LOYAL20')) {
    $personalCoupons[] = ['title' => 'Постоянному клиенту', 'code' => 'LOYAL20', 'discount' => '20%', 'expiry' => '—'];
}

if ($statusName === 'VIP' && $notUsed('VIP25')) {
    $personalCoupons[] = ['title' => 'VIP-бонус', 'code' => 'VIP25', 'discount' => '25%', 'expiry' => '—'];
}

if ($notUsed('SPRING15')) {
    $personalCoupons[] = ['title' => 'Сезонная скидка', 'code' => 'SPRING15', 'discount' => '15%', 'expiry' => '—'];
}

// История начислений
$stmt = $pdo->prepare("
  SELECT id, created_at, items_sum
  FROM orders
  WHERE user_id = :uid
  ORDER BY id DESC
  LIMIT 5
");
$stmt->execute([':uid' => $userId]);
$lastOrdersForBonus = $stmt->fetchAll(PDO::FETCH_ASSOC);

$bonusHistory = [];
foreach ($lastOrdersForBonus as $o) {
    $sum = (int)($o['items_sum'] ?? 0);
    $earned = (int)round($sum * ($BONUS_RATE / 100));

    $bonusHistory[] = [
      'type' => 'plus',
      'title' => 'Начисление за заказ №' . (int)$o['id'],
      'date' => date('d.m.Y', strtotime((string)$o['created_at'])),
      'amount' => '+' . $earned,
    ];
}

// Последний заказ
$stmt = $pdo->prepare("
  SELECT id, total_sum, status, created_at
  FROM orders
  WHERE user_id = :uid
  ORDER BY id DESC
  LIMIT 1
");
$stmt->execute([':uid' => $userId]);
$lastOrder = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

$isAuth = true;
$hasAuthError = !empty($_SESSION['auth_error']);
?>
<?php
$basePath = '..';
require_once __DIR__ . '/../includes/layout.php';

renderHead(
    'Личный кабинет — Лавка',
    'Личный кабинет Лавка: ваши данные, адрес доставки, избранное и купоны.',
    [
        'css/style.css',
        'css/cart.css',
        'css/account.css'
    ]
);

renderHeader();
?>
<main class="container section auth-page" id="main-content" role="main" tabindex="-1">
  <div class="account-page__inner">
    <nav class="breadcrumbs" aria-label="Хлебные крошки">
      <ol>
        <li><a href="../index.php">Главная</a></li>
        <li><span aria-current="page">Личный кабинет</span></li>
      </ol>
    </nav>

    <?php if ($showVerificationSuccess): ?>
    <div class="auth-success" aria-live="polite">
      Ваша почта успешно подтверждена! Добро пожаловать в личный кабинет!
    </div>
    <?php endif; ?>

    <h1 class="auth-title">Личный кабинет</h1>
    <p class="auth-lead">
      Управляйте профилем, отслеживайте заказы и используйте бонусы
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

    <!-- Навигация -->
    <div class="account-tabs" role="tablist" aria-label="Разделы личного кабинета">
      <button class="account-tab active" role="tab" aria-selected="true" data-tab="profile">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
          <circle cx="12" cy="7" r="4"></circle>
        </svg>
        Профиль
      </button>
      <button class="account-tab" role="tab" data-tab="orders">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
          <line x1="1" y1="10" x2="23" y2="10"></line>
        </svg>
        Заказы
      </button>
      <button class="account-tab" role="tab" data-tab="favorites">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path
            d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z">
          </path>
        </svg>
        Избранное
      </button>
      <button class="account-tab" role="tab" data-tab="coupons">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="8" width="18" height="12" rx="2" ry="2"></rect>
          <line x1="3" y1="12" x2="21" y2="12"></line>
          <path d="M12 8v-4a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v4"></path>
        </svg>
        Бонусы
      </button>

      <?php if (!empty($_SESSION['user_role']) && (int)$_SESSION['user_role'] === 1): ?>
      <a class="account-admin-link" href="../pages/admin.php">
        Перейти в админ-панель
      </a>
      <?php endif; ?>
    </div>

    <!-- Профиль -->
    <section class="account-card active" id="profile-tab" role="tabpanel" aria-labelledby="profile-tab">
      <div class="profile-header">
        <div class="avatar-section">

          <div class="avatar">
            <?php if (!empty($user['avatar'])): ?>
            <img
              src="<?= htmlspecialchars($user['avatar'], ENT_QUOTES) ?>"
              alt="Аватар" class="avatar__image">
            <?php else: ?>
            <?= mb_strtoupper(mb_substr($user['login'], 0, 1, 'UTF-8')) ?>
            <?php endif; ?>
          </div>

          <form method="post" enctype="multipart/form-data" class="avatar-form" id="avatarForm">
            <input type="hidden" name="csrf_token"
              value="<?= htmlspecialchars($csrf, ENT_QUOTES) ?>">
            <input type="hidden" name="profile_action" value="upload_avatar">
            <input type="file" name="avatar" id="avatarInput" accept="image/jpeg,image/png,image/webp" hidden>

            <button class="avatar-change" type="button" id="avatarPickBtn">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
              </svg>
              Изменить фото
            </button>
          </form>
        </div>

        <div class="profile-info">

          <div class="profile-nameRow">
            <h2 class="profile-name">
              <?= htmlspecialchars($user['login'], ENT_QUOTES) ?>
            </h2>
            <span
              class="status-badge status-badge--<?= $statusName === 'VIP' ? 'vip' : ($statusName === 'Постоянный' ? 'loyal' : 'new') ?>">
              <?= htmlspecialchars($statusName, ENT_QUOTES) ?>
            </span>
          </div>

          <p class="profile-email">
            <?= htmlspecialchars($user['email'], ENT_QUOTES) ?>
          </p>


          <div class="profile-stats">
            <div class="stat stat--card">
              <span
                class="stat-value"><?= (int)$ordersCount ?></span>
              <span class="stat-label">заказов</span>
            </div>

            <div class="stat stat--card">
              <span
                class="stat-value"><?= (int)$bonusBalance ?></span>
              <span class="stat-label">бонусов</span>
            </div>

            <div class="stat stat--card">
              <span
                class="stat-value"><?= (int)$favoritesCount ?></span>
              <span class="stat-label">в избранном</span>
            </div>
          </div>

          <!-- Статус и прогресс -->
          <div class="status-progress">
            <?php if ($nextGoal): ?>
            <div class="status-progress__top">
              <span class="muted small">
                До уровня
                <b><?= htmlspecialchars($nextName, ENT_QUOTES) ?></b>
                осталось:
                <b><?= number_format((int)$leftToNext, 0, '', ' ') ?>
                  ₽</b>
              </span>
              <span
                class="muted small"><?= (int)$progressPct ?>%</span>
            </div>

            <div class="status-progress__bar" role="progressbar" aria-valuemin="0" aria-valuemax="100"
              aria-valuenow="<?= (int)$progressPct ?>">
              <div class="status-progress__fill"
                style="--progress-width: <?= (int)$progressPct ?>%">
              </div>
            </div>
            <?php else: ?>
            <div class="muted small">Вы на максимальном уровне</div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Последний заказ -->
      <div class="profile-latest">
        <?php if ($lastOrder): ?>
        <?php [$lbl, $cls] = orderStatusMeta((string)$lastOrder['status']); ?>
        <div class="card profile-latest-card">
          <div class="profile-latest-card__row">
            <div class="profile-latest-card__main">
              <div class="muted small profile-latest-card__label">Последний заказ</div>
              <div class="profile-latest-card__number">
                №<?= (int)$lastOrder['id'] ?>
                <span class="order-status <?= $cls ?>">
                  <?= htmlspecialchars($lbl, ENT_QUOTES) ?>
                </span>
              </div>
              <div class="muted small profile-latest-card__date">
                <?= date('d.m.Y', strtotime((string)$lastOrder['created_at'])) ?>
              </div>
            </div>

            <div class="profile-latest-card__sum">
              <?= number_format((int)$lastOrder['total_sum'], 0, '', ' ') ?>
              ₽
            </div>
          </div>

          <div class="profile-latest-card__actions">
            <a class="btn btn--dark btn--sm" href="account.php?tab=orders">Открыть историю заказов</a>
          </div>
        </div>
        <?php else: ?>
        <div class="card profile-latest-card">
          <div class="profile-latest-card__number">Пока нет заказов</div>
          <div class="muted profile-latest-card__date">
            Сделайте первый заказ — начислим бонусы
          </div>
          <div class="profile-latest-card__actions">
            <a class="btn btn--dark btn--sm" href="catalog.php">Перейти в каталог</a>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <a href="../php/logout.php" class="btn btn--outline logout-link">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
          <polyline points="16 17 21 12 16 7"></polyline>
          <line x1="21" y1="12" x2="9" y2="12"></line>
        </svg>
        Выйти из аккаунта
      </a>
    </section>

    <!-- Заказы -->
    <section class="account-card" id="orders-tab" role="tabpanel" aria-labelledby="orders-tab" hidden>
      <div class="section-header">
        <h2 class="section-title">История заказов</h2>

        <div class="section-header__actions">
          <button type="button" class="link-all" id="showAllOrdersBtn">Все заказы →</button>
          <button type="button" class="link-all" id="collapseOrdersBtn" style="display:none;">Свернуть</button>
        </div>
      </div>

      <div class="orders-list" id="ordersList">
        <?php
      $renderOrderCard = function (array $o) use ($orderItems) {

          $oid = (int)$o['id'];
          [$label, $cls] = orderStatusMeta((string)$o['status']);
          $date = date('d.m.Y', strtotime((string)$o['created_at']));

          $deliveryType = (string)($o['delivery_type'] ?? 'delivery');
          $deliveryText = ($deliveryType === 'pickup') ? 'Самовывоз' : 'Доставка';
          $addr = (string)($o['delivery_address'] ?? ($o['pickup_address'] ?? ''));
          $deliveryFee = (int)($o['delivery_fee'] ?? 0);

          $deliveryDate = (string)($o['delivery_date'] ?? '');
          $deliverySlot = (string)($o['delivery_slot'] ?? '');

          $itemsSum = (int)($o['items_sum'] ?? 0);
          $discountSum = (int)($o['discount_sum'] ?? 0);
          $discountPercent = (int)($o['discount_percent'] ?? 0);
          $promo = trim((string)($o['promo_code'] ?? ''));

          $totalNum = (int)($o['total_sum'] ?? 0);

          $itemsSumNum = $itemsSum;
          $discountSumNum = $discountSum;
          $deliveryFeeNum = ($deliveryType === 'pickup') ? 0 : $deliveryFee;

          $items = $orderItems[$oid] ?? [];

          ob_start();
          ?>
        <div class="order-card">
          <div class="order-header">
            <div>
              <h3 class="order-number">
                Заказ №<?= $oid ?>
                <?php if (!empty($o['is_gift'])): ?>
                <span class="order-badge order-badge--gift">Подарочный набор</span>
                <?php endif; ?>
              </h3>
              <span class="order-date"><?= $date ?></span>
            </div>
            <span
              class="order-status <?= $cls ?>"><?= htmlspecialchars($label) ?></span>
          </div>

          <div class="order-body">
            <div class="order-products">
              <?php if ($items): ?>
              <?php foreach ($items as $it): ?>
              <div class="product-preview">
                <div class="product-preview__image">
                  <img
                    src="<?= htmlspecialchars($it['img']) ?>"
                    alt="<?= htmlspecialchars($it['name']) ?>"
                    loading="lazy">
                </div>

                <div class="product-preview__content">
                  <span
                    class="product-preview__name"><?= htmlspecialchars($it['name']) ?></span>
                  <span class="muted small">Количество:
                    <?= (int)$it['qty'] ?></span>
                </div>
              </div>
              <?php endforeach; ?>
              <?php else: ?>
              <div class="muted small">Товары не найдены</div>
              <?php endif; ?>
            </div>

            <div class="order-footer">
              <div class="order-total">
                <?= number_format($totalNum, 0, '', ' ') ?>
                ₽
              </div>

              <div class="muted small">
                Товары
                <b><?= number_format($itemsSumNum, 0, '', ' ') ?>
                  ₽</b>
                <span aria-hidden="true"> • </span>
                Скидка
                <b>−<?= number_format($discountSumNum, 0, '', ' ') ?>
                  ₽</b>
                <span aria-hidden="true"> • </span>
                Доставка
                <b><?= number_format($deliveryFeeNum, 0, '', ' ') ?>
                  ₽</b>
                <span aria-hidden="true"> • </span>
                Итог
                <b><?= number_format($totalNum, 0, '', ' ') ?>
                  ₽</b>
              </div>

              <div class="muted small">
                Способ получения:
                <b><?= htmlspecialchars($deliveryText) ?></b>
                <?php if ($deliveryType !== 'pickup'): ?>
                <span class="muted small">(+<?= (int)$deliveryFee ?>
                  ₽)</span>
                <?php endif; ?>
              </div>

              <?php if ($deliveryType !== 'pickup' && $deliveryDate && $deliverySlot): ?>
              <div class="muted small">
                Доставка:
                <b><?= htmlspecialchars(date('d.m.Y', strtotime($deliveryDate))) ?></b>,
                интервал
                <b><?= htmlspecialchars($deliverySlot) ?></b>
              </div>
              <?php elseif ($deliveryType === 'pickup'): ?>
              <div class="muted small">Самовывоз: <b>ежедневно 10:00–20:00</b></div>
              <?php endif; ?>

              <div class="muted small">Адрес:
                <b><?= htmlspecialchars($addr) ?></b>
              </div>

              <?php if ($discountSumNum > 0): ?>
              <div class="muted small">
                Промокод:
                <b><?= htmlspecialchars($promo ?: '—') ?></b>
                <?php if ($discountPercent > 0): ?>
                <span
                  class="muted small">(−<?= (int)$discountPercent ?>%)</span>
                <?php endif; ?>
              </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php
              return ob_get_clean();
      };

// первые 5
foreach ($ordersTop as $o) {
    echo $renderOrderCard($o);
}
?>
        <div id="ordersTopEndMarker"></div>
      </div>

      <div id="ordersRest" style="display:none;">
        <?php foreach ($restOrders as $o) {
            echo $renderOrderCard($o);
        } ?>
      </div>

      <!-- если заказов нет -->
      <div class="empty-state" id="ordersEmptyState"
        style="<?= empty($ordersAll) ? 'display:block;' : 'display:none;' ?>">
        <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
          <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
          <line x1="3" y1="6" x2="21" y2="6"></line>
          <path d="M16 10a4 4 0 0 1-8 0"></path>
        </svg>
        <h3>Заказов пока нет</h3>
        <p>Совершите первую покупку в нашем магазине</p>
        <a href="catalog.php" class="btn btn--dark">Перейти в каталог</a>
      </div>
    </section>

    <!-- Избранное -->
    <section class="account-card" id="favorites-tab" role="tabpanel" aria-labelledby="favorites-tab" hidden>
      <div class="section-header">
        <h2 class="section-title">Избранное</h2>

        <?php if (!empty($favorites)): ?>
        <button class="btn btn--outline btn--sm" id="clear-favorites-btn" type="button">
          Очистить все
        </button>
        <?php endif; ?>
      </div>

      <?php if (!empty($favorites)): ?>
      <div class="cartList" id="favoritesGrid">
        <?php foreach ($favorites as $f): ?>
        <?php
          $code  = (string)$f['product_code'];
            $name  = (string)$f['name'];
            $price = (int)$f['price'];
            $img   = (string)$f['img'];
            $pidDb = (int)$f['product_db_id'];
            $meta  = trim((string)($f['meta'] ?? ''));
            ?>

        <div class="card favorite-card" data-fav-item
          data-product-code="<?= htmlspecialchars($code, ENT_QUOTES) ?>">

          <div class="cartRow">
            <div class="cartItemImg">
              <a href="product.php?id=<?= urlencode($code) ?>"
                class="favorite-card__image-link">
                <img src="<?= htmlspecialchars($img, ENT_QUOTES) ?>"
                  alt="<?= htmlspecialchars($name, ENT_QUOTES) ?>"
                  loading="lazy">
              </a>
            </div>

            <div class="favorite-card__body">
              <div class="cartTitle">
                <a href="product.php?id=<?= urlencode($code) ?>"
                  class="favorite-card__title-link">
                  <?= htmlspecialchars($name, ENT_QUOTES) ?>
                </a>
              </div>

              <?php if ($meta !== ''): ?>
              <div class="muted small cartMeta">
                <?= htmlspecialchars($meta, ENT_QUOTES) ?>
              </div>
              <?php endif; ?>

              <div class="favorite-card__actions">
                <button class="btn btn--dark btn--sm" type="button" data-add-to-cart
                  data-product-id="<?= htmlspecialchars($code, ENT_QUOTES) ?>"
                  data-product-name="<?= htmlspecialchars($name, ENT_QUOTES) ?>">
                  В корзину
                </button>

                <button class="btn btn--outline btn--sm" type="button" data-fav-remove
                  data-product-db-id="<?= (int)$pidDb ?>"
                  data-product-code="<?= htmlspecialchars($code, ENT_QUOTES) ?>">
                  Удалить
                </button>
              </div>
            </div>
            <div class="cartRight">
              <?= number_format($price, 0, '', ' ') ?>
              ₽
            </div>
          </div>
        </div>

        <?php endforeach; ?>
      </div>

      <div class="empty-state" id="favoritesEmptyState" style="display:none;">
        <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
          <path
            d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z">
          </path>
        </svg>
        <h3>В избранном пока пусто</h3>
        <p>Добавляйте понравившиеся товары, чтобы не потерять</p>
        <a href="catalog.php" class="btn btn--dark">Перейти в каталог</a>
      </div>

      <?php else: ?>
      <div class="empty-state" id="favoritesEmptyState">
        <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
          <path
            d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3  16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z">
          </path>
        </svg>
        <h3>В избранном пока пусто</h3>
        <p>Добавляйте понравившиеся товары, чтобы не потерять</p>
        <a href="catalog.php" class="btn btn--dark">Перейти в каталог</a>
      </div>
      <?php endif; ?>
    </section>

    <section class="account-card" id="coupons-tab" role="tabpanel" aria-labelledby="coupons-tab" hidden>
      <div class="coupons-header">
        <div class="bonus-summary">
          <h2 class="section-title">Бонусы для
            <?= htmlspecialchars($user['login']) ?>
          </h2>

          <div class="bonus-balance-card">
            <div class="bonus-balance">
              <span class="bonus-balance__label">Ваш статус</span>
              <span
                class="bonus-balance__value"><?= htmlspecialchars($statusName) ?></span>
              <span class="bonus-balance__unit">• <?= $ordersCount ?>
                заказ(ов)</span>
            </div>

            <p class="bonus-info">
              Начисляем <b><?= $BONUS_RATE ?>%</b> бонусами от суммы
              товаров (без доставки).
              Сейчас доступно:
              <b><?= number_format($bonusBalance, 0, '', ' ') ?></b>
              баллов.
            </p>
          </div>
        </div>
      </div>

      <div class="bonus-progress">
        <div class="progress-bar">
          <div class="progress-fill"
            style="--progress-width: <?= (int)$progressPct ?>%">
          </div>
        </div>
        <div class="progress-labels">
          <span><?= number_format($itemsTotal, 0, '', ' ') ?>
            ₽ из
            <?= number_format($statusGoal, 0, '', ' ') ?>
            ₽</span>
          <span>До следующего уровня:
            <?= number_format($leftToNext, 0, '', ' ') ?>
            ₽</span>
        </div>
      </div>

      <div class="coupons-section">
        <h3 class="coupons-section__title">Ваши персональные купоны</h3>

        <?php if (!empty($personalCoupons)): ?>
        <div class="coupons-grid">
          <?php foreach ($personalCoupons as $c): ?>
          <div class="coupon-card coupon-card--active">
            <div class="coupon-discount">
              <?= htmlspecialchars($c['discount']) ?>
            </div>
            <div class="coupon-info">
              <h4 class="coupon-title">
                <?= htmlspecialchars($c['title']) ?>
              </h4>
              <p class="coupon-code">
                <?= htmlspecialchars($c['code']) ?>
              </p>
              <p class="coupon-expiry">Действует до
                <?= htmlspecialchars($c['expiry']) ?>
              </p>
            </div>
            <button class="btn btn--dark btn--sm coupon-copy"
              data-coupon-code="<?= htmlspecialchars($c['code']) ?>">
              Скопировать
            </button>
          </div>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="muted">Пока нет персональных купонов. Оформите заказ — появятся предложения</p>
        <?php endif; ?>
      </div>

      <div class="coupons-section">
        <h3 class="coupons-section__title">Статистика</h3>
        <div class="bonus-history">
          <div class="bonus-history-item bonus-history-item--plus">
            <div class="bonus-history-info">
              <h4 class="bonus-history-title">Сумма покупок (товары)</h4>
              <p class="bonus-history-date">За всё время</p>
            </div>
            <div class="bonus-history-amount">
              +<?= number_format($itemsTotal, 0, '', ' ') ?>
              ₽</div>
          </div>

          <div class="bonus-history-item bonus-history-item--minus">
            <div class="bonus-history-info">
              <h4 class="bonus-history-title">Экономия по скидкам</h4>
              <p class="bonus-history-date">Промокоды и скидки</p>
            </div>
            <div class="bonus-history-amount">
              -<?= number_format($discountsTotal, 0, '', ' ') ?>
              ₽</div>
          </div>

          <div class="bonus-history-item bonus-history-item--plus">
            <div class="bonus-history-info">
              <h4 class="bonus-history-title">Бонусы к начислению</h4>
              <p class="bonus-history-date"><?= $BONUS_RATE ?>% от
                суммы товаров</p>
            </div>
            <div class="bonus-history-amount">
              +<?= number_format($bonusBalance, 0, '', ' ') ?>
            </div>
          </div>
        </div>
      </div>

      <div class="coupons-section">
        <h3 class="coupons-section__title">Последние начисления</h3>

        <?php if (!empty($bonusHistory)): ?>
        <div class="bonus-history">
          <?php foreach ($bonusHistory as $h): ?>
          <div
            class="bonus-history-item bonus-history-item--<?= $h['type'] ?>">
            <div class="bonus-history-info">
              <h4 class="bonus-history-title">
                <?= htmlspecialchars($h['title']) ?>
              </h4>
              <p class="bonus-history-date">
                <?= htmlspecialchars($h['date']) ?>
              </p>
            </div>
            <div class="bonus-history-amount">
              <?= htmlspecialchars($h['amount']) ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="muted">Пока нет начислений — оформите первый заказ</p>
        <?php endif; ?>
      </div>
    </section>
  </div>
</main>

<?php
renderFooter();
renderFavoritesSheet();
renderScripts([
    'js/script.js',
    'js/cart.js',
    'js/favorites.js',
    'js/account.js'
]);
?>