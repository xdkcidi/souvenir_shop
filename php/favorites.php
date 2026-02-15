<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db.php'; // $pdo

// ==== AUTH ====
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'AUTH_REQUIRED'], JSON_UNESCAPED_UNICODE);
    exit;
}
$uid = (int)$_SESSION['user_id'];

// ==== HELPERS ====
function json_input(): array {
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function is_digits($v): bool {
    return is_string($v) || is_int($v) ? ctype_digit((string)$v) : false;
}

/**
 * Принимает "product_id" из фронта (может быть "candle-1" или 1..24)
 * Возвращает:
 * - dbId (int)  => products.id
 * - code (string) => products.product_code
 */
function resolve_product(PDO $pdo, $incoming): array {
    if ($incoming === null || $incoming === '') {
        return [null, null];
    }

    // 1) если пришло число -> ищем по products.id
    if (is_digits($incoming)) {
        $stmt = $pdo->prepare("SELECT id, product_code FROM products WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => (int)$incoming]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return [null, null];
        return [(int)$row['id'], (string)$row['product_code']];
    }

    // 2) иначе считаем, что это code -> ищем по products.product_code
    $code = (string)$incoming;
    $stmt = $pdo->prepare("SELECT id, product_code FROM products WHERE product_code = :code LIMIT 1");
    $stmt->execute([':code' => $code]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return [null, null];
    return [(int)$row['id'], (string)$row['product_code']];
}

// ==== ACTION ====
$action = $_GET['action'] ?? null;
$input  = json_input();
if (!$action) $action = $input['action'] ?? 'list';

try {
    // ===== LIST =====
    if ($action === 'list') {
        // ВАЖНО: id отдаём как product_code, чтобы совпадал с data-product-id на кнопках
        $stmt = $pdo->prepare("
            SELECT
                p.product_code AS id,
                p.name AS name,
                p.image AS image,
                p.price AS price
            FROM favorites f
            JOIN products p ON f.product_id = p.id
            WHERE f.user_id = :uid
            ORDER BY f.created_at DESC
        ");
        $stmt->execute([':uid' => $uid]);
        $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($favorites ?: [], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ===== ADD / REMOVE / TOGGLE =====
    if (in_array($action, ['add', 'remove', 'toggle'], true)) {

        if (!isset($input['product_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'MISSING_PRODUCT_ID'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        [$productDbId, $productCode] = resolve_product($pdo, $input['product_id']);

        if (!$productDbId) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'PRODUCT_NOT_FOUND',
                'sent' => $input['product_id'] ?? null
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Есть ли уже в избранном
        $check = $pdo->prepare("SELECT id FROM favorites WHERE user_id = :uid AND product_id = :pid LIMIT 1");
        $check->execute([':uid' => $uid, ':pid' => $productDbId]);
        $exists = (bool)$check->fetch(PDO::FETCH_ASSOC);

        $shouldRemove = false;
        if ($action === 'remove') $shouldRemove = true;
        if ($action === 'add')    $shouldRemove = false;
        if ($action === 'toggle') $shouldRemove = $exists;

        if ($shouldRemove) {
            if ($exists) {
                $del = $pdo->prepare("DELETE FROM favorites WHERE user_id = :uid AND product_id = :pid");
                $del->execute([':uid' => $uid, ':pid' => $productDbId]);
            }
            echo json_encode(['success' => true, 'state' => 'removed', 'id' => $productCode], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Добавление (если уже есть — просто вернём added, без 500)
        if (!$exists) {
            $ins = $pdo->prepare("INSERT INTO favorites (user_id, product_id) VALUES (:uid, :pid)");
            try {
                $ins->execute([':uid' => $uid, ':pid' => $productDbId]);
            } catch (PDOException $e) {
                // 1062 = Duplicate entry (на случай гонки/двойного клика)
                if ((int)($e->errorInfo[1] ?? 0) !== 1062) {
                    throw $e;
                }
            }
        }

        echo json_encode(['success' => true, 'state' => 'added', 'id' => $productCode], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ===== CLEAR =====
    if ($action === 'clear') {
        $del = $pdo->prepare("DELETE FROM favorites WHERE user_id = :uid");
        $del->execute([':uid' => $uid]);

        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'UNKNOWN_ACTION'], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'SERVER_ERROR',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
