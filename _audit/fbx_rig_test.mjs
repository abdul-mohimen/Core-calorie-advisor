/* End-to-end: load the Mixamo FBX, scale it to human height, attach CCARig,
   drive an exercise, and prove the bones actually move. */
import { chromium } from 'playwright';
const BASE = 'http://localhost/Core%20calorie%20advisor';

const PAGE = `<!doctype html><meta charset="utf-8">
<body style="margin:0;background:#0F1115">
<div id="stage" style="width:900px;height:700px"></div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/libs/fflate.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/FBXLoader.js"></script>
<script src="${BASE}/assets/js/titan-rig.js"></script>
<script>
window.__r = null;
const scene = new THREE.Scene();
scene.background = new THREE.Color(0x0F1115);
const cam = new THREE.PerspectiveCamera(42, 900/700, .1, 100);
cam.position.set(2.4, 1.5, 3.0); cam.lookAt(0, 0.95, 0);
const rn = new THREE.WebGLRenderer({antialias:true}); rn.setSize(900,700);
document.getElementById('stage').appendChild(rn.domElement);
scene.add(new THREE.HemisphereLight(0xffffff, 0x222233, 1.5));
const key = new THREE.DirectionalLight(0xffffff, 2.2); key.position.set(3,5,4); scene.add(key);
const fill = new THREE.DirectionalLight(0xffd9b0, 1.0); fill.position.set(-3,2,-2); scene.add(fill);

new THREE.FBXLoader().load('${BASE}/assets/models/trainer-ch06.fbx', (obj) => {
  // Mixamo FBX is in centimetres — scale to ~1.8 m
  const box = new THREE.Box3().setFromObject(obj);
  const h = box.max.y - box.min.y;
  const s = 1.8 / h;
  obj.scale.setScalar(s);
  obj.updateMatrixWorld(true);
  const b2 = new THREE.Box3().setFromObject(obj);
  obj.position.y -= b2.min.y;
  scene.add(obj);

  const attached = window.CCARig && CCARig.attach(obj);
  let moved = false, samples = [];
  if (attached) {
    let hip=null, spine=null, arm=null;
    obj.traverse(o=>{ if(o.isBone){
      if(!spine && /Spine$/i.test(o.name)) spine=o;
      if(!arm && /LeftArm$/i.test(o.name)) arm=o;
    }});
    let t = 0;
    const tick = () => {
      t += 0.05;
      CCARig.update('squat', t);
      if (spine && arm) samples.push(+(spine.rotation.x+arm.rotation.z).toFixed(4));
      rn.render(scene, cam);
      if (samples.length < 24) requestAnimationFrame(tick);
      else {
        moved = new Set(samples).size > 1;
        window.__r = { ok:true, attached:true, moved, distinct:new Set(samples).size,
                       scaledHeight:+(b2.max.y-b2.min.y).toFixed(2), rawHeight:+h.toFixed(1) };
      }
    };
    tick();
  } else {
    rn.render(scene, cam);
    window.__r = { ok:true, attached:false, rawHeight:+h.toFixed(1) };
  }
}, undefined, e => { window.__r = { ok:false, error:String(e && e.message || e) }; });
</script></body>`;

(async () => {
  const b = await chromium.launch();
  const p = await b.newPage({ viewport: { width: 900, height: 700 } });
  await p.setContent(PAGE, { waitUntil: 'load' });
  await p.waitForFunction(() => window.__r !== null, { timeout: 180000 });
  console.log(JSON.stringify(await p.evaluate(() => window.__r), null, 2));
  await p.screenshot({ path: './after/phase11/fbx-character.png' });
  console.log('screenshot -> _audit/after/phase11/fbx-character.png');
  await b.close();
})();
