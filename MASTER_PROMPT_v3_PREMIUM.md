# CORE CALORIE ADVISOR — MASTER PROMPT v3.1 (PREMIUM BUILD)

> ### v3.1 REVISION — 2026-07-31 (verification pass)
> v3 ke saare factual claims code ke against verify kiye gaye. **~50 me se zyadatar
> citations sahi nikleen** (`functions.php:24/167/227/264/294`, `player.js:12-13`,
> `titan3d.js`/`titan-rig.js`/`cca-wardrobe.js` ke LOC, sab confirmed).
> Jo galat nikla, wo ye hai:
>
> | # | Kya badla | Kahan |
> |---|---|---|
> | 1 | **Phase J ke teeno "gaps" false positive thay** — koi privilege escalation nahi, koi missing CSRF nahi. Root cause: grep sirf `csrf_verify_json` dhoond raha tha, codebase 3 idiom use karta hai. | §J.1 (poora rewrite), §0.3.1 (naya) |
> | 2 | **Repo git repository hi nahi hai**, aur `.gitignore` nahi hai jabke `.env` me 20 live secrets hain. Rule 0.1.8 / Phase A.2 / Phase K.2 teeno unexecutable thay. | §0.4 PRE-FLIGHT (naya), §0.3.2 (naya) |
> | 3 | Rate-limiting targets ghalat thay — `chat.php` par koi API cost nahi, `scan.php` par hai. | §J.2.2 |
> | 4 | Phase order ki urgency false P0 par khari thi — revised. | §J.4, Appendix 3 |
> | 5 | `pages/` = 29 files, 26 nahi. | §0.3 |
>
> Jo v3 me sahi tha usay chhera nahi gaya — Phases B, C, D, E, F, G, H, I, L, M, N
> **jyon ke tyon** hain.

> **How to use:** repo root me save karo as `MASTER_PROMPT_v3_PREMIUM.md`.
> Phir VS Code terminal me `claude` chalao aur ye bhejo:
>
> ```
> Read CLAUDE.md and MASTER_PROMPT_v3_PREMIUM.md end to end.
> Then execute PHASE A only. Stop and wait for my written "APPROVED A".
> ```
>
> Har phase ke baad sirf "APPROVED <letter>" likhna hai. Agar tum "APPROVED" na likho,
> agent ko implement karne ki ijazat nahi.

---

## 0. CONTRACT — READ BEFORE ANY TOOL CALL

`CLAUDE.md` already in force hai. Ye document usko **replace nahi** karta, **extend** karta hai.
Conflict ho to `CLAUDE.md` jeetega.

### 0.1 Hard rules (violation = phase rejected, revert)

1. **Evidence or silence.** Har claim ke sath `path:line-range` do. "I think", "probably",
   "should be" — banned. Agar file padhi nahi, us par baat mat karo.
2. **One phase at a time.** Phase ka scope band hai. Agar Phase D me tumhe Phase G ka bug
   dikhe — `## Deferred findings` me note karo, **fix mat karo**.
3. **Deletion table mandatory.** Koi file delete karne se pehle:
   `file | size | reason | who references it (grep output) | safe? yes/no | proof`
   Agar "referenced by" column me kuch bhi hai → `safe? = no`. External callers
   (webhooks, cron, OAuth redirect targets) grep me nahi aate — inko manually verify karo.
4. **No new dependencies.** No new CDN, npm, composer, font, icon pack. Jo already
   `includes/header.php` me load ho raha hai bas wahi use karo.
5. **Business logic freeze.** DB queries, pricing, commission math, auth flow ka
   behaviour change nahi hoga — sirf security wrappers add honge (Phase J).
6. **No placeholder content.** No lorem ipsum, no `example.com`, no fake kcal numbers,
   no stock-photo URLs jo pehle se na hon. Missing data = DB se aayega ya feature
   ship nahi hoga.
7. **No IP copying.** "Free Fire style" ka matlab sirf **mood** hai: high-contrast dark
   stage, hard cuts, impact flash, heavy display type. Garena/Tencent ka logo, wordmark,
   font, sound, color palette, ya UI layout **copy nahi karna**. Original design only.
8. **Backup before destructive work.** Har phase jo delete/rename karta hai, us se pehle:
   `git add -A && git commit -m "pre-<phase> checkpoint"`.
   **Ye repo abhi git repo nahi hai** — `git init` seedha mat chalao, `.env` commit ho jayega.
   Pehle **§0.4 PRE-FLIGHT** ka poora gate chalao (`.gitignore` → `git init` → verify → commit).
9. **`.env` ko haath nahi lagana.** Read bhi mat karo except key *names* ke liye.
10. **Verification = command + output.** Screenshot ka claim tab hi jab file actually
    `_audit/` me likhi ho.

### 0.2 Per-phase report format (fixed)

```
## PHASE <X> — <name>
### What I found          (facts + path:line)
### What I propose        (numbered; each = change + impact + risk + rollback)
### Files I will touch    (table: path | action | why)
### What I will delete    (deletion table from 0.1.3, or "nothing")
### Verification plan     (exact commands, expected output)
### Open questions        (max 3, or "none")
### Roman Urdu summary    (exactly 5 lines)
```

### 0.3 Environment facts (already verified — dobara mat poochhna)

- Stack: **PHP 8.x + MySQL, XAMPP on Windows.** No framework, no build step, no bundler.
- Entry: `index.php`; global chrome: `includes/header.php` (340 lines) + `includes/footer.php` (100 L).
- Portal shims: `portals/{admin,doctor,member,patient,trainer}.php` — har ek 4-line redirect
  hai jo `<role>/dashboard.php` par bhejta hai.
- Real portal pages: `admin/`, `doctor/`, `member/`, `patient/`, `trainer/` folders.
- Shared pages: `pages/` (**29 files**, v3 draft me galti se 26 likha tha). APIs: `api/` (23 files).
- 3D: `assets/js/titan3d.js` (1329 L) = scene/loader, `assets/js/titan-rig.js` (1029 L) =
  procedural bone posing, `assets/js/cca-wardrobe.js` (189 L) = loadout.
- Workout engine: `assets/js/player.js` (318 L) + `pages/player.php` (1660 L).
- Schema: `sql/core_calorie_advisor.sql`, 33 tables.
- Design system: `.cca-*` utility classes + Tailwind bridge in `assets/css/cca-tw-bridge.css`.

### 0.3.1 CSRF ka asal mechanism — ye padhe baghair Phase J ko haath mat lagana

Codebase me CSRF verify karne ke **teen** alag idiom hain. Teeno functionally equivalent hain:

| Idiom | Definition | Example call site |
|---|---|---|
| `csrf_verify()` | `includes/functions.php:15` | `api/admin-set-plan.php:9`, `api/create-checkout.php` |
| `csrf_verify_json()` | `includes/functions.php:24` | `api/admin-warn.php:7`, `api/moderate-review.php` |
| Inline `hash_equals($_SESSION['csrf'], $token)` | file ke andar hi likha hua | `api/chat.php:13`, `api/scan.php:15`, `api/save-workout.php:11` |

> **Warning:** sirf `grep -c "csrf_verify_json"` chalane se 14 endpoints "unprotected" lagte hain
> jo actually protected hain. v3 draft ke Phase J ke saare P0/P1 findings isi galti se paida
> huay the — dekho §J.1. Koi bhi security claim karne se pehle **file kholo, grep par mat jao.**

### 0.3.2 Version control ki asal haalat (v3 draft me miss thi)

- **Ye folder git repository nahi hai.** `git status` → `fatal: not a git repository`.
- **`.gitignore` mojood nahi hai.** `ls .gitignore` → No such file.
- **`.env` repo root me mojood hai**, 20 keys ke sath jin me `ANTHROPIC_API_KEY`,
  `STRIPE_SECRET_KEY`, `STRIPE_WEBHOOK_SECRET`, aur 6 OAuth secrets (Google/Facebook/Instagram) hain.
- `.htaccess:6` `.env` ko **web layer par** block karta hai — lekin git layer par koi protection nahi.

Iska matlab: rule 0.1.8 (`git commit` checkpoint), Phase A.2 (`git status` clean), aur
Phase K.2 (`git rm`) **abhi execute hi nahi ho sakte**. Isi liye naya §0.4 pre-flight add kiya gaya hai.

---

## 0.4 PRE-FLIGHT — PHASE A SE BHI PEHLE (one time, ~2 minutes)

**Kyun:** har destructive phase ka rollback plan git par khara hai, aur git abhi hai hi nahi.
Phase K akela ~200 MB delete karta hai **bina kisi undo path ke**. Ye gate us se bachata hai.

