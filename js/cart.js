// cart.js

const CART_AUTH_MESSAGE = "Чтобы добавить товар в корзину, сначала войдите в аккаунт.";

// ФИКС: указываем полный путь от корня сайта
const CART_API_URL = "/SOUVENIR_SHOP/php/cart.php";

// Флаг для защиты от двойных кликов
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
      "X-Requested-With": "XMLHttpRequest"
    },
    credentials: "same-origin",
    body: JSON.stringify({ action, ...data })
  });

  if (res.status === 401) throw new Error("AUTH_REQUIRED");

  const json = await res.json().catch(() => ({}));

  if (!res.ok || !json.success) {
    throw new Error(json.error || `REQUEST_FAILED (${res.status})`);
  }

  return json;
}

function setCardQty(code, qty) {
  const btnAdd = document.querySelector(
    `[data-add-to-cart][data-product-id="${code}"]`
  );
  const wrap = document.querySelector(`[data-qty-wrap="${code}"]`);
  const val = document.getElementById(`cardQty-${code}`);
  
  if (!btnAdd) return;

  if (wrap && val) {
    // Для страниц с блоками счетчиков (главная, каталог)
    if (qty > 0) {
      wrap.style.display = "";
      btnAdd.style.display = "none";
      val.textContent = qty;
    } else {
      wrap.style.display = "none";
      btnAdd.style.display = "";
    }
  } else {
    // Для страниц без блоков счетчиков
    if (qty > 0) {
      btnAdd.textContent = `В корзине (${qty})`;
      btnAdd.disabled = true;
    } else {
      btnAdd.textContent = "В корзину";
      btnAdd.disabled = false;
    }
  }
}

async function syncHitsWithCart() {
  if (!isAuthed()) return;

  try {
    const data = await cartApi("list");
    const map = new Map();
    (data.items || []).forEach((it) =>
      map.set(it.product_code, parseInt(it.qty, 10))
    );

    document.querySelectorAll("[data-add-to-cart]").forEach((btn) => {
      const code = btn.getAttribute("data-product-id");
      setCardQty(code, map.get(code) || 0);
    });
    
    updateCartCounter(data.items || []);
    
  } catch (e) {
    // если сессия слетела — просто не синкаем
  }
}

// ОБНОВЛЕННАЯ функция обновления счетчика корзины
function updateCartCounter(items) {
  const totalQty = items.reduce((sum, item) => sum + parseInt(item.qty || 0), 0);
  
  // Ищем ссылку на корзину
  const cartLink = document.querySelector('a[href*="cart.php"]');
  if (cartLink) {
    let badge = cartLink.querySelector('.badge');
    
    if (!badge) {
      badge = document.createElement('span');
      badge.className = 'badge badge--permanent'; // добавляем новый класс
      cartLink.style.position = 'relative'; // для позиционирования
      cartLink.appendChild(badge);
    }
    
    if (badge) {
      badge.textContent = totalQty;
      badge.style.display = totalQty > 0 ? 'flex' : 'none';
    }
  }
  
  // Также обновляем счетчик в иконке избранного если есть
  const favCount = document.getElementById('favoritesCount');
  if (favCount) {
    // Здесь логика для избранного
  }
}

// ===== ФУНКЦИИ ДЛЯ РАБОТЫ С КОРЗИНОЙ =====

