<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: cart.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];

require_once __DIR__ . '/../php/db.php';

$checkoutMode = $_GET['mode'] ?? '';
$giftCheckout = ($checkoutMode === 'gift') ? ($_SESSION['gift_checkout'] ?? null) : null;
$isGiftCheckout = is_array($giftCheckout) && !empty($giftCheckout['items']);

$checkoutItems = [];
$checkoutTotalQty = 0;
$checkoutOriginalSum = 0;
$checkoutFinalSum = 0;

if ($isGiftCheckout) {
    $checkoutItems = $giftCheckout['items'];
    $checkoutTotalQty = count($checkoutItems);
    $checkoutOriginalSum = (int)($giftCheckout['original_sum'] ?? 0);
    $checkoutFinalSum = (int)($giftCheckout['final_sum'] ?? 0);
}

$checkoutModeValue = $isGiftCheckout ? 'gift' : 'cart';

$giftFrontendData = [
    'items' => $checkoutItems,
    'totalQty' => $checkoutTotalQty,
    'originalSum' => $checkoutOriginalSum,
    'finalSum' => $checkoutFinalSum,
    'discountSum' => max(0, $checkoutOriginalSum - $checkoutFinalSum),
];

// статистика заказов
$stmt = $pdo->prepare("
  SELECT COUNT(*) AS orders_count,
         COALESCE(SUM(items_sum),0) AS items_total
  FROM orders WHERE user_id = :uid
");
$stmt->execute([':uid' => $userId]);
$st = $stmt->fetch(PDO::FETCH_ASSOC);

$ordersCount = (int)($st['orders_count'] ?? 0);
$itemsTotal  = (int)($st['items_total'] ?? 0);

// уровень
if ($itemsTotal >= 30000) {
    $statusName = 'VIP';
} elseif ($itemsTotal >= 10000) {
    $statusName = 'Постоянный';
} else {
    $statusName = 'Новичок';
}

// использованные промокоды
$stmt = $pdo->prepare("SELECT promo_code FROM promo_redemptions WHERE user_id = :uid");
$stmt->execute([':uid' => $userId]);
$used = array_flip(array_map('strtoupper', array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'promo_code')));

$notUsed = fn (string $code) => !isset($used[strtoupper($code)]);

// доступные промокоды
$availablePromos = [];

if ($ordersCount === 0 && $notUsed('WELCOME10')) {
    $availablePromos['WELCOME10'] = 10;
}
if ($notUsed('SPRING15')) {
    $availablePromos['SPRING15'] = 15;
}

if ($statusName === 'Постоянный' && $notUsed('LOYAL20')) {
    $availablePromos['LOYAL20'] = 20;
}
if ($statusName === 'VIP' && $notUsed('VIP25')) {
    $availablePromos['VIP25'] = 25;
}

?>
<?php
$basePath = '..';
require_once __DIR__ . '/../includes/layout.php';

renderHead(
    'Оформление заказа — Лавка',
    'Оформление заказа в интернет-магазине Лавка: доставка, оплата, промокоды и подтверждение покупки.',
    [
        'css/main.css',
        'css/style.css',
        'css/checkout.css'
    ]
);

renderHeader();
?>

