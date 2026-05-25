<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'METHOD_NOT_ALLOWED']);
    exit;
}

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'AUTH_REQUIRED']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'INVALID_JSON']);
    exit;
}

$codes = $data['items'] ?? [];

if (!is_array($codes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'INVALID_ITEMS']);
    exit;
}

$codes = array_values(array_unique(array_filter(array_map('trim', $codes))));

if (count($codes) < 2 || count($codes) > 4) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'NEED_2_TO_4_ITEMS']);
    exit;
}

$placeholders = implode(',', array_fill(0, count($codes), '?'));
$sql = "SELECT product_code, name, price, image, meta, in_stock
        FROM products
        WHERE product_code IN ($placeholders)";
$stmt = $pdo->prepare($sql);
$stmt->execute($codes);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$byCode = [];
foreach ($rows as $row) {
    $byCode[$row['product_code']] = $row;
}

$items = [];
$originalSum = 0;

foreach ($codes as $code) {
    if (empty($byCode[$code])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'PRODUCT_NOT_FOUND']);
        exit;
    }

    $row = $byCode[$code];

    if ((int)$row['in_stock'] <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'OUT_OF_STOCK']);
        exit;
    }

    $price = (int)$row['price'];
    $originalSum += $price;

    $items[] = [
        'product_code' => $row['product_code'],
        'name'         => $row['name'],
        'price'        => $price,
        'qty'          => 1,
        'image'        => $row['image'],
        'meta'         => $row['meta'] ?? '',
        'line_sum'     => $price,
    ];
}

$discountPercent = 5;
$finalSum = (int)round($originalSum * (100 - $discountPercent) / 100);

$_SESSION['gift_checkout'] = [
    'is_gift_bundle'   => true,
    'package_type'     => 'Подарочная коробка',
    'discount_percent' => $discountPercent,
    'original_sum'     => $originalSum,
    'final_sum'        => $finalSum,
    'items'            => $items,
    'note'             => 'Подарочный набор. Упаковать в красивую коробку и добавить открытку.',
];

echo json_encode([
    'success'  => true,
    'redirect' => '../pages/checkout.php?mode=gift',
]);
