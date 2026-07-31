/* ============ CORE CALORIE ADVISOR — Community feed (post + optimistic like) ============ */
(function () {
  const form = document.getElementById('commForm');
  if (!form) return;
  const body  = document.getElementById('commBody');
  const count = document.getElementById('commCount');
  const feed  = document.getElementById('commFeed');

  function esc(s) { return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c])); }

  body.addEventListener('input', () => { count.textContent = body.value.length; });

  form.addEventListener('submit', e => {
    e.preventDefault();
    const text = body.value.trim();
    if (!text) return;
    const btn = form.querySelector('button[type=submit]');
    btn.disabled = true; btn.textContent = 'Posting…';

    const fd = new FormData();
    fd.append('csrf', TF.csrf);
    fd.append('body', text);
    fetch(TF.baseUrl + '/api/community-post.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(j => {
        btn.disabled = false; btn.textContent = 'Post to Community →';
        if (!j.ok) { toast('⚠ ' + (j.error || 'Could not post.')); return; }
        feed.insertAdjacentHTML('afterbegin', postHTML(j.post));
        body.value = ''; count.textContent = '0';
        const el = feed.firstElementChild;
        el.style.opacity = '0'; el.style.transform = 'translateY(-10px)';
        requestAnimationFrame(() => { el.style.transition = '.5s'; el.style.opacity = '1'; el.style.transform = 'none'; });
        toast('✔ Posted to the Warriors\' Circle 🔥');
      })
      .catch(() => { btn.disabled = false; btn.textContent = 'Post to Community →'; toast('⚠ Network error.'); });
  });

  function postHTML(p) {
    return '<article class="comm-post" data-id="' + p.id + '">'
      + '<div class="cp-head"><div class="comm-ava sm">' + esc(p.initial) + '</div>'
      + '<div class="cp-meta"><b>' + esc(p.name) + ' <span class="role-chip">' + esc(p.role) + '</span></b>'
      + '<small>' + esc(p.when) + '</small></div></div>'
      + '<p>' + esc(p.body).replace(/\n/g, '<br>') + '</p>'
      + '<div class="cp-foot"><span class="cp-like">🔥 <b>' + p.likes + '</b></span>'
      + '<span>💬 Reply</span><span>↗ Share</span></div></article>';
  }

  /* optimistic like toggle */
  feed.addEventListener('click', e => {
    const like = e.target.closest('.cp-like');
    if (!like) return;
    const b = like.querySelector('b');
    if (like.dataset.on) { b.textContent = +b.textContent - 1; delete like.dataset.on; like.style.color = ''; }
    else { b.textContent = +b.textContent + 1; like.dataset.on = '1'; like.style.color = 'var(--ember)'; }
  });
})();