<main class="container section checkout-page">
  <h1 class="h2">Оформление заказа</h1>
  <p class="muted">Заполните данные и подтвердите заказ.</p>

  <!-- Итого -->
  <div class="card" style="padding:16px; margin-top:12px;">
    <div class="muted small">Итого</div>

    <div style="display:grid; gap:6px; margin-top:8px;">
      <div style="display:flex; justify-content:space-between; gap:12px;">
        <span class="muted small">Товары</span>
        <span class="muted small"><span id="itemsSum">0</span> ₽</span>
      </div>

      <div style="display:flex; justify-content:space-between; gap:12px;">
        <span class="muted small">Скидка</span>
        <span class="muted small">−<span id="discountSum">0</span> ₽</span>
      </div>

      <div style="display:flex; justify-content:space-between; gap:12px;">
        <span class="muted small">Доставка</span>
        <span class="muted small"><span id="deliveryFee">0</span> ₽</span>
      </div>

      <div style="display:flex; justify-content:space-between; gap:12px; align-items:baseline; margin-top:6px;">
        <span class="small" style="font-weight:600;">К оплате</span>
        <span class="h2" style="margin:0;"><span id="totalSum">0</span> ₽</span>
      </div>

      <div class="muted small">Товаров: <span id="totalQty">0</span></div>
    </div>
  </div>

  <form id="checkoutForm" method="post" action="#" class="card" style="padding:16px; margin-top:12px;">
    <!-- Форма -->
    <div class="mb-3">
      <label class="small">Имя и фамилия</label>
      <input class="input input--lg" name="customer_name" id="customerName" required placeholder="Мария Иванова"
        autocomplete="name">
      <div class="muted small" id="nameHint" style="margin-top:6px;"></div>
    </div>

    <div class="mb-3">
      <label class="small">Телефон</label>
      <input class="input input--lg" name="phone" id="phoneInput" required placeholder="+7 (___) ___-__-__"
        inputmode="tel" autocomplete="tel">
      <div class="muted small" id="phoneHint" style="margin-top:6px;"></div>
    </div>

    <div class="mb-3">
      <label class="small">Email</label>
      <input class="input input--lg" name="email" id="emailInput" type="email" required placeholder="name@mail.com"
        autocomplete="email">
      <div class="muted small" id="emailHint" style="margin-top:6px;"></div>
    </div>

    <div class="mb-3">
      <div class="small" style="margin-bottom:8px;">Способ получения</div>

      <label style="display:flex; gap:10px; align-items:center; margin-bottom:8px;">
        <input type="radio" name="delivery_type" value="delivery" checked>
        <span>Доставка курьером (+200 ₽)</span>
      </label>

      <label style="display:flex; gap:10px; align-items:center;">
        <input type="radio" name="delivery_type" value="pickup">
        <span>Самовывоз (бесплатно)</span>
      </label>

      <div class="muted small" style="margin-top:8px;">
        Адрес самовывоза: <b>Москва, ул. Примерная, 10</b>
      </div>
    </div>

    <!-- Дата и время (только для доставки) -->
    <div id="deliveryTimeBlock" class="mb-3">
      <div class="small" style="margin-bottom:8px;">Дата и время доставки</div>

      <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
        <div>
          <input class="input" type="date" name="delivery_date" id="deliveryDate" required>
          <div class="muted small" style="margin-top:6px;">Доставка доступна с завтрашнего дня</div>
        </div>

        <div>
          <select class="input" name="delivery_slot" id="deliverySlot" required>
            <option value="">Выберите интервал</option>
            <option value="10:00-14:00">10:00–14:00</option>
            <option value="14:00-18:00">14:00–18:00</option>
            <option value="18:00-22:00">18:00–22:00</option>
          </select>
        </div>
      </div>

      <div class="muted small" id="deliveryTimeHint" style="margin-top:6px;"></div>
    </div>

    <!-- Подсказка для самовывоза -->
    <div id="pickupHint" class="muted small" style="display:none; margin-top:-6px; margin-bottom:12px;">
      Самовывоз доступен ежедневно с <b>10:00</b> до <b>20:00</b>.
    </div>

    <!-- Адрес доставки -->
    <div id="deliveryAddressBlock" class="mb-3">
      <div class="small" style="margin-bottom:8px;">Адрес доставки</div>

      <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
        <div>
          <input class="input" name="city" id="cityInput" required placeholder="Город (например: Москва)">
        </div>
        <div>
          <input class="input" name="street" id="streetInput" required placeholder="Улица (например: Тверская)">
        </div>
      </div>

      <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; margin-top:12px;">
        <div>
          <input class="input" name="house" id="houseInput" required placeholder="Дом (например: 10, 10А, 10/2)">
        </div>
        <div>
          <input class="input" name="apartment" id="aptInput" placeholder="Квартира (необязательно)">
        </div>
      </div>

      <div style="margin-top:12px;">
        <input class="input" name="entrance_info" id="entranceInput"
          placeholder="Подъезд / этаж / домофон (необязательно)">
      </div>

      <div class="muted small" id="addrHint" style="margin-top:6px;"></div>
    </div>

    <!-- Оплата -->
    <div class="mb-3">
      <div class="small" style="margin-bottom:8px;">Способ оплаты</div>

      <label style="display:flex; gap:10px; align-items:center; margin-bottom:8px;">
        <input type="radio" name="payment_method" value="card" checked>
        <span>Картой онлайн</span>
      </label>

      <label style="display:flex; gap:10px; align-items:center; margin-bottom:8px;">
        <input type="radio" name="payment_method" value="cash">
        <span>Наличными при получении</span>
      </label>

      <label style="display:flex; gap:10px; align-items:center;">
        <input type="radio" name="payment_method" value="transfer">
        <span>Переводом</span>
      </label>
    </div>

    <!-- Промокод -->
    <div class="mb-3">
      <div class="small" style="margin-bottom:8px;">Промокод</div>
      <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <input class="input" id="promoInput" name="promo_code" placeholder="Введите промокод"
          style="flex:1; min-width:220px;">
        <button class="btn btn--outline" type="button" id="applyPromoBtn">Применить</button>
      </div>
      <div class="muted small" id="promoHint" style="margin-top:6px;"></div>
      <div class="muted small" style="margin-top:6px;">
        <?php if (!empty($availablePromos)): ?>
        Доступно сейчас:
        <?php foreach ($availablePromos as $c => $p): ?>
        <b><?= htmlspecialchars($c) ?></b>
        (−<?= (int)$p ?>%)&nbsp;
        <?php endforeach; ?>
        <?php else: ?>
        Сейчас нет доступных промокодов.
        <?php endif; ?>
      </div>
    </div>

    <!-- Комм -->
    <div class="mb-3">
      <label class="small">Комментарий (необязательно)</label>
      <textarea class="input" name="comment" rows="3" placeholder="Например: позвонить за 10 минут"></textarea>
    </div>

    <div class="checkout-consent" style="margin:14px 0;">
      <label style="display:flex; align-items:flex-start; gap:10px; line-height:1.5;">
        <input type="checkbox" id="checkoutPrivacyConsent" name="privacy_consent" value="1" required
          style="margin-top:4px;">
        <span>
          Я соглашаюсь на
          <a href="../pages/privacy.php" target="_blank" rel="noopener noreferrer">
            обработку персональных данных
          </a>
        </span>
      </label>
    </div>

    <button class="btn btn--dark btn--full" id="submitBtn" type="submit">
      Подтвердить заказ
    </button>
    <div id="msg" class="muted small" style="margin-top:10px;"></div>
  </form>
