# Per-exercise animation clips

Drop one GLB per exercise here — the **file name** picks the exercise mode and the
clip is bound to `../trainer.glb`'s skeleton by bone name at play time.

Valid file names (any subset — missing ones are skipped):

```
idle.glb  warmup.glb  squat.glb  pushup.glb  curl.glb  press.glb  yoga.glb
plank.glb  crunch.glb  twist.glb  legraise.glb  mountain.glb  highknees.glb  jumpingjack.glb
```

Full recipe (Mixamo → FBX → GLB, and the same-converter rule) is in
[`../README.md`](../README.md). Wired up in `assets/js/titan3d.js`
(`TRAINER_ANIMS`, `bindExtraAnims`).
