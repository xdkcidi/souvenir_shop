<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['ok' => false, 'error' => 'Нужна авторизация.'], JSON_UNESCAPED_UNICODE);
  exit;
}

require_once __DIR__ . '/db.php'; // $pdo (PDO)

$userId = (int)$_SESSION['user_id'];

function fail(int $code, string $msg): void {
  http_response_code($code);
  echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
  exit;
}

$data = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($data)) {
  fail(400, 'Некорректные данные.');
}

/* входные поля */
$customerName = trim((string)($data['customer_name'] ?? ''));
$phoneRaw     = trim((string)($data['phone'] ?? ''));
$email        = trim((string)($data['email'] ?? ''));
$comment      = trim((string)($data['comment'] ?? ''));

$paymentMethod = trim((string)($data['payment_method'] ?? 'card'));
$allowedPayments = ['card', 'cash', 'transfer'];
if (!in_array($paymentMethod, $allowedPayments, true)) $paymentMethod = 'card';

$deliveryType = trim((string)($data['delivery_type'] ?? 'delivery'));
$allowedDelivery = ['delivery', 'pickup'];
if (!in_array($deliveryType, $allowedDelivery, true)) $deliveryType = 'delivery';

$city    = trim((string)($data['city'] ?? ''));
$street  = trim((string)($data['street'] ?? ''));
$house   = trim((string)($data['house'] ?? ''));
$apt     = trim((string)($data['apartment'] ?? ''));
$entranceInfo = trim((string)($data['entrance_info'] ?? ''));

$deliveryDate = trim((string)($data['delivery_date'] ?? ''));
$deliverySlot = trim((string)($data['delivery_slot'] ?? ''));

$promoCodeRaw = trim((string)($data['promo_code'] ?? ''));
$promoCode = $promoCodeRaw !== '' ? strtoupper($promoCodeRaw) : '';

$items = $data['items'] ?? [];

/* ===== ВАЛИДАЦИЯ ===== */

// имя
if ($customerName === '' || mb_strlen($customerName) < 2) {
  fail(422, 'Введите имя (минимум 2 символа).');
}
if (!preg_match('/^[А-Яа-яЁё][А-Яа-яЁё\s\-]{1,79}$/u', $customerName)) {
  fail(422, 'Имя должно быть кириллицей. Можно пробел и дефис. Пример: Мария Иванова');
}

// телефон
$digits = preg_replace('/\D+/', '', $phoneRaw) ?? '';
if ($digits === '') fail(422, 'Введите телефон.');

if (strlen($digits) === 11 && ($digits[0] === '7' || $digits[0] === '8')) {
  $digits10 = substr($digits, 1);
} elseif (strlen($digits) === 10) {
  $digits10 = $digits;
} else {
  fail(422, 'Введите корректный телефон (например: +7 (999) 123-45-67).');
}

// email
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  fail(422, 'Введите корректный email.');
}

// корзина
if (!is_array($items) || count($items) === 0) {
  fail(422, 'Корзина пустая.');
}

// нормализация корзины product_code => qty
$cart = [];
foreach ($items as $it) {
  $code = trim((string)($it['product_code'] ?? ''));
  $qty  = (int)($it['qty'] ?? 0);
  if ($code === '' || $qty <= 0) continue;
  $cart[$code] = ($cart[$code] ?? 0) + $qty;
}
if (!$cart) fail(422, 'Корзина пустая или некорректная.');

// доставка/самовывоз
$PICKUP_ADDRESS = 'Москва, ул. Примерная, 10';
$DELIVERY_FEE = 200;

$deliveryFee = ($deliveryType === 'delivery') ? $DELIVERY_FEE : 0;

// дата/слот только для delivery
$deliveryDateDb = null;
$deliverySlotDb = null;

if ($deliveryType === 'delivery') {
  if ($deliveryDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $deliveryDate)) {
    fail(422, 'Выберите дату доставки.');
  }

  $tomorrow = (new DateTime('tomorrow'))->format('Y-m-d');
  if ($deliveryDate < $tomorrow) {
    fail(422, 'Дата доставки должна быть не раньше завтрашнего дня.');
  }

  $allowedSlots = ['10:00-14:00','14:00-18:00','18:00-22:00'];
  if ($deliverySlot === '' || !in_array($deliverySlot, $allowedSlots, true)) {
    fail(422, 'Выберите интервал времени доставки.');
  }

  $deliveryDateDb = $deliveryDate;
  $deliverySlotDb = $deliverySlot;
} else {
  // pickup — дата/интервал не нужны
  $deliveryDateDb = null;
  $deliverySlotDb = null;
}

