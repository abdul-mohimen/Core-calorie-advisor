/* ============ CORE CALORIE ADVISOR — Navbar account menu ============
   The avatar initial in the navbar used to be a plain link to the portal, so
   there was no way to sign out from the top bar at all — Logout existed only
   inside the slide-out sidebar. It is now a real menu button.

   Kept deliberately small and dependency-free, matching the notification bell's
   pattern in notifications.js.                                              */
(function () {
  const btn  = document.getElementById('acctBtn');
  const drop = document.getElementById('acctDrop');
  if (!btn || !drop) return;

  function open() {
    drop.classList.add('open');
    btn.setAttribute('aria-expanded', 'true');
  }
  function close() {
    drop.classList.remove('open');
    btn.setAttribute('aria-expanded', 'false');
  }
  const isOpen = () => drop.classList.contains('open');

  btn.addEventListener('click', e => {
    e.stopPropagation();
    isOpen() ? close() : open();
  });

  // Click anywhere outside closes it; clicks inside the menu must not.
  drop.addEventListener('click', e => e.stopPropagation());
  document.addEventListener('click', () => { if (isOpen()) close(); });

  // Escape closes and returns focus to the button, so keyboard users aren't trapped.
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && isOpen()) { close(); btn.focus(); }
  });

  /* If the notification bell is open, opening this should close that one —
     two overlapping panels in the same corner is a mess. */
  const notifBtn = document.getElementById('notifBtn');
  if (notifBtn) {
    notifBtn.addEventListener('click', () => { if (isOpen()) close(); });
    btn.addEventListener('click', () => {
      const nd = document.getElementById('notifDrop');
      if (nd) nd.classList.remove('on', 'open');
    });
  }
})();
