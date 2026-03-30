<?php
session_start();
require_once __DIR__ . '/../php/admin_guard.php';
require_once __DIR__ . '/../php/db.php';

function h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function formatPrice($value): string
{
    return number_format((float)$value, 0, '', ' ') . ' ₽';
}

function statusLabel(string $status): string
{
    return match ($status) {
        'new' => 'Новый',
        'processing' => 'В обработке',
        'shipped' => 'Отправлен',
        'completed' => 'Завершён',
        'cancelled' => 'Отменён',
        default => $status,
    };
}

function paymentLabel(?string $payment): string
{
    return match ((string)$payment) {
        'card' => 'Картой',
        'cash' => 'Наличными',
        default => (string)$payment ?: '—',
    };
}

function deliveryLabel(?string $delivery): string
{
    return match ((string)$delivery) {
        'pickup' => 'Самовывоз',
        'delivery' => 'Доставка',
        default => (string)$delivery ?: '—',
    };
}

$q = trim($_GET['q'] ?? '');
$status = trim($_GET['status'] ?? '');
$payment = trim($_GET['payment_method'] ?? '');
$deliveryType = trim($_GET['delivery_type'] ?? '');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');

$allowedStatuses = ['new', 'processing', 'shipped', 'completed', 'cancelled'];
$allowedPayments = ['card', 'cash'];
$allowedDeliveryTypes = ['pickup', 'delivery'];

$sql = "
    SELECT *
    FROM orders
    WHERE 1=1
";
$params = [];

if ($q !== '') {
    $sql .= "
        AND (
            CAST(id AS CHAR) LIKE :q
            OR customer_name LIKE :q
            OR phone LIKE :q
            OR email LIKE :q
            OR delivery_address LIKE :q
            OR pickup_address LIKE :q
            OR promo_code LIKE :q
        )
    ";
    $params[':q'] = '%' . $q . '%';
}

if ($status !== '' && in_array($status, $allowedStatuses, true)) {
    $sql .= " AND status = :status ";
    $params[':status'] = $status;
}

if ($payment !== '' && in_array($payment, $allowedPayments, true)) {
    $sql .= " AND payment_method = :payment_method ";
    $params[':payment_method'] = $payment;
}

if ($deliveryType !== '' && in_array($deliveryType, $allowedDeliveryTypes, true)) {
    $sql .= " AND delivery_type = :delivery_type ";
    $params[':delivery_type'] = $deliveryType;
}

if ($dateFrom !== '') {
    $sql .= " AND DATE(created_at) >= :date_from ";
    $params[':date_from'] = $dateFrom;
}

if ($dateTo !== '') {
    $sql .= " AND DATE(created_at) <= :date_to ";
    $params[':date_to'] = $dateTo;
}

$sql .= " ORDER BY created_at DESC, id DESC ";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$orderIds = array_map(fn($order) => (int)$order['id'], $orders);
$orderItemsMap = [];