// адрес
$deliveryAddressString = '';
$pickupAddress = null;

if ($deliveryType === 'pickup') {
  $pickupAddress = $PICKUP_ADDRESS;
  $deliveryAddressString = $PICKUP_ADDRESS;
  $city = $street = $house = $apt = $entranceInfo = '';
} else {
  if (mb_strlen($city) < 2 || preg_match('/\d/', $city)) fail(422, 'Укажите корректный город.');
  if (mb_strlen($street) < 2) fail(422, 'Укажите корректную улицу.');
  if ($house === '' || !preg_match('/^[0-9А-Яа-яA-Za-z\/\-]{1,10}$/u', $house)) {
    fail(422, 'Укажите корректный дом (например: 10, 10А, 10/2).');
  }

  $deliveryAddressString = $city . ', ' . $street . ', ' . $house;
  if ($apt !== '') $deliveryAddressString .= ', кв. ' . $apt;
  if ($entranceInfo !== '') $deliveryAddressString .= ' (' . $entranceInfo . ')';
}

/* ===== ПРОМОКОДЫ (серверная истина) ===== */
$PROMOS = [
  'WELCOME10' => 10, // только первый заказ
  'SPRING15'  => 15, // одноразовый
  'LOYAL20'   => 20, // одноразовый, уровень "Постоянный" и выше
  'VIP25'     => 25, // одноразовый, уровень VIP
];

