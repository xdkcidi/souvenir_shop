(() => {
  "use strict";

  if (window.__LAVKA_HOME_INITED__) return;
  window.__LAVKA_HOME_INITED__ = true;

  const onReady =
    window.Lavka?.onReady ||
    ((callback) => {
      if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", callback, { once: true });
      } else {
        callback();
      }
    });

  // Инициализация изображений для слайдов
  function initHeroBackgrounds() {
    const bgElements = document.querySelectorAll(".hero__bg[data-hero-bg]");

    bgElements.forEach((el) => {
      const bgUrl = el.getAttribute("data-hero-bg");
      if (bgUrl && !el.style.backgroundImage) {
        const img = new Image();
        img.onload = function () {
          el.style.backgroundImage = `url('${bgUrl}')`;
          el.style.backgroundSize = "cover";
          el.style.backgroundPosition = "center";
          el.style.backgroundRepeat = "no-repeat";
          console.log("Background loaded:", bgUrl);
        };
        img.onerror = function () {
          console.error("Failed to load background:", bgUrl);
          el.style.backgroundColor = "#1a1a1a";
        };
        img.src = bgUrl;
      }
    });
  }

  function setHeroBackgroundsViaCSS() {
    const slides = document.querySelectorAll(".hero__slide");

    slides.forEach((slide, index) => {
      const bgDiv = slide.querySelector(".hero__bg");
      if (bgDiv && bgDiv.hasAttribute("data-hero-bg")) {
        const bgUrl = bgDiv.getAttribute("data-hero-bg");
        bgDiv.setAttribute(
          "style",
          `background-image: url('${bgUrl}'); background-size: cover; background-position: center; background-repeat: no-repeat;`,
        );
      }
    });
  }

  // Предзагрузка изображений слайдов
  function preloadSlideImages() {
    const bgElements = document.querySelectorAll(".hero__bg[data-hero-bg]");
    bgElements.forEach((el) => {
      const bgUrl = el.getAttribute("data-hero-bg");
      if (bgUrl) {
        const link = document.createElement("link");
        link.rel = "preload";
        link.as = "image";
        link.href = bgUrl;
        document.head.appendChild(link);
      }
    });
  }

  class HeroSlider {
    constructor() {
      this.wrap = document.getElementById("hero");
      this.slidesWrap = document.getElementById("heroSlides");
      this.dotsWrap = document.getElementById("heroDots");

      if (!this.wrap || !this.slidesWrap || !this.dotsWrap) return;

      this.slides = Array.from(
        this.slidesWrap.querySelectorAll(".hero__slide"),
      );
      if (!this.slides.length) return;

      this.current = this.slides.findIndex((slide) =>
        slide.classList.contains("is-active"),
      );
      if (this.current < 0) this.current = 0;

      this.timer = null;
      this.interval = 6000;
      this.swipeThreshold = 50;
      this.isTransitioning = false;

      // Инициализируем фоны
      initHeroBackgrounds();

      this.buildDots();
      this.bindTapZones();
      this.bindDots();
      this.bindHoverPause();
      this.bindSwipe();
      this.bindKeyboard();

      this.go(this.current, false);
      this.startAuto();
    }

    buildDots() {
      if (!this.dotsWrap) return;
      this.dotsWrap.innerHTML = "";

      this.dots = this.slides.map((_, index) => {
        const dot = document.createElement("button");
        dot.type = "button";
        dot.className = "dot" + (index === this.current ? " is-active" : "");
        dot.setAttribute("aria-label", `Слайд ${index + 1}`);
        dot.setAttribute("data-slide-index", index);
        this.dotsWrap.appendChild(dot);
        return dot;
      });
    }

    bindTapZones() {
      this.slides.forEach((slide) => {
        const prev = slide.querySelector(".hero__tap--prev");
        const next = slide.querySelector(".hero__tap--next");

        prev?.addEventListener("click", (event) => {
          event.preventDefault();
          event.stopPropagation();
          this.prev();
        });

        next?.addEventListener("click", (event) => {
          event.preventDefault();
          event.stopPropagation();
          this.next();
        });
      });
    }

    bindDots() {
      this.dots.forEach((dot, index) => {
        dot.addEventListener("click", () => {
          if (this.isTransitioning) return;
          this.go(index);
        });
      });
    }

    bindHoverPause() {
      this.wrap.addEventListener("mouseenter", () => this.stopAuto());
      this.wrap.addEventListener("mouseleave", () => this.startAuto());
    }

    bindSwipe() {
      let startX = null;
      let startY = null;

      this.slidesWrap.addEventListener(
        "touchstart",
        (event) => {
          startX = event.changedTouches[0].clientX;
          startY = event.changedTouches[0].clientY;
        },
        { passive: true },
      );

      this.slidesWrap.addEventListener(
        "touchend",
        (event) => {
          if (startX === null) return;

          const endX = event.changedTouches[0].clientX;
          const endY = event.changedTouches[0].clientY;
          const diffX = startX - endX;
          const diffY = Math.abs(startY - endY);

          startX = null;

          if (
            Math.abs(diffX) > this.swipeThreshold &&
            Math.abs(diffX) > diffY
          ) {
            if (diffX > 0) this.next();
            else this.prev();
          }
        },
        { passive: true },
      );
    }

    bindKeyboard() {
      document.addEventListener("keydown", (event) => {
        // Только если мышь над слайдером
        if (!this.wrap.matches(":hover")) return;

        if (event.key === "ArrowLeft") {
          event.preventDefault();
          this.prev();
        } else if (event.key === "ArrowRight") {
          event.preventDefault();
          this.next();
        }
      });
    }

    go(index, resetAuto = true) {
      if (this.isTransitioning) return;
      if (!this.slides.length) return;

      if (index < 0) index = this.slides.length - 1;
      if (index >= this.slides.length) index = 0;
      if (index === this.current) return;

      this.isTransitioning = true;

      const oldSlide = this.slides[this.current];
      const newSlide = this.slides[index];

      oldSlide.classList.remove("is-active");
      this.dots[this.current]?.classList.remove("is-active");

      // Активируем новый слайд
      newSlide.classList.add("is-active");
      this.dots[index]?.classList.add("is-active");

      this.current = index;

      if (this.slidesWrap) {
        this.slidesWrap.setAttribute(
          "aria-label",
          `Слайд ${this.current + 1} из ${this.slides.length}`,
        );
      }

      // Снимаем блокировку после анимации
      setTimeout(() => {
        this.isTransitioning = false;
      }, 450);

      if (resetAuto) this.restartAuto();
    }

    next() {
      if (this.isTransitioning) return;
      this.go(this.current + 1);
    }

    prev() {
      if (this.isTransitioning) return;
      this.go(this.current - 1);
    }

    startAuto() {
      this.stopAuto();
      this.timer = setInterval(() => {
        if (!this.isTransitioning) {
          this.next();
        }
      }, this.interval);
    }

    stopAuto() {
      if (this.timer) {
        clearInterval(this.timer);
        this.timer = null;
      }
    }

    restartAuto() {
      this.stopAuto();
      this.startAuto();
    }

    destroy() {
      this.stopAuto();
    }
  }

  // Ленивая загрузка изображений карточек
  function lazyLoadCards() {
    if ("IntersectionObserver" in window) {
      const cardImages = document.querySelectorAll(
        ".card__img img, .tile__img",
      );

      const imageObserver = new IntersectionObserver(
        (entries, observer) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              const img = entry.target;
              if (img.dataset.src && !img.src) {
                img.src = img.dataset.src;
                img.classList.remove("lazy");
              }
              observer.unobserve(img);
            }
          });
        },
        {
          rootMargin: "50px 0px",
          threshold: 0.01,
        },
      );

      cardImages.forEach((img) => {
        if (!img.src && img.dataset.src) {
          imageObserver.observe(img);
        } else if (img.src && img.src !== "") {
        } else if (img.getAttribute("src") && !img.complete) {
          imageObserver.observe(img);
        }
      });
    }
  }

  onReady(() => {
    preloadSlideImages();

    const slider = new HeroSlider();

    lazyLoadCards();

    if (window.Lavka) {
      window.Lavka.heroSlider = slider;
    }
  });
})();
