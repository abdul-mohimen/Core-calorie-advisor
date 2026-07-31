/* ============ CORE CALORIE ADVISOR — 3D Trainer Engine (Three.js r128) ============
   Loads a RIGGED, gym-clothed human GLB (Mixamo) and plays real motion clips
   via THREE.AnimationMixer with seamless crossfades. Draco GLBs are supported.

   Per-exercise motion comes from EITHER source (both work together):
     (a) multiple named clips embedded in trainer.glb  → mapped by clip name, or
     (b) drop-in files assets/models/anims/<mode>.glb   → mapped by FILE name,
         each bound to the base skeleton by bone name (same-character Mixamo
         exports share the rig, so a separate squat.glb plays on trainer.glb).
   Absent anim files 404 silently. If trainer.glb itself fails to load, a
   procedural humanoid stand-in is shown as a last resort.
   Public API (unchanged):  initThree('hero3d')  |  setMode('squat')            */

const MODE_MAP = {
  "Crunches":"crunch","Bicycle Crunches":"crunch","Russian Twists":"twist","Leg Raises":"legraise",
  "Plank":"plank","Side Plank":"plank","Mountain Climbers":"mountain","Push-Ups":"pushup",
  "Shoulder Press":"press","Bicep Curls":"curl","Bent-Over Rows":"curl","Squat Press":"press",
  "Squats":"squat","Squat Jumps":"squat","Lunges":"squat","Wall Sit":"squat","High Knees":"highknees",
  "Jumping Jacks":"jumpingjack","Burpees":"squat","Tree Pose":"yoga","Warrior Hold":"yoga",
  "Child Pose":"yoga","Deep Breathing":"yoga","Warmup":"warmup"
};

/* Rigged-model config: where the GLB lives + how exercise "modes" map to Mixamo
   clip names (first fuzzy match wins, case/space/punctuation-insensitive). */
const TRAINER_MODEL = {
  /* Ch06 (2.37 MB, 27,999 tris) is the default trainer as of 2026-07-30 — real
     tracksuit + trainers + actual hair, and a smaller download than MocapGuy
     (5.43 MB). Its mixamorig9 bone prefix is normalised by TitanRig.normBone(),
     so the procedural exercises drive it unchanged. MocapGuy is the fallback. */
  /* Follows the loadout the user saved on the workout start screen, so the
     trainer and outfit are the same character everywhere on the site. */
  get url() {
    var base = (window.TF && TF.baseUrl) ? TF.baseUrl : '';
    var f = (window.CCAWardrobe && CCAWardrobe.modelUrlFor)
      ? CCAWardrobe.modelUrlFor() : 'assets/models/trainer-street.glb';
    return base + '/' + f;
  },
  fallbackUrl: ((window.TF && TF.baseUrl) ? TF.baseUrl : '') + '/assets/models/trainers.glb',
  clips: {
    warmup:      ['Warm Up','Warmup','Jumping','Idle','Breathing Idle'],
    idle:        ['Warm Up','Warmup','Breathing Idle','Idle','Stretching'],
    pushup:      ['Push Up','Pushup','Push-Up'],
    squat:       ['Air Squat','Squat','Squatting'],
    curl:        ['Bicep Curl','Weight Curl','Curl'],
    press:       ['Shoulder Press','Overhead Press','Press'],
    yoga:        ['Yoga','Warrior','Tree Pose','Stretch','Standing'],
    plank:       ['Plank'],
    crunch:      ['Ab Crunch','Crunch','Sit Up','Situp'],
    twist:       ['Russian Twist','Twist'],
    legraise:    ['Leg Raise','Leg Raises','Reverse Crunch'],
    mountain:    ['Mountain Climber','Climber'],
    highknees:   ['High Knees','Running','Run'],
    jumpingjack: ['Jumping Jacks','Jumping Jack','Star Jump','Jump']
  }
};

/* Optional per-exercise animation files. Each assets/models/anims/<mode>.glb holds
   ONE clip; it is bound to the base skeleton by BONE NAME, so the internal clip
   name is irrelevant — the FILE NAME picks the mode. Absent files 404 silently.
   IMPORTANT: convert these and trainer.glb with the SAME FBX→glTF tool so the bone
   names match (see assets/models/README.md). */
const TRAINER_ANIMS = []; // Temporarily disabled external animations
const ANIMS_BASE = ((window.TF && TF.baseUrl) ? TF.baseUrl : '') + '/assets/models/anims/';

const LOADING_HTML = '<div class="tf3d-spin"></div><b>Loading 3D Trainer…</b>';
const MISSING_HTML = '<div class="tf3d-ico">🏋️</div><b>3D Trainer</b>'
  + '<small>Add a rigged <code>assets/models/trainer.glb</code> (see the models README) to activate the human trainer.</small>';

let renderer, scene, camera, controls, clock, curContainer = null, threeOK = false,
    TITAN_CONTAINER = 'hero3d', mode = 'idle';
let gltfRoot = null, mixer = null, gltfActions = null, curAction = null, useGLTF = false, gymTexture = null;
const stage = {};

/* ---------- placeholder overlay (shown in the 3D container) ---------- */
function setPlaceholder(container, html) {
  if (!container) return;
  let el = container.querySelector('.tf3d-ph');
  if (!el) { el = document.createElement('div'); el.className = 'tf3d-ph'; container.appendChild(el); }
  el.innerHTML = html; el.style.display = '';
}
function hidePlaceholder(container) {
  const el = container && container.querySelector('.tf3d-ph');
  if (el) el.style.display = 'none';
}

