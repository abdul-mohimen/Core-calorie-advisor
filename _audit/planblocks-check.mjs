/* PHASE G regression guard — exercises the REAL planBlocks() from
   assets/js/player.js (not a reimplementation) under a minimal DOM stub.
   Run: node _audit/planblocks-check.mjs      Exit 0 = pass, 1 = fail. */
import { readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import vm from 'node:vm';

const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..');
const SRC  = readFileSync(join(ROOT, 'assets', 'js', 'player.js'), 'utf8');

function load(exercises) {
  const noop = () => {};
  const stubEl = { style:{}, classList:{ add:noop, remove:noop, contains:()=>false },
                   dataset:{}, textContent:'', innerHTML:'', appendChild:noop, disabled:false };
  const sandbox = {
    console, setTimeout, clearTimeout, setInterval:()=>1, clearInterval:noop,
    toast: noop, setMode: noop, MODE_MAP: {},
    document: {
      getElementById: () => null,
      querySelector:  () => null,
      querySelectorAll: () => [],
      addEventListener: noop,
      createElement: () => ({ ...stubEl })
    }
  };
  sandbox.window = sandbox;
  sandbox.window.TF_WORKOUT = { id: 1, title: 'T', ex: exercises };
  sandbox.window.matchMedia = () => ({ matches: false });
  vm.createContext(sandbox);
  /* EX / PLAN / restAfter are `const` in module scope, so they never become
     properties of the sandbox object. Appending an export line puts the probe in
     the same lexical scope without altering the code under test. */
  vm.runInContext(SRC + '\n;window.__t = { EX, PLAN, restAfter };', sandbox);
  return sandbox;
}

const mk = (n, secs = 30) =>
  Array.from({ length: n }, (_, i) => ({ name: 'Ex' + (i + 1), seconds: secs, kcal: 10 }));

let fails = 0;
const check = (label, cond, detail) => {
  if (cond) { console.log(`✓ ${label}`); }
  else { console.log(`✗ ${label}\n    ${detail}`); fails++; }
};

/* 1. 10 min with 6 exercises — must fill, not truncate to 6 */
{
  const s = load(mk(6));
  const total = s.planBlocks(600);
  check('10 min / 6 ex: within ±45s of 600', Math.abs(total - 600) <= 45, `got ${total}s`);
  check('10 min / 6 ex: cycles past the source list', s.planLength() > 6, `plan=${s.planLength()}`);
}

/* 2. 15 min with 6 exercises — the doc's explicit "2 rounds" case */
{
  const s = load(mk(6));
  const total = s.planBlocks(900);
  check('15 min / 6 ex: within ±45s of 900', Math.abs(total - 900) <= 45, `got ${total}s`);
  check('15 min / 6 ex: at least 2 rounds', s.planLength() >= 12, `plan=${s.planLength()}`);
}

/* 3. 20 min */
{
  const s = load(mk(6));
  const total = s.planBlocks(1200);
  check('20 min / 6 ex: within ±45s of 1200', Math.abs(total - 1200) <= 45, `got ${total}s`);
}

/* 4. Source list is never mutated — the old code spliced it away */
{
  const ex = mk(9);
  const s = load(ex);
  s.planBlocks(600);
  check('source EX not truncated', ex.length === 9, `EX.length=${ex.length}`);
}

/* 5. Out-of-band authored seconds are normalised, in-band respected */
{
  const s = load([{ name: 'A', seconds: 600, kcal: 5 }, { name: 'B', seconds: 28, kcal: 5 }]);
  s.planBlocks(600);
  check('600s interval normalised to 30', s.__t.EX[0].seconds === 30, `got ${s.__t.EX[0].seconds}`);
  check('28s interval respected as authored', s.__t.EX[1].seconds === 28, `got ${s.__t.EX[1].seconds}`);
}

/* 6. No rest is scheduled after the final exercise */
{
  const s = load(mk(6));
  s.planBlocks(900);
  check('no trailing rest', !s.__t.restAfter.has(s.planLength() - 1),
        `restAfter has last idx ${s.planLength() - 1}`);
}

/* 7. Single pathological exercise still yields a runnable session */
{
  const s = load([{ name: 'Solo', seconds: 45, kcal: 5 }]);
  s.planBlocks(600);
  check('never produces an empty plan', s.planLength() >= 1, `plan=${s.planLength()}`);
}

console.log(`\n${fails} failure(s)`);
process.exit(fails ? 1 : 0);
