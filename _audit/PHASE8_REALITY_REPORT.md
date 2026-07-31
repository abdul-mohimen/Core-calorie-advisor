# PHASE 8 — REALITY AUDIT REPORT

**Scope:** discovery only. Per the Phase 8 brief, no application code was modified in this phase.
**Files modified in this phase (only the two Phase 8 item 6 authorises):** `README.md`, `_audit/FINAL_REPORT.md`.
**Date of audit:** 2026-07-25
**Repo audited:** `C:\xampp\htdocs\Core calorie advisor` (live working tree, not the original zip)

**Measurement scope for every count below:** all `*.php` / `*.css` / `*.js` / `*.html` files in the repo,
**excluding** `_audit/` and `node_modules/`. Where the v2 document's number differs from mine, the
reconciliation is stated explicitly — a different number is only a "correction" if it is the same
measurement.

---

## What I found

### 1. Confirm / correct each of the 9 Ground-truth rows

| # | v2 doc claim | Verdict | Evidence (current repo) |
|---|---|---|---|
| 1 | Tailwind CDN at `includes/header.php:191` fights the token system | **CONFIRMED — and worse than stated** | The CDN tag is in **5 files**, not 1: `includes/header.php:191`, `pages/nutrition.php:16`, `pages/player.php:26`, `pages/pricing.php:45`, `pages/trainers.php:11`. Four pages load Tailwind a **second time** on top of the header's copy, each with its own `tailwind.config` block. |
| 2 | 525 hardcoded hex, 536 inline styles | **CONFIRMED** (525→**520**, 536→**596**) | Hex: **520** occurrences / **51 distinct values** in `.php` (PHP `#` comments and `href="#anchor"` excluded — see §2). Inline: **596**. The 536 figure was the same measurement with 3 directories omitted; see the reconciliation table in §2. |
| 3 | `.cca-card` exists (~10 variants, 69 usages) but is undermined | **CONFIRMED — exactly 69** | 7 distinct selectors in `style.css` (`.cca-card`, `-header`, `-title`, `-value`, `-icon`, `-link`, `-footer`); **69** usage lines across 29 files. Note: **`pages/` uses cards in only 2 of 24 files** (`about.php` 8, `features.php` 6). Every other public page — pricing, workouts, shop, trainers, nutrition — has **zero** `.cca-card`. |
| 4 | Two competing logos; header uses an ad-hoc `$BRAND_SVG` | **CONFIRMED** | `includes/header.php:98` defines `$BRAND_SVG` as an inline heredoc. It is what actually renders at `header.php:227`, `header.php:236`, `includes/footer.php:8`, `pages/receipt.php:71`, `pages/billing-success.php:61`, `auth/login.php:44`, `auth/register.php:55`. The 6 approved files in `assets/brand/` are referenced by **zero** PHP files. |
| 5 | 3D trainer has **no** clothing/outfit system at all | **CORRECTED — but the substance is worse** | A clothing system *does* exist (`titan3d.js:657` `TF_ModularClothing`, `titan3d.js:747` `buildClothesPanel`). **Both are non-functional, and one does the exact thing Phase 11 forbids.** See §3. |
| 6 | No fast-preview / speed-ramp; `setEffectiveTimeScale(1)` hardcoded | **CONFIRMED** | `assets/js/titan3d.js:543` — the only `setEffectiveTimeScale` call in the repo, hardcoded to `1`. No preview stage exists in `WORKOUT_STATES` (`titan3d.js:990`). |
| 7 | Intro never included on login/register | **CORRECTED — it already fires there** | See §4. The v2 doc's grep was on the auth files; the include is one level up in `header.php:220`, which both auth pages include. |
| 8 | Security only "9 occurrences" — unproven | **CORRECTED — coverage is materially better than claimed** | All 20 state-changing endpoints verify CSRF; 12 via the `csrf_verify_json()` helper, 8 via an inline `hash_equals()` equivalent. Real gaps are narrower and named in §5. |
| 9 | No TODO / lorem-ipsum / "coming soon" placeholders | **CONFIRMED** | Grep for `lorem ipsum\|TODO\|FIXME\|coming soon\|placeholder image\|dummy data\|via.placeholder\|placehold.co` over all `.php`/`.js`/`.css`: **zero hits in application code.** The only matches are inside the prompt documents themselves (`CLAUDE.md:18`, `MASTER_PROMPT_for_Antigravity.md:12,16`, `CORE_CALORIE_ADVISOR_MASTER_PROMPT.md:28,145`). This row holds up — do not "fix" anything here. |

---

### ROW 10 — findings the Ground-truth table does not cover

These were not in the v2 table. **The first one is, in my assessment, the actual root cause of the
unreadable-text complaint — more so than the Tailwind conflict in row 1.**

#### 10a. 575 references to CSS variables that are **never defined anywhere** ⚠️ PRIMARY ROOT CAUSE

> **The one number to quote:** **575 `var()` references across 27 undefined names; 13 core design
> tokens account for 534 of them.** (A first pass found 583 refs / 32 names; a second pass proved 5 of
> those names *are* defined — see the exclusion note below — so 8 references were subtracted.)

The Phase 4 rewrite installed a new token vocabulary at `style.css:2` (`--text-1/2/3`, `--bg`,
`--surface`, `--border`, …) but **never migrated the old vocabulary's references**. The old token
names are still used 575 times and are declared in **no** file — not in any CSS, not inline, not via
JS `setProperty`.

