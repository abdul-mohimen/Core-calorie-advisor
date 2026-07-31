/* ============ CORE CALORIE ADVISOR — Main JS (sidebar, theme, search, toast, reveal, calcs) ============ */
const $=s=>document.querySelector(s), $$=s=>document.querySelectorAll(s);

/* ---- BULLETPROOF navbar clearance: measure the REAL navbar height and push
   every page's content below it (works no matter the navbar's actual height) ---- */
(function(){
  const nav=document.querySelector('.nav');
  if(!nav)return;
  const apply=()=>{const h=Math.ceil(nav.getBoundingClientRect().height);
    if(h>0)document.documentElement.style.setProperty('--nav-h',h+'px')};
  apply();
  addEventListener('resize',apply);
  addEventListener('orientationchange',apply);
  addEventListener('load',apply);
  if(document.fonts&&document.fonts.ready)document.fonts.ready.then(apply);
  setTimeout(apply,350);setTimeout(apply,1400);   // catch late webfont/CDN reflow
})();

/* ---- Toast ---- */
function toast(m){let t=$('#toast');
  if(!t){t=document.createElement('div');t.id='toast';
    t.style.cssText='position:fixed;bottom:26px;left:50%;transform:translateX(-50%) translateY(80px);z-index:500;background:var(--bg3);border:1px solid var(--line2);color:var(--text);font-family:var(--tech);font-weight:600;padding:13px 22px;border-radius:13px;box-shadow:var(--shadow);opacity:0;transition:.35s;pointer-events:none;max-width:90vw;text-align:center';
    document.body.appendChild(t)}
  t.textContent=m;t.style.opacity=1;t.style.transform='translateX(-50%) translateY(0)';
  clearTimeout(t._h);t._h=setTimeout(()=>{t.style.opacity=0;t.style.transform='translateX(-50%) translateY(80px)'},2600)}

/* ---- Sidebar: CLOSED by default on every load. The hamburger opens it as a
   drawer; on desktop (>=1100px) an open sidebar also pushes the content
   (#app.sb-open → margin-left), on mobile it overlays with a scrim. ---- */
const sb=$('#sidebar'),ov=$('#overlay'),appEl=$('#app');
const isDesktop=()=>matchMedia('(min-width:1100px)').matches;
if($('#burger')){
  const setSidebar=open=>{
    sb.classList.toggle('open',open);
    appEl.classList.toggle('sb-open',open);
    ov.classList.toggle('on',open && !isDesktop());   /* scrim only on mobile */
  };
  setSidebar(false);                                   /* never open on reload */
  $('#burger').onclick=()=>setSidebar(!sb.classList.contains('open'));
  ov.onclick=()=>setSidebar(false);

  /* ══ PHASE I — drawer a11y: Esc to close, focus trap while open ══
     The drawer already had a scrim and click-to-close; keyboard users had no way
     out and Tab walked straight out of the open drawer into the page behind it. */
  const focusables = () => Array.prototype.slice.call(
    sb.querySelectorAll('a[href], button:not([disabled]), input, select, textarea, [tabindex]:not([tabindex="-1"])')
  ).filter(n => n.offsetParent !== null);

  document.addEventListener('keydown', e => {
    if (!sb.classList.contains('open')) return;

    if (e.key === 'Escape') {
      setSidebar(false);
      const b = $('#burger'); if (b) b.focus();       /* return focus to opener */
      return;
    }
    /* Trap Tab only while the drawer overlays content (mobile). On desktop the
       sidebar pushes rather than covers, so the rest of the page is legitimately
       reachable and trapping there would be hostile. */
    if (e.key !== 'Tab' || isDesktop()) return;
    const f = focusables();
    if (!f.length) return;
    const first = f[0], last = f[f.length - 1];
    if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
    else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
  });
}

/* ══ PHASE I — collapsible sidebar groups ══
   header.php already renders the groups (.sb-sec headings followed by .sb-link
   siblings); what was missing was the ability to collapse them and have that
   stick. Done as progressive enhancement: with JS off every group stays open,
   so nothing becomes unreachable. */
