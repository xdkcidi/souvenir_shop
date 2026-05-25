(() => {
  "use strict";

  document.addEventListener("click", (event) => {
    const card = event.target.closest("[data-product]");
    if (!card) return;

    if (
      event.target.closest(
        "a, button, [data-add-to-cart], [data-fav-btn], [data-qty-minus], [data-qty-plus], [data-qty-wrap]",
      )
    ) {
      return;
    }

    const productId = card.dataset.id;
    if (!productId) return;

    const isInPages = /\/pages\/[^/]+$/.test(window.location.pathname);
    const url = isInPages
      ? `product.php?id=${encodeURIComponent(productId)}`
      : `pages/product.php?id=${encodeURIComponent(productId)}`;

    window.location.href = url;
  });
})();
