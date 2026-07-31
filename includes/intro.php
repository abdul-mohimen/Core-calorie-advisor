<?php
// intro.php - Self-contained intro component
?>
<style>
/* ── INTRO SPLASH SCREEN ── */
:root {
  --cca-primary: var(--primary);
  --cca-dark: var(--bg);
}

#cca-intro-shell {
  position: fixed; inset: 0; z-index: 99999;
  background: var(--cca-dark); color: var(--on-media);
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  overflow: hidden; pointer-events: auto;
  transition: opacity 0.3s ease, transform 0.4s ease;
}

#cca-intro-shell.hide-intro {
  opacity: 0; pointer-events: none; transform: scale(1.04);
}

/* 0.00–0.35: Radial Energy Bloom */
.intro-bloom {
  position: absolute; inset: 0;
  background: radial-gradient(circle at center, rgba(255, 107, 26, 0.15) 0%, transparent 60%);
  opacity: 0; transform: translateY(20px);
}

/* 0.25–0.75: Logo Mark Slams in */
.intro-mark {
  width: 120px; height: 120px; opacity: 0; transform: scale(1.6) rotate(-10deg);
  position: relative; z-index: 2;
  filter: drop-shadow(0 0 20px rgba(255,107,26,0.5));
}
.intro-mark svg { width: 100% !important; height: 100% !important; max-width: 100% !important; max-height: 100% !important; color: var(--cca-primary); }

/* ── Mark build-on sequence ──────────────────────────────────────────────
   The ring TRACES itself (stroke-dashoffset), the progress head rides to its
   end, then the flame ignites inside. This is the logo explaining itself:
   measure first, then burn. 268 ≈ the arc's path length at r=34. */
#cca-ring {
  stroke-dasharray: 268;
  stroke-dashoffset: 268;
}
.intro-mark.do-trace #cca-ring {
  animation: ccaTrace .72s cubic-bezier(.32,.72,.28,1) forwards;
}
@keyframes ccaTrace { to { stroke-dashoffset: 0; } }

#cca-head { opacity: 0; transform-origin: 77.85px 30.5px; }
.intro-mark.do-trace #cca-head {
  animation: ccaHead .30s ease-out .62s forwards;
}
@keyframes ccaHead {
  0%   { opacity: 0; transform: scale(0); }
  60%  { opacity: 1; transform: scale(1.45); }
  100% { opacity: 1; transform: scale(1); }
}

#cca-core { opacity: 0; transform-origin: 50px 58px; }
.intro-mark.do-ignite #cca-core {
  animation: ccaIgnite .46s cubic-bezier(.2,1.5,.35,1) forwards;
}
@keyframes ccaIgnite {
  0%   { opacity: 0; transform: scale(.25) translateY(10px); }
  55%  { opacity: 1; transform: scale(1.18) translateY(-2px); }
  100% { opacity: 1; transform: scale(1) translateY(0); }
}

/* Heat shimmer once the flame is lit — subtle, not a strobe.
   ⚠️ Applied to the SVG CHILD, never to .intro-mark itself: .intro-mark already
   carries the `do-slam` entrance animation, and the CSS `animation` shorthand
   REPLACES rather than merges. Putting the shimmer on the parent silently
   cancelled the slam, losing its `forwards` fill — so the mark snapped back to
   opacity:0 and vanished from the whole intro. */
.intro-mark.do-ignite svg {
  animation: ccaHeat 1.6s ease-in-out .5s infinite;
}
@keyframes ccaHeat {
  0%, 100% { filter: drop-shadow(0 0 18px rgba(255,107,26,.45)); }
  50%      { filter: drop-shadow(0 0 34px rgba(255,140,40,.75)); }
}

/* Expanding energy ring at the ignition beat. */
.intro-shock {
  position: absolute; top: 50%; left: 50%;
  width: 120px; height: 120px; margin: -60px 0 0 -60px;
  border-radius: 50%; z-index: 1; opacity: 0;
  border: 2px solid rgba(255,140,40,.85);
  pointer-events: none;
}
.intro-shock.do-shock { animation: ccaShock .85s cubic-bezier(.15,.7,.3,1) forwards; }
@keyframes ccaShock {
  0%   { opacity: .9; transform: scale(.5); border-width: 3px; }
  100% { opacity: 0;  transform: scale(4.2); border-width: 0.5px; }
}

