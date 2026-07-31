/* Renders one still of the REAL 3D trainer performing each exercise mode and
   saves it to assets/images/exercises/<mode>.png.

   Why render instead of sourcing photos: the exercise cards need "the workout
   being performed" imagery. Stock photos would be generic, externally hosted
   and licence-encumbered. These are the actual coach the user trains with,
   generated from the project's own trainers.glb — no new dependency, no
   licensing question, and the pictures always match the animation. */
import { chromium } from 'playwright';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.join(__dirname, '..');
const OUT = path.join(ROOT, 'assets', 'images', 'exercises');
const BASE = 'http://localhost/Core%20calorie%20advisor';

// every anim_mode used by the seeded exercises, plus the two idle states
const MODES = ['idle', 'warmup', 'pushup', 'wallpushup', 'squat', 'yoga',
  'jumpingjack', 'curl', 'press', 'plank', 'crunch', 'twist',
  'legraise', 'mountain', 'highknees'];

/* Floor-work reads best from a low, side-on camera; standing work from a
   slightly raised 3/4. Chosen per mode so no pose is shot from a useless angle. */
const FLOOR = new Set(['pushup', 'plank', 'crunch', 'legraise', 'mountain', 'twist']);

(async () => {
  fs.mkdirSync(OUT, { recursive: true });
  const browser = await chromium.launch();
  const ctx = await browser.newContext({ viewport: { width: 900, height: 900 }, deviceScaleFactor: 2 });
  await ctx.addInitScript(() => sessionStorage.setItem('cca_intro_seen', '1'));
  const page = await ctx.newPage();

  await page.goto(BASE + '/index.php', { waitUntil: 'domcontentloaded' });
  await page.waitForFunction(() => window.CCARig && window.CCARig.attached, { timeout: 90000 });
  console.log('rig attached — rendering poses\n');

  /* Hide ALL page chrome that can bleed into the frame — the first run captured
     the floating chat widget in the corner of every pose. */
  await page.evaluate(() => {
    document.querySelectorAll(
      '.h3d-modes, .h3d-tag, .tf3d-ph, #tf-chat, [class*="chat"], [id*="chat"], .tf-clothes-panel'
    ).forEach(e => (e.style.display = 'none'));
    // brighten the key light so the charcoal kit doesn't read as a silhouette
    if (window.tfScene) {
      window.tfScene.traverse(o => {
        if (o.isLight) o.intensity *= 1.9;
      });
    }
  });

  const results = [];
  for (const mode of MODES) {
    const floor = FLOOR.has(mode);
    await page.evaluate(({ m, floor }) => {
      setMode(m);
      /* Tight framing — the first pass shot from ~3.5 m and the athlete was a
         speck in a mostly-empty floor. These sit close enough to fill the card. */
      window.__poseCam = floor ? [1.75, 0.62, 1.55, 0, 0.28, 0] : [1.30, 1.05, 1.70, 0, 0.92, 0];
    }, { m: mode, floor });

    // let the pose settle, then freeze the camera for the shot
    await page.waitForTimeout(1100);
    await page.evaluate(() => {
      const c = window.__poseCam;
      if (window.tfCamera && c) {
        window.tfCamera.position.set(c[0], c[1], c[2]);
        window.tfCamera.lookAt(c[3], c[4], c[5]);
        window.tfCamera.updateProjectionMatrix();
      }
      if (window.tfControls) window.tfControls.enabled = false;
    });
    await page.waitForTimeout(350);

    const el = await page.$('#hero3d');
    const file = path.join(OUT, mode + '.png');
    await el.screenshot({ path: file });
    const kb = Math.round(fs.statSync(file).size / 1024);
    results.push({ mode, kb });
    console.log(`  ${String(kb).padStart(4)} KB  ${mode}.png`);
  }

  await browser.close();

  // every file must be distinct — identical bytes would mean the pose never changed
  const crypto = await import('crypto');
  const hashes = results.map(r => ({
    mode: r.mode,
    md5: crypto.createHash('md5').update(fs.readFileSync(path.join(OUT, r.mode + '.png'))).digest('hex').slice(0, 10)
  }));
  const uniq = new Set(hashes.map(h => h.md5));
  console.log(`\n${hashes.length} files, ${uniq.size} distinct — ${uniq.size === hashes.length ? 'OK: every pose differs' : 'FAIL: duplicate poses'}`);
  if (uniq.size !== hashes.length) {
    const seen = {};
    hashes.forEach(h => { (seen[h.md5] = seen[h.md5] || []).push(h.mode); });
    Object.values(seen).filter(v => v.length > 1).forEach(v => console.log('   duplicates:', v.join(', ')));
  }
})();
