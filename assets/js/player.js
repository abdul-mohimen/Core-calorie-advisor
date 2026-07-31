/* ============ CORE CALORIE ADVISOR — Fitify-style Workout Engine ============
   Rhythm: GET READY → 3 exercises → REST → 3 exercises → REST → …
   Constraints: 
     - Total routine length max: 10 minutes (600s).
     - Exercise duration forced: exactly 30s.
     - 3-per-rest queue logic.
   Requires: window.TF_WORKOUT = {id, name, ex:[{name,seconds,kcal,anim}]}
             window.TF = {baseUrl, csrf, loggedIn}
             titan3d.js loaded (setMode, MODE_MAP)                      */
const W = window.TF_WORKOUT, EX = W.ex;
const CIRC = 414.7;
const REST_SEC  = 20;          // rest between exercise blocks
const READY_SEC = 10;          // get-ready countdown before first move

/* ============ PHASE 12 — fast preview pass ============
   Before an exercise is performed for the first time in a session, the trainer
   demonstrates it at high speed so the user sees the movement pattern before
   the timer starts.

   PREVIEW_TIMESCALE is a single named constant, not a number sprinkled through
   the code, so it can be tuned per clip length later.

   Why a duration rather than "play the clip once": CCARig drives the trainer
   PROCEDURALLY — the exercises are continuous loops, not fixed-length clips, so
   there is no "one clip" to play through. A push-up cycle is ~2.1s, which at
   11.5× would flash past in under 200ms and read as a glitch. Showing a fixed
   ~1.3s window at 11.5× gives roughly 7 quick reps — long enough to actually
   register, which is the point of the pass. This is the "sane minimum preview
   duration" the spec asks for, expressed as a window instead of a clamp. */
const PREVIEW_TIMESCALE = 11.5;
const PREVIEW_MS        = 1300;   // ≥ the ~400ms visibility floor by a wide margin
const previewed = new Set();      // exercise names already demonstrated this session

/* Shared with the render loop in pages/player.php, which multiplies the rig's
   clock by .scale each frame. */
window.TF_PREVIEW = { active: false, scale: 1 };

const prefersReducedMotion =
  window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

let previewTimer = null;

let idx = 0, phase = 'idle', remain = 0, phaseDur = 0,
    elapsed = 0, kcal = 0, paused = false, tickTimer = null, blockNo = 1;
const restAfter = new Set();   // exercise indices that are followed by a rest

const el = id => document.getElementById(id);

/* Pre-compute block boundaries & enforce constraints */
(function planBlocks() {
  restAfter.clear();
  let totalTime = 0;
  let allowedCount = 0;
  
  for (let i = 0; i < EX.length; i++) {
    // Respect each trainer-authored interval; only repair invalid imported values.
    EX[i].seconds = Math.max(15, Math.min(600, Number(EX[i].seconds) || 30));
    
    // Simulate time additions
    let addition = EX[i].seconds;
    if ((i + 1) % 3 === 0 && i < EX.length - 1) {
      addition += REST_SEC;
    }
    
    // 2. Cap total routine length at 10 minutes max (600 seconds)
    if (totalTime + addition <= 600) {
      totalTime += addition;
      allowedCount++;
    } else {
      break;
    }
  }
  
  // Truncate the queue to keep it under 10 minutes
  EX.splice(allowedCount);
  const N = EX.length;
  
  // Build restAfter set based on 3-per-rest sequence
  for (let i = 0; i < N; i++) {
    if ((i + 1) % 3 === 0 && i < N - 1) {
      restAfter.add(i);
    }
  }
})();

const N = EX.length;

function startPlayer() {
  if (tickTimer) return;
  if (!N) { toast('This workout has no exercises yet. Please choose another program.'); return; }
  idx = 0; elapsed = 0; kcal = 0; paused = false; blockNo = 1;
  el('playerIntro').style.display = 'none';
  el('playerUI').style.display = 'block';
  window.dispatchEvent(new Event('resize'));
  // Phase 12: demonstrate the first move fast, THEN start the get-ready countdown.
  enterPreview(EX[0], enterReady);
  tickTimer = setInterval(tick, 1000);
}

/* ---- PHASE 12: preview stage ----
   Runs BEFORE the given continuation (enterReady / loadExercise). Guarantees
   the rig timescale is back to 1 before the continuation fires, so a real rep
   can never be silently sped up. */
function endPreview(then) {
  if (previewTimer) { clearTimeout(previewTimer); previewTimer = null; }
  TF_PREVIEW.active = false;
  TF_PREVIEW.scale  = 1;                       // ← reset BEFORE the real stage starts
  const btn = el('previewSkip');
  if (btn) btn.style.display = 'none';
  const kick = el('restKicker');
  if (kick) kick.classList.remove('preview-kicker');
  console.assert(TF_PREVIEW.scale === 1, '[preview] timescale must be 1 before the real rep');
  if (typeof then === 'function') then();
}

