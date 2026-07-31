/* PHASE D regression guard — assert no character GLB ships a translucent material.
   Run:  node _audit/opaque-check.mjs
   Exit: 0 = all opaque, 1 = a material declares alphaMode other than OPAQUE.

   Parses the glTF-Binary container directly (JSON chunk only, no deps) and reads
   materials[].alphaMode. Per spec, a material with no alphaMode is OPAQUE, so the
   check is: any explicit MASK/BLEND is a failure. Also flags baseColorFactor
   alpha < 1, which produces translucency even when alphaMode is OPAQUE-by-default
   in some exporters. */
import { readFileSync, readdirSync } from 'node:fs';
import { join, dirname, basename } from 'node:path';
import { fileURLToPath } from 'node:url';

const MODELS = join(dirname(fileURLToPath(import.meta.url)), '..', 'assets', 'models');

function readGlbJson(path) {
  const buf = readFileSync(path);
  if (buf.readUInt32LE(0) !== 0x46546c67) throw new Error('not a GLB (bad magic)');
  let off = 12;
  while (off < buf.length) {
    const len = buf.readUInt32LE(off);
    const type = buf.readUInt32LE(off + 4);
    if (type === 0x4e4f534a) return JSON.parse(buf.slice(off + 8, off + 8 + len).toString('utf8'));
    off += 12 + len - 4;
  }
  throw new Error('no JSON chunk');
}

const files = readdirSync(MODELS).filter(f => f.toLowerCase().endsWith('.glb')).sort();
if (!files.length) { console.error('no .glb found in', MODELS); process.exit(1); }

let failures = 0, checked = 0;
for (const f of files) {
  const p = join(MODELS, f);
  let gltf;
  try { gltf = readGlbJson(p); }
  catch (e) { console.log(`✗ ${f}: ${e.message}`); failures++; continue; }

  const mats = gltf.materials || [];
  const bad = [];
  mats.forEach((m, i) => {
    const name = m.name || `#${i}`;
    if (m.alphaMode && m.alphaMode !== 'OPAQUE') bad.push(`${name} alphaMode=${m.alphaMode}`);
    const a = m.pbrMetallicRoughness?.baseColorFactor?.[3];
    if (typeof a === 'number' && a < 1) bad.push(`${name} baseColorFactor.a=${a}`);
  });

  checked += mats.length;
  if (bad.length) { failures += bad.length; console.log(`✗ ${basename(p)} — ${bad.length} translucent:`); bad.forEach(b => console.log(`    ${b}`)); }
  else console.log(`✓ ${basename(p)} — ${mats.length} materials, all opaque`);
}

console.log(`\n${files.length} files, ${checked} materials checked, ${failures} failures`);
process.exit(failures ? 1 : 0);
