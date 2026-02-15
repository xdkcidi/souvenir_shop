(() => {
  "use strict";

  // ==================== GUARD ====================
  if (window.__NORDE_APP_INITED__) return;
  window.__NORDE_APP_INITED__ = true;

  // ==================== CONFIGURATION ====================
  const CONFIG = {
    favorites: {
      minAuthMessage: "Чтобы добавить в избранное, сначала войдите в аккаунт."
    },
    lazyLoad: {
      rootMargin: "120px 0px",
      threshold: 0.1
    },
    heroSlider: {
      interval: 6000,
      swipeThreshold: 50
    },
    giftBuilder: {
      minItems: 2,
      maxItems: 4
    }
  };

  // ==================== HELPERS ====================
  const $ = (s, el = document) => el.querySelector(s);
  const $$ = (s, el = document) => Array.from(el.querySelectorAll(s));

  const lockScroll = (lock) => {
    document.documentElement.style.overflow = lock ? "hidden" : "";
  };

  const safeParse = (raw, fallback) => {
    try {
      const v = JSON.parse(raw);
      return v ?? fallback;
    } catch {
      return fallback;
    }
  };

  const formatMoney = (n) => Number(n || 0).toLocaleString("ru-RU");

  const debounce = (fn, wait) => {
    let timeout;
    return (...args) => {
      clearTimeout(timeout);
      timeout = setTimeout(() => fn.apply(null, args), wait);
    };
  };

  const throttle = (fn, limit) => {
    let inThrottle;
    return (...args) => {
      if (!inThrottle) {
        fn.apply(null, args);
        inThrottle = setTimeout(() => (inThrottle = null), limit);
      }
    };
  };

  function isAuthorized() {
    return document.documentElement.getAttribute('data-auth') === '1';
  }

  const APP_ROOT = (() => {
    try {
      const cs = document.currentScript;
      if (cs && cs.src) {
        const u = new URL(cs.src, document.baseURI);
        return u.pathname.replace(/\/js\/[^\/]+$/, "/");
      }
    } catch (e) {
      console.warn('Could not determine APP_ROOT from script:', e);
    }

    const p = window.location.pathname || "/";
    if (p.includes("/pages/")) return p.split("/pages/")[0] + "/";
    return "/";
  })();

  const toRootPath = (path) => {
    if (!path) return "";
    if (/^(https?:)?\/\//i.test(path)) return path;
    if (/^data:/i.test(path)) return path;

    path = path.replace(/^\.\//, "");
    while (path.startsWith("../")) path = path.slice(3);

    if (window.location.protocol === "file:") return path;

    if (path.startsWith("/")) path = path.slice(1);

    return APP_ROOT + path;
  };

  const toAbsUrl = (path) => {
    const p = toRootPath(path);
    try {
      return new URL(p, document.baseURI).href;
    } catch {
      return p;
    }
  };

  // ==================== LOGGER ====================
  const createLogger = (prefix) => ({
    log: (...args) => console.log(`[${prefix}]`, ...args),
    warn: (...args) => console.warn(`[${prefix}]`, ...args),
    error: (...args) => console.error(`[${prefix}]`, ...args),
    debug: (...args) => console.debug(`[${prefix}]`, ...args)
  });

  const logFav = createLogger('FAV');
  const logApp = createLogger('APP');

  // ==================== FAVORITES CORE ====================
  let favorites = [];
  let favoritesSet = new Set();

  const normalizeFavItem = (it) => {
    if (!it || typeof it !== "object") return null;
    const id = String(it.id || "").trim();
    if (!id) return null;

    const name = String(it.name || "").trim() || "Товар";
    const price = Number(it.price || 0) || 0;
    const img = it.image || it.img || "";
    const qty = 1;

    return {
      id,
      name,
      price,
      img: img ? toRootPath(String(img)) : "",
      qty
    };
  };

  const getFavBtnData = (btn) => {
    logFav.log('Получаем данные кнопки');
    
    if (!btn) {
      logFav.log('Кнопка не найдена');
      return null;
    }

    const id =
      btn.getAttribute("data-product-id") ||
      btn.dataset.productId ||
      btn.closest("[data-product]")?.getAttribute("data-id") ||
      btn.closest("[data-product]")?.dataset.id;

    logFav.log('ID товара:', id);

    if (!id) {
      logFav.log('ID товара не найден');
      return null;
    }

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
    logFav.log('Цена товара:', price);

    const img =
      btn.getAttribute("data-product-img") ||
      btn.dataset.productImg ||
      btn.closest("[data-product]")?.getAttribute("data-product-img") ||
      btn.closest("[data-product]")?.dataset.productImg ||
      btn.closest("[data-product]")?.querySelector("[data-bg]")?.getAttribute("data-bg") ||
      btn.closest("[data-product]")?.querySelector("[data-bg]")?.dataset.bg ||
      "";

    logFav.log('Изображение товара:', img);

    return normalizeFavItem({ id, name, price, img });
  };

const loadFavorites = async () => {
  logFav.log('Загрузка избранного...');

  if (!isAuthorized()) {
    logFav.log('Пользователь не авторизован');
    favorites = [];
    favoritesSet = new Set();
    return favorites;
  }

  logFav.log('Пользователь авторизован, загружаем...');

  try {
    const url = toRootPath("/php/favorites.php?action=list");
    logFav.log('URL запроса:', url);

    const res = await fetch(url, {
      credentials: "same-origin",
      headers: {
        'Cache-Control': 'no-cache',
        'Pragma': 'no-cache'
      }
    });

    logFav.log('Статус ответа:', res.status);

    if (!res.ok) {
      const text = await res.text();
      logFav.error('Ошибка сервера:', text);
      throw new Error(`HTTP ${res.status}`);
    }

    const data = await res.json();
    logFav.log('Получены данные:', data);

    const arr = Array.isArray(data) ? data : [];
    logFav.log('Массив данных:', arr);

    favorites = arr.map(normalizeFavItem).filter(Boolean);
    logFav.log('Нормализованные данные:', favorites);

    // ✅ главное: строим Set правильных id
    favoritesSet = new Set(
      favorites
        .map(x => String(x.id ?? '').trim())
        .filter(Boolean)
    );

    logFav.log('favoritesSet:', Array.from(favoritesSet));
    return favorites;

  } catch (e) {
    logFav.error("Ошибка загрузки избранного:", e);
    favorites = [];
    favoritesSet = new Set();
    return favorites;
  }
};

const isFavorite = (id) => {
  const key = String(id ?? '').trim();
  const found = favoritesSet.has(key);
  logFav.log('Проверка товара', key, 'в избранном:', found);
  return found;
};

  const updateFavoritesBadge = () => {
    const badge = document.getElementById("favoritesCount");
    const desc = document.getElementById("favorites-count-desc");
    const total = favorites.length;

    logFav.log('Обновление бейджа. Товаров:', total);

    if (badge) badge.textContent = String(total);
    if (desc) desc.textContent = `Товаров в избранном: ${total}`;
  };

  const updateFavoriteButtons = () => {
    logFav.log('Обновление кнопок избранного...');
    const btns = $$("[data-fav-btn]");
    logFav.log('Найдено кнопок:', btns.length);
    
    if (!btns.length) return;

    btns.forEach((btn, index) => {
      const id =
        btn.getAttribute("data-product-id") ||
        btn.dataset.productId ||
        btn.closest("[data-product]")?.dataset.id;

      if (!id) {
        logFav.log('Кнопка', index, 'не имеет ID');
        return;
      }

      const active = isFavorite(id);
      logFav.log('Кнопка', index, '(ID:', id, ') активна:', active);
      
      btn.setAttribute("aria-pressed", active ? "true" : "false");
      btn.classList.toggle("is-active", active);
      
      const svg = btn.querySelector('svg');
      if (svg) {
        const path = svg.querySelector('path');
        if (path) {
          if (active) {
            path.style.fill = '#ff4757';
            path.style.stroke = '#ff4757';
          } else {
            path.style.fill = 'none';
            path.style.stroke = 'currentColor';
          }
        }
      }
    });
  };

  const toggleFavorite = async (btn) => {
    logFav.log('Клик по избранному');
    
    const wasActive = btn.classList.contains("is-active");
    const item = getFavBtnData(btn);
    
    if (!item) {
      logFav.log('Данные товара не получены');
      return;
    }

    if (!isAuthorized()) {
      logFav.log('Пользователь не авторизован');
      openAuthModalWithMessage(CONFIG.favorites.minAuthMessage);
      return;
    }

    logFav.log('Пользователь авторизован');
    const isCurrentlyFavorite = isFavorite(item.id);
    logFav.log('Товар уже в избранном?', isCurrentlyFavorite);
    
    const action = isCurrentlyFavorite ? "remove" : "add";
    logFav.log('Выполняем действие:', action);

    try {
      logFav.log('Отправляем запрос...');
      
      const res = await fetch(toRootPath("/php/favorites.php"), {
        method: "POST",
        headers: { 
          "Content-Type": "application/json",
          'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: "same-origin",
        body: JSON.stringify({
          action: action,
          product_id: item.id,
          product_name: item.name,
          product_price: item.price,
          product_img: item.img
        })
      });

      logFav.log('Ответ сервера:', res.status);
      
      const data = await res.json();
      logFav.log('Данные ответа:', data);

      await loadFavorites();
      updateFavoritesBadge();
      updateFavoriteButtons();
      renderFavoritesSheet();
      
      const message = action === 'add' 
        ? "Добавлено в избранное" 
        : "Удалено из избранного";
      logFav.log('Сообщение:', message);
      announce(message);
      
    } catch (e) {
      logFav.error("Ошибка при переключении избранного:", e);
      announce("Ошибка при обновлении избранного");
      btn.classList.toggle("is-active", wasActive);
      btn.setAttribute("aria-pressed", wasActive ? "true" : "false");
    }
  };

  const removeFavorite = async (id) => {
    logFav.log('Удаление товара', id, 'из избранного');
    
    if (!id) return;

    if (!isAuthorized()) {
      logFav.log('Пользователь не авторизован');
      return;
    }

    try {
      const res = await fetch(toRootPath("/php/favorites.php"), {
        method: "POST",
        headers: { 
          "Content-Type": "application/json",
          'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: "same-origin",
        body: JSON.stringify({ 
          action: "remove", 
          product_id: id 
        })
      });

      const data = await res.json();
      logFav.log('Ответ удаления:', data);
      
      await loadFavorites();
      updateFavoritesBadge();
      updateFavoriteButtons();
      renderFavoritesSheet();
      
      announce("Удалено из избранного");
    } catch (e) {
      logFav.error("Ошибка удаления из избранного:", e);
      announce("Ошибка при удалении из избранного");
    }
  };

  const clearFavorites = async () => {
    logFav.log('Очистка избранного');
    
    if (!confirm("Очистить избранное?")) return;

    if (!isAuthorized()) {
      logFav.log('Пользователь не авторизован');
      return;
    }

    try {
      const res = await fetch(toRootPath("/php/favorites.php"), {
        method: "POST",
        headers: { 
          "Content-Type": "application/json",
          'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: "same-origin",
        body: JSON.stringify({ action: "clear" })
      });

      const data = await res.json();
      logFav.log('Ответ очистки:', data);
      
      await loadFavorites();
      updateFavoritesBadge();
      updateFavoriteButtons();
      renderFavoritesSheet();
      
      announce("Избранное очищено");
    } catch (e) {
      logFav.error("Ошибка очистки избранного:", e);
      announce("Ошибка при очистке избранного");
    }
  };

  const renderFavoritesSheet = () => {
    const content = document.getElementById("favorites-content");
    const actions = document.querySelector(".favorites-actions");
    if (!content) return;

    logFav.log('Рендер боковой панели избранного');
    logFav.log('Авторизован?', isAuthorized());
    logFav.log('Товаров в избранном:', favorites.length);

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
            const imgUrl = toAbsUrl(item.img || "");
            const escapedName = item.name.replace(/"/g, '&quot;');
            
            return `
              <div class="favorites-item" data-favorite-id="${item.id}">
                <div class="favorites-item__image"
                     style="background-image:url('${imgUrl}')"
                     role="img" aria-label="${escapedName}"></div>

                <div class="favorites-item__info">
                  <div class="favorites-item__name">${item.name}</div>
                  <div class="favorites-item__price">${formatMoney(item.price)} ₽</div>
                </div>

                <div class="favorites-item__actions">
                  <button class="btn btn--dark btn--sm"
                          type="button"
                          data-favorite-add-to-cart
                          data-id="${item.id}"
                          aria-label="Добавить ${escapedName} в корзину">В корзину</button>

                  <button class="iconBtn"
                          type="button"
                          data-remove-favorite
                          data-id="${item.id}"
                          aria-label="Удалить ${escapedName} из избранного">✕</button>
                </div>
              </div>
            `;
          })
          .join("")}
      </div>
    `;

    if (actions) actions.style.display = "block";
  };

  // ==================== LAZY BACKGROUNDS ====================
  const initLazyBg = () => {
    const els = $$("[data-bg]");
    if (!els.length) return;

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
      CONFIG.lazyLoad
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

  const openAuthModalWithMessage = (msg) => {
    const modal = document.getElementById("authModal");
    if (!modal) return;

    const body = modal.querySelector(".modal__body") || modal;
    let note = modal.querySelector("[data-auth-required]");

    if (!note) {
      note = document.createElement("div");
      note.setAttribute("data-auth-required", "1");
      note.className = "alert alert--error";
      note.style.marginBottom = "10px";
      body.insertBefore(note, body.firstChild);
    }

    note.textContent = msg || "Сначала войдите в аккаунт.";
    openModal(modal);
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
    logFav.log('Инициализация делегирования событий');
    
    document.addEventListener("click", (e) => {
      const favBtn = e.target.closest("[data-fav-btn]");
      if (favBtn) {
        logFav.log('Нажата кнопка избранного');
        e.preventDefault();
        toggleFavorite(favBtn);
        return;
      }

      if (e.target.closest("#clear-favorites")) {
        logFav.log('Нажата кнопка очистки избранного');
        clearFavorites();
        return;
      }

      const removeBtn = e.target.closest("[data-remove-favorite]");
      if (removeBtn) {
        logFav.log('Нажата кнопка удаления из избранного');
        const id = removeBtn.getAttribute("data-id") || removeBtn.getAttribute("data-product-id");
        if (id) removeFavorite(id);
        return;
      }
    });
  };

  // ==================== HERO SLIDER ====================
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
      this.interval = CONFIG.heroSlider.interval;

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

          if (Math.abs(diff) > CONFIG.heroSlider.swipeThreshold) {
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

  // ==================== CATALOG FILTERS/SEARCH ====================
  const initCatalogFilters = () => {
    const filtersWrap = document.getElementById("categoryFilters");
    const searchInput = document.getElementById("searchInput");
    const searchClear = document.querySelector("[data-search-clear]");
    const clearFiltersBtn = document.getElementById("clear-filters");

    if (!filtersWrap && !searchInput) return;

    let activeFilter = "all";
    let searchTerm = "";
    let searchTimeout = null;

    const debouncedSearch = debounce(() => {
      searchTerm = searchInput.value.trim().toLowerCase();
      applyFilters();
    }, 250);

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

  // ==================== GIFT BUILDER ====================
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

    const MIN = CONFIG.giftBuilder.minItems;
    const MAX = CONFIG.giftBuilder.maxItems;

    const getLabel = (box) => {
      const v = (box.value || "").trim();
      if (v) return v;

      const txt =
        box.closest("label")?.textContent?.replace(/\s+/g, " ")?.trim() ||
        "Товар";
      return txt;
    };

    const escapeCss = (s) => {
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
          const escapedText = text.replace(/"/g, '&quot;');
          return `
            <span class="giftTag">
              ${text}
              <button type="button" data-remove="${key}" aria-label="Убрать ${escapedText}">×</button>
            </span>
          `;
        })
        .join("");
    };

    const update = () => {
      const checked = boxes.filter((b) => b.checked).length;

      if (counter) counter.textContent = String(checked);

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
  const init = async () => {
    logApp.log('DOM загружен');
    
    initCloseButtonsCapture();

    initLazyBg();
    initBurgerMenu();
    initMegaDropdown();
    initModals();
    initSheets();

    logApp.log('Проверка авторизации...');
    if (isAuthorized()) {
      logApp.log('Пользователь авторизован, загружаем избранное');
      await loadFavorites();
    } else {
      logApp.log('Пользователь не авторизован');
      favorites = [];
    }

    logApp.log('Обновление UI...');
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
    
    logApp.log('Инициализация завершена');
  };

  // Инициализация при разных состояниях DOM
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    // DOM уже загружен
    setTimeout(init, 0);
  }

  // Экспорт функций для глобального использования
  window.removeFavorite = removeFavorite;
  window.clearFavorites = clearFavorites;
  window.updateFavorites = async () => {
    await loadFavorites();
    updateFavoritesBadge();
    updateFavoriteButtons();
    renderFavoritesSheet();
  };
})();