/* ---------- stage: solid gym floor + glow rings + ember particles ---------- */
function buildStage() {
  // 1. SOLID GYM FLOOR — procedural rubber tile texture (dark stone look)
  const floorSize = 24;
  const floorCanvas = document.createElement('canvas');
  floorCanvas.width = 512; floorCanvas.height = 512;
  const fctx = floorCanvas.getContext('2d');
  
  // Base dark stone color
  fctx.fillStyle = '#151518';
  fctx.fillRect(0, 0, 512, 512);
  
  // Subtle noise texture for stone grain
  for (let i = 0; i < 8000; i++) {
    const x = Math.random() * 512;
    const y = Math.random() * 512;
    const alpha = Math.random() * 0.08;
    fctx.fillStyle = Math.random() > 0.5 
      ? `rgba(255,255,255,${alpha})` 
      : `rgba(0,0,0,${alpha + 0.04})`;
    fctx.fillRect(x, y, Math.random() * 2 + 1, Math.random() * 2 + 1);
  }
  
  // Tile grid lines (subtle rubber mat pattern)
  fctx.strokeStyle = 'rgba(255,255,255,0.04)';
  fctx.lineWidth = 1;
  const tileCount = 8;
  const tileSize = 512 / tileCount;
  for (let i = 0; i <= tileCount; i++) {
    fctx.beginPath();
    fctx.moveTo(i * tileSize, 0);
    fctx.lineTo(i * tileSize, 512);
    fctx.stroke();
    fctx.beginPath();
    fctx.moveTo(0, i * tileSize);
    fctx.lineTo(512, i * tileSize);
    fctx.stroke();
  }
  
  // Subtle orange accent at center area
  const centerGrad = fctx.createRadialGradient(256, 256, 20, 256, 256, 200);
  centerGrad.addColorStop(0, 'rgba(255,107,26,0.03)');
  centerGrad.addColorStop(1, 'rgba(0,0,0,0)');
  fctx.fillStyle = centerGrad;
  fctx.fillRect(0, 0, 512, 512);
  
  const floorTex = new THREE.CanvasTexture(floorCanvas);
  floorTex.wrapS = THREE.RepeatWrapping;
  floorTex.wrapT = THREE.RepeatWrapping;
  floorTex.repeat.set(3, 3);
  
  const floorMat = new THREE.MeshStandardMaterial({
    map: floorTex,
    color: 0x1a1a1e,
    roughness: 0.85, // Tuned for stone
    metalness: 0.05,
  });
  
  // Make floor a thick circular platform
  const floorRadius = 14;
  const floorGeom = new THREE.CylinderGeometry(floorRadius, floorRadius, 0.2, 64);
  const floorMesh = new THREE.Mesh(floorGeom, floorMat);
  floorMesh.position.set(0, -0.1, 0); // Top of cylinder is at y=0
  floorMesh.receiveShadow = true;
  
  // Lock the floor matrix so it cannot be accidentally moved
  floorMesh.updateMatrix();
  floorMesh.matrixAutoUpdate = false;
  scene.add(floorMesh);
  
  // Add fog to dissolve the edges of the room
  scene.fog = new THREE.FogExp2(0x0a0a0d, 0.06);

  // 2. Shadow-catching plane (on top of solid floor for softer shadow blending)
  const shadowGeom = new THREE.PlaneGeometry(floorRadius*2, floorRadius*2);
  const shadowMat = new THREE.ShadowMaterial({ opacity: 0.55 });
  const shadowPlane = new THREE.Mesh(shadowGeom, shadowMat);
  shadowPlane.rotation.x = -Math.PI / 2;
  shadowPlane.position.y = 0.001;
  shadowPlane.receiveShadow = true;
  shadowPlane.updateMatrix();
  shadowPlane.matrixAutoUpdate = false;
  scene.add(shadowPlane);

  // 3. Soft dark radial contact shadow disc under the Titan
  const contactCanvas = document.createElement('canvas');
  contactCanvas.width = 256; contactCanvas.height = 256;
  const ctx = contactCanvas.getContext('2d');
  const grad = ctx.createRadialGradient(128, 128, 10, 128, 128, 120);
  grad.addColorStop(0, 'rgba(0, 0, 0, 0.7)');
  grad.addColorStop(0.4, 'rgba(0, 0, 0, 0.3)');
  grad.addColorStop(1, 'rgba(0, 0, 0, 0)');
  ctx.fillStyle = grad; ctx.fillRect(0, 0, 256, 256);
  const contactTex = new THREE.CanvasTexture(contactCanvas);
  const contactPlane = new THREE.Mesh(
    new THREE.PlaneGeometry(3.5, 3.5),
    new THREE.MeshBasicMaterial({ map: contactTex, transparent: true, opacity: 0.75, depthWrite: false })
  );
  contactPlane.rotation.x = -Math.PI / 2; contactPlane.position.y = 0.003;
  scene.add(contactPlane);

  // 4. Subtle decorative ring on the floor (brand accent)
  const ring = new THREE.Mesh(
    new THREE.RingGeometry(1.05, 1.35, 48),
    new THREE.MeshBasicMaterial({ color: 0xFF6B1A, transparent: true, opacity: 0.06, side: THREE.DoubleSide })
  );
  ring.rotation.x = -Math.PI / 2; ring.position.y = 0.004; scene.add(ring); stage.ring = ring;

  const ring2 = new THREE.Mesh(
    new THREE.RingGeometry(1.5, 1.56, 48),
    new THREE.MeshBasicMaterial({ color: 0xFF3D00, transparent: true, opacity: 0.04, side: THREE.DoubleSide })
  );
  ring2.rotation.x = -Math.PI / 2; ring2.position.y = 0.004; scene.add(ring2);

  // 5. Ambient dust particles
  const pg = new THREE.BufferGeometry(), Np = 100, pos = new Float32Array(Np * 3);
  for (let i = 0; i < Np; i++) {
    pos[i*3] = (Math.random() - 0.5) * 10;
    pos[i*3+1] = Math.random() * 5;
    pos[i*3+2] = (Math.random() - 0.5) * 10;
  }
  pg.setAttribute('position', new THREE.BufferAttribute(pos, 3));
  stage.parts = new THREE.Points(pg, new THREE.PointsMaterial({
    color: 0xFFFFFF,
    size: 0.025,
    transparent: true,
    opacity: 0.2,
    sizeAttenuation: true
  }));
  scene.add(stage.parts);
}

function buildGymEquipment() {
  // Disabled solid room walls & floor so background video is fully visible behind 3D Titan
}

