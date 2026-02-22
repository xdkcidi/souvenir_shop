<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isAuth = isset($_SESSION['user_id']);
$pageTitle = $pageTitle ?? 'Лавка — сувениры ручной работы';
$pageCss = $pageCss ?? [];
?>
<!doctype html>
<html lang="ru" data-auth="<?= $isAuth ? '1' : '0' ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle) ?></title>

  <link rel="stylesheet" href="/souvenir_shop/css/main.css">
  <link rel="stylesheet" href="/souvenir_shop/css/style.css">

  <?php foreach ($pageCss as $css): ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($css) ?>">
  <?php endforeach; ?>
</head>
<body>

<?php include __DIR__ . '/header.php'; ?>