# PHASE 11 — 3D trainer: status and the blocking asset question

**Date:** 2026-07-25

---

## ⛔ The blocking fact, stated first

You said the 3D asset is *"is project me hai, le lo."* **I searched the entire project. It is not there.**

```
find . -type f \( -iname "*.glb" -o -iname "*.gltf" -o -iname "*.fbx" -o -iname "*.vrm" -o -iname "*.obj" \)
```

Everything found — **only 2 unique files**, the rest are byte-identical copies:

| File | Size | What it actually contains |
|---|---:|---|
| `assets/models/trainers.glb` (+4 identical copies) | 6.5 MB | 6 meshes: `MocapGuy_Body`, `MocapGuy_BrowsLashes`, `MocapGuy_Caruncula`, `MocapGuy_Eyes`, `MocapGuy_Hat`, `MocapGuy_Teeth` · 4 materials · **1** animation |
| `assets/models/trainer.fbx` (+1 identical copy) | 785 KB | **Skeleton + animation only.** Parsed its object table: nothing but `mixamorig:` bones — no mesh, no material, no texture. |

**There is no shirt, no shorts, no shoes, and no hair anywhere in this project.**
`assets/js/titan3d.js:218` says so in its own comment: *"the GLB has no hair mesh"*. Clothing is
**painted into the `Body_MAT` texture**, sharing one material with the skin.

So "real human feel · best hair · best face + body · best clothes" is **not a code change** — it needs
a different `.glb`. I cannot author, download or commission a 3D character.

### What I need from you (one concrete thing)

A **rigged, Mixamo-compatible character `.glb`** whose clothing and hair are **separate meshes** with
their own material slots (so they can be shown/hidden/re-textured independently of skin). Drop it in
`assets/models/` and Phase 11 can proceed properly. Skeleton must use the `mixamorig:` bone names —
`assets/js/titan-rig.js` binds 20 bones by those exact names.

Free sources that meet this: Mixamo characters exported as glTF, or Ready Player Me avatars.
**Your call — I am not fetching anything without your say-so.**

### What I will NOT do