try {
  $pdo->beginTransaction();

  // ---- проверка промокода (делаем внутри транзакции для надёжности) ----
  $discountPercent = 0;

  if ($promoCode !== '') {
    if (!isset($PROMOS[$promoCode])) {
      fail(422, 'Промокод недействителен.');
    }

    // уже использован?
    $stmt = $pdo->prepare("SELECT 1 FROM promo_redemptions WHERE user_id = :uid AND promo_code = :code LIMIT 1");
    $stmt->execute([':uid' => $userId, ':code' => $promoCode]);
    if ($stmt->fetchColumn()) {
      fail(422, 'Промокод уже использован.');
    }

    // WELCOME10 только если заказов 0
    if ($promoCode === 'WELCOME10') {
      $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = :uid");
      $stmt->execute([':uid' => $userId]);
      $cnt = (int)$stmt->fetchColumn();
      if ($cnt > 0) {
        fail(422, 'WELCOME10 доступен только для первого заказа.');
      }
    }

    // уровень по сумме items_sum (до текущего заказа)
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(items_sum),0) FROM orders WHERE user_id = :uid");
    $stmt->execute([':uid' => $userId]);
    $itemsTotal = (int)$stmt->fetchColumn();

    $statusName = 'Новичок';
    if ($itemsTotal >= 30000) $statusName = 'VIP';
    elseif ($itemsTotal >= 10000) $statusName = 'Постоянный';

    if ($promoCode === 'LOYAL20' && !in_array($statusName, ['Постоянный','VIP'], true)) {
      fail(422, 'Промокод LOYAL20 доступен только уровню "Постоянный" и выше.');
    }
    if ($promoCode === 'VIP25' && $statusName !== 'VIP') {
      fail(422, 'Промокод VIP25 доступен только уровню "VIP".');
    }

    $discountPercent = (int)$PROMOS[$promoCode];
  }

  // ---- товары + блокировка ----
  $codes = array_keys($cart);
  $in = implode(',', array_fill(0, count($codes), '?'));

  $stmt = $pdo->prepare("
    SELECT product_code, name, price, in_stock
    FROM products
    WHERE product_code IN ($in)
    FOR UPDATE
  ");
  $stmt->execute($codes);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $products = [];
  foreach ($rows as $r) {
    $products[(string)$r['product_code']] = $r;
  }

  $orderItems = [];
  $itemsSum = 0;

  foreach ($cart as $code => $qty) {
    if (!isset($products[$code])) {
      throw new RuntimeException("Товар не найден: {$code}");
    }

    $name  = (string)$products[$code]['name'];
    $price = (int)$products[$code]['price'];
    $stock = (int)($products[$code]['in_stock'] ?? 0);

    if ($stock < $qty) {
      throw new RuntimeException("Недостаточно товара: {$name}. Осталось: {$stock}");
    }

    $sum = $price * $qty;
    $itemsSum += $sum;

    $orderItems[] = [
      'product_code' => $code,
      'name' => $name,
      'price' => $price,
      'qty' => $qty,
      'sum' => $sum,
    ];
  }

  if (!$orderItems) throw new RuntimeException('Корзина пустая.');

  // скидка
  $discountSum = (int)round($itemsSum * ($discountPercent / 100));
  if ($discountSum < 0) $discountSum = 0;
  if ($discountSum > $itemsSum) $discountSum = $itemsSum;

  $totalSum = ($itemsSum - $discountSum) + $deliveryFee;

  // ---- создаём заказ ----
  $stmt = $pdo->prepare("
    INSERT INTO orders
      (user_id, customer_name, phone, email,
       delivery_type, delivery_fee, delivery_date, delivery_slot,
       promo_code, discount_percent, discount_sum, items_sum,
       city, street, house, apartment, entrance_info, pickup_address,
       delivery_address, comment, payment_method, total_sum, status)
    VALUES
      (:user_id, :customer_name, :phone, :email,
       :delivery_type, :delivery_fee, :delivery_date, :delivery_slot,
       :promo_code, :discount_percent, :discount_sum, :items_sum,
       :city, :street, :house, :apartment, :entrance_info, :pickup_address,
       :delivery_address, :comment, :payment_method, :total_sum, 'new')
  ");

  $stmt->execute([
    ':user_id' => $userId,
    ':customer_name' => $customerName,
    ':phone' => '+7' . $digits10,
    ':email' => $email,

    ':delivery_type' => $deliveryType,
    ':delivery_fee' => $deliveryFee,
    ':delivery_date' => $deliveryDateDb,
    ':delivery_slot' => $deliverySlotDb,

    ':promo_code' => ($promoCode !== '' ? $promoCode : null),
    ':discount_percent' => $discountPercent,
    ':discount_sum' => $discountSum,
    ':items_sum' => $itemsSum,

    ':city' => ($deliveryType === 'delivery' ? $city : null),
    ':street' => ($deliveryType === 'delivery' ? $street : null),
    ':house' => ($deliveryType === 'delivery' ? $house : null),
    ':apartment' => ($deliveryType === 'delivery' && $apt !== '' ? $apt : null),
    ':entrance_info' => ($deliveryType === 'delivery' && $entranceInfo !== '' ? $entranceInfo : null),
    ':pickup_address' => ($deliveryType === 'pickup' ? $pickupAddress : null),

    ':delivery_address' => $deliveryAddressString,
    ':comment' => ($comment !== '' ? $comment : null),
    ':payment_method' => $paymentMethod,
    ':total_sum' => $totalSum,
  ]);

  $orderId = (int)$pdo->lastInsertId();

  // ---- фиксируем использование промокода (одноразовость) ----
  if ($promoCode !== '' && $discountPercent > 0) {
    $stmt = $pdo->prepare("
      INSERT INTO promo_redemptions (user_id, promo_code, order_id)
      VALUES (:uid, :code, :oid)
    ");
    $stmt->execute([
      ':uid' => $userId,
      ':code' => $promoCode,
      ':oid' => $orderId,
    ]);
  }

  // ---- позиции заказа ----
  $stmt = $pdo->prepare("
    INSERT INTO order_items
      (order_id, product_code, name, price, qty, sum)
    VALUES
      (:order_id, :product_code, :name, :price, :qty, :sum)
  ");

  foreach ($orderItems as $oi) {
    $stmt->execute([
      ':order_id' => $orderId,
      ':product_code' => $oi['product_code'],
      ':name' => $oi['name'],
      ':price' => $oi['price'],
      ':qty' => $oi['qty'],
      ':sum' => $oi['sum'],
    ]);
  }

  // ---- списание остатков ----
  $upd = $pdo->prepare("
    UPDATE products
    SET in_stock = in_stock - :qty
    WHERE product_code = :code AND in_stock >= :qty
  ");

  foreach ($orderItems as $oi) {
    $upd->execute([
      ':qty' => (int)$oi['qty'],
      ':code' => $oi['product_code'],
    ]);

    if ($upd->rowCount() !== 1) {
      throw new RuntimeException("Не удалось списать остаток для товара: {$oi['name']}");
    }
  }

  $pdo->commit();

  echo json_encode([
    'ok' => true,
    'order_id' => $orderId,
    'items_sum' => $itemsSum,
    'discount_percent' => $discountPercent,
    'discount_sum' => $discountSum,
    'delivery_fee' => $deliveryFee,
    'total_sum' => $totalSum,
    'promo_code' => ($promoCode !== '' ? $promoCode : null),
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  fail(500, $e->getMessage());
}