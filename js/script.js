/* script.js — единый JS для всех страниц (без дублей) */
(() => {
  "use strict";

  // ==================== GUARD ====================
  if (window.__NORDE_APP_INITED__) return;
  window.__NORDE_APP_INITED__ = true;

  // ==================== HELPERS ====================
  const $ = (s, el = document) => el.querySelector(s);
  const $$ = (s, el = document) => Array.from(el.querySelectorAll(s));

  const lockScroll = (lock) => {
    document.documentElement.style.overflow = lock ? "hidden" : "";
  };

  // ==================== GLOBAL STATE ====================
  // Избранное (должно быть объявлено ДО DOMContentLoaded)
  let favorites = [];

  const safeParse = (raw, fallback) => {
    try {
      const v = JSON.parse(raw);
      return v ?? fallback;
    } catch {
      return fallback;
    }
  };

  const formatMoney = (n) => Number(n || 0).toLocaleString("ru-RU");

  function isAuthorized() {
    return document.documentElement.getAttribute('data-auth') === '1';
  }

  // ✅ нормализация путей, чтобы работало и на file:// и на сервере
  const toRootPath = (path) => {
    if (!path) return "";
    if (/^(https?:)?\/\//i.test(path)) return path;
    if (/^data:/i.test(path)) return path;

    path = path.replace(/^\.\//, "");
    while (path.startsWith("../")) path = path.slice(3);

    if (window.location.protocol === "file:") {
      return path; // "img/..."
    }

    return path.startsWith("/") ? path : "/" + path;
  };

  const toAbsUrl = (path) => {
    const p = toRootPath(path);
    try {
      return new URL(p, document.baseURI).href;
    } catch {
      return p;
    }
  };

  // ==================== FAVORITES MIGRATION ====================
  // Раньше изображения в избранном могли сохраняться как "../img/..." или "./img/...".
  // На разных страницах такие пути ломаются. Нормализуем один раз и сохраняем обратно.
  function migrateFavoritesImages() {
    let raw = null;
    try {
      raw = localStorage.getItem("norde_favorites");
    } catch (e) {
      return;
    }

    if (!raw) return;

    const arr = safeParse(raw, []);
    if (!Array.isArray(arr) || !arr.length) return;

    let changed = false;

    const migrated = arr.map((it) => {
      if (!it || typeof it !== "object") return it;

      // поддерживаем разные поля (на всякий)
      const key = ("img" in it) ? "img" : ("image" in it) ? "image" : ("imageUrl" in it) ? "imageUrl" : null;
      if (!key) return it;

      const cur = String(it[key] || "");
      if (!cur) return it;

      const next = toRootPath(cur);
      if (next && next !== cur) {
        changed = true;
        return { ...it, [key]: next };
      }
      return it;
    });

    if (changed) {
      try {
        localStorage.setItem("norde_favorites", JSON.stringify(migrated));
      } catch (e) {}
    }
  }
  // ==================== FAVORITES CORE ====================
  const FAV_STORAGE_KEY = "norde_favorites";
  const CART_STORAGE_KEY = "norde_cart";

  const saveFavoritesToLocalStorage = () => {
    try {
      localStorage.setItem(FAV_STORAGE_KEY, JSON.stringify(favorites));
    } catch (e) {}
  };

  const readFavoritesFromLocalStorage = () => {
    try {
      const raw = localStorage.getItem(FAV_STORAGE_KEY);
      const arr = safeParse(raw, []);
      return Array.isArray(arr) ? arr : [];
    } catch (e) {
      return [];
    }
  };

  const normalizeFavItem = (it) => {
    if (!it || typeof it !== "object") return null;
    const id = String(it.id ?? it.product_id ?? it.productId ?? "").trim();
    if (!id) return null;

    const name = String(it.name ?? it.title ?? it.product_name ?? it.productName ?? "").trim() || "Товар";
    const price = Number(it.price ?? it.product_price ?? it.productPrice ?? 0) || 0;

    // поддерживаем разные поля картинки
    const img =
      it.img ?? it.image ?? it.imageUrl ?? it.product_img ?? it.productImg ?? it.product_image ?? "";

    const qty = Math.max(1, Number(it.qty ?? it.quantity ?? 1) || 1);

    return {
      id,
      name,
      price,
      img: img ? toRootPath(String(img)) : "",
      qty
    };
  };

  const isFavorite = (id) => favorites.some((x) => String(x.id) === String(id));

  const getFavBtnData = (btn) => {
    if (!btn) return null;

    const id =
      btn.getAttribute("data-product-id") ||
      btn.dataset.productId ||
      btn.closest("[data-product]")?.getAttribute("data-id") ||
      btn.closest("[data-product]")?.dataset.id;

    if (!id) return null;

    const name =
      btn.getAttribute("data-product-name") ||
      btn.dataset.productName ||
      btn.closest("[data-product]")?.getAttribute("data-name") ||
      btn.closest("[data-product]")?.dataset.name ||
      btn.getAttribute("aria-label") ||
      "Товар";

    const priceRaw =
      btn.getAttribute("data-product-price") ||
      btn.dataset.productPrice ||
      btn.closest("[data-product]")?.getAttribute("data-price") ||
      btn.closest("[data-product]")?.dataset.price;

    const price = Number(String(priceRaw || "").replace(/[\s₽]/g, "")) || 0;

    // пробуем вытащить картинку из data-product-img, data-bg или style background-image
    const img =
      btn.getAttribute("data-product-img") ||
      btn.dataset.productImg ||
      btn.closest("[data-product]")?.getAttribute("data-product-img") ||
      btn.closest("[data-product]")?.dataset.productImg ||
      btn.closest("[data-product]")?.querySelector("[data-bg]")?.getAttribute("data-bg") ||
      btn.closest("[data-product]")?.querySelector("[data-bg]")?.dataset.bg ||
      "";

    return normalizeFavItem({ id, name, price, img, qty: 1 });
  };

  const loadFavorites = async () => {
    // для гостя — из localStorage (хотя на главной вы их очищаете)
    if (!isAuthorized()) {
      favorites = readFavoritesFromLocalStorage().map(normalizeFavItem).filter(Boolean);
      return favorites;
    }

    // авторизован: пробуем с сервера, иначе fallback на localStorage
    try {
      const res = await fetch(toRootPath("/php/favorites.php?action=list"), {
        credentials: "same-origin"
      });
      const data = await res.json();
      const arr = Array.isArray(data?.favorites) ? data.favorites : Array.isArray(data) ? data : [];
      favorites = arr.map(normalizeFavItem).filter(Boolean);
      // можно сохранить локально как кэш
      saveFavoritesToLocalStorage();
      return favorites;
    } catch (e) {
      favorites = readFavoritesFromLocalStorage().map(normalizeFavItem).filter(Boolean);
      return favorites;
    }
  };

  const updateFavoritesBadge = () => {
    const badge = document.getElementById("favoritesCount");
    const desc = document.getElementById("favorites-count-desc");

    const total = favorites.reduce((s, i) => s + (Number(i.qty) || 1), 0);

    if (badge) badge.textContent = String(total);
    if (desc) desc.textContent = `Товаров в избранном: ${total}`;
  };

  const updateFavoriteButtons = () => {
    const btns = $$("[data-fav-btn]");
    if (!btns.length) return;

    btns.forEach((btn) => {
      const id =
        btn.getAttribute("data-product-id") ||
        btn.dataset.productId ||
        btn.closest("[data-product]")?.dataset.id;

      if (!id) return;

      const active = isFavorite(id);
      btn.setAttribute("aria-pressed", active ? "true" : "false");
      btn.classList.toggle("is-active", active);
    });
  };

  const toggleFavorite = async (btn) => {
    const item = getFavBtnData(btn);
    if (!item) return;

    // если не авторизован — открываем модалку входа
    if (!isAuthorized()) {
      const modalId = btn.getAttribute("data-open-modal") || "authModal";
      if (document.getElementById(modalId)) openModal(modalId);
      return;
    }

    // авторизован: серверное избранное
    try {
      const res = await fetch(toRootPath("/php/favorites.php"), {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        credentials: "same-origin",
        body: JSON.stringify({
          action: isFavorite(item.id) ? "remove" : "add",
          product_id: item.id,
          name: item.name,
          price: item.price,
          image: item.img
        })
      });

      const data = await res.json().catch(() => ({}));

      if (data?.success === false) throw new Error(data?.error || "favorites api error");

      await loadFavorites().then(() => {
      updateFavoritesBadge();
      updateFavoriteButtons();
      renderFavoritesSheet();
    });
      updateFavoritesBadge();
      updateFavoriteButtons();
      renderFavoritesSheet();
      announce(isFavorite(item.id) ? "Добавлено в избранное" : "Удалено из избранного");
      return;
    } catch (e) {
      // fallback: локально (чтобы UI не ломался)
      if (isFavorite(item.id)) {
        favorites = favorites.filter((x) => x.id !== item.id);
      } else {
        favorites.push(item);
      }
      saveFavoritesToLocalStorage();
      updateFavoritesBadge();
      updateFavoriteButtons();
      renderFavoritesSheet();
    }
  };

  const changeFavQty = async (id, delta) => {
    const idx = favorites.findIndex((x) => x.id === id);
    if (idx < 0) return;
    const nextQty = Math.max(1, (Number(favorites[idx].qty) || 1) + Number(delta || 0));

    if (!isAuthorized()) {
      favorites[idx].qty = nextQty;
      saveFavoritesToLocalStorage();
      updateFavoritesBadge();
      renderFavoritesSheet();
      return;
    }

    try {
      await fetch(toRootPath("/php/favorites.php"), {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        credentials: "same-origin",
        body: JSON.stringify({ action: "update_qty", product_id: id, qty: nextQty })
      });
      await loadFavorites();
      updateFavoritesBadge();
      renderFavoritesSheet();
    } catch (e) {
      favorites[idx].qty = nextQty;
      saveFavoritesToLocalStorage();
      updateFavoritesBadge();
      renderFavoritesSheet();
    }
  };

  const setFavQty = async (id, qty) => {
    const idx = favorites.findIndex((x) => x.id === id);
    if (idx < 0) return;
    const nextQty = Math.max(1, Number(qty) || 1);

    if (!isAuthorized()) {
      favorites[idx].qty = nextQty;
      saveFavoritesToLocalStorage();
      updateFavoritesBadge();
      renderFavoritesSheet();
      return;
    }

    try {
      await fetch(toRootPath("/php/favorites.php"), {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        credentials: "same-origin",
        body: JSON.stringify({ action: "update_qty", product_id: id, qty: nextQty })
      });
      await loadFavorites();
      updateFavoritesBadge();
      renderFavoritesSheet();
    } catch (e) {
      favorites[idx].qty = nextQty;
      saveFavoritesToLocalStorage();
      updateFavoritesBadge();
      renderFavoritesSheet();
    }
  };

  const removeFavorite = async (id) => {
    if (!id) return;

    if (!isAuthorized()) {
      favorites = favorites.filter((x) => x.id !== id);
      saveFavoritesToLocalStorage();
      updateFavoritesBadge();
      updateFavoriteButtons();
      renderFavoritesSheet();
      return;
    }

    try {
      await fetch(toRootPath(`/php/favorites.php?action=remove&product_id=${encodeURIComponent(id)}`), {
        credentials: "same-origin"
      });
      await loadFavorites();
      updateFavoritesBadge();
      updateFavoriteButtons();
      renderFavoritesSheet();
    } catch (e) {
      favorites = favorites.filter((x) => x.id !== id);
      saveFavoritesToLocalStorage();
      updateFavoritesBadge();
      updateFavoriteButtons();
      renderFavoritesSheet();
    }
  };

  const clearFavorites = async () => {
    if (!confirm("Очистить избранное?")) return;

    if (!isAuthorized()) {
      favorites = [];
      saveFavoritesToLocalStorage();
      updateFavoritesBadge();
      updateFavoriteButtons();
      renderFavoritesSheet();
      return;
    }

    try {
      await fetch(toRootPath("/php/favorites.php?action=clear"), { credentials: "same-origin" });
      await loadFavorites();
      updateFavoritesBadge();
      updateFavoriteButtons();
      renderFavoritesSheet();
    } catch (e) {
      favorites = [];
      saveFavoritesToLocalStorage();
      updateFavoritesBadge();
      updateFavoriteButtons();
      renderFavoritesSheet();
    }
  };

  const readCart = () => {
    try {
      const raw = localStorage.getItem(CART_STORAGE_KEY);
      const arr = safeParse(raw, []);
      return Array.isArray(arr) ? arr : [];
    } catch (e) {
      return [];
    }
  };

  const saveCart = (cart) => {
    try {
      localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(cart));
    } catch (e) {}
  };

  const addFavoriteToCart = (id) => {
    const item = favorites.find((x) => x.id === id);
    if (!item) return;

    const cart = readCart();
    const idx = cart.findIndex((c) => String(c.id) === String(id));
    if (idx >= 0) {
      cart[idx].qty = (Number(cart[idx].qty) || 1) + (Number(item.qty) || 1);
    } else {
      cart.push({ ...item });
    }
    saveCart(cart);
    announce("Добавлено в корзину");
  };

  const addAllFavoritesToCart = () => {
    if (!favorites.length) return;
    favorites.forEach((it) => addFavoriteToCart(it.id));
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
        ${favorites
          .map((item) => {
            const qty = Number(item.qty) || 1;
            const lineSum = (Number(item.price) || 0) * qty;
            const imgUrl = toAbsUrl(item.img || item.image || "");

            return `
              <div class="favorites-item" data-favorite-id="${item.id}">
                <div class="favorites-item__image"
                     style="background-image:url('${imgUrl}')"
                     role="img" aria-label="${item.name}"></div>

                <div class="favorites-item__info">
                  <div class="favorites-item__name">${item.name}</div>
                  <div class="favorites-item__price">
                    ${formatMoney(item.price)} ₽
                    <span class="muted small"> • сумма: ${formatMoney(lineSum)} ₽</span>
                  </div>

                  <div class="favorites-qty" style="margin-top:10px; display:flex; gap:10px; align-items:center;">
                    <button class="btn btn--outline btn--sm"
                            type="button"
                            data-fav-qty-minus
                            data-id="${item.id}"
                            aria-label="Уменьшить количество ${item.name}">−</button>

                    <input class="input"
                           style="width:70px; text-align:center;"
                           type="number"
                           min="1"
                           value="${qty}"
                           data-fav-qty-input
                           data-id="${item.id}"
                           aria-label="Количество ${item.name}" />

                    <button class="btn btn--outline btn--sm"
                            type="button"
                            data-fav-qty-plus
                            data-id="${item.id}"
                            aria-label="Увеличить количество ${item.name}">+</button>
                  </div>
                </div>

                <div class="favorites-item__actions">
                  <button class="btn btn--dark btn--sm"
                          type="button"
                          data-favorite-add-to-cart
                          data-id="${item.id}"
                          aria-label="Добавить ${item.name} в корзину">В корзину</button>

                  <button class="iconBtn"
                          type="button"
                          data-remove-favorite
                          data-id="${item.id}"
                          aria-label="Удалить ${item.name} из избранного">✕</button>
                </div>
              </div>
            `;
          })
          .join("")}
      </div>
    `;

    if (actions) actions.style.display = "block";
  };

  // экспортируем несколько функций в window (на случай использования где-то ещё)
  window.changeFavQty = changeFavQty;
  window.setFavQty = setFavQty;
  window.removeFavorite = removeFavorite;
  window.clearFavorites = clearFavorites;
  window.addFavoriteToCart = addFavoriteToCart;
  window.addAllFavoritesToCart = addAllFavoritesToCart;


  // ==================== LAZY BACKGROUNDS ====================
  const initLazyBg = () => {
    const els = $$("[data-bg]");
    if (!els.length) return;

    // если IntersectionObserver нет — просто проставим сразу
    if (!("IntersectionObserver" in window)) {
      els.forEach((el) => {
        const url = el.getAttribute("data-bg");
        if (url && (!el.style.backgroundImage || el.style.backgroundImage === "none")) {
          el.style.backgroundImage = `url('${url}')`;
        }
      });
      return;
    }

    const io = new IntersectionObserver(
      (entries, observer) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting) return;

          const el = entry.target;
          const url = el.getAttribute("data-bg");
          if (!url) return observer.unobserve(el);

          if (el.style.backgroundImage && el.style.backgroundImage !== "none") {
            return observer.unobserve(el);
          }

          const img = new Image();
          el.classList.add("loading");
          img.onload = () => {
            el.style.backgroundImage = `url('${url}')`;
            el.classList.remove("loading");
          };
          img.onerror = () => {
            el.classList.remove("loading");
            console.warn("Failed to load bg:", url);
          };
          img.src = url;

          observer.unobserve(el);
        });
      },
      { rootMargin: "120px 0px", threshold: 0.1 }
    );

    els.forEach((el) => io.observe(el));
  };

  // ==================== MENU ====================
  const initBurgerMenu = () => {
    const burger = $("[data-burger]");
    const menu = $("[data-menu]");
    if (!burger || !menu) return;

    const close = () => {
      menu.classList.remove("is-open");
      burger.setAttribute("aria-expanded", "false");
    };

    burger.addEventListener("click", () => {
      const isOpen = menu.classList.toggle("is-open");
      burger.setAttribute("aria-expanded", String(isOpen));
    });

    document.addEventListener("click", (e) => {
      if (!menu.classList.contains("is-open")) return;
      if (menu.contains(e.target) || burger.contains(e.target)) return;
      close();
    });

    menu.addEventListener("click", (e) => {
      const link = e.target.closest("a");
      if (!link) return;
      if (window.matchMedia("(max-width: 820px)").matches) close();
    });

    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && menu.classList.contains("is-open")) close();
    });
  };

  const initMegaDropdown = () => {
    const wrap = $("[data-dropdown]");
    const btn = $("[data-dropdown-btn]");
    const mega = $("[data-dropdown-menu]");
    if (!wrap || !btn || !mega) return;

    const close = () => {
      mega.classList.remove("is-open");
      btn.setAttribute("aria-expanded", "false");
    };

    btn.addEventListener("click", (e) => {
      e.preventDefault();
      const isOpen = mega.classList.toggle("is-open");
      btn.setAttribute("aria-expanded", String(isOpen));
    });

    document.addEventListener("click", (e) => {
      if (!mega.classList.contains("is-open")) return;
      if (!wrap.contains(e.target)) close();
    });

    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && mega.classList.contains("is-open")) close();
    });

    document.addEventListener("click", (e) => {
      if (!e.target.closest("[data-close-mega]")) return;
      close();
    });
  };

  // ==================== MODALS ====================
  const openModal = (modal) => {
    modal.classList.add("is-open");
    modal.setAttribute("aria-hidden", "false");
    modal.setAttribute("aria-modal", "true");
    lockScroll(true);

    const focusable = modal.querySelector(
      "input, button, [tabindex]:not([tabindex='-1'])"
    );
    focusable?.focus?.();
  };

  const closeModal = (modal) => {
    modal.classList.remove("is-open");
    modal.setAttribute("aria-hidden", "true");
    modal.setAttribute("aria-modal", "false");
    lockScroll(false);
  };

  const initModals = () => {
    document.addEventListener("click", (e) => {
      const openBtn = e.target.closest("[data-open-modal]");
      if (!openBtn) return;

      const id = openBtn.getAttribute("data-open-modal");
      const modal = id ? document.getElementById(id) : null;
      if (modal) openModal(modal);
    });

    document.addEventListener("click", (e) => {
      if (!e.target.classList.contains("modal__backdrop")) return;
      const modal = e.target.closest(".modal");
      if (modal) closeModal(modal);
    });

    document.addEventListener("keydown", (e) => {
      if (e.key !== "Escape") return;
      const opened = $(".modal.is-open");
      if (opened) closeModal(opened);
    });
  };

  // ==================== SHEETS ====================
  const openSheet = (sheet) => {
    sheet.classList.add("is-open");
    sheet.setAttribute("aria-hidden", "false");
    sheet.setAttribute("aria-modal", "true");
    lockScroll(true);
  };

  const closeSheet = (sheet) => {
    sheet.classList.remove("is-open");
    sheet.setAttribute("aria-hidden", "true");
    sheet.setAttribute("aria-modal", "false");
    lockScroll(false);
  };

  const initSheets = () => {
    document.addEventListener("click", (e) => {
      const openBtn = e.target.closest("[data-open-sheet]");
      if (!openBtn) return;

      const id = openBtn.getAttribute("data-open-sheet");
      const sheet = id ? document.getElementById(id) : null;
      if (!sheet) return;

      openSheet(sheet);
      if (sheet.id === "favoritesSheet") renderFavoritesSheet();
    });

    document.addEventListener("click", (e) => {
      if (!e.target.classList.contains("sheet__backdrop")) return;
      const sheet = e.target.closest(".sheet");
      if (sheet) closeSheet(sheet);
    });

    document.addEventListener("keydown", (e) => {
      if (e.key !== "Escape") return;
      const opened = $(".sheet.is-open");
      if (opened) closeSheet(opened);
    });
  };

  // закрытие по крестику/кнопке — capture phase
  const initCloseButtonsCapture = () => {
    document.addEventListener(
      "click",
      (e) => {
        const closeBtn = e.target.closest(
          "[data-close], [data-close-modal], [data-close-sheet]"
        );
        if (!closeBtn) return;

        const modal = closeBtn.closest(".modal.is-open");
        if (modal) {
          e.preventDefault();
          closeModal(modal);
          return;
        }

        const sheet = closeBtn.closest(".sheet.is-open");
        if (sheet) {
          e.preventDefault();
          closeSheet(sheet);
          return;
        }
      },
      true
    );
  };

  // ==================== A11Y ANNOUNCER ====================
  const getAnnouncer = () => {
    let el = document.getElementById("screen-reader-announcer");
    if (el) return el;

    el = document.createElement("div");
    el.id = "screen-reader-announcer";
    el.className = "visually-hidden";
    el.setAttribute("aria-live", "assertive");
    el.setAttribute("aria-atomic", "true");
    document.body.appendChild(el);
    return el;
  };

  const announce = (msg) => {
    const el = getAnnouncer();
    el.textContent = msg;
    setTimeout(() => (el.textContent = ""), 1200);
  };

  // ==================== EVENT DELEGATION (FAV) ====================
  const initFavoritesDelegation = () => {
    document.addEventListener("click", (e) => {
      const favBtn = e.target.closest("[data-fav-btn]");
      if (favBtn) {
        e.preventDefault();
        toggleFavorite(favBtn);
        return;
      }

      if (e.target.closest("#clear-favorites")) {
        clearFavorites();
        return;
      }

      if (e.target.closest("#add-all-to-cart")) {
        addAllFavoritesToCart();
        return;
      }

      const removeBtn = e.target.closest("[data-remove-favorite]");
      if (removeBtn) {
        const id = removeBtn.getAttribute("data-product-id");
        if (id) removeFavorite(id);
        return;
      }

      const addBtn = e.target.closest("[data-favorite-add-to-cart]");
      if (addBtn) {
        const id = addBtn.getAttribute("data-product-id");
        if (id) addFavoriteToCart(id);
        return;
      }

      const minus = e.target.closest("[data-fav-qty-minus]");
      const plus = e.target.closest("[data-fav-qty-plus]");
      if (minus || plus) {
        const btn = minus || plus;
        const id = btn.getAttribute("data-product-id");
        const delta = plus ? 1 : -1;
        if (id) changeFavQty(id, delta);
        return;
      }
    });

    document.addEventListener("change", (e) => {
      const inp = e.target.closest("[data-fav-qty-input]");
      if (!inp) return;
      const id = inp.getAttribute("data-product-id");
      const val = Math.max(1, parseInt(inp.value, 10) || 1);
      if (id) setFavQty(id, val);
    });
  };

  // ==================== HERO SLIDER (один) ====================
  class HeroSlider {
    constructor() {
      this.wrap = document.getElementById("hero");
      this.slidesWrap = document.getElementById("heroSlides");
      this.dotsWrap = document.getElementById("heroDots");

      if (!this.wrap || !this.slidesWrap || !this.dotsWrap) return;

      this.slides = Array.from(this.slidesWrap.querySelectorAll(".hero__slide"));
      if (!this.slides.length) return;

      this.current = this.slides.findIndex((s) => s.classList.contains("is-active"));
      if (this.current < 0) this.current = 0;

      this.timer = null;
      this.interval = 6000;

      this.buildDots();
      this.bindTapZones();
      this.bindDots();
      this.bindHoverPause();
      this.bindSwipe();

      this.go(this.current, false);
      this.startAuto();
    }

    buildDots() {
      this.dotsWrap.innerHTML = "";
      this.dots = this.slides.map((_, i) => {
        const b = document.createElement("button");
        b.type = "button";
        b.className = "dot" + (i === this.current ? " is-active" : "");
        b.setAttribute("aria-label", `Слайд ${i + 1}`);
        this.dotsWrap.appendChild(b);
        return b;
      });
    }

    bindTapZones() {
      this.slides.forEach((slide) => {
        const prev = slide.querySelector(".hero__tap--prev");
        const next = slide.querySelector(".hero__tap--next");

        prev?.addEventListener("click", (e) => {
          e.preventDefault();
          this.prev();
        });

        next?.addEventListener("click", (e) => {
          e.preventDefault();
          this.next();
        });
      });
    }

    bindDots() {
      this.dots.forEach((dot, i) => dot.addEventListener("click", () => this.go(i)));
    }

    bindHoverPause() {
      this.wrap.addEventListener("mouseenter", () => this.stopAuto());
      this.wrap.addEventListener("mouseleave", () => this.startAuto());
    }

    bindSwipe() {
      let startX = null;

      this.slidesWrap.addEventListener(
        "touchstart",
        (e) => {
          startX = e.changedTouches[0].clientX;
        },
        { passive: true }
      );

      this.slidesWrap.addEventListener(
        "touchend",
        (e) => {
          if (startX === null) return;
          const endX = e.changedTouches[0].clientX;
          const diff = startX - endX;
          startX = null;

          if (Math.abs(diff) > 50) {
            if (diff > 0) this.next();
            else this.prev();
          }
        },
        { passive: true }
      );
    }

    go(index, resetAuto = true) {
      if (!this.slides[this.current]) return;

      if (index < 0) index = this.slides.length - 1;
      if (index >= this.slides.length) index = 0;

      this.slides[this.current].classList.remove("is-active");
      this.dots[this.current]?.classList.remove("is-active");

      this.current = index;

      this.slides[this.current].classList.add("is-active");
      this.dots[this.current]?.classList.add("is-active");

      this.slidesWrap.setAttribute(
        "aria-label",
        `Слайд ${this.current + 1} из ${this.slides.length}`
      );

      if (resetAuto) this.restartAuto();
    }

    next() {
      this.go(this.current + 1);
    }
    prev() {
      this.go(this.current - 1);
    }

    startAuto() {
      this.stopAuto();
      this.timer = setInterval(() => this.next(), this.interval);
    }

    stopAuto() {
      if (this.timer) clearInterval(this.timer);
      this.timer = null;
    }

    restartAuto() {
      this.stopAuto();
      this.startAuto();
    }
  }

  // ==================== REVEAL ====================
  const initReveal = () => {
    const items = $$(".reveal");
    if (!items.length) return;

    if (!("IntersectionObserver" in window)) {
      items.forEach((el) => el.classList.add("in"));
      return;
    }

    const io = new IntersectionObserver(
      (entries) => {
        entries.forEach((en) => {
          if (!en.isIntersecting) return;
          en.target.classList.add("in");
          io.unobserve(en.target);
        });
      },
      { threshold: 0.12, rootMargin: "80px" }
    );

    items.forEach((el) => io.observe(el));
  };

  // ==================== CATALOG FILTERS/SEARCH (если есть) ====================
  const initCatalogFilters = () => {
    const filtersWrap = document.getElementById("categoryFilters");
    const searchInput = document.getElementById("searchInput");
    const searchClear = document.querySelector("[data-search-clear]");
    const clearFiltersBtn = document.getElementById("clear-filters");

    if (!filtersWrap && !searchInput) return;

    let activeFilter = "all";
    let searchTerm = "";
    let searchTimeout = null;

    const debounce = (fn, wait) => (...args) => {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => fn.apply(null, args), wait);
    };

    const loadFilterableProducts = () => {
      const sections = Array.from(document.querySelectorAll("[data-group]"));
      const list = [];
      sections.forEach((section) => {
        section.querySelectorAll("[data-product]").forEach((card) => {
          list.push({
            element: card,
            name: (card.getAttribute("data-name") || "").toLowerCase(),
            category: card.getAttribute("data-category") || "unknown",
            group: card.closest("[data-group]"),
          });
        });
      });
      return list;
    };

    const filterableProducts = loadFilterableProducts();

    const applyFilters = () => {
      let visibleCount = 0;
      const hasFilter = activeFilter !== "all" || !!searchTerm;

      filterableProducts.forEach(({ element, name, category }) => {
        const matchCategory = activeFilter === "all" || category === activeFilter;
        const matchSearch = !searchTerm || name.includes(searchTerm);
        const isVisible = matchCategory && matchSearch;

        element.style.display = isVisible ? "" : "none";
        element.setAttribute("aria-hidden", String(!isVisible));
        if (isVisible) visibleCount++;
      });

      document.querySelectorAll("[data-group]").forEach((group) => {
        const visibleInGroup = group.querySelectorAll(
          '[data-product]:not([style*="display: none"])'
        );
        const hasVisible = visibleInGroup.length > 0;
        group.style.display = hasVisible ? "" : "none";
        group.setAttribute("aria-hidden", String(!hasVisible));
      });

      if (clearFiltersBtn) clearFiltersBtn.style.display = hasFilter ? "inline-block" : "none";
      if (searchClear) searchClear.classList.toggle("visually-hidden", !searchInput?.value);
    };

    const setActiveFilter = (filterBtn) => {
      const filter = filterBtn.getAttribute("data-filter") || "all";

      $$("#categoryFilters [role='tab']").forEach((tab) => {
        const isSelected = tab === filterBtn;
        tab.setAttribute("aria-selected", String(isSelected));
        tab.classList.toggle("is-active", isSelected);
      });

      activeFilter = filter;

      const anchor = document.getElementById("filtersAnchor");
      anchor?.scrollIntoView?.({ behavior: "smooth", block: "start" });
    };

    const resetFilters = () => {
      activeFilter = "all";
      searchTerm = "";

      const allBtn = document.getElementById("filter-all");
      if (allBtn) {
        $$("#categoryFilters [role='tab']").forEach((tab) => {
          const isAll = tab === allBtn;
          tab.setAttribute("aria-selected", String(isAll));
          tab.classList.toggle("is-active", isAll);
        });
      }

      if (searchInput) searchInput.value = "";
      if (searchClear) searchClear.classList.add("visually-hidden");

      applyFilters();
      allBtn?.focus?.();
    };

    if (filtersWrap) {
      filtersWrap.addEventListener("click", (e) => {
        const filterBtn = e.target.closest("[data-filter]");
        if (!filterBtn) return;
        e.preventDefault();
        setActiveFilter(filterBtn);
        applyFilters();
      });
    }

    if (searchInput) {
      const debouncedSearch = debounce(() => {
        searchTerm = searchInput.value.trim().toLowerCase();
        applyFilters();
      }, 250);

      searchInput.addEventListener("input", debouncedSearch);
    }

    searchClear?.addEventListener("click", () => {
      if (!searchInput) return;
      searchInput.value = "";
      searchTerm = "";
      searchClear.classList.add("visually-hidden");
      applyFilters();
      searchInput.focus();
    });

    clearFiltersBtn?.addEventListener("click", resetFilters);

    applyFilters();
  };

  // ==================== GIFT BUILDER (2-4 items + tags) ====================
  const initGiftBuilder = () => {
    const form = document.getElementById("giftForm");
    if (!form) return;

    const boxes = Array.from(form.querySelectorAll('input[type="checkbox"][name="giftItems"]'));
    if (!boxes.length) return;

    const counter = document.getElementById("giftPicked");
    const btn = document.getElementById("giftSubmit");
    const note = document.getElementById("giftNote");
    const tagsWrap = document.getElementById("giftPickedTags");
    const clearAll = document.getElementById("giftClearAll");

    const MIN = 2;
    const MAX = 4;

    const getLabel = (box) => {
      // value предпочтительнее (корректно для dropdown)
      const v = (box.value || "").trim();
      if (v) return v;

      // fallback: ищем текст рядом
      const txt =
        box.closest("label")?.textContent?.replace(/\s+/g, " ")?.trim() ||
        "Товар";
      return txt;
    };

    const escapeCss = (s) => {
      // CSS.escape может отсутствовать в старых браузерах
      if (window.CSS && typeof window.CSS.escape === "function") return window.CSS.escape(s);
      return String(s).replace(/["\\#.:?[\]()=]/g, "\\$&");
    };

    const renderTags = () => {
      if (!tagsWrap) return;

      const selected = boxes.filter((b) => b.checked);
      if (!selected.length) {
        tagsWrap.innerHTML = `<span class="muted small">Пока ничего не выбрано.</span>`;
        return;
      }

      tagsWrap.innerHTML = selected
        .map((b) => {
          const text = getLabel(b);
          const key = escapeCss(text);
          return `
            <span class="giftTag">
              ${text}
              <button type="button" data-remove="${key}" aria-label="Убрать ${text}">×</button>
            </span>
          `;
        })
        .join("");
    };

    const update = () => {
      const checked = boxes.filter((b) => b.checked).length;

      if (counter) counter.textContent = String(checked);

      // запретить выбирать больше MAX
      boxes.forEach((b) => {
        b.disabled = !b.checked && checked >= MAX;
      });

      const ok = checked >= MIN && checked <= MAX;
      if (btn) btn.disabled = !ok;

      if (note) {
        note.textContent =
          checked < MIN
            ? `Выберите минимум ${MIN} позиции`
            : checked > MAX
            ? `Можно выбрать максимум ${MAX} позиции`
            : "Можно оформить заказ";
      }

      renderTags();
    };

    boxes.forEach((b) => b.addEventListener("change", update));

    tagsWrap?.addEventListener("click", (e) => {
      const rm = e.target.closest("[data-remove]");
      if (!rm) return;

      // у нас ключ — уже escaped, поэтому сравним по label (не escaped)
      const wanted = rm.getAttribute("data-remove");
      const box = boxes.find((b) => escapeCss(getLabel(b)) === wanted);
      if (box) {
        box.checked = false;
        box.dispatchEvent(new Event("change", { bubbles: true }));
      }
    });

    clearAll?.addEventListener("click", () => {
      boxes.forEach((b) => (b.checked = false));
      update();
    });

    update();
  };



// ==================== PAGE-SPECIFIC EXTRAS ====================
const initEngraveForm = () => {
  const form = document.getElementById("engraveForm");
  if (!form) return;
  const btn = form.querySelector("button");
  if (!btn) return;

  btn.addEventListener("click", () => {
    btn.textContent = "Заявка отправлена ✓";
    btn.disabled = true;
  });
};

const initAboutStatsCounter = () => {
  const companyEl = document.getElementById("company");
  if (!companyEl) return;
  if (!("IntersectionObserver" in window)) return;
  if (typeof animateCounter !== "function") return;

  const io = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;

        $$(".aboutStat__n").forEach((stat) => {
          const value = (stat.textContent || "").trim();
          if (!value.includes("+")) return;
          const num = parseInt(value, 10);
          if (Number.isFinite(num)) animateCounter(stat, num, 1500);
        });

        io.unobserve(entry.target);
      });
    },
    { threshold: 0.5 }
  );

  io.observe(companyEl);
};

const FACTS = [
  "Основаны в 2018 году и с тех пор делаем подарки небольшими партиями.",
  "Каждое изделие проходит 3 этапа проверки перед отправкой.",
  "Персонализацию делаем вручную: надпись/имя/дата — аккуратно и читабельно.",
  "Упаковываем в подарок: плотная коробка + наполнитель, чтобы всё доехало целым.",
  "Работаем с проверенными материалами и фурнитурой — без резких запахов и дешёвых покрытий.",
  "Отправляем по России: подскажем сроки и поможем выбрать удобный вариант доставки."
];

let factIndex = 0;

const showFact = (i) => {
  const el = document.getElementById("randomFact");
  const idxEl = document.getElementById("factIndex");
  const totalEl = document.getElementById("factTotal");
  if (!el || !idxEl || !totalEl) return;

  totalEl.textContent = String(FACTS.length);
  idxEl.textContent = String(i + 1);

  el.classList.remove("is-anim");
  // перезапуск анимации
  void el.offsetWidth;

  el.textContent = FACTS[i];
  el.classList.add("is-anim");
};

const nextFact = () => {
  factIndex = (factIndex + 1) % FACTS.length;
  showFact(factIndex);
};

const initRandomFacts = () => {
  const el = document.getElementById("randomFact");
  if (!el) return;

  showFact(factIndex);

  const btn = document.getElementById("factBtn");
  if (btn) btn.addEventListener("click", nextFact);
};

const initMaterialsReveal = () => {
  const cards = $$("#materials .mat");
  if (!cards.length) return;
  if (!("IntersectionObserver" in window)) return;

  cards.forEach((card, i) => {
    card.style.transitionDelay = `${i * 80}ms`;
  });

  const io = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add("is-in");
        io.unobserve(entry.target);
      });
    },
    { threshold: 0.25 }
  );

  cards.forEach((card) => io.observe(card));
};

  // ==================== INIT ====================
  document.addEventListener("DOMContentLoaded", () => {
    initCloseButtonsCapture();

    initLazyBg();
    initBurgerMenu();
    initMegaDropdown();
    initModals();
    initSheets();

    if (isAuthorized()) {
      migrateFavoritesImages();
      loadFavorites();
    } else {
      // гость: очищаем локальное избранное
      favorites = [];
      try {
        localStorage.removeItem("norde_favorites");
      } catch (e) {}
    }

    updateFavoritesBadge();
    updateFavoriteButtons();
    renderFavoritesSheet();
    initFavoritesDelegation();

    initReveal();
    new HeroSlider();

    initCatalogFilters();
    initGiftBuilder();

    initEngraveForm();
    initAboutStatsCounter();
    initRandomFacts();
    initMaterialsReveal();
  });
})();