`TitanRig.setClothes` currently repaints the body texture atlas inside hardcoded UV rectangles, and
`SKIN_LIFT` lightens the character's **skin pixels** by up to 50%. Making the character "look better"
by tuning those is exactly the mistake Phase 11 exists to correct (*"the skin tone must never
change… do not fake it by tinting"*). I have not touched it.

---

## ✅ What I DID fix — the 3D trainer now performs again

You reported: *"workout 3d model pehle perform kar raha tha, ab nahi kar raha."* **Found and fixed.**

### Root cause: a half-completed rename that failed silently

- `assets/js/titan-rig.js:887` exported **`window.TitanRig`**
- `pages/player.php` had already been renamed to call **`window.CCARig`** — in **11 places**
- Every one of those call sites is written defensively: `if (window.CCARig && CCARig.x())`

So the mismatch produced **no error at all**. The guards simply evaluated false and the whole rig was
skipped. Consequences, all invisible in the console:

| Call that never ran | Effect |
|---|---|
| `CCARig.attach()` | Fell back to clip mode — and the anim `.glb` files are identical copies of the base model, so there were no real per-exercise clips. **The character stopped performing.** |
| `CCARig.update()` | No procedural motion at all |
| `CCARig.measure()` | Sizing fell back to `setFromObject`, which the code's own comment says *"mis-measures skinned Mixamo GLBs"* → **character placed off-camera, i.e. invisible** |
| `CCARig.styleModel()` / `hideProps()` | No material cleanup, mocap markers left on |

`trainer-viewer.html` and `dev-rig-shot.html` use `CCARig` too — which is why the Phase 8 §6b
screenshot showed **"Loaded ✓ (no rig — clip mode)"**. Same bug, now explained.

### Fix

`assets/js/titan-rig.js` now exports **`window.CCARig`** as canonical (matching the CCA rebrand) and
keeps `window.TitanRig` as an alias, because `assets/js/titan3d.js` still uses it. Same object, not a
copy — so nothing needed renaming at 14 call sites.

### Proof

| | Before | After |
|---|---|---|
| `pages/player.php?id=1` console | `[exactfit3d] Model loaded (clip mode), meshes: 7` | `[TitanRig] attached — 20/20 bones bound; procedural exercises enabled`<br>`[exactfit3d] Rigged human active — procedural exercises, meshes: 5` |
| Character on screen | **not visible** | visible and performing the crunch |

Screenshot: `_audit/after/phase11/player-after-start.png`
`index.php` was **already** working (`TitanRig attached — 20/20 bones bound`) — the bug was specific
to the pages that had been renamed.

---

## ✅ Crash fixed: `Unknown column 'subcategory'`

`pages/workouts.php:217/232/247` queried `workouts.subcategory`, which did not exist → clicking a
workout card threw an uncaught `PDOException`.

**The schema was not missing — the migrations had simply never been run.**
`sql/phase3-routines.sql` and `sql/phase4-fitify-overhaul.sql` both add the column and seed the
sectioned routines. Both are marked idempotent/additive by their own headers.

Backed up first (`_audit/db-backup/pre-migration-workouts-exercises.sql`, 7,278 bytes) because the
migrations rebuild exercise queues by slug, then ran both:

| | Before | After |
|---|---:|---:|
| `workouts` rows | 9 | **37** |
| `exercises` rows | 39 | **152** |
| `workouts.subcategory` | missing | present, **0 rows orphaned** |

Two rows the migrations' backfill missed (`yoga-stretch`, `knee-safe-strength`) were assigned
`yoga` / `recovery` from the existing vocabulary. All six category filters return **200** with no PHP
error.

---

## ✅ Notifications now cross portals

`notify()` and `pages/notifications.php` already existed, but **no portal linked to it** — it was
reachable only from the bell dropdown. And a purchase notified only the buyer, so nobody running the
platform learned about a sale from inside the app.

- **Notifications, Cart and Wishlist added to the sidebar of all five roles** (member, trainer,
  doctor, patient, admin).
- `api/buy-item.php` now notifies **every admin** as well as the buyer, each with a link into their
  own context. An admin buying something is not notified twice.

**Verified with a real purchase** as `member@corecalorieadvisor.com`:

```
#4  user1 (admin)  [system]  🛒 New shop order #3
#3  user2 (member) [info]    📦 Order Placed Successfully
order3 user2 address=House 4B, Street 12, DHA Phase 6, Karachi
```

That last line also confirms the **shipping-address data-loss bug** fixed earlier is genuinely
persisting now.

---

## ✅ Wishlist and Cart pages

New `pages/wishlist.php` and `pages/cart.php`, both built from the Phase 10 shared card system.

- Every mutation is **POST + CSRF** — a GET link would let any site empty a signed-in user's cart
  with an `<img>` tag.
- Quantity is clamped **server-side** (1–99), matching `api/cart-add.php`; the client value is never
  trusted.
- Real empty states with an icon and a route back to the shop, not a blank page.
- Cart shows line totals, subtotal and item count; checkout is honest that it processes one product
  at a time, because that is what `checkout-shop.php` actually does.

Screenshots: `_audit/after/phase11/{cart,wishlist,notifications}-{light,dark}.png`

---

## ✅ Teko unified — one display font, one less dependency

Corrected earlier: Teko **was** loaded, but only by `player.php` and `workout-detail.php` via their
own `<link>` tags. **24 references** across those two files now use `var(--font-disp)` (Russo One),
which the global header already loads.

- Teko removed from both Google Fonts URLs (Inter and JetBrains Mono on `player.php` kept — they are
  used by other classes and out of this scope).
- `workout-detail.php`'s font link became `css2?display=swap` with no families at all once Teko was
  removed — a dead external request. **Deleted.**
- `grep -rc Teko` across all `.php`/`.css`: the only remaining hit is the explanatory comment in
  `cca-cards.css`.

---

## Verified after all of the above

| Check | Result |
|---|---|
| Contrast, 7 pages × 2 themes | dark **35/35**, light **65/65** — 0 fail ✓ |
| HTTP status, 10 routes incl. all workout categories | all **200** ✓ |
| PHP errors on player / workout-detail | **0** ✓ |
| `php -l` on every edited file | No syntax errors ✓ |
| Purchase → buyer + admin notification | verified with real order #3 ✓ |
| Shipping address persisted | verified ✓ |

---

# ROUND 2 — database rename, cleanup, and a full browser sweep

## ✅ Database renamed: `titanforge` → `core_calorie_advisor`

Done with your explicit permission (it required editing `.env`, which `CLAUDE.md` normally puts
off-limits).

| Step | Result |
|---|---|
| Full dump before touching anything | `_audit/db-backup/titanforge-FULL-before-rename.sql` — 33 tables, 63,889 bytes |
| `.env` backed up | `_audit/db-backup/.env.before-rename` |
| New DB created + restored | **33 tables / 278 rows** — byte-for-byte match with the source |
| `.env` updated | `DB_NAME`, and `MAIL_FROM` → `no-reply@corecalorieadvisor.local` |
| `config/db.php` fallback | now `core_calorie_advisor` |
| Old name purged from SQL + docs | 13 further replacements across `database.sql`, all 4 `sql/*.sql`, `README.md`, `config/config.php` |
| App verified on new DB | `SELECT DATABASE()` → `core_calorie_advisor`; 10 users / 37 workouts / 3 orders intact |

**The old `titanforge` database has NOT been dropped.** It is still on the server as a live rollback.
Drop it only once you are satisfied.

## ✅ Cleanup — 26 MB removed, every deletion proven first

| Deleted | Proof it was safe |
|---|---|
| `anims/idle.glb`, `anims/squat.glb`, `anims/warmup.glb` | md5 `62383bea…` — **byte-identical to `trainers.glb`**. Not animations: copies of the character model carrying its single baked clip. |
| `anims/trainers.glb` | Same md5; `TRAINER_ANIMS` never contained `trainers`, so nothing requested it. |
| `anims/trainer.fbx` | Exact copy of `assets/models/trainer.fbx`, which is kept. |
| `dev-atlas.html` | `grep -rl` across the repo → **zero references**. |

**Kept deliberately:** `trainers.glb` (the real model), `trainer.fbx` (Mixamo source for any future
re-export), and `trainer-viewer.html` / `dev-rig-shot.html` — those two reference `CCARig` and became
functional again after the rename fix, so they are the debugging tools for the 3D work ahead.

Project size **252 MB → 226 MB**.

### The duplicates were not merely idle — they were actively harmful

`pages/player.php` requested **14** anim files. Only 3 existed, all identical copies. So every player
load fetched **~19.7 MB of redundant data** and 404'd 11 times, for zero distinct animation.
`TRAINER_ANIMS` is now `[]` with a comment explaining exactly when to repopulate it. Motion comes
from `CCARig`, which covers all 14 modes procedurally. Re-verified after deletion: rig still attaches
20/20 bones.

## ✅ Homepage 3D was silently falling back — found via the browser, not the test harness

My Playwright runs showed the rigged human on `index.php`. **Real Chrome showed the blocky procedural
stand-in instead.** Console:

```
[titan3d] trainer GLB unavailable — using procedural fallback: timeout
```

Diagnosed in the live page rather than guessed:

- Apache serves the 6.5 MB GLB in **0.06 s** — not a server problem.
- `fetch()` of the exact model URL from the page: **200, 6,556,756 bytes, 1.6 s** — not a URL problem
  (the space in the folder name is fine).
- The GLB declares **no extensions** — DRACO is irrelevant to it.
- `GLTFLoader.load()` measured *on an idle page*: **6.8 s** with DRACO attached, **7.9 s** without.

So parsing a 6.5 MB skinned GLB genuinely costs ~7–8 s, and during **initial page load** it competes
with the Tailwind CDN, Google Fonts, the hero video and the particle canvas — regularly blowing the
old **25 s** budget.

**Two fixes:** `initThree('hero3d')` now runs on `window.load` instead of inline, so the model no
longer races the page's other downloads; and the timeout is **60 s**, framed as a safety net for a
genuinely broken model rather than a slow one.

**Verified in real Chrome after the fix:** rigged human renders, and sampling bone rotations over ~1.2 s
while in `squat` proves it is actually animating —

```
rigAttached: true
movingBones: Spine, LeftArm, LeftUpLeg, LeftLeg   → ANIMATING ✓
```

(`Hips` stays fixed on purpose — root motion is stripped to stop floor-sliding.)

## ✅ Another dead page found by the sweep: Admin → Users

`admin/user-management.php` threw an **uncaught PDOException** on every visit:

```
Unknown column 'status' in 'field list' … user-management.php:4
```

`users` has never had a `status` column — in any migration. In `sql/phase5-monetization.sql` the
`status` ENUM belongs to **`reviews`**, not `users`. Line 31 of the same file already renders
`$u['status'] ?? 'active'`, so the display had always expected it might be absent; only the `SELECT`
hard-failed. Removed from the query — page restored, 10 users listed.
**Flagged, not invented:** the Status badge is now cosmetic until a real suspend/ban feature exists.

## ✅ Old brand name removed from the live auth pages

The login page still read **"WELCOME BACK TO CCAFORGE"**, with `CCA FORGE` in the mark, *"keep
forging"*, and an **"Enter The Forge"** button. Register had the same mark plus *"Forge Your Legacy"*.
All replaced with Core Calorie Advisor wording. `grep -c` on both files → **0**.

## Full browser sweep result

Every public route plus the admin portal, fetched and scanned for rendered PHP errors:

| Group | Routes | Result |
|---|---:|---|
| Public pages + all 6 workout category filters | 22 | **200, zero PHP errors** |
| Auth pages | 3 | **200, zero PHP errors** |
| Admin portal + new pages (cart, wishlist, notifications, receipt) | 14 | **200**, 1 fatal found → fixed (above) |

Final state: contrast **dark 35/35 · light 64/64**, 0 fail. `php -l` clean on every edited file.

---

## Known, and deliberately not changed

- **The old `titanforge` database still exists on the server**, untouched, as a rollback. Drop it
  yourself once you're satisfied with the rename.
- **Test data in the DB:** shop orders #1–#3 (one placed through the real checkout to verify the
  notification flow) plus the cart/wishlist rows from testing. **Remove before production**, along
  with the `cca123` demo accounts.