| Uses | Token | Definition sites found | Has fallback |
|---:|---|---:|---:|
| 101 | `--muted` | **0** | 1 |
| 86 | `--tech` | **0** | 3 |
| 83 | `--line` | **0** | 0 |
| 49 | `--molten` | **0** | 0 |
| 39 | `--text` | **0** | 0 |
| 38 | `--ember` | **0** | 0 |
| 36 | `--line2` | **0** | 0 |
| 21 | `--heat` | **0** | 0 |
| 20 | `--disp` | **0** | 1 |
| 18 | `--glass` | **0** | 0 |
| 18 | `--faint` | **0** | 0 |
| 15 | `--gold` | **0** | 1 |
| 10 | `--shadow` | **0** | 0 |
| 8 | `--heat-soft` | **0** | 0 |
| 7 | `--body` | **0** | 2 |
| 5 | `--fire` | **0** | 0 |
| 4 | `--steel` / `--card` / `--amber` | **0** | 0 |
| … | 9 more single-use names | **0** | — |

**Total: 575 references across 27 undefined names, of which only 10 supply a `var(--x, fallback)`**
(`--tech` 3, `--body` 2, and 1 each for `--muted`, `--disp`, `--gold`, `--card-min`, `--hero-img`).
**The 13 core tokens in the table above account for 534 of the 575.**

**Excluded after a second pass** — these 5 names *are* genuinely defined, so they are **not** part of
the problem and were subtracted (8 references): `--cca-primary` (`intro.php:7`), `--tx`/`--ty`
(`intro.php:220-221`), `--sx`/`--sy` (`player.js:190-191`).

**Why this makes text unreadable:** a `var()` referencing an undefined property with no fallback is
*invalid at computed-value time*. For an inherited property like `color`, the declaration is
discarded and the element **inherits its parent's color instead**. So `color: var(--muted)` on a dark
card inside a light-theme page inherits near-black text; `border: 1px solid var(--line)` silently
loses its border. This fails **identically in both themes**, which is why the theme toggle never
fixed it — and why it is invisible to a Tailwind-only diagnosis.

`--tech` and `--disp` are **font-family** tokens, so 106 elements are also falling back to the
default serif/inherited face rather than Rajdhani/Russo One.

#### 10b. `style.css` declares `:root` **twice**

`style.css:2` (the 40-token Phase 4 block) and `style.css:3459` (a second block declaring only
`--kb-page`). Two `:root` blocks 3,457 lines apart make the token system very easy to mis-edit; this
is the seam where the two design systems were stitched together.

#### 10c. Light theme deliberately forces near-white text — with a stale old-name comment

`style.css:3476-3481`: `[data-theme="light"] .hero h1 { color: #f7f3ec; }` plus 5 sibling rules.
The comment at `style.css:3473-3475` explains this is intentional (the hero photo is dark in both
themes) — **but it cites the old product name**: *"the headline vanish into the image (**"FORGE YOUR
ULTIMATE"** in near-black on a black gym photo)"*. These 6 rules are hand-tuned exceptions of exactly
the kind Phase 9 item 4 forbids ("fix by adjusting the token, not by special-casing one page").

#### 10d. Old product name still shipping in the UI — direct `CLAUDE.md` violation

`CLAUDE.md` line 5: *"Old name(s) must be fully retired from code, UI, DB, docs, meta tags, and emails."*

- `auth/login.php:44` — `<span>CCA<em>FORGE</em></span>` — **renders on the live login page**
- `auth/register.php:55` — same string, **renders on the live register page**
- `assets/js/titan-rig.js` — `// Default: TitanForge Orange`
- `database.sql` — 4 occurrences (**seed data**, explicitly named in `CLAUDE.md`)
- `style.css:3475` — inside the comment quoted in 10c
- Plus 30 further files containing "forge" as prose copy ("Forge me fat melt karo", `database.sql:337`)

**616 total occurrences across 36 files** (excluding `_audit/`). Not all are the wordmark — many are
legitimate English/Roman-Urdu prose — but the two auth-page wordmarks and the DB seed rows are the
literal old brand name on screen.

#### 10e. Every animation `.glb` is a byte-identical copy of the same file

```
62383BEA9687E5C5CED895F642A40427  assets/models/anims/idle.glb
62383BEA9687E5C5CED895F642A40427  assets/models/anims/squat.glb
62383BEA9687E5C5CED895F642A40427  assets/models/anims/trainers.glb
62383BEA9687E5C5CED895F642A40427  assets/models/anims/warmup.glb
62383BEA9687E5C5CED895F642A40427  assets/models/trainers.glb
```
Five identical 6.25 MB files (MD5 above), **31 MB of duplicated payload**. `idle.glb`, `squat.glb`
and `warmup.glb` are not animations — they are copies of the character model.

This is currently harmless only because `titan3d.js:50` reads `const TRAINER_ANIMS = [];
// Temporarily disabled external animations`, so `bindExtraAnims()` (`titan3d.js:514`) iterates an
empty array and is **dead code**. If anyone re-enables it, it will load 3 copies of the same clip.

#### 10f. Undeclared external dependencies (CDN / remote assets)

`CLAUDE.md` rule 5 forbids new dependencies "including CDN links" without asking. Currently live:

| Dependency | Location |
|---|---|
| `cdn.tailwindcss.com` | `header.php:191` + 4 page files (see row 1) |
| `www.gstatic.com/draco/...1.5.7/` DRACO decoder | `titan3d.js:667` |
| `images.unsplash.com` (hero/page background) | `style.css:3460`, `pages/player.php:131` |
| `images.unsplash.com` (9 workout images) | `database.sql:332-340` — **seed data points at a third-party CDN** |

#### 10g. Stale path strings in user-facing error copy

