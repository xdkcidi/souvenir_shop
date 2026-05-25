(() => {
  "use strict";

  if (window.__LAVKA_CATALOG_FILTERS_INITED__) return;
  window.__LAVKA_CATALOG_FILTERS_INITED__ = true;

  const core = () => window.Lavka || {};
  const $ = (selector, root = document) =>
    (core().$ || ((s, el = document) => el.querySelector(s)))(selector, root);
  const $$ = (selector, root = document) =>
    (core().$$ || ((s, el = document) => Array.from(el.querySelectorAll(s))))(selector, root);
  const debounce =
    core().debounce ||
    ((fn, wait = 250) => {
      let timer;
      return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn.apply(null, args), wait);
      };
    });
  const onReady =
    core().onReady ||
    ((callback) => {
      if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", callback, { once: true });
      } else {
        callback();
      }
    });

  const initCatalogFilters = () => {
    const filtersWrap = document.getElementById("categoryFilters");
    const searchInput = document.getElementById("searchInput");
    const searchClear = document.querySelector("[data-search-clear]");
    const clearFiltersBtn = document.getElementById("clear-filters");

    if (!filtersWrap && !searchInput) return;

    let activeFilter = "all";
    let searchTerm = "";

    const loadFilterableProducts = () => {
      const sections = Array.from(document.querySelectorAll("[data-group]"));
      const list = [];

      sections.forEach((section) => {
        section.querySelectorAll("[data-product]").forEach((card) => {
          list.push({
            element: card,
            name: (card.getAttribute("data-name") || "").toLowerCase(),
            category: card.getAttribute("data-category") || "unknown",
            group: card.closest("[data-group]")
          });
        });
      });

      return list;
    };

    const filterableProducts = loadFilterableProducts();

    const applyFilters = () => {
      const hasFilter = activeFilter !== "all" || Boolean(searchTerm);

      filterableProducts.forEach(({ element, name, category }) => {
        const matchCategory = activeFilter === "all" || category === activeFilter;
        const matchSearch = !searchTerm || name.includes(searchTerm);
        const isVisible = matchCategory && matchSearch;

        element.style.display = isVisible ? "" : "none";
        element.setAttribute("aria-hidden", String(!isVisible));
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
      anchor?.scrollIntoView?.({
        behavior: "smooth",
        block: "start"
      });
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
      filtersWrap.addEventListener("click", (event) => {
        const filterBtn = event.target.closest("[data-filter]");
        if (!filterBtn) return;

        event.preventDefault();
        setActiveFilter(filterBtn);
        applyFilters();
      });
    }

    if (searchInput) {
      searchInput.addEventListener(
        "input",
        debounce(() => {
          searchTerm = searchInput.value.trim().toLowerCase();
          applyFilters();
        }, 250)
      );
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

  onReady(initCatalogFilters);
})();
