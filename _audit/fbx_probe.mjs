/* Loads the Mixamo FBX with Three's FBXLoader in a real browser and reports
   what it actually contains + how long it costs. Decide with numbers, not
   assumptions, before wiring a 52 MB asset into every page. */
import { chromium } from 'playwright';

const BASE = 'http://localhost/Core%20calorie%20advisor';

const PAGE = `<!doctype html><meta charset="utf-8"><body style="background:#111;color:#eee;font:13px monospace">
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/libs/fflate.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/FBXLoader.js"></script>
<script>
window.__result = null;
window.addEventListener('load', () => {
  const t0 = performance.now();
  new THREE.FBXLoader().load('${BASE}/assets/models/trainer-ch06.fbx',
    (obj) => {
      const meshes = [], mats = new Set(), bones = [];
      obj.traverse(o => {
        if (o.isMesh || o.isSkinnedMesh) {
          meshes.push({ name: o.name, skinned: !!o.isSkinnedMesh,
            tris: o.geometry && o.geometry.index ? o.geometry.index.count / 3
                 : (o.geometry ? o.geometry.attributes.position.count / 3 : 0),
            mats: (Array.isArray(o.material) ? o.material : [o.material]).map(m => m && m.name) });
          (Array.isArray(o.material) ? o.material : [o.material]).forEach(m => m && mats.add(m.name));
        }
        if (o.isBone) bones.push(o.name);
      });
      const box = new THREE.Box3().setFromObject(obj);
      window.__result = {
        ok: true,
        loadMs: Math.round(performance.now() - t0),
        meshes,
        materials: [...mats],
        boneCount: bones.length,
        hasMixamoBones: bones.filter(b => /mixamorig/i.test(b)).length,
        sampleBones: bones.filter(b => /mixamorig/i.test(b)).slice(0, 6),
        sizeY: +(box.max.y - box.min.y).toFixed(2),
        animations: obj.animations ? obj.animations.length : 0
      };
    },
    undefined,
    (e) => { window.__result = { ok: false, error: String(e && e.message || e) }; });
});
</script></body>`;

(async () => {
  const b = await chromium.launch();
  const p = await b.newPage();
  await p.route('**/fbxtest', r => r.fulfill({ contentType: 'text/html', body: PAGE }));
  await p.goto('http://localhost/fbxtest').catch(async () => {
    await p.setContent(PAGE, { waitUntil: 'load' });
  });
  await p.waitForFunction(() => window.__result !== null, { timeout: 180000 });
  const r = await p.evaluate(() => window.__result);
  console.log(JSON.stringify(r, null, 2));
  await b.close();
})();
