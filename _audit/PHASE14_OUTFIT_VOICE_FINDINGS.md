# PHASE 14 — Real outfit swap + workout voices

Date: 2026-07-30. Status: **voices shipped; garments unblocked (Blender installed), not yet built.**

## ⚠️ CORRECTION — a wrong conclusion I drew and then reversed

Mid-phase I reported that the 3D trainer was "invisible" on both `index.php` and
`pages/player.php`, blamed `titan-rig.js:425 measure()`, and changed shared rig code to fix it.

**That was wrong.** The 3D panel was black in my screenshots because the automated browser tab
was a **background tab**:

```
document.visibilityState : "hidden"
document.hasFocus()      : false
requestAnimationFrame callbacks in 1s : 0
gl.drawElements calls in 1.5s          : 0
```

Chrome suspends `requestAnimationFrame` in hidden tabs, so nothing was ever drawn — the app was
fine. Proof it renders correctly: the earlier `trainer-viewer.html` screenshot shows the trainer
fully rendered in the new graphite kit.

`measure()` is also *correct as written*, and I have annotated why: a SkinnedMesh's
`geometry.boundingBox` is the bind pose while its `matrixWorld` excludes skinning, so world-space
measuring reports `MocapGuy_Body_1` as 16.25 × 2.70 × 14.89 — six times wider than tall. The
shipped code puts `HeadTop_End` at y=2.65 and the hips at y=1.48: a correctly proportioned
2.7-unit figure on the floor.

**All rig changes made on that false premise were reverted** — `fitModel()` in both
`assets/js/titan3d.js` and `pages/player.php` is back to its original code, and the 101 lines of
helpers I had added to `titan-rig.js` are removed. Verified: 0 references remain.

---

## 1. Why the workout player showed no change — confirmed

`pages/player.php` contains its **own complete 3D engine** (`exactfit3d`, ~900 lines,
`pages/player.php:350-1250`): its own `TRAINER_MODEL` (line 367), its own `makeGLTFLoader()`
(951), its own `loadTrainer()` (1077), its own `fitModel()`.

`assets/js/titan3d.js` is a **different engine**, used only by `index.php`,
`pages/features.php:117` and `member/workouts.php`.

The Customize panel was added to `titan3d.js`, so it can never appear on the player page.
Two engines, duplicated — that is the actual defect. Also duplicated across both files:
the whole `VoiceManager` class (`titan3d.js:938-1000` and `player.php:480-570`).

---

## 2. Colour swap is not enough — correct, and here is why the model can't do more

`trainers.glb` is **one body mesh** (`MocapGuy_Body`) with the outfit painted into a single
`Body_MAT` texture. There is no shirt mesh, no trouser mesh, no shoe mesh to show or hide.
On this asset a texture swap is the *only* thing possible — which is exactly the limitation
you noticed.