/* ---------- rigged GLB: load (Draco-ready) + fit + clip mapping + crossfade ---------- */
function normName(s) { return String(s).toLowerCase().replace(/[^a-z0-9]/g, ''); }
function findClip(clips, cands) {
  for (const cand of cands) { const n = normName(cand);
    const hit = clips.find(cl => normName(cl.name).includes(n)); if (hit) return hit; }
  return null;
}
function fitModel(obj) {
  // Cap stays ON (the GLB has no hair mesh — see TitanRig.stripHat); hideProps is a
  // no-op unless that flag is set. styleModel cleans the mocap dots and eye glow.
  if (window.TitanRig && TitanRig.hideProps) TitanRig.hideProps(obj);
  if (window.TitanRig && TitanRig.styleModel) TitanRig.styleModel(obj);
  // Robust fit — setFromObject mis-measures skinned Mixamo GLBs (see TitanRig.measure)
  let size, ctr, min;
  if (window.TitanRig && TitanRig.measure) {
    const m = TitanRig.measure(obj); size = m.size; ctr = m.center; min = m.min;
  } else {
    const box = new THREE.Box3().setFromObject(obj);
    size = new THREE.Vector3(); ctr = new THREE.Vector3();
    box.getSize(size); box.getCenter(ctr); min = box.min;
  }
  const s = 2.7 / (size.y || 1); obj.scale.setScalar(s);

  // Re-run box calculation after scale for precise grounding
  obj.updateMatrixWorld(true);
  const finalBox = new THREE.Box3().setFromObject(obj);
  const finalCtr = new THREE.Vector3();
  finalBox.getCenter(finalCtr);

  obj.position.x = -finalCtr.x;
  obj.position.z = -finalCtr.z;
  obj.position.y = -finalBox.min.y;
  obj.traverse(o => {
    if (!o.isMesh || !o.material) return;

    const meshName = String(o.name).toLowerCase();
    const matName = String(o.material.name || '').toLowerCase();

    // Brute-force strip mocap dots / markers (keep mocapguy skin visible, preserve hat)
    if ((/reflector|marker/i.test(meshName) || /reflector|marker/i.test(matName)) && !/hat/i.test(meshName)) {
      o.visible = false;
      o.scale.set(0, 0, 0);
      return;
    }

    o.frustumCulled = false;              // never cull — a limb popping out mid-rep looks broken
    o.castShadow = true;
    o.receiveShadow = true;

    if (Array.isArray(o.material)) {
      o.material = o.material.map(m => m.clone());
    } else {
      o.material = o.material.clone();
    }
    const mats = Array.isArray(o.material) ? o.material : [o.material];

    mats.forEach(mat => {
      // Force 100% opaque render setup for all human meshes (stripping translucent shader code)
      mat.transparent = false;
      mat.opacity = 1.0;
      mat.alphaTest = 0;
      mat.depthWrite = true;
      if ('blending' in mat) mat.blending = THREE.NormalBlending;
      if (mat.alphaMap) mat.alphaMap = null;

      // Clean, professional PBR: sRGB color maps
      if (mat.map && 'encoding' in mat.map) { mat.map.encoding = THREE.sRGBEncoding; mat.map.needsUpdate = true; }
      if (renderer && mat.map) mat.map.anisotropy = renderer.capabilities.getMaxAnisotropy();
      if (renderer && mat.normalMap) mat.normalMap.anisotropy = renderer.capabilities.getMaxAnisotropy();

      // 1. Skin & Face Setup (Matte clean face/skin)
      if (/body|skin|face|head|eye|mouth|teeth/i.test(meshName) || /body|skin|face|head/i.test(matName)) {
        mat.roughness = 0.75;
        mat.metalness = 0.0;
        if (mat.emissive) {
          mat.emissive.setRGB(0, 0, 0);
          mat.emissiveIntensity = 0;
        }
      } 
      // 2. Hair/Cap Setup
      else if (/hat|cap|hair|headwear/i.test(meshName) || /hat|cap|hair|headwear/i.test(matName)) {
        o.visible = true; // Force cap/hair visibility to true
        mat.roughness = 0.85;
        mat.metalness = 0.0;
        if (mat.color && !mat.map) {
          mat.color.setHex(0x1a1a1a); // Charcoal cap if untextured
        }
      } 
      // 3. Athletic Apparel Setup
      else {
        mat.roughness = 0.8;
        mat.metalness = 0.0;
      }

      mat.needsUpdate = true;
    });
  });
}
function makeGLTFLoader() {
  const loader = new THREE.GLTFLoader();
  if (typeof THREE.DRACOLoader === 'function') {
    const draco = new THREE.DRACOLoader();
    draco.setDecoderPath('https://www.gstatic.com/draco/versioned/decoders/1.5.7/');
    draco.setDecoderConfig({ type: 'js' });
    loader.setDRACOLoader(draco);
  }
  return loader;
}
function buildProceduralHumanoid() {
  const group = new THREE.Group();
  
  const bodyMaterial = new THREE.MeshStandardMaterial({
    color: 0xFF6B1A,
    emissive: 0xFF6B1A,
    emissiveIntensity: 0.5,
    roughness: 0.1,
    metalness: 0.9,
    transparent: true,
    opacity: 0.85
  });
  
  const jointMaterial = new THREE.MeshStandardMaterial({
    color: 0xF59E0B,
    emissive: 0xF59E0B,
    emissiveIntensity: 0.8,
    roughness: 0.1,
    metalness: 0.9
  });

  // Torso
  const torsoGeom = new THREE.CylinderGeometry(0.35, 0.2, 1.4, 16);
  const torso = new THREE.Mesh(torsoGeom, bodyMaterial);
  torso.position.y = 1.3;
  group.add(torso);

  // Head
  const headGeom = new THREE.SphereGeometry(0.2, 16, 16);
  const head = new THREE.Mesh(headGeom, bodyMaterial);
  head.position.y = 2.15;
  group.add(head);

  // Neck
  const neckGeom = new THREE.CylinderGeometry(0.08, 0.08, 0.15, 16);
  const neck = new THREE.Mesh(neckGeom, bodyMaterial);
  neck.position.y = 1.95;
  group.add(neck);

  // Shoulders
  const leftShoulder = new THREE.Mesh(new THREE.SphereGeometry(0.1, 12, 12), jointMaterial);
  leftShoulder.position.set(-0.45, 1.8, 0);
  group.add(leftShoulder);
  
  const rightShoulder = new THREE.Mesh(new THREE.SphereGeometry(0.1, 12, 12), jointMaterial);
  rightShoulder.position.set(0.45, 1.8, 0);
  group.add(rightShoulder);

  // Left arm
  const leftArmGeom = new THREE.CylinderGeometry(0.07, 0.06, 0.6, 12);
  const leftArm = new THREE.Mesh(leftArmGeom, bodyMaterial);
  leftArm.position.set(-0.55, 1.4, 0);
  group.add(leftArm);
  
  // Left forearm
  const leftForearm = new THREE.Mesh(new THREE.CylinderGeometry(0.06, 0.05, 0.5, 12), bodyMaterial);
  leftForearm.position.set(-0.55, 0.85, 0.05);
  leftForearm.rotation.x = 0.2;
  group.add(leftForearm);

  // Right arm
  const rightArmGeom = new THREE.CylinderGeometry(0.07, 0.06, 0.6, 12);
  const rightArm = new THREE.Mesh(rightArmGeom, bodyMaterial);
  rightArm.position.set(0.55, 1.4, 0);
  group.add(rightArm);
  
  // Right forearm
  const rightForearm = new THREE.Mesh(new THREE.CylinderGeometry(0.06, 0.05, 0.5, 12), bodyMaterial);
  rightForearm.position.set(0.55, 0.85, 0.05);
  rightForearm.rotation.x = 0.2;
  group.add(rightForearm);

  // Pelvis / Hips
  const pelvisGeom = new THREE.BoxGeometry(0.5, 0.12, 0.3);
  const pelvis = new THREE.Mesh(pelvisGeom, jointMaterial);
  pelvis.position.y = 0.65;
  group.add(pelvis);

  // Left Thigh
  const leftThighGeom = new THREE.CylinderGeometry(0.1, 0.08, 0.65, 12);
  const leftThigh = new THREE.Mesh(leftThighGeom, bodyMaterial);
  leftThigh.position.set(-0.18, 0.35, 0);
  group.add(leftThigh);
  
  // Left Shin
  const leftShinGeom = new THREE.CylinderGeometry(0.08, 0.06, 0.65, 12);
  const leftShin = new THREE.Mesh(leftShinGeom, bodyMaterial);
  leftShin.position.set(-0.18, -0.3, 0);
  group.add(leftShin);

  // Right Thigh
  const rightThighGeom = new THREE.CylinderGeometry(0.1, 0.08, 0.65, 12);
  const rightThigh = new THREE.Mesh(rightThighGeom, bodyMaterial);
  rightThigh.position.set(0.18, 0.35, 0);
  group.add(rightThigh);
  
  // Right Shin
  const rightShinGeom = new THREE.CylinderGeometry(0.08, 0.06, 0.65, 12);
  const rightShin = new THREE.Mesh(rightShinGeom, bodyMaterial);
  rightShin.position.set(0.18, -0.3, 0);
  group.add(rightShin);

  group.position.set(0, 0.3, 0);
  
  group.userData = {
    parts: {
      leftArm, rightArm, leftForearm, rightForearm,
      leftThigh, rightThigh, leftShin, rightShin, torso, head
    }
  };

  return group;
}

function useProcedural(container) {
  try {
    const humanoid = buildProceduralHumanoid();
    window.proceduralHumanoid = humanoid;
    scene.add(humanoid);
    hidePlaceholder(container);
    useGLTF = false; mixer = null; gltfActions = null;
    console.info('[titan3d] Fallback procedural model active.');
  } catch (err) {
    console.error('Procedural humanoid failed:', err);
    setPlaceholder(container, MISSING_HTML);
  }
}

/* Load the RIGGED human GLB and drive its Mixamo skeleton procedurally (TitanRig),
   so every exercise gets a distinct, human motion. Falls back to the procedural
   stand-in only if the GLB (or its rig) can't be used. */
