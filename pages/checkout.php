<?php
session_start();
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

    <!-- ОПЛАТА (без настоящей оплаты, просто выбор) -->
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
        Сейчас вы будете перенаправлены в личный кабинет → «История заказов».
      </p>

      <div style="display:flex; gap:10px; margin-top:14px; flex-wrap:wrap;">
        <a class="btn btn--dark" id="goAccountBtn" href="account.php#orders">Перейти сейчас</a>
        <button class="btn" type="button" data-close>Остаться здесь</button>
      </div>
    </div>
  </div>
</div>

<script src="../js/cart.js" defer></script>

<script>
document.addEventListener('DOMContentLoaded', async () => {
  const DELIVERY_FEE = 200;

  const form = document.getElementById('checkoutForm');
  const btn  = document.getElementById('submitBtn');
  const msg  = document.getElementById('msg');

  const itemsSumEl = document.getElementById('itemsSum');
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

  let cartItems = [];
  let baseItemsSum = 0;
  let baseQty = 0;

  function rub(n){ return Number(n || 0).toLocaleString('ru-RU'); }

  function setFieldState(input, hintEl, ok, text) {
    if (hintEl) hintEl.textContent = text || '';
    input.style.borderColor = ok ? '' : '#b00020';
  }

  function getDeliveryType() {
    return form.querySelector('input[name="delivery_type"]:checked')?.value || 'delivery';
  }

  function calcDeliveryFee() {
    return getDeliveryType() === 'delivery' ? DELIVERY_FEE : 0;
  }

  function renderTotals() {
    const fee = calcDeliveryFee();
    itemsSumEl.textContent = rub(baseItemsSum);
    deliveryFeeEl.textContent = rub(fee);
    totalSumEl.textContent = rub(baseItemsSum + fee);
    totalQtyEl.textContent = baseQty;
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
    renderTotals();
  }

  // ===== 1) грузим корзину (это и есть причина твоего "0", если код не доходил сюда)
  try {
    const data = await cartApi('list');
    cartItems = data.items || [];

    if (!cartItems.length) {
      // если корзина пуста или API отдал пусто — вернёмся в корзину
      location.href = 'cart.php';
      return;
    }

    baseItemsSum = Number(data.totalSum || 0);
    baseQty = Number(data.totalQty || 0);

    toggleAddressUI();
    renderTotals();
  } catch (e) {
    msg.textContent = 'Не удалось загрузить корзину.';
    btn.disabled = true;
    return;
  }

  // ===== 2) маска телефона (твоя) — отдельно, НЕ внутри других функций!
  phoneInput.addEventListener("input", function () {
    let value = this.value.replace(/\D/g, "");

    if (value.startsWith("8")) value = "7" + value.slice(1);
    if (value.length > 0 && !value.startsWith("7")) value = "7" + value;

    value = value.slice(0, 11);

    let formattedValue = "+7 ";

    if (value.length > 1) formattedValue += "(" + value.substring(1, 4);
    if (value.length >= 4) formattedValue += ") " + value.substring(4, 7);
    if (value.length >= 7) formattedValue += "-" + value.substring(7, 9);
    if (value.length >= 9) formattedValue += "-" + value.substring(9, 11);

    this.value = formattedValue;
  });

  // ===== 3) валидация
  function validateName() {
    const v = (nameInput.value || '').trim();
    const ok = /^[А-Яа-яЁё][А-Яа-яЁё\s\-]{1,79}$/u.test(v);
    setFieldState(nameInput, nameHint, ok,
      ok ? '' : 'Только кириллица. Можно пробел и дефис. Пример: Мария Иванова'
    );
    return ok;
  }

  function validatePhone() {
    const digits = (phoneInput.value || '').replace(/\D/g, '');
    const ok = digits.length === 11 && digits.startsWith('7');
    setFieldState(phoneInput, phoneHint, ok,
      ok ? '' : 'Введите телефон полностью: +7 (999) 123-45-67'
    );
    return ok;
  }

  function validateEmail() {
    const v = (emailInput.value || '').trim();
    const ok = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/i.test(v);
    setFieldState(emailInput, emailHint, ok,
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

    return (cityOk && streetOk && houseOk);
  }

  nameInput.addEventListener('blur', validateName);
  phoneInput.addEventListener('blur', validatePhone);
  emailInput.addEventListener('blur', validateEmail);

  cityInput.addEventListener('blur', validateAddressIfNeeded);
  streetInput.addEventListener('blur', validateAddressIfNeeded);
  houseInput.addEventListener('blur', validateAddressIfNeeded);

  form.addEventListener('change', (e) => {
    if (e.target.name === 'delivery_type') toggleAddressUI();
  });

  // ===== 4) модалка: открытие/закрытие (без автоперехода!)
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

  // ===== 5) submit
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    msg.textContent = '';
    btn.disabled = true;

    const ok =
      validateName() &
      validatePhone() &
      validateEmail() &
      validateAddressIfNeeded();

    if (!ok) {
      msg.textContent = 'Проверьте поля формы — есть ошибки.';
      btn.disabled = false;
      return;
    }

    const deliveryType = getDeliveryType();

    const minimalItems = cartItems.map(it => ({
      product_code: it.product_code,
      qty: parseInt(it.qty, 10) || 1
    }));

    const payload = {
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

      items: minimalItems
    };

    try {
      const res = await fetch('../php/order_create.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify(payload)
      });

      const out = await res.json();

      if (!res.ok || !out.ok) {
        msg.textContent = out.error || 'Ошибка оформления.';
        btn.disabled = false;
        return;
      }

      // чистим корзину на сервере
      try { await cartApi('clear'); } catch(e) {}

      // обновим итог на странице (чисто визуально)
      baseItemsSum = 0;
      baseQty = 0;
      renderTotals();

      // модалка успеха
      const idEl = document.getElementById('successOrderId');
      if (idEl) idEl.textContent = out.order_id;

      const goBtn = document.getElementById('goAccountBtn');
      if (goBtn) goBtn.href = 'account.php#orders';

      openModal('orderSuccessModal');

      setTimeout(() => {
        location.href = 'account.php#orders';
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