@media (prefers-reduced-motion: reduce) {
  #cca-ring { stroke-dashoffset: 0; }
  #cca-head, #cca-core { opacity: 1; }
  .intro-mark.do-trace #cca-ring,
  .intro-mark.do-trace #cca-head,
  .intro-mark.do-ignite #cca-core,
  .intro-mark.do-ignite svg,
  .intro-shock.do-shock { animation: none !important; }
  .intro-shock { display: none; }
}

/* 0.55–0.65: Impact Flash */
.intro-flash {
  position: absolute; inset: 0; background: var(--on-media); opacity: 0; z-index: 3; pointer-events: none;
}

/* Spark Particles */
.intro-sparks { position: absolute; top: 50%; left: 50%; width: 0; height: 0; z-index: 1; overflow: visible; }
.spark {
  position: absolute; width: 4px; height: 4px; background: var(--on-media); border-radius: 50%;
  box-shadow: 0 0 8px var(--cca-primary); opacity: 0;
}

/* 0.80–1.40: Wordmark Reveal (Mask Sweep) */
.intro-wordmark {
  margin-top: 16px; font-family: 'Inter', 'Russo One', sans-serif;
  font-weight: 900; font-size: 32px; letter-spacing: -0.02em;
  color: var(--on-media); text-align: center;
  position: relative; z-index: 2; opacity: 0;
  -webkit-mask-image: linear-gradient(to right, rgba(0,0,0,1) 50%, rgba(0,0,0,0) 50%);
  -webkit-mask-size: 200% 100%; -webkit-mask-position: 100% 0;
  mask-image: linear-gradient(to right, rgba(0,0,0,1) 50%, rgba(0,0,0,0) 50%);
  mask-size: 200% 100%; mask-position: 100% 0;
}
.intro-wordmark span { color: var(--cca-primary); font-size: 24px; letter-spacing: 0.1em; display: block; }

/* 1.20–1.60: Tagline Fades Up */
.intro-tagline {
  margin-top: 12px; font-family: sans-serif; font-size: 12px;
  color: rgba(255,255,255,0.6); opacity: 0; letter-spacing: 0.2em; text-transform: uppercase;
}

/* 1.30–2.40: Loading Bar */
.intro-loader-wrap {
  position: absolute; bottom: 60px; width: 240px; text-align: center; opacity: 0;
}
.intro-loader-track {
  width: 100%; height: 3px; background: rgba(255,255,255,0.1); border-radius: 3px; overflow: hidden; margin-bottom: 8px;
}
.intro-loader-bar {
  height: 100%; width: 0%; background: var(--cca-primary); border-radius: 3px; transition: width 0.1s linear;
}
.intro-loader-text { font-family: monospace; font-size: 11px; color: rgba(255,255,255,0.5); }

/* Skip Button */
.intro-skip {
  position: absolute; top: 20px; right: 20px; z-index: 10;
  background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: var(--on-media);
  padding: 6px 12px; font-size: 11px; border-radius: 4px; cursor: pointer; opacity: 0;
  text-transform: uppercase; letter-spacing: 1px;
}
.intro-skip:hover { background: rgba(255,255,255,0.1); }