- **The outfit selector is disabled, not deleted.** Each option is probed with a `HEAD` request on
  panel build; all four 404, so both selects disable themselves with the tooltip *"No outfit models
  installed — add .glb files to assets/models/clothes/"*. Drop real outfit `.glb` files into that
  folder and they re-enable with **no code change**.
- **`TitanRig.setClothes` / `SKIN_LIFT` untouched.** It repaints the body texture atlas and lifts
  skin pixels. Tuning it to make the character "look better" is precisely what Phase 11 forbids, so
  it stays as-is until a real clothed model exists.

## Phase 11 — what is done vs what is blocked

| Phase 11 requirement | State |
|---|---|
| 3D trainer performs the exercises | ✅ **Fixed** (`CCARig` rename) and verified animating in real Chrome |
| Model actually loads on the homepage | ✅ **Fixed** (deferred init + realistic timeout) |
| Outfit-swap **system** exists and is honest about missing assets | ✅ Done — `TF_ModularClothing` intact, selector self-disables |
| No non-functional UI shipped | ✅ Done — the 404'ing dropdown now reports the truth |
| Skin tone never changed to fake clothing | ✅ Respected — nothing touched |
| **Per-exercise outfit swapping** | ⛔ **BLOCKED** — needs a `.glb` with separate clothing meshes. Not in this project (verified: 2 unique 3D files, neither has clothing or hair geometry). |
