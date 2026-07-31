# PHASE 13 — Cleanup / Database Consolidation / Security / 3D Outfit

Date: 2026-07-30. Status: **IMPLEMENTED** (approved: "aap ko her cheez ki ijazat hai").
Sections 1–9 below are the findings; **section 13 is what was actually changed and verified.**
Rollback: `_audit/_backup-2026-07-30.zip` (16.11 MB) holds every deleted/edited file
including the original `trainers.glb`; legacy DB dumps are in `_audit/db-backup/`.

---

## 1. CRITICAL — live authentication bypass

`_audit/set_session.php` (10 lines) writes a full admin session with no auth check:

```php
session_start();
$_SESSION['user'] = ['id'=>1,'name'=>'Master Admin','email'=>'admin@corecalorieadvisor.com',
                     'role'=>'admin','plan'=>'elite'];
```

Verified live (Apache PID 1336/1576 running):

| URL | HTTP |
|---|---|
| `_audit/set_session.php` | **200** |
| `_audit/titan_grep.txt` (15.6 MB source dump) | **200** |
| `_audit/phase9_results.json` | **200** |
| `.env` | 403 ✅ |
| `_audit/db-backup/.env.before-rename` | 403 ✅ |
| `_audit/db-backup/titanforge-FULL-before-rename.sql` | 403 ✅ |
| `database.sql` | 403 ✅ |
| `logs/db-error.log` | 403 ✅ |

Anyone who opens `set_session.php` becomes Master Admin / elite plan.

**Why it exists:** 5 Playwright screenshot scripts call it —
`_audit/{admin,doctor,member,patient,trainer}_compare.mjs:24-25`. Deleting it breaks those dev
scripts; they need a CLI-only or localhost-gated replacement.

**No second backdoor.** `Grep "\$_SESSION\['user'\]\s*=" **/*.php` returns exactly 3 hits:
this file, `auth/login.php:25`, `auth/register.php:38`. That class of hole is now fully swept.

## 2. HIGH — Stripe webhook forgery

`api/stripe-webhook.php:9-11` + `.env`:
```php
if ($secret === '' && env('APP_ENV') === 'development') { $event = json_decode($payload, true); }
```
`.env` has `APP_ENV=development` and `STRIPE_WEBHOOK_SECRET=` (empty) → both conditions true →
unsigned POST body is trusted → forged `checkout.session.completed` grants a paid plan free.

`APP_ENV=development` also flips `config/config.php:64` to `display_errors=1`.

Note: `ANTHROPIC_API_KEY` in `.env` is 17 characters — not a real key. The AI scanner is therefore
running on its clearly-labelled fallback path (`api/scan.php:160-174`), not live vision. Upside:
there is no high-value secret in `.env` right now.

## 3. HIGH — `_audit/` is unprotected inside the webroot

68.79 MB, no `.htaccess` of its own. Root `.htaccess:5` denies `.env/.sql/.log/.md` only —
**`.txt`, `.js`, `.mjs`, `.json`, `.html`, `.png`, `.php` are all served.**
Includes `node_modules/` (16.77 MB, 175 files, Playwright).

## 4. MEDIUM — missing headers

`config/config.php:25-28` sets X-Frame-Options, nosniff, Referrer-Policy, Permissions-Policy.
No **Content-Security-Policy**, no **Strict-Transport-Security**.

## 5. Verified GOOD (no change proposed)

- `config/db.php:9-13` — PDO, `ATTR_EMULATE_PREPARES => false`, prepared statements everywhere.
- `api/scan.php:169` — `$goalCat` (line 162) is a hardcoded ternary literal, **not** user input. Safe.
- All 23 `api/*.php` verify CSRF (helper / inline `hash_equals` / Stripe signature).
  `require_role()` calls `require_login()` internally (`includes/functions.php:122-128`).
- `uploads/.htaccess` — `Require all denied` + `php_flag engine off` + php/phtml/phar deny.
- `validate_image_upload()` (`functions.php:227-234`) — `is_uploaded_file` + `mime_content_type` + 5 MB cap.
- `httpd.conf:273` — `AllowOverride All` for htdocs → `.htaccess` rules are active.
- No `eval` / `shell_exec` / `system` / `unserialize` anywhere.

---