/* == ANIMATIONS KEYFRAMES == */
@keyframes bloomRise {
  0% { opacity: 0; transform: translateY(20px); }
  100% { opacity: 1; transform: translateY(0); }
}
@keyframes markSlam {
  0% { opacity: 0; transform: scale(1.6) rotate(-10deg); }
  100% { opacity: 1; transform: scale(1) rotate(0deg); }
}
@keyframes screenShake {
  0%, 100% { transform: translate(0,0); }
  25% { transform: translate(-4px, 2px); }
  50% { transform: translate(4px, -2px); }
  75% { transform: translate(-2px, 4px); }
}
@keyframes flash {
  0% { opacity: 0; }
  50% { opacity: 0.12; }
  100% { opacity: 0; }
}
@keyframes sparkBurst {
  0% { opacity: 1; transform: translate(0,0) scale(1); }
  100% { opacity: 0; transform: translate(var(--tx), var(--ty)) scale(0); }
}
@keyframes maskSweep {
  0% { -webkit-mask-position: 100% 0; mask-position: 100% 0; opacity: 1; }
  100% { -webkit-mask-position: 0% 0; mask-position: 0% 0; opacity: 1; }
}
@keyframes taglineFade {
  0% { opacity: 0; letter-spacing: 0.2em; transform: translateY(5px); }
  100% { opacity: 1; letter-spacing: 0.06em; transform: translateY(0); }
}

/* Trigger classes */
.do-bloom { animation: bloomRise 0.35s cubic-bezier(0.2,0.8,0.2,1) forwards; }
.do-slam { animation: markSlam 0.5s cubic-bezier(0.25, 1.2, 0.3, 1) forwards; }
.do-shake { animation: screenShake 0.12s ease-in-out forwards; }
.do-flash { animation: flash 0.1s ease-out forwards; }
.do-sweep { animation: maskSweep 0.6s cubic-bezier(0.4,0,0.2,1) forwards; }
.do-tagline { animation: taglineFade 0.4s cubic-bezier(0.4,0,0.2,1) forwards; }

/* Reduced Motion */
@media (prefers-reduced-motion: reduce) {
  .intro-mark { transform: scale(1) rotate(0deg); transition: opacity 0.4s; }
  .intro-wordmark { -webkit-mask-image: none; mask-image: none; transition: opacity 0.4s; }
  .do-bloom, .do-slam, .do-shake, .do-flash, .do-sweep, .do-tagline { animation: none !important; opacity: 1 !important; transform: none !important; letter-spacing: 0.06em !important; }
  .intro-sparks { display: none; }
}
</style>

<div id="cca-intro-shell">
  <button id="cca-intro-skip" class="intro-skip">Skip</button>
  <div class="intro-flash" id="cca-flash"></div>
  <div class="intro-bloom" id="cca-bloom"></div>
  
  <div class="intro-sparks" id="cca-sparks"></div>
  <div class="intro-shock" id="cca-shock"></div>
  
  <!-- The mark is the SAME geometry as assets/brand/logo-mark.svg, split into
       two animatable parts. The old intro inlined a completely different
       (dumbbell) logo — a third copy that had already drifted from the brand.
       The sequence is built FROM the mark's own meaning: the activity ring
       draws itself, then the flame ignites inside it. Original work; no game's
       assets, wordmark or layout are reproduced (see CLAUDE.md). -->
  <div class="intro-mark" id="cca-mark">
    <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
      <defs>
        <linearGradient id="introFlame" x1="26" y1="76" x2="74" y2="20" gradientUnits="userSpaceOnUse">
          <stop offset="0" stop-color="#FF3D00"/>
          <stop offset=".45" stop-color="#FF6B1A"/>
          <stop offset="1" stop-color="#FFB800"/>
        </linearGradient>
      </defs>
      <path id="cca-ring" d="M22.15 30.5 A34 34 0 1 0 77.85 30.5"
            fill="none" stroke="url(#introFlame)" stroke-width="11" stroke-linecap="round"/>
      <circle id="cca-head" cx="77.85" cy="30.5" r="5.5" fill="#FFB800"/>
      <path id="cca-core"
            d="M50 25c8 10 14 17 14 26 0 9.9-6.3 16-14 16s-14-6.1-14-16c0-7 4-13 8-18 1 7 4 10 6 11 1-5 0-13 0-19Z"
            fill="url(#introFlame)"/>
    </svg>
  </div>
  
  <div class="intro-wordmark" id="cca-wordmark">
    CORE CALORIE <span>ADVISOR</span>
  </div>
  <div class="intro-tagline" id="cca-tagline">Precision nutrition meets 3D coaching.</div>
  
  <div class="intro-loader-wrap" id="cca-loader">
    <div class="intro-loader-track"><div class="intro-loader-bar" id="cca-bar"></div></div>
    <div class="intro-loader-text" id="cca-pct">0%</div>
  </div>
