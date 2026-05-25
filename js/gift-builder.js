document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('giftForm');
  if (!form) return;

  const checkboxes = Array.from(form.querySelectorAll('input[name="giftItems[]"]'));
  const pickedCount = document.getElementById('giftPicked');
  const pickedTags = document.getElementById('giftPickedTags');
  const giftNote = document.getElementById('giftNote');
  const giftTotals = document.getElementById('giftTotals');
  const giftFullSum = document.getElementById('giftFullSum');
  const giftDiscountSum = document.getElementById('giftDiscountSum');
  const clearBtn = document.getElementById('giftClearAll');
  const submitBtn = document.getElementById('giftSubmit');

  const CREATE_GIFT_CHECKOUT_URL = './php/create_gift_checkout.php';

  function formatPrice(value) {
    return new Intl.NumberFormat('ru-RU').format(value) + ' ₽';
  }

  function getSelected() {
    return checkboxes
      .filter(cb => cb.checked)
      .map(cb => ({
        code: cb.dataset.code,
        name: cb.dataset.name,
        price: parseInt(cb.dataset.price || '0', 10)
      }));
  }

  function syncLimitState(selectedCount) {
    const limitReached = selectedCount >= 4;

    checkboxes.forEach(cb => {
      if (!cb.checked) {
        cb.disabled = limitReached;
      }
    });
  }

  function renderTags(selected) {
    if (!pickedTags) return;

    if (!selected.length) {
      pickedTags.innerHTML = '<span class="muted small">Пока ничего не выбрано.</span>';
      return;
    }

    pickedTags.innerHTML = selected.map(item => `
      <span class="giftPicked__tag">
        <span>${item.name}</span>
        <span class="giftPicked__tagPrice">${formatPrice(item.price)}</span>
        <button type="button" class="giftPicked__tagRemove" data-remove-gift="${item.code}" aria-label="Удалить ${item.name}">×</button>
      </span>
    `).join('');
  }

  function updateGiftBlock() {
    const selected = getSelected();
    const count = selected.length;

    if (pickedCount) pickedCount.textContent = count;

    renderTags(selected);
    syncLimitState(count);

    if (count < 2) {
      if (giftNote) giftNote.textContent = 'Выберите минимум 2 позиции';
      if (giftTotals) giftTotals.style.display = 'none';
      if (submitBtn) submitBtn.disabled = true;
      return;
    }

    const fullSum = selected.reduce((sum, item) => sum + item.price, 0);
    const discountSum = Math.round(fullSum * 0.95);

    if (giftNote) giftNote.textContent = 'Скидка на набор 5% и подарочная коробка включена';
    if (giftFullSum) giftFullSum.textContent = formatPrice(fullSum);
    if (giftDiscountSum) giftDiscountSum.textContent = formatPrice(discountSum);
    if (giftTotals) giftTotals.style.display = 'flex';
    if (submitBtn) submitBtn.disabled = false;
  }

  checkboxes.forEach(cb => {
    cb.addEventListener('change', function () {
      const selected = getSelected();

      if (selected.length > 4) {
        this.checked = false;
        return;
      }

      updateGiftBlock();
    });
  });

  if (clearBtn) {
    clearBtn.addEventListener('click', function () {
      checkboxes.forEach(cb => {
        cb.checked = false;
        cb.disabled = false;
      });

      updateGiftBlock();
    });
  }

  if (pickedTags) {
    pickedTags.addEventListener('click', function (e) {
      const btn = e.target.closest('[data-remove-gift]');
      if (!btn) return;

      const code = btn.getAttribute('data-remove-gift');
      const checkbox = checkboxes.find(cb => cb.dataset.code === code);

      if (checkbox) {
        checkbox.checked = false;
        checkbox.disabled = false;
        updateGiftBlock();
      }
    });
  }

  if (submitBtn) {
    submitBtn.addEventListener('click', async function () {
      const selected = getSelected();

      if (selected.length < 2 || selected.length > 4) {
        updateGiftBlock();
        return;
      }

      if (document.documentElement.dataset.auth !== '1') {
        if (typeof window.openAuthModalWithMessage === 'function') {
          window.openAuthModalWithMessage('Чтобы оформить подарочный набор, сначала войдите в аккаунт.');
        } else {
          const authBtn = document.querySelector('[data-open-modal="authModal"]');
          if (authBtn) authBtn.click();
        }

        return;
      }

      submitBtn.disabled = true;
      const oldText = submitBtn.textContent;
      submitBtn.textContent = 'Формируем заказ...';

      try {
        const res = await fetch(CREATE_GIFT_CHECKOUT_URL, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          credentials: 'same-origin',
          body: JSON.stringify({
            items: selected.map(item => item.code)
          })
        });

        const json = await res.json().catch(() => ({}));

        if (!res.ok || !json.success) {
          throw new Error(json.error || 'CREATE_GIFT_CHECKOUT_FAILED');
        }

        window.location.href = json.redirect || './pages/checkout.php?mode=gift';
      } catch (error) {
        console.error(error);
        alert('Не удалось оформить подарочный набор. Попробуйте ещё раз.');
        submitBtn.disabled = false;
        submitBtn.textContent = oldText;
      }
    });
  }

  updateGiftBlock();
});