(function collapsibleSidebarGroups() {
  const KEY = 'cca-sb-collapsed';
  const secs = document.querySelectorAll('#sidebar .sb-sec');
  if (!secs.length) return;

  let collapsed;
  try { collapsed = new Set(JSON.parse(localStorage.getItem(KEY) || '[]')); }
  catch (e) { collapsed = new Set(); }
  const save = () => { try { localStorage.setItem(KEY, JSON.stringify([...collapsed])); } catch (e) {} };

  secs.forEach((sec, i) => {
    /* Members of a group = siblings until the next .sb-sec. */
    const members = [];
    for (let n = sec.nextElementSibling; n && !n.classList.contains('sb-sec'); n = n.nextElementSibling) members.push(n);
    if (!members.length) return;

    const id = (sec.textContent || ('grp' + i)).trim().toLowerCase().replace(/[^a-z0-9]+/g, '-');
    sec.setAttribute('role', 'button');
    sec.setAttribute('tabindex', '0');
    sec.classList.add('is-collapsible');

    const paint = () => {
      const off = collapsed.has(id);
      sec.classList.toggle('is-collapsed', off);
      sec.setAttribute('aria-expanded', String(!off));
      members.forEach(m => { m.style.display = off ? 'none' : ''; });
    };
    const toggle = () => { collapsed.has(id) ? collapsed.delete(id) : collapsed.add(id); save(); paint(); };

    sec.addEventListener('click', toggle);
    sec.addEventListener('keydown', e => {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(); }
    });
    paint();
  });
})();

/* ---- Theme (Global Light / Dark Mode Toggle) ---- */
const initTheme = () => {
  const toggleBtn = document.getElementById('themeToggle');
  const getTheme = () => localStorage.getItem('tf-theme') || 'dark';
  const setTheme = (theme) => {
    localStorage.setItem('tf-theme', theme);
    document.documentElement.dataset.theme = theme;
    if (theme === 'dark') {
      document.documentElement.classList.add('dark');
    } else {
      document.documentElement.classList.remove('dark');
    }
  };

  // Ensure current state is set
  setTheme(getTheme());

  if (toggleBtn) {
    toggleBtn.onclick = () => {
      const current = getTheme();
      const next = current === 'dark' ? 'light' : 'dark';
      setTheme(next);
    };
  }
};
initTheme();

/* ---- Real-time thumbnail search (index injected by header.php as window.TF_INDEX) ---- */
const sIn=$('#searchIn'),sDrop=$('#searchDrop'),sRes=$('#searchResults');
const GENERIC_ICON='data:image/svg+xml;utf8,'+encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><path d="M50 6 L88 26 V58 C88 76 70 90 50 96 C30 90 12 76 12 58 V26 Z" fill="#8B6914" opacity=".35"/><path d="M50 22 C44 34 58 36 54 46 C62 42 66 50 62 58 C70 56 74 66 66 74 C60 80 40 80 34 74 C26 66 30 56 38 58 C34 50 38 42 46 46 C42 36 56 34 50 22 Z" fill="#D4AF37"/></svg>');
if(sIn&&window.TF_INDEX){
  let sTimer=null;
  function esc(s){return String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]))}
  function renderSearch(q){
    if(!q){sRes.innerHTML='<div class="sr-hint">Start typing to search workouts, food, trainers &amp; more…</div>';return}
    const ql=q.toLowerCase();
    const list=TF_INDEX.filter(i=>i.t.toLowerCase().includes(ql));
    if(!list.length){sRes.innerHTML='<div class="sr-hint">No results in the forge for "'+esc(q)+'"…</div>';return}
    sRes.innerHTML=list.slice(0,10).map(i=>
      '<div class="sr-item" data-href="'+esc(i.u)+'">'+
        '<img class="sr-thumb" src="'+esc(i.img||GENERIC_ICON)+'" alt="" loading="lazy" onerror="this.src=\''+GENERIC_ICON+'\'">'+
        '<div class="sr-info"><b>'+esc(i.t)+'</b><small>'+esc(i.k)+'</small></div>'+
      '</div>').join('');
    sRes.querySelectorAll('.sr-item').forEach(el=>el.onclick=()=>{location.href=el.dataset.href});
  }
  sIn.addEventListener('focus',()=>{sDrop.classList.add('open');renderSearch(sIn.value.trim())});
  sIn.addEventListener('input',()=>{clearTimeout(sTimer);const q=sIn.value.trim();sTimer=setTimeout(()=>renderSearch(q),120)});
  document.addEventListener('click',e=>{if(!e.target.closest('.searchbox'))sDrop.classList.remove('open')});
}

