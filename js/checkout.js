document.addEventListener("DOMContentLoaded", async () => {
  const DELIVERY_FEE = 200;

  const config = window.CHECKOUT_CONFIG || {};
  const checkoutMode = config.checkoutMode || "cart";
  const giftData = config.giftData || {};
  const PROMOS = config.promos || {};
  const orderCreateUrl = config.orderCreateUrl || "../php/order_create.php";

  const form = document.getElementById("checkoutForm");
  if (!form) return;

  const btn = document.getElementById("submitBtn");
  const msg = document.getElementById("msg");
  const privacyCheckbox = document.getElementById("checkoutPrivacyConsent");

  const itemsSumEl = document.getElementById("itemsSum");
  const discountSumEl = document.getElementById("discountSum");
  const deliveryFeeEl = document.getElementById("deliveryFee");
  const totalSumEl = document.getElementById("totalSum");
  const totalQtyEl = document.getElementById("totalQty");

  const nameInput = document.getElementById("customerName");
  const phoneInput = document.getElementById("phoneInput");
  const emailInput = document.getElementById("emailInput");

  const deliveryBlock = document.getElementById("deliveryAddressBlock");
  const cityInput = document.getElementById("cityInput");
  const streetInput = document.getElementById("streetInput");
  const houseInput = document.getElementById("houseInput");
  const aptInput = document.getElementById("aptInput");
  const entranceInput = document.getElementById("entranceInput");

  const nameHint = document.getElementById("nameHint");
  const phoneHint = document.getElementById("phoneHint");
  const emailHint = document.getElementById("emailHint");
  const addrHint = document.getElementById("addrHint");

  const deliveryTimeBlock = document.getElementById("deliveryTimeBlock");
  const pickupHint = document.getElementById("pickupHint");
  const deliveryDate = document.getElementById("deliveryDate");
  const deliverySlot = document.getElementById("deliverySlot");
  const deliveryTimeHint = document.getElementById("deliveryTimeHint");

  const promoInput = document.getElementById("promoInput");
  const applyPromoBtn = document.getElementById("applyPromoBtn");
  const promoHint = document.getElementById("promoHint");

  let checkoutItems = [];
  let baseItemsSum = 0;
  let baseQty = 0;
  let appliedPromo = null;
  let discountSum = 0;

  function rub(n) {
    return Number(n || 0).toLocaleString("ru-RU");
  }

  function setFieldState(input, hintEl, ok, text) {
    if (hintEl) hintEl.textContent = text || "";
    if (input) input.style.borderColor = ok ? "" : "#b00020";
  }

  function getDeliveryType() {
    return (
      form.querySelector('input[name="delivery_type"]:checked')?.value ||
      "delivery"
    );
  }

  function calcDeliveryFee() {
    return getDeliveryType() === "delivery" ? DELIVERY_FEE : 0;
  }

  function computeDiscount(itemsSum) {
    if (checkoutMode === "gift") {
      return Number(giftData.discountSum || 0);
    }

    if (!appliedPromo) return 0;

    return Math.round(itemsSum * (appliedPromo.percent / 100));
  }

  function renderTotals() {
    const fee = calcDeliveryFee();
    discountSum = computeDiscount(baseItemsSum);

    if (itemsSumEl) itemsSumEl.textContent = rub(baseItemsSum);
    if (discountSumEl) discountSumEl.textContent = rub(discountSum);
    if (deliveryFeeEl) deliveryFeeEl.textContent = rub(fee);
    if (totalSumEl)
      totalSumEl.textContent = rub(
        Math.max(0, baseItemsSum - discountSum) + fee,
      );
    if (totalQtyEl) totalQtyEl.textContent = baseQty;
  }

  function toggleDeliveryExtras() {
    const type = getDeliveryType();

    if (type === "pickup") {
      if (deliveryTimeBlock) deliveryTimeBlock.style.display = "none";
      if (pickupHint) pickupHint.style.display = "block";

      if (deliveryDate) {
        deliveryDate.required = false;
        deliveryDate.style.borderColor = "";
      }

      if (deliverySlot) {
        deliverySlot.required = false;
        deliverySlot.style.borderColor = "";
      }

      if (deliveryTimeHint) deliveryTimeHint.textContent = "";
    } else {
      if (deliveryTimeBlock) deliveryTimeBlock.style.display = "";
      if (pickupHint) pickupHint.style.display = "none";
      if (deliveryDate) deliveryDate.required = true;
      if (deliverySlot) deliverySlot.required = true;
    }
  }

  function toggleAddressUI() {
    const type = getDeliveryType();

    if (type === "pickup") {
      if (deliveryBlock) deliveryBlock.style.display = "none";

      if (cityInput) {
        cityInput.required = false;
        cityInput.style.borderColor = "";
      }

      if (streetInput) {
        streetInput.required = false;
        streetInput.style.borderColor = "";
      }

      if (houseInput) {
        houseInput.required = false;
        houseInput.style.borderColor = "";
      }

      if (addrHint) addrHint.textContent = "";
    } else {
      if (deliveryBlock) deliveryBlock.style.display = "";

      if (cityInput) cityInput.required = true;
      if (streetInput) streetInput.required = true;
      if (houseInput) houseInput.required = true;
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
    if (checkoutMode === "gift") {
      checkoutItems = Array.isArray(giftData.items) ? giftData.items : [];

      if (!checkoutItems.length) {
        location.href = "cart.php";
        return;
      }

      baseQty = Number(giftData.totalQty || checkoutItems.length || 0);
      baseItemsSum = Number(giftData.originalSum || 0);

      if (promoInput) promoInput.value = "";
      if (promoInput) promoInput.disabled = true;
      if (applyPromoBtn) applyPromoBtn.disabled = true;
      if (promoHint)
        promoHint.textContent =
          "Для подарочного набора уже применена скидка 5%.";
    } else {
      if (typeof window.cartApi !== "function") {
        throw new Error(
          "cartApi is not available. Подключите js/cart.js перед js/checkout.js",
        );
      }

      const data = await window.cartApi("list");
      checkoutItems = data.items || [];

      if (!checkoutItems.length) {
        location.href = "cart.php";
        return;
      }

      baseItemsSum = Number(data.totalSum || 0);
      baseQty = Number(data.totalQty || 0);
    }

    toggleAddressUI();
    renderTotals();
  } catch (e) {
    console.error(e);

    if (msg) msg.textContent = "Не удалось загрузить данные заказа.";
    if (btn) btn.disabled = true;

    return;
  }

  phoneInput?.addEventListener("input", function () {
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

  applyPromoBtn?.addEventListener("click", () => {
    if (checkoutMode === "gift") return;

    const code = (promoInput?.value || "").trim().toUpperCase();

    if (!code) {
      if (promoHint) promoHint.textContent = "Введите промокод.";
      appliedPromo = null;
      renderTotals();
      return;
    }

    const percent = PROMOS[code];

    if (!percent) {
      if (promoHint) promoHint.textContent = "Промокод не найден.";
      appliedPromo = null;
      renderTotals();
      return;
    }

    appliedPromo = {
      code,
      percent,
    };

    if (promoHint)
      promoHint.textContent = `Промокод ${code} применён: скидка ${percent}%`;

    renderTotals();
  });

  function validateName() {
    const v = (nameInput?.value || "").trim();
    const ok = /^[А-Яа-яЁё][А-Яа-яЁё\s\-]{1,79}$/u.test(v);

    setFieldState(
      nameInput,
      nameHint,
      ok,
      ok ? "" : "Только кириллица. Можно пробел и дефис. Пример: Мария Иванова",
    );

    return ok;
  }

  function validatePhone() {
    const digits = (phoneInput?.value || "").replace(/\D/g, "");
    const ok = digits.length === 11 && digits.startsWith("7");

    setFieldState(
      phoneInput,
      phoneHint,
      ok,
      ok ? "" : "Введите телефон полностью: +7 (999) 123-45-67",
    );

    return ok;
  }

  function validateEmail() {
    const v = (emailInput?.value || "").trim();
    const ok = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/i.test(v);

    setFieldState(
      emailInput,
      emailHint,
      ok,
      ok ? "" : "Введите корректный email (например: name@mail.com)",
    );

    return ok;
  }

  function validateAddressIfNeeded() {
    if (getDeliveryType() === "pickup") return true;

    const city = (cityInput?.value || "").trim();
    const street = (streetInput?.value || "").trim();
    const house = (houseInput?.value || "").trim();

    const cityOk = city.length >= 2 && !/\d/.test(city);
    const streetOk = street.length >= 2;
    const houseOk = /^[0-9А-Яа-яA-Za-z\/\-]{1,10}$/u.test(house);

    if (cityInput) cityInput.style.borderColor = cityOk ? "" : "#b00020";
    if (streetInput) streetInput.style.borderColor = streetOk ? "" : "#b00020";
    if (houseInput) houseInput.style.borderColor = houseOk ? "" : "#b00020";

    if (addrHint) {
      addrHint.textContent =
        cityOk && streetOk && houseOk
          ? ""
          : "Заполните город, улицу и дом корректно (дом: 10, 10А, 10/2).";
    }

    return cityOk && streetOk && houseOk;
  }

  function validateDeliveryTimeIfNeeded() {
    if (getDeliveryType() === "pickup") return true;

    let ok = true;
    const min = deliveryDate?.min || "";
    const vDate = (deliveryDate?.value || "").trim();
    const vSlot = (deliverySlot?.value || "").trim();

    if (!vDate || (min && vDate < min)) ok = false;
    if (!vSlot) ok = false;

    if (deliveryDate) {
      deliveryDate.style.borderColor =
        !vDate || (min && vDate < min) ? "#b00020" : "";
    }

    if (deliverySlot) {
      deliverySlot.style.borderColor = !vSlot ? "#b00020" : "";
    }

    if (deliveryTimeHint) {
      deliveryTimeHint.textContent = ok
        ? ""
        : "Выберите дату (не раньше завтра) и интервал времени.";
    }

    return ok;
  }

  function validatePrivacy() {
    if (!privacyCheckbox) return true;

    const ok = privacyCheckbox.checked;

    if (!ok) {
      privacyCheckbox.style.outline = "2px solid #b00020";
      privacyCheckbox.style.outlineOffset = "2px";
    } else {
      privacyCheckbox.style.outline = "";
      privacyCheckbox.style.outlineOffset = "";
    }

    return ok;
  }

  nameInput?.addEventListener("blur", validateName);
  phoneInput?.addEventListener("blur", validatePhone);
  emailInput?.addEventListener("blur", validateEmail);

  cityInput?.addEventListener("blur", validateAddressIfNeeded);
  streetInput?.addEventListener("blur", validateAddressIfNeeded);
  houseInput?.addEventListener("blur", validateAddressIfNeeded);

  deliveryDate?.addEventListener("blur", validateDeliveryTimeIfNeeded);
  deliverySlot?.addEventListener("change", validateDeliveryTimeIfNeeded);

  form.addEventListener("change", (e) => {
    if (e.target.name === "delivery_type") {
      toggleAddressUI();
    }
  });

  privacyCheckbox?.addEventListener("change", validatePrivacy);

  function openModal(modalId) {
    const modal = document.getElementById(modalId);

    if (!modal) return;

    modal.setAttribute("aria-hidden", "false");
    modal.classList.add("is-open");
  }

  function closeModal(el) {
    const modal = el.closest(".modal");

    if (!modal) return;

    modal.setAttribute("aria-hidden", "true");
    modal.classList.remove("is-open");
  }

  document.addEventListener("click", (e) => {
    const closeBtn = e.target.closest("[data-close]");

    if (closeBtn) {
      closeModal(closeBtn);
    }
  });

  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    if (msg) msg.textContent = "";
    if (btn) btn.disabled = true;

    const ok =
      validateName() &&
      validatePhone() &&
      validateEmail() &&
      validateAddressIfNeeded() &&
      validateDeliveryTimeIfNeeded() &&
      validatePrivacy();

    if (!ok) {
      if (msg) {
        msg.textContent =
          "Проверьте поля формы и подтвердите согласие на обработку персональных данных.";
      }

      if (btn) btn.disabled = false;

      return;
    }

    const deliveryType = getDeliveryType();

    const minimalItems = checkoutItems.map((it) => ({
      product_code: it.product_code,
      qty: parseInt(it.qty, 10) || 1,
    }));

    const promoCode =
      checkoutMode === "gift"
        ? ""
        : appliedPromo?.code || (promoInput?.value || "").trim().toUpperCase();

    const payload = {
      checkout_mode: checkoutMode,

      customer_name: nameInput.value.trim(),
      phone: phoneInput.value.trim(),
      email: emailInput.value.trim(),
      comment: (
        form.querySelector('textarea[name="comment"]')?.value || ""
      ).trim(),
      payment_method:
        form.querySelector('input[name="payment_method"]:checked')?.value ||
        "card",

      delivery_type: deliveryType,
      city: deliveryType === "delivery" ? cityInput.value.trim() : "",
      street: deliveryType === "delivery" ? streetInput.value.trim() : "",
      house: deliveryType === "delivery" ? houseInput.value.trim() : "",
      apartment: deliveryType === "delivery" ? aptInput.value.trim() : "",
      entrance_info:
        deliveryType === "delivery" ? entranceInput.value.trim() : "",

      delivery_date:
        deliveryType === "delivery" ? deliveryDate?.value || "" : "",
      delivery_slot:
        deliveryType === "delivery" ? deliverySlot?.value || "" : "",

      promo_code: promoCode || "",
      privacy_consent: privacyCheckbox && privacyCheckbox.checked ? 1 : 0,
      items: minimalItems,
    };

    try {
      const res = await fetch(orderCreateUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(payload),
      });

      const out = await res.json();

      if (!res.ok || !out.ok) {
        if (msg) msg.textContent = out.error || "Ошибка оформления.";
        if (btn) btn.disabled = false;
        return;
      }

      if (checkoutMode === "cart" && typeof window.cartApi === "function") {
        try {
          await window.cartApi("clear");
        } catch (e) {
          console.warn("Не удалось очистить корзину после заказа:", e);
        }
      }

      baseItemsSum = 0;
      baseQty = 0;
      appliedPromo = null;

      if (promoInput) promoInput.value = "";
      if (promoHint) promoHint.textContent = "";

      renderTotals();

      const idEl = document.getElementById("successOrderId");

      if (idEl) {
        idEl.textContent = out.order_id;
      }

      const goBtn = document.getElementById("goAccountBtn");

      if (goBtn) {
        goBtn.href = "account.php?tab=orders";
      }

      openModal("orderSuccessModal");

      setTimeout(() => {
        location.href = "account.php?tab=orders";
      }, 8000);
    } catch (err) {
      console.error(err);

      if (msg) msg.textContent = "Ошибка сети.";
      if (btn) btn.disabled = false;
    }
  });
});
