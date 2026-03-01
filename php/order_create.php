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

/* ===== входные поля ===== */
$customerName = trim((string)($data['customer_name'] ?? ''));
$phoneRaw     = trim((string)($data['phone'] ?? ''));
$email        = trim((string)($data['email'] ?? ''));
$comment      = trim((string)($data['comment'] ?? ''));

$paymentMethod = trim((string)($data['payment_method'] ?? 'card'));
$allowedPayments = ['card', 'cash', 'transfer'];
if (!in_array($paymentMethod, $allowedPayments, true)) {
  $paymentMethod = 'card';
}

$deliveryType = trim((string)($data['delivery_type'] ?? 'delivery'));
$allowedDelivery = ['delivery', 'pickup'];
if (!in_array($deliveryType, $allowedDelivery, true)) {
  $deliveryType = 'delivery';
}

/* адресные поля */
$city    = trim((string)($data['city'] ?? ''));
$street  = trim((string)($data['street'] ?? ''));
$house   = trim((string)($data['house'] ?? ''));
$apt     = trim((string)($data['apartment'] ?? ''));
$entranceInfo = trim((string)($data['entrance_info'] ?? ''));

/* товары */
$items = $data['items'] ?? [];

/* ===== ВАЛИДАЦИЯ ===== */

// Имя: кириллица + пробелы + дефис, минимум 2 символа
if ($customerName === '' || mb_strlen($customerName) < 2) {
  fail(422, 'Введите имя (минимум 2 символа).');
}
if (!preg_match('/^[А-Яа-яЁё][А-Яа-яЁё\s\-]{1,79}$/u', $customerName)) {
  fail(422, 'Имя должно быть кириллицей. Можно пробел и дефис. Пример: Мария Иванова');
}

// Телефон: оставляем только цифры
$digits = preg_replace('/\D+/', '', $phoneRaw) ?? '';
if ($digits === '') {
  fail(422, 'Введите телефон.');
}
// допускаем 11 цифр (7XXXXXXXXXX или 8XXXXXXXXXX) или 10 цифр (XXXXXXXXXX)
if (strlen($digits) === 11 && ($digits[0] === '7' || $digits[0] === '8')) {
  $digits10 = substr($digits, 1);
} elseif (strlen($digits) === 10) {
  $digits10 = $digits;
} else {
  fail(422, 'Введите корректный телефон (например: +7 (999) 123-45-67).');
}

// Email обязателен
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  fail(422, 'Введите корректный email.');
}

// Корзина
if (!is_array($items) || count($items) === 0) {
  fail(422, 'Корзина пустая.');
}

// Нормализуем корзину: product_code => qty
$cart = [];
foreach ($items as $it) {
  $code = trim((string)($it['product_code'] ?? ''));
  $qty  = (int)($it['qty'] ?? 0);
  if ($code === '' || $qty <= 0) continue;
  $cart[$code] = ($cart[$code] ?? 0) + $qty;
}
if (!$cart) {
  fail(422, 'Корзина пустая или некорректная.');
}

// Доставка / самовывоз
$PICKUP_ADDRESS = 'Москва, ул. Примерная, 10';
$DELIVERY_FEE = 200;

$deliveryFee = ($deliveryType === 'delivery') ? $DELIVERY_FEE : 0;

// Адрес: обязателен только при delivery
$deliveryAddressString = '';
$pickupAddress = null;

if ($deliveryType === 'pickup') {
  $pickupAddress = $PICKUP_ADDRESS;
  $city = $street = $house = $apt = $entranceInfo = '';
  $deliveryAddressString = $PICKUP_ADDRESS;
} else {
  if (mb_strlen($city) < 2 || preg_match('/\d/', $city)) {
    fail(422, 'Укажите корректный город.');
  }
  if (mb_strlen($street) < 2) {
    fail(422, 'Укажите корректную улицу.');
  }
  if ($house === '' || !preg_match('/^[0-9А-Яа-яA-Za-z\/\-]{1,10}$/u', $house)) {
    fail(422, 'Укажите корректный дом (например: 10, 10А, 10/2).');
  }

  $deliveryAddressString = $city . ', ' . $street . ', ' . $house;
  if ($apt !== '') $deliveryAddressString .= ', кв. ' . $apt;
  if ($entranceInfo !== '') $deliveryAddressString .= ' (' . $entranceInfo . ')';
}

try {
  $pdo->beginTransaction();

  // Берём товары из products по product_code + блокируем строки до конца транзакции
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

    // считаем in_stock как количество
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

  if (!$orderItems) {
    throw new RuntimeException('Корзина пустая.');
  }

  $totalSum = $itemsSum + $deliveryFee;

  // Создаём заказ
  $stmt = $pdo->prepare("
    INSERT INTO orders
      (user_id, customer_name, phone, email, delivery_type, delivery_fee,
       city, street, house, apartment, entrance_info, pickup_address,
       delivery_address, comment, payment_method, total_sum, status)
    VALUES
      (:user_id, :customer_name, :phone, :email, :delivery_type, :delivery_fee,
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

  // Позиции заказа
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

  // Списание остатков (всегда, т.к. in_stock = количество)
  $upd = $pdo->prepare("
    UPDATE products
    SET in_stock = in_stock - :qty
    WHERE product_code = :code AND in_stock >= :qty
  ");

  foreach ($orderItems as $oi) {
    $upd->execute([
      ':qty'  => (int)$oi['qty'],
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
    'delivery_fee' => $deliveryFee,
    'total_sum' => $totalSum,
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  fail(500, $e->getMessage());
}