# PHASE 16 — Trainer + outfit loadout system

Date: 2026-07-30. Status: **SHIPPED and verified.**
Renders: `_audit/loadout-hoodie-joggers-cap.png`, `_audit/loadout-tank-shorts-glasses.png`

---

## The real problem

Everything before this could only ever **repaint a texture** — which is exactly why it read as a
colour change and not a change of clothes. Both source characters fuse their entire outfit into a
single body mesh: there was no shirt mesh, no trouser mesh, no cap, nothing to show or hide.

Fixed properly: each trainer GLB now carries **real, separate garment meshes**.

---

## How the garments were made

`wardrobe.py` (Blender, headless). For each piece it duplicates the body, keeps only the vertices
whose **dominant skin weight** belongs to the target bones, then pushes those faces out along
their normals so the piece sits *on* the body rather than inside it.

Because the duplicate inherits the original vertex groups and armature modifier, every garment is
skinned to the **same skeleton** and animates for free — no weight transfer, no rebinding, which
is the step that usually destroys skin weights.

| slot | built from bones | Street | Pro |
|---|---|---|---|
| `top_tank` | Spine, Spine1, Spine2, Shoulders | 4,771 tris | 1,723 |
| `top_tee` | + Arm | 5,484 | 2,441 |
| `top_hoodie` | + ForeArm | 6,730 | 3,261 |
| `bottom_shorts` | Hips, UpLeg | 6,248 | 1,659 |
| `bottom_tights` | + Leg | 9,510 | 2,254 |
| `cap` | Head, top 11.5 cm of skull | 1,696 | 830 |
| `glasses` | frame built at the eye line, rigid-bound to the Head bone | 18 | 18 |

Also removed a stray `Icosphere` that shipped inside both GLBs — its z = −1 extent inflated the
model bounding box and skewed the runtime auto-fit.

Two trainers ship: **Street** (`trainer-street.glb`, 3.57 MB — tracksuit build, headphones) and
**Pro** (`trainer-pro.glb`, 5.89 MB — lean build, compression fit).

---

## Runtime — `assets/js/cca-wardrobe.js`

A garment swap is a **visibility toggle** on a model already in memory: instant, no download.
Colour is applied to the garment material at runtime, so any style + colour combination is free.
The GLB is re-fetched **only** when the trainer itself changes.

Slots: top (Base / Tank / T-Shirt / Hoodie) · bottom (Base / Shorts / Joggers) · cap (on/off) ·
glasses (on/off) · 6 colours each for upper and lower.
Saved to `localStorage` under `cca-loadout` and read by **both** 3D engines, so the character is
the same on the home page, the features page, member workouts and inside the workout.

## UI

A "Choose your trainer & kit" panel on the workout start screen (`pages/player.php`), with a live
draggable 3D preview and a Reset. Design is original, in an esports mood — **no third-party
game's marks, fonts or assets are used or imitated** (CLAUDE.md hard prohibition).

---

## Verification

- 25 controls rendered: 2 trainers, 4 tops, 3 bottoms, 2 cap, 2 glasses, 12 colour swatches
- Model exposes all 7 garment meshes plus the body
- Toggle test, two loadouts on the same model:
  - `top_tee + bottom_shorts + cap` → shown 3, hidden 4
  - `top_hoodie + bottom_tights + glasses` → shown 3, hidden 4 — different set, confirmed
- Blender renders confirm the pieces are **geometrically** different, not recoloured: full sleeves
  and long legs vs sleeveless and knee-length
- 7 pages → 200 · all JS `node --check` clean · all PHP `php -l` clean
- `.env`, `_audit/set_session.php` → 403

## Honest limitations

1. Garment edges follow the **bone-weight boundary**, not a tailored hem, so the tank top's
   shoulder line is slightly ragged up close. Fine at workout camera distance; a hand-modelled hem
   would be cleaner.
2. The base character keeps its own painted outfit underneath, so "Base" is the trainer's default
   look rather than bare skin — garments layer over it.
3. The Blender preview renders wash the colours out (Blender's view transform). The app applies
   the exact hex via `material.color.setHex()` under its own lighting.

---

## Roman Urdu — summary

1. **Asli masla yehi tha:** dono characters ke kapre body mesh me hi fuse the — koi alag shirt ya
   pant mesh tha hi nahi. Is liye sirf texture ka rang badal sakta tha. **Ab har trainer ke saath
   asli alag-alag garment meshes hain.**
2. **Kaise banaye:** Blender me har piece ko body se banaya — jis bone par vertex ka weight sab se
   zyada hai us se region choose kiya, phir normals ke sath thora bahar nikaal diya taake kapra
   body ke upar baithe. Skeleton wahi rehta hai, is liye animation apne aap chalti hai.
3. **Kya mila:** Upper — Tank / T-Shirt / Hoodie · Lower — Shorts / Joggers · Cap on/off ·
   Chashma on/off · dono ke liye 6-6 rang. **Do trainers** (Street aur Pro).
4. **Workout se pehle select karte hain** — start screen par panel hai, live 3D preview ke sath,
   aur wahi selection workout me chalti hai. Home page par bhi wahi character.
5. **Speed:** kapra badalna sirf show/hide hai — koi download nahi, foran. Sirf trainer badlein to
   naya model aata hai.
6. **Sach saaf:** kapre ke kinare bone boundary par katte hain, tailored hem nahi — paas se tank
   top ka kandha thora rough lagta hai. Workout distance par theek hai.