async function addToCart(productId, productName) {
  if (!isAuthed()) {
    openCartAuthModal();
    return false;
  }

  try {
    const result = await cartApi("add", { product_code: productId });
    await syncHitsWithCart();
    
    // Если мы на странице корзины, обновляем её
    if (document.getElementById('cartList')) {
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
    let result;
    
    if (action === 'add') {
      result = await cartApi("add", { product_code: productId });
    } else if (action === 'remove') {
      // Получаем текущее количество
      const data = await cartApi("list");
      const item = data.items.find(i => i.product_code === productId);
      const currentQty = item ? parseInt(item.qty) : 0;
      
      if (currentQty <= 1) {
        // Если остался 1 товар, удаляем полностью
        result = await cartApi("remove", { product_code: productId });
      } else {
        // Устанавливаем количество -1
        result = await cartApi("setQty", { 
          product_code: productId, 
          qty: currentQty - 1 
        });
      }
    }
    
    await syncHitsWithCart();
    
    // Если мы на странице корзины, обновляем её
    if (document.getElementById('cartList')) {
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

function showNotification(message) {
  let notification = document.querySelector('.cart-notification');
  
  if (!notification) {
    notification = document.createElement('div');
    notification.className = 'cart-notification';
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

// Добавим стили для анимации и нового бейджа
const style = document.createElement('style');
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
  
  /* Новый стиль для постоянного бейджа */
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
  
  /* При наведении на кнопку корзины */
  a[href*="cart.php"]:hover .badge--permanent {
    background: #dc3545;
    color: white;
    border-color: white;
  }
  
  /* Для мобильной версии */
  @media (max-width: 768px) {
    .badge--permanent {
      top: -4px;
      right: -4px;
    }
  }
`;
document.head.appendChild(style);

// ===== ФУНКЦИИ ДЛЯ СТРАНИЦЫ КОРЗИНЫ =====
function formatPrice(price) {
  return new Intl.NumberFormat('ru-RU').format(price);
}

function renderCartItems(items, totalQty, totalSum) {
  const cartList = document.getElementById('cartList');
  const cartEmpty = document.getElementById('cartEmpty');
  const cartLayout = document.getElementById('cartLayout');
  const cartTotalQty = document.getElementById('cartTotalQty');
  const cartTotalSum = document.getElementById('cartTotalSum');
  
  if (!cartList) return;
  
  if (!items || items.length === 0) {
    if (cartEmpty) cartEmpty.style.display = '';
    if (cartLayout) cartLayout.style.display = 'none';
    cartList.innerHTML = '';
    return;
  }
  
  if (cartEmpty) cartEmpty.style.display = 'none';
  if (cartLayout) cartLayout.style.display = 'grid';
  
  if (cartTotalQty) cartTotalQty.textContent = totalQty || 0;
  if (cartTotalSum) cartTotalSum.textContent = formatPrice(totalSum || 0);
  
  let html = '';
  items.forEach(item => {
    const code = item.product_code;
    const price = parseInt(item.price, 10) || 0;
    const qty = parseInt(item.qty, 10) || 1;
    const sum = price * qty;
    
    let img = item.image;
    if (img && !img.startsWith('http') && !img.startsWith('/') && !img.startsWith('../')) {
      img = '../' + img;
    }
    if (!img || img.includes('placeholder')) {
      img = '../img/placeholder.webp';
    }
    
    html += `
      <div class="card" style="padding:14px; margin-bottom:12px; position:relative;">
        <div class="cartRow">
          <div class="cartItemImg">
            <img src="${img}" alt="${item.name || 'Товар'}" loading="lazy">
          </div>

          <div style="flex:1;">
            <div class="cartTitle">${item.name || 'Товар'}</div>
            <div class="muted small" style="color:#666;">${formatPrice(price)} ₽ / шт</div>
            
            ${item.meta ? `<div class="muted small cartMeta" style="color:#999;">${item.meta}</div>` : ''}

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
    const data = await cartApi('list');
    renderCartItems(data.items, data.totalQty, data.totalSum);
  } catch (err) {
    if (err.message === 'AUTH_REQUIRED') return;
    console.error('Ошибка загрузки корзины:', err);
  }
}

// ===== ЕДИНСТВЕННЫЙ ОБРАБОТЧИК СОБЫТИЙ =====
document.addEventListener('click', async function(e) {
  // Защита от двойных кликов
  if (isAdding) {
    e.preventDefault();
    return;
  }
  
  // Кнопка "В корзину"
  const addBtn = e.target.closest('[data-add-to-cart]');
  if (addBtn) {
    e.preventDefault();
    isAdding = true;
    
    const productId = addBtn.dataset.productId;
    const productName = addBtn.dataset.productName || 'Товар';
    
    await addToCart(productId, productName);
    
    setTimeout(() => {
      isAdding = false;
    }, 500);
    return;
  }
  
  // Кнопка "+" (увеличить количество)
  const plusBtn = e.target.closest('[data-qty-plus]');
  if (plusBtn) {
    e.preventDefault();
    isAdding = true;
    
    const productId = plusBtn.getAttribute('data-qty-plus') || 
                     plusBtn.closest('[data-qty-wrap]')?.dataset.qtyWrap;
    
    if (productId) {
      await updateQuantity(productId, 'add');
    }
    
    setTimeout(() => {
      isAdding = false;
    }, 300);
    return;
  }
  
  // Кнопка "-" (уменьшить количество)
  const minusBtn = e.target.closest('[data-qty-minus]');
  if (minusBtn) {
    e.preventDefault();
    isAdding = true;
    
    const productId = minusBtn.getAttribute('data-qty-minus') || 
                     minusBtn.closest('[data-qty-wrap]')?.dataset.qtyWrap;
    
    if (productId) {
      await updateQuantity(productId, 'remove');
    }
    
    setTimeout(() => {
      isAdding = false;
    }, 300);
    return;
  }
  
  // Обработчики для страницы корзины
  const removeBtn = e.target.closest('[data-remove]');
  if (removeBtn) {
    e.preventDefault();
    if (!isAuthed()) { openCartAuthModal(); return; }
    
    const code = removeBtn.getAttribute('data-remove');
    
    try {
      await cartApi('remove', { product_code: code });
      await loadCart();
      await syncHitsWithCart();
    } catch (err) {
      if (err.message === 'AUTH_REQUIRED') openCartAuthModal();
      else console.error(err);
    }
    return;
  }
  
  const clearBtn = e.target.closest('#cartClearBtn');
  if (clearBtn) {
    e.preventDefault();
    if (!isAuthed()) { openCartAuthModal(); return; }
    
    if (confirm('Очистить корзину?')) {
      try {
        await cartApi('clear');
        await loadCart();
        await syncHitsWithCart();
      } catch (err) {
        if (err.message === 'AUTH_REQUIRED') openCartAuthModal();
        else console.error(err);
      }
    }
    return;
  }
});

// ===== ИНИЦИАЛИЗАЦИЯ =====
document.addEventListener('DOMContentLoaded', async function() {
  if (isAuthed()) {
    try {
      await syncHitsWithCart();
      if (document.getElementById('cartList')) {
        await loadCart();
      }
    } catch (error) {
      console.error("Ошибка инициализации:", error);
    }
  }
});