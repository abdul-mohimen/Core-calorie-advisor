/* ============ CORE CALORIE ADVISOR — FAQ accordion ============ */
(function () {
  document.querySelectorAll('.faq-item .faq-q').forEach(q => {
    q.addEventListener('click', () => {
      const item = q.closest('.faq-item');
      const open = item.classList.contains('open');
      document.querySelectorAll('.faq-item.open').forEach(el => { if (el !== item) el.classList.remove('open'); });
      item.classList.toggle('open', !open);
    });
  });
})();
