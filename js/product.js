  document.addEventListener('DOMContentLoaded', function() {
    const productCards = document.querySelectorAll('[data-product]');

    productCards.forEach(card => {
      card.addEventListener('click', function(event) {
        if (event.target.closest('button')) {
          return;
        }

        const productId = this.dataset.id;
        // ВАЖНО: указываем полный путь с папкой souvenir_shop
        window.location.href = `/souvenir_shop/pages/product.php?id=${productId}`;
        // Или относительный путь:
        // window.location.href = `pages/product.php?id=${productId}`;
      });
    });
  });


document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('imgModal');
  const modalImg = document.getElementById('imgModalImg');
  const mainImg = document.querySelector('#mainImage[data-zoomable]');

  if (!mainImg || !modal || !modalImg) return;

  let scale = 1;
  let tx = 0, ty = 0;
  let isDrag = false;
  let startX = 0, startY = 0;

  // pinch
  let pinchStartDist = 0;
  let pinchStartScale = 1;

  const clamp = (v, min, max) => Math.max(min, Math.min(max, v));

  const applyTransform = () => {
    modalImg.style.transform = `translate(${tx}px, ${ty}px) scale(${scale})`;
  };

  const setScale = (next) => {
    scale = clamp(next, 1, 5);
    if (scale === 1) { tx = 0; ty = 0; } // сброс позиционирования, если вернулись к 1
    applyTransform();
  };

  const open = () => {
    const src = mainImg.currentSrc || mainImg.src;
    modalImg.src = src;
    modalImg.alt = mainImg.alt || 'Изображение товара';

    scale = 1; tx = 0; ty = 0;
    applyTransform();

    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  };

  const close = () => {
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  };

  // IMPORTANT: capture, чтобы чужие обработчики не мешали открытию
  mainImg.addEventListener('click', (e) => {
    e.preventDefault();
    e.stopPropagation();
    open();
  }, true);

modal.addEventListener('click', (e) => {
  // Крестик/бекдроп (или любой элемент с data-close) — закрываем всегда
  if (e.target.closest('[data-close]')) {
    close();
    return;
  }

  // Клик по панели кнопок — не закрываем
  if (e.target.closest('.imgModal__toolbar')) return;

  // Клик по самой картинке — не закрываем
  if (e.target.closest('#imgModalImg')) return;

  // Остальная область (пустое место вокруг) — закрываем
  close();
});

  document.addEventListener('keydown', (e) => {
    if (modal.getAttribute('aria-hidden') === 'false' && e.key === 'Escape') close();
  });

  // buttons (если есть)
  const btnIn = modal.querySelector('[data-zoom-in]');
  const btnOut = modal.querySelector('[data-zoom-out]');
  const btnReset = modal.querySelector('[data-zoom-reset]');

  if (btnIn) btnIn.addEventListener('click', () => setScale(scale + 0.25));
  if (btnOut) btnOut.addEventListener('click', () => setScale(scale - 0.25));
  if (btnReset) btnReset.addEventListener('click', () => { scale = 1; tx = 0; ty = 0; applyTransform(); });

  // wheel zoom
  modal.addEventListener('wheel', (e) => {
    if (modal.getAttribute('aria-hidden') !== 'false') return;
    e.preventDefault();
    const delta = e.deltaY > 0 ? -0.15 : 0.15;
    setScale(scale + delta);
  }, { passive: false });

  // drag (only when zoomed)
  modalImg.addEventListener('mousedown', (e) => {
    if (modal.getAttribute('aria-hidden') !== 'false') return;
    if (scale <= 1) return;
    isDrag = true;
    startX = e.clientX - tx;
    startY = e.clientY - ty;
  });

  window.addEventListener('mousemove', (e) => {
    if (!isDrag) return;
    tx = e.clientX - startX;
    ty = e.clientY - startY;
    applyTransform();
  });

  window.addEventListener('mouseup', () => { isDrag = false; });

  // touch: drag 1 finger, pinch 2 fingers
  modalImg.addEventListener('touchstart', (e) => {
    if (modal.getAttribute('aria-hidden') !== 'false') return;

    if (e.touches.length === 1) {
      if (scale <= 1) return;
      isDrag = true;
      const t = e.touches[0];
      startX = t.clientX - tx;
      startY = t.clientY - ty;
    }

    if (e.touches.length === 2) {
      isDrag = false;
      const a = e.touches[0], b = e.touches[1];
      pinchStartDist = Math.hypot(a.clientX - b.clientX, a.clientY - b.clientY);
      pinchStartScale = scale;
    }
  }, { passive: true });

  modalImg.addEventListener('touchmove', (e) => {
    if (modal.getAttribute('aria-hidden') !== 'false') return;

    if (e.touches.length === 1 && isDrag) {
      const t = e.touches[0];
      tx = t.clientX - startX;
      ty = t.clientY - startY;
      applyTransform();
    }

    if (e.touches.length === 2) {
      const a = e.touches[0], b = e.touches[1];
      const dist = Math.hypot(a.clientX - b.clientX, a.clientY - b.clientY);
      if (pinchStartDist > 0) {
        const next = pinchStartScale * (dist / pinchStartDist);
        setScale(next);
      }
    }
  }, { passive: true });

  modalImg.addEventListener('touchend', () => {
    isDrag = false;
    pinchStartDist = 0;
  });
});


// thumbs switch (CAPTURE) — сработает даже если кто-то стопает bubbling
document.addEventListener('click', function (e) {
  const btn = e.target.closest('button[data-thumb]');
  if (!btn) return;

  // важно: перехватываем и не даём другим обработчикам мешать
  e.preventDefault();
  e.stopPropagation();

  const src = btn.getAttribute('data-src');
  if (!src) return;

  const mainImg = document.getElementById('mainImage');
  if (!mainImg) return;

  // меняем картинку
  mainImg.setAttribute('src', src);

  // активная рамка
  const thumbsWrap = btn.closest('.pMedia__thumbs');
  if (thumbsWrap) {
    thumbsWrap.querySelectorAll('.pThumb.is-active').forEach(el => el.classList.remove('is-active'));
  }
  btn.classList.add('is-active');
}, true);