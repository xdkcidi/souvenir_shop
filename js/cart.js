console.log("NEW cart.js LOADED");

const CART_AUTH_MESSAGE =
  "Чтобы добавить товар в корзину, сначала войдите в аккаунт.";

const CART_API_URL = "../php/cart.php";
const FAVORITES_API_URL = "../php/favorites.php";

let isAdding = false;

function openCartAuthModal() {
  if (typeof window.openAuthModalWithMessage === "function") {
    window.openAuthModalWithMessage(CART_AUTH_MESSAGE);
    return;
  }

  const openAuth = document.querySelector('[data-open-modal="authModal"]');
  if (openAuth) openAuth.click();
  else alert("Нужно войти в аккаунт");
}

function isAuthed() {
  return document.documentElement.dataset.auth === "1";
}

async function cartApi(action, data = {}) {
  const res = await fetch(CART_API_URL, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
    credentials: "same-origin",
    body: JSON.stringify({ action, ...data }),
  });

  if (res.status === 401) throw new Error("AUTH_REQUIRED");

  const json = await res.json().catch(() => ({}));

  if (!res.ok || !json.success) {
    throw new Error(json.error || `REQUEST_FAILED (${res.status})`);
  }

  return json;
}

async function favoritesApi(action, data = {}) {
  const isList = action === "list";

  const res = await fetch(
    isList ? `${FAVORITES_API_URL}?action=list` : FAVORITES_API_URL,
    {
      method: isList ? "GET" : "POST",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
      credentials: "same-origin",
      body: isList ? undefined : JSON.stringify({ action, ...data }),
    },
  );

  if (res.status === 401) throw new Error("AUTH_REQUIRED");

  const json = await res.json().catch(() => ({}));

  if (isList) {
    if (!res.ok) {
      throw new Error(json.error || `REQUEST_FAILED (${res.status})`);
    }
    return Array.isArray(json) ? json : [];
  }

  if (!res.ok || !json.success) {
    throw new Error(json.error || `REQUEST_FAILED (${res.status})`);
  }

  return json;
}

function ensureQtyWrapForButton(btn, code) {
  if (!btn) return null;

  const card = btn.closest(".card");
  const favoriteItem = btn.closest(".favorites-item");
  const root = card || favoriteItem;

  if (!root) return null;

  let wrap = root.querySelector(`[data-qty-wrap="${code}"]`);
  if (wrap) return wrap;

  let actions = null;
  let insertBeforeEl = null;
  let wrapClass = "qty qty--card";

  if (card) {
    actions = card.querySelector(".card__actions");
    insertBeforeEl = actions?.querySelector("[data-fav-btn]") || null;
    wrapClass = "qty qty--card";
  } else if (favoriteItem) {
    actions = favoriteItem.querySelector(".favorites-item__actions");
    insertBeforeEl = actions?.querySelector("[data-remove-favorite]") || null;
    wrapClass = "qty qty--card";
  }

  if (!actions) return null;

  wrap = document.createElement("div");
  wrap.className = wrapClass;
  wrap.setAttribute("data-qty-wrap", code);
  wrap.style.display = "none";
  wrap.innerHTML = `
    <button class="qty__btn" type="button" aria-label="Уменьшить количество" data-qty-minus="${code}">−</button>
    <span class="qty__val">1</span>
    <button class="qty__btn" type="button" aria-label="Увеличить количество" data-qty-plus="${code}">+</button>
  `;

  if (insertBeforeEl) {
    actions.insertBefore(wrap, insertBeforeEl);
  } else {
    actions.appendChild(wrap);
  }

  return wrap;
}