`titan3d.js:55` tells the user to *"Add a rigged `assets/models/trainer.glb`"*. The real file is
`trainers.glb` (plural) — `titan3d.js:26`. `_audit/FINAL_REPORT.md:8` also credits a file
`assets/js/3d-scene.js` that **does not exist** in this repo.

---

## §2 — Row 2 expanded: full styling inventory

### 2a. Reconciliation with the v2 document's numbers

The v2 doc's per-directory inline-style figures match mine **exactly**. Its total was low only because
three directories were left out of the sum:

| Directory | v2 doc | Measured now | Δ |
|---|---:|---:|---|
| `pages/` | 267 | **267** | — |
| `member/` | 91 | **91** | — |
| `trainer/` | 53 | **53** | — |
| `admin/` | 42 | **42** | — |
| `patient/` | 42 | **42** | — |
| `doctor/` | 37 | **37** | — |
| `includes/` | 4 | **5** | +1 |
| `auth/` | *not counted* | **41** | +41 |
| repo root | *not counted* | **16** | +16 |
| `config/` | *not counted* | **2** | +2 |
| **TOTAL** | **536** | **596** | **+60** |

So 596 is not a regression — it is the same measurement, completed. Hex: **520** vs the doc's 525;
within noise of comment/anchor filtering, and I treat row 2 as **confirmed**.

### 2b. The 596 inline styles, classified for Phase 9

Phase 9 item 6 explicitly permits genuinely dynamic inline values to remain. Splitting on that basis:

| Class | Count | Phase 9 disposition |
|---|---:|---|
| **A1** — contains a raw colour, gradient, or typography property | **284** | **Hard fail.** Must become a token-backed utility class. |
| **A2** — inline, but already references only `var(--token)` | **49** | Soft. Correct colour source, wrong delivery mechanism → fold into a class. |
| **B** — PHP-computed dynamic value, no colour/typography | **5** | **Legitimate — keep inline.** All 5 listed below. |
| **C** — static layout/spacing only (`margin`, `display:flex`, `gap`) | **258** | Should become utility classes, but is not a theming bug. |

