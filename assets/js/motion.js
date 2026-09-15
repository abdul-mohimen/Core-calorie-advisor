// assets/js/motion.js — Core Calorie Advisor Vanilla Motion Engine (MR-2)
(function () {
  'use strict';
  const REDUCED = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ---------- 1. Scroll-reveal (IntersectionObserver, zero dependencies) ---------- */
  function initReveal() {
    const targets = document.querySelectorAll(
      '.cca-card, .portal-card, .plan-card, .checkout-card, .wk-card, .cat-card, ' +
      '.cca-hero__title, section > h2, .cca-hero__glass, .cca-metric, .workout-studio-card, .hero-wk-card, .portal-dashboard-hub__card'
    );
    if (!targets.length) return;

    if (REDUCED || !('IntersectionObserver' in window)) {
      targets.forEach(el => el.classList.add('is-visible'));
      return;
    }

    // Stagger: index within each parent container, capped at 4 so vertical lists remain snappy
    const groups = new Map();
    targets.forEach(el => {
      const key = el.parentElement || document.body;
      const count = groups.get(key) || 0;
      const i = Math.min(count, 4);
      el.style.setProperty('--reveal-i', i);
      groups.set(key, count + 1);
      el.classList.add('reveal');
    });

    const io = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          io.unobserve(entry.target);
        }
      });
    }, { threshold: 0.15, rootMargin: '0px 0px -8% 0px' });

    targets.forEach(el => io.observe(el));
  }

  /* ---------- 2. Count-up stat numbers ---------- */
  function initCountUp() {
    const nodes = document.querySelectorAll('[data-count-to]');
    if (!nodes.length) return;

    const run = (el) => {
      const to = parseFloat(el.dataset.countTo);
      if (isNaN(to)) return;
      const decimals = (el.dataset.countTo.split('.')[1] || '').length;

      if (REDUCED) {
        el.textContent = to.toLocaleString(undefined, {
          minimumFractionDigits: decimals,
          maximumFractionDigits: decimals
        });
        return;
      }

      const dur = 900;
      const start = performance.now();
      const from = 0;

      function tick(now) {
        const p = Math.min(1, (now - start) / dur);
        const eased = 1 - Math.pow(1 - p, 3); // cubic ease-out
        const currentVal = from + (to - from) * eased;
        el.textContent = currentVal.toLocaleString(undefined, {
          minimumFractionDigits: decimals,
          maximumFractionDigits: decimals
        });
        if (p < 1) {
          requestAnimationFrame(tick);
        } else {
          el.textContent = to.toLocaleString(undefined, {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
          });
        }
      }
      requestAnimationFrame(tick);
    };

    if (REDUCED || !('IntersectionObserver' in window)) {
      nodes.forEach(el => {
        const to = parseFloat(el.dataset.countTo);
        if (!isNaN(to)) {
          const decimals = (el.dataset.countTo.split('.')[1] || '').length;
          el.textContent = to.toLocaleString(undefined, {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
          });
        }
      });
      return;
    }

    const io = new IntersectionObserver((entries) => {
      entries.forEach(e => {
        if (e.isIntersecting) {
          run(e.target);
          io.unobserve(e.target);
        }
      });
    }, { threshold: 0.3 });

    nodes.forEach(el => io.observe(el));
  }

  /* ---------- 3. Smooth scroll handler (MR-4) ---------- */
  function initSmoothScroll() {
    if (REDUCED) {
      document.documentElement.style.scrollBehavior = 'auto';
      return;
    }
    document.documentElement.style.scrollBehavior = 'smooth';

    // Track scroll position for CSS custom property and smooth anchor handling
    let ticking = false;
    window.addEventListener('scroll', () => {
      if (!ticking) {
        requestAnimationFrame(() => {
          document.documentElement.style.setProperty('--scroll-y', window.scrollY.toFixed(1) + 'px');
          ticking = false;
        });
        ticking = true;
      }
    }, { passive: true });

    // Smooth anchor navigation
    document.querySelectorAll('a[href^="#"]:not([href="#"])').forEach(anchor => {
      anchor.addEventListener('click', function (e) {
        const targetId = this.getAttribute('href');
        if (!targetId || targetId === '#') return;
        try {
          const targetEl = document.querySelector(targetId);
          if (targetEl) {
            e.preventDefault();
            targetEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
          }
        } catch (_) {}
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
      initReveal();
      initCountUp();
      initSmoothScroll();
    });
  } else {
    initReveal();
    initCountUp();
    initSmoothScroll();
  }
})();
