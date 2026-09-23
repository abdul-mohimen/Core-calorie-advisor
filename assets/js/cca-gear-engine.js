/**
 * CORE CALORIE ADVISOR — 3D Socket Gear & Outfits Engine
 * Provides AAA Game-style (Free Fire / GTA) real 3D equipment,
 * wearable accessories, bone sockets, and VFX auras in Three.js.
 */
(function (w) {
  'use strict';

  if (typeof THREE === 'undefined') {
    console.warn('[CCAGearEngine] Three.js not found');
    return;
  }

  const GEAR_REGISTRY = {
    headphones: {
      name: 'Cyber Studio Pro Headphones',
      slot: 'head',
      bone: 'head',
      rarity: 'epic',
      color: '#FF6B1A',
      desc: 'Over-ear acoustic monitor with active audio drivers and LED ring',
      build: createHeadphones
    },
    visor: {
      name: 'HUD Tactical Visor',
      slot: 'head',
      bone: 'head',
      rarity: 'rare',
      color: '#00F0FF',
      desc: 'Smart biometric optical HUD with real-time vitals display',
      build: createVisor
    },
    headband: {
      name: 'Warrior Sweatband',
      slot: 'head',
      bone: 'head',
      rarity: 'common',
      color: '#E5E7EB',
      desc: 'Moisture-wicking athletic compression sweatband',
      build: createHeadband
    },
    belt: {
      name: 'Titan Leather Lifting Belt',
      slot: 'waist',
      bone: 'spine',
      rarity: 'legendary',
      color: '#FFB800',
      desc: '10mm genuine leather Olympic belt with double-prong brushed chrome buckle',
      build: createLiftingBelt
    },
    watch: {
      name: 'Apex Biometric Smartwatch',
      slot: 'arm',
      bone: 'lForeArm',
      rarity: 'rare',
      color: '#10B981',
      desc: 'Continuous optical heart-rate, VO2 max & telemetry tracker',
      build: createSmartwatch
    },
    wraps: {
      name: 'Powerlifting Wrist Wraps',
      slot: 'wrists',
      bone: 'hands',
      rarity: 'common',
      color: '#EF4444',
      desc: 'Heavy-duty elastic wrist stabilizer for bench and overhead press',
      build: createWristWraps
    },
    dumbbells: {
      name: 'Cast Iron Hex Dumbbells',
      slot: 'hands',
      bone: 'hands',
      rarity: 'rare',
      color: '#9CA3AF',
      desc: '20kg textured knurled-steel dumbbells with anti-roll hex heads',
      build: createDumbbells
    }
  };

  // VFX Shaders & Auras
  const AURAS = {
    cyber: {
      name: 'Cyber Pulse Aura',
      color: '#00F0FF',
      rarity: 'epic',
      desc: 'Pulsing holographic neon ring with biometric grid lines',
      build: createCyberAura
    },
    ember: {
      name: 'Ember Fury Aura',
      color: '#FF5500',
      rarity: 'legendary',
      desc: 'Intense rising combustion particles and thermal floor glow',
      build: createEmberAura
    },
    electric: {
      name: 'Lightning Surge Aura',
      color: '#A855F7',
      rarity: 'epic',
      desc: 'High-voltage ionization arcs encircling the platform',
      build: createElectricAura
    }
  };

  /* Helper: find standard Mixamo bone by case-insensitive name */
  function findBone(root, key) {
    if (!root) return null;
    const candidates = {
      head: ['mixamorig:head', 'mixamorighead', 'head'],
      neck: ['mixamorig:neck', 'mixamorigneck', 'neck'],
      spine: ['mixamorig:spine', 'mixamorigspine', 'spine', 'mixamorig:spine1', 'spine1', 'mixamorig:hips', 'hips'],
      hips: ['mixamorig:hips', 'mixamorighips', 'hips'],
      lForeArm: ['mixamorig:leftforearm', 'mixamorigleftforearm', 'leftforearm'],
      rForeArm: ['mixamorig:rightforearm', 'mixamorigrightforearm', 'rightforearm'],
      lHand: ['mixamorig:lefthand', 'mixamoriglefthand', 'lefthand'],
      rHand: ['mixamorig:righthand', 'mixamorigrighthand', 'righthand']
    }[key] || [key];

    let found = null;
    root.traverse(o => {
      if (found || !o.isBone) return;
      const lower = o.name.toLowerCase().replace(/[\s_-]/g, '');
      for (let c of candidates) {
        if (lower === c.toLowerCase().replace(/[\s:_-]/g, '')) {
          found = o;
          return;
        }
      }
    });
    return found;
  }

  /* ══ BUILDERS FOR REAL 3D SOCKET GEAR ══ */

  function createHeadphones() {
    const group = new THREE.Group();
    group.name = 'cca_gear_headphones';

    const bandMat = new THREE.MeshStandardMaterial({
      color: 0x181a20,
      metalness: 0.6,
      roughness: 0.35
    });
    const cupMat = new THREE.MeshStandardMaterial({
      color: 0x0f1115,
      metalness: 0.8,
      roughness: 0.2
    });
    const glowMat = new THREE.MeshStandardMaterial({
      color: 0xff6b1a,
      emissive: 0xff4400,
      emissiveIntensity: 0.85,
      roughness: 0.1
    });

    // Headband arc
    const bandGeom = new THREE.TorusGeometry(0.095, 0.012, 10, 24, Math.PI * 0.95);
    const band = new THREE.Mesh(bandGeom, bandMat);
    band.rotation.x = Math.PI * 0.5;
    band.rotation.z = Math.PI * 0.52;
    band.position.set(0, 0.088, -0.005);
    group.add(band);

    // Left and right earcups
    [-0.092, 0.092].forEach(x => {
      const cupGroup = new THREE.Group();
      cupGroup.position.set(x, 0.01, 0);

      // Outer housing
      const cupGeom = new THREE.CylinderGeometry(0.038, 0.038, 0.024, 20);
      const cup = new THREE.Mesh(cupGeom, cupMat);
      cup.rotation.z = Math.PI * 0.5;
      cupGroup.add(cup);

      // Glowing LED accent ring
      const ringGeom = new THREE.TorusGeometry(0.032, 0.0035, 8, 20);
      const ring = new THREE.Mesh(ringGeom, glowMat);
      ring.rotation.y = Math.PI * 0.5;
      ring.position.x = x > 0 ? 0.013 : -0.013;
      cupGroup.add(ring);

      // Soft ear cushion
      const padMat = new THREE.MeshStandardMaterial({ color: 0x08090b, roughness: 0.9 });
      const padGeom = new THREE.TorusGeometry(0.033, 0.01, 8, 20);
      const pad = new THREE.Mesh(padGeom, padMat);
      pad.rotation.y = Math.PI * 0.5;
      pad.position.x = x > 0 ? -0.006 : 0.006;
      cupGroup.add(pad);

      group.add(cupGroup);
    });

    return group;
  }

  function createVisor() {
    const group = new THREE.Group();
    group.name = 'cca_gear_visor';

    const frameMat = new THREE.MeshStandardMaterial({ color: 0x111317, metalness: 0.9, roughness: 0.2 });
    const glassMat = new THREE.MeshStandardMaterial({
      color: 0x00f0ff,
      emissive: 0x00a8cc,
      emissiveIntensity: 0.6,
      transparent: true,
      opacity: 0.82,
      roughness: 0.1,
      metalness: 0.9
    });

    // Curved front glass
    const visorGeom = new THREE.CylinderGeometry(0.09, 0.09, 0.032, 24, 1, true, -Math.PI * 0.38, Math.PI * 0.76);
    const glass = new THREE.Mesh(visorGeom, glassMat);
    glass.position.set(0, 0.045, 0.05);
    glass.rotation.y = Math.PI * 0.5;
    group.add(glass);

    // Frame top brow
    const browGeom = new THREE.TorusGeometry(0.09, 0.006, 6, 20, Math.PI * 0.75);
    const brow = new THREE.Mesh(browGeom, frameMat);
    brow.position.set(0, 0.061, 0.05);
    brow.rotation.x = Math.PI * 0.5;
    brow.rotation.z = Math.PI * 0.625;
    group.add(brow);

    return group;
  }

  function createHeadband() {
    const group = new THREE.Group();
    group.name = 'cca_gear_headband';
    const bandMat = new THREE.MeshStandardMaterial({
      color: 0x222630,
      roughness: 0.85,
      metalness: 0.1
    });
    const stripeMat = new THREE.MeshStandardMaterial({
      color: 0xff6b1a,
      roughness: 0.6
    });

    const geom = new THREE.CylinderGeometry(0.092, 0.094, 0.032, 28, 1, true);
    const mesh = new THREE.Mesh(geom, bandMat);
    mesh.position.set(0, 0.058, 0);
    group.add(mesh);

    const stripeGeom = new THREE.CylinderGeometry(0.093, 0.095, 0.008, 28, 1, true);
    const stripe = new THREE.Mesh(stripeGeom, stripeMat);
    stripe.position.set(0, 0.058, 0);
    group.add(stripe);

    return group;
  }

  function createLiftingBelt() {
    const group = new THREE.Group();
    group.name = 'cca_gear_belt';

    const leatherMat = new THREE.MeshStandardMaterial({
      color: 0x171412,
      roughness: 0.65,
      metalness: 0.15
    });
    const chromeMat = new THREE.MeshStandardMaterial({
      color: 0xd8d8d8,
      metalness: 0.95,
      roughness: 0.15
    });
    const goldAccentMat = new THREE.MeshStandardMaterial({
      color: 0xffb800,
      metalness: 0.85,
      roughness: 0.25
    });

    // Contoured wide-back weightlifting belt
    const beltGeom = new THREE.CylinderGeometry(0.182, 0.178, 0.13, 32, 1, true);
    const belt = new THREE.Mesh(beltGeom, leatherMat);
    belt.position.set(0, 0.02, 0);
    group.add(belt);

    // Front chrome heavy buckle
    const buckleGeom = new THREE.BoxGeometry(0.065, 0.08, 0.02);
    const buckle = new THREE.Mesh(buckleGeom, chromeMat);
    buckle.position.set(0, 0.02, 0.185);
    group.add(buckle);

    // Embossed center core brand badge
    const badgeGeom = new THREE.BoxGeometry(0.045, 0.045, 0.015);
    const badge = new THREE.Mesh(badgeGeom, goldAccentMat);
    badge.position.set(0, 0.02, -0.18);
    group.add(badge);

    return group;
  }

  function createSmartwatch() {
    const group = new THREE.Group();
    group.name = 'cca_gear_watch';

    const strapMat = new THREE.MeshStandardMaterial({ color: 0x1f232b, roughness: 0.7 });
    const caseMat = new THREE.MeshStandardMaterial({ color: 0x090b0e, metalness: 0.9, roughness: 0.2 });
    const screenMat = new THREE.MeshStandardMaterial({
      color: 0x10b981,
      emissive: 0x059669,
      emissiveIntensity: 0.9,
      roughness: 0.1
    });

    // Strap band around wrist
    const strapGeom = new THREE.CylinderGeometry(0.042, 0.04, 0.028, 20, 1, true);
    const strap = new THREE.Mesh(strapGeom, strapMat);
    group.add(strap);

    // Watch body
    const caseGeom = new THREE.BoxGeometry(0.038, 0.03, 0.014);
    const watchCase = new THREE.Mesh(caseGeom, caseMat);
    watchCase.position.set(0, 0, 0.042);
    group.add(watchCase);

    // OLED display face
    const screenGeom = new THREE.PlaneGeometry(0.028, 0.022);
    const screen = new THREE.Mesh(screenGeom, screenMat);
    screen.position.set(0, 0, 0.05);
    group.add(screen);

    return group;
  }

  function createWristWraps() {
    const group = new THREE.Group();
    group.name = 'cca_gear_wraps';
    const wrapMat = new THREE.MeshStandardMaterial({ color: 0x991b1b, roughness: 0.75 });
    const lineMat = new THREE.MeshStandardMaterial({ color: 0x111827, roughness: 0.6 });

    [-1, 1].forEach(side => {
      const wrapSub = new THREE.Group();
      wrapSub.name = side === 1 ? 'wrap_r' : 'wrap_l';

      const wrapGeom = new THREE.CylinderGeometry(0.046, 0.044, 0.06, 20, 1, true);
      const wrapMesh = new THREE.Mesh(wrapGeom, wrapMat);
      wrapSub.add(wrapMesh);

      const lineGeom = new THREE.TorusGeometry(0.046, 0.004, 8, 20);
      const lineMesh = new THREE.Mesh(lineGeom, lineMat);
      lineMesh.rotation.x = Math.PI * 0.5;
      wrapSub.add(lineMesh);

      group.add(wrapSub);
    });

    return group;
  }

  function createDumbbells() {
    const group = new THREE.Group();
    group.name = 'cca_gear_dumbbells';

    const steelMat = new THREE.MeshStandardMaterial({ color: 0xcccccc, metalness: 0.95, roughness: 0.2 });
    const rubberMat = new THREE.MeshStandardMaterial({ color: 0x1f242d, roughness: 0.8, metalness: 0.1 });
    const ringMat = new THREE.MeshStandardMaterial({ color: 0xff6b1a, emissive: 0xff4400, emissiveIntensity: 0.6 });

    function buildSingleDumbbell() {
      const dbGroup = new THREE.Group();

      // Grip handle
      const barGeom = new THREE.CylinderGeometry(0.015, 0.015, 0.24, 16);
      const bar = new THREE.Mesh(barGeom, steelMat);
      bar.rotation.z = Math.PI * 0.5;
      dbGroup.add(bar);

      // Hex heads on both ends
      [-0.12, 0.12].forEach(offset => {
        const headGeom = new THREE.CylinderGeometry(0.065, 0.065, 0.065, 6);
        const head = new THREE.Mesh(headGeom, rubberMat);
        head.rotation.z = Math.PI * 0.5;
        head.position.x = offset;
        dbGroup.add(head);

        const accentGeom = new THREE.TorusGeometry(0.05, 0.005, 6, 16);
        const accent = new THREE.Mesh(accentGeom, ringMat);
        accent.rotation.y = Math.PI * 0.5;
        accent.position.x = offset + (offset > 0 ? 0.034 : -0.034);
        dbGroup.add(accent);
      });

      return dbGroup;
    }

    const dbL = buildSingleDumbbell();
    dbL.name = 'dumbbell_l';
    group.add(dbL);

    const dbR = buildSingleDumbbell();
    dbR.name = 'dumbbell_r';
    group.add(dbR);

    return group;
  }

  /* ══ AURAS & VFX BUILDERS ══ */

  function createCyberAura() {
    const group = new THREE.Group();
    group.name = 'cca_aura_cyber';

    const ringMat = new THREE.MeshBasicMaterial({
      color: 0x00f0ff,
      transparent: true,
      opacity: 0.75,
      side: THREE.DoubleSide
    });
    const ringGeom = new THREE.RingGeometry(0.68, 0.72, 48);
    const ring = new THREE.Mesh(ringGeom, ringMat);
    ring.rotation.x = -Math.PI * 0.5;
    ring.position.y = 0.02;
    group.add(ring);

    const innerGeom = new THREE.RingGeometry(0.55, 0.57, 36);
    const inner = new THREE.Mesh(innerGeom, ringMat.clone());
    inner.material.opacity = 0.45;
    inner.rotation.x = -Math.PI * 0.5;
    inner.position.y = 0.025;
    group.add(inner);

    return group;
  }

  function createEmberAura() {
    const group = new THREE.Group();
    group.name = 'cca_aura_ember';

    const ringMat = new THREE.MeshBasicMaterial({
      color: 0xff5500,
      transparent: true,
      opacity: 0.85,
      side: THREE.DoubleSide
    });
    const ringGeom = new THREE.RingGeometry(0.65, 0.73, 40);
    const ring = new THREE.Mesh(ringGeom, ringMat);
    ring.rotation.x = -Math.PI * 0.5;
    ring.position.y = 0.02;
    group.add(ring);

    return group;
  }

  function createElectricAura() {
    const group = new THREE.Group();
    group.name = 'cca_aura_electric';

    const ringMat = new THREE.MeshBasicMaterial({
      color: 0xa855f7,
      transparent: true,
      opacity: 0.8,
      side: THREE.DoubleSide
    });
    const ringGeom = new THREE.RingGeometry(0.66, 0.72, 48);
    const ring = new THREE.Mesh(ringGeom, ringMat);
    ring.rotation.x = -Math.PI * 0.5;
    ring.position.y = 0.02;
    group.add(ring);

    return group;
  }

  /* ══ ATTACH & UPDATE PIPELINE ══ */

  function removeExistingGear(root) {
    if (!root) return;
    const toRemove = [];
    root.traverse(o => {
      if (o.name && (o.name.startsWith('cca_gear_') || o.name.startsWith('cca_aura_'))) {
        toRemove.push(o);
      }
    });
    toRemove.forEach(o => {
      if (o.parent) o.parent.remove(o);
      if (o.geometry) o.geometry.dispose();
      if (o.material) {
        if (Array.isArray(o.material)) o.material.forEach(m => m.dispose());
        else o.material.dispose();
      }
    });
  }

  function attach(root, ld) {
    if (!root) return;
    ld = ld || (w.CCAWardrobe ? w.CCAWardrobe.load() : {});
    removeExistingGear(root);

    const headBone = findBone(root, 'head');
    const spineBone = findBone(root, 'spine');
    const lForeArm = findBone(root, 'lForeArm');
    const lHand = findBone(root, 'lHand');
    const rHand = findBone(root, 'rHand');

    // 1. Headwear
    if (ld.headphones && GEAR_REGISTRY.headphones && headBone) {
      const hp = GEAR_REGISTRY.headphones.build();
      headBone.add(hp);
    }
    if (ld.visor && GEAR_REGISTRY.visor && headBone) {
      const vs = GEAR_REGISTRY.visor.build();
      headBone.add(vs);
    }
    if (ld.headband && GEAR_REGISTRY.headband && headBone) {
      const hb = GEAR_REGISTRY.headband.build();
      headBone.add(hb);
    }

    // 2. Waist / Belt
    if (ld.belt && GEAR_REGISTRY.belt && spineBone) {
      const bt = GEAR_REGISTRY.belt.build();
      spineBone.add(bt);
    }

    // 3. Smartwatch
    if (ld.watch && GEAR_REGISTRY.watch && lForeArm) {
      const sw = GEAR_REGISTRY.watch.build();
      sw.position.set(0, 0.16, 0);
      lForeArm.add(sw);
    }

    // 4. Wrist Wraps
    if (ld.wraps && GEAR_REGISTRY.wraps) {
      const ww = GEAR_REGISTRY.wraps.build();
      const wrapL = ww.getObjectByName('wrap_l');
      const wrapR = ww.getObjectByName('wrap_r');
      if (wrapL && lHand) { wrapL.position.set(0, 0.02, 0); lHand.add(wrapL); }
      if (wrapR && rHand) { wrapR.position.set(0, 0.02, 0); rHand.add(wrapR); }
    }

    // 5. Dumbbells in hands
    if (ld.dumbbells && GEAR_REGISTRY.dumbbells) {
      const db = GEAR_REGISTRY.dumbbells.build();
      const dbL = db.getObjectByName('dumbbell_l');
      const dbR = db.getObjectByName('dumbbell_r');
      if (dbL && lHand) { dbL.position.set(0, 0.06, 0); lHand.add(dbL); }
      if (dbR && rHand) { dbR.position.set(0, 0.06, 0); rHand.add(dbR); }
    }

    // 6. VFX Aura Ring on platform base
    if (ld.aura && AURAS[ld.aura]) {
      const auraMesh = AURAS[ld.aura].build();
      root.add(auraMesh);
    }
  }

  // Animation pulse hook for studio / workout loops
  function update(timeSeconds, root) {
    if (!root) return;
    const cyberAura = root.getObjectByName('cca_aura_cyber');
    if (cyberAura) {
      cyberAura.rotation.y += 0.012;
      const s = 1.0 + Math.sin(timeSeconds * 3.5) * 0.04;
      cyberAura.scale.set(s, s, s);
    }
    const emberAura = root.getObjectByName('cca_aura_ember');
    if (emberAura) {
      emberAura.rotation.y -= 0.016;
      const s = 1.0 + Math.cos(timeSeconds * 4.0) * 0.05;
      emberAura.scale.set(s, s, s);
    }
    const elecAura = root.getObjectByName('cca_aura_electric');
    if (elecAura) {
      elecAura.rotation.y += 0.025;
      const s = 1.0 + Math.sin(timeSeconds * 6.0) * 0.03;
      elecAura.scale.set(s, s, s);
    }
  }

  w.CCAGearEngine = {
    GEAR_REGISTRY: GEAR_REGISTRY,
    AURAS: AURAS,
    attach: attach,
    update: update,
    removeExistingGear: removeExistingGear,
    findBone: findBone
  };

})(window);