The 5 legitimate dynamic values (Phase 9 item 6's carve-out):
```
index.php:290              background-image:url('<?= $cat['img'] ?>')
pages/player.php:131       background-image: url('<?= e($w['image'] ?? '…') ?>')
pages/workout-detail.php:109  background-image: url('<?= e($wImg) ?>')
pages/workouts.php:233     background-image: url('<?= e($heroData['img']) ?>')
pages/workouts.php:236     color: <?= $heroData['accent'] ?>
```
`pages/workouts.php:236` is dynamic **and** a colour — it needs a token-keyed lookup, not a raw
value, but it cannot become a static class. Flagging it for a Phase 9 decision.

### 2c. Hex colours by directory

| Directory | Hex occurrences |
|---|---:|
| `pages/` | 340 |
| `includes/` | 65 |
| repo root (`index.php`) | 35 |
| `auth/` | 27 |
| `member/` | 16 |
| `patient/` | 11 |
| `trainer/` | 11 |
| `doctor/` | 8 |
| `admin/` | 7 |
| `api/`, `config/`, `portals/` | 0 |
| **TOTAL** | **520** |

### 2d. Classification (a)/(b)/(c) — done per **distinct value**, not per occurrence

Phase 8 item 2 asks for an (a)/(b)/(c) classification. Per-occurrence prose across 520 hex + 596
inline items is not a usable deliverable, so I classified the **51 distinct hex values** instead —
which answers (b) vs (c) exactly, and (a) then falls out per call site. **Stating the scoping choice
explicitly so it can be rejected if you want it done differently.**

**(b) Duplicates an existing token — replace with the token. 9 values, 340 of the 520 occurrences (65%):**

| Hex | Uses | Files | Existing token |
|---|---:|---:|---|
| `#FF6B1A` | 140 | 16 | `--primary` (`style.css:4`) |
| `#FFFFFF` / `#FFF` | 73 | 12 | `--text-1` (dark) / `--surface` (light) |
| `#0F1115` | 1 | 1 | `--bg` (`style.css:7`) |
| `#10B981` | 24 | 12 | `--success` (`style.css:21`) |
| `#F59E0B` | 24 | 12 | `--warning` (`style.css:22`) |
| `#EF4444` | 8 | 7 | `--danger` (`style.css:23`) |
| `#3B82F6` | 15 | 10 | `--info` (`style.css:24`) |
| `#6B7280` | 2 | 1 | `--text-3` (`style.css:18`) |
| `#E85D00` | — | — | `--primary-hover` (`style.css:5`) |

**(c) Genuinely new values — a token is missing. FLAGGED FOR YOUR DECISION, none invented:**

| Hex | Uses | Files | What it appears to be |
|---|---:|---:|---|
| `#0A0A0A` | 44 | 8 | A **second** near-black page background, competing with `--bg` `#0F1115`. Also hardcoded in `header.php:216` (`dark:bg-[#0A0A0A]`). **Two different "black"s ship side by side.** |
| `#121212` | 29 | 12 | A **third** dark surface, competing with `--bg2` `#16181D`. |
| `#38BDF8` | 26 | 10 | Sky-blue accent — declared as Tailwind `brand-accent` at `header.php:199` but absent from the token system. |
| `#FF3D00` | 17 | 10 | Secondary hot-orange (the old `--ember`/`--heat` family). |
| `#7DD3FC` | 11 | 4 | Light sky — pairs with `#38BDF8`. |
| `#FFC02E`, `#FFB800`, `#FBBF24`, `#FFD23E`, `#FFE08A`, `#D4AF37` | 21 | — | **Six** different golds/ambers with no single source. |
| `#FF8833`, `#FF3300`, `#FF9F1A`, `#FF8A00`, `#FF543E` | 16 | — | **Five** further orange variants beyond `--primary`. |
| `#2ECC71`, `#30C85E` | 10 | — | Two extra greens competing with `--success`. |
| `#8B5CF6`, `#A855F7` | 7 | — | Purple accent, no token. |
| `#E5484D` | 6 | 3 | Extra red competing with `--danger`. |
| `#181818`, `#141422`, `#0D0D15`, `#05050A`, `#0F1115` | 6 | — | Yet more dark surfaces. |
| `#4285F4`, `#34A853`, `#FBBC05`, `#EA4335`, `#1877F2`, `#FFDD55`, `#C837AB` | 14 | 2 | **Third-party brand colours** (Google / Facebook / Instagram OAuth buttons) — these are *correct* as literals and should be **exempted**, not tokenised. |

**Recommendation for Phase 9 (not yet actioned, awaiting approval):** the (c) list collapses to roughly
**8 new named tokens** (`--accent-sky`, `--accent-sky-soft`, `--primary-hot`, `--gold`, `--accent-violet`,
`--bg-deep`, `--surface-deep`, plus retaining the OAuth literals as an explicit exemption). The
remaining ~30 near-duplicate values are drift and should collapse into existing tokens.

**(a) Cosmetic one-offs → utility class:** the 258 class-C inline styles plus the 49 class-A2, i.e.
**307 items** needing a spacing/layout utility rather than a new colour token.

---

## §3 — Row 5 expanded: 3D mesh & material inventory

### 3a. What is actually inside `trainers.glb`

Parsed directly from the GLB's JSON chunk (12-byte header → chunk 0, 71,548 bytes of JSON):

**Meshes — 6 total:**

| # | Mesh name | Primitives | Material slots | Classification |
|---|---|---:|---|---|
| 0 | `MocapGuy_Body` | 2 | `Body_MAT`, `Reflectors` | **skin + everything else, one mesh** |
| 1 | `MocapGuy_BrowsLashes` | 1 | `Brows_MAT` | face |
| 2 | `MocapGuy_Caruncula` | 1 | `Body_MAT` | face (eye corner) |
| 3 | `MocapGuy_Eyes` | 1 | `Eyes_MAT` | face |
| 4 | `MocapGuy_Hat` | 2 | `Body_MAT`, `Reflectors` | headwear |
| 5 | `MocapGuy_Teeth` | 1 | `Body_MAT` | face |

**Materials — 4 total:** `Body_MAT`, `Reflectors`, `Brows_MAT`, `Eyes_MAT`
**Animations — 1 total:** `Armature|mixamo.com|Layer0` (195 channels)
**Skins:** 1 · **Nodes:** 73 (`mixamorig:` Mixamo skeleton) · **Textures:** 3

> The dev viewer in §6b reports **"Meshes 8"** rather than 6 — it counts **primitives**
> (2+1+1+1+2+1 = 8), not meshes. Both numbers are correct; the GLB JSON above is the authoritative
> one for mesh/material naming.

### 3b. Definitive answer to Phase 11's gating question

> *"Tell me honestly whether the current `trainer.glb` even HAS separate clothing meshes/materials
> baked in, or whether new model assets are required — don't assume."*

**NO. It does not.** There is **no shirt, no shorts, no joggers, no shoes** — not as meshes, not as
material slots. The character's clothing is **painted into the `Body_MAT` texture atlas**, sharing one
material with the skin. Phase 11's "if NO" branch applies: **outfit swapping is impossible with the
current asset.** It requires either a re-exported `.glb` with separate clothing meshes, or a small
library of alternate outfit `.glb` files. Per Phase 11 item 1, I am **stopping and asking you** before
sourcing or commissioning anything.

### 3c. The existing "clothing" code is worse than row 5's "nothing exists"

Row 5 said there is no outfit system. There are two — **both broken, and one is the specific mistake
Phase 11 exists to correct.**

**(i) `TF_ModularClothing` (`titan3d.js:657-741`) — points at files that do not exist.**
The shipped UI (`titan3d.js:761-772`) offers four dropdown options:
```
assets/models/clothes/tank_top.glb      assets/models/clothes/hoodie.glb
assets/models/clothes/shorts.glb        assets/models/clothes/sweatpants.glb
```
`assets/models/` contains only `anims/`, `README.md`, `trainer.fbx`, `trainers.glb`. **There is no
`clothes/` directory.** Every one of those four options 404s and lands in the `catch` at
`titan3d.js:726`. This is a **non-functional control shipped into the product** — `CLAUDE.md` rule 6.

**(ii) `TitanRig.setClothes` (`titan-rig.js:280`) — repaints the body texture, including skin.**
It does not swap geometry. It rewrites pixels of the single `Body_MAT` atlas inside hardcoded UV
rectangles (`titan-rig.js` `SUIT_ZONES`):
```js
[0.000, 0.510, 0.480, 0.700, 255, 107, 26, 'shirt'],   // guesses where the shirt is on the atlas
[0.000, 0.780, 0.480, 0.920,  26,  28, 32, 'pants'],
[0.200, 0.920, 0.800, 1.000,  20,  24, 30, 'shoes'],
```
and the same pass runs `liftSkin()` with `SKIN_LIFT = [0.50, 0.40, 0.34]`, which **lightens the
character's skin pixels by up to 50%**.

Phase 11's opening line is *"The 3D character's SKIN TONE must never change"* and *"Do not fake it by
tinting the skin material — that is the exact mistake we are correcting."* **`titan-rig.js` is
currently doing exactly that.** This needs an explicit decision from you, not a silent rewrite.

### 3d. Procedural fallback body (`titan3d.js:321-416`) — for completeness

Two materials only: `bodyMaterial` (`titan3d.js:321`) and `jointMaterial` (`titan3d.js:331`), shared
across all 16 primitive meshes (torso, head, neck, shoulders, arms, forearms, pelvis, thighs, shins).
**No clothing meshes.** This path only activates if the GLB or `GLTFLoader` fails.

### 3e. Animation coverage: complete in code, unverified at runtime

The primary animation path is **not** clips. `titan3d.js:468` calls `TitanRig.attach(gltfRoot)`, which
drives the Mixamo skeleton **procedurally**; the mixer is deliberately disabled (`mixer = null`,
`titan3d.js:469`, *"rig drives; mixer would fight it"*).

All **14** `anim_mode` values used by the 43 seeded exercises have a real procedural implementation
in `titan-rig.js`: `squat:650`, `curl:663`, `press:673`, `jumpingjack:689`, `highknees:699`,
`twist:711`, `yoga:726`, `wallpushup:737`, `pushup:754`, `plank:773`, `mountain:783`, `crunch:800`,
`legraise:814`, `warmup:826`. **Coverage is complete — 14/14.**

This corrects a premise in Phase 13, which assumes failures look like *"an animation clip is not
resolved"*. With the rig path active there are no per-exercise clips to resolve; the matrix must
assert that **`TitanRig` produced a distinct pose sequence** for each mode instead.

> ⚠️ **Scope limit on this finding — stated so it is not over-read.** I verified 14/14 mode coverage
> **in source code only**. I have **not** verified at runtime that `TitanRig.attach()` actually
> succeeds in the app, because Phase 8 permits no code execution against a running instance. §6 gives
> a concrete reason to doubt it: the one existing 3D capture shows the status **"Loaded ✓ (no rig —
> clip mode)"**, i.e. the rig did *not* attach in that run. That capture is of `trainer-viewer.html`,
> not the app, so it is **not** proof the app fails — but **"does `TitanRig.attach()` return true in
> `index.php` / `pages/player.php` / `member/workouts.php`?" is now the single most important runtime
> question for Phase 12/13**, and it must be answered before either phase is trusted. If it returns
> false, the app falls into clip mode — where the GLB's **one** clip (`Armature|mixamo.com|Layer0`)
> would play for **all 43 exercises identically**.

