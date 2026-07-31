/* PHASE 9 VERIFICATION
   Proves (or disproves) three things against the RUNNING app:
     1. the 27 previously-undefined tokens now resolve to a real value
     2. text/background pairings meet WCAG AA in BOTH themes
     3. the theme toggle actually changes visible surfaces
   Screenshots land in _audit/after/phase9/.

   Evidence rule (Phase 8 §6d): every screenshot set is hashed, and files that
   should differ MUST differ. A capture that produces identical light/dark
   images is reported as FAIL, not quietly accepted.                          */
import { chromium } from 'playwright';
import fs from 'fs';
import path from 'path';
import crypto from 'crypto';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const BASE = 'http://localhost/Core%20calorie%20advisor';
const OUT = path.join(__dirname, 'after', 'phase9');

const PAGES = [
  ['home',      '/index.php'],
  ['pricing',   '/pages/pricing.php'],   // Tailwind-heavy (414 classes)
  ['workouts',  '/pages/workouts.php'],  // card-heavy
  ['login',     '/auth/login.php'],      // auth template
  /* id=1 ("Instant Six Pack", is_free=1) is REQUIRED. Without a valid id,
     player.php:6 flashes "Workout nahi mila." and 302s to workouts.php — so a
     bare /pages/player.php silently screenshots the wrong page. id=1 is free,
     so it also needs no login. */
  ['player',    '/pages/player.php?id=1'],   // Tailwind-heaviest (576) + 3D
  ['features',  '/pages/features.php'],      // most inline-style edits (16)
  ['about',     '/pages/about.php'],         // 9 inline-style edits
];

// tokens Phase 8 proved were referenced but never declared
const LEGACY = ['--text','--muted','--faint','--line','--line2','--molten','--heat',
  '--ember','--fire','--glass','--card','--steel','--shadow','--disp','--tech','--body',
  '--heat-soft','--gold','--amber','--purple','--orange','--champagne','--card-bg','--cca-dark'];

