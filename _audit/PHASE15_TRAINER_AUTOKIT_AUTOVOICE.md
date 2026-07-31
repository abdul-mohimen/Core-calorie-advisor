# PHASE 15 — New trainer + automatic per-workout kit + per-exercise voice

Date: 2026-07-30. Status: **SHIPPED and verified.**
Previews: `_audit/ch06-kit-default.png`, `_audit/ch06-kit-ocean.png`

---

## 1. Why nothing changed on the workout page (root cause)

`pages/player.php` contains its **own complete 3D engine** — `exactfit3d`, ~900 lines
(`player.php:350-1250`), with its own `TRAINER_MODEL`, `makeGLTFLoader()`, `loadTrainer()` and
`fitModel()`. `assets/js/titan3d.js` is a **separate** engine used only by `index.php`,
`pages/features.php` and `member/workouts.php`.

Everything in Phase 13 went into `titan3d.js`, so the workout player could never show it.
Both engines are now wired.

---

## 2. New default trainer — Ch06

Built with Blender 5.2 (`blender --background --python ch06_web.py`):

| | MocapGuy (was) | **Ch06 (now)** |
|---|---|---|
| file | 5.43 MB | **2.37 MB** |
| triangles | 17,916 | 27,999 |
| clothing | mocap capture suit (tracking dots) | **real tracksuit — hoodie + joggers + tee + trainers** |
| head | mocap cap, **no hair mesh** | **actual hair** + headphones |
| rig | `mixamorig:` | `mixamorig9:` |

Pipeline: import FBX → Decimate 53,878 → **27,999 tris** → textures 4096²→1024² → materials
forced to matte (the FBX's specular/glossiness map wired into glTF *Roughness* read inverted and
made everything look like wet plastic; the link is dropped and a constant used) → GLB with JPEG
textures.

**Rig compatibility verified in-browser**, not assumed: `CCARig.attach()` returns true, and
driving it produces **10 distinct poses through a squat cycle and 6 through jumping jacks** across
`LeftUpLeg`, `LeftLeg`, `Spine`, `LeftArm`. `normBone()` normalises the `mixamorig9` prefix.

MocapGuy is kept as an automatic **fallback**: both engines now try Ch06, then `trainers.glb`,
before dropping to the procedural body — a missing primary model can never cost us the human.

---

## 3. Clothes change automatically per workout

`assets/js/cca-coach.js` maps workout category → kit. **The visitor never picks it.**

| category | kit |
|---|---|
| `strength` | navy tracksuit (already in the GLB — no download) |
| `hiit-cardio` | `ch06-ember.png` |
| `yoga-stretching` | `ch06-forest.png` |
| `warmup-recovery` | `ch06-ocean.png` |

Colourways were produced by masking Ch06's own atlas: the tracksuit is a large dark **navy**
region, skin is warm brown, the tee near-white, headphones red/grey — so "dark AND not warm"
isolates the garment. 58.0% of the atlas selected; skin, face, hair, tee, headphones and sneakers
verified untouched in `ch06_mask.png` and in the rendered head crop.

Each trainer has its **own** kit set, chosen from the loaded model's material names
(`CCACoach.kitSetFor`) — feeding MocapGuy's atlas to Ch06 would smear the wrong pixels onto the
wrong body parts.

**Verified end-to-end:** workout "Full Body Burn" → category `hiit-cardio` → kit set `ch06`
→ `assets/models/outfits/ch06-ember.png`, resolved automatically on load.

---

## 4. Voice matches the exercise automatically

Previously `announce()` read cues from `WORKOUT_STATES`, keyed by the **14 animation modes**. So
Squats / Lunges / Burpees / Wall Sit / Squat Jumps all said one line, and Russian Twists,
Crunches and Bicycle Crunches shared another.

`cca-coach.js` now holds **31 exercises** keyed by their real database name, each with `setup`
(said while getting ready), `go` (on the switch into the rep) and `push` (mid-set), all real form
coaching. Anim mode remains the fallback for anything added later.

**Verified — 8/8 lines distinct, spoken automatically:**
```
Plank          → "Ribs down, glutes tight, one straight line from head to heels."
   halfway     → "Do not let the hips drop — brace like you are about to be punched."
Russian Twists → "Rotate through the ribcage, not just the arms. Touch each side."
   halfway     → "Chest stays open — control every turn."
Bicep Curls    → "Curl without swinging. Squeeze hard at the top."
   halfway     → "Lower slowly — three seconds down."
Squats         → "Sit back into the hips, knees tracking over your toes. Depth before speed."
   halfway     → "Drive through your heels — stand tall at the top."
```

Also from Phase 14: `assets/js/cca-voice.js` is the single source of truth for **which** voice is
used (the two engines had drifted to different priority lists and different pitch, 0.85 vs 0.9).

> **The voice quality ceiling is not code.** The Web Speech API can only use voices installed on
> the visitor's machine. This machine has 6 English voices and **zero neural** ones, so the best
> available is `Google UK English Male` — already selected. Installing the free Windows Natural
> voices (Settings → Time & Language → Speech → Manage voices) makes the coach sound human with
> **no code change**.

---

## 5. Cleanup

`assets/models/trainer-ch06.fbx` (51.9 MB) deleted — it was the conversion source, is now
superseded by the 2.37 MB GLB, and two copies of the original remain in `Downloads`.
Project: **273.83 MB → 221.93 MB**.

---

## 6. Verification

- 15/15 JS pass `node --check`; all PHP pass `php -l`
- 7 pages → 200 (`index`, `workouts`, `player?id=1`, `player?id=4`, `features`, `member/workouts`, `login`)
- All 6 new assets → 200
- `.env`, `_audit/set_session.php`, `.fbx` → 403 (security from Phase 13 intact)

---

## Roman Urdu — summary

1. **Workout page par kuch nahi badla tha kyun:** `pages/player.php` ka apna **alag** 3D engine
   hai. Pehle sirf `titan3d.js` me kaam kiya tha. **Ab dono me lag gaya hai.**
2. **Naya trainer:** Blender se Ch06 ko web-ready banaya — **asli tracksuit (hoodie + joggers),
   andar tee, joote, aur asli baal** (purane model par mocap suit aur topi thi, baal hi nahi the).
   Aur file **chhoti** hai: 5.43 MB → **2.37 MB**. Rig test kiya — squat me 10 alag pose, jumping
   jack me 6. Purana model automatic fallback ke tor par mojood hai.
3. **Kapre khud-ba-khud badalte hain** workout ke hisab se: strength → navy, HIIT → ember,
   yoga → forest, warm-up → ocean. Aap ko kuch select nahi karna. Test kiya: "Full Body Burn"
   (hiit) → `ch06-ember.png` khud aa gaya.
4. **Voice bhi khud-ba-khud** har exercise ke mutabiq — **31 exercises** ki apni asli coaching.
   Plank par "hips drop na hone do", curls par "teen second me neeche", squats par "heels se
   dhakka". 8/8 lines alag, verify kiya.
5. **Voice ki quality ka masla code nahi hai:** aap ke PC par 6 voice hain aur koi neural nahi.
   Code pehle se best (Google UK English Male) le raha hai. **Windows Settings → Speech →
   Manage voices** se Natural voices install kar lein — awaaz insani ho jayegi, code change
   kiye baghair.
6. **Safai:** 51.9 MB ki purani FBX hata di (GLB ban gaya, original Downloads me mojood).
   Project **273 MB → 221 MB**.
