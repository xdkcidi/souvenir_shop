<?php
session_start();

if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}

require_once __DIR__ . '/../php/db.php'; // $pdo

$userId  = (int)$_SESSION['user_id'];
$errors  = [];
$success = '';

// ===== 1) Загружаем пользователя (без delivery_address, но с avatar) =====
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

// ===== CSRF =====
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf_token'];

// ===== 2) Функция статуса заказа (лейбл + класс) =====
function orderStatusMeta(string $status): array {
  $s = strtolower(trim($status));
  return match ($s) {
    'new' => ['НОВЫЙ', 'status-processing'],
    'processing' => ['В ОБРАБОТКЕ', 'status-processing'],
    'delivered' => ['ДОСТАВЛЕН', 'status-delivered'],
    'canceled' => ['ОТМЕНЁН', 'status-canceled'],
    default => ['В ОБРАБОТКЕ', 'status-processing'],
  };
}

// ===== 3) POST: Профиль (телефон + аватар) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['profile_action'])) {
  $token = $_POST['csrf_token'] ?? '';
  if (!$token || !hash_equals($_SESSION['csrf_token'], $token)) {
    $errors[] = 'Ошибка безопасности. Обновите страницу и попробуйте снова.';
  } else {
    $action = (string)($_POST['profile_action'] ?? '');

    // 3.1 сохранить телефон
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

    // 3.2 загрузить аватар (users.avatar)
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
              // удалить старый аватар (если был)
              $old = trim((string)($user['avatar'] ?? ''));
              if ($old && str_contains($old, '/img/avatars/')) {
                $oldFs = __DIR__ . '/../' . ltrim($old, '/');
                if (is_file($oldFs)) @unlink($oldFs);
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

// ===== 4) Экспорт данных (JSON) =====
if (isset($_GET['export']) && $_GET['export'] === '1') {
  header('Content-Type: application/json; charset=utf-8');
  header('Content-Disposition: attachment; filename="lavka_account_data.json"');

  $profile = [
    'id'    => (int)$user['id'],
    'login' => (string)$user['login'],
    'email' => (string)$user['email'],
    'phone' => (string)($user['phone'] ?? ''),
    'avatar'=> (string)($user['avatar'] ?? ''),
  ];

  $stmt = $pdo->prepare("
    SELECT id, total_sum, status, created_at,
           delivery_type, delivery_fee, delivery_address, pickup_address,
           delivery_date, delivery_slot,
           promo_code, discount_percent, discount_sum, items_sum
    FROM orders
    WHERE user_id = :uid
    ORDER BY id DESC
  ");
  $stmt->execute([':uid' => $userId]);
  $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $stmt = $pdo->prepare("
    SELECT p.product_code, p.name, p.price, p.meta
    FROM favorites f
    JOIN products p ON p.id = f.product_id
    WHERE f.user_id = :uid
    ORDER BY f.created_at DESC
  ");
  $stmt->execute([':uid' => $userId]);
  $favoritesExport = $stmt->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode([
    'profile' => $profile,
    'orders' => $orders,
    'favorites' => $favoritesExport,
    'exported_at' => date('c'),
  ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

  exit;
}

// ===== 5) POST: Безопасность (пароль + удаление) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['security_action'])) {
  $action = (string)($_POST['security_action'] ?? '');

  $token = $_POST['csrf_token'] ?? '';
  if (!$token || !hash_equals($_SESSION['csrf_token'], $token)) {
    $errors[] = 'Ошибка безопасности (CSRF). Обновите страницу и попробуйте снова.';
  } else {

    // 5.1 смена пароля
    if ($action === 'change_password') {
      $current = (string)($_POST['current_password'] ?? '');
      $new1    = (string)($_POST['new_password'] ?? '');
      $new2    = (string)($_POST['new_password2'] ?? '');

      if (mb_strlen($new1) < 6) {
        $errors[] = 'Новый пароль должен быть не короче 6 символов.';
      } elseif ($new1 !== $new2) {
        $errors[] = 'Пароли не совпадают.';
      } else {
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || empty($row['password_hash'])) {
          $errors[] = 'Не найден хэш пароля в базе (password_hash).';
        } elseif (!password_verify($current, $row['password_hash'])) {
          $errors[] = 'Текущий пароль неверный.';
        } else {
          $hash = password_hash($new1, PASSWORD_DEFAULT);
          $upd = $pdo->prepare("UPDATE users SET password_hash = :h WHERE id = :id");
          $upd->execute([':h' => $hash, ':id' => $userId]);
          $success = 'Пароль успешно изменён.';
        }
      }
    }

    // 5.2 удаление аккаунта
    if ($action === 'delete_account') {
      $confirm = trim((string)($_POST['confirm_delete'] ?? ''));
      if ($confirm !== 'УДАЛИТЬ') {
        $errors[] = 'Для удаления аккаунта введите слово: УДАЛИТЬ';
      } else {
        try {
          $pdo->beginTransaction();

          $pdo->prepare("DELETE FROM favorites WHERE user_id = ?")->execute([$userId]);
          $pdo->prepare("DELETE FROM cart_items WHERE user_id = ?")->execute([$userId]);
          $pdo->prepare("DELETE FROM promo_redemptions WHERE user_id = ?")->execute([$userId]);

          $stmt = $pdo->prepare("SELECT id FROM orders WHERE user_id = ?");
          $stmt->execute([$userId]);
          $orderIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

          if ($orderIds) {
            $in = implode(',', array_fill(0, count($orderIds), '?'));
            $pdo->prepare("DELETE FROM order_items WHERE order_id IN ($in)")->execute($orderIds);
          }
          $pdo->prepare("DELETE FROM orders WHERE user_id = ?")->execute([$userId]);

          $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);

          $pdo->commit();
          session_destroy();
          header('Location: ../index.php');
          exit;

        } catch (Throwable $e) {
          if ($pdo->inTransaction()) $pdo->rollBack();
          $errors[] = 'Не удалось удалить аккаунт. Попробуйте позже.';
        }
      }
    }
  }
}

// ===== 6) Избранное (для вкладки + счётчик) =====
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

// ===== 7) Заказы (топ-5 + все) =====
$stmt = $pdo->prepare("
  SELECT id, total_sum, status, created_at,
         delivery_type, delivery_fee, delivery_address, pickup_address,
         delivery_date, delivery_slot,
         promo_code, discount_percent, discount_sum, items_sum
  FROM orders
  WHERE user_id = :uid
  ORDER BY id DESC
  LIMIT 5
");
$stmt->execute([':uid' => $userId]);
$ordersTop = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
  SELECT id, total_sum, status, created_at,
         delivery_type, delivery_fee, delivery_address, pickup_address,
         delivery_date, delivery_slot,
         promo_code, discount_percent, discount_sum, items_sum
  FROM orders
  WHERE user_id = :uid
  ORDER BY id DESC
");
$stmt->execute([':uid' => $userId]);
$ordersAll = $stmt->fetchAll(PDO::FETCH_ASSOC);

$topIds = array_map(fn($o) => (int)$o['id'], $ordersTop);
$restOrders = array_values(array_filter($ordersAll, fn($o) => !in_array((int)$o['id'], $topIds, true)));

// товары по заказам
$orderItems = [];
if (!empty($ordersAll)) {
  $allIds = array_map(fn($o) => (int)$o['id'], $ordersAll);
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
    if (!isset($orderItems[$oid])) $orderItems[$oid] = [];

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

// ===== 8) Профиль: реальные цифры + статус/прогресс + последний заказ =====
$stmt = $pdo->prepare("
  SELECT COUNT(*) AS orders_count,
         COALESCE(SUM(items_sum),0) AS items_total
  FROM orders
  WHERE user_id = :uid
");
$stmt->execute([':uid' => $userId]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

$ordersCount = (int)($stats['orders_count'] ?? 0);
$itemsTotal  = (int)($stats['items_total'] ?? 0);

$BONUS_RATE = 3;
$bonusBalance = (int)round($itemsTotal * ($BONUS_RATE / 100));

if ($itemsTotal >= 30000) {
  $statusName = 'VIP';
  $nextName = null; $nextGoal = null;
} elseif ($itemsTotal >= 10000) {
  $statusName = 'Постоянный';
  $nextName = 'VIP'; $nextGoal = 30000;
} else {
  $statusName = 'Новичок';
  $nextName = 'Постоянный'; $nextGoal = 10000;
}

$progressPct = 0;
$leftToNext = 0;
if ($nextGoal) {
  $progressPct = (int)round(min(100, ($itemsTotal / $nextGoal) * 100));
  $leftToNext  = max(0, $nextGoal - $itemsTotal);
}

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
<!doctype html>
<html lang="ru" data-auth="<?php echo $isAuth ? '1' : '0'; ?>">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Личный кабинет — Лавка</title>
  <meta name="description" content="Личный кабинет Лавка: ваши данные, адрес доставки, избранное и купоны." />
  <link rel="stylesheet" href="../css/cart.css"/>
  <link rel="stylesheet" href="../css/account.css"/>
  <link rel="stylesheet" href="../css/style.css"/>
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
        <!-- 🔑 ИКОНКА АККАУНТА - всегда показываем для авторизованного пользователя -->
        <a class="iconBtn iconBtn--auth"
           href="account.php"
           aria-label="Личный кабинет">
          <svg viewBox="0 0 24 24" aria-hidden="true" class="iconUser">
            <circle cx="12" cy="8" r="3.2" />
            <path d="M5 19c1.4-3 3.6-4.5 7-4.5s5.6 1.5 7 4.5" />
          </svg>
        </a>

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
    <div class="account-page__inner">
        <nav class="breadcrumbs" aria-label="Хлебные крошки">
            <ol>
                <li><a href="../index.php">Главная</a></li>
                <li><span aria-current="page">Личный кабинет</span></li>
            </ol>
        </nav>

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

        <!-- Навигация по вкладкам -->
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
                    <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"></path>
                </svg>
                Избранное
                <span class="badge"><?= (int)$favoritesCount ?></span>
            </button>
            <button class="account-tab" role="tab" data-tab="coupons">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="8" width="18" height="12" rx="2" ry="2"></rect>
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <path d="M12 8v-4a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v4"></path>
                </svg>
                Бонусы
            </button>
            <button class="account-tab" role="tab" data-tab="security">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
                Безопасность
            </button>
        </div>

<?php
// ===== CSRF =====
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf_token'];

// ===== Статистика профиля (заказы / сумма товаров) =====
$stmt = $pdo->prepare("
  SELECT
    COUNT(*) AS orders_count,
    COALESCE(SUM(items_sum), 0) AS items_total
  FROM orders
  WHERE user_id = :uid
");
$stmt->execute([':uid' => $userId]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

$ordersCount = (int)($stats['orders_count'] ?? 0);
$itemsTotal  = (int)($stats['items_total'] ?? 0);

// бонусы (как у тебя): 3%
$BONUS_RATE = 3;
$bonusBalance = (int)round($itemsTotal * ($BONUS_RATE / 100));

// статус пользователя
if ($itemsTotal >= 30000) {
  $statusName = 'VIP';
} elseif ($itemsTotal >= 10000) {
  $statusName = 'Постоянный';
} else {
  $statusName = 'Новичок';
}

// ===== Обработка формы профиля (телефон) и загрузка аватара =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = $_POST['csrf_token'] ?? '';
  if (!$token || !hash_equals($_SESSION['csrf_token'], $token)) {
    $errors[] = 'Ошибка безопасности. Обновите страницу и попробуйте снова.';
  } else {

    $action = $_POST['profile_action'] ?? 'save_profile';

    // 1) Сохранить телефон
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

    // 2) Загрузить аватар (users.avatar)
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

              // опционально удаляем старый аватар
              $old = trim((string)($user['avatar'] ?? ''));
              if ($old && str_contains($old, '/img/avatars/')) {
                $oldFs = __DIR__ . '/../' . ltrim($old, '/');
                if (is_file($oldFs)) @unlink($oldFs);
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
?>
<!-- Вкладка "Профиль" -->
<section class="account-card active" id="profile-tab" role="tabpanel" aria-labelledby="profile-tab">
  <div class="profile-header">
    <div class="avatar-section">

      <div class="avatar" style="background: linear-gradient(135deg, #398550 0%, #164324 100%); overflow:hidden;">
        <?php if (!empty($user['avatar'])): ?>
          <img src="<?= htmlspecialchars($user['avatar'], ENT_QUOTES) ?>"
               alt="Аватар"
               style="width:100%;height:100%;object-fit:cover;display:block;">
        <?php else: ?>
          <?= mb_strtoupper(mb_substr($user['login'], 0, 1, 'UTF-8')) ?>
        <?php endif; ?>
      </div>

      <form method="post" enctype="multipart/form-data" style="margin:0;" id="avatarForm">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES) ?>">
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
        <h2 class="profile-name"><?= htmlspecialchars($user['login'], ENT_QUOTES) ?></h2>
        <span class="status-badge status-badge--<?= $statusName === 'VIP' ? 'vip' : ($statusName === 'Постоянный' ? 'loyal' : 'new') ?>">
          <?= htmlspecialchars($statusName, ENT_QUOTES) ?>
        </span>
      </div>

      <p class="profile-email"><?= htmlspecialchars($user['email'], ENT_QUOTES) ?></p>

      <div class="profile-stats">
        <div class="stat stat--card">
          <span class="stat-value"><?= (int)$ordersCount ?></span>
          <span class="stat-label">заказов</span>
        </div>

        <div class="stat stat--card">
          <span class="stat-value"><?= (int)$bonusBalance ?></span>
          <span class="stat-label">бонусов</span>
        </div>

        <div class="stat stat--card">
          <span class="stat-value"><?= (int)$favoritesCount ?></span>
          <span class="stat-label">в избранном</span>
        </div>
      </div>

      <!-- Статус и прогресс -->
      <div class="status-progress">
        <?php if ($nextGoal): ?>
          <div class="status-progress__top">
            <span class="muted small">
              До уровня <b><?= htmlspecialchars($nextName, ENT_QUOTES) ?></b> осталось:
              <b><?= number_format((int)$leftToNext, 0, '', ' ') ?> ₽</b>
            </span>
            <span class="muted small"><?= (int)$progressPct ?>%</span>
          </div>

          <div class="status-progress__bar" role="progressbar"
               aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= (int)$progressPct ?>">
            <div class="status-progress__fill" style="width: <?= (int)$progressPct ?>%"></div>
          </div>
        <?php else: ?>
          <div class="muted small">Вы на максимальном уровне ✅</div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Последний заказ / empty -->
  <div class="profile-latest">
    <?php if ($lastOrder): ?>
      <?php [$lbl, $cls] = orderStatusMeta((string)$lastOrder['status']); ?>
      <div class="card" style="padding:16px;">
        <div style="display:flex; justify-content:space-between; gap:12px; align-items:flex-start;">
          <div>
            <div class="muted small">Последний заказ</div>
            <div style="font-weight:800; font-size:18px; margin-top:4px;">
              №<?= (int)$lastOrder['id'] ?>
              <span class="order-status <?= $cls ?>" style="margin-left:10px; font-size:12px; padding:6px 10px;">
                <?= htmlspecialchars($lbl, ENT_QUOTES) ?>
              </span>
            </div>
            <div class="muted small" style="margin-top:6px;">
              <?= date('d.m.Y', strtotime((string)$lastOrder['created_at'])) ?>
            </div>
          </div>

          <div style="font-weight:900; font-size:22px; white-space:nowrap;">
            <?= number_format((int)$lastOrder['total_sum'], 0, '', ' ') ?> ₽
          </div>
        </div>

        <div style="margin-top:12px;">
          <a class="btn btn--dark btn--sm" href="account.php?tab=orders">Открыть историю заказов</a>
        </div>
      </div>
    <?php else: ?>
      <div class="card" style="padding:16px;">
        <div style="font-weight:800; font-size:18px;">Пока нет заказов</div>
        <div class="muted" style="margin-top:6px;">
          Сделайте первый заказ — начислим бонусы 🙂
        </div>
        <div style="margin-top:12px;">
          <a class="btn btn--dark btn--sm" href="catalog.php">Перейти в каталог</a>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <div class="profile-card">
    <h3 class="profile-card__title">Личные данные</h3>

    <form method="post" class="profile-form" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES) ?>">
      <input type="hidden" name="profile_action" value="save_profile">

      <div class="profile-form__grid">
        <div class="profile-form__group">
          <label class="profile-form__label" for="phone">Телефон</label>
          <input class="profile-input"
                 type="tel"
                 id="phone"
                 name="phone"
                 placeholder="+7 (999) 000-00-00"
                 value="<?= htmlspecialchars($user['phone'] ?? '', ENT_QUOTES) ?>">
        </div>
      </div>

 <div class="profile-form__actions profile-form__actions--row">
  <button class="btn btn--dark" type="submit">Сохранить изменения</button>

  <a href="../php/logout.php" class="btn btn--outline logout-link">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
      <polyline points="16 17 21 12 16 7"></polyline>
      <line x1="21" y1="12" x2="9" y2="12"></line>
    </svg>
    Выйти из аккаунта
  </a>
</div>
    </form>
  </div>
</section>

<!-- Вкладка "Заказы" -->
<section class="account-card" id="orders-tab" role="tabpanel" aria-labelledby="orders-tab" hidden>
  <div class="section-header">
    <h2 class="section-title">История заказов</h2>

    <div style="display:flex; gap:12px; align-items:center;">
      <button type="button" class="link-all" id="showAllOrdersBtn">Все заказы →</button>
      <button type="button" class="link-all" id="collapseOrdersBtn" style="display:none;">Свернуть</button>
    </div>
  </div>

  <div class="orders-list" id="ordersList">
    <?php
      $renderOrderCard = function(array $o) use ($orderItems) {

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

        // для строки итогов:
        $itemsSumNum = $itemsSum;
        $discountSumNum = $discountSum;
        $deliveryFeeNum = ($deliveryType === 'pickup') ? 0 : $deliveryFee;

        // товары
        $items = $orderItems[$oid] ?? [];

        ob_start();
    ?>
      <div class="order-card">
        <div class="order-header">
          <div>
            <h3 class="order-number">Заказ №<?= $oid ?></h3>
            <span class="order-date"><?= $date ?></span>
          </div>
          <span class="order-status <?= $cls ?>"><?= htmlspecialchars($label) ?></span>
        </div>

        <div class="order-body">
          <!-- товары (фото + кол-во) -->
          <div class="order-products" style="gap:12px;">
            <?php if ($items): ?>
              <?php foreach ($items as $it): ?>
                <div class="product-preview" style="align-items:center;">
                  <div class="product-preview__image"
                      style="width:64px;height:64px;border-radius:14px;overflow:hidden;background:#f3f3f3;flex:0 0 auto;">
                    <img src="<?= htmlspecialchars($it['img']) ?>"
                        alt="<?= htmlspecialchars($it['name']) ?>"
                        style="width:100%;height:100%;object-fit:cover;display:block;">
                  </div>

                  <div style="display:flex;flex-direction:column;gap:2px;">
                    <span class="product-preview__name"><?= htmlspecialchars($it['name']) ?></span>
                    <span class="muted small">Количество: <?= (int)$it['qty'] ?></span>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="muted small">Товары не найдены</div>
            <?php endif; ?>
          </div>

          <!-- данные заказа -->
          <div class="order-footer" style="margin-top:14px; display:grid; gap:8px;">
            <!-- итог крупно -->
            <div class="order-total"><?= number_format($totalNum, 0, '', ' ') ?> ₽</div>

            <!-- строка итогов (сразу под суммой) -->
            <div class="muted small">
              Товары <b><?= number_format($itemsSumNum, 0, '', ' ') ?> ₽</b>
              <span aria-hidden="true"> • </span>
              Скидка <b>−<?= number_format($discountSumNum, 0, '', ' ') ?> ₽</b>
              <span aria-hidden="true"> • </span>
              Доставка <b><?= number_format($deliveryFeeNum, 0, '', ' ') ?> ₽</b>
              <span aria-hidden="true"> • </span>
              Итог <b><?= number_format($totalNum, 0, '', ' ') ?> ₽</b>
            </div>

            <!-- способ получения -->
            <div class="muted small">
              Способ получения: <b><?= htmlspecialchars($deliveryText) ?></b>
              <?php if ($deliveryType !== 'pickup'): ?>
                <span class="muted small">(+<?= (int)$deliveryFee ?> ₽)</span>
              <?php endif; ?>
            </div>

            <!-- дата/интервал или часы самовывоза -->
            <?php if ($deliveryType !== 'pickup' && $deliveryDate && $deliverySlot): ?>
              <div class="muted small">
                Доставка: <b><?= htmlspecialchars(date('d.m.Y', strtotime($deliveryDate))) ?></b>,
                интервал <b><?= htmlspecialchars($deliverySlot) ?></b>
              </div>
            <?php elseif ($deliveryType === 'pickup'): ?>
              <div class="muted small">
                Самовывоз: <b>ежедневно 10:00–20:00</b>
              </div>
            <?php endif; ?>

            <!-- адрес -->
            <div class="muted small">
              Адрес: <b><?= htmlspecialchars($addr) ?></b>
            </div>

            <!-- промокод (только если была скидка) -->
            <?php if ($discountSumNum > 0): ?>
              <div class="muted small">
                Промокод: <b><?= htmlspecialchars($promo ?: '—') ?></b>
                <?php if ($discountPercent > 0): ?>
                  <span class="muted small">(−<?= (int)$discountPercent ?>%)</span>
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
    <?php foreach ($restOrders as $o) echo $renderOrderCard($o); ?>
  </div>

  <!-- если заказов нет -->
  <div class="empty-state" id="ordersEmptyState" style="<?= empty($ordersAll) ? 'display:block;' : 'display:none;' ?>">
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

<!-- Вкладка "Избранное" -->
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
    <!-- ВАЖНО: cartList, чтобы применились стили корзины -->
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

        <div class="card"
             style="padding:14px; margin-bottom:12px; position:relative;"
             data-fav-item
             data-product-code="<?= htmlspecialchars($code, ENT_QUOTES) ?>">

          <div class="cartRow">
            <div class="cartItemImg">
              <a href="product.php?id=<?= urlencode($code) ?>" style="display:block;">
                <img
                  src="<?= htmlspecialchars($img, ENT_QUOTES) ?>"
                  alt="<?= htmlspecialchars($name, ENT_QUOTES) ?>"
                  loading="lazy"
                >
              </a>
            </div>

            <div style="flex:1;">
              <div class="cartTitle">
                <a href="product.php?id=<?= urlencode($code) ?>" style="color:inherit; text-decoration:none;">
                  <?= htmlspecialchars($name, ENT_QUOTES) ?>
                </a>
              </div>

              <?php if ($meta !== ''): ?>
                <div class="muted small cartMeta" style="color:#999;">
                  <?= htmlspecialchars($meta, ENT_QUOTES) ?>
                </div><br>
              <?php endif; ?>

              <div style="display:flex; align-items:center; gap:12px; margin-top:10px; flex-wrap:wrap;">
                <button class="btn btn--dark btn--sm"
                        type="button"
                        data-add-to-cart
                        data-product-id="<?= htmlspecialchars($code, ENT_QUOTES) ?>"
                        data-product-name="<?= htmlspecialchars($name, ENT_QUOTES) ?>">
                  В корзину
                </button>

                <button class="btn btn--outline btn--sm"
                        type="button"
                        data-fav-remove
                        data-product-db-id="<?= (int)$pidDb ?>"
                        data-product-code="<?= htmlspecialchars($code, ENT_QUOTES) ?>">
                  Удалить
                </button>
              </div>
            </div>

            <!-- Цена ТОЛЬКО здесь (как в корзине) -->
            <div class="cartRight"><?= number_format($price, 0, '', ' ') ?> ₽</div>
          </div>
        </div>

      <?php endforeach; ?>
    </div>

    <div class="empty-state" id="favoritesEmptyState" style="display:none;">
      <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"></path>
      </svg>
      <h3>В избранном пока пусто</h3>
      <p>Добавляйте понравившиеся товары, чтобы не потерять</p>
      <a href="catalog.php" class="btn btn--dark">Перейти в каталог</a>
    </div>

  <?php else: ?>
    <div class="empty-state" id="favoritesEmptyState">
      <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3  16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"></path>
      </svg>
      <h3>В избранном пока пусто</h3>
      <p>Добавляйте понравившиеся товары, чтобы не потерять</p>
      <a href="catalog.php" class="btn btn--dark">Перейти в каталог</a>
    </div>
  <?php endif; ?>
</section>

<?php
// ===== БОНУСЫ: персонализация на основе заказов =====

// общая сумма товаров по всем заказам (без доставки и скидок)
$stmt = $pdo->prepare("
  SELECT
    COUNT(*) AS orders_count,
    COALESCE(SUM(items_sum), 0) AS items_total,
    COALESCE(SUM(discount_sum), 0) AS discounts_total
  FROM orders
  WHERE user_id = :uid
");
$stmt->execute([':uid' => $userId]);
$bonusStats = $stmt->fetch(PDO::FETCH_ASSOC);

$ordersCount = (int)($bonusStats['orders_count'] ?? 0);
$itemsTotal  = (int)($bonusStats['items_total'] ?? 0);
$discountsTotal = (int)($bonusStats['discounts_total'] ?? 0);

// начисляем 3% бонусов от суммы товаров (можешь поменять процент)
$BONUS_RATE = 3; // %
$bonusBalance = (int)round($itemsTotal * ($BONUS_RATE / 100));

// статус по сумме покупок
if ($itemsTotal >= 30000) {
  $statusName = 'VIP';
  $statusGoal = 50000;
} elseif ($itemsTotal >= 10000) {
  $statusName = 'Постоянный';
  $statusGoal = 30000;
} else {
  $statusName = 'Новичок';
  $statusGoal = 10000;
}

$progressPct = $statusGoal > 0 ? min(100, (int)round(($itemsTotal / $statusGoal) * 100)) : 0;
$leftToNext = max(0, $statusGoal - $itemsTotal);

// персональные купоны (пример логики)
$personalCoupons = [];

if ($ordersCount === 0) {
  $personalCoupons[] = ['title' => 'На первую покупку', 'code' => 'WELCOME10', 'discount' => '10%', 'expiry' => '30.04.2026'];
} else {
  // всем покупавшим
  $personalCoupons[] = ['title' => 'Скидка для клиента', 'code' => 'SPRING15', 'discount' => '15%', 'expiry' => '15.05.2026'];
}

if ($statusName === 'Постоянный') {
  $personalCoupons[] = ['title' => 'Постоянному клиенту', 'code' => 'LOYAL20', 'discount' => '20%', 'expiry' => '01.06.2026'];
}
if ($statusName === 'VIP') {
  $personalCoupons[] = ['title' => 'VIP-бонус', 'code' => 'VIP25', 'discount' => '25%', 'expiry' => '01.06.2026'];
}

// какие промокоды уже использованы пользователем
$stmt = $pdo->prepare("SELECT promo_code FROM promo_redemptions WHERE user_id = :uid");
$stmt->execute([':uid' => $userId]);
$used = array_flip(array_map('strtoupper', array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'promo_code')));

// helper: проверка "не использован"
$notUsed = fn(string $code) => !isset($used[strtoupper($code)]);

// собираем доступные купоны
$personalCoupons = [];

// WELCOME10 — только если заказов 0 и не использован
if ($ordersCount === 0 && $notUsed('WELCOME10')) {
  $personalCoupons[] = ['title' => 'На первый заказ', 'code' => 'WELCOME10', 'discount' => '10%', 'expiry' => '—'];
}

// Новичок — кроме WELCOME10 ничего
// Постоянный — одноразовый LOYAL20
if ($statusName === 'Постоянный' && $notUsed('LOYAL20')) {
  $personalCoupons[] = ['title' => 'Постоянному клиенту', 'code' => 'LOYAL20', 'discount' => '20%', 'expiry' => '—'];
}

// VIP — одноразовый VIP25
if ($statusName === 'VIP' && $notUsed('VIP25')) {
  $personalCoupons[] = ['title' => 'VIP-бонус', 'code' => 'VIP25', 'discount' => '25%', 'expiry' => '—'];
}

// SPRING15 — одноразовый для всех (если не использован)
if ($notUsed('SPRING15')) {
  $personalCoupons[] = ['title' => 'Сезонная скидка', 'code' => 'SPRING15', 'discount' => '15%', 'expiry' => '—'];
}

// история начислений: начисление бонусов за последние 5 заказов
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
  $sum = (int)$o['items_sum'];
  $earned = (int)round($sum * ($BONUS_RATE / 100));
  $bonusHistory[] = [
    'type' => 'plus',
    'title' => 'Начисление за заказ №' . (int)$o['id'],
    'date' => date('d.m.Y', strtotime($o['created_at'])),
    'amount' => '+' . $earned
  ];
}
?>
<section class="account-card" id="coupons-tab" role="tabpanel" aria-labelledby="coupons-tab" hidden>
  <div class="coupons-header">
    <div class="bonus-summary">
      <h2 class="section-title">Бонусы для <?= htmlspecialchars($user['login']) ?></h2>

      <div class="bonus-balance-card">
        <div class="bonus-balance">
          <span class="bonus-balance__label">Ваш статус</span>
          <span class="bonus-balance__value"><?= htmlspecialchars($statusName) ?></span>
          <span class="bonus-balance__unit">• <?= $ordersCount ?> заказ(ов)</span>
        </div>

        <p class="bonus-info">
          Начисляем <b><?= $BONUS_RATE ?>%</b> бонусами от суммы товаров (без доставки).
          Сейчас доступно: <b><?= number_format($bonusBalance, 0, '', ' ') ?></b> баллов.
        </p>
      </div>
    </div>

    <div class="coupon-activate">
      <input type="text" class="input coupon-input" placeholder="Введите код купона" id="coupon-code">
      <button class="btn btn--dark" id="activate-coupon-btn">Активировать</button>
    </div>
  </div>

  <div class="bonus-progress">
    <div class="progress-bar">
      <div class="progress-fill" style="width: <?= (int)$progressPct ?>%"></div>
    </div>
    <div class="progress-labels">
      <span><?= number_format($itemsTotal, 0, '', ' ') ?> ₽ из <?= number_format($statusGoal, 0, '', ' ') ?> ₽</span>
      <span>До следующего уровня: <?= number_format($leftToNext, 0, '', ' ') ?> ₽</span>
    </div>
  </div>

  <div class="coupons-section">
    <h3 class="coupons-section__title">Ваши персональные купоны</h3>

    <?php if (!empty($personalCoupons)): ?>
      <div class="coupons-grid">
        <?php foreach ($personalCoupons as $c): ?>
          <div class="coupon-card coupon-card--active">
            <div class="coupon-discount"><?= htmlspecialchars($c['discount']) ?></div>
            <div class="coupon-info">
              <h4 class="coupon-title"><?= htmlspecialchars($c['title']) ?></h4>
              <p class="coupon-code"><?= htmlspecialchars($c['code']) ?></p>
              <p class="coupon-expiry">Действует до <?= htmlspecialchars($c['expiry']) ?></p>
            </div>
            <button class="btn btn--dark btn--sm coupon-copy" data-coupon-code="<?= htmlspecialchars($c['code']) ?>">
              Скопировать
            </button>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="muted">Пока нет персональных купонов. Оформите заказ — появятся предложения 🙂</p>
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
        <div class="bonus-history-amount">+<?= number_format($itemsTotal, 0, '', ' ') ?> ₽</div>
      </div>

      <div class="bonus-history-item bonus-history-item--minus">
        <div class="bonus-history-info">
          <h4 class="bonus-history-title">Экономия по скидкам</h4>
          <p class="bonus-history-date">Промокоды и скидки</p>
        </div>
        <div class="bonus-history-amount">-<?= number_format($discountsTotal, 0, '', ' ') ?> ₽</div>
      </div>

      <div class="bonus-history-item bonus-history-item--plus">
        <div class="bonus-history-info">
          <h4 class="bonus-history-title">Бонусы к начислению</h4>
          <p class="bonus-history-date"><?= $BONUS_RATE ?>% от суммы товаров</p>
        </div>
        <div class="bonus-history-amount">+<?= number_format($bonusBalance, 0, '', ' ') ?></div>
      </div>
    </div>
  </div>

  <div class="coupons-section">
    <h3 class="coupons-section__title">Последние начисления</h3>

    <?php if (!empty($bonusHistory)): ?>
      <div class="bonus-history">
        <?php foreach ($bonusHistory as $h): ?>
          <div class="bonus-history-item bonus-history-item--<?= $h['type'] ?>">
            <div class="bonus-history-info">
              <h4 class="bonus-history-title"><?= htmlspecialchars($h['title']) ?></h4>
              <p class="bonus-history-date"><?= htmlspecialchars($h['date']) ?></p>
            </div>
            <div class="bonus-history-amount"><?= htmlspecialchars($h['amount']) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="muted">Пока нет начислений — оформите первый заказ 🙂</p>
    <?php endif; ?>
  </div>
</section>

        <!-- Вкладка "Безопасность" -->
<section class="account-card" id="security-tab" role="tabpanel" aria-labelledby="security-tab" hidden>
  <h2 class="section-title">Безопасность</h2>

  <div class="security-list">

    <!-- 1) Смена пароля -->
    <div class="security-item">
      <div class="security-item__info">
        <h3 class="security-item__title">Смена пароля</h3>
        <p class="security-item__desc">Рекомендуем менять пароль каждые 3 месяца</p>

        <form method="post" style="margin-top:12px; display:grid; gap:10px; max-width:420px;">
          <input type="hidden" name="security_action" value="change_password">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES) ?>">

          <input class="input" type="password" name="current_password" placeholder="Текущий пароль" required>
          <input class="input" type="password" name="new_password" placeholder="Новый пароль (мин. 6 символов)" required>
          <input class="input" type="password" name="new_password2" placeholder="Повторите новый пароль" required>

          <button class="btn btn--outline" type="submit">Изменить пароль</button>
        </form>
      </div>
    </div>

    <!-- 2) Экспорт данных -->
    <div class="security-item">
      <div class="security-item__info">
        <h3 class="security-item__title">Экспорт данных</h3>
        <p class="security-item__desc">Скачайте данные аккаунта: профиль, заказы и избранное</p>
      </div>

      <a class="btn btn--outline" href="account.php?export=1">
        Скачать JSON
      </a>
    </div>

    <!-- 3) Удаление аккаунта -->
    <div class="security-item security-item--danger">
      <div class="security-item__info">
        <h3 class="security-item__title">Удаление аккаунта</h3>
        <p class="security-item__desc">Это действие необратимо. Все данные будут удалены.</p>

        <form method="post" style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
          <input type="hidden" name="security_action" value="delete_account">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES) ?>">

          <input class="input" type="text" name="confirm_delete" placeholder='Введите "УДАЛИТЬ"' required style="max-width:220px;">
          <button class="btn btn--danger" type="submit">Удалить аккаунт</button>
        </form>
      </div>
    </div>

  </div>
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
      <!-- Блок с логотипом -->
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
        
        <!-- Соцсети с иконками -->
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

      <!-- Навигация -->
      <div>
        <h3 class="footer__title">Навигация</h3>
        <ul class="footer__list">
          <li><a class="footer__link" href="../index.php">Главная</a></li>
          <li><a class="footer__link" href="about.php">О компании</a></li>
          <li><a class="footer__link" href="catalog.php">Каталог</a></li>
          <li><a class="footer__link" href="registration.php">Регистрация</a></li>
        </ul>
      </div>

      <!-- Информация -->
      <div>
        <h3 class="footer__title">Информация</h3>
        <ul class="footer__list">
          <li><a class="footer__link" href="about.php#delivery">Доставка</a></li>
          <li><a class="footer__link" href="about.php#returns">Возврат</a></li>
          <li><a class="footer__link" href="about.php#materials">Материалы</a></li>
          <li><a class="footer__link" href="about.php#contacts">Контакты</a></li>
        </ul>
      </div>

      <!-- Рассылка -->
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
    
    // Обработка формы подписки (опционально)
    const newsletterForm = document.querySelector('[data-newsletter-form]');
    if (newsletterForm) {
      newsletterForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const emailInput = this.querySelector('#newsletter-email');
        const email = emailInput.value.trim();
        
        if (email && email.includes('@')) {
          // Здесь можно добавить AJAX-запрос для отправки данных
          console.log('Подписка на рассылку:', email);
          alert('Спасибо за подписку! На ' + email + ' отправлено письмо с подтверждением.');
          emailInput.value = '';
        }
      });
    }
  });
</script>

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
<script src="../js/account.js" defer></script>
<script src="../js/cart.js" defer></script>

</body>
</html>