function setCardQty(code, qty) {
  const addButtons = document.querySelectorAll(
    `[data-add-to-cart][data-product-id="${code}"]`,
  );

  addButtons.forEach((btn) => {
    const wrap = ensureQtyWrapForButton(btn, code);

    if (!wrap) {
      btn.textContent = qty > 0 ? `В корзине (${qty})` : "В корзину";
      btn.disabled = false;
      return;
    }

    const val = wrap.querySelector(".qty__val");

    btn.style.display = qty > 0 ? "none" : "";
    btn.disabled = false;
    btn.textContent = "В корзину";

    wrap.style.display = qty > 0 ? "flex" : "none";

    if (val) {
      val.textContent = qty;
    }
  });
}

function updateCartCounter(items) {
  const totalQty = items.reduce(
    (sum, item) => sum + parseInt(item.qty || 0, 10),
    0,
  );

  const cartLink = document.querySelector('a[href*="cart.php"]');
  if (cartLink) {
    let badge = cartLink.querySelector(".badge");

    if (!badge) {
      badge = document.createElement("span");
      badge.className = "badge badge--permanent";
      cartLink.style.position = "relative";
      cartLink.appendChild(badge);
    }

    badge.textContent = totalQty;
    badge.style.display = totalQty > 0 ? "flex" : "none";
  }
}

async function syncHitsWithCart() {
  if (!isAuthed()) return;

  try {
    const data = await cartApi("list");
    const map = new Map();

    (data.items || []).forEach((it) => {
      map.set(it.product_code, parseInt(it.qty, 10));
    });

    document.querySelectorAll("[data-add-to-cart]").forEach((btn) => {
      const code = btn.getAttribute("data-product-id");
      if (!code) return;
      setCardQty(code, map.get(code) || 0);
    });

    updateCartCounter(data.items || []);
  } catch (e) {
    console.error("Ошибка синхронизации корзины:", e);
  }
}

function showNotification(message) {
  let notification = document.querySelector(".cart-notification");

  if (!notification) {
    notification = document.createElement("div");
    notification.className = "cart-notification";
    notification.style.cssText = `
      position: fixed;
      top: 20px;
      right: 20px;
      background: #1f7a4a;
      color: white;
      padding: 12px 24px;
      border-radius: 8px;
      z-index: 9999;
      animation: slideIn 0.3s ease;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    `;
    document.body.appendChild(notification);
  }

  notification.textContent = message;

  setTimeout(() => {
    if (notification.parentNode) {
      notification.remove();
    }
  }, 3000);
}

async function addToCart(productId, productName) {
  if (!isAuthed()) {
    openCartAuthModal();
    return false;
  }

  try {
    await cartApi("add", { product_code: productId });
    await syncHitsWithCart();

    if (document.getElementById("cartList")) {
      await loadCart();
    }

    showNotification(`${productName} добавлен в корзину`);
    return true;
  } catch (error) {
    if (error.message === "AUTH_REQUIRED") {
      openCartAuthModal();
    } else {
      console.error("Ошибка добавления:", error);
      alert("Не удалось добавить товар. Попробуйте позже.");
    }
    return false;
  }
}

async function updateQuantity(productId, action) {
  if (!isAuthed()) return false;

  try {
    if (action === "add") {
      await cartApi("add", { product_code: productId });
    } else if (action === "remove") {
      const data = await cartApi("list");
      const item = data.items.find((i) => i.product_code === productId);
      const currentQty = item ? parseInt(item.qty, 10) : 0;

      if (currentQty <= 1) {
        await cartApi("remove", { product_code: productId });
      } else {
        await cartApi("setQty", {
          product_code: productId,
          qty: currentQty - 1,
        });
      }
    }

    await syncHitsWithCart();

    if (document.getElementById("cartList")) {
      await loadCart();
    }

    return true;
  } catch (error) {
    if (error.message === "AUTH_REQUIRED") {
      openCartAuthModal();
    } else {
      console.error("Ошибка обновления количества:", error);
    }
    return false;
  }
}

