(() => {
  "use strict";

  if (window.__LAVKA_FAVORITES_INITED__) return;
  window.__LAVKA_FAVORITES_INITED__ = true;

  const AUTH_MESSAGE = "Чтобы добавить в избранное, сначала войдите в аккаунт.";
  const API_PATH = "php/favorites.php";

  const getCore = () => window.Lavka || {};
  const $ = (selector, root = document) =>
    (getCore().$ || ((s, el = document) => el.querySelector(s)))(selector, root);
  const $$ = (selector, root = document) =>
    (getCore().$$ || ((s, el = document) => Array.from(el.querySelectorAll(s))))(selector, root);
  const onReady = (callback) =>
    (getCore().onReady || ((cb) => {
      if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", cb, { once: true });
      } else {
        cb();
      }
    }))(callback);

  const isAuthorized = () =>
    (getCore().isAuthorized || (() => document.documentElement.dataset.auth === "1"))();

  const toRootPath = (path) =>
    (getCore().toRootPath || ((p) => p))(path);

  const escapeHtml = (value) =>
    (getCore().escapeHtml || ((v) => String(v ?? "")))(value);

  const formatMoney = (value) =>
    (getCore().formatMoney || ((v) => Number(v || 0).toLocaleString("ru-RU")))(value);

  const announce = (message) => {
    const fn = getCore().announce;
    if (typeof fn === "function") {
      fn(message);
    }
  };

  let favorites = [];
  let favoritesSet = new Set();

  const normalizeFavItem = (item) => {
    if (!item || typeof item !== "object") return null;

    const id = String(item.product_code || item.id || "").trim();
    if (!id) return null;

    const img = item.image || item.img || "";

    return {
      id,
      name: String(item.name || "").trim() || "Товар",
      price: Number(item.price || 0) || 0,
      img: img ? toRootPath(String(img)) : "",
      qty: 1
    };
  };

  const getFavoriteApiUrl = (suffix = "") => toRootPath(API_PATH + suffix);

  const getFavBtnData = (btn) => {
    if (!btn) return null;

    const card = btn.closest("[data-product]");

    const id =
      btn.getAttribute("data-product-id") ||
      btn.dataset.productId ||
      card?.getAttribute("data-id") ||
      card?.dataset.id ||
      "";

    if (!id) return null;

    const name =
      btn.getAttribute("data-product-name") ||
      btn.dataset.productName ||
      card?.getAttribute("data-name") ||
      card?.dataset.name ||
      btn.getAttribute("aria-label") ||
      "Товар";

    const priceRaw =
      btn.getAttribute("data-product-price") ||
      btn.dataset.productPrice ||
      card?.getAttribute("data-price") ||
      card?.dataset.price ||
      "0";

    const img =
      btn.getAttribute("data-product-img") ||
      btn.dataset.productImg ||
      card?.getAttribute("data-product-img") ||
      card?.dataset.productImg ||
      card?.querySelector("[data-bg]")?.getAttribute("data-bg") ||
      card?.querySelector("[data-bg]")?.dataset.bg ||
      "";

    return normalizeFavItem({
      id,
      name,
      price: Number(String(priceRaw).replace(/[\s₽]/g, "")) || 0,
      img
    });
  };

  const loadFavorites = async () => {
    if (!isAuthorized()) {
      favorites = [];
      favoritesSet = new Set();
      return favorites;
    }

    try {
      const res = await fetch(getFavoriteApiUrl("?action=list"), {
        credentials: "same-origin",
        headers: {
          "Cache-Control": "no-cache",
          "Pragma": "no-cache"
        }
      });

      if (!res.ok) throw new Error(`HTTP ${res.status}`);

      const data = await res.json();
      const list = Array.isArray(data) ? data : [];

      favorites = list.map(normalizeFavItem).filter(Boolean);
      favoritesSet = new Set(favorites.map((item) => item.id));

      window.dispatchEvent(new CustomEvent("favorites:updated", {
        detail: {
          list: favorites
        }
      }));

      return favorites;
    } catch (error) {
      console.error("Ошибка загрузки избранного:", error);
      favorites = [];
      favoritesSet = new Set();
      return favorites;
    }
  };

  const isFavorite = (id) => favoritesSet.has(String(id ?? "").trim());

  const updateFavoritesBadge = () => {
    const total = favorites.length;
    const badge = document.getElementById("favoritesCount");
    const desc = document.getElementById("favorites-count-desc");

    if (badge) badge.textContent = String(total);
    if (desc) desc.textContent = `Товаров в избранном: ${total}`;
  };

  const updateFavoriteButtons = () => {
    $$("[data-fav-btn]").forEach((btn) => {
      const id =
        btn.getAttribute("data-product-id") ||
        btn.dataset.productId ||
        btn.closest("[data-product]")?.dataset.id ||
        "";

      if (!id) return;

      const active = isFavorite(id);

      btn.setAttribute("aria-pressed", active ? "true" : "false");
      btn.classList.toggle("is-active", active);

      const path = btn.querySelector("svg path");
      if (path) {
        path.style.fill = active ? "#ff4757" : "none";
        path.style.stroke = active ? "#ff4757" : "currentColor";
      }
    });
  };

  const renderFavoritesSheet = () => {
    const content = document.getElementById("favorites-content");
    const actions = document.querySelector(".favorites-actions");

    if (!content) return;

    if (!isAuthorized()) {
      content.innerHTML = '<p class="muted">Чтобы пользоваться избранным, войдите в аккаунт.</p>';
      if (actions) actions.style.display = "none";
      return;
    }

    if (!favorites.length) {
      content.innerHTML = '<p class="muted">В избранном пока ничего нет.</p>';
      if (actions) actions.style.display = "none";
      return;
    }

    content.innerHTML = `
      <div class="favorites-items">
        ${favorites.map((item) => {
          const id = escapeHtml(item.id);
          const name = escapeHtml(item.name);
          const price = formatMoney(item.price);
          const img = escapeHtml(item.img || toRootPath("img/placeholder.webp"));

          return `
            <div class="favorites-item" data-favorite-id="${id}">
              <div class="favorites-item__image">
                <img src="${img}" alt="${name}" loading="lazy">
              </div>

              <div class="favorites-item__info">
                <div class="favorites-item__name">${name}</div>
                <div class="favorites-item__price">${price} ₽</div>
              </div>

              <div class="favorites-item__actions">
                <button
                  class="btn btn--dark btn--sm"
                  type="button"
                  data-add-to-cart
                  data-product-id="${id}"
                  data-product-name="${name}"
                  aria-label="Добавить ${name} в корзину"
                >
                  В корзину
                </button>

                <button
                  class="iconBtn"
                  type="button"
                  data-remove-favorite
                  data-id="${id}"
                  aria-label="Удалить ${name} из избранного"
                >
                  ✕
                </button>
              </div>
            </div>
          `;
        }).join("")}
      </div>
    `;

    if (actions) actions.style.display = "block";

    if (typeof window.syncHitsWithCart === "function") {
      requestAnimationFrame(() => {
        const result = window.syncHitsWithCart();
        if (result && typeof result.catch === "function") {
          result.catch((error) => console.error("Ошибка syncHitsWithCart:", error));
        }
      });
    }
  };

  const toggleFavorite = async (btn) => {
    const item = getFavBtnData(btn);

    if (!item) return;

    if (!isAuthorized()) {
      if (typeof window.openAuthModalWithMessage === "function") {
        window.openAuthModalWithMessage(AUTH_MESSAGE);
      }
      return;
    }

    const wasActive = btn.classList.contains("is-active");
    const action = isFavorite(item.id) ? "remove" : "add";

    try {
      const res = await fetch(getFavoriteApiUrl(), {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest"
        },
        credentials: "same-origin",
        body: JSON.stringify({
          action,
          product_id: item.id,
          product_name: item.name,
          product_price: item.price,
          product_img: item.img
        })
      });

      const data = await res.json().catch(() => ({}));
      if (!res.ok || data.success === false) throw new Error(data.error || `HTTP ${res.status}`);

      await loadFavorites();

      updateFavoritesBadge();
      updateFavoriteButtons();
      renderFavoritesSheet();

      announce(action === "add" ? "Добавлено в избранное" : "Удалено из избранного");
    } catch (error) {
      console.error("Ошибка при обновлении избранного:", error);

      btn.classList.toggle("is-active", wasActive);
      btn.setAttribute("aria-pressed", wasActive ? "true" : "false");

      announce("Ошибка при обновлении избранного");
    }
  };

  const removeFavorite = async (id) => {
    if (!id || !isAuthorized()) return;

    try {
      const res = await fetch(getFavoriteApiUrl(), {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest"
        },
        credentials: "same-origin",
        body: JSON.stringify({
          action: "remove",
          product_id: id
        })
      });

      const data = await res.json().catch(() => ({}));
      if (!res.ok || data.success === false) throw new Error(data.error || `HTTP ${res.status}`);

      await loadFavorites();

      updateFavoritesBadge();
      updateFavoriteButtons();
      renderFavoritesSheet();

      announce("Удалено из избранного");
    } catch (error) {
      console.error("Ошибка удаления из избранного:", error);
      announce("Ошибка при удалении из избранного");
    }
  };

  const clearFavorites = async () => {
    if (!isAuthorized()) return;
    if (!confirm("Очистить избранное?")) return;

    try {
      const res = await fetch(getFavoriteApiUrl(), {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest"
        },
        credentials: "same-origin",
        body: JSON.stringify({
          action: "clear"
        })
      });

      const data = await res.json().catch(() => ({}));
      if (!res.ok || data.success === false) throw new Error(data.error || `HTTP ${res.status}`);

      await loadFavorites();

      updateFavoritesBadge();
      updateFavoriteButtons();
      renderFavoritesSheet();

      announce("Избранное очищено");
    } catch (error) {
      console.error("Ошибка очистки избранного:", error);
      announce("Ошибка при очистке избранного");
    }
  };

  const initFavoritesDelegation = () => {
    document.addEventListener("click", (event) => {
      const favBtn = event.target.closest("[data-fav-btn]");
      if (favBtn) {
        event.preventDefault();
        toggleFavorite(favBtn);
        return;
      }

      if (event.target.closest("#clear-favorites")) {
        event.preventDefault();
        clearFavorites();
        return;
      }

      const removeBtn = event.target.closest("[data-remove-favorite]");
      if (removeBtn) {
        event.preventDefault();

        const id =
          removeBtn.getAttribute("data-id") ||
          removeBtn.getAttribute("data-product-id");

        if (id) removeFavorite(id);
      }
    });
  };

  const updateFavorites = async () => {
    await loadFavorites();

    updateFavoritesBadge();
    updateFavoriteButtons();
    renderFavoritesSheet();
  };

  window.renderFavoritesSheet = renderFavoritesSheet;
  window.removeFavorite = removeFavorite;
  window.clearFavorites = clearFavorites;
  window.updateFavorites = updateFavorites;

  onReady(async () => {
    initFavoritesDelegation();
    await updateFavorites();
  });
})();