if ($orderIds) {
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    $itemsStmt = $pdo->prepare("
        SELECT order_id, product_code, name, price, qty, sum
        FROM order_items
        WHERE order_id IN ($placeholders)
        ORDER BY order_id ASC, id ASC
    ");
    $itemsStmt->execute($orderIds);
    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as $item) {
        $oid = (int)$item['order_id'];
        if (!isset($orderItemsMap[$oid])) {
            $orderItemsMap[$oid] = [];
        }
        $orderItemsMap[$oid][] = $item;
    }
}
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Заказы — Админка</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
<main class="admin-wrap">
  <div class="admin-head">
    <div>
      <h1 style="margin:0 0 8px;">Заказы</h1>
      <p style="margin:0; color:#666;">Просмотр заказов, фильтрация, поиск и изменение статуса.</p>
    </div>
    <a class="btn btn--outline" href="/souvenir_shop/pages/admin.php">Назад</a>
  </div>

  <?php if (!empty($_SESSION['admin_success'])): ?>
    <div style="margin-bottom:16px; padding:12px 14px; border-radius:14px; background:#eef8f0; color:#1f7a43;">
      <?= h($_SESSION['admin_success']) ?>
    </div>
    <?php unset($_SESSION['admin_success']); ?>
  <?php endif; ?>

  <?php if (!empty($_SESSION['admin_error'])): ?>
    <div style="margin-bottom:16px; padding:12px 14px; border-radius:14px; background:#fff0f0; color:#b00020;">
      <?= h($_SESSION['admin_error']) ?>
    </div>
    <?php unset($_SESSION['admin_error']); ?>
  <?php endif; ?>

  <section class="admin-panel" style="margin-bottom:20px;">
    <form method="get">
      <div class="filters">
        <div>
          <label for="q" style="display:block;margin-bottom:8px;font-weight:600;">Поиск</label>
          <input class="input" id="q" name="q" type="text" value="<?= h($q) ?>" placeholder="ID, имя, телефон, email, адрес, промокод">
        </div>

        <div>
          <label for="status" style="display:block;margin-bottom:8px;font-weight:600;">Статус</label>
          <select class="input" id="status" name="status">
            <option value="">Все</option>
            <option value="new" <?= $status === 'new' ? 'selected' : '' ?>>Новый</option>
            <option value="processing" <?= $status === 'processing' ? 'selected' : '' ?>>В обработке</option>
            <option value="shipped" <?= $status === 'shipped' ? 'selected' : '' ?>>Отправлен</option>
            <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Завершён</option>
            <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Отменён</option>
          </select>
        </div>

        <div>
          <label for="payment_method" style="display:block;margin-bottom:8px;font-weight:600;">Оплата</label>
          <select class="input" id="payment_method" name="payment_method">
            <option value="">Все</option>
            <option value="card" <?= $payment === 'card' ? 'selected' : '' ?>>Картой</option>
            <option value="cash" <?= $payment === 'cash' ? 'selected' : '' ?>>Наличными</option>
          </select>
        </div>

        <div>
          <label for="delivery_type" style="display:block;margin-bottom:8px;font-weight:600;">Получение</label>
          <select class="input" id="delivery_type" name="delivery_type">
            <option value="">Все</option>
            <option value="pickup" <?= $deliveryType === 'pickup' ? 'selected' : '' ?>>Самовывоз</option>
            <option value="delivery" <?= $deliveryType === 'delivery' ? 'selected' : '' ?>>Доставка</option>
          </select>
        </div>

        <div class="full-actions">
          <button class="btn btn--dark" type="submit">Применить фильтры</button>
          <a class="btn btn--outline" href="/souvenir_shop/pages/admin_orders.php">Сбросить</a>
          <span style="color:#666;">Найдено заказов: <?= count($orders) ?></span>
        </div>
      </div>
    </form>
  </section>

  <?php if (!$orders): ?>
    <div class="empty">Заказы не найдены.</div>
  <?php else: ?>
    <section class="orders-list">
      <?php foreach ($orders as $order): ?>
        <?php
          $orderId = (int)$order['id'];
          $items = $orderItemsMap[$orderId] ?? [];
          $statusClass = 'status-' . preg_replace('/[^a-z\-]/', '', (string)$order['status']);
        ?>
        <article class="order-card">
          <div class="order-top">
            <div>
              <p class="order-id">Заказ #<?= $orderId ?></p>
              <p class="order-sub">
                Создан: <?= h((string)$order['created_at']) ?>
                <?php if (!empty($order['user_id'])): ?>
                  · Пользователь ID: <?= (int)$order['user_id'] ?>
                <?php endif; ?>
              </p>
            </div>

            <div class="status-badge <?= h($statusClass) ?>">
              <?= h(statusLabel((string)$order['status'])) ?>
            </div>
          </div>

          <div class="order-body">
            <div style="display:grid; gap:14px;">
              <div class="info-grid">
                <div class="info-box">
                  <strong>Покупатель</strong>
                  <div class="meta-line">Имя: <?= h($order['customer_name']) ?: '—' ?></div>
                  <div class="meta-line">Телефон: <?= h($order['phone']) ?: '—' ?></div>
                  <div class="meta-line">Email: <?= h($order['email']) ?: '—' ?></div>
                </div>

                <div class="info-box">
                  <strong>Доставка и оплата</strong>
                  <div class="meta-line">Способ получения: <?= h(deliveryLabel($order['delivery_type'])) ?></div>
                  <div class="meta-line">Способ оплаты: <?= h(paymentLabel($order['payment_method'])) ?></div>
                  <div class="meta-line">Стоимость доставки: <?= formatPrice((float)($order['delivery_fee'] ?? 0)) ?></div>
                  <div class="meta-line">Слот: <?= h($order['delivery_slot']) ?: '—' ?></div>
                </div>

                <div class="info-box">
                  <strong>Адрес</strong>
                  <div class="meta-line">Адрес доставки: <?= h($order['delivery_address']) ?: '—' ?></div>
                  <div class="meta-line">Адрес самовывоза: <?= h($order['pickup_address']) ?: '—' ?></div>
                  <div class="meta-line">Город: <?= h($order['city']) ?: '—' ?></div>
                  <div class="meta-line">
                    Улица / дом / кв.: 
                    <?= h(trim(
                      (string)($order['street'] ?? '') . ' ' .
                      (string)($order['house'] ?? '') . ' ' .
                      (string)($order['apartment'] ?? '')
                    )) ?: '—' ?>
                  </div>
                  <div class="meta-line">Подъезд / доп. инфо: <?= h($order['entrance_info']) ?: '—' ?></div>
                </div>

                <div class="info-box">
                  <strong>Суммы и скидки</strong>
                  <div class="meta-line">Сумма товаров: <?= formatPrice((float)($order['items_sum'] ?? 0)) ?></div>
                  <div class="meta-line">Скидка: <?= (int)($order['discount_percent'] ?? 0) ?>%</div>
                  <div class="meta-line">Сумма скидки: <?= formatPrice((float)($order['discount_sum'] ?? 0)) ?></div>
                  <div class="meta-line">Итого: <?= formatPrice((float)($order['total_sum'] ?? 0)) ?></div>
                  <div class="meta-line">Промокод: <?= h($order['promo_code']) ?: '—' ?></div>
                </div>
              </div>

              <?php if (!empty($order['comment'])): ?>
                <div class="info-box">
                  <strong>Комментарий к заказу</strong>
                  <div class="meta-line"><?= nl2br(h((string)$order['comment'])) ?></div>
                </div>
              <?php endif; ?>
            </div>

            <div style="display:grid; gap:14px;">
              <div class="items-box">
                <strong>Состав заказа</strong>

                <?php if (!$items): ?>
                  <div class="meta-line">Товары не найдены.</div>
                <?php else: ?>
                  <div style="overflow:auto;">
                    <table class="items-table">
                      <thead>
                        <tr>
                          <th>Код</th>
                          <th>Товар</th>
                          <th>Цена</th>
                          <th>Кол-во</th>
                          <th>Сумма</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($items as $item): ?>
                          <tr>
                            <td><?= h($item['product_code']) ?></td>
                            <td><?= h($item['name']) ?></td>
                            <td><?= formatPrice((float)$item['price']) ?></td>
                            <td><?= (int)$item['qty'] ?></td>
                            <td><?= formatPrice((float)$item['sum']) ?></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                <?php endif; ?>

                <div class="summary-row">
                  <span class="summary-chip">Позиций: <?= count($items) ?></span>
                  <span class="summary-chip">Итого: <?= formatPrice((float)($order['total_sum'] ?? 0)) ?></span>
                </div>
              </div>

              <div class="status-box">
                <strong>Изменить статус</strong>
                <form class="status-form" action="/souvenir_shop/php/admin_order_status.php" method="post">
                  <input type="hidden" name="id" value="<?= $orderId ?>">

                  <select class="input" name="status" required>
                    <option value="new" <?= $order['status'] === 'new' ? 'selected' : '' ?>>Новый</option>
                    <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>В обработке</option>
                    <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : '' ?>>Отправлен</option>
                    <option value="completed" <?= $order['status'] === 'completed' ? 'selected' : '' ?>>Завершён</option>
                    <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Отменён</option>
                  </select>

                  <button class="btn btn--dark" type="submit">Сохранить статус</button>
                </form>
              </div>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </section>
  <?php endif; ?>
</main>
</body>
</html>