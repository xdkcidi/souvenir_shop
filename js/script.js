(() => {
  "use strict";

  if (window.__LAVKA_CORE_INITED__) return;
  window.__LAVKA_CORE_INITED__ = true;

  const CONFIG = {
    lazyLoad: {
      rootMargin: "120px 0px",
      threshold: 0.1
    },
    reveal: {
      threshold: 0.12,
      rootMargin: "80px"
    }
  };

  const $ = (selector, root = document) => root.querySelector(selector);
  const $$ = (selector, root = document) => Array.from(root.querySelectorAll(selector));

  const onReady = (callback) => {
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", callback, { once: true });
    } else {
      callback();
    }
  };

  const debounce = (fn, wait = 250) => {
    let timer;

    return (...args) => {
      clearTimeout(timer);
      timer = setTimeout(() => fn.apply(null, args), wait);
    };
  };

  const escapeHtml = (value) =>
    String(value ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");

  const formatMoney = (value) => Number(value || 0).toLocaleString("ru-RU");

  const lockScroll = (lock) => {
    document.documentElement.style.overflow = lock ? "hidden" : "";
  };

  const isAuthorized = () => document.documentElement.dataset.auth === "1";

  const APP_ROOT = (() => {
    try {
      const current = document.currentScript;
      if (current?.src) {
        const url = new URL(current.src, document.baseURI);
        return url.pathname.replace(/\/js\/[^/]+$/, "/");
      }
    } catch (error) {
      console.warn("Не удалось определить корень сайта:", error);
    }

    const path = window.location.pathname || "/";
    if (path.includes("/pages/")) return path.split("/pages/")[0] + "/";
    return "/";
  })();

  const toRootPath = (path) => {
    if (!path) return "";
    if (/^(https?:)?\/\//i.test(path)) return path;
    if (/^data:/i.test(path)) return path;

    let clean = String(path).trim().replace(/^\.\//, "");
    while (clean.startsWith("../")) clean = clean.slice(3);

    if (window.location.protocol === "file:") return clean;

    const appRootNoSlash = APP_ROOT.replace(/\/+$/, "");

    if (clean.startsWith(appRootNoSlash + "/")) {
      return clean;
    }

    if (clean.startsWith("/")) {
      clean = clean.slice(1);
    }

    return APP_ROOT + clean;
  };

  const toAbsUrl = (path) => {
    try {
      return new URL(toRootPath(path), document.baseURI).href;
    } catch {
      return toRootPath(path);
    }
  };

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

  const announce = (message) => {
    const el = getAnnouncer();
    el.textContent = message;
    setTimeout(() => {
      el.textContent = "";
    }, 1200);
  };

  const openModal = (modal) => {
    if (!modal) return;

    modal.classList.add("is-open");
    modal.setAttribute("aria-hidden", "false");
    modal.setAttribute("aria-modal", "true");
    lockScroll(true);

    const focusable = modal.querySelector(
      "input, button, a, textarea, select, [tabindex]:not([tabindex='-1'])"
    );

    focusable?.focus?.();
  };

  const closeModal = (modal) => {
    if (!modal) return;

    modal.classList.remove("is-open");
    modal.setAttribute("aria-hidden", "true");
    modal.setAttribute("aria-modal", "false");
    lockScroll(false);
  };

  const openSheet = (sheet) => {
    if (!sheet) return;

    sheet.classList.add("is-open");
    sheet.setAttribute("aria-hidden", "false");
    sheet.setAttribute("aria-modal", "true");
    lockScroll(true);

    if (sheet.id === "favoritesSheet" && typeof window.renderFavoritesSheet === "function") {
      window.renderFavoritesSheet();
    }
  };

  const closeSheet = (sheet) => {
    if (!sheet) return;

    sheet.classList.remove("is-open");
    sheet.setAttribute("aria-hidden", "true");
    sheet.setAttribute("aria-modal", "false");
    lockScroll(false);
  };

  const initLazyBackgrounds = () => {
    const elements = $$("[data-bg]");
    if (!elements.length) return;

    const applyBackground = (el) => {
      const url = el.getAttribute("data-bg");
      if (!url) return;

      if (el.style.backgroundImage && el.style.backgroundImage !== "none") {
        return;
      }

      el.style.backgroundImage = `url('${url}')`;
    };

    if (!("IntersectionObserver" in window)) {
      elements.forEach(applyBackground);
      return;
    }

    const io = new IntersectionObserver((entries, observer) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;

        const el = entry.target;
        const url = el.getAttribute("data-bg");

        if (!url) {
          observer.unobserve(el);
          return;
        }

        if (el.style.backgroundImage && el.style.backgroundImage !== "none") {
          observer.unobserve(el);
          return;
        }

        const img = new Image();

        el.classList.add("loading");

        img.onload = () => {
          el.style.backgroundImage = `url('${url}')`;
          el.classList.remove("loading");
        };

        img.onerror = () => {
          el.classList.remove("loading");
          console.warn("Не удалось загрузить фон:", url);
        };

        img.src = url;
        observer.unobserve(el);
      });
    }, CONFIG.lazyLoad);

    elements.forEach((el) => io.observe(el));
  };

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

    document.addEventListener("click", (event) => {
      if (!menu.classList.contains("is-open")) return;
      if (menu.contains(event.target) || burger.contains(event.target)) return;

      close();
    });

    menu.addEventListener("click", (event) => {
      const link = event.target.closest("a");
      if (!link) return;

      if (window.matchMedia("(max-width: 820px)").matches) {
        close();
      }
    });

    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape" && menu.classList.contains("is-open")) {
        close();
      }
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

    btn.addEventListener("click", (event) => {
      event.preventDefault();

      const isOpen = mega.classList.toggle("is-open");
      btn.setAttribute("aria-expanded", String(isOpen));
    });

    document.addEventListener("click", (event) => {
      if (!mega.classList.contains("is-open")) return;
      if (!wrap.contains(event.target)) close();
    });

    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape" && mega.classList.contains("is-open")) {
        close();
      }
    });

    document.addEventListener("click", (event) => {
      if (event.target.closest("[data-close-mega]")) {
        close();
      }
    });
  };

  const initModals = () => {
    document.addEventListener("click", (event) => {
      const openBtn = event.target.closest("[data-open-modal]");
      if (!openBtn) return;

      const id = openBtn.getAttribute("data-open-modal");
      const modal = id ? document.getElementById(id) : null;

      openModal(modal);
    });

    document.addEventListener("click", (event) => {
      if (!event.target.classList.contains("modal__backdrop")) return;

      closeModal(event.target.closest(".modal"));
    });

    document.addEventListener("keydown", (event) => {
      if (event.key !== "Escape") return;

      const opened = $(".modal.is-open");
      if (opened) closeModal(opened);
    });
  };

  const initSheets = () => {
    document.addEventListener("click", (event) => {
      const openBtn = event.target.closest("[data-open-sheet]");
      if (!openBtn) return;

      const id = openBtn.getAttribute("data-open-sheet");
      const sheet = id ? document.getElementById(id) : null;

      openSheet(sheet);
    });

    document.addEventListener("click", (event) => {
      if (!event.target.classList.contains("sheet__backdrop")) return;

      closeSheet(event.target.closest(".sheet"));
    });

    document.addEventListener("keydown", (event) => {
      if (event.key !== "Escape") return;

      const opened = $(".sheet.is-open");
      if (opened) closeSheet(opened);
    });
  };

  const initCloseButtons = () => {
    document.addEventListener(
      "click",
      (event) => {
        const closeBtn = event.target.closest(
          "[data-close], [data-close-modal], [data-close-sheet]"
        );

        if (!closeBtn) return;

        const modal = closeBtn.closest(".modal.is-open");
        if (modal) {
          event.preventDefault();
          closeModal(modal);
          return;
        }

        const sheet = closeBtn.closest(".sheet.is-open");
        if (sheet) {
          event.preventDefault();
          closeSheet(sheet);
        }
      },
      true
    );
  };

  const initReveal = () => {
    const items = $$(".reveal");
    if (!items.length) return;

    if (!("IntersectionObserver" in window)) {
      items.forEach((el) => el.classList.add("in"));
      return;
    }

    const io = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;

        entry.target.classList.add("in");
        io.unobserve(entry.target);
      });
    }, CONFIG.reveal);

    items.forEach((el) => io.observe(el));
  };

  window.openAuthModalWithMessage = (message) => {
    const note = document.getElementById("authNote");

    if (note) {
      const text = String(message || "").trim();
      note.textContent = text;
      note.hidden = !text;
    }

    const opener = document.querySelector('[data-open-modal="authModal"]');

    if (opener) {
      opener.click();
      return;
    }

    const modal = document.getElementById("authModal");
    if (modal) openModal(modal);
  };

  window.Lavka = Object.assign(window.Lavka || {}, {
    $,
    $$,
    onReady,
    debounce,
    escapeHtml,
    formatMoney,
    lockScroll,
    isAuthorized,
    toRootPath,
    toAbsUrl,
    announce,
    openModal,
    closeModal,
    openSheet,
    closeSheet,
    refreshLazyBackgrounds: initLazyBackgrounds,
    refreshReveal: initReveal
  });

  onReady(() => {
    initCloseButtons();
    initLazyBackgrounds();
    initBurgerMenu();
    initMegaDropdown();
    initModals();
    initSheets();
    initReveal();
  });
})();
