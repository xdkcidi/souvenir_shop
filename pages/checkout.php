<?php
session_start();

require_once __DIR__ . '/../php/db.php';
$userId = (int)$_SESSION['user_id'];

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
if ($itemsTotal >= 30000) $statusName = 'VIP';
elseif ($itemsTotal >= 10000) $statusName = 'Постоянный';
else $statusName = 'Новичок';

// использованные промокоды
$stmt = $pdo->prepare("SELECT promo_code FROM promo_redemptions WHERE user_id = :uid");
$stmt->execute([':uid' => $userId]);
$used = array_flip(array_map('strtoupper', array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'promo_code')));

$notUsed = fn(string $code) => !isset($used[strtoupper($code)]);

// доступные промокоды -> code => percent
$availablePromos = [];

if ($ordersCount === 0 && $notUsed('WELCOME10')) $availablePromos['WELCOME10'] = 10;
if ($notUsed('SPRING15')) $availablePromos['SPRING15'] = 15;

if ($statusName === 'Постоянный' && $notUsed('LOYAL20')) $availablePromos['LOYAL20'] = 20;
if ($statusName === 'VIP' && $notUsed('VIP25')) $availablePromos['VIP25'] = 25;


if (!isset($_SESSION['user_id'])) {
  header('Location: cart.php');
  exit;
}
?>
<!doctype html>
<html lang="ru" data-auth="1">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Оформление заказа — Лавка</title>

  <link rel="stylesheet" href="../css/main.css"/>
  <link rel="stylesheet" href="../css/style.css"/>
</head>
<body>

<main class="container section">
  <h1 class="h2">Оформление заказа</h1>
  <p class="muted">Заполните данные и подтвердите заказ.</p>

  <!-- ИТОГО -->
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

  <!-- ФОРМА -->
  <form id="checkoutForm" method="post" action="#" class="card" style="padding:16px; margin-top:12px;">
    <!-- КОНТАКТЫ -->
    <div class="mb-3">
      <label class="small">Имя и фамилия</label>
      <input class="input input--lg" name="customer_name" id="customerName" required
             placeholder="Мария Иванова" autocomplete="name">
      <div class="muted small" id="nameHint" style="margin-top:6px;"></div>
    </div>

    <div class="mb-3">
      <label class="small">Телефон</label>
      <input class="input input--lg" name="phone" id="phoneInput" required
             placeholder="+7 (___) ___-__-__" inputmode="tel" autocomplete="tel">
      <div class="muted small" id="phoneHint" style="margin-top:6px;"></div>
    </div>

    <div class="mb-3">
      <label class="small">Email</label>
      <input class="input input--lg" name="email" id="emailInput" type="email" required
             placeholder="name@mail.com" autocomplete="email">
      <div class="muted small" id="emailHint" style="margin-top:6px;"></div>
    </div>

    <!-- СПОСОБ ПОЛУЧЕНИЯ -->
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

    <!-- ДАТА И ВРЕМЯ (только для доставки) -->
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

    <!-- АДРЕС ДОСТАВКИ -->
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
        <input class="input" name="entrance_info" id="entranceInput" placeholder="Подъезд / этаж / домофон (необязательно)">
      </div>

      <div class="muted small" id="addrHint" style="margin-top:6px;"></div>
    </div>

    <!-- ОПЛАТА -->
    <div class="mb-3">
      <div class="small" style="margin-bottom:8px;">Способ оплаты</div>

      <label style="display:flex; gap:10px; align-items:center; margin-bottom:8px;">
        <input type="radio" name="payment_method" value="card" checked>
        <span>Картой онлайн (симуляция)</span>
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

    <!-- ПРОМОКОД -->
    <div class="mb-3">
      <div class="small" style="margin-bottom:8px;">Промокод</div>
      <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <input class="input" id="promoInput" name="promo_code" placeholder="Введите промокод (например WELCOME10)" style="flex:1; min-width:220px;">
        <button class="btn btn--outline" type="button" id="applyPromoBtn">Применить</button>
      </div>
      <div class="muted small" id="promoHint" style="margin-top:6px;"></div>