**Order matlab rakhta hai — `git init` pehle chalaya to secrets history me chale jayenge.**

```bash
# STEP 1 — .gitignore PEHLE banao (git init se pehle, warna .env commit ho jayega)
cat > .gitignore <<'EOF'
.env
logs/
uploads/
_audit/
*.zip
*.bak
*.old
EOF

# STEP 2 — ab repo initialize karo
git init

# STEP 3 — GATE: confirm .env untracked hai. Ye output KHALI aana chahiye.
git status --porcelain --untracked-files=all | grep -E "^\?\? \.env$"
#   ^ agar is command ka koi output aaya → RUKO. .gitignore theek nahi hai. Aage mat barho.

# STEP 4 — pehla checkpoint
git add -A
git status --short | grep -c ""      # kitni files stage huin, note kar lo
git commit -m "pre-phase-A baseline checkpoint"
```

**Acceptance:** STEP 3 ka output khali ho, aur `git log --oneline` me exactly 1 commit ho.

> `ANTHROPIC_API_KEY` abhi tak kisi git history me nahi gaya (repo hi nahi tha), is liye
> **rotate karna zaroori nahi** — bas is gate ko sahi order me chalao. Agar galti se
> `git init && git add -A` pehle chal gaya, tab key rotate karni paregi.

---

## PHASE A — FORENSIC BASELINE (no code changes)

**Goal:** ek honest map banao. Zero edits is phase me.

### A.1 Produce `_audit/PHASE_A_BASELINE.md` containing:

1. **Route map** — har `.php` file ke liye: `path | reachable from (grep) | requires auth? |
   requires role? | includes header? | LOC`. Reachable = koi `href`/`action`/`fetch`/`redirect`
   us par point karta ho.
2. **Orphan list.** Meri apni grep pass ne ye 8 files unreferenced dikhaye — **verify karo,
   trust mat karo**:
   ```
   api/admin-set-plan.php
   api/admin-warn.php
   api/create-appointment-checkout.php
   api/moderate-review.php
   api/request-payout.php
   api/stripe-webhook.php        <-- WARNING: Stripe isko bahar se call karta hai. DELETE MAT KARNA.
   api/submit-review.php
   pages/about.php
   ```
   Har ek ke liye faisla do: `wire it up` / `delete` / `keep (external caller)`.
   Jo API mojood hai lekin koi UI use nahi kar raha — matlab ya to **button missing hai**
   (Phase M) ya feature dead hai. Dono me farq batao.
3. **Security matrix.** Har `api/*.php` ke liye 5 columns:
   `CSRF (kis idiom se)? | login guard? | role guard? | prepared statements? | output escaped?`

   > **Ye matrix ab §J.1 me verified shakl me mojood hai — dobara scratch se mat banao.**
   > v3 draft me yahan jo "NO CSRF (9 endpoints)" aur "NO AUTH GUARD (4 endpoints)" ki list thi,
   > wo **teeno false positive** nikli. Wajah: grep sirf `csrf_verify_json` dhoond raha tha,
   > jabke codebase teen idiom use karta hai (§0.3.1). Us list ko mat dohrao.

   Is phase me tumhara kaam sirf **do baaki columns** bharna hai jo abhi tak verify nahi huay:
   - **Prepared statements** — har `db()->prepare()` vs koi raw string concat SQL. Grep:
     `grep -rn "query(\|exec(" --include=*.php api/` aur har hit manually padho.
   - **Output escaping** — har API jo HTML return karta hai (JSON nahi), kya `e()` se guzra?

   Matrix `_audit/PHASE_A_BASELINE.md` me. Har row ke sath `path:line` ho.
4. **Weight report.** `du -sh` per folder. Baseline: `assets/videos` = 153 MB,
   `assets/models` = 22 MB, `_audit` = 57 MB. Total repo bloat ka number do.
5. **Doc drift.** `assets/models/README.md` bar bar `trainer.glb` kehta hai lekin asli file
   `assets/models/trainers.glb` hai. Aise sab drift points list karo. Known drift jo already
   confirm ho chuki hai (report me shamil karo, dobara verify karne ki zarurat nahi):
   - `CLAUDE.md` "Project Status" kehta hai *"`csrf_verify_json()` applied to all APIs"*.
     **Protection to sach me mojood hai**, lekin mechanism ka bayan ghalat hai — 23 me se
     sirf 7 APIs wo helper use karte hain, baqi `csrf_verify()` ya inline `hash_equals`
     use karte hain (§0.3.1). Ye wording hi thi jis ne v3 ke false P0 findings paida kiye.
     **Priority: low** (docs wording, code nahi). `CLAUDE.md` ko is phase me **edit mat karo** —
     sirf note karo, kyunke wo file scope se bahar hai.
   - `MASTER_PROMPT_v3_PREMIUM.md` §0.3 khud `pages/` ko 26 kehta tha, asal 29 hai — ab fix ho chuka.
6. **Line-ending drift.** `includes/header.php` CRLF hai, baqi files LF. Full list do.

### A.2 Acceptance
- Report me har number ke sath wo command ho jisse wo number nikla.
- Koi file edit nahi hui — `git status` clean (sirf `_audit/PHASE_A_BASELINE.md` untracked).

**STOP. Wait for "APPROVED A".**

---

## PHASE B — BRAND SYSTEM & LOGO

**Goal:** logo ko "acha lag raha hai" se "system hai" tak le jao.

### B.1 Constraints
- Source of truth **already exists**: `assets/brand/logo-mark.svg`, aur `includes/header.php`
  usko runtime par padhta hai (~line 100-118). Ye pattern **todna nahi** — sirf file behtar karo.
- Naya logo concept: name ka matlab hi design hai — **Core** (center/ring), **Calorie**
  (flame/energy), **Advisor** (progress head jo ring par chal raha ho). Ye idea already
  `includes/intro.php` ke comments me encoded hai (`#cca-ring`, `#cca-head`, `#cca-core`).
  Usko refine karo, replace nahi.

### B.2 Deliverables
1. `assets/brand/logo-mark.svg` — square mark, 1 color-token (`currentColor`), koi
   embedded raster nahi, koi filter jo IE/Safari me toot jaye nahi. Optical alignment fix.
2. `assets/brand/logo-full.svg` — mark + wordmark horizontal, aligned baseline.
3. `assets/brand/logo-stacked.svg` — mark upar, wordmark neeche, centered.
4. `assets/brand/logo-mono-light.svg` + `-mono-dark.svg` — single-fill, koi gradient nahi
   (print/invoice/email ke liye).
5. `assets/brand/favicon.svg` — 16px par readable. Agar mark 16px par mud jaye to
   simplified variant banao, mat squeeze karo.
6. `assets/brand/BRAND.md` update — clear-space rule, min-size (px), approved
   background list, aur **kya karna mana hai** (stretch, recolor, drop-shadow, rotate).
7. **Wordmark ke liye koi naya font mat khareedo/load karo.** Jo display font already
   header me hai (`Russo One` / `Rajdhani` — verify karo kaun sa actually load ho raha hai)
   usi se wordmark banao, ya letterforms ko path me convert kar do taake load hi na karna pare.

### B.3 Verification
- `node _audit/logo-test.html`-style page ki jagah ek naya `_audit/brand-sheet.html` banao
  jo saaton files ko 5 sizes (16/24/48/128/512 px) par, light + dark dono par render kare.
- Screenshot `_audit/brand-sheet.png` me save karo.
- Grep proof: koi bhi file me inline hand-typed SVG copy bachi nahi
  (`grep -rn "cca-ring" --include=*.php` sirf `intro.php` dikhaye).

**STOP. Wait for "APPROVED B".**

---

## PHASE C — INTRO CINEMATIC

**Goal:** pehla 2.5 second aisa ho ke banda screenshot le.

### C.1 Current state
`includes/intro.php` (352 L) already ek 5-beat sequence chalata hai: bloom → ring trace →
head → ignite → shockwave. Comments me ek real bug ka fix documented hai (parent par
`animation` shorthand ne `do-slam` ko cancel kar diya tha). **Wo comment mat hatao** —
wo institutional knowledge hai.

### C.2 Required upgrades
1. **Timing lock.** Total intro ≤ **2600 ms**, hard cap. Ek constant banao
   (`--cca-intro-total`) aur saari `animation-delay` usi se derive hon. Aaj wo hardcoded
   numbers hain — audit karo aur normalize karo.
2. **Audio-optional impact.** Koi audio file add **nahi** karni (dependency rule). Lekin
   beat structure aisa ho ke baad me sound drop karna trivial ho: har beat ko
   `data-beat="1..5"` do.