async function addAllFavoritesToCart() {
  if (!isAuthed()) {
    openCartAuthModal();
    return false;
  }

  const btn = document.getElementById("add-all-to-cart");
  if (btn) btn.disabled = true;

  try {
    const favorites = await favoritesApi("list");

    if (!favorites.length) {
      showNotification("В избранном пока ничего нет");
      return true;
    }

    for (const item of favorites) {
      const code = item.product_code || item.id;
      if (!code) continue;

      await cartApi("add", { product_code: code });
    }

    await syncHitsWithCart();

    if (document.getElementById("cartList")) {
      await loadCart();
    }

    showNotification("Все товары из избранного добавлены в корзину");
    return true;
  } catch (error) {
    if (error.message === "AUTH_REQUIRED") {
      openCartAuthModal();
    } else {
      console.error("Ошибка добавления всех товаров из избранного:", error);
      alert("Не удалось добавить товары из избранного в корзину.");
    }
    return false;
  } finally {
    if (btn) btn.disabled = false;
  }
}

function formatPrice(price) {
  return new Intl.NumberFormat("ru-RU").format(price);
}

function renderCartItems(items, totalQty, totalSum) {
  const cartList = document.getElementById("cartList");
  const cartEmpty = document.getElementById("cartEmpty");
  const cartLayout = document.getElementById("cartLayout");
  const cartTotalQty = document.getElementById("cartTotalQty");
  const cartTotalSum = document.getElementById("cartTotalSum");

  if (!cartList) return;

  if (!items || items.length === 0) {
    if (cartEmpty) cartEmpty.style.display = "";
    if (cartLayout) cartLayout.style.display = "none";
    cartList.innerHTML = "";
    return;
  }

  if (cartEmpty) cartEmpty.style.display = "none";
  if (cartLayout) cartLayout.style.display = "grid";

  if (cartTotalQty) cartTotalQty.textContent = totalQty || 0;
  if (cartTotalSum) cartTotalSum.textContent = formatPrice(totalSum || 0);

  let html = "";

  items.forEach((item) => {
    const code = item.product_code;
    const price = parseInt(item.price, 10) || 0;
    const qty = parseInt(item.qty, 10) || 1;
    const sum = price * qty;

    let img = item.image;
    if (
      img &&
      !img.startsWith("http") &&
      !img.startsWith("/") &&
      !img.startsWith("../")
    ) {
      img = "../" + img;
    }
    if (!img || img.includes("placeholder")) {
      img = "../img/placeholder.webp";
    }

    html += `
      <div class="card" style="padding:14px; margin-bottom:12px; position:relative;">
        <div class="cartRow">
          <div class="cartItemImg">
            <img src="${img}" alt="${item.name || "Товар"}" loading="lazy">
          </div>

          <div style="flex:1;">
            <div class="cartTitle">${item.name || "Товар"}</div>
            <div class="muted small" style="color:#666;">${formatPrice(price)} ₽ / шт</div>

            ${item.meta ? `<div class="muted small cartMeta" style="color:#999;">${item.meta}</div>` : ""}

            <div style="display:flex; align-items:center; gap:12px; margin-top:10px; flex-wrap:wrap;">
              <div class="qty">
                <button class="qty__btn" type="button" data-qty-minus="${code}">−</button>
                <span class="qty__val" id="qty-${code}">${qty}</span>
                <button class="qty__btn" type="button" data-qty-plus="${code}">+</button>
              </div>

              <button class="btn btn--outline btn--sm" type="button" data-remove="${code}">
                Удалить
              </button>
            </div>
          </div>

          <div class="cartRight">${formatPrice(sum)} ₽</div>
        </div>
      </div>
    `;
  });

  cartList.innerHTML = html;
}

async function loadCart() {
  if (!isAuthed()) return;

  try {
    const data = await cartApi("list");
    renderCartItems(data.items, data.totalQty, data.totalSum);
  } catch (err) {
    if (err.message === "AUTH_REQUIRED") return;
    console.error("Ошибка загрузки корзины:", err);
  }
}