<div class="muted small" style="margin-top:6px;">
  <?php if (!empty($availablePromos)): ?>
    Доступно сейчас:
    <?php foreach ($availablePromos as $c => $p): ?>
      <b><?= htmlspecialchars($c) ?></b> (−<?= (int)$p ?>%)&nbsp;
    <?php endforeach; ?>
  <?php else: ?>
    Сейчас нет доступных промокодов.
  <?php endif; ?>
</div>
    </div>

    <!-- КОММЕНТАРИЙ -->
    <div class="mb-3">
      <label class="small">Комментарий (необязательно)</label>
      <textarea class="input" name="comment" rows="3" placeholder="Например: позвонить за 10 минут"></textarea>
    </div>

    <button class="btn btn--dark btn--full" id="submitBtn" type="submit">
      Подтвердить заказ
    </button>
    <div id="msg" class="muted small" style="margin-top:10px;"></div>
  </form>
</main>

<!-- MODAL SUCCESS -->
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
        Вы можете перейти в личный кабинет → «История заказов» или остаться на странице.
      </p>

      <div style="display:flex; gap:10px; margin-top:14px; flex-wrap:wrap;">
        <a class="btn btn--dark" id="goAccountBtn" href="account.php?tab=orders">Перейти сейчас</a>
        <button class="btn" type="button" data-close>Остаться здесь</button>
      </div>
    </div>
  </div>
</div>

<script src="../js/cart.js" defer></script>

