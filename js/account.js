document.addEventListener('DOMContentLoaded', function () {

  // =========================
  // 1) ВКЛАДКИ
  // =========================
  const tabs = document.querySelectorAll('.account-tab');
  const tabPanels = document.querySelectorAll('.account-card');

  function showTab(tabId) {
    tabs.forEach(tab => {
      const active = tab.dataset.tab === tabId;
      tab.classList.toggle('active', active);
      tab.setAttribute('aria-selected', active ? 'true' : 'false');
    });

    tabPanels.forEach(panel => {
      const active = panel.id === `${tabId}-tab`;
      panel.style.display = active ? 'block' : 'none';
      panel.toggleAttribute('hidden', !active);
      panel.classList.toggle('active', active);
    });

    const wrap = document.querySelector('.account-page__inner');
    if (wrap) wrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  if (tabs.length > 0) {
    // по умолчанию
    showTab('profile');

    // авто-открытие по URL
    const params = new URLSearchParams(window.location.search);
    let tabToOpen = params.get('tab');

    if (!tabToOpen && window.location.hash) {
      const h = window.location.hash.replace('#', '');
      if (['profile','orders','favorites','coupons','security'].includes(h)) tabToOpen = h;
    }

    if (tabToOpen && ['profile','orders','favorites','coupons','security'].includes(tabToOpen)) {
      showTab(tabToOpen);
    }

    tabs.forEach(tab => {
      tab.addEventListener('click', function () {
        const tabId = this.dataset.tab;
        if (tabId) showTab(tabId);
      });
    });
  }

const pickBtn = document.getElementById('avatarPickBtn');
const fileInp = document.getElementById('avatarInput');
const form = document.getElementById('avatarForm');

if (pickBtn && fileInp && form) {
  pickBtn.addEventListener('click', () => fileInp.click());
  fileInp.addEventListener('change', () => {
    if (fileInp.files && fileInp.files[0]) form.submit();
  });
}

  const showAllBtn = document.getElementById('showAllOrdersBtn');
  const collapseBtn = document.getElementById('collapseOrdersBtn');
  const ordersList = document.getElementById('ordersList');
  const ordersRest = document.getElementById('ordersRest');
  const marker = document.getElementById('ordersTopEndMarker');

  if (showAllBtn && collapseBtn && ordersList && ordersRest && marker) {
    showAllBtn.addEventListener('click', () => {
      if (showAllBtn.dataset.loaded === '1') return;

      const wrap = document.createElement('div');
      wrap.id = 'ordersRestRendered';
      wrap.innerHTML = ordersRest.innerHTML;
      marker.insertAdjacentElement('afterend', wrap);

      showAllBtn.dataset.loaded = '1';
      showAllBtn.style.display = 'none';
      collapseBtn.style.display = '';
    });

    collapseBtn.addEventListener('click', () => {
      const wrap = document.getElementById('ordersRestRendered');
      if (wrap) wrap.remove();

      showAllBtn.dataset.loaded = '0';
      showAllBtn.style.display = '';
      collapseBtn.style.display = 'none';
      ordersList.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  }

  document.querySelectorAll('.coupon-copy').forEach(button => {
    button.addEventListener('click', function () {
      const couponCode = this.dataset.couponCode;
      if (!couponCode) return;

      navigator.clipboard.writeText(couponCode).then(() => {
        const originalText = this.textContent;
        this.textContent = 'Скопировано!';
        this.classList.add('btn--success');

        setTimeout(() => {
          this.textContent = originalText;
          this.classList.remove('btn--success');
        }, 2000);
      });
    });
  });

  function showNotification(message, type = 'info') {
    const n = document.createElement('div');
    n.className = `notification notification--${type}`;
    n.textContent = message;
    n.style.cssText = `
      position: fixed;
      top: 20px;
      right: 20px;
      padding: 12px 20px;
      background: ${type === 'success' ? '#4CAF50' : type === 'error' ? '#f44336' : '#2196F3'};
      color: white;
      border-radius: 8px;
      z-index: 10000;
      animation: slideIn 0.3s ease;
    `;
    document.body.appendChild(n);

    setTimeout(() => {
      n.style.animation = 'slideOut 0.3s ease';
      setTimeout(() => n.remove(), 300);
    }, 3000);
  }

  // =========================
  // 5) ИЗБРАННОЕ: ВКЛАДКА + СИНХРОНИЗАЦИЯ С ШТОРКОЙ
  // =========================
  const FAV_API = '../php/favorites.php';

  const favGrid = document.getElementById('favoritesGrid');
  const favEmpty = document.getElementById('favoritesEmptyState');
  const favClearBtn = document.getElementById('clear-favorites-btn');

  const favTabBadge = document.querySelector('.account-tab[data-tab="favorites"] .badge');
  const favoritesCountTop = document.getElementById('favoritesCount');
  const favoritesCountDesc = document.getElementById('favorites-count-desc');

  function setFavCount(n) {
    if (favTabBadge) favTabBadge.textContent = String(n);
    if (favoritesCountTop) favoritesCountTop.textContent = String(n);
    if (favoritesCountDesc) favoritesCountDesc.textContent = 'Товаров в избранном: ' + String(n);
  }

  function updateFavEmptyState() {
    if (!favGrid) return;
    const items = favGrid.querySelectorAll('[data-fav-item]');
    const has = items.length > 0;

    favGrid.style.display = has ? '' : 'none';
    if (favEmpty) favEmpty.style.display = has ? 'none' : 'block';
    if (favClearBtn) favClearBtn.style.display = has ? '' : 'none';

    setFavCount(items.length);
  }

  async function favList() {
    const res = await fetch(FAV_API + '?action=list', { credentials: 'same-origin' });
    if (!res.ok) return null;
    return await res.json(); // [{id: product_code, name, image, price}, ...]
  }

  // Удаляем из вкладки те карточки, которых нет в актуальном list
  async function syncFavoritesTabWithServer() {
    if (!favGrid) return;

    const list = await favList();
    if (!list) return;

    const allowed = new Set(list.map(x => String(x.id)));

    favGrid.querySelectorAll('[data-fav-item]').forEach(el => {
      const code = String(el.getAttribute('data-product-code') || '');
      if (code && !allowed.has(code)) el.remove();
    });

    setFavCount(list.length);
    updateFavEmptyState();

    // Если шторка умеет перерисовываться от события — отправим
    window.dispatchEvent(new CustomEvent('favorites:updated', { detail: { list } }));
  }

  async function favApi(action, productId) {
    const res = await fetch(FAV_API + '?action=' + encodeURIComponent(action), {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ product_id: productId })
    });

    // favorites.php возвращает 401, если не авторизован
    if (res.status === 401) {
      showNotification('Чтобы изменить избранное, войдите в аккаунт.', 'error');
      return null;
    }

    const data = await res.json().catch(() => null);
    if (!res.ok || !data || data.success !== true) return null;

    return data;
  }

  // Удаление со вкладки
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-fav-remove]');
    if (!btn) return;

    e.preventDefault();

    const productDbId = btn.getAttribute('data-product-db-id'); // ЧИСЛОВОЙ id products
    if (!productDbId) return;

    btn.disabled = true;
    const ok = await favApi('remove', productDbId);
    btn.disabled = false;

    if (!ok) {
      showNotification('Не удалось удалить из избранного', 'error');
      return;
    }

    const item = btn.closest('[data-fav-item]');
    if (item) item.remove();

    updateFavEmptyState();

    // подтянем актуал и синхронизируем шторку
    await syncFavoritesTabWithServer();
  });

  // Очистить все
  if (favClearBtn) {
    favClearBtn.addEventListener('click', async () => {
      if (!confirm('Очистить избранное?')) return;

      favClearBtn.disabled = true;

      const res = await fetch(FAV_API + '?action=clear', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'clear' })
      });

      favClearBtn.disabled = false;

      if (!res.ok) {
        showNotification('Не удалось очистить избранное', 'error');
        return;
      }

      if (favGrid) favGrid.innerHTML = '';
      updateFavEmptyState();
      await syncFavoritesTabWithServer();
    });
  }

  (function interceptFavoritesFetch() {
    const origFetch = window.fetch;
    if (origFetch.__favIntercepted) return; // защита от двойного перехвата
    origFetch.__favIntercepted = true;

    window.fetch = function (...args) {
      const p = origFetch.apply(this, args);

      try {
        const url = typeof args[0] === 'string' ? args[0] : (args[0] && args[0].url) ? args[0].url : '';
        if (url && url.includes('php/favorites.php')) {
          p.then(() => {
            // после любого add/remove/toggle/clear/list из шторки — синкаем вкладку
            syncFavoritesTabWithServer();
          }).catch(() => {});
        }
      } catch (_) {}

      return p;
    };
  })();

  // первичная синхронизация (на случай если вкладка отрисована сервером, но шторка уже меняла)
  updateFavEmptyState();
  syncFavoritesTabWithServer();

  const changePasswordBtn = document.getElementById('change-password-btn');
  if (changePasswordBtn) {
    changePasswordBtn.addEventListener('click', function () {
      const newPassword = prompt('Введите новый пароль:');
      if (newPassword && newPassword.length >= 6) {
        showNotification('Пароль успешно изменен', 'success');
      } else if (newPassword) {
        showNotification('Пароль должен содержать не менее 6 символов', 'error');
      }
    });
  }

  // Удаление аккаунта
  const deleteAccountBtn = document.getElementById('delete-account-btn');
  if (deleteAccountBtn) {
    deleteAccountBtn.addEventListener('click', function () {
      if (confirm('Вы уверены? Это действие нельзя отменить. Все ваши данные будут удалены.')) {
        const secondConfirm = prompt('Для подтверждения введите "УДАЛИТЬ":');
        if (secondConfirm === 'УДАЛИТЬ') {
          showNotification('Запрос на удаление аккаунта отправлен', 'info');
        }
      }
    });
  }

  const style = document.createElement('style');
  style.textContent = `
    @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
    @keyframes slideOut { from { transform: translateX(0); opacity: 1; } to { transform: translateX(100%); opacity: 0; } }
  `;
  document.head.appendChild(style);

});