3. **Once per session.** Abhi check karo ke intro har page load par chalta hai ya nahi.
   Agar chalta hai — `sessionStorage` flag lagao (`cca-intro-seen`). Repeat visitor ko
   2.6 s har baar dena premium nahi, irritating hai.
4. **Skip affordance.** Bottom-right me chhota "Skip ›" jo 400 ms baad fade-in ho.
   Keyboard: `Esc` ya `Space` bhi skip kare.
5. **prefers-reduced-motion.** Already partially handled (`intro.php` ~line 92-104).
   Complete karo: reduced-motion me intro **bilkul** na chale, seedha content dikhe.
6. **No layout shift.** Intro ke hatne par CLS = 0. Verify karo ke `#cca-intro-shell`
   `position:fixed` hi rahe aur body scroll lock properly release ho.
7. **Wordmark reveal.** Mark ignite hone ke baad "CORE CALORIE ADVISOR" letter-by-letter
   nahi — **word-by-word** aaye (letter-stagger 2026 me cheap lagta hai). Tracking
   `0.35em → 0.12em` tighten hote hue.

### C.3 Verification
- `_audit/intro-frames/` me 8 frames capture karo (0, 300, 600, 900, 1200, 1600, 2000, 2600 ms).
- Chrome DevTools Performance trace: koi layout thrash nahi, koi long task > 50 ms nahi.
- Reduced-motion emulation par screenshot: content instantly visible.

**STOP. Wait for "APPROVED C".**

---

## PHASE D — 3D TRAINER: FULLY SOLID, ZERO TRANSPARENCY

**Goal:** character ka koi bhi hissa see-through nahi. Ye mera **#1 complaint** hai.

### D.1 Root causes — ye exact lines dekho

| File:line | Problem |
|---|---|
| `assets/js/titan3d.js:333-341` | Procedural fallback athlete ka `bodyMaterial` me `transparent: true, opacity: 0.85`. Jab GLB load fail ho ya timeout ho, ye ghost body render hota hai. |
| `assets/js/titan3d.js:280-283` | GLB path par already `transparent=false, opacity=1, alphaTest=0, depthWrite=true` force hota hai — **ye sahi hai**, lekin confirm karo ke ye har mesh par chalta hai, sirf top-level par nahi. |
| `assets/js/titan3d.js:190,196,211` | Atmosphere cones/volumes transparent hain — **ye theek hai**, ye character nahi. Lekin verify karo ke inka `renderOrder` character ke *peeche* ho. |
| `assets/js/titan3d.js:161,182` | Shadow catcher + contact shadow transparent hain — theek hai. |
| `assets/js/cca-wardrobe.js:apply()` | Outfit texture swap `m.map` set karta hai lekin `m.transparent` / `m.alphaTest` ko touch nahi karta. Agar swapped PNG me alpha channel hai, material silently transparent ho sakta hai. |

### D.2 Required work

1. **Ek single choke point banao.** `titan3d.js` me ek function:
   ```js
   function forceOpaque(root) {
     root.traverse(o => {
       if (!o.isMesh && !o.isSkinnedMesh) return;
       (Array.isArray(o.material) ? o.material : [o.material]).forEach(m => {
         if (!m) return;
         m.transparent = false;
         m.opacity     = 1;
         m.alphaTest   = 0;
         m.depthWrite  = true;
         m.depthTest   = true;
         m.side        = THREE.FrontSide;   // back-face bleed roke
         m.alphaMap    = null;
         m.needsUpdate = true;
       });
     });
   }
   ```
   Isko **teen jagah** call karo: (a) GLB load ke turant baad, (b) `CCAWardrobe.apply()`
   ke `setMap()` ke baad, (c) character switch ke baad (Phase E).
   Export bhi karo (`window.CCAForceOpaque`) taake `pages/player.php` aur
   `pages/trainer-studio.php` dono use kar sakein.

2. **Fallback athlete ko solid karo.** `titan3d.js:333-341` me `transparent`/`opacity`
   lines **delete** karo. Fallback ko translucent rakhne ka koi design reason nahi —
   agar wo "loading state" ke liye tha, to wo kaam loader overlay (`#arena-loader`,
   `pages/player.php:195`) already kar raha hai.

3. **Outfit textures se alpha strip karo.** `assets/models/outfits/*.png` (6 files) ko
   check karo: `identify -format "%[channels]"` ya sharp se. Agar RGBA hain aur alpha
   fully-opaque hai → RGB me re-encode karo (file size bhi girega). Agar alpha me
   actual holes hain → wo texture reject hai, batao mujhe.

4. **Floor ko solid stone banao.** `titan3d.js:137` ka `floorMat`:
   - `metalness: 0` (stone metal nahi hota), `roughness: 0.85-0.95`
   - Ek subtle procedural noise/normal, koi transparency nahi
   - **Height lock:** floor ka `y` ek constant se aaye (`const STAGE_FLOOR_Y = 0`),
     kisi bhi camera/animation code me recompute na ho. Mera purana complaint —
     "workout ke sath camera hile to floor uthta hua lagta hai" — iski wajah yahi
     scattered y-calculation hai. Grep karo `position.y` aur ek jagah consolidate karo.

5. **Regression guard.** `_audit/opaque-check.mjs` likho jo GLB parse kare aur assert kare
   ke koi material `alphaMode !== 'OPAQUE'` na ho. CI na sahi, manual run to ho.

### D.3 Verification
- Player page par console me: `let n=0; scene.traverse(o=>{if(o.material && [].concat(o.material).some(m=>m&&m.transparent)) n++}); n` → sirf atmosphere/shadow objects count me aayein, character ka koi mesh nahi. Exact list print karo naam ke sath.
- 3 screenshots: default outfit, ember outfit, ocean outfit — teeno me character ke peeche wala background character ke through **nahi** dikhna chahiye.
- Camera ko 4 angles par ghumao (front/side/back/low) — screenshots `_audit/solid-check/`.

**STOP. Wait for "APPROVED D".**

---

## PHASE E — CHARACTER ROSTER (GAME-STYLE SWITCHING)

**Goal:** member alag-alag trainer characters choose kar sake, jaise battle-royale games me.

### E.1 Current state (padho pehle)
`assets/js/cca-wardrobe.js:26-52` me sirf **2** trainers hain: `street`
(`trainer-street.glb`) aur `pro` (`trainer-pro.glb`). Ek teesra file
`assets/models/trainer-ch06.glb` bhi mojood hai lekin roster me nahi. Aur
`assets/models/trainers.glb` fallback hai.

### E.2 Required work

1. **Roster ko data-driven karo.** Hardcoded JS array ki jagah ek naya table:
   ```sql
   CREATE TABLE `trainer_characters` (
     `id` int AUTO_INCREMENT PRIMARY KEY,
     `slug` varchar(40) NOT NULL UNIQUE,
     `display_name` varchar(80) NOT NULL,
     `model_file` varchar(160) NOT NULL,
     `mesh_prefix` varchar(20) NOT NULL,
     `build_label` varchar(60) DEFAULT NULL,
     `wears_label` varchar(120) DEFAULT NULL,
     `tier` enum('free','pro','elite') NOT NULL DEFAULT 'free',
     `is_active` tinyint(1) NOT NULL DEFAULT 1,
     `sort_order` int NOT NULL DEFAULT 0
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
   ```
   Seed karo **sirf un models se jo asal me repo me hain** — invent mat karo.
   `trainer-ch06.glb` ko verify karo (load hota hai? rig valid hai?) — agar haan to roster me,
   agar nahi to delete list me.

2. **Selection UI.** `pages/trainer-studio.php` (348 L) already ek studio page hai —
   usi ko character-select screen banao, naya page mat banao. Layout:
   - Left: vertical character strip (portrait cards, active par accent border + glow)
   - Center: live 3D preview, slow auto-orbit, floor spotlight
   - Right: outfit categories (Phase F)
   - Bottom: `EQUIP` primary button + `RANDOMIZE` secondary
   - Locked characters par tier badge + "Upgrade to PRO" CTA jo `pages/pricing.php` par jaye

3. **Hot-swap without page reload.** `CCAWardrobe` me `switchCharacter(slug)` add karo:
   dispose old scene graph properly (`geometry.dispose()`, `material.dispose()`,
   `texture.dispose()` — warna XAMPP par 4-5 switch ke baad GPU memory bhar jayegi),
   naya GLB load karo, `TitanRig.attach()` dobara call karo, `forceOpaque()` chalao,
   `CCAWardrobe.apply()` chalao. Transition: 250 ms fade out → load → fade in.
   Loading ke dauran skeleton shimmer, blank screen nahi.