<script>
document.addEventListener('DOMContentLoaded', async () => {
  const DELIVERY_FEE = 200;

  const checkoutMode = <?php echo json_encode($checkoutModeValue, JSON_UNESCAPED_UNICODE); ?>;
  const giftData = <?php echo json_encode($giftFrontendData, JSON_UNESCAPED_UNICODE); ?>;

  const form = document.getElementById('checkoutForm');
  const btn  = document.getElementById('submitBtn');
  const msg  = document.getElementById('msg');

  const itemsSumEl = document.getElementById('itemsSum');
  const discountSumEl = document.getElementById('discountSum');
  const deliveryFeeEl = document.getElementById('deliveryFee');
  const totalSumEl = document.getElementById('totalSum');
  const totalQtyEl = document.getElementById('totalQty');

  const nameInput = document.getElementById('customerName');
  const phoneInput = document.getElementById('phoneInput');
  const emailInput = document.getElementById('emailInput');

  const deliveryBlock = document.getElementById('deliveryAddressBlock');
  const cityInput = document.getElementById('cityInput');
  const streetInput = document.getElementById('streetInput');
  const houseInput = document.getElementById('houseInput');
  const aptInput = document.getElementById('aptInput');
  const entranceInput = document.getElementById('entranceInput');

  const nameHint = document.getElementById('nameHint');
  const phoneHint = document.getElementById('phoneHint');
  const emailHint = document.getElementById('emailHint');
  const addrHint = document.getElementById('addrHint');

  const deliveryTimeBlock = document.getElementById('deliveryTimeBlock');
  const pickupHint = document.getElementById('pickupHint');
  const deliveryDate = document.getElementById('deliveryDate');
  const deliverySlot = document.getElementById('deliverySlot');
  const deliveryTimeHint = document.getElementById('deliveryTimeHint');

  const promoInput = document.getElementById('promoInput');
  const applyPromoBtn = document.getElementById('applyPromoBtn');
  const promoHint = document.getElementById('promoHint');

  let checkoutItems = [];
  let baseItemsSum = 0;
  let baseQty = 0;

  const PROMOS = <?php echo json_encode($availablePromos, JSON_UNESCAPED_UNICODE); ?>;
  let appliedPromo = null;
  let discountSum = 0;

  function rub(n) {
    return Number(n || 0).toLocaleString('ru-RU');
  }

  function setFieldState(input, hintEl, ok, text) {
    if (hintEl) hintEl.textContent = text || '';
    if (input) input.style.borderColor = ok ? '' : '#b00020';
  }

  function getDeliveryType() {
    return form.querySelector('input[name="delivery_type"]:checked')?.value || 'delivery';
  }

  function calcDeliveryFee() {
    return getDeliveryType() === 'delivery' ? DELIVERY_FEE : 0;
  }

  function computeDiscount(itemsSum) {
    if (checkoutMode === 'gift') {
      return Number(giftData.discountSum || 0);
    }
    if (!appliedPromo) return 0;
    return Math.round(itemsSum * (appliedPromo.percent / 100));
  }

  function renderTotals() {
    const fee = calcDeliveryFee();
    discountSum = computeDiscount(baseItemsSum);

    itemsSumEl.textContent = rub(baseItemsSum);
    discountSumEl.textContent = rub(discountSum);
    deliveryFeeEl.textContent = rub(fee);
    totalSumEl.textContent = rub(Math.max(0, baseItemsSum - discountSum) + fee);
    totalQtyEl.textContent = baseQty;
  }

  function toggleDeliveryExtras() {
    const type = getDeliveryType();

    if (type === 'pickup') {
      if (deliveryTimeBlock) deliveryTimeBlock.style.display = 'none';
      if (pickupHint) pickupHint.style.display = 'block';
      if (deliveryDate) {
        deliveryDate.required = false;
        deliveryDate.style.borderColor = '';
      }
      if (deliverySlot) {
        deliverySlot.required = false;
        deliverySlot.style.borderColor = '';
      }
      if (deliveryTimeHint) deliveryTimeHint.textContent = '';
    } else {
      if (deliveryTimeBlock) deliveryTimeBlock.style.display = '';
      if (pickupHint) pickupHint.style.display = 'none';
      if (deliveryDate) deliveryDate.required = true;
      if (deliverySlot) deliverySlot.required = true;
    }
  }

  function toggleAddressUI() {
    const type = getDeliveryType();

    if (type === 'pickup') {
      deliveryBlock.style.display = 'none';
      cityInput.required = false;
      streetInput.required = false;
      houseInput.required = false;

      addrHint.textContent = '';
      cityInput.style.borderColor = '';
      streetInput.style.borderColor = '';
      houseInput.style.borderColor = '';
    } else {
      deliveryBlock.style.display = '';
      cityInput.required = true;
      streetInput.required = true;
      houseInput.required = true;
    }

    toggleDeliveryExtras();
    renderTotals();
  }

  if (deliveryDate) {
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    deliveryDate.min = tomorrow.toISOString().slice(0, 10);
  }

  try {
    if (checkoutMode === 'gift') {
      checkoutItems = Array.isArray(giftData.items) ? giftData.items : [];

      if (!checkoutItems.length) {
        location.href = 'cart.php';
        return;
      }

      baseQty = Number(giftData.totalQty || checkoutItems.length || 0);
      baseItemsSum = Number(giftData.originalSum || 0);

      promoInput.value = '';
      promoInput.disabled = true;
      applyPromoBtn.disabled = true;
      promoHint.textContent = 'Для подарочного набора уже применена скидка 5%.';
    } else {
      const data = await cartApi('list');
      checkoutItems = data.items || [];

      if (!checkoutItems.length) {
        location.href = 'cart.php';
        return;
      }

      baseItemsSum = Number(data.totalSum || 0);
      baseQty = Number(data.totalQty || 0);
    }

    toggleAddressUI();
    renderTotals();
  } catch (e) {
    msg.textContent = 'Не удалось загрузить данные заказа.';
    btn.disabled = true;
    return;
  }

  phoneInput.addEventListener('input', function () {
    let value = this.value.replace(/\D/g, '');

    if (value.startsWith('8')) value = '7' + value.slice(1);
    if (value.length > 0 && !value.startsWith('7')) value = '7' + value;

    value = value.slice(0, 11);

    let formattedValue = '+7 ';

    if (value.length > 1) formattedValue += '(' + value.substring(1, 4);
    if (value.length >= 4) formattedValue += ') ' + value.substring(4, 7);
    if (value.length >= 7) formattedValue += '-' + value.substring(7, 9);
    if (value.length >= 9) formattedValue += '-' + value.substring(9, 11);

    this.value = formattedValue;
  });

  applyPromoBtn?.addEventListener('click', () => {
    if (checkoutMode === 'gift') return;

    const code = (promoInput.value || '').trim().toUpperCase();

    if (!code) {
      promoHint.textContent = 'Введите промокод.';
      appliedPromo = null;
      renderTotals();
      return;
    }

    const percent = PROMOS[code];
    if (!percent) {
      promoHint.textContent = 'Промокод не найден.';
      appliedPromo = null;
      renderTotals();
      return;
    }

    appliedPromo = { code, percent };
    promoHint.textContent = `Промокод ${code} применён: скидка ${percent}%`;
    renderTotals();
  });

  function validateName() {
    const v = (nameInput.value || '').trim();
    const ok = /^[А-Яа-яЁё][А-Яа-яЁё\s\-]{1,79}$/u.test(v);
    setFieldState(
      nameInput,
      nameHint,
      ok,
      ok ? '' : 'Только кириллица. Можно пробел и дефис. Пример: Мария Иванова'
    );
    return ok;
  }

  function validatePhone() {
    const digits = (phoneInput.value || '').replace(/\D/g, '');
    const ok = digits.length === 11 && digits.startsWith('7');
    setFieldState(
      phoneInput,
      phoneHint,
      ok,
      ok ? '' : 'Введите телефон полностью: +7 (999) 123-45-67'
    );
    return ok;
  }

  function validateEmail() {
    const v = (emailInput.value || '').trim();
    const ok = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/i.test(v);
    setFieldState(
      emailInput,
      emailHint,
      ok,
      ok ? '' : 'Введите корректный email (например: name@mail.com)'
    );
    return ok;
  }

  function validateAddressIfNeeded() {
    if (getDeliveryType() === 'pickup') return true;

    const city = (cityInput.value || '').trim();
    const street = (streetInput.value || '').trim();
    const house = (houseInput.value || '').trim();

    const cityOk = city.length >= 2 && !/\d/.test(city);
    const streetOk = street.length >= 2;
    const houseOk = /^[0-9А-Яа-яA-Za-z\/\-]{1,10}$/u.test(house);

    cityInput.style.borderColor = cityOk ? '' : '#b00020';
    streetInput.style.borderColor = streetOk ? '' : '#b00020';
    houseInput.style.borderColor = houseOk ? '' : '#b00020';

    addrHint.textContent = (cityOk && streetOk && houseOk)
      ? ''
      : 'Заполните город, улицу и дом корректно (дом: 10, 10А, 10/2).';

    return cityOk && streetOk && houseOk;
  }

  function validateDeliveryTimeIfNeeded() {
    if (getDeliveryType() === 'pickup') return true;

    let ok = true;
    const min = deliveryDate?.min || '';
    const vDate = (deliveryDate?.value || '').trim();
    const vSlot = (deliverySlot?.value || '').trim();

    if (!vDate || (min && vDate < min)) ok = false;
    if (!vSlot) ok = false;

    if (deliveryDate) deliveryDate.style.borderColor = (!vDate || (min && vDate < min)) ? '#b00020' : '';
    if (deliverySlot) deliverySlot.style.borderColor = !vSlot ? '#b00020' : '';

    if (deliveryTimeHint) {
      deliveryTimeHint.textContent = ok ? '' : 'Выберите дату (не раньше завтра) и интервал времени.';
    }

    return ok;
  }

  nameInput.addEventListener('blur', validateName);
  phoneInput.addEventListener('blur', validatePhone);
  emailInput.addEventListener('blur', validateEmail);

  cityInput.addEventListener('blur', validateAddressIfNeeded);
  streetInput.addEventListener('blur', validateAddressIfNeeded);
  houseInput.addEventListener('blur', validateAddressIfNeeded);

  deliveryDate?.addEventListener('blur', validateDeliveryTimeIfNeeded);
  deliverySlot?.addEventListener('change', validateDeliveryTimeIfNeeded);

  form.addEventListener('change', (e) => {
    if (e.target.name === 'delivery_type') {
      toggleAddressUI();
    }
  });

  function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    modal.setAttribute('aria-hidden', 'false');
    modal.classList.add('is-open');
  }

  function closeModal(el) {
    const modal = el.closest('.modal');
    if (!modal) return;
    modal.setAttribute('aria-hidden', 'true');
    modal.classList.remove('is-open');
  }

  document.addEventListener('click', (e) => {
    const c = e.target.closest('[data-close]');
    if (c) closeModal(c);
  });

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    msg.textContent = '';
    btn.disabled = true;

    const ok =
      validateName() &
      validatePhone() &
      validateEmail() &
      validateAddressIfNeeded() &
      validateDeliveryTimeIfNeeded();

    if (!ok) {
      msg.textContent = 'Проверьте поля формы — есть ошибки.';
      btn.disabled = false;
      return;
    }

    const deliveryType = getDeliveryType();

    const minimalItems = checkoutItems.map((it) => ({
      product_code: it.product_code,
      qty: parseInt(it.qty, 10) || 1
    }));

    const promoCode = checkoutMode === 'gift'
      ? ''
      : (appliedPromo?.code || ((promoInput.value || '').trim().toUpperCase()));

    const payload = {
      checkout_mode: checkoutMode,

      customer_name: nameInput.value.trim(),
      phone: phoneInput.value.trim(),
      email: emailInput.value.trim(),
      comment: (form.querySelector('textarea[name="comment"]')?.value || '').trim(),
      payment_method: form.querySelector('input[name="payment_method"]:checked')?.value || 'card',

      delivery_type: deliveryType,
      city: (deliveryType === 'delivery') ? cityInput.value.trim() : '',
      street: (deliveryType === 'delivery') ? streetInput.value.trim() : '',
      house: (deliveryType === 'delivery') ? houseInput.value.trim() : '',
      apartment: (deliveryType === 'delivery') ? aptInput.value.trim() : '',
      entrance_info: (deliveryType === 'delivery') ? entranceInput.value.trim() : '',

      delivery_date: (deliveryType === 'delivery') ? (deliveryDate.value || '') : '',
      delivery_slot: (deliveryType === 'delivery') ? (deliverySlot.value || '') : '',

      promo_code: promoCode || '',
      items: minimalItems
    };

    try {
      const res = await fetch('../php/order_create.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });

      const out = await res.json();

      if (!res.ok || !out.ok) {
        msg.textContent = out.error || 'Ошибка оформления.';
        btn.disabled = false;
        return;
      }

      if (checkoutMode === 'cart') {
        try {
          await cartApi('clear');
        } catch (e) {}
      }

      baseItemsSum = 0;
      baseQty = 0;
      appliedPromo = null;

      if (promoInput) promoInput.value = '';
      if (promoHint) promoHint.textContent = '';

      renderTotals();

      const idEl = document.getElementById('successOrderId');
      if (idEl) idEl.textContent = out.order_id;

      const goBtn = document.getElementById('goAccountBtn');
      if (goBtn) goBtn.href = 'account.php?tab=orders';

      openModal('orderSuccessModal');

      setTimeout(() => {
        location.href = 'account.php?tab=orders';
      }, 8000);

    } catch (err) {
      msg.textContent = 'Ошибка сети.';
      btn.disabled = false;
    }
  });
});
</script>

</body>
</html>