function loadTrainer(container) {
  if (typeof THREE.GLTFLoader !== 'function') { useProcedural(container); return; }
  let done = false;
  const fail = (e) => {
    if (done) return; done = true; clearTimeout(to);
    console.warn('[titan3d] trainer GLB unavailable — using procedural fallback:', e);
    useProcedural(container);
  };
  /* Measured in real Chrome on this machine: GLTFLoader.load() of the 6.5 MB
     trainers.glb takes ~7-8s when the page is idle. During INITIAL page load it
     competes with the Tailwind CDN, Google Fonts, the hero video and the
     particle canvas, and regularly blew past the old 25s budget — so the
     homepage silently dropped to the blocky procedural stand-in instead of the
     rigged human. 60s is generous rather than tight: the fallback exists for a
     genuinely missing/broken model, not for a slow one.
     The real cure is deferring the load (see initThree callers) plus shipping a
     compressed model — this timeout is the safety net, not the fix. */
  const to = setTimeout(() => fail('timeout'), 60000);
  try {
    const loader = makeGLTFLoader();
    /* Fall back to MocapGuy before giving up, so a missing or corrupt primary
       model never costs us the human trainer. */
    let triedFallback = false;
    const onModelError = (e) => {
      if (done) return;
      if (!triedFallback && TRAINER_MODEL.fallbackUrl) {
        triedFallback = true;
        console.warn('[titan3d] primary trainer failed, trying fallback:', e);
        loader.load(TRAINER_MODEL.fallbackUrl, onModelLoad, undefined, fail);
        return;
      }
      fail(e);
    };
    const onModelLoad = (gltf) => {
      if (done) return; done = true; clearTimeout(to);
      gltfRoot = gltf.scene;
      fitModel(gltfRoot);
      scene.add(gltfRoot);
      hidePlaceholder(container);

      /* Wear whatever the user picked on the workout start screen. */
      if (window.CCAWardrobe && CCAWardrobe.apply) CCAWardrobe.apply(gltfRoot);

      // Primary path: bind the Mixamo bones and animate exercises procedurally.
      if (window.TitanRig && TitanRig.attach(gltfRoot)) {
        useGLTF = true; mixer = null;                 // rig drives; mixer would fight it
        console.info('[titan3d] Rigged human trainer active (procedural exercises).');
      } else {
        // Rig names not recognised → at least play any embedded clip.
        mixer = new THREE.AnimationMixer(gltfRoot);
        const clips = gltf.animations || [];
        gltfActions = {};
        
        // Strip root motion to prevent floor-sliding illusion
        const rootAllowlist = ['walk', 'run'];
        clips.forEach(clip => {
          let retained = false;
          clip.tracks.forEach(track => {
            if (track.name.match(/(mixamorig)?Hips\.position$/i)) {
              if (rootAllowlist.some(a => normName(clip.name).includes(a))) {
                retained = true;
              } else {
                // Zero X/Z, preserve Y for vertical movement
                for (let i = 0; i < track.values.length; i += 3) {
                  track.values[i] = 0;     // X
                  track.values[i+2] = 0;   // Z
                }
              }
            }
          });
          console.log(`[titan3d] Clip "${clip.name}" processed. Root motion retained: ${retained}`);
        });

        if (clips.length) {
          for (const m in TRAINER_MODEL.clips) {
            const cl = findClip(clips, TRAINER_MODEL.clips[m]);
            if (cl) gltfActions[m] = mixer.clipAction(cl);
          }
          if (!gltfActions.idle) gltfActions.idle = mixer.clipAction(clips[0]);
        }
        useGLTF = true;
        playClip(mode);
        console.info('[titan3d] Human trainer loaded (embedded-clip mode).');
      }
    };
    loader.load(TRAINER_MODEL.url, onModelLoad, undefined, onModelError);
  } catch (err) { fail(err); }
}

/* Try each assets/models/anims/<mode>.glb; attach its single clip to the base mixer,
   keyed by file name. Retargets by bone name — internal clip name is ignored. */
function bindExtraAnims(loader) {
  TRAINER_ANIMS.forEach(m => {
    loader.load(ANIMS_BASE + m + '.glb', (g) => {
      const clip = (g.animations || [])[0];
      if (!clip || !mixer) return;
      clip.name = m;                                          // readable in logs / crossfades
      
      // Strip root motion to prevent floor-sliding illusion
      clip.tracks.forEach(track => {
        if (track.name.match(/(mixamorig)?Hips\.position$/i)) {
          // Zero X/Z, preserve Y for vertical movement
          for (let i = 0; i < track.values.length; i += 3) {
            track.values[i] = 0;     // X
            track.values[i+2] = 0;   // Z
          }
        }
      });

      gltfActions[m] = mixer.clipAction(clip);
      if (!gltfActions.idle && (m === 'idle' || m === 'warmup')) gltfActions.idle = gltfActions[m];
      if (m === mode) playClip(m);                            // user already on this mode → swap in now
      console.info('[titan3d] +anim', m);
    }, undefined, () => {/* file absent → skip silently */});
  });
}
function playClip(m) {
  if (!mixer || !gltfActions) return;
  const act = gltfActions[m] || gltfActions.idle;
  if (!act || act === curAction) return;
  act.reset(); act.enabled = true; act.setEffectiveTimeScale(1); act.setEffectiveWeight(1); act.play();
  if (curAction) act.crossFadeFrom(curAction, 0.4, true);   // seamless Mixamo transition
  curAction = act;
}

/* ---------- init + loop + public API ---------- */
function initThree(containerId) {
  TITAN_CONTAINER = containerId || 'hero3d';
  const container = document.getElementById(TITAN_CONTAINER);
  setPlaceholder(container, LOADING_HTML);   // show "Loading…" first — before any WebGL, never a robot
  try {
    scene = new THREE.Scene();
    camera = new THREE.PerspectiveCamera(42, 1, .1, 60); camera.position.set(0, 2.1, 6.4); camera.lookAt(0, 1.5, 0);
    try {
      renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true, powerPreference: 'high-performance' });
      renderer.setPixelRatio(Math.min(devicePixelRatio, 1.5));   // 2× DPR doubled fill-rate cost for no visible gain
      renderer.setClearColor(0x000000, 0);
      renderer.shadowMap.enabled = true;
      renderer.shadowMap.type = THREE.PCFSoftShadowMap;
      if ('outputEncoding' in renderer) renderer.outputEncoding = THREE.sRGBEncoding;
      if ('toneMapping' in renderer) { renderer.toneMapping = THREE.ACESFilmicToneMapping; renderer.toneMappingExposure = 1.08; }
    } catch (e) {
      console.warn('WebGL Renderer failed to initialize:', e);
      setPlaceholder(container, MISSING_HTML);
      return;
    }
    
    // ---- Enhanced Gym Lighting Setup ----
    scene.add(new THREE.AmbientLight(0xffFAF0, 0.35));
    scene.add(new THREE.HemisphereLight(0xfff3e3, 0x111118, 0.40));

    // Key Light: warm sunlight coming from the left windows
    const keyLight = new THREE.DirectionalLight(0xfff1e0, 1.8);
    keyLight.position.set(-10, 7, 3);
    keyLight.castShadow = true;
    keyLight.shadow.mapSize.width = 2048;
    keyLight.shadow.mapSize.height = 2048;
    keyLight.shadow.camera.near = 0.5;
    keyLight.shadow.camera.far = 30;
    keyLight.shadow.camera.left = -8;
    keyLight.shadow.camera.right = 8;
    keyLight.shadow.camera.top = 8;
    keyLight.shadow.camera.bottom = -4;
    keyLight.shadow.bias = -0.0002;
    keyLight.shadow.radius = 3;
    scene.add(keyLight);

    // Warm brand orange rim light (#FF7700)
    const warmRim = new THREE.DirectionalLight(0xFF7700, 1.2);
    warmRim.position.set(6, 5, -6);
    scene.add(warmRim);

    // Ground fill light
    const groundFill = new THREE.DirectionalLight(0xffe8d6, 0.2);
    groundFill.position.set(0, -2, 4);
    scene.add(groundFill);

    // Overhead gym spot lights (simulate ceiling fixtures)
    [-3, 0, 3].forEach(function(x) {
      var spot = new THREE.PointLight(0xfff0e0, 0.6, 12, 2);
      spot.position.set(x, 4.8, -1);
      spot.castShadow = false; // perf: only key light casts shadows
      scene.add(spot);
    });

    // Brand accent light (vibrant orange glow matching CORE CALORIE ADVISOR theme)
    var accentLight = new THREE.PointLight(0xFF7700, 1.5, 10, 2);
    accentLight.position.set(0, 1.5, -3);
    scene.add(accentLight);

    buildStage();
    buildGymEquipment();

    // Forcefully load the industrial gym background texture
    const textureLoader = new THREE.TextureLoader();
    textureLoader.load(((window.TF && TF.baseUrl) ? TF.baseUrl : '') + '/assets/images/industrial_gym.jpg', function(texture) {
      texture.encoding = THREE.sRGBEncoding;
      texture.wrapS = THREE.ClampToEdgeWrapping;
      texture.wrapT = THREE.ClampToEdgeWrapping;
      texture.minFilter = THREE.LinearFilter;
      gymTexture = texture;
      scene.background = null;
      resize3d();
    });

    clock = new THREE.Clock(); threeOK = true;
    mountCanvas(container);
    
    if (typeof THREE.OrbitControls === 'function') {
      controls = new THREE.OrbitControls(camera, renderer.domElement);
      controls.target.set(0, 1.2, 0); // Chest height
      controls.maxPolarAngle = Math.PI / 2 - 0.06; // Never go under floor
      controls.minPolarAngle = 0.25;
      controls.enablePan = false;
      controls.enableDamping = true;
      controls.dampingFactor = 0.06;
      controls.minDistance = 2.0;
      controls.maxDistance = 12.0;
    }

    // Attach for testing
    window.tfScene = scene;
    window.tfCamera = camera;
    window.tfControls = controls;

    loadTrainer(container);
    /* Re-enabled 2026-07-30. The panel was orphaned — nothing called
       buildClothesPanel() and nothing rendered its toggle button, so ~130 lines
       of UI could never run. It is wired up again now that real training-kit
       textures ship in assets/models/outfits/ and the swap actually works. */
    buildClothesPanel(container);
    animate();
  } catch (e) { console.warn('3D unavailable', e); setPlaceholder(container, MISSING_HTML); }
}