---

## §4 — Row 7 expanded: exactly which pages include `includes/intro.php`

**One include site exists**, and row 7 is **stale**:

```php
includes/header.php:218   if (empty($_SESSION['cca_splash_shown'])):
includes/header.php:219       $_SESSION['cca_splash_shown'] = 1;
includes/header.php:220       include __DIR__ . '/intro.php';
includes/header.php:221   endif;
```

Line 220 sits **before** the `$authMinimal` early-return at `header.php:225-231`. `auth/login.php:38`
and `auth/register.php:49` both set `$authMinimal = true` and then include `header.php` (`login.php:39`,
`register.php:50`). **The intro therefore already fires on login and register.** The v2 doc's grep
(`grep -n "intro" auth/login.php auth/register.php`) returned nothing because the include is one level
up in the shared header, not in the auth files.

**Two independent guards** mean it plays at most once per session:
1. `$_SESSION['cca_splash_shown']` (`header.php:218`) — server-side, first page of the session only.
2. `sessionStorage 'cca_intro_seen'` (`intro.php:172`) — client-side; if set, the shell is removed
   immediately (`intro.php:173`). Bypass with `?test_intro` (`intro.php:171`).

**Coverage:** `header.php` is included by **68 pages** — all of `pages/`, `member/`, `trainer/`,
`doctor/`, `patient/`, `admin/`, `auth/`, and `index.php:5`. The 5 files in `portals/` are 4-line
redirect stubs that include only `config/config.php` and render no HTML.

**Consequence for Phase 14:** its premise — *"it is only wired into `index.php`"* — is incorrect. The
real gap is different and narrower: a user landing **directly** on `/auth/login.php` gets the **full
2.6-second splash** (`intro.php:242`) in front of a form they came to fill in. Phase 14's own item 1
already argues for a lighter form-appropriate entrance instead; that reasoning stands, but the work is
**replacing** the current behaviour, not adding a missing one.

**One live defect in the intro:** `intro.php:36` and `:61` colour the mark and wordmark with
`var(--cca-primary)`. That token **is** defined — but at `intro.php:7`, inside a `<style>` block scoped
to `#cca-intro-shell`. It is used at `intro.php:36,61,76` (3 of 4 uses) within that scope, so those
resolve; the 4th use needs a Phase 9 check for scope escape.

---

## §5 — Row 8 re-checked: per-file CSRF / role verification across all 21 `api/` files

Read file-by-file, checking whether the guard actually gates the **write branch** — not merely that
the string appears somewhere.

