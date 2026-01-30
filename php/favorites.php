<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Not authorized']);
    exit;
}

$userId = (int) $_SESSION['user_id'];
$action = $_GET['action'] ?? '';
$productId = isset($_GET['product_id']) ? (int) $_GET['product_id'] : 0;

try {
    if ($action === 'add' && $productId) {
        $stmt = $pdo->prepare("INSERT INTO favorites (user_id, product_id) VALUES (:uid, :pid)");
        $stmt->execute([':uid' => $userId, ':pid' => $productId]);
        echo json_encode(['success' => true]);
        exit;
    } elseif ($action === 'remove' && $productId) {
        $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = :uid AND product_id = :pid");
        $stmt->execute([':uid' => $userId, ':pid' => $productId]);
        echo json_encode(['success' => true]);
        exit;
    } elseif ($action === 'list') {
        $stmt = $pdo->prepare("SELECT id, name, price, image 
                               FROM products 
                               JOIN favorites ON products.id = favorites.product_id 
                               WHERE favorites.user_id = :uid");
        $stmt->execute([':uid' => $userId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($items);
        exit;
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Unknown action']);
        exit;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
    exit;
}
?>
