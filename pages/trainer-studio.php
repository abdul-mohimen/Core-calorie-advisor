<?php
require_once dirname(__DIR__) . '/config/config.php';
$pageTitle = 'Trainer Studio';
include dirname(__DIR__) . '/includes/header.php';

$next = trim((string)get('next', ''));
$backUrl = $next !== '' && preg_match('#^[A-Za-z0-9_\-/\.\?=&]+$#', $next)
    ? url($next) : url('pages/workouts.php');
?>

<section class="cca-hero" style="min-height:auto;padding-block:clamp(28px,5vw,56px)">
  <div class="cca-hero__overlay"></div>
  <div class="cca-hero__content" style="display:block">
    <div class="cca-hero__breadcrumb">
      <a href="<?= url('index.php') ?>">Home</a> <span class="sep">›</span>
      <a href="<?= url('pages/workouts.php') ?>">Workouts</a> <span class="sep">›</span> Trainer Studio
    </div>
    <div class="cca-hero__badge cca-hero__badge--pro"><span class="dot"></span> Your Coach, Your Way</div>
    <h1 class="cca-hero__title" style="margin-bottom:8px">Trainer <span class="grad">Studio</span></h1>
    <p class="cca-hero__subtitle" style="max-width:60ch">
      Choose the trainer who takes your sessions and set their kit.
      Your pick is remembered and used in every workout.
    </p>
  </div>
</section>