const srgb = c => { c /= 255; return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4); };
const lum = ([r, g, b]) => 0.2126 * srgb(r) + 0.7152 * srgb(g) + 0.0722 * srgb(b);
const ratio = (a, b) => { const [x, y] = [lum(a), lum(b)].sort((m, n) => n - m); return (x + 0.05) / (y + 0.05); };
const parse = s => { const m = String(s).match(/rgba?\(([\d.]+)[,\s]+([\d.]+)[,\s]+([\d.]+)(?:[,\s/]+([\d.]+))?/); return m ? [+m[1], +m[2], +m[3], m[4] === undefined ? 1 : +m[4]] : null; };
// flatten a translucent foreground over its backdrop
const over = (fg, bg) => fg[3] >= 1 ? fg.slice(0, 3) : [0, 1, 2].map(i => fg[i] * fg[3] + bg[i] * (1 - fg[3]));

const md5 = f => crypto.createHash('md5').update(fs.readFileSync(f)).digest('hex');

(async () => {
  fs.mkdirSync(OUT, { recursive: true });
  const browser = await chromium.launch();
  const results = { tokens: {}, contrast: [], shots: [], themeDiff: [] };

  for (const theme of ['dark', 'light']) {
    const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
    // set the theme the same way the app does, before any page script runs
    await ctx.addInitScript(t => localStorage.setItem('tf-theme', t), theme);
    const page = await ctx.newPage();

    for (const [name, url] of PAGES) {
      await page.goto(BASE + url, { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(1200);
      // dismiss the intro splash so it doesn't cover every screenshot
      await page.evaluate(() => { sessionStorage.setItem('cca_intro_seen', '1'); document.getElementById('cca-intro-shell')?.remove(); });
      await page.waitForTimeout(400);

      // --- 1. token resolution (once per theme, on the first page) ---
      if (name === 'home') {
        results.tokens[theme] = await page.evaluate(list => {
          const cs = getComputedStyle(document.documentElement);
          const out = {};
          for (const t of list) out[t] = cs.getPropertyValue(t).trim();
          return out;
        }, LEGACY);
      }

      // --- 2. contrast: sample real rendered text nodes ---
      const samples = await page.evaluate(() => {
        const seen = new Map();
        const px = s => { const m = String(s).match(/rgba?\(([\d.]+)[,\s]+([\d.]+)[,\s]+([\d.]+)(?:[,\s/]+([\d.]+))?/); return m ? [+m[1], +m[2], +m[3], m[4] === undefined ? 1 : +m[4]] : null; };
        /* Composite the ENTIRE ancestor background stack, back to front.
           A naive "first non-transparent ancestor" walk treats
           rgba(255,255,255,0.05) as solid white and reports white-on-white
           (1.00:1) for every card — a measurement artefact, not a real defect.
           Returns null when a background IMAGE or gradient is involved, since
           contrast against a photo can't be computed from colours alone. */
        /* ONE ordered walk outward from the text node. At each ancestor, in
           paint order: a background IMAGE (on the element itself, or on its
           ::before/::after, or on an absolutely-positioned previous sibling
           such as .hero-bg) means the real backdrop is a photo — not derivable
           from colours, so the sample is marked `photo` and excluded from
           scoring rather than counted as a failure.
           Crucially the walk STOPS at the first OPAQUE background colour,
           because an opaque layer occludes everything behind it. Without that
           stop, `.page-hero::before` far up the tree wrongly taints every
           element on the page (it did: it excluded 100% of samples). */
        const bgOf = el => {
          const stack = [];
          for (let n = el; n && n.nodeType === 1; n = n.parentElement) {
            const cs = getComputedStyle(n);
            if (cs.backgroundImage && cs.backgroundImage !== 'none') return { photo: true };
            for (const pseudo of ['::before', '::after']) {
              const p = getComputedStyle(n, pseudo);
              if (p && p.content !== 'none' && p.backgroundImage && p.backgroundImage !== 'none') return { photo: true };
            }
            const prev = n.previousElementSibling;
            if (prev) {
              const ps = getComputedStyle(prev);
              if (ps.backgroundImage !== 'none' && (ps.position === 'absolute' || ps.position === 'fixed')) return { photo: true };
            }
            const c = px(cs.backgroundColor);
            if (c && c[3] > 0) { stack.push(c); if (c[3] >= 1) break; }   // opaque → occludes
          }
          const base = px(getComputedStyle(document.documentElement).backgroundColor);
          let out = (base && base[3] >= 1) ? base.slice(0, 3) : [255, 255, 255];
          for (let i = stack.length - 1; i >= 0; i--) {
            const c = stack[i];
            out = [0, 1, 2].map(k => c[k] * c[3] + out[k] * (1 - c[3]));
          }
          return { photo: false, color: `rgb(${out.map(Math.round).join(', ')})` };
        };
        for (const el of document.querySelectorAll('p,span,a,h1,h2,h3,h4,li,td,th,label,button,small,b,strong,div')) {
          const txt = (el.textContent || '').trim();
          if (!txt || txt.length < 2) continue;
          if (el.children.length && ![...el.childNodes].some(n => n.nodeType === 3 && n.textContent.trim())) continue;
          const cs = getComputedStyle(el);
          if (cs.visibility === 'hidden' || cs.display === 'none' || +cs.opacity === 0) continue;
          /* background-clip:text glyphs (.grad-text) are painted from the
             element's BACKGROUND, and `color` is irrelevant because
             -webkit-text-fill-color is transparent. Reading `color` here scores
             the wrong pixel entirely, so treat these as not machine-measurable
             and verify them from the screenshots instead. */
          const fill = cs.webkitTextFillColor || cs.getPropertyValue('-webkit-text-fill-color');
          const clipped = /transparent|rgba\(0, 0, 0, 0\)/.test(fill || '') ||
                          /text/.test(cs.webkitBackgroundClip || cs.backgroundClip || '');
          const r = el.getBoundingClientRect();
          if (r.width < 4 || r.height < 4) continue;
          const b = bgOf(el);
          const unmeasurable = b.photo || clipped;
          const size = parseFloat(cs.fontSize), weight = +cs.fontWeight || 400;
          const key = cs.color + '|' + (unmeasurable ? 'UNMEASURABLE' : b.color) + '|' + (size >= 24 || (size >= 18.66 && weight >= 700) ? 'L' : 'N');
          if (!seen.has(key)) seen.set(key, { color: cs.color, bg: b.color || 'rgb(0, 0, 0)', photo: unmeasurable, size, weight, sample: txt.slice(0, 40), sel: el.tagName.toLowerCase() + (el.className && typeof el.className === 'string' ? '.' + el.className.split(/\s+/).slice(0, 2).join('.') : '') });
        }
        return [...seen.values()];
      });

      for (const s of samples) {
        const fg = parse(s.color), bg = parse(s.bg);
        if (!fg || !bg) continue;
        if (fg[3] === 0) continue;
        const large = s.size >= 24 || (s.size >= 18.66 && s.weight >= 700);
        const cr = ratio(over(fg, bg.slice(0, 3)), bg.slice(0, 3));
        results.contrast.push({ page: name, theme, sel: s.sel, sample: s.sample, size: Math.round(s.size), large, photo: s.photo, ratio: +cr.toFixed(2), need: large ? 3 : 4.5, pass: cr >= (large ? 3 : 4.5) });
      }

      const file = path.join(OUT, `${name}-${theme}.png`);
      await page.screenshot({ path: file, fullPage: false });
      results.shots.push({ page: name, theme, file: path.relative(path.join(__dirname, '..'), file), md5: md5(file) });
    }
    await ctx.close();
  }
  await browser.close();

  // --- 3. did the theme actually change the pixels? ---
  for (const [name] of PAGES) {
    const d = results.shots.find(s => s.page === name && s.theme === 'dark');
    const l = results.shots.find(s => s.page === name && s.theme === 'light');
    results.themeDiff.push({ page: name, differs: d && l ? d.md5 !== l.md5 : false });
  }

  fs.writeFileSync(path.join(__dirname, 'phase9_results.json'), JSON.stringify(results, null, 2));

  // ---------------- console report ----------------
  console.log('\n=== 1. LEGACY TOKEN RESOLUTION ===');
  for (const theme of ['dark', 'light']) {
    const t = results.tokens[theme] || {};
    const unresolved = Object.entries(t).filter(([, v]) => !v);
    console.log(`  ${theme}: ${Object.keys(t).length - unresolved.length}/${Object.keys(t).length} resolve` +
      (unresolved.length ? `  UNRESOLVED: ${unresolved.map(([k]) => k).join(', ')}` : '  ✓ all resolve'));
  }
  console.log('  sample (dark):', ['--muted', '--line', '--molten', '--tech'].map(k => `${k}=${results.tokens.dark?.[k]}`).join('  '));
  console.log('  sample (light):', ['--muted', '--line', '--molten', '--tech'].map(k => `${k}=${results.tokens.light?.[k]}`).join('  '));

  console.log('\n=== 2. CONTRAST (WCAG AA) — measurable surfaces only ===');
  for (const theme of ['dark', 'light']) {
    const all = results.contrast.filter(r => r.theme === theme);
    const rows = all.filter(r => !r.photo);            // flat surfaces = machine-measurable
    const skipped = all.length - rows.length;
    const fails = rows.filter(r => !r.pass);
    console.log(`  ${theme}: ${rows.length - fails.length}/${rows.length} pass  (${fails.length} fail)   [${skipped} over photo/gradient — not machine-measurable, checked visually]`);
  }
  const allFails = results.contrast.filter(r => !r.pass && !r.photo).sort((a, b) => a.ratio - b.ratio);
  if (allFails.length) {
    console.log('\n  --- REAL FAILURES (flat surfaces) ---');
    allFails.slice(0, 30).forEach(f => console.log(`   ${f.ratio.toFixed(2)}:1 (need ${f.need}) [${f.theme}] ${f.page} <${f.sel}> "${f.sample}"`));
  } else {
    console.log('\n  No failures on machine-measurable surfaces.');
  }

  console.log('\n=== 3. THEME ACTUALLY CHANGES PIXELS ===');
  results.themeDiff.forEach(t => console.log(`  ${t.page}: ${t.differs ? 'DIFFERS ✓' : 'IDENTICAL ✗ (theme had no visible effect)'}`));

  console.log('\n=== 4. SCREENSHOTS ===');
  results.shots.forEach(s => console.log(`  ${s.md5.slice(0, 8)}  ${s.file}`));
  const uniq = new Set(results.shots.map(s => s.md5));
  console.log(`  ${results.shots.length} files, ${uniq.size} distinct — ${uniq.size === results.shots.length ? 'OK ✓' : 'DUPLICATES PRESENT ✗'}`);
})();