/* ---- MODULAR MESH SWAPPING SYSTEM ----
   Loads a modular clothing GLB (e.g., shirt, pants) and binds its SkinnedMesh
   to the base character's skeleton so they share animations perfectly. */
window.TF_ModularClothing = {
  equipped: {},
  loader: null,
  
  // Equip a new clothing item (type: 'shirt', 'pants', 'shoes')
  equip: async function(baseCharacterModel, type, clothingUrl) {
    if (!this.loader) {
      this.loader = new THREE.GLTFLoader();
      if (typeof THREE.DRACOLoader === 'function') {
        const draco = new THREE.DRACOLoader();
        draco.setDecoderPath('https://www.gstatic.com/draco/versioned/decoders/1.5.7/');
        draco.setDecoderConfig({ type: 'js' });
        this.loader.setDRACOLoader(draco);
      }
    }
    
    try {
      const clothingGltf = await this.loader.loadAsync(clothingUrl);
      let clothingMesh = null;
      
      // Find the SkinnedMesh in the clothing GLTF
      clothingGltf.scene.traverse((child) => {
        if (child.isSkinnedMesh) clothingMesh = child;
      });
      
      if (!clothingMesh) {
        console.warn('No SkinnedMesh found in clothing GLB:', clothingUrl);
        return null;
      }
      
      // Find the base character's skeleton
      let baseSkeleton = null;
      baseCharacterModel.traverse((child) => {
        if (child.isSkinnedMesh && !baseSkeleton) {
          baseSkeleton = child.skeleton;
        }
      });
      
      if (!baseSkeleton) {
        console.warn('Base model has no skeleton to bind to.');
        return null;
      }
      
      // Re-bind clothing to the master skeleton
      clothingMesh.skeleton = baseSkeleton;
      clothingMesh.bindMatrix.copy(baseSkeleton.boneInverses[0].clone().invert());
      
      // Remove previously equipped item of the same type
      this.unequip(baseCharacterModel, type);
      
      clothingMesh.name = 'tf_cloth_' + type;
      clothingMesh.castShadow = true;
      clothingMesh.receiveShadow = true;
      
      // Apply correct materials
      if (clothingMesh.material) {
        const mat = clothingMesh.material;
        mat.roughness = 0.8;
        mat.metalness = 0.0;
        if (mat.map) {
          mat.map.encoding = THREE.sRGBEncoding;
          if (renderer) mat.map.anisotropy = renderer.capabilities.getMaxAnisotropy();
        }
      }
      
      baseCharacterModel.add(clothingMesh);
      this.equipped[type] = clothingMesh;
      return clothingMesh;
      
    } catch (err) {
      console.error('Failed to equip modular clothing:', err);
      return null;
    }
  },
  
  // Unequip a clothing item by type
  unequip: function(baseCharacterModel, type) {
    if (this.equipped[type]) {
      baseCharacterModel.remove(this.equipped[type]);
      if (this.equipped[type].geometry) this.equipped[type].geometry.dispose();
      if (this.equipped[type].material) this.equipped[type].material.dispose();
      delete this.equipped[type];
    }
  }
};

/* ---- CLOTHES CUSTOMIZATION UI PANEL ----
   Floating panel overlaid on the 3D container with color pickers for
   shirt / pants / shoes and preset outfit buttons. Toggled via the
   🎨 Customize button added to the exercise mode bar. */