4. **Persistence.** Abhi choice sirf `localStorage['cca-loadout']` me hai
   (`cca-wardrobe.js:18`). Logged-in user ke liye DB me bhi save ho:
   `users` table me `character_slug varchar(40)` + `outfit_slug varchar(40)` add karo,
   ya better — ek `user_loadouts` table. localStorage ko cache rakho, DB source of truth.
   Naya API: `api/save-loadout.php` — **CSRF + require_login dono** (Phase J ke rules).

5. **Rig compatibility gate.** `titan-rig.js` Mixamo bone names par depend karta hai.
   Roster me daalne se pehle har model ke liye assert karo ke standard bones milte hain,
   warna character T-pose me khada rahega aur user ko lagega app toota hua hai.
   Fail hone par: model ko `is_active = 0` karo aur `_audit` me log karo — **UI me mat dikhao**.

### E.3 Verification
- Har character par 3 exercises chala kar screenshot: `_audit/roster/<slug>-<exercise>.png`
- Memory leak test: 10 baar switch karo, `performance.memory.usedJSHeapSize` before/after log karo. Growth < 15 MB.
- Locked character par free user click kare → pricing page, no 3D load (bandwidth waste na ho).

**STOP. Wait for "APPROVED E".**

---

## PHASE F — WARDROBE CATEGORIES

**Goal:** kapde categories me organized hon, ek flat list nahi.

### F.1 Reality check — ye padhna zaroori hai
`cca-wardrobe.js:8-22` me ek **honest failure note** likha hua hai: separate garment
meshes 3 baar try huay aur fail huay, kyunke dono characters ke kapde body mesh me hi
modelled hain (hoodie collar, pockets = body geometry). Evidence:
`_audit/garment-fail-v1-collar.png`, `-v2-collar.png`.

**Is note ko delete mat karna aur wahi galti dobara mat karna.** Aaj ka outfit system
= texture swap on body material. Ye limitation hai, jhoot nahi bolna.

### F.2 Required work — categories *within* what's actually possible

1. **Category schema:**
   ```sql
   CREATE TABLE `outfit_categories` (
     `id` int AUTO_INCREMENT PRIMARY KEY,
     `slug` varchar(40) NOT NULL UNIQUE,
     `label` varchar(60) NOT NULL,
     `icon` varchar(40) DEFAULT NULL,
     `sort_order` int NOT NULL DEFAULT 0
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

   CREATE TABLE `outfits` (
     `id` int AUTO_INCREMENT PRIMARY KEY,
     `character_id` int NOT NULL,
     `category_id` int NOT NULL,
     `slug` varchar(40) NOT NULL,
     `label` varchar(60) NOT NULL,
     `swatch_hex` char(7) NOT NULL,
     `texture_file` varchar(160) DEFAULT NULL,
     `tier` enum('free','pro','elite') NOT NULL DEFAULT 'free',
     `is_active` tinyint(1) NOT NULL DEFAULT 1,
     UNIQUE KEY `uq_char_slug` (`character_id`,`slug`),
     FOREIGN KEY (`character_id`) REFERENCES `trainer_characters`(`id`) ON DELETE CASCADE,
     FOREIGN KEY (`category_id`) REFERENCES `outfit_categories`(`id`)
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
   ```

2. **Seed categories — sirf wo jo REAL hain:**
   - `colorway` — jo abhi kaam karta hai (Midnight/Ember/Ocean/Forest texture swaps)
   - `accessory` — real geometry toggles: `cap` (Street par kaam karta hai,
     `cca-wardrobe.js:87-90`). **`glasses` ko mat resurrect karna** — wo 0.2 cm ka
     artefact banata hai aur floor ke neeche chala jaata hai (comment lines 24-32).
     Usko permanently hidden rakho.
   - Aur categories tab add karna jab **asli asset** aaye. Khali category card
     ("Shoes — coming soon") = placeholder = rule 0.1.6 ka violation. Mat banao.

3. **UI.** Right panel me tabbed categories, har tab me swatch grid.
   Locked items par lock icon + tier badge. Hover par live preview (apply on hover,
   revert on mouse-leave agar user ne click nahi kiya).

4. **Admin CRUD.** `admin/exercise-library-admin.php` ke pattern par ek naya
   `admin/character-wardrobe-admin.php`: characters + categories + outfits manage karo,
   texture upload (`validate_image_upload()` already `includes/functions.php:227` me hai —
   wahi use karo, naya validator mat likho), tier set karo, activate/deactivate.

5. **Migration path.** Jo 2 trainers + 8 outfits abhi JS array me hardcoded hain, unhe
   SQL seed me move karo. `cca-wardrobe.js` ab data ek bootstrapped JSON blob se le
   (`pages/*.php` render time par `<script>window.CCA_ROSTER = <?= json_encode(...) ?></script>`),
   har page load par API call nahi — ye ek local XAMPP app hai, N+1 requests se bachao.

### F.3 Verification
- Har category × har character combo par screenshot grid: `_audit/wardrobe-matrix.png`
- Admin se ek naya colorway add karo → member studio me bina cache clear ke dikhe.
- Free user ko PRO outfit par lock dikhe; DB me force karke check karo ke apply bhi na ho
  (client-side lock kaafi nahi — server-side gate lagao).

**STOP. Wait for "APPROVED F".**

---

## PHASE G — WORKOUT ENGINE: TIMING & REST CONTROL

**Goal:** Fitify-grade session control. Ye phase functionality hai, cosmetics nahi —
extra dhyan se.

### G.1 Current behaviour (exact, verified)

| Fact | Location |
|---|---|
| `REST_SEC = 20` hardcoded | `assets/js/player.js:12` |
| `READY_SEC = 10` hardcoded | `assets/js/player.js:13` |
| Rest har **3 exercises** ke baad | `player.js:87-91` |
| Total cap **600 s** hardcoded, extra exercises **silently truncate** ho jaate hain | `player.js:69-79` |
| Per-exercise seconds DB se aate hain, clamp 15–600 | `player.js:57` |
| `addRest()` +20 s | `player.js` (bottom) + button `pages/player.php:293` |
| `skipPhase()` exists | `player.js` + buttons `pages/player.php:334-335` |
| **Rest overlay me skip button NAHI hai** — sirf "+20s Rest" | `pages/player.php:292-296` |

Matlab: rest ke dauran user sirf **barha** sakta hai, **chhod nahi sakta** bina neeche
wale bar tak jaaye. Ye meri exact shikayat hai.

### G.2 Required work

1. **Duration selector.** Session start se pehle (`#playerIntro` block, `player.php` ~line 130-190)
   ek segmented control: **10 min · 15 min · 20 min**. Default = 10.
   Choice ko `TF_WORKOUT.targetSeconds` me pass karo.

2. **Rewrite `planBlocks()`** taake wo target ke hisaab se **fit** kare, silently truncate na kare:
   - Work interval target: **30 s** (allowed range 25–35 s; DB value agar is range me hai to
     respect karo, warna 30 par normalize karo).
   - Rest: 20 s default.
   - Rest cadence: har 3 exercises ke baad (existing behaviour, mat todo).
   - Get-ready: 10 s, total budget me count hoga.
   - Algorithm: exercise queue ko **cycle** karo jab tak budget bhare. Agar workout me
     6 exercises hain aur 15 min chahiye → 2 rounds chalao (round 2 me preview skip,
     kyunke `previewed` Set already ye handle karta hai — `player.js:31`).
   - Final block ko truncate karke adhoora mat chhodo — nearest complete block par khatam karo,
     aur actual total UI me dikhao ("Your session: 14:40").
   - **Tolerance ±45 s** target ke. Agar fit na ho pae, actual duration dikhao, jhoot mat bolo.

3. **Rest overlay controls — teen buttons, ek row me:**
   ```
   [ + 20s ]   [ SKIP REST ▶ ]   [ ⏸ PAUSE ]
   ```
   - `SKIP REST` = `remain = 1` (same as `skipPhase()`), lekin **sirf rest phase me** —
     `ready` phase par skip allow karo lekin `+20s` nahi (already correct at `player.js` `enterReady()`).
   - `+20s` ka cap: **max +60 s per rest**. Warna user infinite rest karke "workout complete"
     claim kar lega. Cap hit hone par button disable + tooltip.
   - Har button `active:scale-95` + haptic-ish micro-animation, existing button classes reuse karo.

4. **Rest me progress context.** Overlay par abhi sirf "REST / 20 / next exercise" hai. Add karo:
   - Block indicator: "Block 2 of 4"
   - Remaining session time: "6:20 left"
   - Next-next preview: "Then: Mountain Climbers"