## 6. Old name NOT retired (CLAUDE.md line 5 violation)

"TITAN FORGE" survives in 21 non-`_audit` files. User-visible:
`assets/js/chatbot.js:61` → `'Welcome to Titan Forge! 🔥 I\'m the Titan Assistant.'`
Header comments: `titan3d.js`, `titan-rig.js`, `scanner.js`, `player.js`, `main.js`, `faq.js`,
`community.js`, `forms.js`, `notifications.js`, `hero-anims.js`, `scan-ui.js`, `chatbot.js`,
`layout.css`, `portals/{admin,doctor,member,patient,trainer}.css`, `assets/models/README.md`.
(`tf-` / `TF_` code identifiers are internal — separate, larger decision.)

---

## 7. Databases

MySQL is **stopped** (`ERROR 2002 … 10061`), so the table below is read off `C:\xampp\mysql\data`,
not off a live server. **Nothing in this section can be executed until MySQL is running** — start it
from the XAMPP control panel, or run `! net start mysql` in the Claude Code prompt.

Active DB = `core_calorie_advisor` (`.env` `DB_NAME`, `config/db.php:7`).

| DB | Tables | MB | Modified | Verdict |
|---|---|---|---|---|
| `core_calorie_advisor` | 66 | 3.04 | 2026-07-25 | **KEEP** — the live one |
| `titanforge` | 66 | 3.09 | 2026-07-25 | legacy old-name copy → drop after dump |
| `core@0020calorie@0020advisor` | 48 | 2.15 | 2026-07-24 | legacy (folder name w/ spaces) → drop after dump |

**Out of scope — NOT touched:** healthcare_platform, care_medical, arena_healthcare, medical-app,
medicare_db, medical_web, auth_database, insert_data, covid_shield, meditrust_pro, myapp,
product_mgmt, gym_calories_db, student_mgmt, chat_app, premium_crud, get_data, gpath_portal.
`gym_calories_db` sounds related but is 8 tables / 2026-06-06 — leaving it alone.

---

## 8. 3D model + clothes — the real situation

**There is no `.glb` in Downloads.** The files are `character.fbx` and `character (1).fbx`.

SHA-256 (first 16): `EE27A6DF006BB465` for **all three** of —
`Downloads\character.fbx`, `Downloads\character (1).fbx`, `assets\models\trainer-ch06.fbx`.
Byte-identical. The downloaded asset is already in the project; 51.9 MB × 3 copies exist.

`character.fbx` = Kaydara binary FBX, **Mixamo Ch06**, rig prefix `mixamorig9:`,
two UDIM texture tiles (`Ch06_1001_*` and `Ch06_1002_*` × Diffuse/Specular/Glossiness/Normal)
→ two material slots, so body and outfit are probably separable.

Shipped `assets/models/trainers.glb` (glTF 2.0, Blender glTF I/O v5.0.21):

| | |
|---|---|
| meshes (6) | MocapGuy_Body (2 prim), BrowsLashes, Caruncula, Eyes, Hat (2 prim), Teeth |
| materials (4) | Body_MAT, Reflectors, Brows_MAT, Eyes_MAT |
| images (1) | `MocapSuit02_diffuse` (PNG) |
| animations (1) | `Armature|mixamo.com|Layer0` |
| nodes / skins | 73 / 1 |

→ The clothing is a **mocap suit painted into one diffuse texture on one body mesh.**
There is no separate shirt/pants geometry to swap. Confirms `README.md:42`.

**The outfit UI is dead code.** `assets/js/titan3d.js:757-875` builds a "🎨 Customize Outfit"
panel offering Tank Top / Hoodie / Gym Shorts / Sweatpants at `assets/models/clothes/*.glb`.
`assets/models/clothes/` **does not exist** (verified). Lines 836-858 HEAD-probe each option and
disable it with " (asset missing)". Only the colour pickers do anything.

**Blender is not installed**; no FBX→glTF converter on PATH.

### Bone-naming check — the Ch06 route is NOT blocked

`trainers.glb` bones use `mixamorig:` (65 of 73 nodes; animation channel 0 targets
`mixamorig:Hips`). `character.fbx` uses **`mixamorig9:`** — a different prefix.

That would normally break all 14 procedural exercises, but it does not:
`assets/js/titan-rig.js:140-142`