function buildClothesPanel(container) {
  if (!container) return;

  var panel = document.createElement('div');
  panel.id = 'tf-clothes-panel';
  panel.className = 'tf-clothes-panel';
  panel.innerHTML = `
    <div class="tf-clothes-head">
      <span>🎨 Customize Outfit</span>
      <button id="tfClothesClose" class="tf-clothes-close" aria-label="Close">✕</button>
    </div>
    <div class="tf-clothes-body">
      <div class="tf-clothes-row">
        <label>Training Kit</label>
        <select id="tfKit" class="tf-cloth-select">
          <option value="">Default kit</option>
          <option value="ember">Ember</option>
          <option value="ocean">Ocean</option>
          <option value="forest">Forest</option>
        </select>
      </div>
      <div class="tf-clothes-row" id="tfVoiceRow"></div>
      <p class="tf-clothes-note">Kit texture swap. Skin tone and the trainer's
      base colours stay fixed for a realistic look.</p>
    </div>
  `;
  /* The shirt/pants/shoes/accent colour pickers and the six preset buttons that
     used to live here were removed 2026-07-30. They all funnel into
     TitanRig.setClothes(), which titan-rig.js:1009 deliberately does NOT export
     any more ("the kit is a fixed, realistic colour set"). Every one of those
     controls was therefore a no-op, and shipping controls that do nothing is
     exactly what the no-non-functional-UI rule forbids. The Training Kit select
     above is the control that genuinely works. */
  container.appendChild(panel);

  /* The toggle button was styled in style.css (.tf-customize-btn) but never
     rendered by anything, so the panel had no way to be opened. Create it here. */
  if (!container.querySelector('.tf-customize-btn')) {
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.id = 'tfCustomizeBtn';
    btn.className = 'tf-customize-btn';
    btn.setAttribute('aria-label', 'Customize trainer outfit');
    btn.textContent = '🎨 Customize';
    btn.style.cssText = 'position:absolute;top:12px;right:12px;z-index:6;padding:8px 14px;border-radius:999px;cursor:pointer;font-size:13px;';
    if (getComputedStyle(container).position === 'static') container.style.position = 'relative';
    container.appendChild(btn);
  }

  /* ---- Training-kit swap (2026-07-30) ----------------------------------
     This used to offer Tank Top / Hoodie / Gym Shorts / Sweatpants pointing at
     assets/models/clothes/*.glb — a directory that never existed, so every
     option 404'd and disabled itself. The model has no separable garment
     geometry either: the outfit is baked into MocapGuy_Body's single
     Body_MAT diffuse texture.
     So the kit is swapped the only way this asset supports — by replacing that
     texture. The colourways ship in assets/models/outfits/ and are lazy-loaded
     on first selection; "Graphite (default)" restores the texture embedded in
     trainers.glb, costing no download. */
  var kitBaseMap = null;                       // the GLB's own texture, kept for reset
  var kitCache = {};

  /* Only the character's garment material. Matching "any material with a map"
     also catches the floor/ground texture, which is what the first version of
     this did — it swapped the ground instead of the outfit. */
  function bodyMaterials() {
    var mats = [];
    if (!gltfRoot) return mats;
    var isKit = function (n) {
      return (window.CCACoach && CCACoach.isKitMaterial) ? CCACoach.isKitMaterial(n) : n === 'Body_MAT';
    };
    gltfRoot.traverse(function (o) {
      if (!o.isMesh && !o.isSkinnedMesh) return;
      var list = Array.isArray(o.material) ? o.material : [o.material];
      list.forEach(function (m) {
        if (!m || !m.map || mats.indexOf(m) !== -1) return;
        if (isKit(m.name) || m.__isKit) mats.push(m);
      });
    });
    return mats;
  }

  function applyKit(url, sel) {
    var mats = bodyMaterials();
    if (!mats.length) return;
    if (kitBaseMap === null) kitBaseMap = mats[0].map;

    function setMap(tex) {
      mats.forEach(function (m) {
        if (m.map === kitBaseMap || m.__isKit) { m.map = tex; m.__isKit = true; m.needsUpdate = true; }
      });
    }
    if (!url) { setMap(kitBaseMap); return; }
    if (kitCache[url]) { setMap(kitCache[url]); return; }

    var base = (window.TF && TF.baseUrl) ? TF.baseUrl + '/' : '';
    if (sel) sel.disabled = true;
    new THREE.TextureLoader().load(base + url, function (tex) {
      /* match the encoding/orientation glTF gave the original map */
      if (kitBaseMap) {
        tex.flipY = kitBaseMap.flipY;
        tex.wrapS = kitBaseMap.wrapS; tex.wrapT = kitBaseMap.wrapT;
        if ('colorSpace' in kitBaseMap) tex.colorSpace = kitBaseMap.colorSpace;
        else if ('encoding' in kitBaseMap) tex.encoding = kitBaseMap.encoding;
      }
      kitCache[url] = tex;
      setMap(tex);
      if (sel) sel.disabled = false;
    }, undefined, function () {
      console.warn('[cca3d] training kit failed to load:', url);
      if (sel) sel.disabled = false;
    });
  }

  /* Option values are kit NAMES, not paths — the actual texture depends on which
     trainer loaded (Ch06 and MocapGuy have different UV layouts). */
  function kitUrl(name) {
    if (!name) return '';
    var which = (window.CCACoach && CCACoach.kitSetFor) ? CCACoach.kitSetFor(gltfRoot) : 'ch06';
    var prefix = which === 'mocapguy' ? '' : 'ch06-';
    return 'assets/models/outfits/' + prefix + name + '.png';
  }

  var kitSel = document.getElementById('tfKit');
  if (kitSel) {
    kitSel.addEventListener('change', function (e) { applyKit(kitUrl(e.target.value), kitSel); });
  }

  /* Coach-voice picker. The Web Speech API can only offer voices installed on
     the visitor's machine, so instead of silently settling for whatever ranks
     first, let them choose — and say so when no neural voice is installed. */
  (function mountVoicePicker() {
    var row = document.getElementById('tfVoiceRow');
    if (!row || typeof window.CCAVoicePicker !== 'function') return;
    function build() {
      row.innerHTML = '';
      window.CCAVoicePicker(row, function () {
        if (voiceManager && voiceManager.initVoices) voiceManager.initVoices();
        if (window.TitanVoice && TitanVoice.say) TitanVoice.say('Coach voice set.');
      });
    }
    if (window.CCAVoiceList && CCAVoiceList().length) build();
    else if ('speechSynthesis' in window) {
      window.speechSynthesis.addEventListener('voiceschanged', build, { once: true });
      setTimeout(build, 1200);
    }
  })();

  // Close button
  document.getElementById('tfClothesClose').addEventListener('click', function() {
    panel.classList.remove('open');
  });
}

let stageOnScreen = true;   // perf: don't render the hero while it's scrolled out of view
function mountCanvas(container) { if (!threeOK || !container || curContainer === container) return;
  container.appendChild(renderer.domElement); curContainer = container; resize3d();
  if ('IntersectionObserver' in window) {
    new IntersectionObserver(en => { stageOnScreen = !en[0] || en[0].isIntersecting; }).observe(container);
  } }
function resize3d() { if (!threeOK || !curContainer) return; const w = curContainer.clientWidth, h = curContainer.clientHeight;
  if (w && h) {
    renderer.setSize(w, h); camera.aspect = w / h; camera.updateProjectionMatrix();
    // Adjust background texture mapping and aspect ratios to wrap naturally (cover fit)
    const texture = scene.background;
    if (texture && texture.isTexture && texture.image) {
      const img = texture.image;
      const aspect = w / h;
      const imageAspect = img.width / img.height;
      if (aspect > imageAspect) {
        texture.repeat.set(1, imageAspect / aspect);
        texture.offset.set(0, (1 - imageAspect / aspect) / 2);
      } else {
        texture.repeat.set(aspect / imageAspect, 1);
        texture.offset.set((1 - aspect / imageAspect) / 2, 0);
      }
    }
  } }
addEventListener('resize', resize3d);

function setMode(m) { mode = m || 'idle';
  if (useGLTF) playClip(mode);
  document.querySelectorAll('.h3d-modes button').forEach(b => b.classList.toggle('on', b.dataset.mode === m)); }

/* ============================================================================
   WORKOUT STATE MACHINE + WEB SPEECH   (Fitify-style guided coaching)
   ----------------------------------------------------------------------------
   A finite state machine drives the trainer through GET READY → GO → REST and
   announces every stage with the Web Speech API. The per-workout transitions
   are HARD-CODED in WORKOUT_STATES (animation clip + spoken cue), e.g.
       resolveWorkout('pushup') → { animation:'pushup', cue:'Push ups. …' }
   Public API (all on window):
       TitanTrainer.start('pushup')       run a self-contained guided loop
       TitanTrainer.stop()                stop → idle
       TitanTrainer.announce(stage, wk)   speak a stage for an EXTERNAL engine
                                          (player.js); stage = ready|go|rest|done
       TitanTrainer.setMuted(bool) / toggleMute()   mute the voice (persisted)
   ============================================================================ */

/* ---- Premium Voice Engine Class ---- */
class VoiceManager {
  constructor() {
    this.voice = null;
    /* Matched to pages/player.php's VoiceManager — these two had drifted
       (0.85 here vs 0.9 there), so the same coach sounded different on the
       home page and inside a workout. */
    this.pitch = 0.9;
    this.rate = 1.1;
    this.muted = false;
    
    try {
      this.muted = localStorage.getItem('tf-voice') === 'off';
    } catch (e) {}

    if (typeof window !== 'undefined' && 'speechSynthesis' in window) {
      this.initVoices();
      if (window.speechSynthesis.onvoiceschanged !== undefined) {
        window.speechSynthesis.onvoiceschanged = () => this.initVoices();
      }
    }
  }

  /* Voice CHOICE lives in assets/js/cca-voice.js so this engine and the
     exactfit3d engine in pages/player.php can never drift apart again (they
     previously had two different priority lists and two different pitches).
     The legacy list below is kept only as a fallback if that file is absent. */
  initVoices() {
    if (!('speechSynthesis' in window)) return;
    const voices = window.speechSynthesis.getVoices();
    if (!voices.length) return;

    if (typeof window.CCAVoicePick === 'function') {
      this.voice = window.CCAVoicePick(voices);
      if (this.voice) console.info('[VoiceManager] coach voice:', this.voice.name);
      return;
    }

    const priorities = [
      /Online \(Natural\)/i, /Natural|Neural/i,
      /Google UK English Male/i, /Microsoft David/i, /Google UK English/i,
      /English Male/i, /Male/i, /Google/i, /English/i
    ];
    for (const pattern of priorities) {
      const found = voices.find(v => pattern.test(v.name) && /^en/i.test(v.lang));
      if (found) { this.voice = found; break; }
    }
    if (!this.voice) this.voice = voices[0];
  }

