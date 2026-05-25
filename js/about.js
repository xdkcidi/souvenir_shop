(() => {
  "use strict";

  if (window.__LAVKA_ABOUT_INITED__) return;
  window.__LAVKA_ABOUT_INITED__ = true;

  const core = () => window.Lavka || {};
  const $$ = (selector, root = document) =>
    (core().$$ || ((s, el = document) => Array.from(el.querySelectorAll(s))))(
      selector,
      root,
    );
  const onReady =
    core().onReady ||
    ((callback) => {
      if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", callback, { once: true });
      } else {
        callback();
      }
    });

  const animateCounterFallback = (el, target, duration = 1500) => {
    const start = 0;
    const startedAt = performance.now();

    const tick = (now) => {
      const progress = Math.min((now - startedAt) / duration, 1);
      const value = Math.floor(start + (target - start) * progress);

      el.textContent = `${value}+`;

      if (progress < 1) {
        requestAnimationFrame(tick);
      }
    };

    requestAnimationFrame(tick);
  };

  const initAboutStatsCounter = () => {
    const companyEl = document.getElementById("company");
    if (!companyEl || !("IntersectionObserver" in window)) return;

    const animate =
      typeof window.animateCounter === "function"
        ? window.animateCounter
        : animateCounterFallback;

    const io = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting) return;

          $$(".aboutStat__n").forEach((stat) => {
            const value = (stat.textContent || "").trim();
            if (!value.includes("+")) return;

            const number = parseInt(value, 10);
            if (Number.isFinite(number)) animate(stat, number, 1500);
          });

          io.unobserve(entry.target);
        });
      },
      {
        threshold: 0.5,
      },
    );

    io.observe(companyEl);
  };

  const initMaterialsReveal = () => {
    const cards = $$("#materials .mat");
    if (!cards.length || !("IntersectionObserver" in window)) return;

    cards.forEach((card, index) => {
      card.style.transitionDelay = `${index * 80}ms`;
    });

    const io = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting) return;

          entry.target.classList.add("is-in");
          io.unobserve(entry.target);
        });
      },
      {
        threshold: 0.25,
      },
    );

    cards.forEach((card) => io.observe(card));
  };

  onReady(() => {
    initAboutStatsCounter();
    initMaterialsReveal();
  });
})();
