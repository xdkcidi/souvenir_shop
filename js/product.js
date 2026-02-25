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
  const mainImg = document.querySelector('#mainImage[data-zoomable]');
  const modal = document.getElementById('imgModal');
  const modalImg = document.getElementById('imgModalImg');

  if (!mainImg || !modal || !modalImg) return;

  let scale = 1;
  let tx = 0, ty = 0;
  let isDrag = false;
  let startX = 0, startY = 0;

  const applyTransform = () => {
    modalImg.style.transform = `translate(${tx}px, ${ty}px) scale(${scale})`;
  };

  const open = () => {
    modalImg.src = mainImg.src;
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

  mainImg.addEventListener('click', open);

  modal.addEventListener('click', (e) => {
    if (e.target.closest('[data-close]')) close();
  });

  document.addEventListener('keydown', (e) => {
    if (modal.getAttribute('aria-hidden') === 'false' && e.key === 'Escape') close();
  });

  const zoom = (delta) => {
    const next = Math.min(5, Math.max(1, scale + delta));
    scale = next;
    applyTransform();
  };

  modal.querySelector('[data-zoom-in]').addEventListener('click', () => zoom(0.25));
  modal.querySelector('[data-zoom-out]').addEventListener('click', () => zoom(-0.25));
  modal.querySelector('[data-zoom-reset]').addEventListener('click', () => {
    scale = 1; tx = 0; ty = 0;
    applyTransform();
  });

  // wheel zoom
  modal.addEventListener('wheel', (e) => {
    if (modal.getAttribute('aria-hidden') !== 'false') return;
    e.preventDefault();
    zoom(e.deltaY > 0 ? -0.15 : 0.15);
  }, { passive: false });

  // drag
  modalImg.addEventListener('mousedown', (e) => {
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

  // touch (mobile)
  modalImg.addEventListener('touchstart', (e) => {
    if (e.touches.length !== 1) return;
    isDrag = true;
    const t = e.touches[0];
    startX = t.clientX - tx;
    startY = t.clientY - ty;
  }, { passive: true });

  modalImg.addEventListener('touchmove', (e) => {
    if (!isDrag || e.touches.length !== 1) return;
    const t = e.touches[0];
    tx = t.clientX - startX;
    ty = t.clientY - startY;
    applyTransform();
  }, { passive: true });

  modalImg.addEventListener('touchend', () => { isDrag = false; });
});