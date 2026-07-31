<?php
require_once dirname(__DIR__) . '/config/config.php';
$pageTitle = 'Trainer Studio — Core Calorie Advisor';
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

<section style="max-width:1180px;margin:0 auto;padding:0 clamp(14px,3vw,26px) 64px">
  <!-- Column count lives in CSS only. An inline grid-template-columns here beat
       the min-width:900px media query, so the controls stacked under a narrow
       preview with a big empty gap on the right. -->
  <div id="studioGrid">

    <!-- ══ live preview ══ -->
    <div>
      <div id="studioStage"
           style="position:relative;border-radius:20px;overflow:hidden;border:1px solid var(--line,rgba(255,255,255,.1));
                  background:radial-gradient(120% 90% at 50% 0%,#1b1f28 0%,#0f1116 60%,#0b0d11 100%);
                  aspect-ratio:4/5;min-height:380px;max-height:min(74vh,660px)">
        <div id="studioLoading"
             style="position:absolute;inset:0;display:grid;place-items:center;font:700 11px/1 ui-monospace,monospace;
                    letter-spacing:3px;text-transform:uppercase;color:#FF6B1A">Loading trainer…</div>
        <!-- name sits at the BOTTOM; it used to share the top row with the pose
             buttons and the two overlapped ("Pro · Lean buil[WARMUP]"). -->
        <div id="studioName"
             style="position:absolute;left:14px;bottom:12px;font:900 13px/1 system-ui;color:#fff;
                    text-shadow:0 2px 10px rgba(0,0,0,.75);pointer-events:none;z-index:2"></div>
        <div id="studioPose"
             style="position:absolute;left:10px;right:10px;top:10px;display:flex;gap:6px;
                    flex-wrap:wrap;justify-content:center;z-index:2"></div>
      </div>
      <p style="margin-top:8px;font:500 11px/1.5 system-ui;color:var(--muted,#8b93a1)">
        Drag to rotate · scroll to zoom. The preview animates exactly as the workout does.
      </p>
    </div>

    <!-- ══ controls ══ -->
    <div style="display:flex;flex-direction:column;gap:22px">
      <div id="studioTrainers"></div>
      <div id="studioOutfits"></div>
      <div id="studioExtras"></div>

      <div style="display:flex;gap:10px;flex-wrap:wrap;padding-top:4px">
        <a id="studioDone" class="cca-btn cca-btn-primary" href="<?= e($backUrl) ?>"
           style="flex:1 1 190px;text-align:center;text-decoration:none">Save &amp; Continue</a>
        <button type="button" id="studioReset" class="cca-btn cca-btn-ghost" style="flex:0 0 auto">Reset</button>
      </div>
      <p style="margin:0;font:500 11px/1.6 system-ui;color:var(--muted,#8b93a1)">
        Saved on this device. Changing the trainer loads a different character;
        kit and extras apply instantly.
      </p>
    </div>
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

  var BASE = <?= json_encode(rtrim(BASE_URL, '/') . '/') ?>;
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
    new THREE.GLTFLoader().load(BASE + t.file, function (g) {
      if (root) scene.remove(root);
      root = g.scene;
      W.uncull(root);            // otherwise the whole trainer is frustum-culled
      /* Pro is a motion-capture actor: without this its suit markers show up as
         white spheres all over the body. The workout engines call this too. */
      if (window.CCARig && CCARig.styleModel) CCARig.styleModel(root);
      fitByBones(root, FIT_H);
      scene.add(root);
      if (window.CCARig && CCARig.attach) CCARig.attach(root);
      W.apply(root, ld);
      resize(); placeCamera();
      document.getElementById('studioLoading').style.display = 'none';
      document.getElementById('studioName').textContent = t.name + ' · ' + t.build;
      if (!raf) loop();
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
