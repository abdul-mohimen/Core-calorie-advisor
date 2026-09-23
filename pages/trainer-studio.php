<?php
require_once dirname(__DIR__) . '/config/config.php';
$pageTitle = 'Trainer Studio — 3D Locker Room';
include dirname(__DIR__) . '/includes/header.php';

$next = trim((string)get('next', ''));
$backUrl = $next !== '' && preg_match('#^[A-Za-z0-9_\-/\.\?=&]+$#', $next)
    ? url($next) : url('pages/workouts.php');
?>

<section class="cca-hero" style="min-height:auto;padding-block:clamp(28px,5vw,52px)">
  <div class="cca-hero__overlay"></div>
  <div class="cca-hero__content" style="display:block">
    <div class="cca-hero__breadcrumb">
      <a href="<?= url('index.php') ?>">Home</a> <span class="sep">›</span>
      <a href="<?= url('pages/workouts.php') ?>">Workouts</a> <span class="sep">›</span> Trainer Studio
    </div>
    <div class="cca-hero__badge cca-hero__badge--pro"><span class="dot"></span> Free Fire &amp; GTA Style 3D Customization</div>
    <h1 class="cca-hero__title" style="margin-bottom:8px">Trainer <span class="grad">Studio</span></h1>
    <p class="cca-hero__subtitle" style="max-width:64ch">
      Customise your 3D coach with genuine modular workout apparel (gym tanks, lifting shorts, hoodies), bone-socket accessories, and animated VFX auras.
    </p>
  </div>
</section>