| # | File | Verifies CSRF? | How | Auth / role gate | State-changing | Verdict |
|---|---|---|---|---|---|---|
| 1 | `admin-set-plan.php` | ✅ | `csrf_verify_json()` | `require_role('admin')` | ✅ | **OK** |
| 2 | `admin-warn.php` | ✅ | `csrf_verify_json()` | `require_role('admin')` | ✅ | **OK** |
| 3 | `appointment-action.php` | ✅ | `csrf_verify_json()` | role-checked | ✅ | **OK** |
| 4 | `book-appointment.php` | ✅ | `csrf_verify_json()` | logged-in | ✅ | **OK** |
| 5 | `buy-item.php` | ✅ | `csrf_verify_json()` | logged-in | ✅ | **OK** |
| 6 | `create-appointment-checkout.php` | ✅ | `csrf_verify_json()` | logged-in | ✅ | ⚠️ **CORRECTED — see note** |
| 7 | `create-checkout.php` | ✅ | `csrf_verify_json()` | logged-in | ✅ | **OK** |
| 8 | `moderate-review.php` | ✅ | `csrf_verify_json()` | role-checked | ✅ | **OK** |
| 9 | `request-payout.php` | ✅ | `csrf_verify_json()` | role-checked | ✅ | **OK** |
| 10 | `sandbox-checkout.php` | ✅ | `csrf_verify_json()` | logged-in | ✅ | **OK** |
| 11 | `submit-review.php` | ✅ | `csrf_verify_json()` | logged-in | ✅ | ⚠️ **CORRECTED — see note** |
| 12 | `chat.php` | ✅ | inline `hash_equals` :13 | ❌ **none** | ❌ (read/AI reply) | ⚠️ **see below** |
| 13 | `community-post.php` | ✅ | inline :8 | `is_logged_in()` :11 | ✅ INSERT :17 | **OK** |
| 14 | `feedback.php` | ✅ | inline :8 | ❌ **none** | ✅ INSERT :18 | ⚠️ **GAP** |
| 15 | `food-lookup.php` | ✅ | inline :10 | `is_logged_in` + `is_pro` + role :8 | ❌ | **OK** |
| 16 | `log-food.php` | ✅ | inline :9 | `is_logged_in` :12 + role :13 | ✅ INSERT :23 | **OK** |
| 17 | `notifications.php` | ✅ | inline :13, **inside the POST branch** | `is_logged_in()` :6 | ✅ UPDATE :16,:19 | **OK** |
| 18 | `report-issue.php` | ✅ | inline :8 | `is_logged_in()` :9 | ✅ INSERT :20 | **OK** |
| 19 | `save-workout.php` | ✅ | inline :10 | `is_logged_in()` :13 | ✅ INSERT :23 | **OK** |
| 20 | `scan.php` | ✅ | inline :15 | `is_logged_in`+`is_pro` :11, role :12 | ✅ INSERT :130,:172 | **OK** |
| 21 | `stripe-webhook.php` | ❌ | n/a | ❌ | ✅ | **Correctly exempt** — webhooks are authenticated by Stripe signature, not a session CSRF token. A CSRF check here would break it. |

> ### ⚠️ CORRECTION added 2026-07-25 — two rows above were wrong
>
> `api/submit-review.php:3` and `api/create-appointment-checkout.php:3` both called
> **`require_logged_in()`, which does not exist** (the real helper is `require_login()`,
> `includes/functions.php:116`). Every request to either endpoint died with
> `Fatal error: Uncaught Error: Call to undefined function require_logged_in()`, leaking the server
> path to the client. **"Submit a review" and "book a paid appointment" were entirely broken.**
>
> I marked both **OK** above because I read them for the presence and ordering of the CSRF/auth guards
> — which were correct — but **never executed them**, so a fatal error on line 3 was invisible.
> This audit's own rule was that presence "somewhere in the file" is not proof; I applied that to guard
> *placement* but not to whether the file runs at all. Both are now fixed and runtime-tested; see
> `PHASE10_CARD_AUDIT.md` → "Bug 2".
>
> **Lesson for later phases: a read-based endpoint audit cannot establish that an endpoint works.**

**Corrected verdict on row 8:** the *"only 9 occurrences"* count was measuring the `csrf_verify_json()`
helper by name. Counting behaviour instead, **20 of 20 session-authenticated endpoints verify CSRF
before their write branch.** Row 8's implied conclusion (broad exposure) is **not supported**.

**Two real, narrower findings:**

1. **`api/feedback.php` — no authentication at all.** CSRF is checked (`:8`), then it `INSERT`s into
   `feedback` (`:18`) with no `is_logged_in()`. Since `$_SESSION['csrf']` is issued to anonymous
   visitors too, **any visitor can write unlimited rows to the `feedback` table.** Whether that is
   intended (public contact form) or a gap is a **product decision — flagging, not fixing.**
2. **`api/chat.php` — no authentication.** CSRF-gated (`:13`) but no login check, and it drives an AI
   reply path. Not state-changing, so lower severity, but it is an unauthenticated compute endpoint.

**Inconsistency worth noting:** two different CSRF idioms coexist — the `csrf_verify_json()` helper
(11 files) and a hand-rolled `hash_equals($_SESSION['csrf'] ?? '', $token)` (9 files). Both are
correct, but the second bypasses the helper, which is why the original grep undercounted. Worth
unifying in a later phase — **not a security hole today.**

---

## §6 — Audit of the *existing* screenshot evidence (not requested, but decisive)

`_audit/FINAL_REPORT.md` cites screenshots as proof of its 20 PASS cells. Those screenshot files do
exist — so I hashed and opened them. **None of them prove what they were cited for.**

### 6a. `_audit/compare/` — 20 files, and not one "before"

```
5 × after-desktop.png      5 × after-tablet.png
5 × after-mobile-landscape.png   5 × after-mobile-portrait.png
before-*.png : 0 files
```
One set per portal (admin, doctor, member, patient, trainer). A before/after comparison with no
"before" is a screenshot, not a comparison. **The claim it was cited to support cannot be checked.**

### 6b. `_audit/after/3d/` — all 12 captures are invalid