5. **State machine ko explicit karo.** Abhi phase strings hain (`'idle'|'preview'|'ready'|'ex'|'rest'`)
   aur `tick()` me nested ifs. Ek `const PHASE = Object.freeze({...})` banao aur transitions ko
   ek `transition(from, to)` function me route karo. Kyun: `player.js:180-186` ka comment
   khud batata hai ke ek missing guard ne exercise **silently skip** kar di thi. Explicit
   machine me wo class of bug structurally impossible ho jaata hai.

6. **Pause ko real karo.** `togglePause()` sirf ek boolean flip karta hai — 3D animation
   chalti rehti hai. Pause par rig ko bhi freeze karo (`TF_PREVIEW.scale = 0` ya rig clock hold).

7. **Session save.** `api/save-workout.php` ko `duration` aur `kcal` bhejta hai
   (`player.js` `finishWorkout()`). Ab `target_seconds`, `blocks_completed`,
   `rest_added_seconds`, `skips_used` bhi bhejo — `workout_logs` me columns add karo.
   Ye admin analytics (Phase L) ke liye chahiye. **Aur is API par CSRF lagao** — abhi nahi hai.

### G.3 Verification — ye phase manual test maangta hai
- 10 min select karke poori session chalao, stopwatch se compare karo. Log: target vs actual.
- 15 min par ek 6-exercise workout chalao → confirm 2 rounds bane, exercise skip na hui.
- Rest me `+20s` 4 baar dabao → 3rd ke baad disabled ho.
- Rest me `SKIP REST` dabao → seedha next exercise, kcal accounting sahi rahe.
- Pause 30 s → resume → total elapsed me wo 30 s count na hon.
- Console me koi `console.assert` fail na ho.

**STOP. Wait for "APPROVED G".**

---

## PHASE H — CALCULATOR SUITE

**Goal:** `pages/calculators.php` abhi sirf **4** calculators deta hai (BMI, BMR/TDEE,
Water, Macro Split) — 74 lines, saara markup inline. Isko ek proper suite banao.

### H.1 Add these (sab client-side, koi API nahi — instant results)

| Calculator | Formula (use exactly this) |
|---|---|
| Body Fat % | US Navy method (waist/neck/height, female me hip) |
| Lean Body Mass | Boer formula |
| Ideal Body Weight | Devine + Hamwi dono dikhao, range ke tor par |
| TDEE + Goal | BMR × activity, phir cut −20% / maintain / bulk +10% |
| One-Rep Max | Epley **aur** Brzycki, dono ka average + %-of-1RM table (95/90/85/80/75/70) |
| Target Heart Rate | Karvonen (resting HR ke sath), 5 zones ke sath |
| Calories Burned | MET × weight(kg) × hours; MET table exercises ke liye |
| Waist-to-Hip Ratio | ratio + WHO risk category |
| Waist-to-Height Ratio | ratio + risk band |
| Protein Requirement | g/kg by goal (sedentary 0.8 → athlete 2.2) |
| Macro by goal | current Macro Split ko goal-aware banao (keto/balanced/high-protein presets) |
| Pace / Distance | pace ↔ speed ↔ time, km + miles |
| Water (upgrade) | existing ko activity + climate factor do |

### H.2 Structural work — ye zyada important hai

1. **Formulas ko ek jagah karo.** Naya `assets/js/calculators.js`: har calculator ek
   pure function jo object leta hai aur object deta hai. Koi DOM access nahi.
   Kyun: abhi `calcBMI()` etc. inline `onclick` se bandhe hain aur formula UI me ghusi hui hai —
   test karna namumkin hai, aur `member/diet-planner.php` bhi shayad wahi math duplicate
   kar raha hai. **Grep karo** aur duplicate math ko is single module par point karo.
2. **Server-side mirror.** `includes/calculators.php` me wahi formulas PHP me, kyunke
   `member/diet-planner.php` aur `member/dashboard.php` ko server par bhi ye chahiye.
   Dono implementations ke liye ek shared test vector table `_audit/calc-vectors.md` banao
   (input → expected output) aur dono ko usi ke against verify karo.
3. **Unit toggle.** Metric ⇄ Imperial, poore page par ek switch, choice localStorage me.
4. **Layout.** 74-line inline blob ki jagah: categorized tabs
   (Body Composition · Energy · Performance · Hydration), har calculator ek
   `.cca-card`, results me plain number nahi — **interpretation** (category, healthy range,
   ek line ka guidance). Number ke sath meaning na ho to calculator bekaar hai.
5. **Hero image.** `pages/calculators.php:7` me hardcoded Unsplash URL hai —
   external dependency + slow. Local asset se replace karo (`assets/images/` me already
   `industrial_gym.jpg` hai) ya CSS gradient. **Poore repo me aise hardcoded Unsplash
   URLs grep karo** aur list do (mujhe pata hai `player.php:131` me bhi ek hai).
6. **Save to profile.** Logged-in user apna result profile me save kar sake
   (`user_progress` table already exists — usko use karo, naya mat banao).
   Naya API `api/save-calc.php` — CSRF + require_login.

### H.3 Verification
- Har calculator ke liye 3 test vectors, expected vs actual table `_audit/PHASE_H_VECTORS.md` me.
- JS aur PHP dono ka output identical (rounding tak).
- Metric/Imperial toggle round-trip: value badle nahi.

**STOP. Wait for "APPROVED H".**

---

## PHASE I — PORTAL QUICK-ACCESS STRIP + SIDEBAR

**Goal:** har portal ke hero ke neeche ek **patti** (horizontal quick-access bar) ho jo us
portal ke saare pages tak le jaye, plus sidebar consistent ho.

### I.1 Current state
`includes/functions.php:294-306` me `portal_nav()` already hai aur 20+ portal pages usko
call karte hain. `nav_icon()` (line 309+) 18 icons deta hai. Matlab **foundation mojood hai** —
naya system mat banao, isko upgrade karo.

`includes/header.php:23-75` me navbar + sidebar menus role ke hisaab se define hote hain,
aur deliberately **disjoint** hain (comment line 18-21). Ye rule maintain karna hai.

### I.2 Required work

1. **`portal_nav()` ko upgrade karo:**
   - Horizontal scroll on mobile (`overflow-x:auto`, snap points, edge fade masks)
   - Active item: accent underline + subtle glow, portal ke apne accent color se
     (`assets/css/portals/<role>.css` me already per-portal accents hain — wahi use karo)
   - Badge support: unread counts (Notifications), pending counts (Doctor queue, Admin reviews).
     `unread_count()` already `functions.php:167` me hai.
   - Keyboard: arrow keys se navigate, `Enter` se open
   - Sticky: hero se neeche scroll karne par patti top par chipak jaye (navbar ke neeche),
     `position: sticky; top: var(--nav-h)`

2. **Har portal ka complete link set define karo.** Abhi har page apna array khud pass karta
   hai → drift ka guarantee. Ek central function banao:
   ```php
   function portal_links(string $portal): array   // includes/functions.php
   ```
   Har portal ke liye canonical list, ek jagah. Phir `portal_nav($portal, portal_links($portal))`.
   Audit karo ke aaj kaunse pages patti me **missing** hain — mujhe strong shak hai ke
   har portal ke saare pages listed nahi.

   Expected sets (verify + complete karo):
   - **member**: Dashboard · Workouts · Diet Planner · Appointments · Trainers & Doctors · Billing · Trainer Studio · Progress
   - **trainer**: Dashboard · Client Roster · Routine Creator · Earnings & Payouts · Reviews & Ratings
   - **doctor**: Dashboard · Patient Queue · Consultations · Prescriptions · Financials · Ratings
   - **patient**: Dashboard · Doctors · Appointments · Prescriptions · Vitals Log
   - **admin**: Dashboard · User Management · Appointments Master · Exercise Library · Wardrobe Admin (Phase F) · Reviews Moderation · Monetization · Platform Settings

3. **Sidebar.** `header.php` ka `$sideMenu` already role-aware hai. Fix:
   - Collapsible groups (Tools / Account / Support), state localStorage me
   - Icon + label, collapsed mode me sirf icon + tooltip
   - Mobile par off-canvas drawer, backdrop, `Esc` se band, focus trap
   - Active state patti ke sath **conflict na kare** — disjoint rule maintain

4. **Hero ke neeche placement.** Har portal dashboard ke hero section ke turant baad patti
   inject ho. Ek shared partial banao `includes/portal-hero.php` jo hero + patti dono render
   kare, taake 20 pages me copy-paste na ho.

### I.3 Verification
- Har 5 portals ka screenshot: desktop + mobile, patti visible aur scrollable.
- Har portal me patti ke har link par click → correct page, 200, koi 404 nahi.
- Grep proof: `portal_nav(` ka har call site ab `portal_links(` use kar raha ho.

