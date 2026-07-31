# PHASE 17 — Trainer Studio page + honest limits on garment geometry

Date: 2026-07-30. Status: **page shipped; separate garment meshes abandoned with evidence.**
Renders: `_audit/studio-trainer-street.png`, `_audit/studio-trainer-pro.png`
Failures kept as evidence: `_audit/garment-fail-v1-collar.png`, `_audit/garment-fail-v2-collar.png`

---

## 1. What you said was wrong, and you were right

- The inline picker on the workout intro was cramped and badly designed → **now its own page.**
- The clothes did not sit on the model properly → **true, and I could not fix it. Details below.**
- The model should change, not just the clothes → **model change does work** (verified: Street and
  Pro are different characters with different meshes, skeletons and proportions).

---

## 2. Separate garment meshes — three attempts, all failed

| attempt | method | result |
|---|---|---|
| v1 | duplicate body region by bone weight, offset along normals | crumpled neckline; ragged hems where bone weights stopped |
| v2 | + heavy Smooth, + plane-cut hems, + Solidify thickness | collar **still** crumpled; body poked through as white patches |
| v3 | same, tuned offsets | no better |

**Root cause, stated plainly:** both characters have their clothing **modelled into the body mesh**
— the hoodie collar, the pockets, the folds are body geometry, not a separate garment. Harvesting a
body region therefore inherits all of that relief. Smoothing cannot remove a deep collar fold, and
pushing the shell out far enough to clear the body makes it self-intersect while the body still
shows through.

A fourth blind iteration was not worth your time. **What this actually needs** is the other
technique: model a clean primitive garment, shrinkwrap it to the body, then transfer skin weights
with a Data Transfer modifier — real modelling against a purpose-built asset. Off-the-shelf rigged
clothing (an asset store, or a character system such as Ready Player Me / Avaturn whose avatars
ship with genuinely swappable outfits) is the other route.

Both were dropped from the shipped build rather than left in looking broken. A bug in the plane
cuts is documented in the script while I was there: the mesh carries a 0.01 scale **and** a
Y-up→Z-up rotation, so a world-space plane is not the same plane in the local space `bmesh` works
in — world Z maps to local Y. v2's first run silently deleted every vertex of all three tops
because of it.

---

## 3. What shipped — `pages/trainer-studio.php`

A dedicated page, matching the site's `.cca-*` design system, with:

- **Large live 3D preview** (4:5), drag to rotate, scroll to zoom
- **Pose buttons** — warm-up / squat / push-up / curl / jack — the preview animates through the
  *same* procedural rig the workout uses, so you see the trainer actually moving before you commit
- **Trainer**: Street (athletic, tracksuit + headphones + trainers) · Pro (lean, compression fit)
- **Kit**: 4 colourways per trainer, each with its own swatch — these are real texture sets built
  per character (the two have different UV layouts, so they cannot share)
- **Extras**: Cap and Glasses — **real geometry**, kept because they do not suffer the collar
  problem (the skull is a smooth region; the glasses are clean primitives)
- Save &amp; Continue / Reset, persisted per device, and a `?next=` return path

The workout intro now shows a compact read-out ("Street · Ember · cap") with a **Change** button
into the studio.

### Precedence
An explicit kit choice in the studio **always wins**. The automatic per-workout-category colour
from Phase 15 now only applies when you have never chosen one — otherwise it would silently undo
your pick.

---

## 4. Verification

- All JS pass `node --check`; all PHP pass `php -l`
- 8 pages → 200, including the new `pages/trainer-studio.php`
- `trainer-street.glb` 2.51 MB · `trainer-pro.glb` 5.26 MB, both 200
- `.env`, `_audit/set_session.php` → 403
- Renders confirm both trainers are clean with cap + glasses on: no lumpy overlays, no
  see-through patches

> Not verified in-browser this round: the Chrome extension disconnected partway, so the studio
> page's 3D preview and control wiring were checked by syntax, HTTP and Blender renders rather
> than by driving the live page. Worth a click-through.

---

## Roman Urdu — summary

1. **Aap sahi thay.** Purana picker workout screen par thusa hua tha — **ab uska apna poora page
   hai** (`pages/trainer-studio.php`): bara 3D preview, drag se ghumao, scroll se zoom, aur pose
   buttons — trainer wahi asli workout animation me chal kar dikhata hai.
2. **Model change chalta hai** — Street aur Pro do alag characters hain (alag mesh, alag body).
3. **Kapron ka geometry — teen dafa try kiya, teeno fail.** Wajah saaf: in characters ke kapre
   **body mesh me hi modelled** hain (hoodie ka collar, pockets, folds — sab body geometry hai).
   Body ka hissa copy karne se wahi collar bhi aata hai; smooth karne se nahi jata, aur zyada
   bahar nikalo to body aar-paar dikhne lagti hai. Saboot `_audit/garment-fail-v1-collar.png`
   aur `-v2-collar.png` me hai.
4. **Chauthi dafa andhaadhund try nahi kiya.** Iske liye asal me ye chahiye: saaf primitive kapra
   bana kar body par shrinkwrap karna aur skin weights transfer karna — ya phir ready-made rigged
   clothing (asset store, ya Ready Player Me / Avaturn jaisa system jis ke avatars me kapre asli
   me swappable hote hain). **Tuta hua cheez ship nahi ki.**
5. **Jo ship hua aur acha lagta hai:** trainer select, 4 kit colourways (har trainer ke apne),
   Cap aur Glasses (asli geometry — ye theek hain), aur aap ka chuna hua kit auto-colour se
   **oopar** rehta hai.