```
A52BF0A8EE0E76083F874D1A9D75EB8E  after/3d/idle/orbit-0deg.png
A52BF0A8EE0E76083F874D1A9D75EB8E  after/3d/idle/orbit-90deg.png
A52BF0A8EE0E76083F874D1A9D75EB8E  after/3d/idle/orbit-180deg.png
A52BF0A8EE0E76083F874D1A9D75EB8E  after/3d/idle/orbit-270deg.png
8BC20B1124896B507B2513C77E6C45BA  after/3d/pushup/orbit-{0,90,180,270}deg.png
8BC20B1124896B507B2513C77E6C45BA  after/3d/squat/orbit-{0,90,180,270}deg.png
```
Two distinct images across twelve files. **All four "orbit" angles are byte-identical** — the camera
never moved. **`pushup/` and `squat/` are literally the same file** — the exercise never changed.

**Root cause, found in `_audit/orbit-test.mjs`:**
1. It drives `http://localhost/.../trainer-viewer.html` — the **standalone dev viewer, not the app**.
   So the application's 3D player was never captured.
2. It switches exercise via `if (typeof window.setMode === 'function') window.setMode(mode)`.
   `setMode` is defined in **`titan3d.js:880`** — and `trainer-viewer.html:81` loads **only
   `titan-rig.js`**, never `titan3d.js`. The guard is therefore **always false and the mode switch is
   a silent no-op**. That is exactly why all three "exercises" produced the same frames.

Opening `after/3d/pushup/orbit-90deg.png` confirms it: the highlighted button is **"Squat"**, not
Push-Up (the viewer's unchanged default), the header reads **"CCAFORGE · Trainer Viewer"** (old name
again — see 10d), **the character is not visible in the viewport at all**, and the status badge reads
**"Loaded ✓ (no rig — clip mode)"** with `EMBEDDED CLIPS: Armature|mixamo.com|Layer0 (11.4s)` — one
clip, matching §3a.

### 6c. `_audit/intro/` — 10 identical blank frames

```
F1CD5C1D76FB309801354E0640FF4710  frame-1.png … frame-10.png   (all 10, 4,258 bytes each)
```
Opened `frame-1.png`: a **completely blank dark rectangle**. The intro "frame strip" captured the
splash overlay before anything rendered, ten times.

### 6d. Why this matters for Phases 9–17

Every remaining phase in the v2 document ends with a screenshot requirement, and Phase 17 makes
screenshot paths the *only* currency for the word "PASS". §6 shows a capture harness can produce a
directory full of plausible-looking PNGs that prove nothing.

**Proposed guard for every future capture (needs your approval):** a screenshot set is only accepted
as evidence if (i) files that should differ have **different MD5s** — asserted by the script, not by
eye; (ii) the capture targets a **real application URL**, never `trainer-viewer.html` or
`dev-rig-shot.html`; and (iii) any `window.X` the script depends on is **asserted present**, not
skipped by a `typeof` guard that fails silently. All three failures above would have been caught by
that rule.

---

## What I propose

Nothing is implemented yet — Phase 8 is discovery only. Proposed ordering for your approval:

1. **Re-scope Phase 9 to lead with the undefined-token migration (§10a), not the Tailwind removal.**
   *Impact:* fixes the actual unreadable-text cause — 575 broken references across 27 names, of which
   13 core tokens are 534.
   *Risk:* low-medium; it is a rename/define operation, but it touches `style.css` broadly.
   Removing Tailwind is still correct and still in scope — it is just **second**, because deleting
   the CDN while 575 token references stay broken would make the app look *worse*, not better.
2. **Phase 9 must also cover the 4 duplicate Tailwind CDN tags** in `pages/` (row 1), not just `header.php:191`.
   *Impact:* prevents "removed it" that leaves 4 pages still loading it. *Risk:* low.
3. **Get your decision on the 8 candidate new tokens in §2d(c) before any find/replace.**
   *Impact:* prevents inventing tokens, per Phase 8 item 2c. *Risk:* none — it is a question.
4. **Phase 11 is BLOCKED on a 3D asset decision (§3b).** *Impact:* it cannot proceed honestly.
   *Risk:* proceeding anyway means faking it via skin-tinting — the prohibited outcome.
5. **Treat §3c(ii) `SKIN_LIFT` as a live defect needing your ruling.** *Impact:* the shipped app
   modifies the character's skin tone today. *Risk:* changing it alters current visual output, so
   I will not touch it without your word.
6. **Re-scope Phase 14 from "add missing intro" to "replace the full splash on auth pages" (§4).**
   *Impact:* correct problem statement. *Risk:* low.
7. **Correct Phase 13's premise from clip-resolution to procedural-pose assertion (§3e).**
   *Impact:* the matrix would otherwise report 43 false FAILs. *Risk:* low.
8. **Adopt the §6d screenshot-validity rule before any phase produces screenshots again.**
   *Impact:* prevents Phases 9–17 repeating the exact failure that made the last report untrustworthy —
   a folder of valid-looking PNGs proving nothing. *Risk:* none; it only adds assertions.
9. **Answer the runtime question in §3e first: does `TitanRig.attach()` return true in the app?**
   *Impact:* if it returns false, all 43 exercises play one identical clip and Phases 12/13 are built
   on sand. *Risk:* none — it is one console assertion on a running page.

## What I will delete/replace

**Nothing in this phase.** For transparency, the deletion candidates Phase 8 surfaced, to be tabled
properly with proof when their phase is approved:

