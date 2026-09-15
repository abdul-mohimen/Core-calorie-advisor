/* ============ CORE CALORIE ADVISOR — CCA Assistant chatbot ============
   Talks to api/chat.php (context-aware). Requires window.TF.        */
(function () {
  const root = document.getElementById('tf-chat');
  if (!root) return;

  const toggle = document.getElementById('tfChatToggle');
  const closeB = document.getElementById('tfChatClose');
  const panel  = document.getElementById('tfChatPanel');
  const body   = document.getElementById('tfChatBody');
  const quick  = document.getElementById('tfChatQuick');
  const form   = document.getElementById('tfChatForm');
  const input  = document.getElementById('tfChatText');
  const ping   = root.querySelector('.tf-chat-ping');

  const QUICK = ['Body Scanner?', 'Pricing', 'Find a trainer', 'How do I start?'];
  let greeted = false;

  function esc(s) { return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c])); }

  function bubble(text, who, links) {
    const row = document.createElement('div');
    row.className = 'tf-msg ' + who;
    // allow the pre-escaped &amp; etc from server; escape only user text
    row.innerHTML = '<div class="tf-bub">' + (who === 'me' ? esc(text) : text) + '</div>';
    if (links && links.length) {
      const lw = document.createElement('div');
      lw.className = 'tf-links';
      lw.innerHTML = links.map(l => '<a class="tf-link" href="' + esc(l[1]) + '">' + esc(l[0]) + ' →</a>').join('');
      row.appendChild(lw);
    }
    body.appendChild(row);
    body.scrollTop = body.scrollHeight;
  }

  function typing(on) {
    let t = document.getElementById('tfTyping');
    if (on) {
      if (t) return;
      t = document.createElement('div');
      t.id = 'tfTyping';
      t.className = 'tf-msg bot';
      t.innerHTML = '<div class="tf-bub tf-typing"><span></span><span></span><span></span></div>';
      body.appendChild(t);
      body.scrollTop = body.scrollHeight;
    } else if (t) { t.remove(); }
  }

  function renderQuick() {
    quick.innerHTML = QUICK.map(q => '<button type="button" class="tf-chip">' + esc(q) + '</button>').join('');
    quick.querySelectorAll('.tf-chip').forEach(b => b.addEventListener('click', () => send(b.textContent)));
  }

  function open() {
    root.dataset.open = 'true';
    ping.style.display = 'none';
    if (!greeted) {
      greeted = true;
      const hello = TF.loggedIn
        ? 'Hey ' + (TF.name ? esc(TF.name.split(' ')[0]) : 'Champ') + '! 👋 I\'m your CCA Assistant. How can I help?'
        : 'Welcome to CORE CALORIE ADVISOR! 🔥 I\'m the CCA Assistant. Ask me about workouts, AI scanners, trainers or pricing.';
      typing(true);
      setTimeout(() => { typing(false); bubble(hello, 'bot'); renderQuick(); }, 500);
    }
    setTimeout(() => input.focus(), 300);
  }
  function close() { root.dataset.open = 'false'; }

  function send(text) {
    text = (text || '').trim();
    if (!text) return;
    bubble(text, 'me');
    input.value = '';
    typing(true);
    const fd = new FormData();
    fd.append('csrf', TF.csrf);
    fd.append('message', text);
    fetch(TF.baseUrl + '/api/chat.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(j => {
        typing(false);
        if (j.ok) bubble(j.reply, 'bot', j.links);
        else bubble('Hmm, I couldn\'t reach the advisor just now. Try again?', 'bot');
      })
      .catch(() => { typing(false); bubble('Network hiccup — please try again.', 'bot'); });
  }

  toggle.addEventListener('click', () => (root.dataset.open === 'true' ? close() : open()));
  closeB.addEventListener('click', close);
  form.addEventListener('submit', e => { e.preventDefault(); send(input.value); });
})();