  say(text) {
    if (!('speechSynthesis' in window) || this.muted || !text) return;
    try {
      window.speechSynthesis.cancel();
      const u = new SpeechSynthesisUtterance(text);
      if (this.voice) {
        u.voice = this.voice;
      }
      u.pitch = this.pitch;
      u.rate = this.rate;
      u.volume = 1.0;
      window.speechSynthesis.speak(u);
    } catch (e) {
      console.warn('[VoiceManager] Speech failed:', e);
    }
  }

  stop() {
    if ('speechSynthesis' in window) {
      window.speechSynthesis.cancel();
    }
  }
}

const voiceManager = new VoiceManager();

const TitanVoice = {
  ok: typeof window !== 'undefined' && 'speechSynthesis' in window,
  get muted() { return voiceManager.muted; },
  set muted(val) {
    voiceManager.muted = !!val;
    try { localStorage.setItem('tf-voice', val ? 'off' : 'on'); } catch (e) {}
    if (val) voiceManager.stop();
  },
  say(text) {
    voiceManager.say(text);
  },
  stop() {
    voiceManager.stop();
  }
};

/* ---- Hard-coded per-workout transitions: mode key → { animation, label, cue } ---- */
const WORKOUT_STATES = {
  idle:        { animation: 'idle',        label: 'Warm Up',           cue: 'Warm up. Loosen those joints and get the blood flowing.' },
  warmup:      { animation: 'warmup',      label: 'Warm Up',           cue: 'Warm up. Loosen those joints and get the blood flowing.' },
  pushup:      { animation: 'pushup',      label: 'Push Ups',          cue: 'Push ups. Lower your chest to the floor, elbows tucked, keep that core braced.' },
  wallpushup:  { animation: 'wallpushup',  label: 'Wall Push Ups',     cue: 'Wall push ups. Keep a straight torso, lower with control, then press the wall away.' },
  squat:       { animation: 'squat',       label: 'Squats',            cue: 'Squats. Sit back, chest up, knees out, and drive up through your heels.' },
  yoga:        { animation: 'yoga',        label: 'Yoga Flow',         cue: 'Yoga flow. Breathe deep, move slow, and hold each pose with control.' },
  jumpingjack: { animation: 'jumpingjack', label: 'Jumping Jacks',     cue: 'Jumping jacks. Big arms overhead, land soft, keep the rhythm going.' },
  curl:        { animation: 'curl',        label: 'Bicep Curls',       cue: 'Bicep curls. Slow on the way up, squeeze hard at the top, no swinging.' },
  press:       { animation: 'press',       label: 'Shoulder Press',    cue: 'Shoulder press. Press straight overhead, lock it out, and control it down.' },
  plank:       { animation: 'plank',       label: 'Plank',             cue: 'Plank. Straight line head to heels, squeeze your abs and glutes, hold strong.' },
  crunch:      { animation: 'crunch',      label: 'Crunches',          cue: 'Crunches. Curl up slow, squeeze your abs at the top, control the way down.' },
  twist:       { animation: 'twist',       label: 'Russian Twists',    cue: 'Russian twists. Rotate from the waist, tap each side, keep your core tight.' },
  legraise:    { animation: 'legraise',    label: 'Leg Raises',        cue: 'Leg raises. Straight legs up, lower back pressed down, control every rep.' },
  mountain:    { animation: 'mountain',    label: 'Mountain Climbers', cue: 'Mountain climbers. Drive those knees to your chest, fast feet, hips down.' },
  highknees:   { animation: 'highknees',   label: 'High Knees',        cue: 'High knees. Pump your arms, knees up to waist height, stay light and fast.' }
};

/* Explicit resolver (the hard-coded transition):
   resolveWorkout('pushup') → { animation:'pushup', label:'Push Ups', cue:'…' } */
function resolveWorkout(workout) {
  return WORKOUT_STATES[workout] || WORKOUT_STATES.idle;
}

/* ---- The finite state machine ---- */
const TitanTrainer = {
  state: 'idle',                                  // idle | ready | go | rest | done
  workout: 'idle',
  timer: null,
  durations: { ready: 5, work: 30, rest: 15 },    // seconds per stage (self-contained loop)

  setMuted(m) {
    TitanVoice.muted = !!m;
    return TitanVoice.muted;
  },
  toggleMute() { return this.setMuted(!TitanVoice.muted); },

  _clear() { if (this.timer) { clearTimeout(this.timer); this.timer = null; } },
  _emit() {
    try { document.dispatchEvent(new CustomEvent('titan:stage', {
      detail: { state: this.state, workout: this.workout, label: resolveWorkout(this.workout).label }
    })); } catch (e) {}
  },

  /* Self-contained guided loop for ONE exercise (hero "guided demo"). */
  start(workout) {
    this.workout = WORKOUT_STATES[workout] ? workout : 'idle';
    this._toReady();
  },
  stop() {
    this._clear(); TitanVoice.stop();
    this.state = 'idle'; this.workout = 'idle';
    if (typeof setMode === 'function') setMode('idle');
    this._emit();
  },

  _toReady() {
    this._clear(); this.state = 'ready';
    const w = resolveWorkout(this.workout);
    if (typeof setMode === 'function') setMode('idle');        // relaxed pose during countdown
    TitanVoice.say(`Let's go! Get ready for ${w.label}. Push your limits!`);
    this._emit();
    this.timer = setTimeout(() => this._toGo(), this.durations.ready * 1000);
  },
  _toGo() {
    this._clear(); this.state = 'go';
    const w = resolveWorkout(this.workout);
    if (typeof setMode === 'function') setMode(w.animation);   // hard-coded animation
    TitanVoice.say(`Go, go, go! Explode into ${w.label}! Give me maximum effort!`);
    this._emit();
    this.timer = setTimeout(() => this._toRest(), this.durations.work * 1000);
  },
  _toRest() {
    this._clear(); this.state = 'rest';
    if (typeof setMode === 'function') setMode('warmup');      // light recovery bounce — 'yoga' is its own workout, never a rest filler
    TitanVoice.say('Rest phase! Take a deep breath. Recover.');
    this._emit();
    this.timer = setTimeout(() => this._toGo(), this.durations.rest * 1000);   // loop back to GO
  },

  /* Announce a stage for an EXTERNAL engine (player.js owns its own timers).
     stage: 'ready' | 'go' | 'rest' | 'done';  wk = mode key OR workout name. */
  announce(stage, wk, intro) {
    const key = (typeof MODE_MAP !== 'undefined' && MODE_MAP[wk]) ? MODE_MAP[wk] : wk;
    const known = Object.prototype.hasOwnProperty.call(WORKOUT_STATES, key);
    const w = known ? WORKOUT_STATES[key] : { label: wk || 'your workout' };
    const name = w.label;
    const cue  = w.cue || (name + '. Give it everything.');   // exercise-specific coaching line
    const pick = (a) => a[Math.floor(Math.random() * a.length)];

    let text = '';
    if (stage === 'ready') {
      text = (intro ? intro + '. ' : '') + pick(['Get ready! ', 'Here we go! ', 'Next up, ']) + name + '. ' + cue;
    } else if (stage === 'go') {
      text = pick(["Let's go! ", 'Come on, Champ! ', 'Drive it! ', 'Here we go! ']) + cue;
    } else if (stage === 'countdown') {
      text = 'Five! Four! Three! Two! One! Push!';
    } else if (stage === 'rest') {
      text = 'Rest up, Champ! Catch your breath. Up next, ' + name + '. ' + cue;
    } else if (stage === 'done') {
      text = 'Boom! Session complete! Outstanding work, Champ! You built real strength today!';
    }
    
    TitanVoice.say(text);
  }
};

/* Expose globally (this script is not an ES module) */
if (typeof window !== 'undefined') {
  window.TitanVoice = TitanVoice;
  window.TitanTrainer = TitanTrainer;
  window.resolveWorkout = resolveWorkout;
}