</div>

<script>
(function(){
  // Only show once per session unless bypassing for tests
  const isTest = new URLSearchParams(window.location.search).has('test_intro');
  if (sessionStorage.getItem('cca_intro_seen') && !isTest) {
    document.getElementById('cca-intro-shell').remove();
    return;
  }
  
  const shell = document.getElementById('cca-intro-shell');
  const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  let done = false;
  
  function skipIntro() {
    if (done) return; done = true;
    shell.classList.add('hide-intro');
    sessionStorage.setItem('cca_intro_seen', '1');
    setTimeout(() => shell.remove(), 400);
  }

  // Keyboard accessibility
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') skipIntro(); });
  document.getElementById('cca-intro-skip').addEventListener('click', skipIntro);

  // Timeline scheduler
  /* Beat sheet — the mark builds itself, then the brand lands:
       0.00  bloom          ambient light rises
       0.18  slam + trace   mark drops in and the activity ring draws round
       0.90  ignite         flame catches, shockwave, impact flash, sparks
       1.25  wordmark       mask-swept reveal
       1.60  tagline
       1.72  loader
     Each beat waits for the previous one to READ, rather than firing on top
     of it — the old timeline flashed at 0.55s while the mark was still
     settling, so the impact landed on nothing. */
  const tl = [
    { t: 0,    f: () => document.getElementById('cca-bloom').classList.add('do-bloom') },
    { t: 180,  f: () => {
        const m = document.getElementById('cca-mark');
        m.classList.add('do-slam');
        m.classList.add('do-trace');            // ring traces itself
    }},
    { t: 400,  f: () => document.getElementById('cca-intro-skip').style.opacity = 1 },
    { t: 900,  f: () => {
        document.getElementById('cca-mark').classList.add('do-ignite');   // flame catches
        if (!prefersReduced) {
          document.getElementById('cca-shock').classList.add('do-shock');
          shell.classList.add('do-shake');
          document.getElementById('cca-flash').classList.add('do-flash');
          createSparks();
        }
    }},
    { t: 1250, f: () => document.getElementById('cca-wordmark').classList.add('do-sweep') },
    { t: 1600, f: () => document.getElementById('cca-tagline').classList.add('do-tagline') },
    { t: 1720, f: () => { document.getElementById('cca-loader').style.opacity = 1; startLoader(); } }
  ];

  tl.forEach(step => setTimeout(() => { if(!done) step.f(); }, step.t));

  function createSparks() {
    const cont = document.getElementById('cca-sparks');
    for(let i=0; i<30; i++) {
      const angle = Math.random() * Math.PI * 2;
      const dist = 60 + Math.random() * 120;
      const tx = Math.cos(angle) * dist + 'px';
      const ty = Math.sin(angle) * dist + 'px';
      const sp = document.createElement('div');
      sp.className = 'spark';
      sp.style.setProperty('--tx', tx);
      sp.style.setProperty('--ty', ty);
      sp.style.animation = `sparkBurst ${0.4 + Math.random()*0.3}s cubic-bezier(0.2,0.8,0.2,1) forwards`;
      cont.appendChild(sp);
    }
  }

  function startLoader() {
    let p = 0;
    const bar = document.getElementById('cca-bar');
    const txt = document.getElementById('cca-pct');
    // Simulate real progress based on resources
    const int = setInterval(() => {
      if (done) { clearInterval(int); return; }
      
      // If document complete, accelerate to 100%
      if (document.readyState === 'complete') p += 15;
      else p += Math.random() * 8;
      
      if (p >= 100) {
        p = 100;
        clearInterval(int);
        setTimeout(skipIntro, 200); // 2.40 - 2.60 fade out
      }
      bar.style.width = p + '%';
      txt.innerText = Math.floor(p) + '%';
    }, 50);
  }
})();
</script>
