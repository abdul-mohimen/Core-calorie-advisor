/* ============ CORE CALORIE ADVISOR — Procedural Mixamo Rig Animator ==================
   Drives a REAL rigged human GLB (the "MocapGuy" / any Mixamo character) by its
   bone names, so a single-clip model can PERFORM every exercise. No extra motion
   files needed — each exercise is generated procedurally on the live skeleton.

   Why: trainer.glb ships with only ONE embedded clip, so the AnimationMixer plays
   the same motion for every workout. Instead we bind the standard Mixamo bones and
   pose them per-frame, giving distinct, human squats / push-ups / curls / etc.

   Usage (both hero + player engines):
     if (TitanRig.attach(gltf.scene)) { …skip the mixer, drive procedurally… }
     // each frame:
     TitanRig.update(mode, elapsedSeconds);

   The rig is a STANDARD Mixamo T-pose (arms out along ±X, legs down −Y, spine +Y,
   character faces +Z toward the camera). All joint rotations below are expressed in
   WORLD axes and layered on top of the bind pose, so they stay correct regardless
   of model scale or which way a parent bone was already rotated this frame.
   ========================================================================== */
(function () {
  'use strict';
  if (typeof THREE === 'undefined') { console.warn('[TitanRig] THREE not loaded'); return; }

  // World axes (character up = +Y, front = +Z, character-left = +X)
  var AX = new THREE.Vector3(1, 0, 0);
  var AY = new THREE.Vector3(0, 1, 0);
  var AZ = new THREE.Vector3(0, 0, 1);

  /* How far the hands sit outside the shoulder line during push-ups /
     mountain climbers, in radians. Exposed as a named constant (and tunable
     via window.__pushupSpread) so the value can be swept in the browser
     instead of guessed. */
  var PUSHUP_HAND_SPREAD = 0.22;
  /* Which WORLD axis swings the planted arms apart sideways. proneBrace()
     re-orients the root (YXZ: π/2, -π/2, 0), which remaps the character's local
     axes, so this is resolved by measurement in the browser rather than derived
     — override live with window.__pushupSpreadAxis = 'X' | 'Y' | 'Z'. */
  var PUSHUP_SPREAD_AXIS = 'Y';
  function spreadAxis() {
    var a = (typeof window !== 'undefined' && window.__pushupSpreadAxis) || PUSHUP_SPREAD_AXIS;
    return a === 'X' ? AX : (a === 'Z' ? AZ : AY);
  }
  function spreadAmt() {
    var v = (typeof window !== 'undefined') ? window.__pushupSpreadAmt : null;
    return (v == null) ? PUSHUP_HAND_SPREAD : v;
  }

  // key → candidate node names (mixamorig prefix optional / separator-insensitive)
  var BONE_NAMES = {
    hips:      ['mixamorig:Hips', 'Hips'],
    spine:     ['mixamorig:Spine', 'Spine'],
    spine1:    ['mixamorig:Spine1', 'Spine1'],
    spine2:    ['mixamorig:Spine2', 'Spine2'],
    neck:      ['mixamorig:Neck', 'Neck'],
    head:      ['mixamorig:Head', 'Head'],
    lShoulder: ['mixamorig:LeftShoulder', 'LeftShoulder'],
    lArm:      ['mixamorig:LeftArm', 'LeftArm'],
    lForeArm:  ['mixamorig:LeftForeArm', 'LeftForeArm'],
    lHand:     ['mixamorig:LeftHand', 'LeftHand'],
    rShoulder: ['mixamorig:RightShoulder', 'RightShoulder'],
    rArm:      ['mixamorig:RightArm', 'RightArm'],
    rForeArm:  ['mixamorig:RightForeArm', 'RightForeArm'],
    rHand:     ['mixamorig:RightHand', 'RightHand'],
    lUpLeg:    ['mixamorig:LeftUpLeg', 'LeftUpLeg'],
    lLeg:      ['mixamorig:LeftLeg', 'LeftLeg'],
    lFoot:     ['mixamorig:LeftFoot', 'LeftFoot'],
    rUpLeg:    ['mixamorig:RightUpLeg', 'RightUpLeg'],
    rLeg:      ['mixamorig:RightLeg', 'RightLeg'],
    rFoot:     ['mixamorig:RightFoot', 'RightFoot']
  };
  var KEYS = Object.keys(BONE_NAMES);

  var B = {};        // key → bone Object3D
  var bind = {};     // key → bind local quaternion
  var root = null;   // gltf.scene
  var basePos = new THREE.Vector3();
  var attached = false;

  var leftDumbbell = null;
  var rightDumbbell = null;

  function createDumbbellMesh() {
    var dbGroup = new THREE.Group();
    var handleGeom = new THREE.CylinderGeometry(0.018, 0.018, 0.26, 10);
    var handleMat = new THREE.MeshStandardMaterial({ color: 0xaaaaaa, roughness: 0.12, metalness: 0.95 });
    var handle = new THREE.Mesh(handleGeom, handleMat);
    handle.rotation.x = Math.PI / 2;
    handle.castShadow = true;
    dbGroup.add(handle);

    // Knurled grip texture in the centre
    var gripGeom = new THREE.CylinderGeometry(0.022, 0.022, 0.14, 10);
    var gripMat = new THREE.MeshStandardMaterial({ color: 0x333333, roughness: 0.9, metalness: 0.1 });
    var grip = new THREE.Mesh(gripGeom, gripMat);
    grip.rotation.x = Math.PI / 2;
    dbGroup.add(grip);

    var weightGeom = new THREE.CylinderGeometry(0.08, 0.08, 0.06, 14);
    var weightMat = new THREE.MeshStandardMaterial({ color: 0x181a1c, roughness: 0.55, metalness: 0.35 });
    
    // Two plates per side for a chunky realistic look
    var w1 = new THREE.Mesh(weightGeom, weightMat);
    w1.position.z = 0.10;
    w1.rotation.x = Math.PI / 2;
    w1.castShadow = true;
    w1.receiveShadow = true;
    dbGroup.add(w1);

    var w1b = new THREE.Mesh(new THREE.CylinderGeometry(0.065, 0.065, 0.04, 14), weightMat);
    w1b.position.z = 0.14;
    w1b.rotation.x = Math.PI / 2;
    w1b.castShadow = true;
    dbGroup.add(w1b);

    var w2 = new THREE.Mesh(weightGeom, weightMat);
    w2.position.z = -0.10;
    w2.rotation.x = Math.PI / 2;
    w2.castShadow = true;
    w2.receiveShadow = true;
    dbGroup.add(w2);

    var w2b = new THREE.Mesh(new THREE.CylinderGeometry(0.065, 0.065, 0.04, 14), weightMat);
    w2b.position.z = -0.14;
    w2b.rotation.x = Math.PI / 2;
    w2b.castShadow = true;
    dbGroup.add(w2b);

    return dbGroup;
  }

  function norm(s) { return String(s).toLowerCase().replace(/[^a-z0-9]/g, ''); }

  /* Bone-name normaliser for the SKELETON specifically.
     Mixamo is not consistent about its namespace separator across exports:
       trainers.glb  ships  "mixamorig:Hips"   → norm() gives "mixamorighips"
       Ch06 FBX      ships  "mixamorig9Hips"   → norm() gives "mixamorig9hips"
     Those never compare equal, so a perfectly good Mixamo skeleton was rejected
     with "rig not recognised". Collapsing the whole `mixamorig<anything>` prefix
     to a single token makes the matcher work across every export variant. */
  function normBone(s) {
    return norm(s).replace(/^mixamorig[0-9]*/, 'mixamorig');
  }

  /* ---- cosmetic head-prop stripping -----------------------------------------
     The shipped "MocapGuy" wears a peaked Hat mesh (its own node "MocapGuy_Hat",
     material "Reflectors") that covers the head/hair and looks off on a fitness
     coach. We hide it so the full, clean athletic body reads. ⚠️ NEVER test for
     "cap" — it is a substring of "moCAPguy" (every mesh name carries that prefix),
     so it would hide the ENTIRE character. Match hat/headwear tokens only. */
  var PROP_TOKENS = ['hat', 'helmet', 'beanie', 'headwear', 'visor', 'fedora'];
  function isProp(name) {
    var n = norm(name);
    for (var i = 0; i < PROP_TOKENS.length; i++) if (n.indexOf(PROP_TOKENS[i]) !== -1) return true;
    return false;
  }
  // false if the object OR any ancestor is hidden (three.js visibility is inherited)
  function renderable(o) {
    for (var p = o; p; p = p.parent) if (p.visible === false) return false;
    return true;
  }
  /* Head-prop handling. ⚠️ PRODUCT DECISION (2026-07-13): the GLB has NO hair mesh —
     the cap is the character's ONLY head covering, so stripping it (the old default)
     left the trainer looking bald/glitched. The cap now stays ON so the athlete reads
     as "coach in a training cap". Set `TitanRig.stripHat = true` before load to get
     the old bald behaviour back. Returns how many meshes were hidden. */
  function hideProps(node) {
    if (!node || !window.TitanRig || !window.TitanRig.stripHat) return 0;
    var count = 0;
    node.traverse(function (o) {
      if (o.name && isProp(o.name) && o.visible !== false) { o.visible = false; count++; }
    });
    if (count) console.info('[TitanRig] stripped ' + count + ' head-prop mesh(es) (hat)');
    return count;
  }

  /* -------- runtime texture repaint: mocap suit → clean athletic wear ----------
     The GLB's single diffuse atlas (MocapSuit02_diffuse, 2048²) paints EVERYTHING:
     face+hair, suit, gloves, cap, shoes, teeth, eyes. The suit fabric carries dozens
     of BAKED-IN red/maroon oval mocap marker patches — they can't be hidden by mesh
     visibility (they're pixels, not geometry). Fix: draw the atlas to a canvas once,
     erase every warm-hued pixel in suit territory (repaint with luminance-shaded
     fabric so seams/folds survive), and protect the photo regions (face/hair, eye,
     mouth, teeth, sneakers) so skin stays natural. Regions measured off the real
     atlas as u/v fractions — resolution-independent. */
  var KEEP_ZONES = [            // [x0, y0, x1, y1] fractions of the atlas
    [0.000, 0.145, 0.505, 0.510],   // face, hair, ears, neck skin
    [0.850, 0.000, 0.985, 0.115],   // eyeball
    [0.390, 0.110, 0.500, 0.220],   // mouth interior
    [0.455, 0.740, 0.520, 0.870],   // teeth
    [0.480, 0.320, 0.735, 0.590]    // sneakers (keep their red accents)
  ];
  var FABRIC = [26, 27, 30];        // base kit: matte charcoal activewear
  /* Trainer-kit recolor zones painted over suit territory: [x0,y0,x1,y1, r,g,b]
     in u/v fractions of the atlas (same convention as KEEP_ZONES). Filled from
     direct atlas measurement — lets the single mocap suit read as a real outfit
     (top / bottoms / accents) instead of one flat wetsuit. */
  /* Trainer-kit recolor zones painted over suit territory: [x0,y0,x1,y1, r,g,b]
     in u/v fractions of the atlas (same convention as KEEP_ZONES). Expanded to
     cover shirt, pants, shoes, and accents as SEPARATE zones so users can
     customize each piece independently via setClothes(). */
  var SUIT_ZONES = [
    // [x0 fractions, y0, x1, y1, r, g, b, zoneTag]
    [0.000, 0.510, 0.480, 0.700, 255, 107, 26, 'shirt'],    // Upper torso / shirt
    [0.200, 0.700, 0.480, 0.780, 255, 107, 26, 'shirt'],    // Shirt sleeve extensions
    [0.480, 0.600, 0.900, 0.780, 255, 184, 0,  'accent'],   // Gold athletic stripes & highlights
    [0.000, 0.780, 0.480, 0.920, 26,  28,  32, 'pants'],    // Pants / lower body
    [0.480, 0.780, 0.900, 0.920, 30,  32,  36, 'pants'],    // Pants side panels
    [0.200, 0.920, 0.800, 1.000, 20,  24,  30, 'shoes'],    // Footwear zone
    [0.480, 0.500, 0.700, 0.600, 40,  42,  48, 'accent'],   // Shoulder cap accents
  ];
  /* SKIN TONE — one fixed, natural mid/wheatish tone.
     History: this was [0.50, 0.40, 0.34], an aggressive "fair-skin" lift that
     washed the face out. Zeroing it swung too far the other way — with the dark
     charcoal kit the character read as almost black rather than as a person.
     [0.22, 0.17, 0.13] is a gentle warm lift: enough to bring the face and arms
     to a natural mid tone, small enough that hair, brows and shadows stay put.
     It is a CONSTANT — there is no runtime way to change it (setClothes and the
     Customize panel are both gone), which is the "ik hi rakho" requirement. */
  /* MEASURED from the shipped atlas (2048², 130k skin-hued pixels sampled in the
     face/neck zone): the baked tone averages rgb(112,73,66) = #704942, median
     luminance 74/255 — a very dark red-brown, which is why the coach read as
     "kaala" on screen.

     The old approach lifted each channel TOWARD WHITE:
         r += (255 - r) * LIFT * f
     At LIFT 0.22 that moved the average by just +16, so nothing visibly
     changed; at 0.50 it washed the face out grey. Lifting toward white is the
     wrong operation for skin — it desaturates instead of warming.

     Instead we retarget: a per-channel GAIN that moves the measured average to
     a natural warm mid tone, applied multiplicatively so every fold, shadow and
     highlight keeps its relative shading. */
  var SKIN_SRC_AVG = [112, 73, 66];      // measured, do not edit without re-measuring
  /* Target is set ABOVE the tone we want on screen. Measured feedback loop:
     target [196,138,92] produced an actual average of [153,99,72] — the gate
     skips some pixels and the highlight soft-clip pulls the mean down, so the
     realised tone lands ~78% of the way to target. [232,164,110] therefore
     renders as roughly [181,128,86] = a natural warm mid tone. */
  var SKIN_TARGET  = [232, 164, 110];
  var SKIN_GAIN = [
    SKIN_TARGET[0] / SKIN_SRC_AVG[0],    // ≈ 1.75
    SKIN_TARGET[1] / SKIN_SRC_AVG[1],    // ≈ 1.89
    SKIN_TARGET[2] / SKIN_SRC_AVG[2]     // ≈ 1.39
  ];

  /* KIT COLOURS — fixed, not user-customisable.
     These were bright brand orange (255,107,26) and gold (255,184,0), which
     made the trainer read as a costume rather than a person in gym clothes,
     and they were editable at runtime through a "Customize" panel. Both the
     panel and the setClothes() API are gone; these are now constants tuned to
     look like real matte activewear. */
  /* Mid-grey rather than charcoal. At [46,49,56] the kit was so dark that the
     whole athlete rendered as a silhouette under the hero's dim key light —
     the lighter skin tone could not read against it. These sit high enough to
     show fabric shading and separate the body from the dark stage, while still
     looking like real training wear rather than a costume. */
  var clothesColors = {
    shirt:  [96, 102, 112],   // slate training top
    pants:  [72, 77, 86],     // darker slate bottoms
    shoes:  [46, 49, 56],     // charcoal trainers
    accent: [128, 134, 145]   // light steel seams
  };
  var lastRepaintedMat = null;  // track which material was repainted
  function liftSkin(p, i4) {
    var r = p[i4], g = p[i4 + 1], b = p[i4 + 2];
    // Gate unchanged: hair, brows, eyes and deep shadow must not be retinted.
    if (r < 52 || r < g - 8 || g < b - 16) return;
    /* Multiplicative retarget. Because it scales rather than adds, a pixel that
       was 40% brighter than its neighbour stays 40% brighter — folds, creases
       and the shadow under the jaw all survive. Soft-clip the top end so the
       brightest highlights roll off instead of clipping to flat white. */
    var nr = r * SKIN_GAIN[0], ng = g * SKIN_GAIN[1], nb = b * SKIN_GAIN[2];
    p[i4]     = nr > 245 ? 245 + (nr - 245) * 0.15 : nr;
    p[i4 + 1] = ng > 245 ? 245 + (ng - 245) * 0.15 : ng;
    p[i4 + 2] = nb > 245 ? 245 + (nb - 245) * 0.15 : nb;
  }
  function cleanSuitTexture(m) {
    var tex = m.map;
    if (!tex || tex.__tfCleaned || typeof document === 'undefined') return;
    var img = tex.image;
    if (!img || !img.width || !img.height) return;
    try {
      var w = img.width, h = img.height;
      var cv = document.createElement('canvas'); cv.width = w; cv.height = h;
      var cx = cv.getContext('2d', { willReadFrequently: true });
      cx.drawImage(img, 0, 0, w, h);
      // Backup the original image for re-paints (clothes colour changes)
      if (!tex.__tfOrigImage) tex.__tfOrigImage = img;
      var d = cx.getImageData(0, 0, w, h), p = d.data;
      var toPx = function (z) {
        // Invert Y-axis: WebGL UV space origin is bottom-left, Canvas origin is top-left
        return [z[0] * w, (1 - z[3]) * h, z[2] * w, (1 - z[1]) * h];
      };
      var zones = KEEP_ZONES.map(toPx);
      var suitZones = SUIT_ZONES.map(function (z) { var r2 = toPx(z); r2.push(z[4], z[5], z[6]); return r2; });
      for (var y = 0; y < h; y++) {
        for (var x = 0; x < w; x++) {
          var zi = -1;
          for (var k = 0; k < zones.length; k++) {
            var z = zones[k];
            if (x >= z[0] && x <= z[2] && y >= z[1] && y <= z[3]) { zi = k; break; }
          }
          var i4 = (y * w + x) * 4;
          if (zi === 0) { liftSkin(p, i4); continue; }   // face/hair/neck → fair skin
          if (zi !== -1) continue;                        // eyes/mouth/teeth/sneakers untouched
          var r = p[i4], g = p[i4 + 1], b = p[i4 + 2];
          var lum = (r + g + b) / 3;
          // RESURFACE of suit territory: trainer-kit zone colour (or base fabric). The
          // original luminance drives the shading but hard-compressed (0.8–1.2×) so
          // baked-in marker patches flatten into the cloth instead of reading as blocks.
          var col = FABRIC;
          for (var s = 0; s < suitZones.length; s++) {
            var sz = suitZones[s];
            if (x >= sz[0] && x <= sz[2] && y >= sz[1] && y <= sz[3]) {
              var tag = SUIT_ZONES[s] ? (SUIT_ZONES[s][7] || '') : '';
              if (tag && clothesColors[tag]) {
                col = clothesColors[tag];
              } else {
                col = [sz[4], sz[5], sz[6]];
              }
              break;
            }
          }
          var f = 0.8 + 0.4 * (lum / 255);
          p[i4] = col[0] * f; p[i4 + 1] = col[1] * f; p[i4 + 2] = col[2] * f;
        }
      }
      cx.putImageData(d, 0, 0);
      tex.image = cv;
      tex.needsUpdate = true;
      tex.__tfCleaned = true;
      lastRepaintedMat = m;
      // Matte cloth: the source material ships semi-gloss (roughness .55), which throws
      // blotchy specular hot-spots off every fold under the point lights. Fabric isn't shiny.
      if ('roughness' in m) m.roughness = 0.88;
      if ('metalness' in m) m.metalness = 0.0;
      m.needsUpdate = true;
      console.info('[TitanRig] suit texture repainted — mocap marker patches erased, skin/face preserved');
    } catch (e) { console.warn('[TitanRig] texture repaint skipped:', e); }
  }

  /* ---- setClothes(opts): user-facing API to change shirt/pants/shoes/accent colours.
     `opts` = { shirt: '#FF6B1A', pants: '#1A1C20', shoes: '#141618', accent: '#FFB800' }
     Each key is optional; only provided keys are changed. Triggers an immediate texture
     repaint on the current model. ---- */
  function hexToRGB(hex) {
    hex = hex.replace('#', '');
    return [
      parseInt(hex.substring(0, 2), 16),
      parseInt(hex.substring(2, 4), 16),
      parseInt(hex.substring(4, 6), 16)
    ];
  }

  function setClothes(opts) {
    if (!opts) return;
    var changed = false;
    ['shirt', 'pants', 'shoes', 'accent'].forEach(function(key) {
      if (opts[key]) {
        clothesColors[key] = typeof opts[key] === 'string' ? hexToRGB(opts[key]) : opts[key];
        changed = true;
      }
    });
    if (!changed) return;

    // Force re-repaint: clear the cleaned flag and re-run cleanSuitTexture on the model
    if (root) {
      root.traverse(function(o) {
        if (!o.isMesh || !o.material) return;
        var mats = Array.isArray(o.material) ? o.material : [o.material];
        for (var i = 0; i < mats.length; i++) {
          var m = mats[i];
          if (m && m.map && m.map.__tfCleaned) {
            // Restore the original image data from the backup, then repaint with new colors
            if (m.map.__tfOrigImage) {
              m.map.image = m.map.__tfOrigImage;
            }
            m.map.__tfCleaned = false;
            cleanSuitTexture(m);
          }
        }
      });
    }
    console.info('[TitanRig] clothes updated:', JSON.stringify(clothesColors));
  }

  /* Cosmetic material clean-up so the athlete reads well:
       • hide the mocap-suit retro-reflector dots (material "Reflectors") → clean body
       • kill the glowing eyes (material "Eyes…") so the face looks natural, not creepy
       • repaint the shared diffuse atlas (see cleanSuitTexture above)
     GLTFLoader splits a multi-primitive mesh into one child mesh per material, so
     hiding a child drops just that primitive. Safe to call more than once. */
  function styleModel(node) {
    if (!node) return;
    node.traverse(function (o) {
      if (!o.isMesh || !o.material) return;
      var mats = Array.isArray(o.material) ? o.material : [o.material];
      for (var i = 0; i < mats.length; i++) {
        var m = mats[i]; if (!m) continue;
        var mn = String(m.name || '').toLowerCase();
        var meshName = String(o.name || '').toLowerCase();
        if (m.map) {
          console.info("STYLE_DEBUG: Mesh:", o.name, "| Mat:", m.name, "| Map:", m.map.image ? (m.map.image.src || (m.map.image.width + 'x' + m.map.image.height)) : 'no image');
        }
        if (mn.indexOf('reflector') !== -1 && meshName.indexOf('hat') === -1) { o.visible = false; }      // drop joint dots
        else if (mn.indexOf('eye') !== -1) {                            // calm the eye glow
          if (m.emissive) m.emissive.setRGB(0, 0, 0);
          m.emissiveIntensity = 0;
          if ('metalness' in m) m.metalness = Math.min(m.metalness, 0.2);
          m.needsUpdate = true;
        }
        else if (m.map) cleanSuitTexture(m);                            // suit → clean athletic wear
      }
    });
  }

  /* Robust size/centre for a rigged glTF. `Box3.setFromObject()` is WRONG for
     skinned meshes exported under a scaled Armature (Blender bakes a 0.01 armature
     scale + 90° up-axis fix), so it reports a ~0.003-unit-tall box and callers then
     blow the model up ~540×, pushing the real (bone-driven) body kilometres tall and
     off-camera. The raw per-geometry bounding boxes ARE authored in final world
     metres (feet ≈ 0, head ≈ 1.8, Y-up), which is exactly where the skin renders, so
     union those instead. Hidden meshes (e.g. a stripped hat) are skipped so they
     don't inflate the height. Falls back to setFromObject for non-skinned models. */
  function measure(obj) {
    obj.updateMatrixWorld(true);
    var box = new THREE.Box3(), tb = new THREE.Box3(), has = false;
    /* This unions raw geometry bounding boxes (bind space) rather than world
       boxes, which looks wrong at first glance — but it is correct in practice
       and must not be "fixed" casually. `fit()` applies the resulting scale to
       the glTF Scene node, and the character's own Armature scale is part of
       the same chain, so the ratio works out. Two things make world-space
       measurement the WRONG tool here: a SkinnedMesh's geometry.boundingBox is
       the bind pose while its matrixWorld excludes skinning, so on trainers.glb
       MocapGuy_Body_1 measures 16.25 x 2.70 x 14.89 — a body six times wider
       than tall. Verified 2026-07-30: the shipped behaviour puts HeadTop_End at
       y=2.65 and the hips at y=1.48, i.e. a correctly proportioned 2.7-unit
       figure standing on the floor. */
    obj.traverse(function (o) {
      if (o.isMesh && o.geometry && renderable(o)) {
        if (!o.geometry.boundingBox) o.geometry.computeBoundingBox();
        var bb = o.geometry.boundingBox;
        if (bb && isFinite(bb.min.x) && isFinite(bb.max.y)) { tb.copy(bb); box.union(tb); has = true; }
      }
    });
    if (!has || !isFinite(box.min.x)) box.setFromObject(obj);
    var size = new THREE.Vector3(), ctr = new THREE.Vector3();
    box.getSize(size); box.getCenter(ctr);
    return { size: size, center: ctr, min: box.min.clone(), max: box.max.clone(), skinned: has };
  }

  function fit(obj, targetH) {
    targetH = targetH || 2.7;
    hideProps(obj);                 // no-op unless TitanRig.stripHat — cap stays on by default
    styleModel(obj);                // clean up dots + eye glow
    var m = measure(obj);
    var s = targetH / (m.size.y || 1);
    obj.scale.setScalar(s);
    obj.position.set(-m.center.x * s, -m.min.y * s, -m.center.z * s);
    return s;
  }

  /* Suggested camera for a mode so the whole body stays framed. Standing work is
     shot head-to-toe with headroom for overhead reaches; floor work (push-ups,
     plank, crunches…) is laid out side-on near the ground, so the camera drops and
     looks lower to keep it centred instead of sinking off the bottom of the view.
     Engines lerp toward this each frame. Returns metres in the fitted model space. */
  var FLOOR_MODES = { pushup: 1, plank: 1, mountain: 1, crunch: 1, legraise: 1 };
  var CAM_STAND = { pos: [0, 1.9, 5.6],  target: [0, 1.35, 0] };
  // Floor work (push-ups, plank, crunches…) lays the body near the ground, so drop the
  // camera low and tilt it DOWN toward the floor and pull in closer — the old preset
  // (y 1.55, target 0.68) barely dipped below the standing shot, leaving the athlete
  // sinking off the bottom of the view. Now the whole prone/supine body stays centred.
  var CAM_FLOOR = { pos: [0, 1.35, 5.0], target: [0, 0.45, 0] };
  function cameraFor(mode) { return FLOOR_MODES[mode] ? CAM_FLOOR : CAM_STAND; }

  /* ---- floor-anchor: plant the body ON the ground so it stops "swimming" --------
     The prone/supine poses rotate the whole rig about its feet, which used to leave
     the body floating ~0.6 units in the air on a GUESSED height offset (the reported
     "swimming in mid-air"). Instead we read the REAL posed world-Y of the ground-
     contact bones (hands+feet for prone work, hips+feet/hands for supine) — bones are
     truly posed, unlike Box3.setFromObject which ignores skinning in r128 — and drop
     the root so the lowest contact bone rests right on the floor (y ≈ clearance). */
  var CONTACT_BONES = {
    pushup:   ['lHand', 'rHand', 'lFoot', 'rFoot'],
    plank:    ['lHand', 'rHand', 'lFoot', 'rFoot'],
    mountain: ['lHand', 'rHand', 'lFoot', 'rFoot'],
    crunch:   ['hips', 'lFoot', 'rFoot'],
    legraise: ['hips', 'spine', 'lHand', 'rHand']
  };
  var FLOOR_FALLBACK = { pushup: 0.30, plank: 0.30, mountain: 0.30, crunch: 0.18, legraise: 0.18 };
  
  // Sneaker & palm mesh thickness offsets — tuned to the actual mesh vertex extents
  // of the MocapGuy model's shoes and hands, so the floor contact reads correctly.
  var BONE_OFFSETS = {
    lHand: 0.07,
    rHand: 0.07,
    lFoot: 0.12,
    rFoot: 0.12,
    hips: 0.15,
    spine: 0.15
  };
  var floorBob = 0;             // small per-frame vertical motion (e.g. push-up pump), added after anchoring

  function getBoneOffset(key, m) {
    if (m === 'pushup' || m === 'plank' || m === 'mountain') {
      if (key === 'lFoot' || key === 'rFoot') return 0.18; // toes touch floor
      if (key === 'lHand' || key === 'rHand') return 0.05; // palms & fingers pressed flat to Y=0
    }
    if (m === 'crunch' || m === 'legraise') {
      if (key === 'hips' || key === 'spine') return 0.16; // back flat on floor
      if (key === 'lHand' || key === 'rHand') return 0.07;
    }
    return BONE_OFFSETS[key] || 0;
  }

  function applyFloorAnchor(mode) {
    var keys = CONTACT_BONES[mode];
    if (!keys) { root.position.y = basePos.y + 0.15 + floorBob; return; }
    
    // ── PASS 1: Read contact-bone world Y and shift root to plant on the floor ──
    root.updateMatrixWorld(true);
    
    var minY = Infinity;
    var found = 0;
    
    for (var i = 0; i < keys.length; i++) {
      var bone = B[keys[i]];
      if (bone) {
        bone.getWorldPosition(_wp);
        var offset = getBoneOffset(keys[i], mode);
        var vertexMinY = _wp.y - offset;
        if (vertexMinY < minY) minY = vertexMinY;
        found++;
      }
    }
    
    if (found && isFinite(minY)) {
      // Shift the root so the lowest contact vertex sits at Y = 0
      root.position.y += (0.0 - minY) + floorBob;
      
      // ── PASS 2: Re-read after correction to verify tolerance ──
      root.updateMatrixWorld(true);
      var minY2 = Infinity;
      for (var j = 0; j < keys.length; j++) {
        var bone2 = B[keys[j]];
        if (bone2) {
          bone2.getWorldPosition(_wp);
          var offset2 = getBoneOffset(keys[j], mode);
          var vy2 = _wp.y - offset2;
          if (vy2 < minY2) minY2 = vy2;
        }
      }
      // If still floating more than 2cm above the floor, correct again
      if (isFinite(minY2) && minY2 > 0.02) {
        root.position.y -= (minY2 - 0.0);
        root.updateMatrixWorld(true);
      }
      // If somehow sunk below the floor (negative Y), push back up
      if (isFinite(minY2) && minY2 < -0.02) {
        root.position.y += (-0.02 - minY2);
        root.updateMatrixWorld(true);
      }
    } else {
      root.position.y = basePos.y + (FLOOR_FALLBACK[mode] || 0.15) + floorBob;
    }
  }

  function findBone(names) {
    var hit = null;
    root.traverse(function (o) {
      if (hit || !o.name) return;
      var n = normBone(o.name);
      for (var i = 0; i < names.length; i++) {
        var c = normBone(names[i]);
        if (n === c || n.endsWith(c)) { hit = o; return; }
      }
    });
    return hit;
  }

  function attach(scene) {
    if (!scene) return false;
    root = scene;
    B = {}; bind = {};
    var found = 0;
    for (var i = 0; i < KEYS.length; i++) {
      var k = KEYS[i];
      var b = findBone(BONE_NAMES[k]);
      if (b) { B[k] = b; bind[k] = b.quaternion.clone(); found++; }
    }
    // need the core rig to be worth driving
    var essential = B.hips && B.lArm && B.rArm && B.lUpLeg && B.rUpLeg && B.spine;
    if (!essential) {
      console.warn('[TitanRig] rig not recognised (found ' + found + '/' + KEYS.length + ' bones) — skeleton names differ');
      attached = false;
      return false;
    }
    basePos.copy(root.position);
    attached = true;
    console.info('[TitanRig] attached — ' + found + '/' + KEYS.length + ' bones bound; procedural exercises enabled');
    return true;
  }

  // ---- world-axis rotation layered on the bind pose (parent-first order) ----
  var _pq = new THREE.Quaternion(), _pi = new THREE.Quaternion(),
      _av = new THREE.Vector3(),    _dq = new THREE.Quaternion(),
      _wp = new THREE.Vector3(),    _tq = new THREE.Quaternion();

  function rot(key, axis, angle) {
    var b = B[key];
    if (!b || !angle) return;
    b.parent.updateWorldMatrix(true, false);
    b.parent.getWorldQuaternion(_pq); _pi.copy(_pq).invert();
    _av.copy(axis).applyQuaternion(_pi);
    _dq.setFromAxisAngle(_av, angle);
    b.quaternion.premultiply(_dq);
  }

  function resetPose() {
    for (var i = 0; i < KEYS.length; i++) {
      var k = KEYS[i];
      if (B[k] && bind[k]) B[k].quaternion.copy(bind[k]);
    }
    root.position.copy(basePos);
    root.rotation.set(0, 0, 0);
  }

  /* Prone face-down BRACE for plank / push-up / mountain climber. The body is laid
     side-on to the camera and INCLINED (feet-end dips to the floor, shoulders stay up
     at arm's length) so it reads as a real plank LINE — not a flat "lying down". Both
     upper arms are driven straight DOWN to plant the hands on the floor under the
     shoulders and hold the torso up. `incline` tilts the feet-end toward the ground.
     Empirically tuned via the headless CDP harness (screenshots). */
  function braceAxis(name) { return name === 'X' ? AX : (name === 'Y' ? AY : AZ); }
  /* After the prone root rotation the body lies ALONG world X (head → −X, feet → +X),
     belly down. So the sagittal plane is the world X-Y plane and every "pitch" motion
     (incline, elbow fold, knee drive, spine curl) must rotate about world Z. The old
     code inclined about world X — that ROLLS the prone body like a rotisserie, so the
     feet never dropped: the rig hung level in mid-air with only the hands anchored
     (the reported "floating push-up"). Verified via the CDP screenshot harness:
     inc about Z, NEGATIVE = feet-end down → toes plant at y≈0, hands at shoulders. */
  function proneBrace(incline) {
    root.rotation.set(Math.PI / 2, -Math.PI / 2, 0, 'YXZ');   // face-down, side-on profile
    // Tunables (window.__*) let the CDP harness sweep candidates in ONE page load;
    // the winning values are then baked in as the defaults below.
    var W = (typeof window !== 'undefined') ? window : {};
    var incAxis = braceAxis(W.__incAxis || 'Z');
    var inc = (W.__inc != null) ? W.__inc : incline;
    if (inc) { _tq.setFromAxisAngle(incAxis, inc); root.quaternion.premultiply(_tq); }
    // Upper arms drop straight to the floor under the shoulders (world-X, ±90° — this
    // one really is X: the arms swing in the Y-Z plane from the T-pose).
    var armAxis = braceAxis(W.__armAxis || 'X');
    var armAng = (W.__armAng != null) ? W.__armAng : Math.PI / 2;
    rot('lArm', armAxis, armAng); rot('rArm', armAxis, -armAng);   // upper arms → hands to floor
  }

  // Arms resting down at the sides (base for standing exercises). `tuck` pulls
  // them closer to the torso; `bend` folds the elbows forward a touch.
  function armsDown(tuck, bend) {
    tuck = tuck || 0; bend = (bend == null ? 0.16 : bend);
    rot('lArm', AZ, -1.26 - tuck);
    rot('rArm', AZ,  1.26 + tuck);
    rot('lForeArm', AX, -bend);
    rot('rForeArm', AX, -bend);
  }

  /* Clamp the Y-axis (twist) component of upper-arm quaternions so arms never read
     as "backwards" or "inverted" during compound raises like shoulder press. Extracts
     the twist about the local arm axis and constrains it to ±maxTwist radians. */
  function sanitizeArmTwist(maxTwist) {
    maxTwist = maxTwist || (Math.PI / 12);   // ±15° default
    var arms = ['lArm', 'rArm'];
    for (var a = 0; a < arms.length; a++) {
      var bone = B[arms[a]];
      if (!bone) continue;
      // Decompose quaternion into twist about Y and swing
      var q = bone.quaternion;
      var twistAngle = 2 * Math.atan2(q.y, q.w);
      if (Math.abs(twistAngle) > maxTwist) {
        // Clamp twist while preserving swing
        var clampedTwist = Math.max(-maxTwist, Math.min(maxTwist, twistAngle));
        var halfDiff = (clampedTwist - twistAngle) / 2;
        _tq.setFromAxisAngle(AY, halfDiff);
        q.premultiply(_tq);
      }
    }
  }

  /* Stabilize wrist bones during prone exercises to prevent hyperextension.
     Constrains lHand/rHand X-axis rotation to a reasonable range. */
  function stabilizeWrists() {
    var hands = ['lHand', 'rHand'];
    for (var h = 0; h < hands.length; h++) {
      var bone = B[hands[h]];
      if (!bone) continue;
      // Apply a gentle correction toward neutral wrist alignment
      _tq.setFromAxisAngle(AX, 0.15);   // slight dorsiflexion for natural push-up hand position
      bone.quaternion.slerp(_tq.multiply(bind[hands[h]] || _tq), 0.3);
    }
  }

  /* ---------------------------------------------------------------------------
     update(mode, t) — reset to bind, then sculpt the current exercise pose.
     `t` = clock.getElapsedTime() (seconds). Motions oscillate with sin(t·speed).
     --------------------------------------------------------------------------- */
  function update(mode, t) {
    if (!attached) return;
    resetPose();
    floorBob = 0;                          // reset per-frame floor motion; floor cases set it below

    // Dumbbell attachment logic
    var isDumbbellMode = (mode === 'curl' || mode === 'press');
    if (isDumbbellMode) {
      if (!leftDumbbell) leftDumbbell = createDumbbellMesh();
      if (!rightDumbbell) rightDumbbell = createDumbbellMesh();
      if (B.lHand && leftDumbbell.parent !== B.lHand) {
        B.lHand.add(leftDumbbell);
        leftDumbbell.position.set(0, -0.05, 0);
        leftDumbbell.rotation.set(0, 0, 0);
      }
      if (B.rHand && rightDumbbell.parent !== B.rHand) {
        B.rHand.add(rightDumbbell);
        rightDumbbell.position.set(0, -0.05, 0);
        rightDumbbell.rotation.set(0, 0, 0);
      }
    } else {
      if (leftDumbbell && leftDumbbell.parent) leftDumbbell.parent.remove(leftDumbbell);
      if (rightDumbbell && rightDumbbell.parent) rightDumbbell.parent.remove(rightDumbbell);
    }

    switch (mode) {

      /* ---- SQUATS: authentic hip-hinge & forward counterbalance reach ---- */
      case 'squat': {
        var u = 0.5 - 0.5 * Math.cos(t * 2.4);           // 0 (stand) → 1 (deep)
        rot('lUpLeg', AX, -1.12 * u); rot('rUpLeg', AX, -1.12 * u);   // thighs fold up
        rot('lLeg',   AX,  1.68 * u); rot('rLeg',   AX,  1.68 * u);   // knees track forward/down
        rot('lFoot',  AX, -0.54 * u); rot('rFoot',  AX, -0.54 * u);   // ankles flex → flat contact
        rot('spine',  AX,  0.32 * u); rot('spine1', AX, 0.10 * u);    // athletic neutral torso lean
        
        // Counterbalance reach: arms raise smoothly from side to shoulder height in front
        rot('lArm', AZ, -1.26 + 1.20 * u);  // raise arm from side (-1.26) up toward horizontal
        rot('rArm', AZ,  1.26 - 1.20 * u);
        rot('lArm', AX, 0.85 * u);          // pitch arm forward into front view
        rot('rArm', AX, 0.85 * u);
        rot('lForeArm', AX, -0.25 * u);     // soft natural elbow bend (15°)
        rot('rForeArm', AX, -0.25 * u);
        
        root.position.y = basePos.y - 0.62 * u;                       // sink so feet stay planted
        break;
      }

      /* ---- BICEP CURLS: strict elbow flexion in front of chest ---- */
      case 'curl': {
        var u2 = 0.5 - 0.5 * Math.cos(t * 3.4);
        // Keep upper arms pinned slightly in front of the ribs
        rot('lArm', AZ, -1.28);
        rot('rArm', AZ,  1.28);
        rot('lArm', AX, 0.12);
        rot('rArm', AX, 0.12);
        // Curl forearm smoothly upward toward anterior deltoids in front of body
        rot('lForeArm', AX, -0.20 - 1.95 * u2);
        rot('rForeArm', AX, -0.20 - 1.95 * u2);
        rot('spine', AX, 0.025 * Math.sin(t * 3.4));
        break;
      }

      /* ---- SHOULDER PRESS: hands rack at shoulders, press overhead ---- */
      case 'press': {
        var p = 0.5 - 0.5 * Math.cos(t * 3.0);           // 0 rack → 1 locked out
        // Keep the whole press in a clean vertical (coronal) plane. The previous build
        // also tilted the upper arms forward (world-X) AND folded the forearms hard
        // about world-X while they pointed overhead — stacking a large AZ raise with two
        // AX rotations twisted the limb so it read "inverted/backwards". Dropping the
        // forward tilt and softening the fold keeps the raise readable without the twist.
        rot('lArm', AZ, 1.20 + 0.35 * p); rot('rArm', AZ, -1.20 - 0.35 * p);  // straight up along the body
        rot('lForeArm', AX, -0.85 * (1 - p));            // gently folded when racked, straight at lockout
        rot('rForeArm', AX, -0.85 * (1 - p));
        root.position.y = basePos.y + 0.015 * p;
        sanitizeArmTwist(Math.PI / 12);   // ±15° twist clamp prevents backwards-looking arms
        break;
      }

      /* ---- JUMPING JACKS: arms sweep to overhead, legs spread, hop ---- */
      case 'jumpingjack': {
        var s = Math.sin(t * 6.0), u3 = 0.5 + 0.5 * s;   // 0 closed → 1 open
        rot('lArm', AZ, -1.26 + u3 * 2.70); rot('rArm', AZ, 1.26 - u3 * 2.70); // down↔overhead
        rot('lUpLeg', AZ, 0.30 * u3); rot('rUpLeg', AZ, -0.30 * u3);            // legs spread
        rot('lArm', AX, -0.05); rot('rArm', AX, -0.05);
        root.position.y = basePos.y + Math.max(0, s) * 0.10;                    // little hop
        break;
      }

      /* ---- HIGH KNEES: run in place, knees drive up, arms pump ---- */
      case 'highknees': {
        var kl = Math.max(0, Math.sin(t * 6.5));
        var kr = Math.max(0, Math.sin(t * 6.5 + Math.PI));
        armsDown(0.10, 0.9);
        rot('lUpLeg', AX, -1.45 * kl); rot('lLeg', AX, 1.15 * kl);
        rot('rUpLeg', AX, -1.45 * kr); rot('rLeg', AX, 1.15 * kr);
        rot('lForeArm', AX, -0.6 * kr); rot('rForeArm', AX, -0.6 * kl);  // opposite arm pumps
        root.position.y = basePos.y + 0.06 * Math.abs(Math.sin(t * 6.5));
        break;
      }

      /* ---- RUSSIAN TWISTS: torso rotates side to side, hands in front ---- */
      case 'twist': {
        var tw = Math.sin(t * 2.6);
        rot('lUpLeg', AX, -1.15); rot('rUpLeg', AX, -1.15);   // seated: thighs up
        rot('lLeg', AX, 1.0); rot('rLeg', AX, 1.0);
        rot('spine',  AX, 0.45); rot('spine1', AX, 0.10);     // lean back a bit
        rot('lArm', AY, -1.35); rot('rArm', AY, 1.35);        // hands clasped ahead
        rot('lArm', AX, -0.55); rot('rArm', AX, -0.55);
        rot('lForeArm', AX, -0.5); rot('rForeArm', AX, -0.5);
        rot('spine1', AY, 0.55 * tw); rot('spine2', AY, 0.35 * tw); // the twist
        rot('lArm', AY, 0.35 * tw);  rot('rArm', AY, 0.35 * tw);
        root.position.y = basePos.y - 0.28;
        break;
      }

      /* ---- YOGA: slow, calm — reach overhead, gentle breathing sway ---- */
      case 'yoga': {
        var breath = Math.sin(t * 0.9);
        rot('lArm', AZ, 1.45 + 0.08 * breath); rot('rArm', AZ, -1.45 - 0.08 * breath); // hands high
        rot('lForeArm', AX, -0.05); rot('rForeArm', AX, -0.05);
        rot('spine', AX, 0.03 * breath); rot('spine1', AX, 0.03 * breath);
        rot('rUpLeg', AX, -0.20); rot('rLeg', AX, 0.30);       // one knee softly raised
        rot('head', AX, -0.05);
        break;
      }

      /* ---- WALL PUSH-UPS: upright and distinct from a floor push-up ---- */
      case 'wallpushup': {
        var wp = 0.5 - 0.5 * Math.cos(t * 2.8);
        armsDown(0.05, 0.08);
        rot('spine', AX, 0.16 * wp); rot('spine1', AX, 0.10 * wp);
        rot('lArm', AY, -1.34); rot('rArm', AY, 1.34);
        rot('lArm', AX, -0.12 * wp); rot('rArm', AX, -0.12 * wp);
        rot('lForeArm', AX, -0.78 * wp); rot('rForeArm', AX, -0.78 * wp);
        root.position.z = basePos.z - 0.10 * wp;
        break;
      }

      /* ---- PUSH-UPS: prone brace, chest dips as the elbows fold ----
         Body lies along X (head −X, feet +X, belly down) so ALL sagittal motion is
         about world Z. The upper arm swings back toward the feet while the forearm
         gets the equal-opposite delta, so the forearm stays vertical and the elbow
         visibly folds — then the floor anchor replants hands+toes, which drops the
         chest toward the floor for a real dip. */
      case 'pushup': {
        var d = 0.5 - 0.5 * Math.cos(t * 3.0);           // 0 top (arms straight) → 1 bottom (chest low)
        // 0.09/1.05 tuned via the CDP screenshot sweep: hands AND toes stay planted
        // (max contact error ≈ 4cm) while the head dips 0.94 → 0.75 for a real rep.
        proneBrace(-0.34 + 0.09 * d);                    // body pitches flatter as the chest drops

        /* LEFT/RIGHT HAND PLACEMENT — real push-up form.
           rot() works in WORLD axes, and proneBrace() has already mirrored the
           two arms (+armAng on the left, -armAng on the right). Applying the
           SAME world-Z sweep to both therefore rotated them the same way in
           world space, so both hands drifted to the same side of the body and
           the character appeared to push off with both arms on the right.
           Mirroring the sweep keeps each arm on its own side of the torso. */
        /* Elbow fold — SAME sign on both arms is correct here. (An earlier
           attempt mirrored these, which sent one hand forward and the other
           back along the body axis instead of spreading them sideways.) */
        rot('lArm', AZ, 1.05 * d);      rot('rArm', AZ, 1.05 * d);       // upper arms sweep toward feet
        rot('lForeArm', AZ, -1.05 * d); rot('rForeArm', AZ, -1.05 * d);  // forearms stay vertical → elbow fold

        /* Lateral spread: plant the hands one LEFT and one RIGHT of the torso,
           wider than the shoulders, so they never stack on the centre line.
           Axis is resolved empirically (see PUSHUP_SPREAD_AXIS) because the
           prone root rotation remaps the character's local axes. */
        rot('lArm', spreadAxis(),  spreadAmt());
        rot('rArm', spreadAxis(), -spreadAmt());

        // Palms and all five fingers flat on the ground plane (Y = 0)
        rot('lHand', AX, 1.45);         rot('rHand', AX, 1.45);
        rot('lHand', AZ, -0.15);        rot('rHand', AZ, 0.15);
        rot('head', AZ, -0.30);                          // eyes slightly ahead, not at the floor
        floorBob = -0.06 * d;                            // chest dips down
        root.position.x = basePos.x + 1.05;   // recentre: model pivots on its feet when laid down
        root.position.z = basePos.z + 0.12;   // height set by the floor anchor (hands+toes planted)
        applyFloorAnchor('pushup');
        break;
      }

      /* ---- PLANK: hold the prone brace line, subtle core tremble ---- */
      case 'plank': {
        proneBrace(-0.30);
        rot('head', AZ, -0.25);
        floorBob = 0.004 * Math.sin(t * 7);   // subtle core tremble
        root.position.x = basePos.x + 1.05;
        root.position.z = basePos.z + 0.12;
        break;
      }

      /* ---- MOUNTAIN CLIMBERS: plank + alternating knee drive to chest ---- */
      case 'mountain': {
        var ml = Math.max(0, Math.sin(t * 6.5));
        var mr = Math.max(0, Math.sin(t * 6.5 + Math.PI));
        proneBrace(-0.30);
        rot('head', AZ, -0.25);
        // knee drives under the torso toward the hands: thigh −Z, shin folds back +Z.
        // Shin fold > thigh drive so the airborne foot arcs UP — if it dips below the
        // floor the anchor hoists the whole body and the plank visibly bounces.
        rot('lUpLeg', AZ, -0.95 * ml); rot('lLeg', AZ, 1.65 * ml);
        rot('rUpLeg', AZ, -0.95 * mr); rot('rLeg', AZ, 1.65 * mr);
        root.position.x = basePos.x + 1.05;
        root.position.z = basePos.z + 0.12;
        break;
      }

      /* ---- CRUNCHES: supine (face-up, head +X), knees bent, shoulders curl up ----
         Face-up the sagittal plane is still world X-Y → curl/knee motion about Z. */
      case 'crunch': {
        var cu = 0.5 - 0.5 * Math.cos(t * 2.8);          // 0 flat → 1 crunched
        root.rotation.set(-Math.PI / 2, -Math.PI / 2, 0, 'YXZ');   // face-up, side-on profile
        rot('lUpLeg', AZ, -0.85); rot('rUpLeg', AZ, -0.85);  // knees bent up
        rot('lLeg', AZ, 1.70);    rot('rLeg', AZ, 1.70);     // shins fold back so the feet plant
        rot('spine', AZ, 0.45 * cu); rot('spine1', AZ, 0.30 * cu); rot('neck', AZ, 0.22 * cu); // curl up
        rot('lArm', AZ, 0.9); rot('rArm', AZ, 0.9);          // elbows forward
        rot('lForeArm', AZ, 1.1); rot('rForeArm', AZ, 1.1);  // hands folded toward the head
        root.position.x = basePos.x - 1.10;   // recentre: model pivots on its feet when laid down
        root.position.z = basePos.z + 0.10;   // height set by the floor anchor (hips+feet planted)
        break;
      }

      /* ---- LEG RAISES: supine, straight legs sweep up together ---- */
      case 'legraise': {
        var lr = 0.5 - 0.5 * Math.cos(t * 2.4);          // 0 low → 1 high
        root.rotation.set(-Math.PI / 2, -Math.PI / 2, 0, 'YXZ');   // face-up, side-on profile
        rot('lUpLeg', AZ, -1.45 * lr); rot('rUpLeg', AZ, -1.45 * lr); // straight legs sweep up
        rot('lFoot', AZ, 0.35);                                       // toes pointed
        rot('lArm', AZ, 0.55); rot('rArm', AZ, 0.55);                 // arms braced at the sides
        root.position.x = basePos.x - 1.10;   // recentre: model pivots on its feet when laid down
        root.position.z = basePos.z + 0.10;   // height set by the floor anchor (hips+hands planted)
        break;
      }

      /* ---- WARM-UP: dynamic athletic arm drive & light bounce ---- */
      case 'warmup': {
        var w = Math.sin(t * 2.8);
        var swingL = 0.22 + 0.28 * w;     // always >= -0.06 rad; swings forward in front
        var swingR = 0.22 - 0.28 * w;
        armsDown(0.04, 0.22);
        // Swing forward in front of the chest, never behind the back
        rot('lArm', AX, swingL);
        rot('rArm', AX, swingR);
        rot('lForeArm', AX, -0.30 - 0.20 * Math.max(0, w));   // forearm flexes naturally on forward swing
        rot('rForeArm', AX, -0.30 - 0.20 * Math.max(0, -w));
        rot('spine1', AY, 0.08 * w);
        root.position.y = basePos.y + Math.abs(Math.sin(t * 2.8)) * 0.035;
        break;
      }

      /* ---- IDLE: relaxed athletic stance, gentle breathing ---- */
      default: {
        var b = Math.sin(t * 1.6);
        armsDown(-0.06, 0.22);
        rot('lArm', AX, 0.06); rot('rArm', AX, 0.06);     // hands sit slightly forward of thighs
        rot('spine', AX, 0.02 * b); rot('spine1', AX, 0.02 * b);
        rot('lArm', AZ, 0.02 * b); rot('rArm', AZ, -0.02 * b);
        rot('head', AY, 0.04 * Math.sin(t * 0.7));
        root.position.y = basePos.y + 0.008 * b;
        break;
      }
    }

    // Plant floor exercises on the ground (kills the "swimming in mid-air" float).
    // Runs after the pose is built so it reads the truly-posed contact-bone heights.
    if (FLOOR_MODES[mode]) applyFloorAnchor(mode);

    // Always enforce the brute-force hard floor boundary clamp constraint so no bone ever sinks below Y = 0
    if (attached && root && B) {
      root.updateMatrixWorld(true);
      var maxCorrection = 0;
      var checkBones = ['lFoot', 'rFoot', 'lHand', 'rHand', 'hips', 'spine'];
      for (var i = 0; i < checkBones.length; i++) {
        var bone = B[checkBones[i]];
        if (bone) {
          bone.getWorldPosition(_wp);
          var offset = getBoneOffset(checkBones[i], mode) || 0;
          // Offset measures to the bottom boundary vertex of the sneaker/hand mesh
          var vertexY = _wp.y - offset;
          if (vertexY < 0.0) {
            var diff = 0.0 - vertexY;
            if (diff > maxCorrection) {
              maxCorrection = diff;
            }
          }
        }
      }
      if (maxCorrection > 0) {
        root.position.y += maxCorrection;
        root.updateMatrixWorld(true);
      }
    }

    // slow showcase turn so the crowd sees the form in 3D (skip while prone/supine)
    if (root.rotation.x === 0) root.rotation.y = Math.sin(t * 0.22) * 0.16;
  }

  function detach() {
    attached = false; B = {}; bind = {}; root = null;
    if (leftDumbbell) { if (leftDumbbell.parent) leftDumbbell.parent.remove(leftDumbbell); leftDumbbell = null; }
    if (rightDumbbell) { if (rightDumbbell.parent) rightDumbbell.parent.remove(rightDumbbell); rightDumbbell = null; }
  }

  /* Canonical global is CCARig (Core Calorie Advisor).
     ------------------------------------------------------------------
     This file used to export ONLY window.TitanRig while pages/player.php,
     trainer-viewer.html and dev-rig-shot.html had already been renamed to call
     CCARig — 11 call sites in player.php alone. Because every one of those is
     written defensively as `if (window.CCARig && CCARig.x())`, the mismatch
     failed SILENTLY: no error, the rig simply never attached.

     The visible symptom was the 3D trainer not performing on the workout
     player: CCARig.attach() never ran (so it fell back to clip mode with no
     real per-exercise clips), CCARig.update() never ran (no procedural
     motion), and CCARig.measure() never ran — so the model was sized by
     THREE's setFromObject, which mis-measures skinned Mixamo GLBs and put the
     character off-camera entirely.

     Both names are exported so nothing has to be renamed at 14 call sites:
     CCARig is canonical, TitanRig stays as an alias for assets/js/titan3d.js. */
  window.CCARig = {
    stripHat: false,   // true = hide the cap (old behaviour); false = cap on, no bald head
    attach: attach,
    update: update,
    detach: detach,
    measure: measure,
    fit: fit,
    hideProps: hideProps,
    styleModel: styleModel,
    isProp: isProp,
    cameraFor: cameraFor,
    /* setClothes / getClothes intentionally NOT exported: the kit is a fixed,
       realistic colour set and the Customize panel has been removed, so there
       is no supported way to recolour the trainer at runtime any more. */

    get attached() { return attached; }
  };

  // Backwards-compatible alias — assets/js/titan3d.js (index.php, member/workouts.php,
  // pages/features.php) still calls TitanRig. Same object, not a copy.
  window.TitanRig = window.CCARig;
})();