/* Leak prevention: free every GPU resource when the page goes away. */
function disposeStage3d() {
  if (!renderer || !scene) return;
  scene.traverse(o => {
    if (o.geometry) o.geometry.dispose();
    if (o.material) {
      (Array.isArray(o.material) ? o.material : [o.material]).forEach(m => {
        for (const k in m) { const v = m[k]; if (v && v.isTexture) v.dispose(); }
        m.dispose();
      });
    }
  });
  renderer.dispose();
  threeOK = false;
}
if (typeof window !== 'undefined') window.addEventListener('pagehide', disposeStage3d, { once: true });

/* Delegated click handler — a button click switches the animation (crossfade)
   AND gives a short Fitify-style spoken cue (user gesture → speech allowed). */
document.addEventListener('click', e => {
  const b = e.target.closest('.h3d-modes button');
  if (!b || !b.dataset.mode) return;
  setMode(b.dataset.mode);
  const w = resolveWorkout(b.dataset.mode);
  TitanVoice.say(b.dataset.mode === 'idle' ? 'Warming up.' : ('Go! ' + w.label));
});

/* Customize button toggles the clothes panel */
document.addEventListener('click', e => {
  if (e.target.closest('#tfCustomizeBtn') || e.target.closest('.tf-customize-btn')) {
    var panel = document.getElementById('tf-clothes-panel');
    if (panel) panel.classList.toggle('open');
  }
});

function animate() { requestAnimationFrame(animate); if (!threeOK) return;
  if (document.hidden || !stageOnScreen) return;   // tab backgrounded / hero scrolled away → skip GPU work

  // Theme check: keep background transparent in both themes
  scene.background = null;
  renderer.setClearColor(0x000000, 0);

  // Dynamic Camera Framing for Floor Exercises (Pushups, Planks, Crunches) vs Standing Poses
  const isFloorMode = ['pushup', 'plank', 'crunch', 'twist', 'legraise', 'mountain'].includes(mode);
  const targetCamY = isFloorMode ? 1.35 : 2.1;
  const targetCamZ = isFloorMode ? 7.6 : 6.4;
  const targetLookY = isFloorMode ? 0.4 : 1.4;

  // Smooth Lerp Camera position & controls target
  camera.position.y += (targetCamY - camera.position.y) * 0.08;
  camera.position.z += (targetCamZ - camera.position.z) * 0.08;
  if (controls) {
    controls.target.y += (targetLookY - controls.target.y) * 0.08;
    controls.update(); // Enforces the polar angle clamps and damping
  }

  // clamp dt so the mixer doesn't lurch after a skipped stretch (getDelta accumulates)
  const dt = Math.min(clock.getDelta(), 0.1), t = clock.getElapsedTime();
  
  // Animate procedural humanoid if active
  if (window.proceduralHumanoid) {
    const humanoid = window.proceduralHumanoid;
    
    // Reset standard postures first
    humanoid.rotation.set(0, 0, 0);
    humanoid.position.set(0, 0.3, 0);
    
    humanoid.userData.parts.leftArm.rotation.set(0, 0, 0);
    humanoid.userData.parts.rightArm.rotation.set(0, 0, 0);
    humanoid.userData.parts.leftForearm.rotation.set(0.2, 0, 0);
    humanoid.userData.parts.rightForearm.rotation.set(0.2, 0, 0);
    
    humanoid.userData.parts.leftThigh.position.set(-0.18, 0.35, 0);
    humanoid.userData.parts.rightThigh.position.set(0.18, 0.35, 0);
    humanoid.userData.parts.leftShin.position.set(-0.18, -0.3, 0);
    humanoid.userData.parts.rightShin.position.set(0.18, -0.3, 0);
    
    humanoid.userData.parts.torso.rotation.set(0, 0, 0);
    humanoid.userData.parts.torso.scale.set(1, 1, 1);
    humanoid.userData.parts.head.position.set(0, 2.15, 0);

    const speed = 3.5;
    if (mode === 'squat') {
      const squatFactor = Math.sin(t * speed) * 0.5 + 0.5; // 0 to 1
      humanoid.position.y = 0.3 - squatFactor * 0.45;
      
      // Bend knees and torso
      humanoid.userData.parts.torso.rotation.x = squatFactor * 0.25;
      humanoid.userData.parts.leftThigh.position.y = 0.35 - squatFactor * 0.15;
      humanoid.userData.parts.rightThigh.position.y = 0.35 - squatFactor * 0.15;
      humanoid.userData.parts.leftShin.position.y = -0.3 + squatFactor * 0.1;
      humanoid.userData.parts.rightShin.position.y = -0.3 + squatFactor * 0.1;
    } else if (mode === 'pushup') {
      // Rotate horizontal for push-up pose
      humanoid.rotation.x = -Math.PI / 2;
      const pushFactor = Math.sin(t * speed) * 0.5 + 0.5; // 0 to 1
      humanoid.position.y = 0.28 - pushFactor * 0.22; // Lower to Y=0 ground level
      humanoid.position.z = -pushFactor * 0.1;
      humanoid.userData.parts.leftArm.rotation.x = 0.8 + pushFactor * 0.4;
      humanoid.userData.parts.rightArm.rotation.x = 0.8 + pushFactor * 0.4;
      humanoid.userData.parts.leftForearm.rotation.x = 1.2 - pushFactor * 0.6;
      humanoid.userData.parts.rightForearm.rotation.x = 1.2 - pushFactor * 0.6;
    } else if (mode === 'jumpingjack') {
      const jackFactor = Math.sin(t * 5.0) * 0.5 + 0.5; // 0 to 1
      // Raise arms overhead
      humanoid.userData.parts.leftArm.rotation.z = -jackFactor * 2.2;
      humanoid.userData.parts.rightArm.rotation.z = jackFactor * 2.2;
      // Spread thighs
      humanoid.userData.parts.leftThigh.position.x = -0.18 - jackFactor * 0.2;
      humanoid.userData.parts.rightThigh.position.x = 0.18 + jackFactor * 0.2;
    } else if (mode === 'curl') {
      const curlFactor = Math.sin(t * 4.0) * 0.5 + 0.5; // 0 to 1
      // Move forearms up and down
      humanoid.userData.parts.leftForearm.rotation.x = 0.2 + curlFactor * 1.8;
      humanoid.userData.parts.rightForearm.rotation.x = 0.2 + curlFactor * 1.8;
    } else if (mode === 'press') {
      const pressFactor = Math.sin(t * 4.0) * 0.5 + 0.5; // 0 to 1
      // Arms up and down
      humanoid.userData.parts.leftArm.rotation.z = -1.5 - pressFactor * 1.2;
      humanoid.userData.parts.rightArm.rotation.z = 1.5 + pressFactor * 1.2;
    } else {
      // Breathing / Idle default posture
      const breath = Math.sin(t * 2.0);
      humanoid.userData.parts.torso.scale.set(1 + breath * 0.03, 1, 1 + breath * 0.03);
      humanoid.userData.parts.leftArm.rotation.z = 0.05 * breath;
      humanoid.userData.parts.rightArm.rotation.z = -0.05 * breath;
    }
  }

  if (useGLTF && window.TitanRig && TitanRig.attached) TitanRig.update(mode, t);   // rigged human performs the exercise
  else if (useGLTF && mixer) { mixer.update(dt); if (gltfRoot) gltfRoot.rotation.y = Math.sin(t * .25) * .12; }
  if (stage.parts) { const p = stage.parts.geometry.attributes.position;
    for (let i = 0; i < p.count; i++) { let y = p.getY(i) + .008; if (y > 4) y = 0; p.setY(i, y); } p.needsUpdate = true; }
  if (stage.ring) stage.ring.rotation.z = t * .4;
  renderer.render(scene, camera); }