<section class="studio-page">
  <!-- Column count lives in CSS only. An inline grid-template-columns here beat
       the min-width:900px media query, so the controls stacked under a narrow
       preview with a big empty gap on the right. -->
  <div id="studioGrid">

    <!-- ══ live preview ══ -->
    <div class="studio-preview-wrap">
      <div class="studio-preview-head">
        <div><span class="studio-kicker"><i></i> Live 3D preview</span><h2>Your training partner</h2></div>
        <span class="studio-drag-hint">Drag to explore</span>
      </div>
      <div id="studioStage" class="studio-stage"
           style="position:relative;border-radius:20px;overflow:hidden;border:1px solid var(--line,rgba(255,255,255,.1));
                  background:radial-gradient(120% 90% at 50% 0%,#1b1f28 0%,#0f1116 60%,#0b0d11 100%);
                  aspect-ratio:4/5;min-height:380px;max-height:min(74vh,660px)">
        <div id="studioLoading" class="studio-loading"
             style="position:absolute;inset:0;display:grid;place-items:center;font:700 11px/1 ui-monospace,monospace;
                    letter-spacing:3px;text-transform:uppercase;color:#FF6B1A">Loading trainer…</div>
        <!-- name sits at the BOTTOM; it used to share the top row with the pose
             buttons and the two overlapped ("Pro · Lean buil[WARMUP]"). -->
        <div id="studioName" class="studio-name"
             style="position:absolute;left:14px;bottom:12px;font:900 13px/1 system-ui;color:#fff;
                    text-shadow:0 2px 10px rgba(0,0,0,.75);pointer-events:none;z-index:2"></div>
        <div id="studioPose" class="studio-pose"
             style="position:absolute;left:10px;right:10px;top:10px;display:flex;gap:6px;
                    flex-wrap:wrap;justify-content:center;z-index:2"></div>
      </div>
      <p class="studio-preview-note" style="margin-top:8px;font:500 11px/1.5 system-ui;color:var(--muted,#8b93a1)">
        Drag to rotate · scroll to zoom. The preview animates exactly as the workout does.
      </p>
    </div>

    <!-- ══ controls ══ -->
    <aside class="studio-controls" aria-label="Trainer customisation controls">
      <div class="studio-controls-head">
        <span class="studio-kicker">Studio setup</span>
        <h2>Build your coach</h2>
        <p>Choose a trainer, kit and finishing touches. Your selection follows you into every session.</p>
      </div>
      <div class="studio-selection" id="studioSelection" aria-live="polite"></div>
      <div class="studio-control-groups">
        <div id="studioTrainers"></div>
        <div id="studioOutfits"></div>
        <div id="studioExtras"></div>
      </div>

      <div class="studio-actions">
        <a id="studioDone" class="cca-btn cca-btn-primary" href="<?= e($backUrl) ?>"
           style="flex:1 1 190px;text-align:center;text-decoration:none">Save &amp; Continue <span aria-hidden="true">→</span></a>
        <button type="button" id="studioReset" class="cca-btn cca-btn-ghost" style="flex:0 0 auto">Reset</button>
      </div>
      <p class="studio-save-note" style="margin:0;font:500 11px/1.6 system-ui;color:var(--muted,#8b93a1)">
        <span aria-hidden="true">✓</span> Saved on this device. Changing the trainer, kit and extras applies instantly.
      </p>
    </aside>
  </div>
</section>

<style>
#studioGrid{display:grid;gap:clamp(16px,2.5vw,30px);grid-template-columns:minmax(0,1fr)}
@media (min-width: 900px){ #studioGrid{grid-template-columns:minmax(0,1fr) 380px;align-items:start} }
.st-group-label{font:800 10px/1 ui-monospace,monospace;letter-spacing:2.6px;text-transform:uppercase;color:#FF6B1A;margin-bottom:9px;display:block}
.st-row{display:flex;flex-wrap:wrap;gap:9px}
.st-card{display:flex;flex-direction:column;gap:3px;align-items:flex-start;padding:12px 15px;border-radius:14px;
  border:1px solid var(--line,rgba(255,255,255,.12));background:var(--surface,rgba(255,255,255,.04));
  color:var(--fg,#dfe3ea);cursor:pointer;flex:1 1 165px;transition:.16s;text-align:left}
.st-card:hover{border-color:rgba(255,107,26,.5);transform:translateY(-1px)}
.st-card[aria-pressed="true"]{border-color:#FF6B1A;background:linear-gradient(135deg,rgba(255,107,26,.26),rgba(255,184,0,.09));color:#fff}
.st-card b{font:800 14px/1.1 system-ui}
.st-card span{font:500 11px/1.35 system-ui;opacity:.72}
.st-chip{padding:9px 15px;border-radius:999px;border:1px solid var(--line,rgba(255,255,255,.14));
  background:var(--surface,rgba(255,255,255,.04));color:var(--fg,#dfe3ea);font:650 12.5px/1 system-ui;cursor:pointer;transition:.16s}
.st-chip:hover{border-color:rgba(255,107,26,.5)}
.st-chip[aria-pressed="true"]{border-color:#FF6B1A;background:linear-gradient(135deg,rgba(255,107,26,.3),rgba(255,184,0,.1));color:#fff;font-weight:800}
.st-kit{display:flex;align-items:center;gap:9px;padding:8px 14px 8px 9px;border-radius:999px;
  border:1px solid var(--line,rgba(255,255,255,.14));background:var(--surface,rgba(255,255,255,.04));
  color:var(--fg,#dfe3ea);font:650 12.5px/1 system-ui;cursor:pointer;transition:.16s}
.st-kit:hover{border-color:rgba(255,107,26,.5)}
.st-kit[aria-pressed="true"]{border-color:#FF6B1A;background:linear-gradient(135deg,rgba(255,107,26,.28),rgba(255,184,0,.1));color:#fff;font-weight:800}
.st-kit i{width:20px;height:20px;border-radius:50%;display:block;border:2px solid rgba(255,255,255,.28)}
.st-pose{padding:5px 10px;border-radius:999px;border:1px solid rgba(255,255,255,.16);background:rgba(10,12,16,.6);
  color:#cfd5df;font:700 10px/1 ui-monospace,monospace;letter-spacing:1.2px;text-transform:uppercase;cursor:pointer;backdrop-filter:blur(4px)}
.st-pose[aria-pressed="true"]{border-color:#FF6B1A;color:#fff;background:rgba(255,107,26,.28)}

/* Premium studio workspace layer */
.studio-page{max-width:1280px;margin:0 auto;padding:10px clamp(16px,3vw,34px) 84px}
#studioGrid{gap:clamp(20px,3vw,38px)}
.studio-preview-head{display:flex;align-items:end;justify-content:space-between;gap:18px;margin:0 2px 14px}.studio-preview-head h2,.studio-controls h2{margin:5px 0 0;color:var(--text-1);font:700 24px/1.08 var(--font-tech);letter-spacing:.01em}.studio-kicker{display:flex;align-items:center;gap:7px;color:var(--primary-text);font:800 10px/1 var(--font-tech);letter-spacing:.15em;text-transform:uppercase}.studio-kicker i{width:7px;height:7px;border-radius:50%;background:var(--success);box-shadow:0 0 0 4px color-mix(in srgb,var(--success) 14%,transparent)}.studio-drag-hint{color:var(--text-3);font:700 11px var(--font-tech);letter-spacing:.06em;text-transform:uppercase}
.studio-stage{border-color:color-mix(in srgb,var(--primary) 22%,var(--line))!important;border-radius:24px!important;box-shadow:0 28px 58px rgba(0,0,0,.28);isolation:isolate}.studio-stage::before{content:"";position:absolute;z-index:0;inset:54% -25% -30%;background:linear-gradient(rgba(255,255,255,.07) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.07) 1px,transparent 1px);background-size:32px 32px;transform:perspective(360px) rotateX(58deg);mask-image:linear-gradient(to bottom,transparent,black 52%);pointer-events:none}.studio-stage canvas{z-index:1;position:absolute}.studio-loading{z-index:3!important;background:radial-gradient(circle,color-mix(in srgb,var(--bg) 14%,transparent),rgba(8,10,14,.3));font-family:var(--font-tech)!important;letter-spacing:.18em!important}.studio-loading::before{content:"";position:absolute;width:42px;height:42px;border:2px solid color-mix(in srgb,var(--primary) 22%,transparent);border-top-color:var(--primary);border-radius:50%;animation:studioSpin .9s linear infinite}@keyframes studioSpin{to{transform:rotate(360deg)}}.studio-name{left:18px!important;bottom:16px!important;z-index:2!important;padding:9px 12px;border:1px solid rgba(255,255,255,.16);border-radius:11px;background:rgba(7,9,13,.5);backdrop-filter:blur(10px);font-family:var(--font-tech)!important;letter-spacing:.045em}.studio-pose{left:14px!important;right:14px!important;top:14px!important;gap:7px!important;z-index:2!important}.studio-preview-note{margin:12px 3px 0!important;color:var(--text-3)!important;font:500 13px/1.55 var(--font-body)!important}
.studio-controls{display:flex;flex-direction:column;gap:20px;padding:24px;border:1px solid color-mix(in srgb,var(--primary) 15%,var(--line));border-radius:22px;background:linear-gradient(145deg,color-mix(in srgb,var(--surface) 92%,var(--primary) 8%),var(--surface));box-shadow:var(--shadow-1)}.studio-controls-head p{margin:10px 0 0;color:var(--text-2);font-size:14px;line-height:1.6}.studio-selection{padding:12px 14px;border:1px solid color-mix(in srgb,var(--primary) 22%,var(--line));border-radius:13px;color:var(--text-1);background:color-mix(in srgb,var(--primary) 8%,var(--bg2));font:700 13px/1.4 var(--font-tech)}.studio-selection span{color:var(--text-3);font-weight:600}.studio-control-groups{display:grid;gap:20px}
.st-group-label{font-family:var(--font-tech);font-size:10.5px;letter-spacing:.16em;color:var(--primary-text);margin-bottom:11px}.st-row{gap:10px}.st-card{min-height:94px;padding:15px;border-color:var(--line);background:color-mix(in srgb,var(--surface) 92%,var(--bg3));color:var(--text-1);flex-basis:145px;transition:transform .2s var(--ease),border-color .2s var(--ease),background .2s var(--ease),box-shadow .2s var(--ease)}.st-card:hover{border-color:color-mix(in srgb,var(--primary) 56%,var(--border));transform:translateY(-2px);box-shadow:0 10px 20px color-mix(in srgb,var(--primary) 11%,transparent)}.st-card[aria-pressed="true"]{border-color:var(--primary);background:linear-gradient(135deg,color-mix(in srgb,var(--primary) 24%,var(--surface)),color-mix(in srgb,var(--gold) 8%,var(--surface)));box-shadow:inset 3px 0 0 var(--primary)}.st-card b{font:800 16px/1.1 var(--font-tech);letter-spacing:.02em}.st-card span{font:500 12px/1.35 var(--font-body);color:var(--text-2)}
.st-chip,.st-kit{min-height:40px;border-color:var(--line);background:color-mix(in srgb,var(--surface) 92%,var(--bg3));color:var(--text-1);transition:transform .2s var(--ease),border-color .2s var(--ease),background .2s var(--ease)}.st-chip{padding:10px 15px;font:700 13px/1 var(--font-tech);letter-spacing:.02em}.st-kit{font:700 13px/1 var(--font-tech)}.st-chip:hover,.st-kit:hover{border-color:color-mix(in srgb,var(--primary) 56%,var(--border));transform:translateY(-1px)}.st-chip[aria-pressed="true"],.st-kit[aria-pressed="true"]{border-color:var(--primary);background:color-mix(in srgb,var(--primary) 16%,var(--surface));color:var(--text-1)}.st-kit i{width:22px;height:22px}.st-pose{padding:7px 11px;background:rgba(7,9,13,.62);font-family:var(--font-tech);letter-spacing:.11em;transition:.18s}.st-pose:hover,.st-pose[aria-pressed="true"]{border-color:var(--primary);color:#fff;background:rgba(255,107,26,.3)}
.studio-actions{display:flex;gap:10px;flex-wrap:wrap;padding-top:4px}.studio-actions .cca-btn{min-height:48px}.studio-actions #studioDone{display:inline-flex;align-items:center;justify-content:center;gap:10px;flex:1 1 190px}.studio-save-note{padding-top:16px;border-top:1px solid var(--line);color:var(--text-3)!important;font:500 12.5px/1.6 var(--font-body)!important}.studio-save-note span{color:var(--success-text);font-weight:800}
@media (min-width:900px){#studioGrid{grid-template-columns:minmax(0,1fr) 405px}.studio-controls{position:sticky;top:calc(var(--nav-h) + 20px)}}@media (max-width:640px){.studio-page{padding-inline:14px;padding-bottom:56px}.studio-preview-head{align-items:flex-start;flex-direction:column;gap:9px}.studio-stage{min-height:380px!important;border-radius:19px!important}.studio-controls{padding:20px;border-radius:18px}.studio-preview-head h2,.studio-controls h2{font-size:22px}.studio-drag-hint{display:none}.st-card{flex-basis:100%}.studio-actions #studioReset{flex:1 1 110px}}
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/GLTFLoader.js"></script>
<script src="<?= asset('js/titan-rig.js') ?>"></script>
<script src="<?= asset('js/cca-wardrobe.js') ?>"></script>
<script>
(function () {
  var W = window.CCAWardrobe;
  var stage = document.getElementById('studioStage');
  if (!W || !stage || typeof THREE === 'undefined' || typeof THREE.GLTFLoader !== 'function') {
    var l = document.getElementById('studioLoading');
    if (l) l.textContent = '3D preview unavailable in this browser';
    return;
  }

  var BASE = '../';
  var ld = W.load();
  var scene, cam, rend, root, raf = null, yaw = 0.4, dist = 1.0, clock = new THREE.Clock();
  var pose = 'warmup';

  /* ---- scene ---- */
  scene = new THREE.Scene();
  cam = new THREE.PerspectiveCamera(36, 4 / 5, 0.1, 60);
  rend = new THREE.WebGLRenderer({ antialias: true, alpha: true });
  rend.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
  stage.appendChild(rend.domElement);
  rend.domElement.style.cssText = 'position:absolute;inset:0;width:100%;height:100%;display:block';
  scene.add(new THREE.HemisphereLight(0xffffff, 0x1b1f28, 1.05));
  var key = new THREE.DirectionalLight(0xffffff, 2.1); key.position.set(-2.2, 3.2, 3.0); scene.add(key);
  var fill = new THREE.DirectionalLight(0xffd9b0, 0.85); fill.position.set(2.6, 1.4, -2.2); scene.add(fill);
  var rim = new THREE.DirectionalLight(0x9dc4ff, 0.7); rim.position.set(0, 2.4, -3.4); scene.add(rim);

  function resize() {
    var w = stage.clientWidth || 400, h = stage.clientHeight || 500;
    rend.setSize(w, h, false);
    cam.aspect = w / h; cam.updateProjectionMatrix();
  }
  window.addEventListener('resize', resize);

  /* Camera is derived from the height we FIT the model to, never measured off the
     mesh. Box3.setFromObject() on a SkinnedMesh returns the bind-pose box
     transformed by a matrix that excludes skinning — here it came back about
     0.02 units tall, which put the camera on top of the origin and left the
     trainer completely out of frame. That was why the preview looked empty.
     The workout engines sidestep it the same way, via CCARig.cameraFor(). */
  var FIT_H = 2.2;

  /* Fit from BONE world positions, which is the only scale-agnostic measure here.
     CCARig.fit() sizes from geometry bounding boxes, and these GLBs store
     geometry already in metres while their node chain still carries the 0.01
     Mixamo scale — so fit() computed 1.83 -> scale 1.204 and produced a trainer
     0.022 units tall, i.e. invisible. Bones are read through matrixWorld, so the
     node scale is included and this works for any export convention.
     Mixamo rigs expose HeadTop_End at the crown and a toe/foot bone at the floor. */
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
    if (!s || s.height <= 1e-9) {           // no skeleton — geometry fit is valid
      if (window.CCARig && CCARig.fit) CCARig.fit(o, targetH);
      return;
    }
    /* HeadTop_End is the crown and the lowest bone is the ankle, so the bone
       span slightly under-reports standing height; ~4% covers the foot. */
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
      o.position.y -= f.bottom;             // stand on y = 0
      o.updateMatrixWorld(true);
    }
  }

  function placeCamera() {
    cam.position.set(0, FIT_H * 0.60, FIT_H * 1.55 * dist);
    cam.lookAt(0, FIT_H * 0.52, 0);
  }

  function loop() {
    raf = requestAnimationFrame(loop);
    if (stage.offsetParent === null || document.hidden) return;
    var t = clock.getElapsedTime();
    if (root) root.rotation.y = yaw;
    if (window.CCARig && CCARig.attached && CCARig.update) CCARig.update(pose, t);
    rend.render(scene, cam);
  }

  function loadTrainer() {
    var t = W.trainerById(ld.trainer);
    document.getElementById('studioLoading').style.display = 'grid';
    document.getElementById('studioLoading').textContent = 'Loading ' + t.name + '…';
    W.resetBaseMap();
    var modelPath = '../' + t.file;
    new THREE.GLTFLoader().load(modelPath, function (g) {
      try {
        if (root) scene.remove(root);
        root = g.scene;
        W.uncull(root);            // otherwise the whole trainer is frustum-culled
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
        document.getElementById('studioName').textContent = t.name + ' · ' + t.build;
        if (!raf) loop();
      } catch (innerErr) {
        console.error('[studio] Error inside onLoad:', innerErr);
      }
    }, undefined, function (e) {
      console.warn('[studio] model failed', e);
      document.getElementById('studioLoading').textContent = 'Could not load this trainer';
    });
  }

  /* ---- controls ---- */
  function group(mount, label) {
    mount.innerHTML = '';
    var l = document.createElement('span'); l.className = 'st-group-label'; l.textContent = label;
    var r = document.createElement('div'); r.className = 'st-row';
    mount.appendChild(l); mount.appendChild(r);
    return r;
  }

  function updateSelection() {
    var mount = document.getElementById('studioSelection');
    if (!mount) return;
    var trainer = W.trainerById(ld.trainer);
    var outfit = W.outfitsFor(ld.trainer).filter(function (o) { return o.id === ld.outfit; })[0];
    mount.innerHTML = '<span>Active coach</span><br>' + trainer.name + ' <span>·</span> ' +
      (outfit ? outfit.name : 'Signature kit');
  }

  function renderTrainers() {
    var row = group(document.getElementById('studioTrainers'), 'Trainer');
    W.TRAINERS.forEach(function (t) {
      var b = document.createElement('button');
      b.type = 'button'; b.className = 'st-card';
      b.setAttribute('aria-pressed', ld.trainer === t.id ? 'true' : 'false');
      b.innerHTML = '<b>' + t.name + '</b><span>' + t.build + '</span><span>' + t.wears + '</span>';
      b.onclick = function () {
        if (ld.trainer === t.id) return;
        ld.trainer = t.id; ld.outfit = '';
        /* drop an extra the new trainer does not offer, or it stays "on" invisibly */
        Object.keys(W.EXTRA_LABEL).forEach(function (s) {
          if (W.extrasFor(t.id).indexOf(s) === -1) ld[s] = '';
        });
        W.save(ld);
        /* Extras must re-render too — it is per-trainer. Without this, switching
           to Street kept showing Pro's "comes with their own headwear" note. */
        renderTrainers(); renderOutfits(); renderExtras();
        loadTrainer();
      };
      row.appendChild(b);
    });
    updateSelection();
  }

  function renderOutfits() {
    var row = group(document.getElementById('studioOutfits'), 'Kit');
    W.outfitsFor(ld.trainer).forEach(function (o) {
      var b = document.createElement('button');
      b.type = 'button'; b.className = 'st-kit';
      b.setAttribute('aria-pressed', (ld.outfit || '') === o.id ? 'true' : 'false');
      b.innerHTML = '<i style="background:' + o.hex + '"></i>' + o.name;
      b.onclick = function () {
        ld.outfit = o.id; W.save(ld);
        renderOutfits();
        if (root) W.apply(root, ld);
      };
      row.appendChild(b);
    });
    updateSelection();
  }

  function renderExtras() {
    var mount = document.getElementById('studioExtras');
    var slots = W.extrasFor(ld.trainer);
    if (!slots.length) {                       // Pro already wears its own cap
      mount.innerHTML = '<span class="st-group-label">Extras</span>' +
        '<p style="margin:0;font:500 11.5px/1.5 system-ui;color:var(--muted,#8b93a1)">' +
        'This trainer comes with their own headwear — nothing to add.</p>';
      return;
    }
    var row = group(mount, 'Extras');
    slots.forEach(function (slot) {
      var b = document.createElement('button');
      b.type = 'button'; b.className = 'st-chip';
      var on = !!ld[slot];
      b.setAttribute('aria-pressed', on ? 'true' : 'false');
      b.textContent = (on ? '✓ ' : '') + (W.EXTRA_LABEL[slot] || slot);
      b.onclick = function () {
        ld[slot] = ld[slot] ? '' : slot; W.save(ld);
        renderExtras();
        if (root) W.apply(root, ld);
      };
      row.appendChild(b);
    });
  }

  /* pose buttons — the preview animates the same way the workout does */
  ['warmup', 'squat', 'pushup', 'curl', 'jumpingjack'].forEach(function (m) {
    var b = document.createElement('button');
    b.type = 'button'; b.className = 'st-pose';
    b.setAttribute('aria-pressed', m === pose ? 'true' : 'false');
    b.textContent = m === 'jumpingjack' ? 'Jack' : m;
    b.onclick = function () {
      pose = m;
      [].forEach.call(document.getElementById('studioPose').children, function (c) {
        c.setAttribute('aria-pressed', c === b ? 'true' : 'false');
      });
    };
    document.getElementById('studioPose').appendChild(b);
  });

  document.getElementById('studioReset').onclick = function () {
    ld = Object.assign({}, W.DEFAULT); W.save(ld);
    renderTrainers(); renderOutfits(); renderExtras(); loadTrainer();
  };

  /* drag to spin, wheel to zoom */
  var drag = null;
  stage.addEventListener('pointerdown', function (e) { drag = e.clientX; stage.setPointerCapture(e.pointerId); });
  stage.addEventListener('pointermove', function (e) {
    if (drag === null) return;
    yaw += (e.clientX - drag) * 0.011; drag = e.clientX;
  });
  ['pointerup', 'pointercancel'].forEach(function (ev) { stage.addEventListener(ev, function () { drag = null; }); });
  stage.addEventListener('wheel', function (e) {
    e.preventDefault();
    dist = Math.min(1.6, Math.max(0.62, dist + (e.deltaY > 0 ? 0.07 : -0.07)));
    placeCamera();
  }, { passive: false });

  renderTrainers(); renderOutfits(); renderExtras();
  resize(); loadTrainer();
})();
</script>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