document.addEventListener("click", async function (e) {
  if (isAdding) {
    e.preventDefault();
    return;
  }

  const addBtn = e.target.closest("[data-add-to-cart]");
  if (addBtn) {
    e.preventDefault();
    isAdding = true;

    const productId = addBtn.dataset.productId;
    const productName = addBtn.dataset.productName || "Товар";

    await addToCart(productId, productName);

    setTimeout(() => {
      isAdding = false;
    }, 500);
    return;
  }

  const plusBtn = e.target.closest("[data-qty-plus]");
  if (plusBtn) {
    e.preventDefault();
    isAdding = true;

    const productId =
      plusBtn.getAttribute("data-qty-plus") ||
      plusBtn.closest("[data-qty-wrap]")?.dataset.qtyWrap;

    if (productId) {
      await updateQuantity(productId, "add");
    }

    setTimeout(() => {
      isAdding = false;
    }, 300);
    return;
  }

  const minusBtn = e.target.closest("[data-qty-minus]");
  if (minusBtn) {
    e.preventDefault();
    isAdding = true;

    const productId =
      minusBtn.getAttribute("data-qty-minus") ||
      minusBtn.closest("[data-qty-wrap]")?.dataset.qtyWrap;

    if (productId) {
      await updateQuantity(productId, "remove");
    }

    setTimeout(() => {
      isAdding = false;
    }, 300);
    return;
  }

  const addAllFavoritesBtn = e.target.closest("#add-all-to-cart");
  if (addAllFavoritesBtn) {
    e.preventDefault();
    isAdding = true;

    await addAllFavoritesToCart();

    setTimeout(() => {
      isAdding = false;
    }, 500);
    return;
  }

  const removeBtn = e.target.closest("[data-remove]");
  if (removeBtn) {
    e.preventDefault();
    if (!isAuthed()) {
      openCartAuthModal();
      return;
    }

    const code = removeBtn.getAttribute("data-remove");

    try {
      await cartApi("remove", { product_code: code });
      await loadCart();
      await syncHitsWithCart();
    } catch (err) {
      if (err.message === "AUTH_REQUIRED") openCartAuthModal();
      else console.error(err);
    }
    return;
  }

  const clearBtn = e.target.closest("#cartClearBtn");
  if (clearBtn) {
    e.preventDefault();
    if (!isAuthed()) {
      openCartAuthModal();
      return;
    }

    if (confirm("Очистить корзину?")) {
      try {
        await cartApi("clear");
        await loadCart();
        await syncHitsWithCart();
      } catch (err) {
        if (err.message === "AUTH_REQUIRED") openCartAuthModal();
        else console.error(err);
      }
    }
  }
});

const style = document.createElement("style");
style.textContent = `
  @keyframes slideIn {
    from {
      transform: translateX(100%);
      opacity: 0;
    }
    to {
      transform: translateX(0);
      opacity: 1;
    }
  }

  .badge--permanent {
    position: absolute;
    top: -8px;
    right: -8px;
    background: white;
    color: #dc3545;
    border: 1px solid #dc3545;
    min-width: 20px;
    height: 20px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 4px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: all 0.2s ease;
  }

  a[href*="cart.php"]:hover .badge--permanent {
    background: #dc3545;
    color: white;
    border-color: white;
  }

  @media (max-width: 768px) {
    .badge--permanent {
      top: -4px;
      right: -4px;
    }
  }
`;
document.head.appendChild(style);

document.addEventListener("DOMContentLoaded", async function () {
  if (isAuthed()) {
    try {
      await syncHitsWithCart();

      if (document.getElementById("cartList")) {
        await loadCart();
      }
    } catch (error) {
      console.error("Ошибка инициализации:", error);
    }
  }
});

window.cartApi = cartApi;
window.syncHitsWithCart = syncHitsWithCart;
window.loadCart = loadCart;
window.showNotification = showNotification;
window.openCartAuthModal = openCartAuthModal;
window.addToCart = addToCart;
window.addAllFavoritesToCart = addAllFavoritesToCart;