function enterPreview(exercise, then) {
  const name = exercise && exercise.name;
  // Once per exercise per session — not before every rep.
  if (!name || previewed.has(name)) { return endPreview(then); }
  previewed.add(name);

  phase = 'preview';
  setAnim(exercise.anim || (typeof MODE_MAP !== 'undefined' && MODE_MAP[name]) || 'idle');

  /* Reduced motion: no fast playback at all — hold a single static pose for a
     beat so the user still sees the shape of the movement, then continue. */
  if (prefersReducedMotion) {
    TF_PREVIEW.active = false;
    TF_PREVIEW.scale  = 1;
    previewTimer = setTimeout(() => endPreview(then), 700);
    return;
  }

  TF_PREVIEW.active = true;
  TF_PREVIEW.scale  = PREVIEW_TIMESCALE;

  el('restScreen').classList.add('on');
  const kick = el('restKicker');
  if (kick) { kick.textContent = 'QUICK LOOK'; kick.style.fontSize = '2.2rem'; kick.classList.add('preview-kicker'); }
  if (el('restNext')) el('restNext').textContent = name;
  if (el('restNum')) el('restNum').style.display = 'none';
  if (el('restSpinner')) el('restSpinner').style.display = 'none';
  if (el('pName')) el('pName').innerHTML = 'Quick look — ' + name;

  // Skippable, per the spec.
  let btn = el('previewSkip');
  if (!btn) {
    btn = document.createElement('button');
    btn.id = 'previewSkip';
    btn.type = 'button';
    btn.className = 'wk-btn cca-btn-soft';
    btn.style.cssText = 'margin:14px auto 0;max-width:220px;display:block';
    btn.textContent = 'Skip preview';
    const host = el('restControls') || el('restScreen');
    if (host) host.appendChild(btn);
  }
  btn.style.display = 'block';
  btn.onclick = () => endPreview(then);

  /* Voice: deliberately NOT the full coaching cue — reading "Push ups. Lower
     your chest, elbows tucked…" over a 1.3s blur is nonsense. A short dedicated
     cue is requested instead.
     NOTE: window.TitanTrainer is not defined on this page — every voice call in
     this file is already a guarded no-op, so the preview is currently SILENT.
     That is the spec's other accepted option, and the call below starts working
     the moment a voice engine is wired up. */
  if (window.TitanTrainer && TitanTrainer.announce) TitanTrainer.announce('preview', name);

  previewTimer = setTimeout(() => endPreview(then), PREVIEW_MS);
}

/* ---- Phases ---- */
function enterReady() {
  phase = 'ready'; remain = READY_SEC; phaseDur = READY_SEC;
  el('restScreen').classList.add('on');
  el('restKicker').textContent = 'GET READY';
  el('restKicker').style.fontSize = '3rem';
  el('restNext').textContent = EX[0].name;
  el('pProg').textContent = (W.title || 'Workout') + ' · Get Ready';
  el('pName').innerHTML = 'Get Ready <span class="info-icon" title="Prepare for your workout!">i</span>';
  if (el('restSpinner')) el('restSpinner').style.display = 'none';
  if (el('restNum')) {
    el('restNum').style.display = 'block';
    el('restNum').textContent = remain;
  }
  /* No "+20s REST" on the opening countdown — nobody is resting before the
     first exercise has started. The control belongs to the REST phase only. */
  if (el('restControls')) el('restControls').style.display = 'none';
  setAnim('idle');
  if (window.TitanTrainer) TitanTrainer.announce('ready', EX[0].name, W.title);
  updateUI();
}

function loadExercise() {
  const e = EX[idx];
  /* Phase 12: if this exercise has not been demonstrated yet this session,
     show the fast preview first and re-enter once it finishes. enterPreview
     resets the timescale to 1 before calling back, so the rep below always
     runs at real speed. */
  if (e && e.name && !previewed.has(e.name) && phase !== 'preview') {
    enterPreview(e, loadExercise);
    return;
  }
  phase = 'ex'; remain = e.seconds; phaseDur = e.seconds;
  el('restScreen').classList.remove('on');
  el('pProg').textContent = 'Exercise ' + (idx + 1) + ' / ' + N + ' · Block ' + blockNo;
  el('pName').innerHTML = e.name + ' <span class="info-icon" title="Watch your form!">i</span>';
  setAnim(e.anim || (typeof MODE_MAP !== 'undefined' && MODE_MAP[e.name]) || 'idle');
  if (window.TitanTrainer) TitanTrainer.announce('go', e.name);
  updateUI();
}

function enterRest() {
  phase = 'rest'; remain = REST_SEC; phaseDur = REST_SEC;
  el('restScreen').classList.add('on');
  el('restKicker').textContent = 'REST';
  el('restKicker').style.fontSize = '';
  el('restNext').textContent = EX[idx + 1] ? EX[idx + 1].name : '—';
  el('pName').innerHTML = 'Rest <span class="info-icon" title="Breathe and recover.">i</span>';
  if (el('restSpinner')) el('restSpinner').style.display = 'none';
  if (el('restNum')) el('restNum').style.display = 'block';
  if (el('restControls')) el('restControls').style.display = 'block';
  setAnim('warmup');
  if (window.TitanTrainer) TitanTrainer.announce('rest', EX[idx + 1] ? EX[idx + 1].name : null);
  updateUI();
}

