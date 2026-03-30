<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header('Location: /souvenir_shop/index.php');
    exit;
}

if (!isset($_SESSION['user_role']) || (int)$_SESSION['user_role'] !== 1) {
    http_response_code(403);
    exit('Доступ запрещён.');
}