<section class="studio-page">
  <div id="studioGrid">

    <!-- ══ LEFT: Free Fire & GTA Style Customization Controls ══ -->
    <aside class="studio-controls" aria-label="Trainer customization controls">
      <div class="studio-controls-head">
        <span class="studio-kicker">Locker Room Vault</span>
        <h2>Customise Coach</h2>
        <p>Switch workout tops, shorts, tracksuits, accessories and energy rings.</p>
      </div>

      <!-- Live Equipped HUD summary -->
      <div class="studio-selection" id="studioSelection" aria-live="polite"></div>

      <!-- Free Fire Style Category Navigation Bar -->
      <div class="st-tabs-nav" role="tablist" aria-label="Customization categories">
        <button type="button" class="st-tab-btn active" data-tab="presets" role="tab" aria-selected="true"><span class="tab-icon">🥋</span> Presets</button>
        <button type="button" class="st-tab-btn" data-tab="tops" role="tab" aria-selected="false"><span class="tab-icon">👕</span> Tops</button>
        <button type="button" class="st-tab-btn" data-tab="bottoms" role="tab" aria-selected="false"><span class="tab-icon">🩳</span> Bottoms</button>
        <button type="button" class="st-tab-btn" data-tab="shoes" role="tab" aria-selected="false"><span class="tab-icon">👟</span> Shoes</button>
        <button type="button" class="st-tab-btn" data-tab="headwear" role="tab" aria-selected="false"><span class="tab-icon">🎧</span> Headwear</button>
        <button type="button" class="st-tab-btn" data-tab="gear" role="tab" aria-selected="false"><span class="tab-icon">⚡</span> Gear</button>
        <button type="button" class="st-tab-btn" data-tab="auras" role="tab" aria-selected="false"><span class="tab-icon">✨</span> Auras</button>
        <button type="button" class="st-tab-btn" data-tab="poses" role="tab" aria-selected="false"><span class="tab-icon">🏃</span> Poses</button>
      </div>

      <!-- Tab Content Panels -->
      <div class="st-tab-panels">
        <!-- Tab 1: Presets & Body Archetypes -->
        <div class="st-panel active" id="panelPresets" role="tabpanel">
          <div class="studio-control-groups">
            <div id="studioTrainers"></div>
            <div id="studioOutfits"></div>
          </div>
        </div>

        <!-- Tab 2: Tops / Shirts -->
        <div class="st-panel" id="panelTops" role="tabpanel">
          <div class="st-panel-header">
            <span class="st-group-label">Workout Tops &amp; Apparel</span>
            <p class="st-panel-sub">Modular 3D upper-body activewear.</p>
          </div>
          <div class="st-gear-grid" id="studioTops"></div>
        </div>

        <!-- Tab 3: Bottoms / Shorts / Pants -->
        <div class="st-panel" id="panelBottoms" role="tabpanel">
          <div class="st-panel-header">
            <span class="st-group-label">Workout Shorts &amp; Pants</span>
            <p class="st-panel-sub">Cut for functional lifting, squats, and agility.</p>
          </div>
          <div class="st-gear-grid" id="studioBottoms"></div>
        </div>

        <!-- Tab 4: Shoes -->
        <div class="st-panel" id="panelShoes" role="tabpanel">
          <div class="st-panel-header">
            <span class="st-group-label">Footwear</span>
            <p class="st-panel-sub">High-traction training footwear.</p>
          </div>
          <div class="st-gear-grid" id="studioShoes"></div>
        </div>

        <!-- Tab 5: Headwear -->
        <div class="st-panel" id="panelHeadwear" role="tabpanel">
          <div class="st-panel-header">
            <span class="st-group-label">Headwear &amp; Acoustic Audio</span>
            <p class="st-panel-sub">Mounts cleanly to cranial bones with zero mesh clipping.</p>
          </div>
          <div class="st-gear-grid" id="studioHeadwear"></div>
        </div>

        <!-- Tab 6: Gear (Belt, Watch, Wraps, Dumbbells) -->
        <div class="st-panel" id="panelGear" role="tabpanel">
          <div class="st-panel-header">
            <span class="st-group-label">Olympic &amp; Biometric Gear</span>
            <p class="st-panel-sub">Skeletal bone-mounted training gear.</p>
          </div>
          <div class="st-gear-grid" id="studioGear"></div>
        </div>

        <!-- Tab 7: VFX Auras -->
        <div class="st-panel" id="panelAuras" role="tabpanel">
          <div class="st-panel-header">
            <span class="st-group-label">VFX Energy Rings</span>
            <p class="st-panel-sub">Dynamic Three.js rings synchronized to workout cadence.</p>
          </div>
          <div class="st-gear-grid" id="studioAuras"></div>
        </div>

        <!-- Tab 8: Exercise Poses -->
        <div class="st-panel" id="panelPoses" role="tabpanel">
          <div class="st-panel-header">
            <span class="st-group-label">Movement Animations</span>
            <p class="st-panel-sub">Test procedural kinematics in real-time.</p>
          </div>
          <div class="st-gear-grid" id="studioPosesTab"></div>
        </div>
      </div>

      <!-- Actions -->
      <div class="studio-actions">
        <a id="studioDone" class="cca-btn cca-btn-primary" href="<?= e($backUrl) ?>"
           style="flex:1 1 180px;text-align:center;text-decoration:none">Save &amp; Continue <span aria-hidden="true">→</span></a>
        <button type="button" id="studioReset" class="cca-btn cca-btn-ghost" style="flex:0 0 auto">Reset</button>
      </div>
      <p class="studio-save-note" style="margin:0;font:500 11.5px/1.6 system-ui;color:var(--muted,#8b93a1)">
        <span aria-hidden="true">✓</span> Saved locally. Your custom 3D clothes and gear carry directly into live workout sessions.
      </p>
    </aside>

    <!-- ══ RIGHT: 3D Model Viewport & Showcase Pedestal (Free Fire Style) ══ -->
    <div class="studio-preview-wrap">
      <div class="studio-preview-head">
        <div>
          <span class="studio-kicker"><i></i> Live 3D Viewport</span>
          <h2>Your Training Partner</h2>
        </div>
        <div class="studio-head-badges">
          <span class="studio-badge-fps" id="studioFPS">60 FPS · PBR</span>
          <span class="studio-drag-hint">Drag to rotate · Wheel to zoom</span>
        </div>
      </div>
      
      <div id="studioStage" class="studio-stage"
           style="position:relative;border-radius:24px;overflow:hidden;border:1px solid var(--line,rgba(255,255,255,.12));
                  background:radial-gradient(120% 90% at 50% 0%,#1b1f28 0%,#0f1116 60%,#0b0d11 100%);
                  aspect-ratio:4/5;min-height:440px;max-height:min(76vh,720px)">
        <div id="studioLoading" class="studio-loading"
             style="position:absolute;inset:0;display:grid;place-items:center;font:700 11px/1 ui-monospace,monospace;
                    letter-spacing:3px;text-transform:uppercase;color:#FF6B1A">Loading trainer…</div>
        
        <!-- Bottom-left: Active Character Title HUD -->
        <div id="studioName" class="studio-name"
             style="position:absolute;left:14px;bottom:14px;font:900 13px/1 system-ui;color:#fff;
                    text-shadow:0 2px 10px rgba(0,0,0,.75);pointer-events:none;z-index:2"></div>

        <!-- Top bar: Pose quick switchers -->
        <div id="studioPose" class="studio-pose"
             style="position:absolute;left:10px;right:10px;top:10px;display:flex;gap:6px;
                    flex-wrap:wrap;justify-content:center;z-index:2"></div>

        <!-- Bottom-right: Turntable Auto-Rotate Button -->
        <button type="button" id="studioSpinBtn" class="studio-spin-btn active" title="Toggle 360° Turntable Rotation">
          <span class="spin-icon">🔄</span> <span class="spin-label">Auto Spin</span>
        </button>
      </div>

      <p class="studio-preview-note" style="margin-top:10px;font:500 12px/1.5 system-ui;color:var(--muted,#8b93a1)">
        Kinematics updated: arms and hands stay cleanly in front during squats, bicep curls, and warm-ups. Zero backwards clipping.
      </p>
    </div>

  </div>
</section>

<style>
/* ══ Free Fire & GTA Style Grid Layout ══ */
.studio-page{max-width:1380px;margin:0 auto;padding:10px clamp(16px,3vw,34px) 84px}
#studioGrid{display:grid;gap:clamp(20px,3vw,36px);grid-template-columns:minmax(0,1fr)}
@media (min-width: 960px){
  /* Free Fire layout: Controls on LEFT (460px), Model Viewport on RIGHT */
  #studioGrid{grid-template-columns:460px minmax(0,1fr);align-items:start}
  .studio-controls{position:sticky;top:calc(var(--nav-h, 72px) + 16px)}
}
@media (max-width: 959px){
  /* Mobile / tablet: Stage on top, controls below */
  #studioGrid{display:flex;flex-direction:column-reverse}
}

.studio-preview-head{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;margin:0 2px 14px}
.studio-preview-head h2, .studio-controls h2{margin:5px 0 0;color:var(--text-1,#fff);font:700 24px/1.1 var(--font-tech, system-ui);letter-spacing:.01em}
.studio-kicker{display:flex;align-items:center;gap:7px;color:var(--primary-text,#FF6B1A);font:800 10.5px/1 var(--font-tech, monospace);letter-spacing:.15em;text-transform:uppercase}
.studio-kicker i{width:7px;height:7px;border-radius:50%;background:#10B981;box-shadow:0 0 0 4px rgba(16,185,129,.2)}
.studio-head-badges{display:flex;align-items:center;gap:10px}
.studio-badge-fps{padding:4px 8px;border-radius:6px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);color:var(--text-3,#9ca3af);font:700 10px/1 ui-monospace,monospace;letter-spacing:.08em}
.studio-drag-hint{color:var(--text-3,#8b93a1);font:700 11px var(--font-tech, system-ui);letter-spacing:.06em;text-transform:uppercase}

/* ══ 3D Stage Container ══ */
.studio-stage{border-color:color-mix(in srgb,var(--primary,#FF6B1A) 24%,var(--line,rgba(255,255,255,.1)))!important;border-radius:24px!important;box-shadow:0 28px 60px rgba(0,0,0,.45);isolation:isolate}
.studio-stage::before{content:"";position:absolute;z-index:0;inset:54% -25% -30%;background:linear-gradient(rgba(255,255,255,.07) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.07) 1px,transparent 1px);background-size:32px 32px;transform:perspective(360px) rotateX(58deg);mask-image:linear-gradient(to bottom,transparent,black 52%);pointer-events:none}
.studio-stage canvas{z-index:1;position:absolute}
.studio-loading{z-index:4!important;background:radial-gradient(circle,rgba(20,24,32,.85),rgba(8,10,14,.95));font-family:var(--font-tech, monospace)!important;letter-spacing:.18em!important}
.studio-loading::before{content:"";position:absolute;width:42px;height:42px;border:2px solid color-mix(in srgb,var(--primary,#FF6B1A) 22%,transparent);border-top-color:var(--primary,#FF6B1A);border-radius:50%;animation:studioSpin .9s linear infinite}
@keyframes studioSpin{to{transform:rotate(360deg)}}

.studio-name{left:16px!important;bottom:16px!important;z-index:3!important;padding:8px 14px;border:1px solid rgba(255,255,255,.16);border-radius:12px;background:rgba(7,9,13,.75);backdrop-filter:blur(12px);font-family:var(--font-tech, system-ui)!important;letter-spacing:.03em;max-width:calc(100% - 140px)}
.studio-pose{left:12px!important;right:12px!important;top:12px!important;gap:6px!important;z-index:3!important}

/* Auto-spin turntable button */
.studio-spin-btn{position:absolute;right:14px;bottom:14px;z-index:3;display:inline-flex;align-items:center;gap:6px;padding:7px 13px;border-radius:999px;border:1px solid rgba(255,255,255,.16);background:rgba(10,12,16,.75);backdrop-filter:blur(10px);color:#e2e8f0;font:700 11px/1 var(--font-tech, system-ui);letter-spacing:.04em;cursor:pointer;transition:.18s}
.studio-spin-btn:hover{border-color:#FF6B1A;background:rgba(255,107,26,.22);color:#fff}
.studio-spin-btn.active{border-color:#FF6B1A;background:linear-gradient(135deg,rgba(255,107,26,.32),rgba(255,184,0,.15));color:#fff;box-shadow:0 0 14px rgba(255,107,26,.35)}
.studio-spin-btn.active .spin-icon{display:inline-block;animation:studioSpin 4s linear infinite}

/* ══ Controls Container ══ */
.studio-controls{display:flex;flex-direction:column;gap:16px;padding:22px;border:1px solid color-mix(in srgb,var(--primary,#FF6B1A) 16%,var(--line,rgba(255,255,255,.12)));border-radius:24px;background:linear-gradient(150deg,rgba(24,28,38,.85),rgba(12,14,20,.95));backdrop-filter:blur(16px);box-shadow:0 20px 40px rgba(0,0,0,.35)}
.studio-controls-head p{margin:6px 0 0;color:var(--text-2,#9ca3af);font-size:13.5px;line-height:1.55}

/* Equipped HUD summary bar */
.studio-selection{padding:12px 14px;border:1px solid color-mix(in srgb,var(--primary,#FF6B1A) 24%,rgba(255,255,255,.1));border-radius:14px;background:rgba(255,107,26,.06);color:var(--text-1,#fff);font:600 13px/1.45 var(--font-tech, system-ui)}
.studio-selection-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;font-size:10px;text-transform:uppercase;letter-spacing:.15em;color:#FF6B1A;font-weight:800}
.studio-selection-name{font:800 15px/1.2 var(--font-tech, system-ui);color:#fff;margin-bottom:6px}
.studio-selection-chips{display:flex;flex-wrap:wrap;gap:5px}
.st-loadout-chip{display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border-radius:999px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);color:#cbd5e1;font:650 10.5px/1 var(--font-tech, system-ui)}

/* ══ Category Tabs (Free Fire Horizontal Navigation) ══ */
.st-tabs-nav{display:flex;gap:4px;padding:5px;background:rgba(0,0,0,.45);border-radius:14px;border:1px solid rgba(255,255,255,.08);overflow-x:auto;scrollbar-width:none}
.st-tabs-nav::-webkit-scrollbar{display:none}
.st-tab-btn{flex:1 0 auto;display:inline-flex;align-items:center;justify-content:center;gap:5px;padding:7px 10px;border-radius:10px;border:1px solid transparent;background:transparent;color:var(--text-2,#94a3b8);font:700 11px/1 var(--font-tech, system-ui);cursor:pointer;transition:all .18s ease;text-transform:uppercase;letter-spacing:.04em}
.st-tab-btn:hover{color:#fff;background:rgba(255,255,255,.06)}
.st-tab-btn.active{color:#fff;background:linear-gradient(135deg,rgba(255,107,26,.36),rgba(255,184,0,.15));border-color:#FF6B1A;box-shadow:0 4px 14px rgba(255,107,26,.25)}

/* Tab Panels */
.st-tab-panels{min-height:240px}
.st-panel{display:none}
.st-panel.active{display:block;animation:stFadeIn .22s cubic-bezier(0.16,1,0.3,1)}
@keyframes stFadeIn{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:translateY(0)}}
.st-panel-header{margin-bottom:12px}
.st-panel-sub{margin:3px 0 0;font:500 11.5px/1.4 system-ui;color:var(--muted,#8b93a1)}

/* ══ Gear Item Cards (AAA Free Fire / GTA Locker Room) ══ */
.st-gear-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:9px}
.st-gear-card{display:flex;flex-direction:column;gap:5px;padding:11px;border-radius:14px;border:1px solid rgba(255,255,255,.1);background:rgba(255,255,255,.03);color:var(--text-1,#dfe3ea);cursor:pointer;transition:all .2s cubic-bezier(0.16,1,0.3,1);text-align:left;position:relative;overflow:hidden}
.st-gear-card:hover{transform:translateY(-2px);border-color:rgba(255,107,26,.45);background:rgba(255,255,255,.05);box-shadow:0 8px 20px rgba(0,0,0,.3)}

/* Rarity Accents */
.st-gear-card[data-rarity="common"] .st-badge-rarity{color:#9ca3af;border-color:rgba(156,163,175,.3);background:rgba(156,163,175,.1)}
.st-gear-card[data-rarity="rare"] .st-badge-rarity{color:#38bdf8;border-color:rgba(56,189,248,.4);background:rgba(56,189,248,.12)}
.st-gear-card[data-rarity="epic"] .st-badge-rarity{color:#c084fc;border-color:rgba(192,132,252,.4);background:rgba(192,132,252,.14)}
.st-gear-card[data-rarity="legendary"] .st-badge-rarity{color:#f59e0b;border-color:rgba(245,158,11,.5);background:rgba(245,158,11,.18);font-weight:800}

.st-gear-card-top{display:flex;align-items:center;justify-content:space-between;width:100%}
.st-badge-rarity{padding:2px 5px;border-radius:5px;border:1px solid;font:800 8.5px/1 ui-monospace,monospace;letter-spacing:.1em;text-transform:uppercase}
.st-gear-icon{font-size:20px;line-height:1}
.st-gear-name{font:800 13px/1.2 var(--font-tech, system-ui);color:#fff;letter-spacing:.01em}
.st-gear-type{font:700 10px/1 var(--font-tech, monospace);color:var(--text-3,#9ca3af);text-transform:uppercase;letter-spacing:.06em}
.st-gear-desc{font:500 10.5px/1.35 system-ui;color:var(--text-2,#9ca3af);opacity:.85}

.st-gear-card-bottom{display:flex;align-items:center;justify-content:space-between;margin-top:auto;padding-top:6px;border-top:1px solid rgba(255,255,255,.06);font:700 10px/1 var(--font-tech, system-ui);letter-spacing:.04em}
.st-gear-status{display:inline-flex;align-items:center;gap:3px;color:var(--text-3,#8b93a1)}

/* Active / Equipped Gear Card */
.st-gear-card[aria-pressed="true"]{border-color:#FF6B1A;background:linear-gradient(135deg,rgba(255,107,26,.26),rgba(255,184,0,.09));box-shadow:0 0 0 1px #FF6B1A, 0 8px 20px rgba(255,107,26,.22)}
.st-gear-card[aria-pressed="true"] .st-gear-status{color:#10B981;font-weight:800}
.st-gear-card[aria-pressed="true"][data-rarity="legendary"]{border-color:#f59e0b;background:linear-gradient(135deg,rgba(245,158,11,.3),rgba(255,107,26,.12));box-shadow:0 0 0 1px #f59e0b, 0 8px 24px rgba(245,158,11,.28)}

/* Legacy Trainer & Kit Card styles */
.st-group-label{font:800 10px/1 ui-monospace,monospace;letter-spacing:2.2px;text-transform:uppercase;color:#FF6B1A;margin-bottom:8px;display:block}
.st-row{display:flex;flex-wrap:wrap;gap:8px}
.st-card{display:flex;flex-direction:column;gap:3px;align-items:flex-start;padding:11px 13px;border-radius:14px;border:1px solid var(--line,rgba(255,255,255,.12));background:rgba(255,255,255,.04);color:var(--fg,#dfe3ea);cursor:pointer;flex:1 1 140px;transition:.16s;text-align:left}
.st-card:hover{border-color:rgba(255,107,26,.5);transform:translateY(-1px)}
.st-card[aria-pressed="true"]{border-color:#FF6B1A;background:linear-gradient(135deg,rgba(255,107,26,.26),rgba(255,184,0,.09));color:#fff;box-shadow:inset 3px 0 0 #FF6B1A}
.st-card b{font:800 14px/1.1 var(--font-tech, system-ui)}
.st-card span{font:500 11px/1.35 system-ui;opacity:.75}

.st-kit{display:flex;align-items:center;gap:8px;padding:7px 12px;border-radius:999px;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.04);color:#dfe3ea;font:700 12px/1 var(--font-tech, system-ui);cursor:pointer;transition:.16s}
.st-kit:hover{border-color:rgba(255,107,26,.5)}
.st-kit[aria-pressed="true"]{border-color:#FF6B1A;background:rgba(255,107,26,.22);color:#fff;font-weight:800}
.st-kit i{width:18px;height:18px;border-radius:50%;display:block;border:2px solid rgba(255,255,255,.28)}

.st-pose{padding:6px 11px;border-radius:999px;border:1px solid rgba(255,255,255,.16);background:rgba(10,12,16,.68);color:#cfd5df;font:700 10px/1 ui-monospace,monospace;letter-spacing:1.2px;text-transform:uppercase;cursor:pointer;backdrop-filter:blur(6px);transition:.16s}
.st-pose:hover,.st-pose[aria-pressed="true"]{border-color:#FF6B1A;color:#fff;background:rgba(255,107,26,.32)}

.studio-actions{display:flex;gap:10px;flex-wrap:wrap;padding-top:4px}
.studio-actions .cca-btn{min-height:48px}
.studio-save-note{padding-top:14px;border-top:1px solid var(--line,rgba(255,255,255,.1));color:var(--text-3,#8b93a1)!important}
.studio-save-note span{color:#10B981;font-weight:800}

@media (max-width: 640px){
  .studio-page{padding-inline:14px;padding-bottom:56px}
  .studio-preview-head{align-items:flex-start;flex-direction:column;gap:8px}
  .studio-stage{min-height:360px!important;border-radius:20px!important}
  .studio-controls{padding:18px;border-radius:18px}
  .studio-drag-hint{display:none}
  .st-card{flex-basis:100%}
  .st-gear-grid{grid-template-columns:1fr 1fr}
}
</style>

<!-- Scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/GLTFLoader.js"></script>
<script src="<?= asset('js/titan-rig.js') ?>"></script>
<script src="<?= asset('js/cca-gear-engine.js') ?>"></script>
<script src="<?= asset('js/cca-wardrobe.js') ?>"></script>
<script>
(function () {
  'use strict';
  var W = window.CCAWardrobe;
  var G = window.CCAGearEngine;
  var stage = document.getElementById('studioStage');

  if (!W || !stage || typeof THREE === 'undefined' || typeof THREE.GLTFLoader !== 'function') {
    var l = document.getElementById('studioLoading');
    if (l) l.textContent = '3D preview unavailable in this browser';
    return;
  }

  var ld = W.load();
  var scene, cam, rend, root = null, raf = null, yaw = 0.4, dist = 1.0, clock = new THREE.Clock();
  var pose = 'warmup';
  var autoRotate = true;
  var audioCtx = null;

  /* ══ Web Audio Acoustic Feedback ══ */
  function playEquipSound(rarity) {
    try {
      if (!audioCtx) {
        audioCtx = new (window.AudioContext || window.webkitAudioContext)();
      }
      if (audioCtx.state === 'suspended') {
        audioCtx.resume();
      }
      var now = audioCtx.currentTime;
      var osc = audioCtx.createOscillator();
      var gain = audioCtx.createGain();
      osc.connect(gain);
      gain.connect(audioCtx.destination);

      if (rarity === 'legendary') {
        osc.type = 'triangle';
        osc.frequency.setValueAtTime(523.25, now);
        osc.frequency.exponentialRampToValueAtTime(1046.5, now + 0.16);
        gain.gain.setValueAtTime(0.12, now);
        gain.gain.exponentialRampToValueAtTime(0.001, now + 0.22);
        osc.start(now);
        osc.stop(now + 0.22);
      } else if (rarity === 'epic') {
        osc.type = 'sine';
        osc.frequency.setValueAtTime(440, now);
        osc.frequency.exponentialRampToValueAtTime(880, now + 0.12);
        gain.gain.setValueAtTime(0.1, now);
        gain.gain.exponentialRampToValueAtTime(0.001, now + 0.15);
        osc.start(now);
        osc.stop(now + 0.15);
      } else if (rarity === 'rare') {
        osc.type = 'sine';
        osc.frequency.setValueAtTime(587.33, now);
        osc.frequency.exponentialRampToValueAtTime(880, now + 0.08);
        gain.gain.setValueAtTime(0.08, now);
        gain.gain.exponentialRampToValueAtTime(0.001, now + 0.1);
        osc.start(now);
        osc.stop(now + 0.1);
      } else {
        osc.type = 'triangle';
        osc.frequency.setValueAtTime(320, now);
        osc.frequency.exponentialRampToValueAtTime(160, now + 0.05);
        gain.gain.setValueAtTime(0.06, now);
        gain.gain.exponentialRampToValueAtTime(0.001, now + 0.06);
        osc.start(now);
        osc.stop(now + 0.06);
      }
    } catch (e) {}
  }

  /* ══ Three.js Scene Setup ══ */
  scene = new THREE.Scene();
  cam = new THREE.PerspectiveCamera(36, 4 / 5, 0.1, 60);
  rend = new THREE.WebGLRenderer({ antialias: true, alpha: true });
  rend.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
  stage.appendChild(rend.domElement);
  rend.domElement.style.cssText = 'position:absolute;inset:0;width:100%;height:100%;display:block';
  
  // High-contrast PBR lighting
  scene.add(new THREE.HemisphereLight(0xffffff, 0x1b1f28, 1.15));
  var key = new THREE.DirectionalLight(0xffffff, 2.2); key.position.set(-2.2, 3.2, 3.0); scene.add(key);
  var fill = new THREE.DirectionalLight(0xffd9b0, 0.9); fill.position.set(2.6, 1.4, -2.2); scene.add(fill);
  var rim = new THREE.DirectionalLight(0x9dc4ff, 0.75); rim.position.set(0, 2.4, -3.4); scene.add(rim);

  /* ══ Turntable Circular Pedestal ══ */
  var pedestalGroup = new THREE.Group();
  var diskGeo = new THREE.CylinderGeometry(1.1, 1.2, 0.04, 48);
  var diskMat = new THREE.MeshStandardMaterial({
    color: 0x12141a,
    roughness: 0.6,
    metalness: 0.8
  });
  var disk = new THREE.Mesh(diskGeo, diskMat);
  disk.position.y = -0.02;
  pedestalGroup.add(disk);

  var neonRingGeo = new THREE.RingGeometry(1.11, 1.16, 48);
  var neonRingMat = new THREE.MeshBasicMaterial({
    color: 0xFF6B1A,
    side: THREE.DoubleSide
  });
  var neonRing = new THREE.Mesh(neonRingGeo, neonRingMat);
  neonRing.rotation.x = -Math.PI / 2;
  neonRing.position.y = 0.002;
  pedestalGroup.add(neonRing);

  var innerGridGeo = new THREE.RingGeometry(0.55, 0.57, 36);
  var innerGridMat = new THREE.MeshBasicMaterial({
    color: 0xFF9E58,
    transparent: true,
    opacity: 0.35,
    side: THREE.DoubleSide
  });
  var innerGrid = new THREE.Mesh(innerGridGeo, innerGridMat);
  innerGrid.rotation.x = -Math.PI / 2;
  innerGrid.position.y = 0.002;
  pedestalGroup.add(innerGrid);

  scene.add(pedestalGroup);

  function resize() {
    var w = stage.clientWidth || 400, h = stage.clientHeight || 500;
    rend.setSize(w, h, false);
    cam.aspect = w / h; cam.updateProjectionMatrix();
  }
  window.addEventListener('resize', resize);

  var FIT_H = 2.2;
  function boneSpan(o) {
    o.updateMatrixWorld(true);
    var top = null, bot = null, cx = 0, cz = 0, n = 0, p = new THREE.Vector3();
    o.traverse(function (b) {
      if (!b.isBone) return;
      b.getWorldPosition(p);
      if (!isFinite(p.y)) return;
      if (top === null || p.y > top) top = p.y;
      if (bot === null || p.y < bot) bot = p.y;
      cx += p.x; cz += p.z; n++;
    });
    if (!n) return null;
    return { top: top, bottom: bot, height: top - bot, cx: cx / n, cz: cz / n };
  }

  function fitByBones(o, targetH) {
    var s = boneSpan(o);
    if (!s || s.height <= 1e-9) {
      if (window.CCARig && CCARig.fit) CCARig.fit(o, targetH);
      return;
    }
    for (var i = 0; i < 8; i++) {
      var cur = boneSpan(o);
      if (!cur || cur.height <= 1e-9) break;
      var err = targetH / (cur.height * 1.04);
      if (Math.abs(err - 1) < 0.004) break;
      o.scale.multiplyScalar(err);
      o.updateMatrixWorld(true);
    }
    var f = boneSpan(o);
    if (f) {
      o.position.x -= f.cx;
      o.position.z -= f.cz;
      o.position.y -= f.bottom;
      o.updateMatrixWorld(true);
    }
  }

  function placeCamera() {
    cam.position.set(0, FIT_H * 0.60, FIT_H * 1.55 * dist);
    cam.lookAt(0, FIT_H * 0.52, 0);
  }

  /* ══ RAF Render Loop ══ */
  function loop() {
    raf = requestAnimationFrame(loop);
    if (stage.offsetParent === null || document.hidden) return;
    var t = clock.getElapsedTime();
    if (autoRotate) {
      yaw += 0.007;
    }
    if (root) {
      root.rotation.y = yaw;
      if (G && G.update) {
        G.update(t, root);
      }
    }
    if (window.CCARig && CCARig.attached && CCARig.update) CCARig.update(pose, t);
    rend.render(scene, cam);
  }

  function loadTrainer() {
    var t = W.trainerById(ld.trainer);
    document.getElementById('studioLoading').style.display = 'grid';
    document.getElementById('studioLoading').textContent = 'Equipping ' + t.name + '…';
    W.resetBaseMap();
    var modelPath = '../' + t.file;
    new THREE.GLTFLoader().load(modelPath, function (g) {
      try {
        if (root) scene.remove(root);
        root = g.scene;
        W.uncull(root);
        if (window.CCARig && CCARig.styleModel) CCARig.styleModel(root);
        fitByBones(root, FIT_H);
        scene.add(root);
        if (window.CCARig && CCARig.attach) CCARig.attach(root);
        try {
          W.apply(root, ld);
        } catch (we) {
          console.error('[studio] W.apply error:', we);
        }
        resize(); placeCamera();
        document.getElementById('studioLoading').style.display = 'none';
        updateStudioNameHUD();
        if (!raf) loop();
      } catch (innerErr) {
        console.error('[studio] Error inside onLoad:', innerErr);
      }
    }, undefined, function (e) {
      console.warn('[studio] model failed, trying fallback...', e);
      if (t.fallbackFile) {
        new THREE.GLTFLoader().load('../' + t.fallbackFile, function (g) {
          if (root) scene.remove(root);
          root = g.scene;
          W.uncull(root);
          if (window.CCARig && CCARig.styleModel) CCARig.styleModel(root);
          fitByBones(root, FIT_H);
          scene.add(root);
          if (window.CCARig && CCARig.attach) CCARig.attach(root);
          W.apply(root, ld);
          resize(); placeCamera();
          document.getElementById('studioLoading').style.display = 'none';
          updateStudioNameHUD();
          if (!raf) loop();
        });
      } else {
        document.getElementById('studioLoading').textContent = 'Could not load this trainer';
      }
    });
  }

  /* ══ Auto Spin Turntable Button ══ */
  var spinBtn = document.getElementById('studioSpinBtn');
  if (spinBtn) {
    spinBtn.onclick = function () {
      autoRotate = !autoRotate;
      spinBtn.classList.toggle('active', autoRotate);
      var label = spinBtn.querySelector('.spin-label');
      if (label) label.textContent = autoRotate ? 'Auto Spin' : 'Paused';
    };
  }

  /* ══ Drag to Spin, Wheel to Zoom ══ */
  var drag = null;
  stage.addEventListener('pointerdown', function (e) {
    drag = e.clientX;
    autoRotate = false;
    if (spinBtn) {
      spinBtn.classList.remove('active');
      var label = spinBtn.querySelector('.spin-label');
      if (label) label.textContent = 'Paused';
    }
    stage.setPointerCapture(e.pointerId);
  });
  stage.addEventListener('pointermove', function (e) {
    if (drag === null) return;
    yaw += (e.clientX - drag) * 0.011;
    drag = e.clientX;
  });
  ['pointerup', 'pointercancel'].forEach(function (ev) {
    stage.addEventListener(ev, function () { drag = null; });
  });
  stage.addEventListener('wheel', function (e) {
    e.preventDefault();
    dist = Math.min(1.6, Math.max(0.62, dist + (e.deltaY > 0 ? 0.07 : -0.07)));
    placeCamera();
  }, { passive: false });

  /* ══ Free Fire Category Tab Switching ══ */
  var tabButtons = document.querySelectorAll('.st-tab-btn');
  var panels = {
    presets: document.getElementById('panelPresets'),
    tops: document.getElementById('panelTops'),
    bottoms: document.getElementById('panelBottoms'),
    shoes: document.getElementById('panelShoes'),
    headwear: document.getElementById('panelHeadwear'),
    gear: document.getElementById('panelGear'),
    auras: document.getElementById('panelAuras'),
    poses: document.getElementById('panelPoses')
  };

  tabButtons.forEach(function (btn) {
    btn.addEventListener('click', function () {
      var targetTab = btn.getAttribute('data-tab');
      tabButtons.forEach(function (b) {
        var isTarget = b === btn;
        b.classList.toggle('active', isTarget);
        b.setAttribute('aria-selected', isTarget ? 'true' : 'false');
      });
      Object.keys(panels).forEach(function (key) {
        if (panels[key]) {
          panels[key].classList.toggle('active', key === targetTab);
        }
      });
    });
  });

  /* ══ HUD & Summary Readout ══ */
  function updateStudioNameHUD() {
    var t = W.trainerById(ld.trainer);
    var nameEl = document.getElementById('studioName');
    if (!nameEl) return;
    var extrasCount = 0;
    ['headphones', 'visor', 'headband', 'belt', 'watch', 'wraps', 'dumbbells'].forEach(function (k) {
      if (ld[k]) extrasCount++;
    });
    if (ld.aura) extrasCount++;
    nameEl.textContent = t.name + ' · ' + (ld.top === 'tank' ? 'Gym Tank' : 'Hoodie') + (extrasCount ? ' (' + extrasCount + ' Gear Equipped)' : '');
  }

  function updateSelectionHUD() {
    var mount = document.getElementById('studioSelection');
    if (!mount) return;
    var trainer = W.trainerById(ld.trainer);
    var outfit = W.outfitsFor(ld.trainer).filter(function (o) { return o.id === ld.outfit; })[0];
    
    var chips = [];
    if (ld.top === 'tank') chips.push('<span class="st-loadout-chip" style="border-color:#FF6B1A;color:#FF6B1A">👕 Gym Tank</span>');
    else chips.push('<span class="st-loadout-chip">👕 Hoodie</span>');

    if (ld.bottom === 'shorts') chips.push('<span class="st-loadout-chip" style="border-color:#FF6B1A;color:#FF6B1A">🩳 Lifting Shorts</span>');
    else chips.push('<span class="st-loadout-chip">🩳 Trackpants</span>');

    if (ld.shoes === 'hightops') chips.push('<span class="st-loadout-chip">👟 High-Tops</span>');

    if (ld.cap && ld.trainer === 'street') chips.push('<span class="st-loadout-chip">🧢 Cap</span>');
    if (ld.headphones) chips.push('<span class="st-loadout-chip">🎧 Cyber Audio</span>');
    if (ld.visor) chips.push('<span class="st-loadout-chip">🥽 HUD Visor</span>');
    if (ld.headband) chips.push('<span class="st-loadout-chip">🥋 Sweatband</span>');
    if (ld.belt) chips.push('<span class="st-loadout-chip">🏆 Olympic Belt</span>');
    if (ld.watch) chips.push('<span class="st-loadout-chip">⌚ Smartwatch</span>');
    if (ld.wraps) chips.push('<span class="st-loadout-chip">🥊 Wrist Wraps</span>');
    if (ld.dumbbells) chips.push('<span class="st-loadout-chip">🏋️ Dumbbells</span>');
    if (ld.aura) {
      var auraName = ld.aura === 'cyber' ? 'Cyber Pulse' : (ld.aura === 'ember' ? 'Ember Fury' : 'Lightning Surge');
      chips.push('<span class="st-loadout-chip" style="border-color:#FF6B1A;color:#FF6B1A">✨ ' + auraName + '</span>');
    }

    mount.innerHTML = 
      '<div class="studio-selection-header"><span>Active Loadout</span><span>Synchronized</span></div>' +
      '<div class="studio-selection-name">' + trainer.name + ' <span style="opacity:0.6">·</span> ' + (outfit ? outfit.name : 'Signature Kit') + '</div>' +
      '<div class="studio-selection-chips">' + (chips.length ? chips.join('') : '<span class="st-loadout-chip" style="opacity:0.6">Standard Apparel</span>') + '</div>';
    
    updateStudioNameHUD();
  }

  /* ══ Presets Tab ══ */
  function group(mount, label) {
    mount.innerHTML = '';
    var l = document.createElement('span'); l.className = 'st-group-label'; l.textContent = label;
    var r = document.createElement('div'); r.className = 'st-row';
    mount.appendChild(l); mount.appendChild(r);
    return r;
  }

  function renderTrainers() {
    var row = group(document.getElementById('studioTrainers'), 'Character Body Archetype');
    W.TRAINERS.forEach(function (t) {
      var b = document.createElement('button');
      b.type = 'button'; b.className = 'st-card';
      b.setAttribute('aria-pressed', ld.trainer === t.id ? 'true' : 'false');
      b.innerHTML = '<b>' + t.name + '</b><span>' + t.build + '</span><span>' + t.wears + '</span>';
      b.onclick = function () {
        if (ld.trainer === t.id) return;
        ld.trainer = t.id;
        ld.outfit = '';
        W.save(ld);
        playEquipSound('epic');
        renderTrainers();
        renderOutfits();
        renderTops();
        renderBottoms();
        renderHeadwear();
        updateSelectionHUD();
        loadTrainer();
      };
      row.appendChild(b);
    });
  }

  function renderOutfits() {
    var row = group(document.getElementById('studioOutfits'), 'Signature Colorway');
    W.outfitsFor(ld.trainer).forEach(function (o) {
      var b = document.createElement('button');
      b.type = 'button'; b.className = 'st-kit';
      b.setAttribute('aria-pressed', (ld.outfit || '') === o.id ? 'true' : 'false');
      b.innerHTML = '<i style="background:' + o.hex + '"></i>' + o.name;
      b.onclick = function () {
        ld.outfit = o.id;
        W.save(ld);
        playEquipSound('rare');
        renderOutfits();
        updateSelectionHUD();
        if (root) W.apply(root, ld);
      };
      row.appendChild(b);
    });
  }

  /* ══ Tops (Workout Shirts / Tanks / Hoodies) ══ */
  var TOPS = [
    {
      id: 'hoodie',
      name: 'Oversized Street Hoodie',
      type: 'Streetwear Top',
      rarity: 'common',
      icon: '🧥',
      desc: 'Comfortable heavyweight cotton hoodie with full athletic sleeves.'
    },
    {
      id: 'tank',
      name: 'Gym Stringer Tank',
      type: 'Athletic Tank',
      rarity: 'epic',
      icon: '👕',
      desc: 'Sleeveless deep-cut lifting stringer showing shoulder & arm definition.'
    }
  ];

  function renderTops() {
    var grid = document.getElementById('studioTops');
    if (!grid) return;
    grid.innerHTML = '';

    TOPS.forEach(function (item) {
      var card = document.createElement('div');
      card.className = 'st-gear-card';
      card.setAttribute('data-rarity', item.rarity);
      var isEquipped = (ld.top || 'hoodie') === item.id;
      card.setAttribute('aria-pressed', isEquipped ? 'true' : 'false');
      card.innerHTML = 
        '<div class="st-gear-card-top"><span class="st-gear-icon">' + item.icon + '</span><span class="st-badge-rarity">' + item.rarity.toUpperCase() + '</span></div>' +
        '<div class="st-gear-name">' + item.name + '</div>' +
        '<div class="st-gear-type">' + item.type + '</div>' +
        '<div class="st-gear-desc">' + item.desc + '</div>' +
        '<div class="st-gear-card-bottom"><span class="st-gear-status">' + (isEquipped ? '✓ EQUIPPED' : '+ EQUIP') + '</span></div>';
      card.onclick = function () {
        ld.top = item.id;
        W.save(ld);
        playEquipSound(item.rarity);
        renderTops();
        updateSelectionHUD();
        if (root) W.apply(root, ld);
      };
      grid.appendChild(card);
    });
  }

  /* ══ Bottoms (Shorts / Pants) ══ */
  var BOTTOMS = [
    {
      id: 'trackpants',
      name: 'Streetwear Trackpants',
      type: 'Joggers',
      rarity: 'common',
      icon: '👖',
      desc: 'Baggy heavyweight cotton sweatpants with tapered cuffs.'
    },
    {
      id: 'shorts',
      name: 'Olympic Lifting Shorts',
      type: 'Workout Shorts',
      rarity: 'rare',
      icon: '🩳',
      desc: 'Above-the-knee athletic lifting shorts tailored for full squats.'
    }
  ];

  function renderBottoms() {
    var grid = document.getElementById('studioBottoms');
    if (!grid) return;
    grid.innerHTML = '';

    BOTTOMS.forEach(function (item) {
      var card = document.createElement('div');
      card.className = 'st-gear-card';
      card.setAttribute('data-rarity', item.rarity);
      var isEquipped = (ld.bottom || 'trackpants') === item.id;
      card.setAttribute('aria-pressed', isEquipped ? 'true' : 'false');
      card.innerHTML = 
        '<div class="st-gear-card-top"><span class="st-gear-icon">' + item.icon + '</span><span class="st-badge-rarity">' + item.rarity.toUpperCase() + '</span></div>' +
        '<div class="st-gear-name">' + item.name + '</div>' +
        '<div class="st-gear-type">' + item.type + '</div>' +
        '<div class="st-gear-desc">' + item.desc + '</div>' +
        '<div class="st-gear-card-bottom"><span class="st-gear-status">' + (isEquipped ? '✓ EQUIPPED' : '+ EQUIP') + '</span></div>';
      card.onclick = function () {
        ld.bottom = item.id;
        W.save(ld);
        playEquipSound(item.rarity);
        renderBottoms();
        updateSelectionHUD();
        if (root) W.apply(root, ld);
      };
      grid.appendChild(card);
    });
  }

  /* ══ Shoes ══ */
  var SHOES = [
    {
      id: 'hightops',
      name: 'High-Top Court Sneakers',
      type: 'High-Tops',
      rarity: 'rare',
      icon: '👟',
      desc: 'Cushioned high-top basketball sneakers with ankle wrap support.'
    }
  ];

  function renderShoes() {
    var grid = document.getElementById('studioShoes');
    if (!grid) return;
    grid.innerHTML = '';

    SHOES.forEach(function (item) {
      var card = document.createElement('div');
      card.className = 'st-gear-card';
      card.setAttribute('data-rarity', item.rarity);
      var isEquipped = (ld.shoes || 'hightops') === item.id;
      card.setAttribute('aria-pressed', isEquipped ? 'true' : 'false');
      card.innerHTML = 
        '<div class="st-gear-card-top"><span class="st-gear-icon">' + item.icon + '</span><span class="st-badge-rarity">' + item.rarity.toUpperCase() + '</span></div>' +
        '<div class="st-gear-name">' + item.name + '</div>' +
        '<div class="st-gear-type">' + item.type + '</div>' +
        '<div class="st-gear-desc">' + item.desc + '</div>' +
        '<div class="st-gear-card-bottom"><span class="st-gear-status">' + (isEquipped ? '✓ EQUIPPED' : '+ EQUIP') + '</span></div>';
      card.onclick = function () {
        ld.shoes = item.id;
        W.save(ld);
        playEquipSound(item.rarity);
        renderShoes();
        updateSelectionHUD();
        if (root) W.apply(root, ld);
      };
      grid.appendChild(card);
    });
  }

  /* ══ Headwear ══ */
  var HEADWEAR_ITEMS = [
    {
      slot: 'headphones',
      name: 'Cyber Studio Pro',
      type: 'Acoustic Monitors',
      rarity: 'epic',
      icon: '🎧',
      desc: 'Dual 50mm acoustic earcups with glowing neon LED rings.'
    },
    {
      slot: 'visor',
      name: 'HUD Tactical Visor',
      type: 'Optical Biometrics',
      rarity: 'rare',
      icon: '🥽',
      desc: 'Transparent cyan holographic visor projecting telemetry.'
    },
    {
      slot: 'headband',
      name: 'Warrior Sweatband',
      type: 'Athletic Terry',
      rarity: 'common',
      icon: '🥋',
      desc: 'High-density moisture-wicking elastic compression headband.'
    }
  ];

  function renderHeadwear() {
    var grid = document.getElementById('studioHeadwear');
    if (!grid) return;
    grid.innerHTML = '';

    if (ld.trainer === 'street') {
      var capCard = document.createElement('div');
      capCard.className = 'st-gear-card';
      capCard.setAttribute('data-rarity', 'common');
      var isCapOn = !!ld.cap;
      capCard.setAttribute('aria-pressed', isCapOn ? 'true' : 'false');
      capCard.innerHTML = 
        '<div class="st-gear-card-top"><span class="st-gear-icon">🧢</span><span class="st-badge-rarity">COMMON</span></div>' +
        '<div class="st-gear-name">Signature Cap</div>' +
        '<div class="st-gear-type">Streetwear Apparel</div>' +
        '<div class="st-gear-desc">Front-facing aerodynamic athletic cap.</div>' +
        '<div class="st-gear-card-bottom"><span class="st-gear-status">' + (isCapOn ? '✓ EQUIPPED' : '+ EQUIP') + '</span></div>';
      capCard.onclick = function () {
        ld.cap = ld.cap ? '' : 'cap';
        W.save(ld);
        playEquipSound('common');
        renderHeadwear();
        updateSelectionHUD();
        if (root) W.apply(root, ld);
      };
      grid.appendChild(capCard);
    }

    HEADWEAR_ITEMS.forEach(function (item) {
      var card = document.createElement('div');
      card.className = 'st-gear-card';
      card.setAttribute('data-rarity', item.rarity);
      var isEquipped = !!ld[item.slot];
      card.setAttribute('aria-pressed', isEquipped ? 'true' : 'false');
      card.innerHTML = 
        '<div class="st-gear-card-top"><span class="st-gear-icon">' + item.icon + '</span><span class="st-badge-rarity">' + item.rarity.toUpperCase() + '</span></div>' +
        '<div class="st-gear-name">' + item.name + '</div>' +
        '<div class="st-gear-type">' + item.type + '</div>' +
        '<div class="st-gear-desc">' + item.desc + '</div>' +
        '<div class="st-gear-card-bottom"><span class="st-gear-status">' + (isEquipped ? '✓ EQUIPPED' : '+ EQUIP') + '</span></div>';
      card.onclick = function () {
        ld[item.slot] = ld[item.slot] ? '' : item.slot;
        W.save(ld);
        playEquipSound(item.rarity);
        renderHeadwear();
        updateSelectionHUD();
        if (root) W.apply(root, ld);
      };
      grid.appendChild(card);
    });
  }

  /* ══ Gear (Belt, Watch, Wraps, Dumbbells) ══ */
  var GEAR_ITEMS = [
    {
      slot: 'belt',
      name: 'Titan Olympic Belt',
      type: '10mm Saddle Leather',
      rarity: 'legendary',
      icon: '🏆',
      desc: 'Heavy-duty powerlifting belt with chrome double-prong buckle.'
    },
    {
      slot: 'watch',
      name: 'Apex Biometric Watch',
      type: 'Telemetry Wearable',
      rarity: 'rare',
      icon: '⌚',
      desc: 'Continuous pulse, VO2 max and core temperature tracker.'
    },
    {
      slot: 'wraps',
      name: 'Powerlifting Wraps',
      type: 'Wrist Stabilizer',
      rarity: 'common',
      icon: '🥊',
      desc: 'Dual elastic heavy compression wraps for overhead pressing.'
    },
    {
      slot: 'dumbbells',
      name: 'Hex Steel Dumbbells',
      type: 'Cast Iron Free Weights',
      rarity: 'rare',
      icon: '🏋️',
      desc: '20kg knurled steel hex dumbbells held in both hands.'
    }
  ];

  function renderGear() {
    var grid = document.getElementById('studioGear');
    if (!grid) return;
    grid.innerHTML = '';

    GEAR_ITEMS.forEach(function (item) {
      var card = document.createElement('div');
      card.className = 'st-gear-card';
      card.setAttribute('data-rarity', item.rarity);
      var isEquipped = !!ld[item.slot];
      card.setAttribute('aria-pressed', isEquipped ? 'true' : 'false');
      card.innerHTML = 
        '<div class="st-gear-card-top"><span class="st-gear-icon">' + item.icon + '</span><span class="st-badge-rarity">' + item.rarity.toUpperCase() + '</span></div>' +
        '<div class="st-gear-name">' + item.name + '</div>' +
        '<div class="st-gear-type">' + item.type + '</div>' +
        '<div class="st-gear-desc">' + item.desc + '</div>' +
        '<div class="st-gear-card-bottom"><span class="st-gear-status">' + (isEquipped ? '✓ EQUIPPED' : '+ EQUIP') + '</span></div>';
      card.onclick = function () {
        ld[item.slot] = ld[item.slot] ? '' : item.slot;
        W.save(ld);
        playEquipSound(item.rarity);
        renderGear();
        updateSelectionHUD();
        if (root) W.apply(root, ld);
      };
      grid.appendChild(card);
    });
  }

  /* ══ VFX Auras ══ */
  var AURA_ITEMS = [
    {
      id: '',
      name: 'Stealth Mode',
      type: 'No VFX Aura',
      rarity: 'common',
      icon: '⚪',
      desc: 'Clean stage platform without glowing energy rings.'
    },
    {
      id: 'cyber',
      name: 'Cyber Pulse Aura',
      type: 'Holographic Grid',
      rarity: 'epic',
      icon: '💠',
      desc: 'Pulsing cyan neon ring with dynamic grid frequencies.'
    },
    {
      id: 'ember',
      name: 'Ember Fury Aura',
      type: 'Thermal Combustion',
      rarity: 'legendary',
      icon: '🔥',
      desc: 'Rising fiery molten ring with thermal floor distortion.'
    },
    {
      id: 'electric',
      name: 'Lightning Surge Aura',
      type: 'Ionization Arc',
      rarity: 'epic',
      icon: '⚡',
      desc: 'High-voltage electric purple aura crackling around trainer.'
    }
  ];

  function renderAuras() {
    var grid = document.getElementById('studioAuras');
    if (!grid) return;
    grid.innerHTML = '';

    AURA_ITEMS.forEach(function (item) {
      var card = document.createElement('div');
      card.className = 'st-gear-card';
      card.setAttribute('data-rarity', item.rarity);
      var isEquipped = (ld.aura || '') === item.id;
      card.setAttribute('aria-pressed', isEquipped ? 'true' : 'false');
      card.innerHTML = 
        '<div class="st-gear-card-top"><span class="st-gear-icon">' + item.icon + '</span><span class="st-badge-rarity">' + item.rarity.toUpperCase() + '</span></div>' +
        '<div class="st-gear-name">' + item.name + '</div>' +
        '<div class="st-gear-type">' + item.type + '</div>' +
        '<div class="st-gear-desc">' + item.desc + '</div>' +
        '<div class="st-gear-card-bottom"><span class="st-gear-status">' + (isEquipped ? '✓ EQUIPPED' : '+ EQUIP') + '</span></div>';
      card.onclick = function () {
        ld.aura = item.id;
        W.save(ld);
        playEquipSound(item.rarity);
        renderAuras();
        updateSelectionHUD();
        if (root) W.apply(root, ld);
      };
      grid.appendChild(card);
    });
  }

  /* ══ Poses ══ */
  var POSES = [
    { id: 'warmup', name: 'Warm Up', icon: '🏃', desc: 'Forward athletic arm drive & light bounce cadence' },
    { id: 'squat', name: 'Deep Squat', icon: '🦵', desc: 'Hip-hinge air squat with forward counterbalance reach' },
    { id: 'curl', name: 'Bicep Curl', icon: '⚡', desc: 'Strict elbow flexion curling upward in front of chest' },
    { id: 'pushup', name: 'Push Up', icon: '💪', desc: 'Floor press with neutral spine & 45° tucked elbows' },
    { id: 'jumpingjack', name: 'Jumping Jacks', icon: '⭐', desc: 'Cardio star-jumps with full overhead reach' }
  ];

  function renderPosesTab() {
    var grid = document.getElementById('studioPosesTab');
    if (!grid) return;
    grid.innerHTML = '';

    POSES.forEach(function (p) {
      var card = document.createElement('div');
      card.className = 'st-gear-card';
      card.setAttribute('data-rarity', 'rare');
      var isCur = pose === p.id;
      card.setAttribute('aria-pressed', isCur ? 'true' : 'false');
      card.innerHTML = 
        '<div class="st-gear-card-top"><span class="st-gear-icon">' + p.icon + '</span><span class="st-badge-rarity">ANIMATION</span></div>' +
        '<div class="st-gear-name">' + p.name + '</div>' +
        '<div class="st-gear-type">Procedural Rig</div>' +
        '<div class="st-gear-desc">' + p.desc + '</div>' +
        '<div class="st-gear-card-bottom"><span class="st-gear-status">' + (isCur ? '✓ PLAYING' : '▶ TEST POSE') + '</span></div>';
      card.onclick = function () {
        pose = p.id;
        playEquipSound('rare');
        renderPosesTab();
        renderTopPoseBar();
      };
      grid.appendChild(card);
    });
  }

  function renderTopPoseBar() {
    var mount = document.getElementById('studioPose');
    if (!mount) return;
    mount.innerHTML = '';
    POSES.forEach(function (p) {
      var b = document.createElement('button');
      b.type = 'button'; b.className = 'st-pose';
      b.setAttribute('aria-pressed', p.id === pose ? 'true' : 'false');
      b.textContent = p.name;
      b.onclick = function () {
        pose = p.id;
        playEquipSound('rare');
        renderTopPoseBar();
        renderPosesTab();
      };
      mount.appendChild(b);
    });
  }

  /* ══ Reset Button ══ */
  document.getElementById('studioReset').onclick = function () {
    ld = Object.assign({}, W.DEFAULT);
    W.save(ld);
    playEquipSound('legendary');
    renderTrainers();
    renderOutfits();
    renderTops();
    renderBottoms();
    renderShoes();
    renderHeadwear();
    renderGear();
    renderAuras();
    renderPosesTab();
    renderTopPoseBar();
    updateSelectionHUD();
    loadTrainer();
  };

  /* ══ Initialization ══ */
  renderTrainers();
  renderOutfits();
  renderTops();
  renderBottoms();
  renderShoes();
  renderHeadwear();
  renderGear();
  renderAuras();
  renderPosesTab();
  renderTopPoseBar();
  updateSelectionHUD();
  resize();
  loadTrainer();

})();
</script>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
