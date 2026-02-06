<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Подключаем БД
require_once __DIR__ . '/db.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'AUTH_REQUIRED']);
    exit;
}

$uid = (int)$_SESSION['user_id'];

// Определяем action из GET или POST
$action = $_GET['action'] ?? 'list';

// Если action не в GET, проверяем POST
if ($action === 'list' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? 'list';
}

try {
    // ========== LIST ==========
    if ($action === 'list') {
        // Запрос для получения избранного
        $stmt = $pdo->prepare("
            SELECT 
                p.id as id,
                p.name as name,
                p.image as image,
                p.price as price
            FROM favorites f
            JOIN products p ON f.product_id = p.id
            WHERE f.user_id = :uid
            ORDER BY f.created_at DESC
        ");
        $stmt->execute([':uid' => $uid]);
        $favorites = $stmt->fetchAll();
        
        // Для action=list возвращаем МАССИВ
        echo json_encode($favorites ?: [], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // ========== ADD/REMOVE/TOGGLE ==========
    if (in_array($action, ['add', 'remove', 'toggle'])) {
        // Получаем JSON данные
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['product_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'MISSING_PRODUCT_ID']);
            exit;
        }
        
        $productId = $input['product_id'];
        
        // Проверяем, есть ли уже в избранном
        $check = $pdo->prepare("SELECT id FROM favorites WHERE user_id = :uid AND product_id = :pid");
        $check->execute([':uid' => $uid, ':pid' => $productId]);
        $exists = $check->fetch();
        
        $shouldRemove = false;
        
        if ($action === 'remove') {
            $shouldRemove = true;
        } elseif ($action === 'add') {
            $shouldRemove = false;
        } elseif ($action === 'toggle') {
            $shouldRemove = (bool)$exists;
        }
        
        // Удаление
        if ($shouldRemove) {
            if ($exists) {
                $del = $pdo->prepare("DELETE FROM favorites WHERE user_id = :uid AND product_id = :pid");
                $del->execute([':uid' => $uid, ':pid' => $productId]);
            }
            // Для add/remove/toggle возвращаем ОБЪЕКТ
            echo json_encode(['success' => true, 'state' => 'removed']);
            exit;
        }
        
        // Добавление
        if (!$exists) {
            // Проверяем, существует ли товар
            $checkProduct = $pdo->prepare("SELECT id FROM products WHERE id = :pid");
            $checkProduct->execute([':pid' => $productId]);
            
            if (!$checkProduct->fetch()) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'PRODUCT_NOT_FOUND']);
                exit;
            }
            
            // Добавляем в избранное
            $ins = $pdo->prepare("INSERT INTO favorites (user_id, product_id) VALUES (:uid, :pid)");
            $ins->execute([':uid' => $uid, ':pid' => $productId]);
        }
        
        // Для add/remove/toggle возвращаем ОБЪЕКТ
        echo json_encode(['success' => true, 'state' => 'added']);
        exit;
    }
    
    // ========== CLEAR ==========
    if ($action === 'clear') {
        $del = $pdo->prepare("DELETE FROM favorites WHERE user_id = :uid");
        $del->execute([':uid' => $uid]);
        // Для clear возвращаем ОБЪЕКТ
        echo json_encode(['success' => true]);
        exit;
    }
    
    // Неизвестный action
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'UNKNOWN_ACTION']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'SERVER_ERROR',
        'message' => $e->getMessage()
    ]);
}