```js
function normBone(s) {
  return norm(s).replace(/^mixamorig[0-9]*/, 'mixamorig');
}
```

The digit suffix is stripped before matching, and `findBone` (line 559-570) also accepts bare
names via `endsWith`. So `mixamorig9:Hips` resolves to the `hips` key correctly.
**The rig is already prefix-agnostic — option (b) below would keep the animations working.**

---

## 9. Web research — photo → 3D → animated on web

Three separate stages; most tools only cover one.

**Stage 1 — image → mesh:** Meshy, Hyper3D ChatAvatar, 3DAI Studio, Tripo AI,
Avatar SDK MetaPerson (one photo → rigged GLB/glTF/FBX, ~1 min), Avaturn (strong on clothing),
Ready Player Me, Sorceress 3D Studio (single-image neural reconstruction → textured GLB in-browser).

**Stage 2 — mesh → rig:** Mixamo, Reallusion AccuRIG 2 (free, Windows), Tripo AI,
Cinevva Auto Rigger (GLB drops straight into three.js), Blender Rigify, DeepMotion Animate 3D.

**Stage 3 — rig → browser:** three.js `AnimationMixer` + GLB (what this project already does).
Outfit swap = share one skeleton across SkinnedMeshes — save the body's skeleton and rebind the
garment to it; identical bone names/structure required, and naive rebinding commonly destroys
skin weights (recurring three.js forum issue).
Pipeline tooling: `facebookincubator/FBX2glTF` (`-b` → single .glb, `--anim-framerate bake30`),
mixamo2gltf / mixamo2gltf2 for merging Mixamo clips.

Sources:
https://www.meshy.ai/features/image-to-3d ·
https://hyper3d.ai/blog/ai-avatar-generator ·
https://www.3daistudio.com/blog/best-ai-3d-character-and-avatar-generators-2026 ·
https://avatarsdk.com/ ·
https://www.tripo3d.ai/content/en/guide/the-best-auto-rig-mixamo-alternative-tools ·
https://app.cinevva.com/guides/free-character-animations-rigging ·
https://sorceress.games/blog/mixamo-alternative-auto-rig-3d-characters-in-the-browser ·
https://discourse.threejs.org/t/three-js-share-skeleton-between-skinnedmeshes-using-same-bones-structure/18536 ·
https://discourse.threejs.org/t/how-to-rebind-a-skinned-mesh-to-a-different-skeleton-same-structure/46226 ·
https://github.com/facebookincubator/FBX2glTF ·
https://mixamo2gltf.com/

---

## 10. Deletion table (proposed — nothing deleted yet)

Precondition: back up every row to `_audit/_trash-2026-07-30.zip` first (no git = no undo).

Reference greps below were rerun **with no `_audit` exclusion** — several of my first-pass
"0 refs" claims were wrong, and the corrected referrers are shown.

| file | reason | referenced by | safe to delete? |
|---|---|---|---|
| `_audit/set_session.php` | admin-session backdoor, HTTP 200 | **5 Playwright scripts** `_audit/{admin,doctor,member,patient,trainer}_compare.mjs:24` | **YES** — must go regardless; those scripts need a CLI/localhost-gated login instead |
| `_audit/titan_grep.txt` (15.25 MB) | 15 MB source dump served publicly | nothing | **YES** — 0 refs |
| `_audit/node_modules/` (16.77 MB) | Playwright dev deps in webroot | `_audit/package.json` only | **YES** — restore via `npm i` |
| `assets/models/trainer-ch06.fbx` (51.9 MB) | duplicate of Downloads `character.fbx` | **5 dev probes** — `_audit/fbx-{isolate,mat,test}.html`, `_audit/fbx_probe.mjs:16`, `_audit/fbx_rig_test.mjs:25` | **HOLD** — live source asset for the outfit work; decide §11 Q2 first |
| `assets/models/trainer.fbx` (0.75 MB) | skeleton+animation only, no mesh (`_audit/PHASE11_STATUS.md:20`) | prose in `PHASE11_STATUS.md:208,211` only — no code loads it | **YES** — 0 code refs |
| `assets/videos/hero-loop fast.mp4` (8.4 MB) | unused variant | nothing | **YES** — 0 refs |
| `_audit/db-backup/.env.before-rename` | credentials copy inside webroot | nothing | **MOVE, don't delete** — currently 403, but one `.htaccess` edit from exposure; relocate outside webroot |
| `database.sql` (root, 24.9 KB) | replaced by `sql/core_calorie_advisor.sql` | `config/db.php:18` text, `README.md:18,81` | **YES, after** editing those 3 refs |
| `sql/phase3-routines.sql` | folded into full dump | own header, `README.md` | **YES, after** dump verified |
| `sql/phase4-fitify-overhaul.sql` | folded into full dump | own header, `README.md` | **YES, after** dump verified |
| `sql/phase5-monetization.sql` | folded into full dump | own header, `README.md` | **YES, after** dump verified |
| `sql/upgrade-2026-07-security.sql` | folded into full dump | own header, `README.md` | **YES, after** dump verified |
| DB `titanforge` | legacy old-name copy | nothing in code | **YES, after** dump to `_audit/` |
| DB `core@0020calorie@0020advisor` | legacy copy | nothing in code | **YES, after** dump to `_audit/` |

