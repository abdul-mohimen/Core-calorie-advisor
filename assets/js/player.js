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

/* ---- PHASE G: explicit state machine ----
   Was: bare strings compared with `===` all over tick(). The comment at the top
   of tick() records what that cost — one missing guard let `remain` run past 0
   into the "exercise ended" branch and SILENTLY SKIPPED an exercise. Naming the
   states and routing every change through transition() makes that class of bug
   structurally impossible: an unknown target throws instead of falling through. */
const PHASE = Object.freeze({
  IDLE: 'idle', PREVIEW: 'preview', READY: 'ready', EX: 'ex', REST: 'rest', DONE: 'done'
});
const LEGAL = Object.freeze({
  idle:    ['preview', 'ready'],
  preview: ['ready', 'ex'],
  ready:   ['ex', 'preview'],
  ex:      ['rest', 'ex', 'preview', 'done'],
  rest:    ['ex', 'preview', 'done'],
  done:    []
});
function transition(to) {
  const from = phase;
  if (!Object.values(PHASE).includes(to)) throw new Error('[player] unknown phase: ' + to);
  console.assert(LEGAL[from] && LEGAL[from].indexOf(to) !== -1,
                 '[player] illegal transition ' + from + ' -> ' + to);
  phase = to;
  return from;
}

let idx = 0, phase = PHASE.IDLE, remain = 0, phaseDur = 0,
    elapsed = 0, kcal = 0, paused = false, tickTimer = null, blockNo = 1;
const restAfter = new Set();   // plan indices that are followed by a rest

/* PHASE G session telemetry — sent to api/save-workout.php on finish. */
let restAddedSeconds = 0, skipsUsed = 0, restAddedThisRest = 0;

const el = id => document.getElementById(id);

/* Pre-compute block boundaries & enforce constraints */
/* ---- PHASE G: work-interval normalisation ----
   Target 30 s. A trainer-authored value inside 25–35 s is respected as authored;
   anything outside that band is normalised to 30 rather than silently obeyed,
   because a 600 s "exercise" used to eat an entire session on its own. */
const WORK_TARGET = 30, WORK_MIN = 25, WORK_MAX = 35;
function normalisedSeconds(raw) {
  const s = Number(raw) || WORK_TARGET;
  return (s >= WORK_MIN && s <= WORK_MAX) ? Math.round(s) : WORK_TARGET;
}

/* PLAN is the actual session queue. It is NOT the same array as EX: a short
   workout is CYCLED to fill the chosen duration, so one source exercise can
   appear several times. idx indexes PLAN; EX stays the untouched source list. */
let PLAN = [];
let plannedTotal = 0;          // ready + work + rest, in seconds
let targetSeconds = 600;       // overwritten by the duration selector

/* ---- PHASE G: build a session that FITS the target ----
   Was: walk EX once, stop when 600 s is hit, then `EX.splice(allowedCount)` —
   which silently deleted the trainer's remaining exercises and always produced
   a session shorter than advertised. Now the queue cycles to fill the budget and
   never ends mid-block; whatever it actually adds up to is reported to the user. */
function planBlocks(target) {
  restAfter.clear();
  PLAN = [];
  targetSeconds = target;

  if (!EX.length) { plannedTotal = 0; return 0; }
  EX.forEach(e => { e.seconds = normalisedSeconds(e.seconds); });

  const budget = target - READY_SEC;      // get-ready counts against the total
  let used = 0, i = 0, guard = 0;

  while (guard++ < 500) {
    const src = EX[i % EX.length];
    const isBlockEnd = ((PLAN.length + 1) % 3 === 0);
    const cost = src.seconds + (isBlockEnd ? REST_SEC : 0);

    /* Stop on a whole block: adding this one would overshoot, so end here rather
       than truncate a block half-built. */
    if (used + cost > budget) break;

    PLAN.push(src);
    used += cost;
    if (isBlockEnd) restAfter.add(PLAN.length - 1);
    i++;
  }

  /* Never ship an empty session: if even one interval overshoots the budget
     (e.g. a 10-min target with a pathological source), keep a single exercise. */
  if (!PLAN.length) { PLAN.push(EX[0]); used = EX[0].seconds; }

  /* A rest after the final exercise is pointless — drop it and refund the time. */
  const lastIdx = PLAN.length - 1;
  if (restAfter.has(lastIdx)) { restAfter.delete(lastIdx); used -= REST_SEC; }

  plannedTotal = used + READY_SEC;
  return plannedTotal;
}

