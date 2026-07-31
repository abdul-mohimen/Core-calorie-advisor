# CORE CALORIE ADVISOR — Rigged 3D Trainer

`assets/js/titan3d.js` loads a **rigged, gym-clothed human GLB** and plays real
motion-capture clips with seamless `AnimationMixer` crossfades. If `trainer.glb`
fails to load, the site falls back to a built-in procedural athlete — so the hero
is never blank.

There are **two model sources** and they work together:

| File | Role | Required? |
|------|------|-----------|
| `assets/models/trainer.glb` | the rigged, skinned body (the mesh you see) | **yes** — this is the character |
| `assets/models/anims/<mode>.glb` | one motion clip per exercise, bound to the body by bone name | optional, but this is what makes each exercise move differently |

> **Filename:** the shipped model is `assets/models/trainers.glb` (plural) and all
> loaders point at it. If you drop in a replacement, keep that exact name (or update
> `TRAINER_MODEL.url` in `assets/js/titan3d.js` **and** `pages/player.php`).

## How per-exercise motion works now (no extra files needed)

The shipped `trainers.glb` is a real rigged human ("MocapGuy", 1 skin, full Mixamo
skeleton) but contains **only one generic clip**. To make one model perform *every*
workout, `assets/js/titan-rig.js` (**TitanRig**) binds the standard Mixamo bones by
name and **poses the skeleton procedurally per exercise** — squat, push-up, curl,
press, jumping jack, high knees, twist, plank, crunch, leg raise, mountain climber,
yoga. Both the hero (`titan3d.js`) and the workout player (`pages/player.php`) call
`TitanRig.attach(gltf.scene)` on load and `TitanRig.update(mode, t)` each frame.

Open **`/trainer-viewer.html`** to preview each exercise live (buttons in the panel).

Because motion is generated on the live rig, **you do not need the per-exercise
`anims/*.glb` files** — they remain an optional path (below) if you'd rather play
real mocap clips. If a dropped-in model's bones aren't standard Mixamo names,
TitanRig steps aside and the loader plays the embedded clip instead.

---

## Why per-exercise files (and not one merged file)

In three.js an `AnimationClip` binds to the skeleton **by bone name** when it plays.
Every animation you export for the **same Mixamo character** shares an identical
rig, so a clip loaded from a *separate* `squat.glb` plays perfectly on the body in
`trainer.glb`. That means:

* **No Blender, no merge tool.** Drop `anims/squat.glb` in → the trainer squats.
* **Incremental.** Add a new exercise later = drop one more file. Nothing to re-merge.
* Missing files just `404` silently and are skipped.

The filename picks the mode — the clip's internal name is ignored. Valid names:

```
idle  warmup  squat  pushup  curl  press  yoga
plank  crunch  twist  legraise  mountain  highknees  jumpingjack
```

> ⚠️ **The one rule: use the SAME FBX→glTF converter for `trainer.glb` and every
> `anims/*.glb`.** Different converters sanitise bone names differently
> (`mixamorig:Hips` vs `mixarmorigHips`), and mismatched names make a clip silently
> do nothing (T-pose). The safest move is to regenerate `trainer.glb` from the same
> pipeline as your anims (convert an *Idle, With Skin* FBX → `trainer.glb`).

---

## Build it (free, ~20 min) — Mixamo → FBX → GLB

**Mixamo exports FBX, not GLB**, so there is always a convert step.

### 1. Download the motions from Mixamo

1. Go to **https://www.mixamo.com** (free Adobe account) and pick ONE muscular,
   gym-clothed character — this is the body for the whole app.
2. For that **same** character, download each animation below:
   * the base/idle one as **FBX Binary (.fbx), *With Skin*** (this carries the mesh),
   * every other one as **FBX Binary (.fbx), *Without Skin*** (animation only, tiny),
   * tick **In Place** for locomotion clips (High Knees, Mountain Climber, …) so the
     trainer doesn't walk off the pedestal.

### 2. Convert each FBX → GLB (no Blender needed)

Meta's **FBX2glTF** is a single portable binary:

```bash
# one-time: pull the converter
npm i -g fbx2gltf          # or grab a release from github.com/facebookincubator/FBX2glTF

# convert (repeat per file)
FBX2glTF -b -i "Air Squat.fbx"  -o squat        # -b = binary .glb, --draco optional
FBX2glTF -b -i "Idle.fbx"       -o trainer      # the WITH-skin one becomes the body
```

`-o squat` writes `squat.glb`. Add `--draco` to shrink files (the loader has a
Draco decoder wired in).

### 3. Drop the files in

```
assets/models/trainer.glb        <- the With-Skin conversion (the body)
assets/models/anims/idle.glb
assets/models/anims/squat.glb
assets/models/anims/pushup.glb
assets/models/anims/...
```

Reload the hero and open the browser console — each bound clip logs
`[titan3d] +anim squat`, and `[titan3d] Rigged trainer loaded — embedded clips: …`
shows what shipped inside `trainer.glb`.

### Mode → Mixamo animation to search

| Mode file | Mixamo animation |
|-----------|------------------|
| `idle.glb` / `warmup.glb` (default) | **Warm Up** / *Breathing Idle* |
| `squat.glb`       | **Air Squat** |
| `pushup.glb`      | **Push Up** |
| `curl.glb`        | **Bicep Curl** |
| `press.glb`       | **Shoulder Press** / *Overhead Press* |
| `yoga.glb`        | **Warrior** / *Yoga* / *Tree Pose* |
| `plank.glb`       | **Plank** |
| `crunch.glb`      | **Ab Crunch** / *Sit Up* |
| `twist.glb`       | **Russian Twist** |
| `legraise.glb`    | **Leg Raises** / *Reverse Crunch* |
| `mountain.glb`    | **Mountain Climber** *(In Place)* |
| `highknees.glb`   | **High Knees** / *Running* *(In Place)* |
| `jumpingjack.glb` | **Jumping Jacks** / *Star Jump* |

---

## Alternative: one merged multi-clip `trainer.glb`

If you'd rather ship a single file, merge several named clips into one
`trainer.glb`. The loader also maps **embedded** clips by name via
`TRAINER_MODEL.clips` in `titan3d.js` (fuzzy, case/space-insensitive). Merging
needs a tool that combines animations onto one armature — **Blender** (import all,
name each Action, export glТF with *all actions*) or a **glTF-Transform** script.
The per-exercise `anims/` files are simpler and are the recommended path.

---

## Notes / gotchas

* **Draco is supported** — `makeGLTFLoader()` wires `DRACOLoader` (decoder from the
  gstatic CDN). Uncompressed GLBs also work.
* The current `trainer.glb` is **6.5 MB uncompressed**; the load timeout is 8 s.
  Consider `--draco` to cut it well under 2 MB for a snappier hero.
* The loader auto-scales the model to ~2.7 units tall and stands it on the floor,
  so any export size works.
* Licensing: Mixamo assets are free for commercial use under the Adobe Mixamo terms.