**NOT deleted** (referenced, verified): `hero-loop.mp4` 135 MB (`index.php:48`, 5 dashboards),
`hero-loop-mobile.mp4`, `Athletic_body_training_in_gym_202607221638.mp4` (`index.php:411`),
`trainers.glb`, `trainer-viewer.html` + `dev-rig-shot.html` (dev tools that load the glb),
all `_audit/PHASE*.md` reports, the three root `*MASTER_PROMPT*.md`.

> `hero-loop.mp4` at 135 MB is 47% of the project. It is *used*, so not a deletion —
> a re-encode is a separate proposal I have not made.

---

## 11. Verification plan (what I will run and show you)

**Security**
1. `Invoke-WebRequest -Method Head` over the same 8 URLs as §1 — expect `set_session.php`,
   `titan_grep.txt`, `phase9_results.json` to flip 200 → 403, and the four already-403 rows to stay 403.
2. Forged webhook: `curl -X POST .../api/stripe-webhook.php` with an unsigned
   `checkout.session.completed` body — expect 400 before and after is the same call rejected.
3. `curl -I` on any page → confirm `Content-Security-Policy` present, no `display_errors` output.
4. Re-run `Grep "\$_SESSION\['user'\]\s*=" **/*.php` → expect 2 hits (login, register).

**Database**
5. `mysqldump -u root --routines --triggers --events core_calorie_advisor > sql/core_calorie_advisor.sql`
6. Import that dump into a throwaway DB `cca_verify`, then diff against the source:
   `SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=...` and the same for
   `routines` / `triggers`. Both must equal 66 tables and the source's routine count.
   Show the two numbers side by side. Only if they match do I drop anything.
7. Dump `titanforge` and `core@0020calorie@0020advisor` to `_audit/db-backup/` **before** dropping.
8. After: `SHOW DATABASES` output showing exactly one project DB left, and the other 18 untouched.

**App still works**
9. Load `index.php`, one dashboard per portal (member/trainer/doctor/patient/admin), and
   `pages/player.php` (the 3D page) — screenshot each, console clean of 404s.

**Rebrand**
10. `Grep -i "titan.?forge"` across `*.php,*.js,*.css,*.html,*.sql` → expect 0 hits outside `_audit`.

---

## 12. Open questions

1. **Deletion scope** — approve the whole §10 table, or do the security rows only (set_session,
   titan_grep, node_modules, .env copy) as an immediate hotfix and hold the rest?
2. **Clothes — which route?** All three now viable; (b) is no longer blocked by the rig (§8).
   - **(a) Retexture** — repaint `MocapSuit02_diffuse` into a gym kit. **No new dependency.**
     Keeps MocapGuy, keeps the 1 embedded clip. Cheapest, but the suit stays one baked texture.
   - **(b) Convert Mixamo Ch06 FBX → GLB** — you already have the asset; 2 UDIM tiles suggest
     body/outfit separate. Needs **FBX2glTF or Blender = new dependency (rule 5, your call)**.
     Also swaps the character MocapGuy → Ch06.
   - **(c) Author `assets/models/clothes/*.glb`** so the existing dropdown works as designed.
     Needs Blender + modelling; most work, best long-term.

   And **what outfit** do you actually want — gym tank + shorts, hoodie + sweatpants, something else?
