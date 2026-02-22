<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db.php'; // $pdo

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['success' => false, 'error' => 'AUTH_REQUIRED'], JSON_UNESCAPED_UNICODE);
  exit;
}
$uid = (int)$_SESSION['user_id'];

$payload = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $payload = json_decode(file_get_contents('php://input'), true);
  if (!is_array($payload)) $payload = [];
}

$action = $_GET['action'] ?? ($payload['action'] ?? 'list');

function ok($data = []) {
  echo json_encode(['success' => true] + $data, JSON_UNESCAPED_UNICODE);
  exit;
}
function err($code, $http = 400, $extra = []) {
  http_response_code($http);
  echo json_encode(['success' => false, 'error' => $code] + $extra, JSON_UNESCAPED_UNICODE);
  exit;
}

try {

  // ---------- LIST ----------
  if ($action === 'list') {
    $stmt = $pdo->prepare("
      SELECT
        ci.product_code,
        ci.qty,
        p.id AS product_id,
        p.category,
        p.name,
        p.image,
        p.price,
        p.meta,
        p.badge
      FROM cart_items ci
      JOIN products p ON p.product_code = ci.product_code
      WHERE ci.user_id = ?
      ORDER BY ci.updated_at DESC
    ");
    $stmt->execute([$uid]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalQty = 0;
    $totalSum = 0;
    foreach ($items as &$it) {
      $q = (int)$it['qty'];
      $p = (int)$it['price'];
      $totalQty += $q;
      $totalSum += $q * $p;
      $it['qty'] = $q;
      $it['price'] = $p;
      $it['sum'] = $q * $p;
    }
    unset($it);

    ok(['items' => $items, 'totalQty' => $totalQty, 'totalSum' => $totalSum]);
  }

  // ---------- COUNT ----------
  if ($action === 'count') {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(qty),0) FROM cart_items WHERE user_id=?");
    $stmt->execute([$uid]);
    ok(['count' => (int)$stmt->fetchColumn()]);
  }

  // ---------- ADD ----------
  if ($action === 'add') {
    $code = trim((string)($payload['product_code'] ?? ''));
    $qtyAdd = (int)($payload['qty'] ?? 1);
    if ($qtyAdd < 1) $qtyAdd = 1;

    if ($code === '') err('BAD_PRODUCT_CODE', 422);

    // товар существует?
    $check = $pdo->prepare("SELECT product_code FROM products WHERE product_code=?");
    $check->execute([$code]);
    if (!$check->fetchColumn()) err('PRODUCT_NOT_FOUND', 404);

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
      SELECT qty
      FROM cart_items
      WHERE user_id=? AND product_code=?
      FOR UPDATE
    ");
    $stmt->execute([$uid, $code]);
    $existing = $stmt->fetchColumn();

    if ($existing !== false) {
      $newQty = (int)$existing + $qtyAdd;
      $upd = $pdo->prepare("UPDATE cart_items SET qty=? WHERE user_id=? AND product_code=?");
      $upd->execute([$newQty, $uid, $code]);
    } else {
      $ins = $pdo->prepare("INSERT INTO cart_items (user_id, product_code, qty) VALUES (?, ?, ?)");
      $ins->execute([$uid, $code, $qtyAdd]);
      $newQty = $qtyAdd;
    }

    $pdo->commit();
    ok(['product_code' => $code, 'qty' => $newQty]);
  }

  // ---------- SET QTY ----------
  if ($action === 'setQty') {
    $code = trim((string)($payload['product_code'] ?? ''));
    $qty = (int)($payload['qty'] ?? 1);

    if ($code === '') err('BAD_PRODUCT_CODE', 422);

    if ($qty <= 0) {
      $del = $pdo->prepare("DELETE FROM cart_items WHERE user_id=? AND product_code=?");
      $del->execute([$uid, $code]);
      ok(['product_code' => $code, 'qty' => 0]);
    }

    $upd = $pdo->prepare("UPDATE cart_items SET qty=? WHERE user_id=? AND product_code=?");
    $upd->execute([$qty, $uid, $code]);

    if ($upd->rowCount() === 0) {
      // если вдруг ещё не было — создаём (и проверяем, что товар существует)
      $check = $pdo->prepare("SELECT product_code FROM products WHERE product_code=?");
      $check->execute([$code]);
      if (!$check->fetchColumn()) err('PRODUCT_NOT_FOUND', 404);

      $ins = $pdo->prepare("INSERT INTO cart_items (user_id, product_code, qty) VALUES (?, ?, ?)");
      $ins->execute([$uid, $code, $qty]);
    }

    ok(['product_code' => $code, 'qty' => $qty]);
  }

  // ---------- REMOVE ----------
  if ($action === 'remove') {
    $code = trim((string)($payload['product_code'] ?? ''));
    if ($code === '') err('BAD_PRODUCT_CODE', 422);

    $del = $pdo->prepare("DELETE FROM cart_items WHERE user_id=? AND product_code=?");
    $del->execute([$uid, $code]);

    ok(['product_code' => $code]);
  }

  // ---------- CLEAR ----------
  if ($action === 'clear') {
    $del = $pdo->prepare("DELETE FROM cart_items WHERE user_id=?");
    $del->execute([$uid]);
    ok();
  }

  err('UNKNOWN_ACTION', 400);

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  err('SERVER_ERROR', 500, ['message' => $e->getMessage()]);
}