const mmss = s => Math.floor(s / 60) + ':' + String(Math.round(s % 60)).padStart(2, '0');

/* N is derived from PLAN and therefore cannot be a module-level const any more. */
function planLength() { return PLAN.length; }
function totalBlocks() { return restAfter.size + 1; }

/* Seconds still to play: current phase remainder + everything after it. */
function remainingSeconds() {
  let s = remain;
  for (let i = idx + 1; i < PLAN.length; i++) {
    s += PLAN[i].seconds;
    if (restAfter.has(i)) s += REST_SEC;
  }
  if (phase === PHASE.EX && restAfter.has(idx)) s += REST_SEC;
  return Math.max(0, s);
}

/* ---- PHASE G: duration selector ----
   10 / 15 / 20 minutes, default 10. Reads the pressed segment; falls back to
   10 min if the control is absent (the engine must still run without the UI). */
const DURATIONS = [600, 900, 1200];
function selectedTarget() {
  const on = document.querySelector('#durationPick .dur-opt.is-on');
  const v = on && Number(on.dataset.seconds);
  return DURATIONS.indexOf(v) !== -1 ? v : 600;
}
function pickDuration(btn) {
  document.querySelectorAll('#durationPick .dur-opt').forEach(b => b.classList.remove('is-on'));
  btn.classList.add('is-on');
  echoDuration(planBlocks(Number(btn.dataset.seconds)));
}

/* Report the ACTUAL total, never the requested one. Tolerance is ±45 s; outside
   that the shortfall is stated plainly rather than hidden. */
function echoDuration(actual) {
  const out = el('durationEcho');
  if (!out) return;
  const drift = Math.abs(actual - targetSeconds);
  let s = 'Your session: ' + mmss(actual) + ' · ' + planLength() + ' exercises · ' + totalBlocks() + ' block' + (totalBlocks() === 1 ? '' : 's');
  if (drift > 45) s += ' — ' + mmss(drift) + ' under target (this workout does not divide evenly)';
  out.textContent = s;
}

/* Populate the echo on load so the default 10 min is not an unexplained number. */
document.addEventListener('DOMContentLoaded', function () {
  if (el('durationPick')) echoDuration(planBlocks(selectedTarget()));
});

function startPlayer() {
  if (tickTimer) return;
  if (!EX.length) { toast('This workout has no exercises yet. Please choose another program.'); return; }

  /* Build the queue for the chosen duration at START time, not at load time, so
     the selector actually changes the session. */
  planBlocks(selectedTarget());
  if (!planLength()) { toast('Could not build a session from this workout.'); return; }

  idx = 0; elapsed = 0; kcal = 0; paused = false; blockNo = 1;
  restAddedSeconds = 0; skipsUsed = 0; restAddedThisRest = 0;
  el('playerIntro').style.display = 'none';
  el('playerUI').style.display = 'block';
  window.dispatchEvent(new Event('resize'));
  buildSegments();
  // Phase 12: demonstrate the first move fast, THEN start the get-ready countdown.
  enterPreview(PLAN[0], enterReady);
  tickTimer = setInterval(tick, 1000);
}

/* The progress strip is rendered server-side for the source list; PLAN can be
   longer (cycled rounds), so rebuild it to match what will actually be played. */
function buildSegments() {
  const host = el('progressContainer') || (el('seg_0') && el('seg_0').parentNode);
  if (!host) return;
  host.innerHTML = '';
  for (let i = 0; i < PLAN.length; i++) {
    const s = document.createElement('div');
    s.id = 'seg_' + i;
    s.className = 'progress-segment';
    host.appendChild(s);
  }
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

  transition(PHASE.PREVIEW);
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
  transition(PHASE.READY); remain = READY_SEC; phaseDur = READY_SEC;
  el('restScreen').classList.add('on');
  el('restKicker').textContent = 'GET READY';
  el('restKicker').style.fontSize = '3rem';
  el('restNext').textContent = PLAN[0].name;
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
  if (window.TitanTrainer) TitanTrainer.announce('ready', PLAN[0].name, W.title);
  updateUI();
}