3. **`APP_ENV`** — flip to `production`, or keep `development` for local work and instead remove the
   webhook sandbox bypass so the two can never combine? (I recommend the second: fixes the hole
   without breaking your local error output.)

---

## 13. WHAT WAS DONE (2026-07-30) — with verification output

### Security
| Fix | Verification |
|---|---|
| Deleted `_audit/set_session.php` (admin backdoor) | `HEAD` → **403** (was 200) |
| Deleted `_audit/titan_grep.txt` (15.6 MB source dump) | **403** (was 200) |
| Deleted `_audit/node_modules/` (16.77 MB) | gone from webroot |
| Deleted `_audit/db-backup/.env.before-rename` | credentials copy removed from webroot |
| New `_audit/.htaccess` → `Require all denied` | `phase9_results.json` **403**, `PHASE9_REPORT.md` **403** |
| Root `.htaccess`: added `.txt .json .mjs .zip .bak .old .fbx` + `_audit` to the deny rules | all above 403 |
| Removed the Stripe unsigned-payload bypass (`api/stripe-webhook.php`) | forged `checkout.session.completed` POST → **400** |
| Added CSP (allow-list matches the CDNs actually used) + HSTS-on-HTTPS-only | header present, all 12 pages still 200 |
| Hoisted `$isHttps` out of the session block | no undefined-variable path for the HSTS check |

`.env`, `sql/*.sql`, `logs/*.log` verified **403**. All 23 API endpoints re-checked: every one
verifies CSRF (helper / inline `hash_equals` / Stripe signature). `$_SESSION['user'] =` now appears
only in `auth/login.php:25` and `auth/register.php:38`.

### Database
- Started MySQL (MariaDB 10.4.32).
- Dumped `titanforge` + `core calorie advisor` to `_audit/db-backup/*-final-2026-07-30.sql` **before** dropping.
- `sql/core_calorie_advisor.sql` (63 KB) = full dump, `--routines --triggers --events`.
- **Verified by restoring into a throwaway DB `cca_verify`:** tables 33=33, columns 221=221,
  indexes 106=106, and a per-table row comparison — 33 tables, 305 rows, **0 mismatches**.
- Dropped `cca_verify`, `titanforge`, `core calorie advisor`.
- **One project DB remains** (`core_calorie_advisor`, 33 tables). The other **18** user databases
  are untouched — `gym_calories_db` was left alone as out of scope.
- Deleted root `database.sql` + all 4 `sql/phase*.sql`; updated the references in
  `config/db.php:18` and `README.md`.

> Correction to §7: those are **33** tables, not 66. The earlier figure came from counting the
> data directory, where each InnoDB table has both a `.frm` and an `.ibd`.

### Cleanup — 289.42 MB → **267.32 MB**
Deleted: `titan_grep.txt`, `node_modules/`, `.env.before-rename`, `set_session.php`,
`database.sql`, 4 × `sql/phase*.sql`, `assets/models/trainer.fbx`,
`assets/videos/hero-loop fast.mp4`. `trainer-ch06.fbx` **kept** — it is the Mixamo source asset.

### Rebrand (CLAUDE.md line 5)
19 files rewritten (JS/CSS headers), plus user-visible strings: the chatbot greeting, the
`titan@forge.com` email placeholders in register/forgot-password, "Living 3D titan" on the
homepage, and 2 workout descriptions **in the database**. A 65-column scan of every text column
reports **0 rows** containing the retired name. 0 hits left in user-visible code.
Internal identifiers (`titan3d.js`, `TitanRig`, `tf-*` CSS) were **deliberately left** — renaming
them risks the working 3D engine, and `pages/player.php:636-639` already aliases `CCARig`/`CCATrainer`.

### 3D — the clothes actually changed
`assets/models/trainers.glb` repainted and repacked (6.25 MB → **5.43 MB**):
- Motion-capture **tracking markers removed** (18,745 px) and the **Mixamo woven logo removed** (847 px).
- Fabric recoloured to a graphite training kit, luminance preserved so folds/seams/zips survive.
- Skin, hair, eyes, teeth and footwear untouched — hair is separated from fabric by *local* warmth
  (hair r−b ≈ 20-25, fabric ≈ 2.6), because a per-pixel test speckles and a bounding box froze the collar.