</main>

<!-- Модалка успешное оформление -->
<div class="modal" id="orderSuccessModal" aria-hidden="true">
  <div class="modal__backdrop" data-close></div>

  <div class="modal__dialog" role="dialog" aria-modal="true" aria-label="Заказ оформлен">
    <div class="modal__head">
      <div class="modal__title">Спасибо за заказ!</div>
      <button class="iconBtn" type="button" data-close aria-label="Закрыть">✕</button>
    </div>

    <div class="modal__body">
      <p class="lead" style="margin-top:0;">
        Ваш заказ № <b id="successOrderId">—</b> оформлен.
      </p>
      <p class="muted small" style="margin-top:8px;">
        Вы можете перейти в личный кабинет → «Заказы» или остаться на странице.
      </p>

      <div style="display:flex; gap:10px; margin-top:14px; flex-wrap:wrap;">
        <a class="btn btn--dark" id="goAccountBtn" href="account.php?tab=orders">Перейти сейчас</a>
        <button class="btn" type="button" data-close>Остаться здесь</button>
      </div>
    </div>
  </div>
</div>

<script>
  window.CHECKOUT_CONFIG = {
    checkoutMode: <?= json_encode($checkoutModeValue, JSON_UNESCAPED_UNICODE); ?> ,
    giftData: <?= json_encode($giftFrontendData, JSON_UNESCAPED_UNICODE); ?> ,
    promos: <?= json_encode($availablePromos, JSON_UNESCAPED_UNICODE); ?> ,
    orderCreateUrl: '../php/order_create.php'
  };
</script>

<?php
renderFooter();
renderAuthModal();
renderFavoritesSheet();

renderScripts([
    'js/script.js',
    'js/cart.js',
    'js/favorites.js',
    'js/checkout.js'
]);
?>