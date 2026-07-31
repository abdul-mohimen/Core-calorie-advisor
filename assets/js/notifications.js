/* ============ CORE CALORIE ADVISOR — Notification bell ============ */
(function () {
  const btn = document.getElementById('notifBtn');
  if (!btn) return;
  const drop  = document.getElementById('notifDrop');
  const list  = document.getElementById('notifList');
  const badge = document.getElementById('notifBadge');
  const clear = document.getElementById('notifClear');

  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c])); }

  function setBadge(n) {
    if (n > 0) { badge.style.display = ''; badge.textContent = n > 9 ? '9+' : n; }
    else badge.style.display = 'none';
  }

  function render(items) {
    if (!items || !items.length) { list.innerHTML = '<div class="notif-empty">You\'re all caught up 🎉</div>'; return; }
    list.innerHTML = items.map(n => {
      const cls = 'notif-item ' + n.type + (n.read ? '' : ' unread');
      const inner = '<div class="ni-dot"></div><div class="ni-body"><b>' + esc(n.title) + '</b>'
        + (n.body ? '<p>' + esc(n.body) + '</p>' : '') + '<small>' + esc(n.when) + ' ago</small></div>';
      return n.link
        ? '<a class="' + cls + '" href="' + esc(n.link) + '">' + inner + '</a>'
        : '<div class="' + cls + '">' + inner + '</div>';
    }).join('');
  }

  function load() {
    fetch(TF.baseUrl + '/api/notifications.php?action=list', { headers: { 'X-Requested-With': 'fetch' } })
      .then(r => r.json())
      .then(j => { if (j.ok) { setBadge(j.unread); render(j.items); } })
      .catch(() => {});
  }

  function markRead() {
    const fd = new FormData();
    fd.append('csrf', TF.csrf);
    fd.append('action', 'read');
    fetch(TF.baseUrl + '/api/notifications.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(j => { if (j.ok) { setBadge(0); list.querySelectorAll('.notif-item.unread').forEach(el => el.classList.remove('unread')); } })
      .catch(() => {});
  }

  btn.addEventListener('click', e => {
    e.stopPropagation();
    const open = drop.classList.toggle('open');
    if (open) { load(); setTimeout(markRead, 1200); }
  });
  clear.addEventListener('click', e => { e.stopPropagation(); markRead(); });
  document.addEventListener('click', e => { if (!e.target.closest('.notif-wrap')) drop.classList.remove('open'); });

  setBadge(TF.unread || 0);
  load();
  setInterval(load, 30000);
})();