function loadExercise() {
  const e = PLAN[idx];
  /* Phase 12: if this exercise has not been demonstrated yet this session,
     show the fast preview first and re-enter once it finishes. enterPreview
     resets the timescale to 1 before calling back, so the rep below always
     runs at real speed. */
  if (e && e.name && !previewed.has(e.name) && phase !== PHASE.PREVIEW) {
    enterPreview(e, loadExercise);
    return;
  }
  transition(PHASE.EX); remain = e.seconds; phaseDur = e.seconds;
  el('restScreen').classList.remove('on');
  el('pProg').textContent = 'Exercise ' + (idx + 1) + ' / ' + planLength() + ' · Block ' + blockNo + ' of ' + totalBlocks();
  el('pName').innerHTML = e.name + ' <span class="info-icon" title="Watch your form!">i</span>';
  setAnim(e.anim || (typeof MODE_MAP !== 'undefined' && MODE_MAP[e.name]) || 'idle');
  if (window.TitanTrainer) TitanTrainer.announce('go', e.name);
  updateUI();
}

function enterRest() {
  transition(PHASE.REST); remain = REST_SEC; phaseDur = REST_SEC;
  restAddedThisRest = 0;
  el('restScreen').classList.add('on');
  el('restKicker').textContent = 'REST';
  el('restKicker').style.fontSize = '';
  el('restNext').textContent = PLAN[idx + 1] ? PLAN[idx + 1].name : '—';
  el('pName').innerHTML = 'Rest <span class="info-icon" title="Breathe and recover.">i</span>';
  if (el('restSpinner')) el('restSpinner').style.display = 'none';
  if (el('restNum')) el('restNum').style.display = 'block';
  if (el('restControls')) el('restControls').style.display = 'block';
  setAnim('warmup');
  if (window.TitanTrainer) TitanTrainer.announce('rest', PLAN[idx + 1] ? PLAN[idx + 1].name : null);
  updateUI();
}

/* ---- Clock ---- */
function tick() {
  if (paused) return;
  /* The Phase 12 preview is driven by its own timer, not this 1s clock. Without
     this guard `remain` would tick 0 → -1, fall straight past the `remain > 0`
     check, match none of the phase branches below, and drop into the
     "an exercise just ended" path — silently SKIPPING an exercise. */
  if (phase === PHASE.PREVIEW) return;
  remain--; elapsed++;
  if (phase === PHASE.EX) kcal += (PLAN[idx].kcal || 0) / PLAN[idx].seconds;
  if (window.TitanTrainer) {
    if (phase === PHASE.EX && remain === 5) TitanTrainer.announce('countdown');
    // halfway motivation only on longer intervals so short moves aren't spammed
    if (phase === PHASE.EX && phaseDur >= 24 && remain === Math.floor(phaseDur / 2)) TitanTrainer.announce('halfway');
    // spoken 3-2-1-Go so the next move starts ON the beat
    if ((phase === PHASE.READY || phase === PHASE.REST) && remain === 3) TitanTrainer.announce('readycount');
  }
  if (remain > 0) { updateUI(); return; }

  if (phase === PHASE.READY) { loadExercise(); return; }
  if (phase === PHASE.REST)  { idx++; blockNo++; loadExercise(); return; }
  
  // an exercise just ended
  if (idx >= planLength() - 1) { finishWorkout(); return; }
  if (restAfter.has(idx))  { enterRest(); return; }
  idx++; loadExercise();
}