**STOP. Wait for "APPROVED I".**

---

## PHASE J — SECURITY CONSISTENCY & HARDENING (P2 — revised down from P0)

**Goal:** ~~registered portals ko actually safe karo~~ → **portals already guarded hain**
(§J.1.a). Asal kaam: teen competing CSRF idioms ko ek me lao, aur cost-bearing endpoint
(`scan.php`) par throttle lagao. Ye phase ab **emergency nahi** — dekho §J.4.

### J.1 CORRECTED FINDINGS — v3 draft ke teeno "gaps" false positive thay

> **Ye section poora replace ho chuka hai (2026-07-31).** Neeche jo tha wo grep ki galti
> par bana tha. Har entry ab file padh kar verify hui hai. Purani list dobara mat use karo.

#### J.1.a Jo claim kiya gaya tha vs jo asal me hai

| v3 draft ka claim | Reality | Proof |
|---|---|---|
| **P0** — `admin-set-plan.php` me role check nahi, koi bhi user plan `elite` kar sakta hai | **FALSE.** Inline admin gate mojood hai, non-admin ko 403 milta hai. CSRF bhi hai. | `api/admin-set-plan.php:8` — `if (!is_logged_in() \|\| (current_user()['role'] ?? '') !== 'admin') { http_response_code(403); … exit; }` aur `:9` — `csrf_verify();` |
| **P0** — `admin-warn.php` — same | **FALSE.** Same gate. | `api/admin-warn.php:7` `csrf_verify_json();` + `:8` inline admin check → 403 |
| **P1** — 9 endpoints par CSRF nahi | **FALSE, saare 9 par hai** — inline `hash_equals` se. | `chat.php:13`, `feedback.php:8`, `scan.php:15`, `save-workout.php:11`, `notifications.php:13`, `log-food.php:10`, `food-lookup.php:10`, `community-post.php:9`, `report-issue.php:8` |
| **P1** — `cart-add`, `wishlist-toggle` par auth nahi | **FALSE.** Dono `is_logged_in()` par 401 dete hain. | `api/cart-add.php:16-17`, `api/wishlist-toggle.php:16-17` |

**Root cause:** audit ne `grep -c "csrf_verify_json"` / `grep -c "require_login"` chalaya tha.
Wo grep `csrf_verify()` aur inline `hash_equals` dono ko miss karta hai, aur inline
`is_logged_in()` guard ko bhi. Isi liye §0.1.1 kehta hai *"agar file padhi nahi, us par baat mat karo"* —
ye us rule ka live example hai.

**Matlab: is project me abhi koi known privilege-escalation ya CSRF hole nahi hai.**
Phase J ab "emergency patch" nahi, "consistency + hardening" phase hai. Priority Appendix 3 me
J ko A ke foran baad rakhti thi — **ab wo urgency justified nahi**; dekho §J.4.

#### J.1.b Jo gaps ASAL me hain (verified)

**H1 — Teen competing CSRF idioms (maintainability, P2).**
`csrf_verify()` (6 files), `csrf_verify_json()` (7 files), inline `hash_equals` (9 files)
= 22. Baaki 1 = `stripe-webhook.php`, jo `hash_equals` **Stripe signature** ke liye use karta hai,
CSRF ke liye nahi — wo legitimately excluded hai (external caller). Total 23. ✓
Counts verified: `grep -lE "csrf_verify\(\)" api/*.php | wc -l` → 6, etc.
Nuqsaan theoretical nahi hai: **isi inconsistency ne is document ke do false P0 paida kiye.**
Jo codebase apne aap ko audit nahi karne deta, wo agli baar asli hole bhi chhupa dega.
→ Fix: §J.2.1 ka `api/_bootstrap.php`. Remedy wahi hai, justification badla hai.

**H2 — `api/chat.php` par koi login guard nahi — ~~gap~~ → CLOSED, by design.**
CSRF hai (`:13`), auth nahi. File ka apna comment (`:3-6`) kehta hai ye
**deliberately visitor-aware** hai, aur `resolve()` ek local keyword matcher hai —
koi outbound API call nahi, koi paisa nahi lagta.
→ **FAISLA (owner, 2026-07-31): login guard NAHI lagana. Chatbot public rahega.**
Ye ab finding nahi hai. Kisi bhi future phase me `require_login()` `chat.php` par
**mat lagao** — ye intentional hai, oversight nahi.

**H3 — Rate limiting sirf login par hai (P2, ye asli paisa-wala risk hai).**
`login_is_throttled()` (`functions.php:48`) sirf ek jagah use hota hai: `auth/login.php:11`.
`api/scan.php` ek **real outbound Anthropic call** karta hai —
`api/scan.php:94` → `curl_init('https://api.anthropic.com/v1/messages')`, model
`claude-haiku-4-5` (`:84`), key `.env` ke `ANTHROPIC_API_KEY` se (`:81`).
Us par koi throttle nahi.
→ Mitigating: `scan.php:11-12` pro + member + CSRF teeno maangta hai, to abuse ke liye
**paid account chahiye** — yani ye P0 nahi. Lekin ek pro user tumhara pura API budget jala sakta hai.
→ `api/food-lookup.php` me koi outbound call **nahi** hai (grep clean) — usay throttle ki zarurat nahi.

### J.2 Systematic work — spot fixes se aage jao

1. **Ek API bootstrap banao.** `api/_bootstrap.php`:
   ```php
   <?php
   require_once dirname(__DIR__) . '/config/config.php';
   header('Content-Type: application/json; charset=utf-8');
   header('X-Content-Type-Options: nosniff');
   if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit(json_encode(['ok'=>false,'err'=>'method'])); }
   ```
   Har API file line 1-2 par isko require kare, phir apna `require_login()` /
   `require_role()` / `csrf_verify_json()` declare kare. Exception: `api/stripe-webhook.php`
   (external caller, signature verification alag hai — usay bootstrap se **exclude** karo
   aur file ke top par comment likho ke kyun).

2. **Rate limiting.** `auth_attempts` table + `login_is_throttled()` already login ke liye
   hai (`functions.php:48`, single call site `auth/login.php:11`). Ise generalize karo —
   **lekin sahi targets par** (v3 draft ne yahan ghalat endpoints chune thay):

   | Endpoint | Throttle chahiye? | Wajah (verified) |
   |---|---|---|
   | `api/scan.php` | **HAAN — pehli priority** | Real outbound Anthropic call, `scan.php:94`. Har request par paisa lagta hai. |
   | `api/feedback.php` | Haan (halka) | DB write, spam vector. Koi API cost nahi. |
   | `api/report-issue.php` | Haan (halka) | Same — DB write, spam vector. |
   | `api/chat.php` | **NAHI** | `resolve()` ek local keyword matcher hai, koi outbound call nahi (`chat.php:3-6`). Zero cost. v3 draft ne isay ghalti se "Claude API" samjha tha. |
   | `api/food-lookup.php` | **NAHI** | Koi outbound call nahi — grep clean. |

3. **Ownership checks.** Har API jo `id` parameter leta hai, verify karo ke wo record
   **is user ka** hai. IDOR ka classic case: `api/appointment-action.php`,
   `api/cart-add.php`, `api/wishlist-toggle.php`. Har ek me `WHERE ... AND user_id = ?`
   confirm karo — sirf `require_login()` kaafi nahi.

4. **Session hardening.** `config/config.php` me:
   `session.cookie_httponly=1`, `session.cookie_samesite=Lax`, `session.use_strict_mode=1`,
   `session_regenerate_id(true)` login ke baad (check karo `auth/login.php` me hai ya nahi).

5. **Upload hardening.** `uploads/` me 6 user files hain. Add karo `uploads/.htaccess`:
   ```apache
   php_flag engine off
   <FilesMatch "\.(php|phtml|phar|cgi|pl|py|sh)$">
     Require all denied
   </FilesMatch>
   ```
   `validate_image_upload()` (`functions.php:227`) ko audit karo: sirf extension check kar
   raha hai ya real MIME (`finfo`) + dimensions bhi? Filename randomize hota hai ya user ka
   naam use hota hai?

6. **Output escaping sweep.** `e()` helper mojood hai. Har `echo`/`<?=` jo DB ya user input
   print karta hai aur `e()` se nahi guzra — list banao aur fix karo. Ye grep-able hai.