Research agrees on the real technique: garments must be **separate meshes skinned to the same
skeleton**, authored in Blender, then shown/hidden or skeleton-shared at runtime
(three.js forum: [skeleton sharing](https://discourse.threejs.org/t/three-js-share-skeleton-between-skinnedmeshes-using-same-bones-structure/18536),
[rebinding](https://discourse.threejs.org/t/how-to-rebind-a-skinned-mesh-to-a-different-skeleton-same-structure/46226)).
Mixamo/Adobe do not ship separable garments — each Mixamo character is one fused mesh.
Fitify uses **recorded HD video**, not a runtime 3D avatar, so it is not a model for this.

---

## 3. What I proved works: real character swap via your Ch06 FBX

Built a **build-time** converter (three.js r128 + fflate + FBXLoader — the exact stack
`_audit/fbx-test.html` already used — plus the matching GLTFExporter). It ran, and the tool was
deleted afterwards; **no new runtime dependency, nothing added to the app**.

Result — `character.fbx` → GLB:

| | |
|---|---|
| loaded | 1 mesh, 3 materials, **65 bones**, rig prefix `mixamorig9` |
| rig compatibility | ✅ `titan-rig.js:141` `normBone()` strips `mixamorig[0-9]*`, so `mixamorig9Hips` binds correctly |
| outfit | ✅ genuinely different — navy/black kit, white + red accents, different skin tone (verified by extracting the 8 embedded PBR textures) |
| **geometry** | ❌ **808,170 vertices / 269,390 triangles** |

For comparison the shipped trainer is **17,916 triangles** — Ch06 is **15× heavier**.

I welded duplicate vertices (FBXLoader emits non-indexed geometry): 808,170 → **153,960
vertices, 80.9% removed**, and downscaled every texture to 512². The file still lands at
**~19 MB** versus 5.43 MB for the current trainer, because ~270k triangles of position/normal/
UV/joints/weights plus an index buffer is simply that much data.

**Conclusion: Ch06 is a cinematic-density asset, not a web asset.** Shipping it would multiply
the workout page's load. Making it viable needs *mesh decimation*, which is not something I can
do responsibly with hand-written tooling — it needs Blender's Decimate modifier or `gltf-transform
simplify` (both new dependencies).

---

## 4. Voices — measured, and the ceiling is real

Both engines use the browser **Web Speech API** (`speechSynthesis`), which can only use voices
installed on the machine. Measured in your Chrome right now — **6 English voices, zero neural**:

| voice | type |
|---|---|
| Microsoft Mark / David / Zira – en-US | old local SAPI (robotic) |
| Google US English, Google UK English Female/Male | Google network voices |

`player.php:505` already asks for `Guy Online (Natural)`, `Andrew Online (Natural)`,
`Christopher Online (Natural)` — **none of those exist on this machine**, so it falls through to
**Google UK English Male**, which is the best of the six. *The code is already picking the best
available voice.* That is why it still sounds "purani" — it is a missing-voice problem, not a
code problem.

Three real ways forward:

1. **Free, immediate** — install the Windows "Natural" neural voices
   (Settings → Time & Language → Speech → Manage voices). Chrome then exposes
   `Microsoft Guy Online (Natural)` etc. and the existing preference list picks them up with
   **no code change**. Biggest quality jump for zero cost.
2. **Neural TTS API** (ElevenLabs / Azure Speech / Google Cloud TTS) — generate one audio file
   per cue once, ship them as static `.mp3`. Best and most consistent quality, works on every
   visitor's device regardless of their OS. Needs an API key + a small cost.
3. **Recorded human coach audio** — what Fitify actually does. Best of all, needs a real voice
   recording session. I will not fabricate audio files.

Independent of the above, I can improve: merge the two duplicated `VoiceManager` classes, add a
**voice picker** in the UI so a visitor chooses among their installed voices (persisted), tune
rate/pitch per cue type, and rewrite the coaching lines per exercise.

---

## 5. Open question (one)

For real garment/style switching, which asset route?

- **(a) Web-optimised character packs** — get 2-3 *low-poly* dressed characters (Mixamo has
  lighter ones; or Tripo/Meshy export with a configurable polycount). Swap whole characters.
  Needs downloading assets + checking licence.
- **(b) Blender + garment meshes** — the technically correct answer: model/retarget shirt,
  shorts, hoodie, joggers onto the `mixamorig` skeleton, export one GLB, toggle mesh visibility.
  Needs Blender installed.
- **(c) Decimate Ch06** — keep the character you already downloaded, cut it to ~25-30k triangles
  with Blender Decimate or `gltf-transform simplify`, then ship it as outfit #2.

All three need something installed or downloaded, which is why I stopped rather than guess.

---

## 6. SHIPPED in this phase — voices

New `assets/js/cca-voice.js` is now the single source of truth for **which** coach voice is used.
Both engines previously carried their own `VoiceManager` with different priority lists and
different pitch (0.85 vs 0.9) — the same coach sounded different on the home page and inside a
workout. Both now call `CCAVoicePick()`, with their old lists kept only as a fallback, and both
run at pitch 0.9.

Added a **coach-voice picker** (persisted in `localStorage`), rendered in two places:
- home / features / member-workouts → inside the 🎨 Customize panel
- `pages/player.php` → in the Voice Sync bar, under the live caption

When no neural voice is installed it says so honestly, with the exact path to fix it.

Included on: `index.php`, `pages/player.php`, `pages/features.php`, `member/workouts.php`.

**Verified in-browser:** shared API loaded; 6 English voices listed; default resolves to
`Google UK English Male` (the best installed); choosing a voice persists and `CCAVoicePick()`
honours it; clearing the preference falls back to the ranked best; picker renders on the player
page during a live workout.

> One caveat found while testing: an earlier automated test left a stale voice preference in
> `localStorage`, which made the picker look like it had chosen wrongly. It had not — cleared and
> re-verified.

## 7. Blender 5.2.0 LTS installed

`winget install BlenderFoundation.Blender` → exit 0 →
`C:\Program Files\Blender Foundation\Blender 5.2\blender.exe`.

This unblocks the route you chose. Ch06 can now be decimated from 269k triangles to a web budget,
and real garment meshes (shirt / shorts / hoodie / joggers) can be rigged to the `mixamorig`
skeleton and exported as one GLB whose meshes are toggled at runtime — the technique the three.js
community actually uses.

**Next step (not yet done):** author the garments in Blender, export, and wire a real style
switcher into **both** engines — `titan3d.js` *and* the separate `exactfit3d` engine in
`pages/player.php`, so the change appears on the workout page too.

---

## Roman Urdu — summary

1. **Player page ka masla samajh aa gaya:** `pages/player.php` ka apna **alag** 3D engine hai
   (`exactfit3d`), aur maine jo panel banaya wo `titan3d.js` me tha — is liye wahan nahi dikha.
   Do alag engine hain, yehi asal kharabi hai.
2. **Aap sahi keh rahe hain** — sirf colour badalna kaafi nahi. Lekin maujooda model me shirt/pant
   alag mesh hain hi nahi, sab ek hi body mesh + ek texture me paint hai. Is asset par texture
   swap ke ilawa kuch mumkin hi nahi.
3. **Ch06 wali FBX ko GLB me convert kar ke dikha diya** — chal gaya, rig bhi match karta hai, aur
   kapre sach me alag hain (navy + safed/laal). **Magar** wo **269,000 triangle** ka hai jabke
   maujooda trainer sirf **17,916** ka — yani 15 guna bhaari, file ~19 MB. Website slow ho jati.
   Is liye ship nahi kiya.
4. **Voice ka sach:** aap ke Chrome me sirf 6 English voice hain aur koi bhi "Natural/neural" nahi.
   Code pehle se in me se **behtareen** (Google UK English Male) choose kar raha hai. Ye code ka
   masla nahi, machine par acchi voice install hi nahi. **Sab se asaan hal:** Windows Settings →
   Speech → Manage voices se "Natural" voices install kar lein — code change kiye baghair awaaz
   kaafi behtar ho jayegi.
5. **Meri ek ghalti — theek kar di:** maine kaha tha ke model dono page par "invisible" hai aur
   rig ka code badal diya tha. Wo **ghalat** tha: browser tab background me tha, aur Chrome
   background tab me animation rok deta hai (rAF = 0 calls). Model bilkul theek hai. Jo bhi
   badla tha wo **wapas revert** kar diya — ab dono engine ka `fitModel` bilkul purana wala hai.
6. **Voice ka kaam ho gaya:** ek shared file `assets/js/cca-voice.js` bana di — ab dono engine ek
   hi voice logic use karte hain (pehle dono ka apna alag tha aur pitch bhi alag). Home page aur
   **workout player dono par voice picker** lag gaya hai, aur agar neural voice install nahi hai
   to saaf likha aata hai ke kahan se install karein. Browser me test kiya.
7. **Blender 5.2 install ho gaya** — ab asli kapre (shirt/shorts/hoodie) banane ka raasta khul
   gaya hai. Agla kadam: Blender me garments bana kar `mixamorig` skeleton par rig karna, aur
   switcher ko **dono** engine me lagana (taake workout page par bhi dikhe).