/* ---- Global scroll reveal (IntersectionObserver) ---- */
(function(){
  const reduce=matchMedia('(prefers-reduced-motion: reduce)').matches;
  const targets=new Set();
  $$('.rv').forEach(el=>targets.add(el));
  // Auto fade-up for common blocks (skip hero + page-hero direct children which self-animate)
  $$('.sec-head, .card, .stat-card, .price-card, .calc-card, .table-wrap, .legal-body, .scan-banner, .cca-card, .cca-metric, .cca-hero__glass').forEach(el=>{
    if(el.closest('.hero')) return;
    if(el.parentElement && el.parentElement.classList.contains('page-hero')) return;
    if(el.classList.contains('rv')||el.classList.contains('reveal')) return;
    el.classList.add('reveal');targets.add(el);
  });
  if(reduce||!('IntersectionObserver' in window)){targets.forEach(el=>el.classList.add('in'));return;}
  const io=new IntersectionObserver(es=>{es.forEach(e=>{if(e.isIntersecting){e.target.classList.add('in');io.unobserve(e.target);}});},{threshold:.12,rootMargin:'0px 0px -40px 0px'});
  targets.forEach(el=>io.observe(el));
})();

/* ---- Hero 3D panel — subtle mouse parallax tilt ---- */
const heroEl=$('.hero'),hero3d=$('#hero3d');
if(heroEl&&hero3d&&!matchMedia('(prefers-reduced-motion: reduce)').matches&&matchMedia('(hover:hover)').matches){
  heroEl.addEventListener('mousemove',e=>{
    const r=heroEl.getBoundingClientRect();
    const px=(e.clientX-r.left)/r.width-.5,py=(e.clientY-r.top)/r.height-.5;
    hero3d.style.transform='rotateY('+(px*6)+'deg) rotateX('+(py*-6)+'deg)'});
  heroEl.addEventListener('mouseleave',()=>{hero3d.style.transform=''});
}

/* ---- Calculators ---- */
function bmiCat(b){return b<18.5?'UNDERWEIGHT — Gain plan suggested':b<25?'NORMAL — Maintain, Champ!':b<30?'OVERWEIGHT — Fat burn suggested':'OBESE — Doctor-safe plan suggested'}
function quickBMI(){const h=+$('#qH').value/100,w=+$('#qW').value;if(!h||!w)return;const b=(w/(h*h)).toFixed(1);$('#qOut').style.display='block';$('#qBMI').textContent=b;$('#qCat').textContent=bmiCat(+b)}
function calcBMI(){const h=+$('#cH').value/100,w=+$('#cW').value;if(!h||!w)return;const b=(w/(h*h)).toFixed(1);$('#cBMIout').style.display='block';$('#cBMI').textContent=b;$('#cBMIcat').textContent=bmiCat(+b)}
function calcBMR(){const a=+$('#bAge').value,g=$('#bGen').value,h=+$('#bH').value,w=+$('#bW').value,act=+$('#bAct').value;
  const bmr=g==='m'?10*w+6.25*h-5*a+5:10*w+6.25*h-5*a-161;
  $('#bOut').style.display='block';$('#bCal').textContent=Math.round(bmr*act)}
function calcWater(){const w=+$('#wW').value;$('#wOut').style.display='block';$('#wL').textContent=(w*0.035).toFixed(1)}
function calcMacro(){const c=+$('#mCal').value;$('#mOut').style.display='block';$('#mTxt').textContent=Math.round(c*.3/4)+'g / '+Math.round(c*.45/4)+'g / '+Math.round(c*.25/9)+'g'}

/* ══ PHASE I — portal patti keyboard navigation ══
   Arrow keys move focus along the strip, Home/End jump to the ends. Enter is
   not handled: these are real <a> elements, so the browser already activates
   them — intercepting it would only risk breaking modified clicks. */
document.addEventListener('keydown', function (ev) {
  if (['ArrowLeft','ArrowRight','Home','End'].indexOf(ev.key) === -1) return;
  var nav = ev.target.closest && ev.target.closest('.cca-portal-nav');
  if (!nav) return;
  var items = Array.prototype.slice.call(nav.querySelectorAll('a'));
  var i = items.indexOf(ev.target);
  if (i === -1) return;
  var to = ev.key === 'Home'  ? 0
         : ev.key === 'End'   ? items.length - 1
         : ev.key === 'ArrowLeft' ? (i - 1 + items.length) % items.length
         : (i + 1) % items.length;
  ev.preventDefault();
  items[to].focus();
  if (items[to].scrollIntoView) items[to].scrollIntoView({ block:'nearest', inline:'nearest' });
});