7. **`.env` exposure.** `.gitignore` ab **§0.4 pre-flight** me ban chuka hoga — yahan sirf
   verify karo ke `git status --porcelain -uall | grep "^?? \.env$"` khali hai.
   Web layer par `.env` already blocked hai (`.htaccess:6` ka `FilesMatch`) — us rule ko mat todo.
   **Key rotation:** repo kabhi git me tha hi nahi, is liye koi secret history me nahi gaya —
   `ANTHROPIC_API_KEY` rotate karna **abhi zaroori nahi**. Sirf tab zaroori hoga agar
   §0.4 ka order tor kar `git init && git add -A` pehle chal gaya ho. Us surat me
   mujhe foran batao. (`.env` me 20 keys hain: Anthropic, Stripe ×4, Google/Facebook/Instagram OAuth ×6, DB ×6.)

8. **Error handling.** `logs/db-error.log` mojood hai. Confirm karo ke production me
   `display_errors=off` (`.htaccess` me hai, lekin `config/config.php` bhi check karo)
   aur koi API stack trace JSON me leak na kare.

### J.3 Verification
- Har refactored endpoint par CSRF ke baghair `curl` maro → **419** aana chahiye
  (403 nahi — `csrf_verify_json()` `functions.php:29` par 419 return karta hai).
  Commands + output do.
- Non-admin session se `api/admin-set-plan.php` call karo → 403.
  **Note:** ye test aaj bhi pass hona chahiye, refactor se pehle bhi — kyunke gate already mojood hai
  (§J.1.a). Agar fail ho to tumne kuch tor diya hai.
- Ek user ke session se doosre user ki appointment ID par action → denied.
- `api/scan.php` par throttle limit se zyada requests → 429.
- `_audit/PHASE_J_SECURITY.md` me before/after matrix, teeno CSRF idioms ka column ke sath.

### J.4 Phase order par asar — ye padho

Appendix 3 kehta hai *"J ko A ke foran baad chalao"*, aur agar time kam ho to
`A → J → K → D → G → I → N` shippable subset hai. **Wo priority ab valid nahi rahi**,
kyunke wo assume karti thi ke live privilege-escalation hole hai. §J.1.a ne prove kiya ke nahi hai.

Corrected guidance:
- Agar tumhara maqsad **user-visible progress** hai → J ko baad me le jao. Recommended:
  `§0.4 → A → D → G → I → J → K → N`. (D = solid 3D, G = workout timing, I = portal patti —
  teeno user ki asal shikayaat hain.)
- Agar tumhara maqsad **audit-readiness** hai → J apni jagah rakho, lekin usay
  "emergency" ki tarah mat treat karo. Sirf H1/H3 (§J.1.b) actionable hain.
- H2 (`chat.php` login guard) ek **product decision** hai, security bug nahi — mujh se poochho.

**STOP. Wait for "APPROVED J".**

---

## PHASE K — DEAD WEIGHT REMOVAL

**Goal:** repo se wo sab nikalo jo product ka hissa nahi. Baseline: `_audit` = 57 MB,
`assets/videos` = 153 MB.

### K.1 Candidates — har ek ke liye deletion table bharo (rule 0.1.3)

**Almost certainly delete (dev artifacts, web root par exposed):**
```
dev-rig-shot.html                              (root par, 5.7 KB, dev tool)
trainer-viewer.html                            (root par, 13.7 KB, dev tool)
CORE_CALORIE_ADVISOR_MASTER_PROMPT.md          (29 KB, superseded)
CORE_CALORIE_ADVISOR_MASTER_PROMPT_v2_...md    (29 KB, superseded)
MASTER_PROMPT_for_Antigravity.md               (7 KB, different tool)
_audit/_backup-2026-07-30.zip                  (backup zip repo ke andar)
_audit/*.mjs, _audit/fbx-*.html                (throwaway test scripts)
_audit/*.png                                   (evidence — KEEP the garment-fail ones)
```
> `.htaccess` `_audit/` ko block karta hai, lekin defence-in-depth: files jo web root me
> nahi honi chahiye, unko repo se hi nikal do. Ek `.gitignore` + local `_local-dev/` folder banao.

**Investigate before touching:**
- `assets/videos/` — **153 MB**. `Athletic_body_training_in_gym_202607221638.mp4` ka naam
  generated-file jaisa hai. Grep karo ke kaunsi videos actually reference hoti hain.
  Jo use ho rahi hain unhe compress karo (H.264 CRF 28, 720p, no audio agar muted loop hai) —
  hero loop 10 MB se zyada nahi hona chahiye.
- `assets/models/trainers.glb` + `trainer-ch06.glb` — agar Phase E ke roster me nahi aaye
  to delete. Lekin `trainers.glb` fallback hai (`pages/player.php:406`) — pehle wo reference hatao.
- `pages/about.php` — orphan. Ya footer se link karo (site ko about page chahiye) ya delete.
- 7 orphan APIs (Phase A list) — **`stripe-webhook.php` ko chhorna**, baqi ke liye
  faisla: wire up (Phase M) ya delete.

**Per-portal cleanup:** har portal folder me confirm karo ke koi file aisi na ho jo us
role ke liye relevant nahi. Abhi structure clean lagta hai (5-6 files per portal) —
lekin verify karo, assume mat karo.

### K.2 Rules
- **PRE-REQ: §0.4 pre-flight chal chuka ho.** Bina git ke ye phase **execute mat karna** —
  ~200 MB delete karne ka koi undo path nahi hoga. Agar `git log` khali hai, ruko.
- Ek hi commit me sab delete mat karna. Group-wise: docs → dev tools → orphan APIs → media.
- Har group ke baad site chala kar confirm karo (har portal ka dashboard load ho), phir commit.
- `git rm` use karo, filesystem delete nahi, taake revert possible ho.
- **Ye file khud (`MASTER_PROMPT_v3_PREMIUM.md`) delete list me nahi hai** — K.1 purane
  master prompts (v1, v2, Antigravity) ko target karta hai, is v3 ko nahi.

### K.3 Verification
- Before/after `du -sh` per folder.
- Har portal ka smoke test: login → dashboard → patti ka har link → koi 404/500 nahi.
- `grep -rn "<deleted-filename>"` → zero hits.

**STOP. Wait for "APPROVED K".**

---

## PHASE L — ADMIN ↔ PORTAL WIRING

**Goal:** admin ke paas har portal par real control ho, aur har portal admin se properly
connected ho.

### L.1 Required audit
Har portal ke liye ye 4 sawal jawab do (evidence ke sath):
1. Admin is portal ke users ko dekh/edit/suspend kar sakta hai? (`admin/user-management.php`)
2. Is portal ka content admin manage karta hai? (workouts → `admin/exercise-library-admin.php`;
   characters/outfits → Phase F ka naya page; shop items → **koi admin page hai?**)
3. Is portal ke financials admin ko visible hain?
   (`trainer/earnings-payouts.php` + `doctor/financials.php` ↔ `admin/monetization-stripe.php`;
   `payout_requests` table exists, `api/request-payout.php` orphan hai — matlab
   **payout approval ka admin UI missing hai**. Confirm karo.)
4. Is portal ke user-generated content ki moderation hai?
   (`community_posts`, `reviews`, `feedback`, `issue_reports` → `admin/reviews-moderation.php`
   sirf reviews karta hai. Community posts aur issue reports ki moderation kahan hai?)

### L.2 Likely missing (Phase A/M me confirm karo)
- **Payout approval queue** — `payout_requests` table + `api/request-payout.php` mojood,
  admin UI nahi.
- **Community moderation** — `api/community-post.php` (CSRF bhi nahi) + `community_posts`
  table, admin moderation page nahi.
- **Issue reports inbox** — `issue_reports` table + `api/report-issue.php`, admin view nahi.
- **Platform settings UI** — `platform_settings` table + `get_setting()`/`set_setting()`
  (`functions.php:237-253`) mojood, admin page nahi. Commission rate, feature flags,
  maintenance mode — sab yahan se control hone chahiye.
- **Warnings** — `warnings` table + `api/admin-warn.php` (orphan!), UI nahi.

### L.3 Work
Har confirmed gap ke liye admin page banao, existing admin pages ke pattern par
(`admin/user-management.php` ko template samjho). Har naye admin page par:
`require_role('admin')` pehli line, CSRF har form par, pagination, search, aur
**audit trail** — kaun ne kya badla (agar `platform_settings` me log column nahi to
ek chhota `admin_actions` table add karo).

### L.4 Verification
- Har admin page: non-admin session se access → redirect/403.
- Har admin action: DB me change confirm karo (before/after query output).
- Round-trip test: trainer payout request kare → admin ko queue me dikhe → approve kare →
  trainer ke wallet me reflect ho (`credit_wallet()` `functions.php:264` already hai).

**STOP. Wait for "APPROVED L".**

---

## PHASE M — MISSING FILES & BROKEN CONNECTIONS