| File | Reason | Referenced by | Safe to delete? |
|---|---|---|---|
| `assets/models/anims/idle.glb` | Byte-identical copy of `trainers.glb` (MD5 `62383BEA…`), not an animation | `titan3d.js:516` via `TRAINER_ANIMS`, which is `[]` at `titan3d.js:50` → never executes | **Not yet proven** — needs Phase 12/13 sign-off on whether external anims get re-enabled |
| `assets/models/anims/squat.glb` | Same | Same | Same |
| `assets/models/anims/warmup.glb` | Same | Same | Same |
| `assets/models/anims/trainers.glb` | Same | Same | Same |
| `assets/models/anims/trainer.fbx` | Copy of `assets/models/trainer.fbx` (MD5 `96732528…`) | No code reference found | **Not yet proven** |

Deleting all five would remove ~31 MB. **I have not deleted anything and will not until this table is
approved with per-file proof.**

## Verification plan

Commands run for this report, reproducible:

| Check | Command |
|---|---|
| Tailwind CDN sites | `Grep "cdn\.tailwindcss\.com" --glob '!**/node_modules/**' -n` |
| Hex inventory (51 distinct) | Node AST-free scan, PHP `#` comments + `href="#…"` stripped |
| Inline-style classification | Node scan, regex `style\s*=\s*"…"`, 4-way A1/A2/B/C split |
| Undefined tokens | Node: all `var(--x)` uses minus all `--x:` declarations + `setProperty('--x'` |
| GLB contents | Node: GLB header → chunk 0 JSON → `meshes[]`, `materials[]`, `animations[]` |
| Duplicate models | `Get-FileHash -Algorithm MD5` over `assets/models/**` |
| API per-file | `grep -n "REQUEST_METHOD\|csrf\|require_role\|is_logged_in\|INSERT\|UPDATE"` per file, then read |
| `.cca-card` | `Select-String '\.cca-card[a-z0-9_-]*'` in `style.css`; usage grep across `.php` |
| Placeholders (row 9) | `Grep "lorem ipsum\|TODO\|FIXME\|coming soon\|placeholder image\|dummy data" -i` |

**Not yet done — belongs to Phase 9, not Phase 8:** contrast-ratio computation and light/dark
screenshot pairs. Phase 8 is explicitly "zero code changes / report only", and screenshots require a
running XAMPP instance plus a browser session. **Flagging this so it is not mistaken for a silent skip.**

## Open questions (max 3)

1. **3D assets (blocks Phase 11):** `trainers.glb` has no clothing geometry (§3b). Do you want to
   (a) commission/source outfit `.glb` assets, (b) drop outfit-swapping and remove the dead
   `assets/models/clothes/*` dropdown UI, or (c) keep texture-atlas recolouring — knowing it repaints
   skin pixels, which Phase 11 forbids?
2. **New tokens (blocks Phase 9):** approve the ~8 candidate tokens in §2d(c)? Specifically, is
   `#0A0A0A` (44 uses) meant to *replace* `--bg` `#0F1115`, or is it drift that should collapse into it?
   And should the 14 OAuth brand literals stay exempt from tokenisation?
3. **`api/feedback.php` (§5):** is anonymous feedback submission intended, or should it require login?

---

## Roman Urdu summary — jo mila (5 lines)

1. **Asli masla Tailwind nahi hai** — **575 jagah** aise CSS token use ho rahe hain jo **kahin define hi nahi** (27 naam; inme se 534 sirf 13 core token ke hain — `--muted`, `--line`, `--tech`, `--molten`). Isi wajah se text **dono** themes mein na-qabil-e-parh raha, aur sirf Tailwind hatane se ye theek nahi hota (§10a).
2. Tailwind CDN **1 nahi, 5 files** mein laga hai; 520 hex colors aur 596 inline styles mile — inme se **284 asli masla** hain, 5 bilkul jaiz hain, aur 14 Google/Facebook ke brand colors hain jinhe haath nahi lagana chahiye.
3. **3D model mein kapre hain hi nahi** — `trainers.glb` khol kar dekha, sirf 6 mesh (Body, Eyes, Teeth, Brows, Hat) aur 4 material hain; koi shirt/shorts/shoes nahi. Aur jo "clothes" code maujood hai wo (a) `assets/models/clothes/` ki files load karta hai jo **wajood hi nahi rakhtin**, aur (b) `SKIN_LIFT` se **skin ka rang badal deta hai** — jo Phase 11 ne sakhti se mana kiya hai.
4. **Purani report ke saare screenshot jhoote nikle** (§6): 3D ke 12 mein se 4-4 angle **bilkul identical file** hain (camera ghooma hi nahi), `pushup` aur `squat` ki file **ek hi hai**, aur intro ke 10 frames **khaali kaali tasveer** hain. Wajah mil gayi — capture script app ko nahi, `trainer-viewer.html` ko kholta hai, aur `setMode` wahan maujood hi nahi, is liye exercise kabhi badla hi nahi. `compare/` folder mein 20 "after" hain magar **ek bhi "before" nahi**.
5. **Do baatein jo document ne ghalat likhi thin, main theek kar raha hoon:** intro login/register pe **pehle se chal raha hai** (`header.php:220`, poora 2.6-second splash — masla ulta hai), aur **security document ke daave se behtar hai** — 20 ke 20 endpoints CSRF check karte hain. Magar login/register page pe abhi bhi purana naam **"CCA FORGE"** likha aa raha hai (`login.php:44`, `register.php:55`) — ye seedha `CLAUDE.md` ki khilaf-warzi hai.

**Maine is phase mein koi app code change nahi kiya.** Sirf `README.md` ka jhoota "V1 COMPLETE" aur
`FINAL_REPORT.md` ka PASS table theek kiya hai — jo Phase 8 ne khud kaha tha. **Aage badhne se pehle
upar wale 3 sawaalon ka jawab chahiye, khaas kar 3D assets wala.**
