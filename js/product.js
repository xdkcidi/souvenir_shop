document.addEventListener("DOMContentLoaded", function () {
  const productCards = document.querySelectorAll("[data-product]");

  productCards.forEach((card) => {
    card.addEventListener("click", function (event) {
      if (
        event.target.closest("button") ||
        event.target.closest("[data-add-to-cart]") ||
        event.target.closest("[data-fav-btn]") ||
        event.target.closest("[data-qty-minus]") ||
        event.target.closest("[data-qty-plus]") ||
        event.target.closest("[data-qty-wrap]")
      ) {
        return;
      }

      const productId = this.dataset.id;
      if (!productId) return;

      const path = window.location.pathname;
      const isInPages = /\/pages\/[^/]+$/.test(path);

      const productUrl = isInPages
        ? `product.php?id=${encodeURIComponent(productId)}`
        : `pages/product.php?id=${encodeURIComponent(productId)}`;

      window.location.href = productUrl;
    });
  });
});

document.addEventListener("DOMContentLoaded", () => {
  const modal = document.getElementById("imgModal");
  const modalImg = document.getElementById("imgModalImg");
  const mainImg = document.querySelector("#mainImage[data-zoomable]");

  if (!mainImg || !modal || !modalImg) return;

  let scale = 1;
  let tx = 0,
    ty = 0;
  let isDrag = false;
  let startX = 0,
    startY = 0;

  let pinchStartDist = 0;
  let pinchStartScale = 1;

  const clamp = (v, min, max) => Math.max(min, Math.min(max, v));

  const applyTransform = () => {
    modalImg.style.transform = `translate(${tx}px, ${ty}px) scale(${scale})`;
  };

  const setScale = (next) => {
    scale = clamp(next, 1, 5);
    if (scale === 1) {
      tx = 0;
      ty = 0;
    }
    applyTransform();
  };

  const open = () => {
    const src = mainImg.currentSrc || mainImg.src;
    modalImg.src = src;
    modalImg.alt = mainImg.alt || "Изображение товара";

    scale = 1;
    tx = 0;
    ty = 0;
    applyTransform();

    modal.setAttribute("aria-hidden", "false");
    document.body.style.overflow = "hidden";
  };

  const close = () => {
    modal.setAttribute("aria-hidden", "true");
    document.body.style.overflow = "";
  };

  mainImg.addEventListener(
    "click",
    (e) => {
      e.preventDefault();
      e.stopPropagation();
      open();
    },
    true,
  );

  modal.addEventListener("click", (e) => {
    if (e.target.closest("[data-close]")) {
      close();
      return;
    }

    if (e.target.closest(".imgModal__toolbar")) return;
    if (e.target.closest("#imgModalImg")) return;

    close();
  });

  document.addEventListener("keydown", (e) => {
    if (modal.getAttribute("aria-hidden") === "false" && e.key === "Escape") {
      close();
    }
  });

  const btnIn = modal.querySelector("[data-zoom-in]");
  const btnOut = modal.querySelector("[data-zoom-out]");
  const btnReset = modal.querySelector("[data-zoom-reset]");

  if (btnIn) btnIn.addEventListener("click", () => setScale(scale + 0.25));
  if (btnOut) btnOut.addEventListener("click", () => setScale(scale - 0.25));
  if (btnReset) {
    btnReset.addEventListener("click", () => {
      scale = 1;
      tx = 0;
      ty = 0;
      applyTransform();
    });
  }

  modal.addEventListener(
    "wheel",
    (e) => {
      if (modal.getAttribute("aria-hidden") !== "false") return;
      e.preventDefault();
      const delta = e.deltaY > 0 ? -0.15 : 0.15;
      setScale(scale + delta);
    },
    { passive: false },
  );

  modalImg.addEventListener("mousedown", (e) => {
    if (modal.getAttribute("aria-hidden") !== "false") return;
    if (scale <= 1) return;

    isDrag = true;
    startX = e.clientX - tx;
    startY = e.clientY - ty;
  });

  window.addEventListener("mousemove", (e) => {
    if (!isDrag) return;
    tx = e.clientX - startX;
    ty = e.clientY - startY;
    applyTransform();
  });

  window.addEventListener("mouseup", () => {
    isDrag = false;
  });

  modalImg.addEventListener(
    "touchstart",
    (e) => {
      if (modal.getAttribute("aria-hidden") !== "false") return;

      if (e.touches.length === 1) {
        if (scale <= 1) return;
        isDrag = true;
        const t = e.touches[0];
        startX = t.clientX - tx;
        startY = t.clientY - ty;
      }

      if (e.touches.length === 2) {
        isDrag = false;
        const a = e.touches[0];
        const b = e.touches[1];
        pinchStartDist = Math.hypot(
          a.clientX - b.clientX,
          a.clientY - b.clientY,
        );
        pinchStartScale = scale;
      }
    },
    { passive: true },
  );

  modalImg.addEventListener(
    "touchmove",
    (e) => {
      if (modal.getAttribute("aria-hidden") !== "false") return;

      if (e.touches.length === 1 && isDrag) {
        const t = e.touches[0];
        tx = t.clientX - startX;
        ty = t.clientY - startY;
        applyTransform();
      }

      if (e.touches.length === 2) {
        const a = e.touches[0];
        const b = e.touches[1];
        const dist = Math.hypot(a.clientX - b.clientX, a.clientY - b.clientY);

        if (pinchStartDist > 0) {
          const next = pinchStartScale * (dist / pinchStartDist);
          setScale(next);
        }
      }
    },
    { passive: true },
  );

  modalImg.addEventListener("touchend", () => {
    isDrag = false;
    pinchStartDist = 0;
  });
});

document.addEventListener(
  "click",
  function (e) {
    const btn = e.target.closest("button[data-thumb]");
    if (!btn) return;

    e.preventDefault();
    e.stopPropagation();

    const src = btn.getAttribute("data-src");
    if (!src) return;

    const mainImg = document.getElementById("mainImage");
    if (!mainImg) return;

    mainImg.setAttribute("src", src);

    const thumbsWrap = btn.closest(".pMedia__thumbs");
    if (thumbsWrap) {
      thumbsWrap.querySelectorAll(".pThumb.is-active").forEach((el) => {
        el.classList.remove("is-active");
      });
    }

    btn.classList.add("is-active");
  },
  true,
);