**Goal:** jo cheezein half-built hain unhe complete karo.

### M.1 Known signals
1. **Orphan APIs = missing UI.** Phase A ki list me se har wo API jiska faisla "wire up"
   tha, uska entry point banao (button/form/page).
2. **`assets/models/anims/` folder khali hai** (sirf README). `player.js` aur `titan3d.js`
   dono is path se clips load karne ki koshish karte hain (`player.php:435`) aur silently
   404 khaate hain. Faisla: ya to procedural rig hi official raasta hai (to loader code aur
   README dono ko truth se match karao), ya clips add karo. **Silent 404s acceptable nahi.**
3. **README drift** — `assets/models/README.md` `trainer.glb` kehta hai, file `trainers.glb` hai.
4. **`pages/about.php`** orphan — footer me link nahi.
5. **`includes/auth_check.php`** sirf 5 lines ka wrapper hai jo `require_login()` call karta
   hai. Grep karo kaun use kar raha hai — agar koi nahi, delete; agar kuch pages, to unhe
   direct `require_login()` par le aao aur file hatao.

### M.2 Systematic sweep
- **Har link check karo.** Ek script likho jo saare `href=` aur `action=` nikale aur
  filesystem/route ke against verify kare. Output `_audit/PHASE_M_LINKS.md`.
- **Har fetch() check karo.** JS me har `fetch(` ka target API file exist karti hai?
- **Har DB table check karo.** 33 tables hain. Har table ke liye: koi code isko read karta
  hai? koi code isko write karta hai? Jo table sirf schema me hai aur kahin use nahi —
  ya feature missing hai ya table dead hai. Table do.
- **Har `include`/`require` check karo.** Koi missing file to nahi.

### M.3 Verification
- Zero broken internal links.
- Zero 404s browser network tab me, har portal ke har page par.
- `_audit/PHASE_M_LINKS.md` me before/after count.

**STOP. Wait for "APPROVED M".**

---

## PHASE N — PREMIUM DESIGN PASS & FINAL QA

**Goal:** ab jab structure sahi hai, tab polish. Pehle nahi — polish on top of broken
structure = waste.

### N.1 Design (functionality freeze — pixels only)
- **Type scale.** Ek modular scale define karo (1.25 ratio), har heading usi se.
  Display font sirf headings + numbers ke liye, body ke liye nahi.
- **Spacing scale.** 4px base, `--space-1..12` tokens. Arbitrary padding values grep karke hatao.
- **Elevation.** 3 levels (flat / raised / floating). Har card ek hi level use kare.
  Abhi `shadow-xl` har jagah hai — ye hierarchy khatam kar deta hai.
- **Motion.** Ek easing token (`cubic-bezier(.32,.72,.28,1)` — already `intro.php` me use ho raha),
  3 durations (120/240/420 ms). Scroll reveal: `IntersectionObserver`, 60 ms stagger,
  translateY 12px + opacity. **Sirf ek baar**, har scroll par nahi.
- **Empty states.** Har list/table ka empty state design karo — abhi shayad blank hai.
- **Loading states.** Skeleton shimmer, spinner nahi (spinner 2026 me cheap lagta hai).
- **Focus rings.** Har interactive element par visible focus, `outline:none` grep karke hatao.

### N.2 Final QA checklist (har item par proof)
```
[ ] Har portal ka har page: 200, koi PHP notice/warning nahi
[ ] Har form: CSRF token present, submit works, validation errors readable
[ ] Har API: correct status code, JSON shape consistent
[ ] Mobile 360px: koi horizontal scroll nahi, koi text clip nahi
[ ] Dark + light theme dono: koi unreadable contrast nahi (WCAG AA)
[ ] Keyboard-only: har portal navigate ho jaye, focus visible rahe
[ ] 3D scene: character solid, floor stable, 30+ fps
[ ] Workout: 10 min aur 15 min dono target ±45s ke andar
[ ] Intro: ≤2.6s, skippable, once per session
[ ] Console: zero errors, zero 404s
[ ] Repo size: baseline se kam
```

### N.3 Final deliverable
`_audit/FINAL_REPORT_v3.md`:
- Har phase ka summary (kya bana, kya delete hua, kya defer hua)
- Before/after metrics table (repo size, file count, security gaps, page count, LOC)
- **Known limitations, honestly** — jaise wardrobe ki garment-mesh limitation. Jo cheez
  kaam nahi karti usay "done" mat likhna.
- Next 5 recommendations, priority order me
- 10-line Roman Urdu summary

**STOP. Wait for "APPROVED N".**

---

## APPENDIX 1 — Kickoff message (copy-paste)

```
Read CLAUDE.md and MASTER_PROMPT_v3_PREMIUM.md completely before doing anything.

Confirm you understand by answering these 5 questions in one short paragraph each.
Answer ONLY from files you have actually opened. Cite path:line for each.

1. What are the 5 portals and where do their real pages live?
2. Why is the wardrobe system a texture swap and not separate garment meshes?
3. How many DIFFERENT CSRF-verification idioms does api/ use, what are they, and why
   does grepping for csrf_verify_json alone produce false "unprotected" findings?
4. What is the current hardcoded total session cap, and in which file and line?
5. Is this folder a git repository? What must happen BEFORE `git init` is run, and why?

If you cannot answer any of these from the actual code, say so — do not guess.
(Q3 and Q5 are traps for exactly the mistake that produced this document's false P0s.
 If you answer Q3 with "9 endpoints have no CSRF", you have grepped instead of read.)

Then run §0.4 PRE-FLIGHT, then execute PHASE A only. Produce _audit/PHASE_A_BASELINE.md.
Do not edit a single existing file in Phase A.
Stop and wait for my written "APPROVED A".
```

## APPENDIX 2 — Course-correction phrases

| Situation | Bolo |
|---|---|
| Agent bina padhe assume kar raha hai | `Stop. You have not read <file>. Read it and cite line numbers, then re-answer.` |
| Agent ne 2 phases mila diye | `You broke rule 0.1.2. Revert everything outside Phase <X> scope and re-report.` |
| Agent ne "should work" kaha | `Rule 0.1.10. Show me the command and its output, or mark it unverified.` |
| Agent ne placeholder daala | `Rule 0.1.6. Remove the placeholder. Either wire real data or ship nothing.` |
| Agent ne kuch delete kiya bina table ke | `Rule 0.1.3. git revert. Produce the deletion table first.` |
| Agent bhatak gaya | `Re-read PHASE <X> section. List its acceptance criteria. Tell me which ones you have met.` |
| Agent ne grep count ko finding bana diya | `You grepped, you did not read. Open the file and quote the actual guard lines. See §0.3.1.` |
| Agent security hole claim kar raha hai | `Show me the exact line that is missing, and the exact line that would exploit it. If you cannot write the exploit request, it is not a finding.` |
| Agent `git init` chalane laga | `Stop. §0.4 pre-flight. .gitignore FIRST or you commit .env with 20 live secrets.` |

## APPENDIX 3 — Phase order (mat badalna)

> ### ✅ APPROVED BY OWNER — 2026-07-31. Ye order ab binding hai.
> **REVISED 2026-07-31.** Neeche wali original order J ko sab se upar rakhti thi kyunke
> hum samajhte thay ke live privilege-escalation hole hai. §J.1.a ne prove kiya ke **wo hole
> mojood hi nahi** — teeno "gaps" grep ki galti thay. Naya recommended order:
>
> ```
> §0.4 pre-flight  (git gate — mandatory, sab se pehle)
> A  baseline
> D  solid 3D          ┐  user ki 3 asal shikayaat
> G  workout timing    │  (see-through character, rest skip, portal nav)
> I  portal patti      ┘
> J  security          (consistency refactor — ab urgent nahi, §J.4)
> K  cleanup           (sirf §0.4 ke baad)
> B → C → E → F → H → L → M → N
> ```
> Shippable subset agar time kam ho: **§0.4 → A → D → G → I → N**.

Original v3 order (reference ke liye rakha gaya, follow mat karo):

```
A  baseline        ──┐
J  security          │  ye do sab se pehle — J ko A ke foran baad
K  cleanup           │  chalao agar time kam ho
                   ──┤
D  solid 3D          │  visible wins
G  workout timing    │
I  portal patti      │
                   ──┤
B  brand             │  identity
C  intro             │
                   ──┤
E  roster            │  feature depth
F  wardrobe          │
H  calculators       │
                   ──┤
L  admin wiring      │  completeness
M  missing files     │
N  polish + QA     ──┘
```
> ~~Agar poora scope na chalana ho: **A → J → K → D → G → I → N** ek complete,
> shippable subset hai.~~ — **superseded**, upar wala revised box dekho.
