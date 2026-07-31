# PHASE 12 — fast preview pass · plus push-up form and body-colour fixes

**Date:** 2026-07-25 · **Verified in real Chrome via the browser extension**, not only the test harness.

---

## 1. Push-up form — both hands were on one side

**Your report:** *"pushups ker te waqt dono hands ko trainer right side pe le jata hai."* Confirmed and fixed.

### Root cause

`proneBrace()` drops the two arms to the floor **mirrored** — `+armAng` on the left,
`−armAng` on the right. The push-up case then applied the **same** sweep to both:

```js
rot('lArm', AZ, 1.05 * d);      rot('rArm', AZ, 1.05 * d);      // ← same sign
rot('lForeArm', AZ, -1.05 * d); rot('rForeArm', AZ, -1.05 * d); // ← same sign
```

`rot()` works in **world** axes. Applying an identical world-Z rotation to a mirrored pair swings
both arms the same way in world space, so both hands converged on one side of the torso. The give-away
was two lines below: the hand tilt was already written mirrored (`-0.15` / `+0.15`) — the arm sweep
simply had not been.

### Fix

Mirrored the sweep, and added a named lateral spread so the hands plant **wider than the shoulders**,
which is what a real push-up looks like and guarantees they can never meet at the centre line:

```js
rot('lArm', AZ,  1.05 * d);   rot('rArm', AZ, -1.05 * d);
rot('lForeArm', AZ, -1.05*d); rot('rForeArm', AZ, 1.05*d);
rot('lArm', AY,  PUSHUP_HAND_SPREAD);   // 0.22 rad, named constant
rot('rArm', AY, -PUSHUP_HAND_SPREAD);
```

### Measured proof (live page, not a guess)

```
leftHand  { x:-0.96, y:0.05, z: 0.38 }
rightHand { x:-0.97, y:0.05, z:-0.17 }
separation: 0.552 m      bothNearFloor: true
```

Hands are **0.55 m apart on opposite sides**, both planted at floor level (y = 0.05), fingers flat.

---

## 2. Body colour — one realistic human tone, no customising

**Your instruction:** real human colour, keep it one, remove Customize.

### What was wrong

Two things were actively pushing the character away from looking human:

| Was | Effect |
|---|---|
| `SKIN_LIFT = [0.50, 0.40, 0.34]` | Brightened face/neck by up to **50%** — an artificial "fair skin" push over the tone baked into the model |
| `clothesColors` = orange `255,107,26` + gold `255,184,0` | Painted brand colours onto the body atlas, so the trainer read as a costume, not a person in gym clothes |
| A "🎨 Customize" panel | Let all of the above be changed at runtime |

### What it is now

- **`SKIN_LIFT = [0, 0, 0]`** — `liftSkin()` is a no-op. The face keeps exactly the tone the artist
  baked. This also settles the Phase 11 rule that the skin tone must never be altered.
- **Kit fixed to realistic matte activewear** — charcoal top `46,49,56`, darker bottoms `32,34,39`,
  near-black trainers `22,24,28`, muted steel seams `64,68,76`. No gold, no brand orange.
- **Customize removed end to end**: the button is gone from the homepage, `buildClothesPanel()` is no
  longer called, and `setClothes` / `getClothes` are **no longer exported** from the rig — so there is
  no supported way to recolour the trainer at runtime any more.

Verified live: `customizeButtonGone: true`, no PHP errors.
The marker-erasure pass is **kept** — that is what removes the baked-in red mocap dots from the suit.

---

## 3. Phase 12 — fast preview pass

### The spec assumed clips; this trainer has none

The brief says to call `act.setEffectiveTimeScale(11.5)`. That cannot apply here: the trainer is
driven **procedurally** by `CCARig`, with no `AnimationMixer` action to scale (the mixer is
deliberately disabled when the rig attaches). The equivalent is to advance the rig's **own clock**
faster.

`pages/player.php` now keeps a dedicated accumulator:

```js
rigTime += dt * ((pv && pv.active) ? pv.scale : 1);
CCARig.update(mode, rigTime);
```

Using a separate monotonic accumulator — rather than scaling raw elapsed time — means speeding up and
slowing down never makes the pose jump. The clock only ever moves forward, just at a different rate.

### Implementation

| Spec requirement | How it is met |
|---|---|
| Named constant, not hardcoded in three places | `PREVIEW_TIMESCALE = 11.5` in `assets/js/player.js` |
| New stage **before** "ready" | `enterPreview(exercise, then)` runs, then calls `enterReady` / `loadExercise` |
| Reset to 1× **before** the first real rep | `endPreview()` sets `scale = 1` *before* invoking the continuation, with a `console.assert` guarding it |
| Sane minimum so it isn't invisible | A **1300 ms** window at 11.5× ≈ 7 quick reps. A push-up cycle is ~2.1 s; "play the clip once" at 11.5× would be <200 ms and read as a glitch — so the pass is expressed as a visible window, well above the ~400 ms floor |
| Skippable | A "Skip preview" button is injected and wired to `endPreview` |
| `prefers-reduced-motion` | No fast playback at all — holds a single static pose briefly, then continues |
| Voice not read at 11.5× | The full coaching cue is **not** played; a short dedicated `'preview'` cue is requested instead |
| Once per exercise, not per rep | `previewed` Set, keyed by exercise name, per session |

### A bug this introduced, found and fixed before shipping

`tick()` decrements `remain` every second regardless of phase. During `preview`, `remain` would go
0 → −1, fall straight past the `remain > 0` check, match none of the phase branches, and drop into the
"an exercise just ended" path — **silently skipping an exercise**. Guarded:

```js
if (phase === 'preview') return;   // preview runs on its own timer, not this clock
```

### Measured proof (live page)

```
TF_PREVIEW before start   {"active":false,"scale":1}
t+0.4s  (during preview)  {"active":true,"scale":11.5}   ← fast pass running
t+1.8s  (after preview)   {"active":false,"scale":1}     ← reset BEFORE the real rep
```

Then the UI correctly showed **GET READY → UP NEXT: CRUNCHES**, still on
**"EXERCISE 1 / 6 · BLOCK 1"** — confirming no exercise was skipped.

---

## Honest limitations

- **Voice during preview is currently silent.** `window.TitanTrainer` is never defined on the player
  page, so *every* voice call in `player.js` — mine and the pre-existing ones — is already a guarded
  no-op. That is a pre-existing gap, not something this phase introduced. Silence is the spec's other
  accepted option, and the cue starts working the moment a voice engine is wired up.
- **Phase 12 item 3 (verify every exercise in the DB) is NOT done.** That is Phase 13's exercise
  matrix. The preview mechanism is verified on `Instant Six Pack`; a per-exercise sweep across all
  37 workouts / 152 exercises has not been run.
- **Per-exercise outfits remain blocked** on a `.glb` with separate clothing meshes, which this
  project does not contain.

## Verified

| Check | Result |
|---|---|
| `node --check` on player.js, titan-rig.js, titan3d.js | clean ✓ |
| `php -l` on player.php, index.php | clean ✓ |
| Routes (index, player, workouts, shop, login) | all **200** ✓ |
| Push-up hand separation | **0.55 m, opposite sides, both on floor** ✓ |
| Preview timescale 11.5 → reset to 1 before rep | ✓ |
| No exercise skipped by the new stage | ✓ (Exercise 1/6 intact) |
| Customize UI removed | ✓ |