/* ---- Clock ---- */
function tick() {
  if (paused) return;
  /* The Phase 12 preview is driven by its own timer, not this 1s clock. Without
     this guard `remain` would tick 0 → -1, fall straight past the `remain > 0`
     check, match none of the phase branches below, and drop into the
     "an exercise just ended" path — silently SKIPPING an exercise. */
  if (phase === 'preview') return;
  remain--; elapsed++;
  if (phase === 'ex') kcal += EX[idx].kcal / EX[idx].seconds;
  if (window.TitanTrainer) {
    if (phase === 'ex' && remain === 5) TitanTrainer.announce('countdown');
    // halfway motivation only on longer intervals so short moves aren't spammed
    if (phase === 'ex' && phaseDur >= 24 && remain === Math.floor(phaseDur / 2)) TitanTrainer.announce('halfway');
    // spoken 3-2-1-Go so the next move starts ON the beat
    if ((phase === 'ready' || phase === 'rest') && remain === 3) TitanTrainer.announce('readycount');
  }
  if (remain > 0) { updateUI(); return; }

  if (phase === 'ready') { loadExercise(); return; }
  if (phase === 'rest')  { idx++; blockNo++; loadExercise(); return; }
  
  // an exercise just ended
  if (idx >= N - 1)        { finishWorkout(); return; }
  if (restAfter.has(idx))  { enterRest(); return; }
  idx++; loadExercise();
}

function updateUI() {
  const overlay = (phase === 'rest' || phase === 'ready');
  if (el('pTimer')) el('pTimer').textContent = String(remain).padStart(2, '0') + 's';
  const restNumEl = el('restNum');
  if (restNumEl) {
    restNumEl.textContent = overlay ? remain : '';
    // Countdown pulse during last 3 seconds of GET READY
    if (phase === 'ready' && remain <= 3 && remain > 0) {
      restNumEl.classList.remove('pulse-countdown');
      void restNumEl.offsetWidth; // force reflow
      restNumEl.classList.add('pulse-countdown');
    } else {
      restNumEl.classList.remove('pulse-countdown');
    }
  }
  if (el('ringFill')) el('ringFill').style.strokeDashoffset = CIRC * (1 - remain / (phaseDur || 1));
  if (el('pCal')) el('pCal').textContent = Math.round(kcal);
  if (el('pTotal')) el('pTotal').textContent = Math.floor(elapsed / 60) + ':' + String(elapsed % 60).padStart(2, '0');
  
  for (let i = 0; i < N; i++) {
    const seg = el('seg_' + i);
    if (!seg) continue;
    if (i < idx) seg.className = 'progress-segment done';
    else if (i === idx) seg.className = 'progress-segment active';
    else seg.className = 'progress-segment';
  }
}

/* ---- Controls ---- */
function togglePause(btn) { paused = !paused; btn.textContent = paused ? '▶ Resume' : '⏸ Pause'; }
function skipPhase() { remain = 1; }
function addRest() {
  if (phase === 'rest' || phase === 'ready') { remain += 20; phaseDur += 20; updateUI(); }
}
function setAnim(m) { if (typeof setMode === 'function') setMode(m); }

/* ---- Finish ---- */
function finishWorkout() {
  clearInterval(tickTimer); tickTimer = null;
  setAnim('idle');
  if (window.TitanTrainer) TitanTrainer.announce('done');
  const total = Math.round(kcal);
  el('congrats').classList.add('on');
  let c = 0; const cc = el('congCal');
  const iv = setInterval(() => { c += Math.ceil(total / 40); if (c >= total) { c = total; clearInterval(iv); } cc.textContent = c + ' kcal'; }, 30);
  el('congMsg').textContent = total + ' kcal';
  const card = el('congCard');
  for (let i = 0; i < 26; i++) {
    const s = document.createElement('i'); s.className = 'spark';
    s.style.left = '50%'; s.style.top = '40%';
    s.style.setProperty('--sx', (Math.random() * 440 - 220) + 'px');
    s.style.setProperty('--sy', (Math.random() * 440 - 220) + 'px');
    s.style.background = Math.random() > .5 ? '#FFB800' : '#FF6B1A';
    card.appendChild(s); setTimeout(() => s.remove(), 1400);
  }
  if (window.TF && TF.loggedIn) {
    const fd = new FormData();
    fd.append('csrf', TF.csrf); fd.append('workout_id', W.id);
    fd.append('kcal', total); fd.append('duration', elapsed);
    fetch(TF.baseUrl + '/api/save-workout.php', { method: 'POST', body: fd })
      .then(r => r.json()).then(j => { if (j.ok) toast('✔ Workout database me save ho gaya!'); }).catch(() => {});
  }
}