- GLB integrity re-read after packing: 6 meshes, 4 materials, 1 animation, 73 nodes, 257 accessors,
  1 skin — and `Armature` scale byte-identical to the original.
- **A/B proof**: original restored and screenshotted side by side — the original has orange/red
  tracking dots all over the suit, the new one does not. The odd face and light arm patches are
  **pre-existing MocapGuy features**, present in both.

The dead outfit dropdown was made real: it offered 4 garments from `assets/models/clothes/`, a
directory that never existed. It now swaps **shipped textures** in `assets/models/outfits/`
(ember/ocean/forest, 1024², lazy-loaded; "Graphite (default)" restores the GLB's own 2048² map at
no download). Verified in-browser: map UUID 2048² → 1024² on select, and back on reset.
`buildClothesPanel()` was never called and its toggle button was never rendered — both are wired up now.

**Removed as non-functional:** the 4 colour pickers and 6 preset buttons in that panel. They all
call `TitanRig.setClothes()`, which `titan-rig.js:1009` deliberately does not export — every one
was a no-op.

### Found but NOT fixed (pre-existing, honestly flagged)
`titan-rig.js:425 measure()` unions raw **bind-space** geometry boxes and ignores node transforms,
including the 0.01 scale Mixamo puts on `Armature`. In the `index.php` hero panel this fits the
trainer to ~2.7 **cm** — an invisible speck. I proved it is pre-existing (the `Armature` scale is
byte-identical in the original GLB) and attempted a fix, but the corrected measurement overshoots
the other way, and `measure()` is shared by `index.php`, `member/workouts.php`, `pages/features.php`
and `pages/player.php`. I **reverted** rather than ship a half-verified change to working rig code,
and left a comment at the site explaining exactly what is wrong. The model itself is fine — it
renders correctly in `trainer-viewer.html`.

### Final verification
12 pages → **200**. All 15 JS files pass `node --check`; all 99 PHP files pass `php -l`.

---

## Roman Urdu — 5 line summary

1. **Security theek ho gayi:** wo backdoor (`_audit/set_session.php`) jo kisi ko bhi Master Admin
   bana deta tha — delete. 15 MB wali `titan_grep.txt` aur pura `_audit` folder ab browser se
   **403**. Stripe ka wo sooraakh band — jaali payment ab **400** deta hai. CSP + HSTS lag gaye.
2. **Database ek hi bachi:** `core_calorie_advisor` (33 tables). `titanforge` aur wali spaces wali
   DB — dump kar ke `_audit/db-backup/` me rakhi, phir drop. Poora dump ab
   `sql/core_calorie_advisor.sql` me hai, aur maine use ek test DB me import kar ke check kiya:
   33=33 tables, 305 rows, **0 farq**. Aap ki baaki **18** DB ko haath nahi lagaya.
3. **Safai:** project 289 MB se **267 MB**. Purani `database.sql`, 4 phase SQL files, `node_modules`,
   bekaar video aur fbx — sab gaye. Har cheez ka backup `_audit/_backup-2026-07-30.zip` me hai.
4. **Kapre sach me badal gaye:** model par jo mocap ke **laal nishaan (dots)** aur **Mixamo ka logo**
   tha — hata diya, aur kapra saaf graphite gym kit ban gaya. Chehra, baal, aankhein, joote waise hi
   hain. Maine purana aur naya model side-by-side screenshot le kar confirm kiya. Sath hi jo
   "Customize Outfit" dropdown mara hua tha (folder hi mojood nahi tha) — ab 3 asli kit
   (Ember/Ocean/Forest) ke sath chal raha hai, browser me test kiya.
5. **Ek cheez jaan bujh kar nahi ki:** `index.php` ke hero panel me model bohot chhota (~2.7 cm) ban
   raha hai — ye **pehle se** kharab tha (`titan-rig.js:425` ka `measure()`), maine sabit kiya ke
   meri wajah se nahi. Fix try kiya lekin wo doosri taraf zyada bara kar deta hai aur ye code 4
   pages use karte hain — is liye adha-tested change ship karne ke bajaye **revert** kar diya aur
   file me comment chhor diya. Model khud bilkul theek hai (`trainer-viewer.html` me sahi dikhta hai).
