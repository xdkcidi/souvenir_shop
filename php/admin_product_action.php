<?php
session_start();
require_once __DIR__ . '/admin_guard.php';
require_once __DIR__ . '/db.php';

$action = trim($_POST['action'] ?? '');

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);

    if ($id <= 0) {
        $_SESSION['admin_error'] = 'Некорректный ID товара.';
        header('Location: /souvenir_shop/pages/admin_products.php');
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);

        $_SESSION['admin_success'] = 'Товар удалён.';
        header('Location: /souvenir_shop/pages/admin_products.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['admin_error'] = 'Не удалось удалить товар.';
        header('Location: /souvenir_shop/pages/admin_products.php');
        exit;
    }
}

if ($action === 'save') {
    $id = (int)($_POST['id'] ?? 0);

    $product_code = trim($_POST['product_code'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $image = trim($_POST['image'] ?? '');
    $image2 = trim($_POST['image2'] ?? '');
    $price = (int)($_POST['price'] ?? 0);
    $in_stock = (int)($_POST['in_stock'] ?? 0);
    $material = trim($_POST['material'] ?? '');
    $color = trim($_POST['color'] ?? '');
    $dimensions = trim($_POST['dimensions'] ?? '');
    $is_personalizable = (int)($_POST['is_personalizable'] ?? 0);
    $meta = trim($_POST['meta'] ?? '');
    $description_full = trim($_POST['description_full'] ?? '');
    $badge = trim($_POST['badge'] ?? '');

    $allowedCategories = ['candles', 'ceramics', 'decor', 'textile', 'postcards', 'sets'];
    $allowedBadges = ['', 'hit', 'new'];

    if (
        $product_code === '' ||
        $category === '' ||
        $name === '' ||
        $price < 0 ||
        $in_stock < 0
    ) {
        $_SESSION['admin_error'] = 'Заполни обязательные поля.';
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/souvenir_shop/pages/admin_products.php'));
        exit;
    }

    if (!in_array($category, $allowedCategories, true)) {
        $_SESSION['admin_error'] = 'Некорректная категория.';
        header('Location: /souvenir_shop/pages/admin_products.php');
        exit;
    }

    if (!in_array($badge, $allowedBadges, true)) {
        $_SESSION['admin_error'] = 'Некорректный бейдж.';
        header('Location: /souvenir_shop/pages/admin_products.php');
        exit;
    }

    try {
        $check = $pdo->prepare("SELECT id FROM products WHERE product_code = ? AND id <> ?");
        $check->execute([$product_code, $id]);

        if ($check->fetch()) {
            $_SESSION['admin_error'] = 'Товар с таким кодом уже существует.';
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/souvenir_shop/pages/admin_products.php'));
            exit;
        }

        if ($id > 0) {
            $stmt = $pdo->prepare("
                UPDATE products SET
                    product_code = :product_code,
                    category = :category,
                    name = :name,
                    image = :image,
                    image2 = :image2,
                    price = :price,
                    in_stock = :in_stock,
                    material = :material,
                    color = :color,
                    dimensions = :dimensions,
                    is_personalizable = :is_personalizable,
                    meta = :meta,
                    description_full = :description_full,
                    badge = :badge
                WHERE id = :id
            ");
            $stmt->execute([
                ':product_code' => $product_code,
                ':category' => $category,
                ':name' => $name,
                ':image' => $image,
                ':image2' => $image2,
                ':price' => $price,
                ':in_stock' => $in_stock,
                ':material' => $material !== '' ? $material : null,
                ':color' => $color !== '' ? $color : null,
                ':dimensions' => $dimensions !== '' ? $dimensions : null,
                ':is_personalizable' => $is_personalizable ? 1 : 0,
                ':meta' => $meta !== '' ? $meta : null,
                ':description_full' => $description_full !== '' ? $description_full : null,
                ':badge' => $badge !== '' ? $badge : null,
                ':id' => $id,
            ]);

            $_SESSION['admin_success'] = 'Товар успешно обновлён.';
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO products (
                    product_code,
                    category,
                    name,
                    image,
                    image2,
                    price,
                    in_stock,
                    material,
                    color,
                    dimensions,
                    is_personalizable,
                    rating,
                    reviews_count,
                    meta,
                    description_full,
                    badge
                ) VALUES (
                    :product_code,
                    :category,
                    :name,
                    :image,
                    :image2,
                    :price,
                    :in_stock,
                    :material,
                    :color,
                    :dimensions,
                    :is_personalizable,
                    0,
                    0,
                    :meta,
                    :description_full,
                    :badge
                )
            ");
            $stmt->execute([
                ':product_code' => $product_code,
                ':category' => $category,
                ':name' => $name,
                ':image' => $image,
                ':image2' => $image2,
                ':price' => $price,
                ':in_stock' => $in_stock,
                ':material' => $material !== '' ? $material : null,
                ':color' => $color !== '' ? $color : null,
                ':dimensions' => $dimensions !== '' ? $dimensions : null,
                ':is_personalizable' => $is_personalizable ? 1 : 0,
                ':meta' => $meta !== '' ? $meta : null,
                ':description_full' => $description_full !== '' ? $description_full : null,
                ':badge' => $badge !== '' ? $badge : null,
            ]);

            $_SESSION['admin_success'] = 'Товар успешно добавлен.';
        }

        header('Location: /souvenir_shop/pages/admin_products.php');
        exit;

    } catch (PDOException $e) {
        $_SESSION['admin_error'] = 'Ошибка при сохранении товара.';
        header('Location: /souvenir_shop/pages/admin_products.php');
        exit;
    }
}

$_SESSION['admin_error'] = 'Неизвестное действие.';
header('Location: /souvenir_shop/pages/admin_products.php');
exit;