function updateUI() {
  const overlay = (phase === PHASE.REST || phase === PHASE.READY);
  if (el('pTimer')) el('pTimer').textContent = String(remain).padStart(2, '0') + 's';
  const restNumEl = el('restNum');
  if (restNumEl) {
    restNumEl.textContent = overlay ? remain : '';
    // Countdown pulse during last 3 seconds of GET READY
    if (phase === PHASE.READY && remain <= 3 && remain > 0) {
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
  
  for (let i = 0; i < planLength(); i++) {
    const seg = el('seg_' + i);
    if (!seg) continue;
    if (i < idx) seg.className = 'progress-segment done';
    else if (i === idx) seg.className = 'progress-segment active';
    else seg.className = 'progress-segment';
  }

  /* ---- PHASE G: rest-overlay context ----
     The overlay used to show only REST / 20 / next exercise, which gave no sense
     of how much session was left. */
  if (overlay) {
    const b = el('restBlock');
    if (b) b.textContent = 'Block ' + Math.min(blockNo, totalBlocks()) + ' of ' + totalBlocks();
    const left = el('restLeft');
    if (left) left.textContent = mmss(remainingSeconds()) + ' left';
    const then = el('restThen');
    if (then) {
      const nn = PLAN[idx + 2];
      then.textContent = nn ? 'Then: ' + nn.name : '';
      then.style.display = nn ? '' : 'none';
    }
  }

  /* Disable +20s once this rest has taken its 60 s, with a reason. */
  const addBtn = el('restAddBtn');
  if (addBtn) {
    const capped = restAddedThisRest + REST_ADD_STEP > REST_ADD_MAX;
    addBtn.disabled = capped;
    addBtn.style.opacity = capped ? '.45' : '';
    addBtn.style.cursor = capped ? 'not-allowed' : '';
    addBtn.title = capped ? 'Maximum +' + REST_ADD_MAX + 's per rest reached'
                          : 'Add ' + REST_ADD_STEP + ' seconds to this rest';
  }
}

/* ---- Controls ---- */
/* ---- PHASE G: pause is real ----
   Was: a bare boolean flip, so the 1 s clock stopped but the rig kept animating —
   the trainer carried on exercising over a paused timer. Freezing the shared
   preview scale to 0 stops the rig's clock too (pages/player.php multiplies the
   rig delta by TF_PREVIEW.scale each frame). */
function togglePause(btn) {
  paused = !paused;
  if (window.TF_PREVIEW) TF_PREVIEW.scale = paused ? 0 : (TF_PREVIEW.active ? PREVIEW_TIMESCALE : 1);
  document.querySelectorAll('[data-role="pause"]').forEach(b => {
    b.textContent = paused ? '▶ Resume' : '⏸ Pause';
  });
  if (btn && !btn.dataset.role) btn.textContent = paused ? '▶ Resume' : '⏸ Pause';
  updateUI();
}

function skipPhase() { skipsUsed++; remain = 1; }

/* Skip is offered during REST and GET READY. It is deliberately NOT offered
   during an exercise — that would let a user "complete" a workout without
   performing it, and kcal is accrued per elapsed second. */
function skipRest() {
  if (phase !== PHASE.REST && phase !== PHASE.READY) return;
  skipsUsed++;
  remain = 1;
  updateUI();
}

/* +20 s, capped at +60 s per rest. Without a cap a user can rest indefinitely
   and still claim a completed session. */
const REST_ADD_STEP = 20, REST_ADD_MAX = 60;
function addRest() {
  if (phase !== PHASE.REST && phase !== PHASE.READY) return;
  if (restAddedThisRest + REST_ADD_STEP > REST_ADD_MAX) return;
  restAddedThisRest += REST_ADD_STEP;
  restAddedSeconds  += REST_ADD_STEP;
  remain   += REST_ADD_STEP;
  phaseDur += REST_ADD_STEP;
  updateUI();
}
function setAnim(m) { if (typeof setMode === 'function') setMode(m); }

/* ---- Finish ---- */
function finishWorkout() {
  transition(PHASE.DONE);
  clearInterval(tickTimer); tickTimer = null;
  if (window.TF_PREVIEW) TF_PREVIEW.scale = 1;   // never leave the rig frozen
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
    /* PHASE G telemetry — lets admin analytics tell a completed session apart
       from one that was rested and skipped to the end. */
    fd.append('target_seconds',     targetSeconds);
    fd.append('blocks_completed',   Math.min(blockNo, totalBlocks()));
    fd.append('rest_added_seconds', restAddedSeconds);
    fd.append('skips_used',         skipsUsed);
    fetch(TF.baseUrl + '/api/save-workout.php', { method: 'POST', body: fd })
      .then(r => r.json()).then(j => { if (j.ok) toast('✔ Workout database me save ho gaya!'); }).catch(() => {});
  }
}
