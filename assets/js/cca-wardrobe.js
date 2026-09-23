/* ============ CORE CALORIE ADVISOR — trainer loadout ============
   Pick a trainer and an outfit before a workout; the choice carries into the
   session and is used by both 3D engines.

   WHAT IS AND IS NOT HERE — this matters, so it is written down:

   Model swap and cap/glasses are real geometry. The OUTFIT is a texture swap on
   the character's own clothing, not separate shirt/trouser meshes.

   Separate garment meshes were attempted three times and abandoned. Root cause:
   both characters have their clothing MODELLED INTO the body mesh — the hoodie
   collar, pockets and folds are body geometry. Harvesting a body region and
   pushing it out along its normals therefore inherits that relief; the neckline
   came out crumpled every time, and offsetting far enough to clear the body made
   the shell self-intersect while the body still showed through as white patches.
   Evidence: _audit/garment-fail-v1-collar.png and -v2-collar.png.
   Doing it properly means a clean primitive garment shrinkwrapped to the body
   with skin weights transferred — real modelling on a purpose-built asset, not
   something to fake with scripted mesh ops.                                   */
(function (w) {
  'use strict';

  var STORE = 'cca-loadout';

  /* `extras` is per-trainer on purpose:
     - Pro (MocapGuy) already wears a cap in its own mesh, so offering another one
       is confusing and barely visible (measured: +42 px vs +652 px on Street).
     - Glasses were cut. The generated frame landed 0.2 cm wide and below the
       floor: it is authored in world metres but ends up under the armature's
       0.01 scale, and no parent-inverse arrangement fixed it cleanly. A button
       that does nothing is worse than no button. */
  var TRAINERS = [
    { id: 'street', name: 'Street Beast', file: 'assets/models/trainer-street.glb', prefix: 'ch06_',
      extras: ['cap', 'headphones', 'visor', 'headband', 'belt', 'watch', 'wraps', 'dumbbells'],
      build: 'Athletic hoodie build', wears: 'Oversized tracksuit · high-tops',
      outfits: [
        { id: '',       name: 'Midnight Black', hex: '#232733' },
        { id: 'ember',  name: 'Ember Crimson',  hex: '#8C3A17', tex: 'assets/models/outfits/ch06-ember.png' },
        { id: 'ocean',  name: 'Ocean Cobalt',   hex: '#153D57', tex: 'assets/models/outfits/ch06-ocean.png' },
        { id: 'forest', name: 'Forest Stealth', hex: '#1A4430', tex: 'assets/models/outfits/ch06-forest.png' }
      ] },
    { id: 'pro', name: 'Titan Pro', file: 'assets/models/trainer-pro.glb', prefix: 'mg_',
      extras: ['headphones', 'visor', 'headband', 'belt', 'watch', 'wraps', 'dumbbells'],
      build: 'Lean muscular build', wears: 'Compression suit · athletic fit',
      outfits: [
        { id: '',       name: 'Graphite Carbon', hex: '#2E333D' },
        { id: 'ember',  name: 'Molten Flame',    hex: '#8C3A17', tex: 'assets/models/outfits/ember.png' },
        { id: 'ocean',  name: 'Abyssal Blue',    hex: '#153D57', tex: 'assets/models/outfits/ocean.png' },
        { id: 'forest', name: 'Military Olive',  hex: '#1A4430', tex: 'assets/models/outfits/forest.png' }
      ] }
  ];

  var DEFAULT = {
    trainer: 'street',
    outfit: '',
    cap: 'cap',
    headphones: 'headphones',
    visor: '',
    headband: '',
    belt: 'belt',
    watch: 'watch',
    wraps: '',
    dumbbells: '',
    aura: ''
  };
  var EXTRA_LABEL = {
    cap: 'Cap',
    headphones: 'Cyber Headphones',
    visor: 'Tactical Visor',
    headband: 'Sweatband',
    belt: 'Lifting Belt',
    watch: 'Smartwatch',
    wraps: 'Wrist Wraps',
    dumbbells: 'Dumbbells',
    aura: 'Aura VFX'
  };

  function trainerById(id) {
    for (var i = 0; i < TRAINERS.length; i++) if (TRAINERS[i].id === id) return TRAINERS[i];
    return TRAINERS[0];
  }
  function outfitsFor(id) { return trainerById(id).outfits; }
  function outfitById(trainerId, outfitId) {
    var list = outfitsFor(trainerId);
    for (var i = 0; i < list.length; i++) if (list[i].id === (outfitId || '')) return list[i];
    return list[0];
  }

  function load() {
    var out = {};
    for (var k in DEFAULT) out[k] = DEFAULT[k];
    try {
      var raw = w.localStorage.getItem(STORE);
      if (raw) { var g = JSON.parse(raw); for (var k2 in DEFAULT) if (g[k2] !== undefined) out[k2] = g[k2]; }
    } catch (e) {}
    /* an outfit id from the other trainer must not leak across */
    if (!outfitsFor(out.trainer).some(function (o) { return o.id === out.outfit; })) out.outfit = '';
    return out;
  }
  function save(ld) { try { w.localStorage.setItem(STORE, JSON.stringify(ld)); } catch (e) {} }

  function modelUrlFor(ld) { return trainerById((ld || load()).trainer).file; }

  /* Accessory meshes are named "<prefix>cap". The unused "<prefix>glasses" mesh
     still ships inside the GLBs; it is force-hidden below so a 0.2 cm artefact
     can never appear. */
  function accessorySlot(name) {
    var n = String(name || '').toLowerCase();
    if (/(^|_)cap$/.test(n)) return 'cap';
    if (/(^|_)glasses$/.test(n)) return 'glasses';
    return null;
  }
  function extrasFor(id) { return trainerById(id).extras || []; }

  /* Material carrying the character's clothing texture, per trainer. */
  var OUTFIT_MATERIALS = ['Ch06_body', 'Ch06_eyelashes', 'Body_MAT'];

  var texCache = {};

  /* MUST be called on any trainer before it is rendered.

     These GLBs store geometry in metres while their inverse-bind matrices still
     carry the Mixamo x100 scale (boneInverse determinant ~1e6). Skinning renders
     correctly, but three.js derives the bounding sphere from the bind-pose
     geometry in the wrong space, decides the mesh is off-screen and culls the
     ENTIRE trainer — the preview looked empty for exactly this reason: 0 pixels
     drawn with culling on, 28,419 with it off.
     titan-rig.js:255 already does this inside styleModel() for the workout
     engines; this makes it true for every consumer. */
  function uncull(root) {
    if (!root) return 0;
    var n = 0;
    root.traverse(function (o) {
      if (o.isMesh || o.isSkinnedMesh) { o.frustumCulled = false; n++; }
    });
    return n;
  }

  /* ============ PHASE D — the single opacity choke point ============
     Lives here, not in titan3d.js, because this file is the only one loaded by
     EVERY page that puts a character on screen: index.php, pages/features.php,
     member/workouts.php and pages/player.php load both, but
     pages/trainer-studio.php loads cca-wardrobe.js WITHOUT titan3d.js.

     Called from: (a) titan3d.js fitModel() after a GLB is styled,
                  (b) setMap() below after every texture swap,
                  (c) character switch (Phase E).

     Why this is needed at all — measured, not assumed:
       · every character GLB declares its body material alphaMode=BLEND
         (trainers.glb/trainer-pro.glb: Body_MAT, Brows_MAT, Eyes_MAT;
          trainer-street.glb/trainer-ch06.glb: Ch06_body, Ch06_eyelashes).
         Run `node _audit/opaque-check.mjs` to reproduce. THAT is the
         see-through character — it ships that way in the asset.
       · the outfit PNGs are RGBA with genuine holes (~1-2% of texels below
         alpha 255, min 0). three.js does not derive `transparent` from a map's
         alpha, so those holes stay inert only while transparent=false and
         alphaTest=0 — which is exactly what a swap must re-assert. */
  function forceOpaque(root) {
    if (!root || !w.THREE) return 0;
    var n = 0;
    root.traverse(function (o) {
      if (!o.isMesh && !o.isSkinnedMesh) return;
      if (!o.material) return;
      (Array.isArray(o.material) ? o.material : [o.material]).forEach(function (m) {
        if (!m) return;
        m.transparent = false;
        m.opacity     = 1;
        m.alphaTest   = 0;
        m.depthWrite  = true;
        m.depthTest   = true;
        m.side        = w.THREE.FrontSide;   // stop back-face bleed through the torso
        m.alphaMap    = null;
        if ('blending' in m) m.blending = w.THREE.NormalBlending;
        m.needsUpdate = true;
        n++;
      });
    });
    return n;
  }

  function apply(root, ld, onDone) {
    if (!root) return { cap: false, glasses: false };
    uncull(root);
    ld = ld || load();

    /* 3D Socket Gear & Outfits Engine (Free Fire / GTA Style) */
    if (w.CCAGearEngine && w.CCAGearEngine.attach) {
      w.CCAGearEngine.attach(root, ld);
    }

    /* accessories: pure visibility. Only slots this trainer actually offers can
       be turned on; the retired glasses mesh is always hidden. */
    var allowed = extrasFor(ld.trainer);
    var state = { cap: false, glasses: false };
    root.traverse(function (o) {
      if (!o.isMesh && !o.isSkinnedMesh) return;
      var slot = accessorySlot(o.name);
      if (!slot) return;
      var on = slot !== 'glasses' && allowed.indexOf(slot) !== -1 && !!ld[slot];
      o.visible = on;
      state[slot] = on;
    });

    /* outfit: swap the clothing texture on the character's own body material */
    var mats = [];
    root.traverse(function (o) {
      if (!o.isMesh && !o.isSkinnedMesh) return;
      if (accessorySlot(o.name)) return;                 // never repaint the cap
      (Array.isArray(o.material) ? o.material : [o.material]).forEach(function (m) {
        if (m && m.map && mats.indexOf(m) === -1 &&
            (OUTFIT_MATERIALS.indexOf(m.name) !== -1 || m.__ccaOutfit)) mats.push(m);
      });
    });
    if (!mats.length) { if (onDone) onDone(state); return state; }

    /* Remember each material's ORIGINAL map on the material itself. A single
       module-level "baseMap" was wrong: it kept the first model's texture, so
       after switching trainer nothing matched and no outfit ever applied. */
    mats.forEach(function (m) { if (m.__ccaBaseMap === undefined) m.__ccaBaseMap = m.map; });

    function setMap(tex) {
      mats.forEach(function (m) {
        m.map = tex || m.__ccaBaseMap;
        m.needsUpdate = true;
      });
      forceOpaque(root);   // PHASE D: re-assert after every swap — see forceOpaque() above
      if (onDone) onDone(state);
    }

    var of = outfitById(ld.trainer, ld.outfit);
    if (!of.tex) { setMap(null); return state; }          // back to the model's own texture
    if (texCache[of.tex]) { setMap(texCache[of.tex]); return state; }

    var ref = mats[0].__ccaBaseMap;
    var base = (w.TF && w.TF.baseUrl) ? w.TF.baseUrl + '/' : '';
    new THREE.TextureLoader().load(base + of.tex, function (tex) {
      if (ref) {
        tex.flipY = ref.flipY; tex.wrapS = ref.wrapS; tex.wrapT = ref.wrapT;
        if ('colorSpace' in ref) tex.colorSpace = ref.colorSpace;
        else if ('encoding' in ref) tex.encoding = ref.encoding;
      }
      texCache[of.tex] = tex; setMap(tex);
    }, undefined, function () {
      console.warn('[wardrobe] outfit texture failed:', of.tex);
      if (onDone) onDone(state);
    });
    return state;
  }

  function resetBaseMap() { /* per-material now; kept for callers */ }

  w.CCAWardrobe = {
    TRAINERS: TRAINERS, DEFAULT: DEFAULT,
    load: load, save: save, apply: apply, uncull: uncull, resetBaseMap: resetBaseMap,
    forceOpaque: forceOpaque,
    modelUrlFor: modelUrlFor, trainerById: trainerById,
    outfitsFor: outfitsFor, outfitById: outfitById,
    extrasFor: extrasFor, EXTRA_LABEL: EXTRA_LABEL
  };
  /* Top-level alias: titan3d.js fitModel() and any page-level scene code can
     re-assert opacity without reaching into the wardrobe module. */
  w.CCAForceOpaque = forceOpaque;
})(window);
