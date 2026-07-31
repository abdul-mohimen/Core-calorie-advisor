<?php
require_once dirname(__DIR__) . '/config/config.php';
$id = (int)get('id', '0');
$st = db()->prepare('SELECT * FROM workouts WHERE id = ?');
$st->execute([$id]); $w = $st->fetch();
if (!$w) { flash('err', 'Workout nahi mila.'); redirect('pages/workouts.php'); }
$isPro = (bool)($w['is_pro'] ?? !($w['is_free'] ?? 1));
if ($isPro) {
    if (!is_logged_in()) { flash('warn', '🔒 PRO workout — pehle login!'); redirect('auth/login.php'); }
    if (!is_pro())       { flash('warn', '🔒 PRO workout — subscription chahiye!'); redirect('pages/pricing.php'); }
}
$ex = db()->prepare('SELECT name, seconds, kcal, anim_mode FROM exercises WHERE workout_id = ? ORDER BY sort_order');
$ex->execute([$id]); $exercises = $ex->fetchAll();
$workoutTitle = $w['name'] ?? $w['title'] ?? 'Workout';
// Single source of truth for the EXACT card the user picked — carried through to the
// player so titles/queue/animation can never fall back to a different (e.g. yoga) workout.
$payload = ['id' => (int)$w['id'], 'title' => $workoutTitle,
  'slug' => $w['slug'] ?? '', 'category' => $w['category'] ?? '',
  'ex' => array_map(fn($e) => ['name' => $e['name'], 'seconds' => (int)$e['seconds'], 'kcal' => (int)$e['kcal'], 'anim' => $e['anim_mode']], $exercises)];
$pageTitle = 'Player — ' . $workoutTitle;
include dirname(__DIR__) . '/includes/header.php';
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    corePlugins: { preflight: false },
    theme: {
      extend: {
        colors: {
          neonCyan: '#FF6B1A',
          neonRed: '#FF3300',
          darkBg: '#0A0A0A',
          darkCard: '#111827',
          darkGlass: 'rgba(255,107,26,0.06)',
          goldAccent: '#FF6B1A'
        },
        fontFamily: {
          inter: ['Inter', 'sans-serif'],
          mono: ['JetBrains Mono', 'monospace'],

        }
      }
    }
  }
</script>
<style>
/* ── ExactFit 3D Dark Theme Overrides ── */
.wrap.player.page-hero {
  max-width: 100% !important;
  padding: 0 !important;
  margin: 0 !important;
}
#playerUI { display: none; }
#playerUI[style*="display: block"] { display: flex !important; }

/* PHASE G — session-length segmented control. Uses the existing accent tokens;
   no new font, icon pack or CDN is introduced. */
.dur-opt {
  flex: 1; padding: 11px 8px; border-radius: 12px;
  border: 1px solid rgba(255,255,255,0.10);
  background: rgba(255,255,255,0.04);
  color: #9CA3AF; font-weight: 800; font-size: 13px;
  letter-spacing: .12em; text-transform: uppercase;
  cursor: pointer; transition: all .24s cubic-bezier(.32,.72,.28,1);
}
.dur-opt:hover { background: rgba(255,255,255,0.08); color: #E5E7EB; }
.dur-opt:active { transform: scale(.96); }
.dur-opt:focus-visible { outline: 2px solid var(--primary); outline-offset: 2px; }
.dur-opt.is-on {
  background: linear-gradient(90deg, #FF6B1A, #FF3D00);
  border-color: rgba(255,107,26,.5); color: #fff;
  box-shadow: 0 6px 18px rgba(255,107,26,.22);
}

/* Segmented progress */
.segmented-progress .progress-segment {
  height: 5px; border-radius: 3px;
  background-color: rgba(255,107,26,0.15);
  transition: all 0.4s cubic-bezier(0.4,0,0.2,1); flex: 1;
}
.segmented-progress .progress-segment.active {
  background-color: var(--primary) !important;
  box-shadow: 0 0 12px rgba(255,107,26,0.7);
}
.segmented-progress .progress-segment.done {
  background-color: rgba(255,255,255,0.6) !important;
}

/* Rest overlay */
#restScreen { opacity: 0; pointer-events: none; transition: all 0.5s cubic-bezier(0.4,0,0.2,1); }
#restScreen.on { opacity: 1 !important; pointer-events: auto !important; }

/* Glow utility classes */
.glow-cyan { text-shadow: 0 0 10px rgba(255,107,26,0.6), 0 0 30px rgba(255,107,26,0.3); }
.glow-red  { text-shadow: 0 0 10px rgba(255,51,0,0.6); }
.border-glow-cyan { box-shadow: 0 0 15px rgba(255,107,26,0.15), inset 0 0 15px rgba(255,107,26,0.05); }

/* Waveform animation */
@keyframes waveBar {
  0%, 100% { height: 4px; }
  50% { height: 18px; }
}
.wave-bar {
  width: 3px; border-radius: 2px; background: var(--primary);
  animation: waveBar 0.8s ease-in-out infinite;
}
.wave-bar:nth-child(2) { animation-delay: 0.1s; }
.wave-bar:nth-child(3) { animation-delay: 0.2s; }
.wave-bar:nth-child(4) { animation-delay: 0.3s; }
.wave-bar:nth-child(5) { animation-delay: 0.15s; }
.wave-bar:nth-child(6) { animation-delay: 0.25s; }
.wave-bar:nth-child(7) { animation-delay: 0.35s; }

/* Pulse ring on muscle SVG */
@keyframes pulseRing {
  0% { transform: scale(0.95); opacity: 0.7; }
  50% { transform: scale(1.05); opacity: 1; }
  100% { transform: scale(0.95); opacity: 0.7; }
}
.pulse-ring { animation: pulseRing 2s ease-in-out infinite; }

/* Canvas styling */
#workout-3d-arena canvas { display: block; width: 100% !important; height: 100% !important; }

/* Scrollbar hide for panels */
.hide-scrollbar::-webkit-scrollbar { display: none; }
.hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

/* Countdown pulse for last 3 seconds of GET READY */
@keyframes countdownPulse {
  0% { transform: scale(1); opacity: 1; }
  50% { transform: scale(1.25); opacity: .85; text-shadow: 0 0 40px rgba(255,107,26,.7); }
  100% { transform: scale(1); opacity: 1; }
}
#restNum.pulse-countdown {
  animation: countdownPulse .5s ease-in-out;
  color: var(--primary-text) !important;
}
</style>

<section class="wrap player page-hero w-full font-inter">

  <!-- ══════════════════ 1. INTRO HERO SECTION ══════════════════ -->
  <div id="playerIntro" class="relative w-full min-h-[90vh] flex items-center justify-center overflow-hidden bg-[#0A0A0A] py-16 px-4">
    <!-- Background Image with Ken Burns effect / transition -->
    <div class="absolute inset-0 bg-cover bg-center z-0 opacity-30 scale-105 transform transition-all duration-1000"
         style="background-image: url('<?= e($w['image'] ?? 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=1600&q=75&auto=format&fit=crop') ?>');">
    </div>
    <!-- Dark fading gradient into the main content -->
    <div class="absolute inset-0 z-10 bg-gradient-to-t from-[#0A0A0A] via-[#0A0A0A]/90 to-[#0A0A0A]/50"></div>

    <div class="relative z-20 max-w-3xl w-full text-center px-4">
      <!-- Gold/Orange Eyebrow Accent -->
      <span class="inline-flex items-center gap-2 px-5 py-2 rounded-full text-xs font-black bg-[#FF6B1A]/10 text-[#FF6B1A] border border-[#FF6B1A]/25 mb-6 uppercase tracking-[0.25em]">
        <span class="w-2.5 h-2.5 rounded-full bg-[#FF6B1A] animate-pulse"></span>
        <?= e($w['tag'] ?? 'CCA WORKOUT') ?>
      </span>

      <!-- Bold Aggressive Title -->
      <h1 class="text-6xl md:text-8xl font-black text-white uppercase tracking-widest leading-none mb-4 cca-font-disp drop-shadow-[0_0_15px_rgba(255,107,26,0.35)]">
        <?= e($workoutTitle) ?>
      </h1>

      <!-- Orange Glowing Subtext -->
      <p class="text-[#FF6B1A] text-sm md:text-lg font-bold tracking-[0.15em] uppercase mb-8 cca-font-disp drop-shadow-[0_0_8px_rgba(255,107,26,0.5)]">
        <?= count($exercises) ?> exercises · ExactFit rhythm · Real-time 3D muscle activation
      </p>

      <!-- Subtext description -->
      <p class="text-gray-400 text-sm md:text-base max-w-xl mx-auto mb-10 leading-relaxed font-medium">
        <?= e($w['description'] ?? 'Prepare to forge your ultimate physique. Follow the 3D trainer and synchronize your heart rate with our dynamic soundscapes.') ?>
      </p>

      <div class="flex items-center justify-center gap-4 mb-10 flex-wrap">
        <div class="flex items-center gap-2 text-xs text-[#FF6B1A] font-bold tracking-widest uppercase"><span class="w-2 h-2 bg-[#FF6B1A] rounded-full shadow-[0_0_8px_#FF6B1A]"></span>3D ARENA</div>
        <div class="w-px h-4 bg-white/20"></div>
        <div class="flex items-center gap-2 text-xs text-[#FF6B1A] font-bold tracking-widest uppercase"><span class="w-2 h-2 bg-[#FF6B1A] rounded-full shadow-[0_0_8px_#FF6B1A]"></span>VOICE SYNC</div>
        <div class="w-px h-4 bg-white/20"></div>
        <div class="flex items-center gap-2 text-xs text-[#FF6B1A] font-bold tracking-widest uppercase"><span class="w-2 h-2 bg-[#FF6B1A] rounded-full shadow-[0_0_8px_#FF6B1A]"></span>MUSCLE TRACK</div>
      </div>

      <!-- ── Who's coaching ────────────────────────────────────────────
           The full picker lives on its own page (pages/trainer-studio.php);
           this is just a read-out of the saved choice with a way in. -->
      <div class="mx-auto mb-9 w-full max-w-md rounded-2xl border border-white/10 bg-black/35 backdrop-blur-sm px-5 py-4
                  flex items-center justify-between gap-4">
        <div class="text-left">
          <div class="text-[9px] font-black tracking-[3px] uppercase text-[#FF6B1A] font-mono mb-1">Your trainer</div>
          <div id="loadoutSummary" class="text-sm font-bold text-white">Loading…</div>
        </div>
        <a href="<?= url('pages/trainer-studio.php?next=pages/player.php%3Fid=' . (int)$w['id']) ?>"
           class="shrink-0 px-4 py-2 rounded-xl border border-[#FF6B1A]/50 text-[#FF6B1A] hover:bg-[#FF6B1A]/15
                  text-[11px] font-black tracking-widest uppercase transition no-underline">Change</a>
      </div>

      <!-- PHASE G: session length. The engine used to hard-cap every session at
           600 s and silently splice off any exercise that did not fit. -->
      <div class="w-full max-w-md mb-6">
        <div class="text-[9px] font-black tracking-[3px] uppercase text-[#FF6B1A] font-mono mb-2 text-center">Session length</div>
        <div id="durationPick" class="flex gap-2 justify-center" role="group" aria-label="Session length">
          <button type="button" class="dur-opt is-on" data-seconds="600"  onclick="pickDuration(this)">10 min</button>
          <button type="button" class="dur-opt"       data-seconds="900"  onclick="pickDuration(this)">15 min</button>
          <button type="button" class="dur-opt"       data-seconds="1200" onclick="pickDuration(this)">20 min</button>
        </div>
        <p id="durationEcho" class="text-[11px] text-gray-500 font-mono text-center mt-2.5 tracking-wide"></p>
      </div>

      <!-- Aggressive Action Button -->
      <button class="px-12 py-5 bg-gradient-to-r from-[#FF6B1A] to-[#FF3D00] hover:from-[#FF8833] hover:to-[#FF6B1A] text-white font-extrabold rounded-2xl shadow-xl shadow-[#FF6B1A]/20 active:scale-[0.97] transition-all tracking-[0.2em] text-base uppercase cca-font-disp"
              onclick="startPlayer()">
        ▶ Begin Forging
      </button>
    </div>
  </div>

  <!-- ══════════════════ 2. MAIN PLAYER STAGE ══════════════════ -->
  <div id="playerUI" style="display:none" class="relative w-full h-screen min-h-[700px] overflow-hidden flex flex-col justify-between text-white select-none font-inter" style="background: var(--stage-bg);">

    <!-- Three.js Canvas Container -->
    <div id="workout-3d-arena" class="absolute inset-0 w-full h-full z-0" style="background: var(--stage-bg);"></div>

    <!-- 3D Loader -->
    <div id="arena-loader" class="absolute inset-0 z-30 flex flex-col items-center justify-center text-white" style="background: var(--stage-bg);">
      <div class="relative w-24 h-24 flex items-center justify-center mb-6">
        <div class="absolute inset-0 rounded-full border-2 border-[#FF6B1A]/20 border-t-[#FF6B1A] animate-spin shadow-[0_0_15px_rgba(255,107,26,0.4)]"></div>
        <div class="absolute inset-2 rounded-full border border-[#FF6B1A]/25 border-b-[#FF6B1A] animate-spin" style="animation-direction: reverse; animation-duration: 1.5s;"></div>
        <div class="text-[#FF6B1A] text-2xl font-black font-mono tracking-widest cca-font-disp drop-shadow-[0_0_8px_rgba(255,107,26,0.5)]">TF</div>
      </div>
      <h3 class="text-xl font-bold tracking-[6px] uppercase text-[#FF6B1A] cca-font-disp drop-shadow-[0_0_10px_rgba(255,107,26,0.5)]">Initializing 3D Arena</h3>
      <p class="text-xs text-gray-500 mt-3 font-semibold uppercase tracking-[4px] animate-pulse">Syncing muscle sensors...</p>
    </div>

    <!-- ── TOP HUD BAR ── -->
    <div class="relative z-10 w-full px-5 md:px-8 pt-5 pb-4 bg-gradient-to-b from-[#0A0A0A]/95 via-[#0A0A0A]/60 to-transparent flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
      <div class="flex-shrink-0">
        <p id="pProg" class="text-xs font-bold tracking-[4px] text-[#FF6B1A] uppercase cca-font-disp drop-shadow-[0_0_5px_rgba(255,107,26,0.3)]">Block 1 · Warm-up</p>
        <h2 id="pName" class="text-2xl md:text-3xl font-black text-white mt-1 leading-none flex items-center gap-2 uppercase tracking-wide cca-font-disp">Get Ready <span class="text-gray-500 text-xs cursor-pointer hover:text-[#FF6B1A] transition-colors font-mono">ⓘ</span></h2>
      </div>

      <!-- Progress Bar -->
      <div class="flex-1 max-w-xl mx-0 md:mx-8 w-full">
        <div class="flex items-center justify-between text-[10px] text-gray-500 mb-1.5 font-bold tracking-[3px] uppercase cca-font-disp">
          <span>Workout Timeline</span>
          <span class="flex items-center gap-1.5">⏱ <span id="pTotal" class="text-[#FF6B1A] font-bold">0:00</span></span>
        </div>
        <div class="segmented-progress flex gap-1 w-full" id="progressContainer">
          <?php foreach ($exercises as $i => $e): ?>
            <div class="progress-segment" id="seg_<?= $i ?>"></div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Control Buttons -->
      <div class="flex items-center gap-2 flex-shrink-0">
        <button class="px-5 py-3 bg-white/5 hover:bg-[#FF6B1A]/15 border border-[#FF6B1A]/20 hover:border-[#FF6B1A]/40 rounded-xl text-xs font-bold transition active:scale-95 flex items-center gap-1.5 backdrop-blur-md text-gray-300 hover:text-[#FF6B1A] tracking-wider uppercase cca-font-disp" onclick="togglePause(this)">⏸ Pause</button>
        <a class="px-5 py-3 bg-white/5 hover:bg-red-500/15 border border-white/10 hover:border-red-500/30 rounded-xl text-xs font-bold transition active:scale-95 flex items-center gap-1.5 text-gray-400 hover:text-red-400 backdrop-blur-md tracking-wider uppercase cca-font-disp no-underline" href="<?= url('pages/workout-detail.php?id=' . $w['id']) ?>">✕ Quit</a>
      </div>
    </div>

    <!-- ── RIGHT SIDE: EXACT MUSCLE ACTIVATION PANEL ── -->
    <div class="absolute right-4 md:right-8 top-1/2 -translate-y-1/2 z-10 w-[220px] md:w-[260px] pointer-events-auto hide-scrollbar">
      <div class="rounded-2xl border border-[#FF6B1A]/20 p-5 shadow-[0_0_15px_rgba(255,107,26,0.08)]" style="background: color-mix(in srgb, var(--stage-bg) 85%, transparent); backdrop-filter: blur(16px);">
        <!-- Header -->
        <div class="flex items-center gap-2 mb-4">
          <div class="w-2 h-2 rounded-full bg-[#FF6B1A] animate-pulse"></div>
          <span class="text-[10px] font-black tracking-[3px] text-[#FF6B1A] uppercase cca-font-disp">Exact Muscle Activation</span>
        </div>

        <!-- Hidden container for compatibility elements -->
        <div style="display:none">
          <svg id="anatomySvg" viewBox="0 0 120 220" class="w-28 h-auto pulse-ring" xmlns="http://www.w3.org/2000/svg">
            <ellipse cx="60" cy="22" rx="14" ry="17" fill="none" stroke="#FF6B1A" stroke-width="1" opacity="0.4"/>
            <rect x="55" y="38" width="10" height="8" fill="none" stroke="#FF6B1A" stroke-width="0.8" opacity="0.3"/>
            <path d="M38 46 L82 46 L86 120 L34 120 Z" fill="none" stroke="#FF6B1A" stroke-width="1" opacity="0.4"/>
            <path d="M38 46 L22 58 L18 95 L24 95 L30 65 L38 56" fill="none" stroke="#FF6B1A" stroke-width="0.8" opacity="0.3"/>
            <path d="M82 46 L98 58 L102 95 L96 95 L90 65 L82 56" fill="none" stroke="#FF6B1A" stroke-width="0.8" opacity="0.3"/>
            <path d="M42 120 L38 170 L34 210 L44 210 L48 170 L52 120" fill="none" stroke="#FF6B1A" stroke-width="0.8" opacity="0.3"/>
            <path d="M68 120 L72 170 L76 210 L66 210 L62 170 L58 120" fill="none" stroke="#FF6B1A" stroke-width="0.8" opacity="0.3"/>
            <path id="muscleHighlight" d="M36 75 L42 65 L42 105 L36 110 Z" fill="#FF3300" opacity="0.5" class="pulse-ring"/>
            <path id="muscleHighlight2" d="M84 75 L78 65 L78 105 L84 110 Z" fill="#FF3300" opacity="0.5" class="pulse-ring"/>
            <rect id="coreHighlight" x="46" y="70" width="28" height="40" rx="4" fill="#FF3300" opacity="0.2"/>
          </svg>
        </div>

        <!-- Muscle Name + % -->
        <div class="mb-3">
          <div class="flex items-center justify-between mb-1.5">
            <span id="muscleName" class="text-sm font-extrabold text-white uppercase tracking-wider cca-font-disp">Obliques</span>
            <span id="musclePct" class="text-sm font-black text-[#FF6B1A] font-mono glow-cyan">85%</span>
          </div>
          <div class="w-full bg-white/10 h-2.5 rounded-full overflow-hidden">
            <div id="muscleBar" class="h-full rounded-full transition-all duration-700 ease-out" style="width: 85%; background: linear-gradient(90deg, var(--primary), var(--primary-hot));"></div>
          </div>
        </div>

        <!-- Status line -->
        <div class="flex items-center gap-1.5 mt-3">
          <div class="w-1.5 h-1.5 bg-green-400 rounded-full animate-pulse"></div>
          <span id="hud-muscle-activation" class="text-[10px] text-gray-400 font-mono tracking-wider uppercase font-semibold">Calibrating sensor...</span>
        </div>

        <!-- Hidden bar for player.js compatibility -->
        <div class="hidden"><div id="hud-activation-bar"></div></div>
      </div>
    </div>

    <!-- ── REST & GET READY OVERLAY ── -->
    <div id="restScreen" class="absolute inset-0 z-20 flex flex-col items-center justify-center bg-[#0A0A0A]/95 backdrop-blur-md">
      <div class="text-center p-10 rounded-3xl border border-[#FF6B1A]/20 max-w-md w-full mx-4 relative overflow-hidden shadow-[0_0_30px_rgba(255,107,26,0.1)]" style="background: var(--stage-bg);">
        <div class="absolute -right-20 -top-20 w-44 h-44 bg-[#FF6B1A]/10 rounded-full blur-3xl"></div>
        <div class="absolute -left-20 -bottom-20 w-44 h-44 bg-[#FF6B1A]/5 rounded-full blur-3xl"></div>

        <div id="restSpinner" class="animate-spin rounded-full h-12 w-12 border-2 border-t-[#FF6B1A] border-r-transparent border-b-[#FF6B1A] border-l-transparent mx-auto mb-6" style="display:none"></div>
        <span id="restKicker" class="text-3xl font-black tracking-[8px] text-[#FF6B1A] uppercase block mb-2 cca-font-disp drop-shadow-[0_0_12px_rgba(255,107,26,0.6)]">REST</span>
        <div id="restNum" class="text-8xl font-black text-white my-4 cca-font-disp tracking-widest" style="text-shadow: 0 0 25px color-mix(in srgb, var(--primary) 40%, transparent);">20</div>
        <div class="w-full bg-white/10 h-px my-6"></div>
        <p class="text-xs text-gray-500 uppercase tracking-[4px] font-bold font-mono mb-2">Up Next</p>
        <span id="restNext" class="text-2xl font-extrabold text-white mb-8 block truncate uppercase tracking-wider cca-font-disp text-[#FF6B1A] drop-shadow-[0_0_8px_rgba(255,107,26,0.3)]">—</span>

        <!-- PHASE G: session context during rest — the overlay used to show only
             the countdown and the next move, with no sense of progress left. -->
        <div class="flex justify-center items-center gap-3 mb-5 text-[11px] font-bold uppercase tracking-[2px] font-mono text-gray-500">
          <span id="restBlock">Block 1 of 1</span>
          <span class="text-gray-700">·</span>
          <span id="restLeft" class="text-[#FF6B1A]">0:00 left</span>
        </div>
        <p id="restThen" class="text-xs text-gray-500 uppercase tracking-[3px] font-bold font-mono mb-6"></p>

        <!-- PHASE G: three controls. Rest could previously only be EXTENDED —
             skipping it meant reaching down to the bottom bar mid-rest. -->
        <!-- One row, no wrap: tracking is tightened rather than letting PAUSE
             drop to a second line, which read as a layout bug. -->
        <div id="restControls" class="flex flex-nowrap justify-center items-center gap-2">
          <button type="button" id="restAddBtn"
                  class="px-4 py-3.5 shrink-0 bg-white/5 hover:bg-white/10 border border-white/10 active:scale-95 text-gray-300 text-[13px] font-extrabold uppercase tracking-wider rounded-xl transition-all cca-font-disp disabled:hover:bg-white/5"
                  title="Add 20 seconds to this rest" onclick="addRest()">+20s</button>
          <button type="button"
                  class="px-6 py-3.5 shrink-0 bg-gradient-to-r from-[#FF6B1A] to-[#FF3D00] hover:from-[#FF8833] hover:to-[#FF6B1A] active:scale-95 text-white text-[13px] font-extrabold uppercase tracking-wider rounded-xl shadow-lg shadow-[#FF6B1A]/20 transition-all cca-font-disp"
                  onclick="skipRest()">Skip Rest ▶</button>
          <button type="button" data-role="pause"
                  class="px-4 py-3.5 shrink-0 bg-white/5 hover:bg-white/10 border border-white/10 active:scale-95 text-gray-300 text-[13px] font-extrabold uppercase tracking-wider rounded-xl transition-all cca-font-disp"
                  onclick="togglePause(this)">⏸ Pause</button>
        </div>
      </div>
    </div>


    <!-- ── BOTTOM PANEL: VOICE SYNC + TIMER ── -->
    <div class="relative z-10 w-full px-5 md:px-8 pb-5 pt-3 bg-gradient-to-t from-[#0A0A0A]/95 via-[#0A0A0A]/50 to-transparent pointer-events-none">
      <div class="flex flex-col md:flex-row items-end justify-between gap-4">

        <!-- Voice Sync Bar -->
        <div class="flex items-center gap-4 rounded-2xl border border-[#FF6B1A]/15 p-4 backdrop-blur-md pointer-events-auto w-full md:max-w-lg border-glow-cyan" style="background: color-mix(in srgb, var(--stage-bg) 75%, transparent);">
          <!-- Mic Icon — click to mute/unmute the coach -->
          <button type="button" id="voiceMuteBtn" title="Coach voice on/off"
                  class="w-11 h-11 rounded-xl bg-[#FF6B1A]/10 flex items-center justify-center border border-[#FF6B1A]/25 flex-shrink-0 cursor-pointer hover:bg-[#FF6B1A]/25 transition active:scale-95"
                  onclick="toggleCoachVoice(this)">
            <svg class="w-5 h-5 text-[#FF6B1A] pointer-events-none" fill="currentColor" viewBox="0 0 24 24"><path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3zm-1 1.93c-3.94-.49-7-3.85-7-7.93h2c0 3.31 2.69 6 6 6s6-2.69 6-6h2c0 4.08-3.06 7.44-7 7.93V20h4v2H8v-2h4v-4.07z"/></svg>
          </button>
          <!-- Waveform -->
          <div class="flex items-center gap-[3px] h-5 flex-shrink-0">
            <div class="wave-bar"></div><div class="wave-bar"></div><div class="wave-bar"></div>
            <div class="wave-bar"></div><div class="wave-bar"></div><div class="wave-bar"></div><div class="wave-bar"></div>
          </div>
          <!-- Voice Text -->
          <div class="flex-1 min-w-0">
            <div class="text-[9px] font-black text-[#FF6B1A] tracking-[3px] uppercase font-mono mb-0.5">Voice Sync</div>
            <div id="voiceSyncText" class="text-[11px] font-semibold text-gray-300 truncate">Voice: Synchronized — Ready to begin</div>
            <!-- Coach-voice picker: the Web Speech API can only use voices the
                 visitor has installed, so let them choose rather than silently
                 settling for whatever ranks first. Filled by assets/js/cca-voice.js. -->
            <div id="ccaVoicePickRow" class="mt-1"></div>
          </div>
        </div>

        <!-- Timer + Controls -->
        <div class="flex items-center gap-6 w-full md:w-auto justify-between md:justify-end pointer-events-auto">
          <div class="flex flex-col items-start md:items-end">
            <span class="text-[9px] font-black text-gray-600 tracking-[3px] uppercase font-mono">Stage Time</span>
            <div id="pTimer" class="text-4xl md:text-5xl font-black text-white font-mono tracking-tighter leading-none mt-1 glow-cyan">00s</div>
          </div>
          <div class="flex gap-2">
            <button class="px-4 py-3 bg-white/5 hover:bg-[#FF6B1A]/15 border border-[#FF6B1A]/15 hover:border-[#FF6B1A]/30 rounded-xl text-xs font-bold uppercase tracking-wider transition active:scale-95 text-gray-400 hover:text-[#FF6B1A]" onclick="skipPhase()">Skip ⏭</button>
            <button class="px-6 py-3 bg-gradient-to-r from-[#FF6B1A] to-[#FF3D00] hover:from-[#FF8833] hover:to-[#FF6B1A] border border-[#FF6B1A]/30 rounded-xl text-xs font-bold uppercase tracking-wider transition active:scale-95 shadow-lg shadow-[#FF6B1A]/20 text-white" onclick="skipPhase()">Next ▶</button>
          </div>
        </div>

      </div>
    </div>

  </div>
</section>

<!-- ══════════════════ CONGRATS SCREEN ══════════════════ -->
<div class="congrats" id="congrats">
  <div class="congrats-card" id="congCard" style="background: color-mix(in srgb, var(--stage-panel) 95%, transparent); border: 1px solid color-mix(in srgb, var(--primary) 25%, transparent);">
    <div style="font-size:66px">🏆</div>
    <h2>Congratulations, <span class="grad-text" style="background: linear-gradient(135deg, var(--primary), var(--primary-hot)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">CCA!</span></h2>
    <p style="color:var(--muted);font-family:var(--tech);letter-spacing:2px;text-transform:uppercase">Workout Complete — Calories Burned</p>
    <div class="cal-count" id="congCal" style="color:var(--primary-text);">0</div>
    <p style="color:var(--muted);margin:6px 0 24px">Aap <b style="color:var(--primary-text)" id="congMsg">0 kcal</b> apne goal ke qareeb ho. Forge ne aaj aap ko stronger banaya. 🔥</p>
    <div class="hero-cta" style="justify-content:center">
      <a class="btn btn-fire" href="<?= url('pages/workouts.php') ?>" style="background: linear-gradient(135deg, var(--primary), var(--primary-hot));">More Workouts</a>
      <a class="btn btn-ghost" href="<?= url('index.php') ?>">Home</a>
    </div>
  </div>
</div>



<?php
$playerJs = asset('js/player.js');
$ccaVoiceJs  = asset('js/cca-voice.js');
$ccaCoachJs  = asset('js/cca-coach.js');
$ccaWardrobeJs = asset('js/cca-wardrobe.js');
$titanRigJs = asset('js/titan-rig.js');
$json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);
$extraScripts = <<<HTML
<script>window.TF_WORKOUT = {$json};</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/DRACOLoader.js"></script>
<script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/GLTFLoader.js"></script>
<script src="{$ccaVoiceJs}"></script>
<script src="{$ccaCoachJs}"></script>
<script src="{$ccaWardrobeJs}"></script>
<script src="{$titanRigJs}"></script>
<script>
// ═══════════ EXACTFIT 3D ENGINE — VANILLA THREE.JS ═══════════

const MODE_MAP = {
  "Crunches":"crunch","Bicycle Crunches":"crunch","Russian Twists":"twist","Leg Raises":"legraise",
  "Plank":"plank","Side Plank":"plank","Mountain Climbers":"mountain","Push-Ups":"pushup",
  "Shoulder Press":"press","Bicep Curls":"curl","Bent-Over Rows":"curl","Squat Press":"press",
  "Squats":"squat","Squat Jumps":"squat","Lunges":"squat","Wall Sit":"squat","High Knees":"highknees",
  "Jumping Jacks":"jumpingjack","Burpees":"squat","Tree Pose":"yoga","Warrior Hold":"yoga",
  "Child Pose":"yoga","Deep Breathing":"yoga","Warmup":"warmup",
  "Seated Leg Extensions":"legraise","Wall Push-Ups":"wallpushup","Glute Bridge Hold":"plank"
};

const TRAINER_MODEL = {
  /* Ch06 (2.37 MB, 27,999 tris) replaced MocapGuy (5.43 MB, 17,916 tris) as the
     default trainer on 2026-07-30: it wears a real tracksuit and trainers, has
     actual hair instead of a mocap cap, and is a smaller download. Its rig uses
     the mixamorig9 prefix, which CCARig.normBone() normalises, so the same
     procedural exercises drive it — verified: 10 distinct poses through a squat
     cycle, 6 through jumping jacks. MocapGuy stays as the fallback. */
  /* The model follows the user's saved loadout (assets/js/cca-wardrobe.js), so
     the trainer they picked on the start screen is the one that trains them. */
  get url() {
    var base = (window.TF && TF.baseUrl) ? TF.baseUrl : '';
    var f = (window.CCAWardrobe && CCAWardrobe.modelUrlFor)
      ? CCAWardrobe.modelUrlFor() : 'assets/models/trainer-street.glb';
    return base + '/' + f;
  },
  fallbackUrl: ((window.TF && TF.baseUrl) ? TF.baseUrl : '') + '/assets/models/trainers.glb',
  clips: {
    warmup:      ['Warm Up','Warmup','Jumping','Idle','Breathing Idle'],
    idle:        ['Warm Up','Warmup','Breathing Idle','Idle','Stretching'],
    pushup:      ['Push Up','Pushup','Push-Up'],
    squat:       ['Air Squat','Squat','Squatting'],
    curl:        ['Bicep Curl','Weight Curl','Curl'],
    press:       ['Shoulder Press','Overhead Press','Press'],
    yoga:        ['Yoga','Warrior','Tree Pose','Stretch','Standing'],
    plank:       ['Plank'],
    crunch:      ['Ab Crunch','Crunch','Sit Up','Situp'],
    twist:       ['Russian Twist','Twist'],
    legraise:    ['Leg Raise','Leg Raises','Reverse Crunch'],
    mountain:    ['Mountain Climber','Climber'],
    highknees:   ['High Knees','Running','Run'],
    jumpingjack: ['Jumping Jacks','Jumping Jack','Star Jump','Jump']
  }
};

/* Empty on purpose — see assets/models/anims/README.md.
   This list used to name 14 clip files. Only 3 ever existed (idle, warmup,
   squat) and all three were byte-identical COPIES of trainers.glb
   (md5 62383bea…), so they carried the model's single baked clip rather than a
   per-exercise animation. The net effect on every player load was ~19.7 MB of
   redundant download plus 11 requests 404'ing.
   Motion comes from CCARig, which drives the Mixamo skeleton procedurally and
   covers all 14 exercise modes. Re-populate this list only when real, distinct
   per-exercise clips are added — one file per mode, each with its own clip. */
const TRAINER_ANIMS = [];
const ANIMS_BASE = ((window.TF && TF.baseUrl) ? TF.baseUrl : '') + '/assets/models/anims/';

/* Each exercise gets its OWN complete coaching cue (form tip) so the voice sounds
   distinct per move — not a generic line with the name swapped in. */
const WORKOUT_STATES = {
  idle:        { label: 'Warm Up',           cue: 'Warm up. Loosen those joints and get the blood flowing.' },
  warmup:      { label: 'Warm Up',           cue: 'Warm up. Loosen those joints and get the blood flowing.' },
  pushup:      { label: 'Push Ups',          cue: 'Push ups. Lower your chest to the floor, elbows tucked, keep that core braced.' },
  wallpushup:  { label: 'Wall Push Ups',     cue: 'Wall push ups. Keep a straight torso, lower with control, then press the wall away.' },
  squat:       { label: 'Squats',            cue: 'Squats. Sit back, chest up, knees out, and drive up through your heels.' },
  yoga:        { label: 'Yoga Flow',         cue: 'Yoga flow. Breathe deep, move slow, and hold each pose with control.' },
  jumpingjack: { label: 'Jumping Jacks',     cue: 'Jumping jacks. Big arms overhead, land soft, keep the rhythm going.' },
  curl:        { label: 'Bicep Curls',       cue: 'Bicep curls. Slow on the way up, squeeze hard at the top, no swinging.' },
  press:       { label: 'Shoulder Press',    cue: 'Shoulder press. Press straight overhead, lock it out, and control it down.' },
  plank:       { label: 'Plank',             cue: 'Plank. Straight line head to heels, squeeze your abs and glutes, hold strong.' },
  crunch:      { label: 'Crunches',          cue: 'Crunches. Curl up slow, squeeze your abs at the top, control the way down.' },
  twist:       { label: 'Russian Twists',    cue: 'Russian twists. Rotate from the waist, tap each side, keep your core tight.' },
  legraise:    { label: 'Leg Raises',        cue: 'Leg raises. Straight legs up, lower back pressed down, control every rep.' },
  mountain:    { label: 'Mountain Climbers', cue: 'Mountain climbers. Drive those knees to your chest, fast feet, hips down.' },
  highknees:   { label: 'High Knees',        cue: 'High knees. Pump your arms, knees up to waist height, stay light and fast.' },
  
  // Specific exercises (case-insensitive keys)
  "jumping jacks": { label: "Jumping Jacks", cue: "Sweep your arms overhead and land soft on your toes." },
  "squats": { label: "Squats", cue: "Drive your hips back, keep your chest up, and push through your heels." },
  "squat jumps": { label: "Squat Jumps", cue: "Explode up into the air and land softly on your feet." },
  "high knees": { label: "High Knees", cue: "Pump your arms and drive your knees up high to waist level." },
  "deep breathing": { label: "Deep Breathing", cue: "Inhale slowly through your nose, expand your chest, and exhale completely." },
  "seated leg extensions": { label: "Seated Leg Extensions", cue: "Straighten your leg fully, squeeze your quad, and lower with control." },
  "wall push-ups": { label: "Wall Push-Ups", cue: "Push away from the wall, keep a straight torso, and squeeze your chest." },
  "glute bridge hold": { label: "Glute Bridge Hold", cue: "Squeeze your glutes, push your hips to the ceiling, and hold strong." },
  "tree pose": { label: "Tree Pose", cue: "Find your balance, place your foot on your inner thigh, and focus your mind." },
  "warrior hold": { label: "Warrior Hold", cue: "Lunge deep, extend your arms straight, and hold your ground." },
  "child pose": { label: "Child Pose", cue: "Sit back on your heels, reach your arms forward, and breathe deep." },
  "warmup march": { label: "Warm Up March", cue: "Pump your knees and swing your arms to raise your heart rate." },
  "cool down": { label: "Cool Down Stretch", cue: "Relax, stretch your muscles, and recover." },
  "full body rolling": { label: "Full Body Rolling", cue: "Roll out your back, legs, and shoulders to release tight muscles." },
  "back rolling": { label: "Back Rolling", cue: "Foam roll your upper back gently, releasing tension." },
  "legs rolling": { label: "Legs Rolling", cue: "Move slowly over the foam roller on your quads and hamstrings." },
  "neck release": { label: "Neck Release", cue: "Let your head tilt gently, stretching the tight side of your neck." }
};

const MUSCLE_ACTIVATION = {
  crunch:      { muscle: 'Rectus Abdominis', namePattern: /rectus|abs|abdominis|core|belly|waist/i, pct: 90, msg: "Rectus abdominis — 90% activation", zone: 'core' },
  twist:       { muscle: 'Obliques',         namePattern: /oblique|core|belly|waist/i,              pct: 85, msg: "Obliques — 85% activation", zone: 'obliques' },
  plank:       { muscle: 'Core Stabilizers', namePattern: /rectus|abs|abdominis|oblique|core/i,     pct: 95, msg: "Core stabilizers — 95% activation", zone: 'core' },
  pushup:      { muscle: 'Pectorals & Triceps', namePattern: /pectoral|chest|arm|tricep|shoulder/i, pct: 75, msg: "Pectorals & Triceps — 75%", zone: 'chest' },
  wallpushup:  { muscle: 'Chest & Triceps',     namePattern: /pectoral|chest|arm|tricep|shoulder/i, pct: 62, msg: "Chest & Triceps — 62%", zone: 'chest' },
  squat:       { muscle: 'Quadriceps & Glutes', namePattern: /quad|thigh|glute|leg|calf|hamstring/i, pct: 80, msg: "Quadriceps & Glutes — 80%", zone: 'legs' },
  curl:        { muscle: 'Biceps Brachii',   namePattern: /bicep|arm|hand/i,                        pct: 85, msg: "Biceps brachii — 85%", zone: 'arms' },
  press:       { muscle: 'Deltoids',         namePattern: /deltoid|shoulder|arm/i,                   pct: 80, msg: "Deltoids — 80% activation", zone: 'shoulders' },
  legraise:    { muscle: 'Lower Abdominals', namePattern: /rectus|abs|core|belly|waist/i,           pct: 85, msg: "Lower abdominals — 85%", zone: 'core' },
  mountain:    { muscle: 'Full Core & Shoulders', namePattern: /rectus|abs|core|deltoid|shoulder/i, pct: 75, msg: "Full core — 75% activation", zone: 'core' },
  highknees:   { muscle: 'Hip Flexors & Quads', namePattern: /quad|thigh|leg/i,                     pct: 70, msg: "Hip flexors — 70%", zone: 'legs' },
  jumpingjack: { muscle: 'Calves & Deltoids', namePattern: /calf|leg|shoulder|deltoid/i,            pct: 60, msg: "Full body cardio — 60%", zone: 'full' },
  yoga:        { muscle: 'Stabilizer Core',  namePattern: /core|back|leg|spine/i,                   pct: 50, msg: "Balance & stabilization — 50%", zone: 'core' }
};

// SVG muscle zone highlight paths
const MUSCLE_ZONES = {
  obliques: { highlights: ['muscleHighlight','muscleHighlight2'], core: 'coreHighlight', coreOp: 0.1 },
  core:     { highlights: ['muscleHighlight','muscleHighlight2'], core: 'coreHighlight', coreOp: 0.45 },
  chest:    { highlights: [], core: 'coreHighlight', coreOp: 0.05 },
  legs:     { highlights: [], core: 'coreHighlight', coreOp: 0.05 },
  arms:     { highlights: [], core: 'coreHighlight', coreOp: 0.05 },
  shoulders:{ highlights: [], core: 'coreHighlight', coreOp: 0.05 },
  full:     { highlights: ['muscleHighlight','muscleHighlight2'], core: 'coreHighlight', coreOp: 0.3 }
};

let renderer, scene, camera, clock, curContainer = null, threeOK = false,
    TITAN_CONTAINER = 'workout-3d-arena', mode = 'idle';
let gltfRoot = null, mixer = null, gltfActions = null, curAction = null, useGLTF = false;
/* Phase 12: the rig's own monotonic clock. Advances at dt × TF_PREVIEW.scale so
   the preview can run fast without ever making the pose jump. */
let rigTime = 0;
let loadedMeshes = [];
let activeTargetPattern = null;
let activeMuscleConfig = null;
const stage = {};
// Smoothed camera framing (reframes lower for floor exercises so nothing is cut off)
const curLookAt = new THREE.Vector3(0, 1.35, 0);
const tmpLookAt = new THREE.Vector3();

// ── Premium Voice Engine Class ──
class VoiceManager {
  constructor() {
    this.voice = null;
    this.pitch = 0.9;
    this.rate = 1.1;
    this.muted = false;
    
    try {
      this.muted = localStorage.getItem('tf-voice') === 'off';
    } catch (e) {}

    if (typeof window !== 'undefined' && 'speechSynthesis' in window) {
      this.initVoices();
      if (window.speechSynthesis.onvoiceschanged !== undefined) {
        window.speechSynthesis.onvoiceschanged = () => this.initVoices();
      }
    }
  }

  /* Voice CHOICE is delegated to assets/js/cca-voice.js — the same picker the
     hero engine (assets/js/titan3d.js) uses. These two files each used to carry
     their own priority list and they had drifted apart. The name list below is
     kept only as a fallback for when that script has not loaded. */
  initVoices() {
    if (!('speechSynthesis' in window)) return;
    const voices = window.speechSynthesis.getVoices();
    if (!voices.length) return;

    if (typeof window.CCAVoicePick === 'function') {
      this.voice = window.CCAVoicePick(voices);
      if (this.voice) console.info('[VoiceManager] coach voice:', this.voice.name);
      this.mountPicker();
      return;
    }

    const targets = ['Guy Online (Natural)', 'Andrew Online (Natural)', 'Christopher Online (Natural)',
                     'Google UK English Male', 'Microsoft Mark', 'Microsoft David', 'Google US English'];
    for (const name of targets) {
      const found = voices.find(v => v.name === name) || voices.find(v => v.name.includes(name));
      if (found) { this.voice = found; break; }
    }
    if (!this.voice) this.voice = voices.find(v => v.lang.startsWith('en')) || voices[0];
  }

  /* Renders the shared voice picker once, into the Voice Sync bar. */
  mountPicker() {
    if (this._picked) return;
    const row = document.getElementById('ccaVoicePickRow');
    if (!row || typeof window.CCAVoicePicker !== 'function') return;
    this._picked = true;
    row.innerHTML = '';
    window.CCAVoicePicker(row, () => {
      this._picked = true;
      this.initVoices();
      this.say('Coach voice set.');
    });
  }

  setMuted(val) {
    this.muted = val;
    try {
      localStorage.setItem('tf-voice', val ? 'off' : 'on');
    } catch (e) {}
  }

  /* interrupt=true (default): phase changes cut the old line — the coach must never
     lag behind the workout. interrupt=false: minor cues (halfway, countdown ticks)
     wait their turn or are skipped, so a coaching line is never chopped mid-word. */
  say(text, interrupt = true) {
    if (!('speechSynthesis' in window) || this.muted || !text) return;
    try {
      if (interrupt) {
        window.speechSynthesis.cancel();
      } else if (window.speechSynthesis.speaking || window.speechSynthesis.pending) {
        return;   // busy — skip the minor cue instead of cutting the current line
      }
      const u = new SpeechSynthesisUtterance(text);
      if (this.voice) {
        u.voice = this.voice;
      }
      u.pitch = this.pitch;
      u.rate = this.rate;
      u.volume = 1.0;
      window.speechSynthesis.speak(u);
    } catch (e) {
      console.warn('[VoiceManager] Speech failed:', e);
    }
  }

  stop() {
    if ('speechSynthesis' in window) {
      window.speechSynthesis.cancel();
    }
  }
}

const voiceManager = new VoiceManager();

const CCAVoice = {
  ok: typeof window !== 'undefined' && 'speechSynthesis' in window,
  get muted() { return voiceManager.muted; },
  set muted(val) { voiceManager.setMuted(val); },
  say(text, interrupt) {
    voiceManager.say(text, interrupt);
  },
  stop() {
    voiceManager.stop();
  }
};

const CCATrainer = {
  announce(stage, wk, intro) {
    const normWk = (wk || '').trim().toLowerCase();
    const w = WORKOUT_STATES[normWk] || WORKOUT_STATES[(typeof MODE_MAP !== 'undefined' && MODE_MAP[wk]) ? MODE_MAP[wk] : ''] || WORKOUT_STATES.idle;
    const name = wk || w.label;
    const pick = (a) => a[Math.floor(Math.random() * a.length)];

    /* Per-EXERCISE coaching. WORKOUT_STATES is keyed by the 14 animation modes,
       so Squats / Lunges / Burpees / Wall Sit all used to say the same line.
       assets/js/cca-coach.js keys the cues to the real exercise name instead;
       w.cue stays as the fallback. */
    const ec = (window.CCACoach && CCACoach.cues) ? CCACoach.cues(wk, w.mode || normWk) : null;
    const cue      = (ec && ec.go)    || w.cue || (name + '. Keep your form tight and drive through each rep.');
    const setupCue = (ec && ec.setup) || cue;
    const pushCue  = (ec && ec.push)  || null;

    let text = '', interrupt = true;
    if (stage === 'ready') {
      text = (intro ? intro + '. ' : '') + pick(['Get ready! ', 'Here we go! ', 'Next up, ']) + name + '. ' + setupCue;
    } else if (stage === 'go') {
      text = pick(['Let\'s go! ', 'Come on! ', 'Drive it! ', 'Here we go! ']) + name + '. ' + cue;
    } else if (stage === 'countdown') {
      text = 'Five! Four! Three! Two! One!';
      interrupt = false;                       // never chop a coaching line for the tick
    } else if (stage === 'halfway') {
      /* Mid-set encouragement is now movement-specific too, so "halfway" on a
         plank talks about the hips and on curls about the tempo. */
      text = pushCue || pick(['Halfway there — stay strong!', 'Half done! Keep that form tight!', 'You\'re over the hump — push through!']);
      interrupt = false;
    } else if (stage === 'readycount') {
      text = 'Three! Two! One!';               // the 'go' announcement lands on the switch itself
      interrupt = true;
    } else if (stage === 'rest') {
      text = 'Rest up! Catch your breath. Up next, ' + name + '. ' + setupCue;
    } else if (stage === 'done') {
      text = 'Boom! Session complete! Outstanding work, CCA! You forged your strength today!';
    }

    CCAVoice.say(text, interrupt);

    // Update voice sync bar
    const vsEl = document.getElementById('voiceSyncText');
    if (vsEl) {
      if (stage === 'go') vsEl.textContent = 'Voice: Coach — Go! Explode into ' + name;
      else if (stage === 'rest') vsEl.textContent = 'Voice: Rest phase — Up next: ' + name;
      else if (stage === 'done') vsEl.textContent = 'Voice: Workout complete! Outstanding work.';
      else if (stage === 'ready') vsEl.textContent = 'Voice: Coach — Get ready for ' + name;
      else if (stage === 'countdown') vsEl.textContent = 'Voice: Coach — 5, 4, 3, 2, 1!';
      else if (stage === 'halfway') vsEl.textContent = 'Voice: Coach — Halfway there, keep pushing!';
      else if (stage === 'readycount') vsEl.textContent = 'Voice: Coach — 3, 2, 1, Go!';
    }
  }
};
window.CCAVoice = CCAVoice;
window.CCATrainer = CCATrainer;

/* ⚠️ THE EXERCISE VOICE DEPENDS ON THESE ALIASES.
   assets/js/player.js drives the whole workout and calls window.TitanTrainer
   .announce(...) at every stage (ready / go / rest / countdown / halfway /
   done). This file was renamed to CCATrainer during the rebrand but player.js
   was not, and because every call site is written defensively as
   `if (window.TitanTrainer) …`, the mismatch failed SILENTLY — no console
   error, the coach simply went mute mid-workout.
   Same class of bug as CCARig/TitanRig in titan-rig.js. Aliasing both names
   restores the voice without renaming 8 call sites. */
window.TitanTrainer = CCATrainer;
window.TitanVoice   = CCAVoice;

// ── Coach voice mute toggle (persisted via VoiceManager → localStorage) ──
function toggleCoachVoice(btn) {
  const muted = !CCAVoice.muted;
  CCAVoice.muted = muted;
  if (muted) CCAVoice.stop();
  btn.classList.toggle('opacity-40', muted);
  document.querySelectorAll('.wave-bar').forEach(b => b.style.animationPlayState = muted ? 'paused' : 'running');
  const vsEl = document.getElementById('voiceSyncText');
  if (vsEl) vsEl.textContent = muted ? 'Voice: Muted — tap the mic to enable' : 'Voice: Synchronized — coach is live';
}
window.toggleCoachVoice = toggleCoachVoice;
// restore persisted mute state on load
document.addEventListener('DOMContentLoaded', () => {
  if (CCAVoice.muted) {
    const btn = document.getElementById('voiceMuteBtn');
    if (btn) { btn.classList.add('opacity-40'); }
    document.querySelectorAll('.wave-bar').forEach(b => b.style.animationPlayState = 'paused');
    const vsEl = document.getElementById('voiceSyncText');
    if (vsEl) vsEl.textContent = 'Voice: Muted — tap the mic to enable';
  }
});

// ── Stage Build (Industrial Warehouse Studio) ──
function buildStage() {
  // 1. Large solid dark stone floor (replaces concrete shadow plane + small floating mat)
  const matGeom = new THREE.BoxGeometry(30, 0.1, 30);
  const matMat = new THREE.MeshStandardMaterial({
    color: 0x141518,      // Clean dark slate stone color
    roughness: 0.65,      // Matte stone finish
    metalness: 0.15       // Low stone metallic factor
  });
  const gymMat = new THREE.Mesh(matGeom, matMat);
  gymMat.position.set(0, -0.05, 0); // Top surface is exactly at y = 0.0
  gymMat.receiveShadow = true;
  gymMat.castShadow = true;
  scene.add(gymMat);

  // 2. Subtle stone tiles grid helper overlay
  const tileGrid = new THREE.GridHelper(30, 30, 0x222327, 0x1c1d20);
  tileGrid.position.y = 0.001; // Rest directly on top surface
  scene.add(tileGrid);

  // 3. Subtle floor ring (not cyan, matching orange brand)
  const ring = new THREE.Mesh(
    new THREE.RingGeometry(1.05, 1.35, 48),
    new THREE.MeshBasicMaterial({ color: 0xFF6B1A, transparent: true, opacity: 0.08, side: THREE.DoubleSide })
  );
  ring.rotation.x = -Math.PI / 2; ring.position.y = 0.002; scene.add(ring); stage.ring = ring;

  const ring2 = new THREE.Mesh(
    new THREE.RingGeometry(1.5, 1.56, 48),
    new THREE.MeshBasicMaterial({ color: 0xFF3D00, transparent: true, opacity: 0.06, side: THREE.DoubleSide })
  );
  ring2.rotation.x = -Math.PI / 2; ring2.position.y = 0.002; scene.add(ring2);

  // 4. Ambient dust particles
  const pg = new THREE.BufferGeometry(), Np = 100, pos = new Float32Array(Np * 3);
  for (let i = 0; i < Np; i++) {
    pos[i*3] = (Math.random() - 0.5) * 10;
    pos[i*3+1] = Math.random() * 5;
    pos[i*3+2] = (Math.random() - 0.5) * 10;
  }
  pg.setAttribute('position', new THREE.BufferAttribute(pos, 3));
  stage.parts = new THREE.Points(pg, new THREE.PointsMaterial({
    color: 0xFFFFFF,
    size: 0.025,
    transparent: true,
    opacity: 0.25,
    sizeAttenuation: true
  }));
  scene.add(stage.parts);
  buildGymEquipment();
}

function buildGymEquipment() {
  const rackGroup = new THREE.Group();
  
  const metalMat = new THREE.MeshStandardMaterial({
    color: 0x1f1f21,
    roughness: 0.45,
    metalness: 0.8
  });
  
  const plateMat = new THREE.MeshStandardMaterial({
    color: 0x151516,
    roughness: 0.6,
    metalness: 0.3
  });
  
  const barMat = new THREE.MeshStandardMaterial({
    color: 0x888888,
    roughness: 0.2,
    metalness: 0.95
  });

  // Vertical upright posts
  const postGeom = new THREE.BoxGeometry(0.1, 3.0, 0.1);
  const leftPost = new THREE.Mesh(postGeom, metalMat);
  leftPost.position.set(-2.0, 1.5, -6.5);
  leftPost.castShadow = true;
  leftPost.receiveShadow = true;
  rackGroup.add(leftPost);

  const rightPost = new THREE.Mesh(postGeom, metalMat);
  rightPost.position.set(2.0, 1.5, -6.5);
  rightPost.castShadow = true;
  rightPost.receiveShadow = true;
  rackGroup.add(rightPost);

  // Top crossbar
  const crossGeom = new THREE.BoxGeometry(4.1, 0.1, 0.1);
  const crossbar = new THREE.Mesh(crossGeom, metalMat);
  crossbar.position.set(0, 3.0, -6.5);
  crossbar.castShadow = true;
  rackGroup.add(crossbar);

  // Barbell bar
  const barGeom = new THREE.CylinderGeometry(0.02, 0.02, 5.0, 12);
  const barbell = new THREE.Mesh(barGeom, barMat);
  barbell.rotation.z = Math.PI / 2;
  barbell.position.set(0, 1.8, -6.4);
  barbell.castShadow = true;
  rackGroup.add(barbell);

  // Barbell plates
  const plateGeom = new THREE.CylinderGeometry(0.26, 0.26, 0.08, 20);
  const plate1 = new THREE.Mesh(plateGeom, plateMat);
  plate1.rotation.z = Math.PI / 2;
  plate1.position.set(-2.1, 1.8, -6.4);
  plate1.castShadow = true;
  rackGroup.add(plate1);

  const plate2 = new THREE.Mesh(plateGeom, plateMat);
  plate2.rotation.z = Math.PI / 2;
  plate2.position.set(-2.2, 1.8, -6.4);
  plate2.castShadow = true;
  rackGroup.add(plate2);

  const plate3 = new THREE.Mesh(plateGeom, plateMat);
  plate3.rotation.z = Math.PI / 2;
  plate3.position.set(2.1, 1.8, -6.4);
  plate3.castShadow = true;
  rackGroup.add(plate3);

  const plate4 = new THREE.Mesh(plateGeom, plateMat);
  plate4.rotation.z = Math.PI / 2;
  plate4.position.set(2.2, 1.8, -6.4);
  plate4.castShadow = true;
  rackGroup.add(plate4);

  // Dumbbell rack on the right side
  const dbRackGroup = new THREE.Group();
  dbRackGroup.position.set(4.5, 0, -5.5);
  dbRackGroup.rotation.y = -Math.PI / 6;

  const dbPostGeom = new THREE.BoxGeometry(0.06, 0.9, 0.06);
  const dbLeft = new THREE.Mesh(dbPostGeom, metalMat);
  dbLeft.position.set(-0.8, 0.45, 0);
  dbLeft.castShadow = true;
  dbRackGroup.add(dbLeft);

  const dbRight = new THREE.Mesh(dbPostGeom, metalMat);
  dbRight.position.set(0.8, 0.45, 0);
  dbRight.castShadow = true;
  dbRackGroup.add(dbRight);

  const shelfGeom = new THREE.BoxGeometry(1.7, 0.04, 0.25);
  const shelf = new THREE.Mesh(shelfGeom, metalMat);
  shelf.position.set(0, 0.5, 0);
  shelf.castShadow = true;
  shelf.receiveShadow = true;
  dbRackGroup.add(shelf);

  // Dumbbells
  const dbHandleGeom = new THREE.CylinderGeometry(0.012, 0.012, 0.2, 8);
  const dbWeightGeom = new THREE.CylinderGeometry(0.06, 0.06, 0.06, 12);
  
  for (let xOffset = -0.6; xOffset <= 0.6; xOffset += 0.4) {
    const dumbbell = new THREE.Group();
    dumbbell.position.set(xOffset, 0.58, 0);
    dumbbell.rotation.x = Math.PI / 2;

    const handle = new THREE.Mesh(dbHandleGeom, barMat);
    dumbbell.add(handle);

    const w1 = new THREE.Mesh(dbWeightGeom, plateMat);
    w1.position.y = 0.08;
    w1.castShadow = true;
    dumbbell.add(w1);

    const w2 = new THREE.Mesh(dbWeightGeom, plateMat);
    w2.position.y = -0.08;
    w2.castShadow = true;
    dumbbell.add(w2);

    dbRackGroup.add(dumbbell);
  }

  scene.add(rackGroup);
  scene.add(dbRackGroup);
}

function normName(s) { return String(s).toLowerCase().replace(/[^a-z0-9]/g, ''); }
function findClip(clips, cands) {
  for (const cand of cands) {
    const n = normName(cand);
    const hit = clips.find(cl => normName(cl.name).includes(n));
    if (hit) return hit;
  }
  return null;
}

function fitModel(obj) {
  // Cap stays ON (the GLB has no hair mesh — see CCARig.stripHat); hideProps is a
  // no-op unless that flag is set. styleModel drops the mocap dots + calms eye glow.
  if (window.CCARig && CCARig.hideProps) CCARig.hideProps(obj);
  if (window.CCARig && CCARig.styleModel) CCARig.styleModel(obj);
  const isProp = (n) => (window.CCARig && CCARig.isProp) ? CCARig.isProp(n) : false;
  // Robust fit — setFromObject mis-measures skinned Mixamo GLBs (see CCARig.measure)
  let size, ctr, min;
  if (window.CCARig && CCARig.measure) {
    const m = CCARig.measure(obj); size = m.size; ctr = m.center; min = m.min;
  } else {
    const box = new THREE.Box3().setFromObject(obj);
    size = new THREE.Vector3(); ctr = new THREE.Vector3();
    box.getSize(size); box.getCenter(ctr); min = box.min;
  }
  const s = 2.7 / (size.y || 1); obj.scale.setScalar(s);
  obj.position.x = -ctr.x * s; obj.position.z = -ctr.z * s; obj.position.y = -min.y * s;

  obj.traverse(o => {
    if (!o.isMesh || !o.material) return;

    const meshName = String(o.name).toLowerCase();
    const matName = String(o.material.name || '').toLowerCase();

    // Brute-force strip mocap dots / markers (keep mocapguy skin visible, preserve hat)
    if ((/reflector|marker/i.test(meshName) || /reflector|marker/i.test(matName)) && !/hat/i.test(meshName)) {
      o.visible = false;
      o.scale.set(0, 0, 0);
      return;
    }

    o.frustumCulled = false;              // never cull — a limb popping out mid-rep looks broken
    o.castShadow = true;
    o.receiveShadow = true;

    if (Array.isArray(o.material)) {
      o.material = o.material.map(m => m.clone());
    } else {
      o.material = o.material.clone();
    }
    const mats = Array.isArray(o.material) ? o.material : [o.material];

    mats.forEach(mat => {
      // Force 100% opaque render setup for all human meshes (stripping translucent shader code)
      mat.transparent = false;
      mat.opacity = 1.0;
      mat.alphaTest = 0;
      mat.depthWrite = true;
      if ('blending' in mat) mat.blending = THREE.NormalBlending;
      if (mat.alphaMap) mat.alphaMap = null;

      // Clean, professional PBR: sRGB color maps
      if (mat.map && 'encoding' in mat.map) { mat.map.encoding = THREE.sRGBEncoding; mat.map.needsUpdate = true; }
      if (renderer && mat.map) mat.map.anisotropy = renderer.capabilities.getMaxAnisotropy();
      if (renderer && mat.normalMap) mat.normalMap.anisotropy = renderer.capabilities.getMaxAnisotropy();

      // 1. Skin & Face Setup (Matte clean face/skin)
      if (/body|skin|face|head|eye|mouth|teeth/i.test(meshName) || /body|skin|face|head/i.test(matName)) {
        mat.roughness = 0.75;
        mat.metalness = 0.0;
        if (mat.emissive) {
          mat.emissive.setRGB(0, 0, 0);
          mat.emissiveIntensity = 0;
        }
      } 
      // 2. Hair/Cap Setup
      else if (/hat|cap|hair|headwear/i.test(meshName) || /hat|cap|hair|headwear/i.test(matName)) {
        o.visible = true; // Force cap/hair visibility to true
        mat.roughness = 0.85;
        mat.metalness = 0.0;
        if (mat.color && !mat.map) {
          mat.color.setHex(0x1a1a1a); // Charcoal cap if untextured
        }
      } 
      // 3. Athletic Apparel Setup
      else {
        mat.roughness = 0.8;
        mat.metalness = 0.0;
      }

      mat.needsUpdate = true;
    });

    // Muscle-activation glow tints the BODY only — never the cap/hat props.
    if (!isProp(o.name)) {
      const originals = mats.map(mat => ({
        material: mat,
        originalEmissive: mat.emissive ? mat.emissive.clone() : new THREE.Color(0,0,0),
        originalEmissiveIntensity: mat.emissiveIntensity || 0
      }));
      loadedMeshes.push({
        mesh: o,
        materials: originals
      });
    }
  });
}

function makeGLTFLoader() {
  const loader = new THREE.GLTFLoader();
  if (typeof THREE.DRACOLoader === 'function') {
    const draco = new THREE.DRACOLoader();
    draco.setDecoderPath('https://www.gstatic.com/draco/versioned/decoders/1.5.7/');
    draco.setDecoderConfig({ type: 'js' });
    loader.setDRACOLoader(draco);
  }
  return loader;
}

function buildProceduralHumanoid() {
  const group = new THREE.Group();
  
  // Materials with neon glowing grids and glassmorphism styling
  const bodyMaterial = new THREE.MeshStandardMaterial({
    color: 0x38BDF8, // Cyan glowing color
    emissive: 0x38BDF8,
    emissiveIntensity: 0.5,
    roughness: 0.1,
    metalness: 0.9,
    transparent: true,
    opacity: 0.85
  });
  
  const jointMaterial = new THREE.MeshStandardMaterial({
    color: 0xF59E0B, // Gold accent for joints
    emissive: 0xF59E0B,
    emissiveIntensity: 0.8,
    roughness: 0.1,
    metalness: 0.9
  });

  // Torso
  const torsoGeom = new THREE.CylinderGeometry(0.35, 0.2, 1.4, 16);
  const torso = new THREE.Mesh(torsoGeom, bodyMaterial);
  torso.position.y = 1.3;
  group.add(torso);

  // Head
  const headGeom = new THREE.SphereGeometry(0.2, 16, 16);
  const head = new THREE.Mesh(headGeom, bodyMaterial);
  head.position.y = 2.15;
  group.add(head);

  // Neck
  const neckGeom = new THREE.CylinderGeometry(0.08, 0.08, 0.15, 16);
  const neck = new THREE.Mesh(neckGeom, bodyMaterial);
  neck.position.y = 1.95;
  group.add(neck);

  // Shoulders
  const leftShoulder = new THREE.Mesh(new THREE.SphereGeometry(0.1, 12, 12), jointMaterial);
  leftShoulder.position.set(-0.45, 1.8, 0);
  group.add(leftShoulder);
  
  const rightShoulder = new THREE.Mesh(new THREE.SphereGeometry(0.1, 12, 12), jointMaterial);
  rightShoulder.position.set(0.45, 1.8, 0);
  group.add(rightShoulder);

  // Left arm
  const leftArmGeom = new THREE.CylinderGeometry(0.07, 0.06, 0.6, 12);
  const leftArm = new THREE.Mesh(leftArmGeom, bodyMaterial);
  leftArm.position.set(-0.55, 1.4, 0);
  group.add(leftArm);
  
  // Left forearm
  const leftForearm = new THREE.Mesh(new THREE.CylinderGeometry(0.06, 0.05, 0.5, 12), bodyMaterial);
  leftForearm.position.set(-0.55, 0.85, 0.05);
  leftForearm.rotation.x = 0.2;
  group.add(leftForearm);

  // Right arm
  const rightArmGeom = new THREE.CylinderGeometry(0.07, 0.06, 0.6, 12);
  const rightArm = new THREE.Mesh(rightArmGeom, bodyMaterial);
  rightArm.position.set(0.55, 1.4, 0);
  group.add(rightArm);
  
  // Right forearm
  const rightForearm = new THREE.Mesh(new THREE.CylinderGeometry(0.06, 0.05, 0.5, 12), bodyMaterial);
  rightForearm.position.set(0.55, 0.85, 0.05);
  rightForearm.rotation.x = 0.2;
  group.add(rightForearm);

  // Pelvis / Hips
  const pelvisGeom = new THREE.BoxGeometry(0.5, 0.12, 0.3);
  const pelvis = new THREE.Mesh(pelvisGeom, jointMaterial);
  pelvis.position.y = 0.65;
  group.add(pelvis);

  // Left Thigh
  const leftThighGeom = new THREE.CylinderGeometry(0.1, 0.08, 0.65, 12);
  const leftThigh = new THREE.Mesh(leftThighGeom, bodyMaterial);
  leftThigh.position.set(-0.18, 0.35, 0);
  group.add(leftThigh);
  
  // Left Shin
  const leftShinGeom = new THREE.CylinderGeometry(0.08, 0.06, 0.65, 12);
  const leftShin = new THREE.Mesh(leftShinGeom, bodyMaterial);
  leftShin.position.set(-0.18, -0.3, 0);
  group.add(leftShin);

  // Right Thigh
  const rightThighGeom = new THREE.CylinderGeometry(0.1, 0.08, 0.65, 12);
  const rightThigh = new THREE.Mesh(rightThighGeom, bodyMaterial);
  rightThigh.position.set(0.18, 0.35, 0);
  group.add(rightThigh);
  
  // Right Shin
  const rightShinGeom = new THREE.CylinderGeometry(0.08, 0.06, 0.65, 12);
  const rightShin = new THREE.Mesh(rightShinGeom, bodyMaterial);
  rightShin.position.set(0.18, -0.3, 0);
  group.add(rightShin);

  group.position.set(0, 0.3, 0);
  
  group.userData = {
    parts: {
      leftArm, rightArm, leftForearm, rightForearm,
      leftThigh, rightThigh, leftShin, rightShin, torso, head
    }
  };

  return group;
}

function loadTrainer(container) {
  let done = false;
  const fail = (e) => {
    if (done) return; done = true; clearTimeout(to);
    console.warn('[exactfit3d] trainer.glb failed, loading procedural humanoid fallback:', e);
    
    // Fallback Procedural Humanoid
    try {
      const humanoid = buildProceduralHumanoid();
      window.proceduralHumanoid = humanoid;
      scene.add(humanoid);
    } catch (err) {
      console.error('[exactfit3d] Failed to create procedural humanoid:', err);
    }

    // Hide loader
    const loaderEl = document.getElementById('arena-loader');
    if (loaderEl) loaderEl.style.display = 'none';
  };
  
  if (typeof THREE.GLTFLoader !== 'function') { fail('GLTFLoader script missing'); return; }
  
  // Timeout set to 8000ms (8 seconds)
  const to = setTimeout(() => fail('timeout'), 8000);

  try {
    const loader = makeGLTFLoader();
    /* Try the default trainer, then MocapGuy, before dropping to the procedural
       body — a missing or corrupt primary model must not lose the human. */
    let triedFallback = false;
    const onModelError = (e) => {
      if (done) return;
      if (!triedFallback && TRAINER_MODEL.fallbackUrl) {
        triedFallback = true;
        console.warn('[exactfit3d] primary trainer failed, trying fallback:', e);
        loader.load(TRAINER_MODEL.fallbackUrl, onModelLoad, undefined, fail);
        return;
      }
      fail(e);
    };
    const onModelLoad = (gltf) => {
      if (done) return; done = true; clearTimeout(to);
      gltfRoot = gltf.scene;
      fitModel(gltfRoot);
      scene.add(gltfRoot);
      useGLTF = true;

      const loaderEl = document.getElementById('arena-loader');
      if (loaderEl) loaderEl.style.display = 'none';

      /* Auto training kit. The visitor never picks this — it follows the
         workout they opened, so a HIIT session and a yoga session put the
         trainer in different gear without any UI. Mapping lives in
         assets/js/cca-coach.js; "" means the kit already baked into
         trainers.glb, which costs no extra download. */
      /* The user's loadout wins: show only the garment pieces they chose and
         tint them. Runs before the kit pass so a chosen piece is never
         overwritten by the automatic category texture. */
      if (window.CCAWardrobe && CCAWardrobe.apply) {
        const r = CCAWardrobe.apply(gltfRoot);
        console.info('[exactfit3d] loadout applied — on:', r.shown, 'off:', r.hidden);
      }
      applyWorkoutKit();

      // Primary path: drive the Mixamo skeleton procedurally so each workout
      // gets its OWN human motion (the GLB ships with only one clip).
      if (window.CCARig && CCARig.attach(gltfRoot)) {
        mixer = null;                                   // rig owns the pose
        console.info('[exactfit3d] Rigged human active — procedural exercises, meshes:', loadedMeshes.length);
      } else {
        // Rig unrecognised → fall back to embedded animation clips.
        mixer = new THREE.AnimationMixer(gltfRoot);
        const clips = gltf.animations || [];
        gltfActions = {};
        if (clips.length) {
          for (const m in TRAINER_MODEL.clips) {
            const cl = findClip(clips, TRAINER_MODEL.clips[m]);
            if (cl) gltfActions[m] = mixer.clipAction(cl);
          }
          if (!gltfActions.idle) gltfActions.idle = mixer.clipAction(clips[0]);
        }
        console.info('[exactfit3d] Model loaded (clip mode), meshes:', loadedMeshes.length);
        bindExtraAnims(loader);
        playClip(mode);
      }
    };
    loader.load(TRAINER_MODEL.url, onModelLoad, undefined, onModelError);
  } catch (err) {
    fail(err);
  }
}

/* ---- Automatic per-workout training kit -----------------------------------
   Swaps the Body_MAT base-colour texture. Only Body_MAT is targeted: matching
   "any material with a map" also catches the floor, which would repaint the
   ground instead of the trainer. */
let kitBaseMap = null, kitCache = {};
function bodyKitMaterials() {
  const out = [];
  if (!gltfRoot) return out;
  const isKit = (n) => (window.CCACoach && CCACoach.isKitMaterial)
    ? CCACoach.isKitMaterial(n) : n === 'Body_MAT';
  gltfRoot.traverse(o => {
    if (!o.isMesh && !o.isSkinnedMesh) return;
    (Array.isArray(o.material) ? o.material : [o.material]).forEach(m => {
      if (m && m.map && out.indexOf(m) === -1 && (isKit(m.name) || m.__isKit)) out.push(m);
    });
  });
  return out;
}
function applyWorkoutKit() {
  if (!window.CCACoach || !CCACoach.kitFor) return;
  /* PRECEDENCE: an explicit choice in the Trainer Studio always wins. The
     automatic per-category kit is only a sensible default for users who never
     opened the studio — otherwise it would silently undo their pick. */
  if (window.CCAWardrobe) {
    const chosen = CCAWardrobe.load();
    if (chosen && chosen.outfit) {
      console.info('[exactfit3d] keeping the user-selected kit:', chosen.outfit);
      return;
    }
  }
  const cat = (typeof W !== 'undefined' && W) ? (W.category || '') : '';
  /* Ch06 and MocapGuy have different UV layouts, so the kit set follows
     whichever model actually loaded — never hard-coded. */
  const which = CCACoach.kitSetFor ? CCACoach.kitSetFor(gltfRoot) : 'ch06';
  const url = CCACoach.kitFor(cat, '', which);
  const mats = bodyKitMaterials();
  if (!mats.length) return;
  if (kitBaseMap === null) kitBaseMap = mats[0].map;

  const setMap = (tex) => mats.forEach(m => {
    if (m.map === kitBaseMap || m.__isKit) { m.map = tex; m.__isKit = true; m.needsUpdate = true; }
  });
  if (!url) { setMap(kitBaseMap); return; }            // graphite is already in the GLB
  if (kitCache[url]) { setMap(kitCache[url]); return; }

  const base = (window.TF && TF.baseUrl) ? TF.baseUrl + '/' : '';
  new THREE.TextureLoader().load(base + url, tex => {
    if (kitBaseMap) {
      tex.flipY = kitBaseMap.flipY;
      tex.wrapS = kitBaseMap.wrapS; tex.wrapT = kitBaseMap.wrapT;
      if ('colorSpace' in kitBaseMap) tex.colorSpace = kitBaseMap.colorSpace;
      else if ('encoding' in kitBaseMap) tex.encoding = kitBaseMap.encoding;
    }
    kitCache[url] = tex; setMap(tex);
    console.info('[exactfit3d] training kit for "' + cat + '" (' + which + '):', url);
  }, undefined, () => console.warn('[exactfit3d] kit failed to load:', url));
}

function bindExtraAnims(loader) {
  TRAINER_ANIMS.forEach(m => {
    loader.load(ANIMS_BASE + m + '.glb', (g) => {
      const clip = (g.animations || [])[0];
      if (!clip || !mixer) return;
      clip.name = m;
      gltfActions[m] = mixer.clipAction(clip);
      if (!gltfActions.idle && (m === 'idle' || m === 'warmup')) gltfActions.idle = gltfActions[m];
      if (m === mode) playClip(m);
      console.info('[exactfit3d] +anim', m);
    }, undefined, () => {/* file absent → skip silently */});
  });
}

function playClip(m) {
  if (!mixer || !gltfActions) return;
  const act = gltfActions[m] || gltfActions.idle;
  if (!act || act === curAction) return;
  act.reset(); act.enabled = true; act.setEffectiveTimeScale(1); act.setEffectiveWeight(1); act.play();
  if (curAction) act.crossFadeFrom(curAction, 0.4, true);
  curAction = act;
}

// ── Three.js Init ──
function initThree(containerId) {
  TITAN_CONTAINER = containerId || 'workout-3d-arena';
  const container = document.getElementById(TITAN_CONTAINER);
  try {
    scene = new THREE.Scene();

    const aspect = (container.clientWidth && container.clientHeight) ? (container.clientWidth / container.clientHeight) : (window.innerWidth / window.innerHeight || 1.77);
    camera = new THREE.PerspectiveCamera(42, aspect, 0.1, 60);
    // Frame head-to-toe with headroom for overhead reaches (press / jacks / yoga)
    camera.position.set(0, 1.9, 5.6);
    camera.lookAt(0, 1.35, 0);

    try {
      renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true, powerPreference: 'high-performance' });
      renderer.setPixelRatio(Math.min(devicePixelRatio, 1.5));   // cap: 2× DPR doubled the fill-rate cost for no visible gain
      renderer.setClearColor(0x0a0a0c, 1);
      renderer.shadowMap.enabled = true;
      renderer.shadowMap.type = THREE.PCFSoftShadowMap;
      if ('outputEncoding' in renderer) renderer.outputEncoding = THREE.sRGBEncoding;
      // Filmic tone mapping → clean skin tones & clothing instead of blown-out highlights
      if ('toneMapping' in renderer) { renderer.toneMapping = THREE.ACESFilmicToneMapping; renderer.toneMappingExposure = 1.08; }
    } catch (e) {
      console.warn('WebGL initialization failed, falling back to basic rendering', e);
      const loaderEl = document.getElementById('arena-loader');
      if (loaderEl) {
        loaderEl.innerHTML = '<div class="text-red-500 text-5xl mb-4">⚠️</div><h3 class="text-sm font-bold tracking-widest text-red-500 uppercase glow-red">WebGL Error</h3><p class="text-[10px] text-gray-500 max-w-xs mt-3 leading-relaxed font-mono text-center">WebGL is not supported or is disabled in your browser.</p>';
      }
      return;
    }

    // Realistic window lighting from the left side
    scene.add(new THREE.AmbientLight(0xffFAF0, 0.45));
    scene.add(new THREE.HemisphereLight(0xfff3e3, 0x222228, 0.35));

    // Key Light: warm sunlight coming from the left windows
    const keyLight = new THREE.DirectionalLight(0xfff1e0, 1.6);
    keyLight.position.set(-10, 6, 2);
    keyLight.castShadow = true;
    keyLight.shadow.mapSize.width = 2048;
    keyLight.shadow.mapSize.height = 2048;
    keyLight.shadow.camera.near = 0.5;
    keyLight.shadow.camera.far = 25;
    keyLight.shadow.camera.left = -6;
    keyLight.shadow.camera.right = 6;
    keyLight.shadow.camera.top = 6;
    keyLight.shadow.camera.bottom = -6;
    keyLight.shadow.bias = -0.0002;
    keyLight.shadow.radius = 3;
    scene.add(keyLight);

    // Warm rim light
    const warmRim = new THREE.DirectionalLight(0xfff5e6, 0.35);
    warmRim.position.set(6, 4, -6);
    scene.add(warmRim);

    // Ground fill light
    const groundFill = new THREE.DirectionalLight(0xffe8d6, 0.12);
    groundFill.position.set(0, -2, 4);
    scene.add(groundFill);

    buildStage();

    // Forcefully load the industrial gym background texture
    const textureLoader = new THREE.TextureLoader();
    textureLoader.load(((window.TF && TF.baseUrl) ? TF.baseUrl : '') + '/assets/images/industrial_gym.jpg', function(texture) {
      texture.encoding = THREE.sRGBEncoding;
      texture.wrapS = THREE.ClampToEdgeWrapping;
      texture.wrapT = THREE.ClampToEdgeWrapping;
      texture.minFilter = THREE.LinearFilter;
      scene.background = texture;
      resize3d();
    });

    clock = new THREE.Clock();
    threeOK = true;

    mountCanvas(container);
    loadTrainer(container);
    animate();
  } catch(e) {
    console.warn('Three.js init failed:', e);
  }
}

function mountCanvas(container) {
  if (!threeOK || !container || curContainer === container) return;
  container.appendChild(renderer.domElement);
  curContainer = container;
  resize3d();
}

function resize3d() {
  if (!threeOK || !curContainer) return;
  const w = curContainer.clientWidth, h = curContainer.clientHeight;
  if (w && h) {
    renderer.setSize(w, h);
    camera.aspect = w / h;
    camera.updateProjectionMatrix();

    // Adjust background texture mapping and aspect ratios to wrap naturally (cover fit)
    const texture = scene.background;
    if (texture && texture.isTexture && texture.image) {
      const img = texture.image;
      const aspect = w / h;
      const imageAspect = img.width / img.height;
      if (aspect > imageAspect) {
        texture.repeat.set(1, imageAspect / aspect);
        texture.offset.set(0, (1 - imageAspect / aspect) / 2);
      } else {
        texture.repeat.set(aspect / imageAspect, 1);
        texture.offset.set((1 - aspect / imageAspect) / 2, 0);
      }
    }
  }
}
window.addEventListener('resize', resize3d);

// ── Update HUD panels for muscle zone ──
function updateMuscleHUD(config) {
  const nameEl = document.getElementById('muscleName');
  const pctEl = document.getElementById('musclePct');
  const barEl = document.getElementById('muscleBar');
  const textEl = document.getElementById('hud-muscle-activation');
  const vsEl = document.getElementById('voiceSyncText');

  if (config) {
    if (nameEl) nameEl.textContent = config.muscle;
    if (pctEl) pctEl.textContent = config.pct + '%';
    if (barEl) barEl.style.width = config.pct + '%';
    if (textEl) textEl.textContent = config.msg;

    // Update SVG highlights
    const zone = MUSCLE_ZONES[config.zone] || MUSCLE_ZONES.core;
    const h1 = document.getElementById('muscleHighlight');
    const h2 = document.getElementById('muscleHighlight2');
    const core = document.getElementById('coreHighlight');
    if (h1) h1.setAttribute('opacity', zone.highlights.includes('muscleHighlight') ? '0.6' : '0.05');
    if (h2) h2.setAttribute('opacity', zone.highlights.includes('muscleHighlight2') ? '0.6' : '0.05');
    if (core) core.setAttribute('opacity', String(zone.coreOp));
  } else {
    if (nameEl) nameEl.textContent = 'Standby';
    if (pctEl) pctEl.textContent = '—';
    if (barEl) barEl.style.width = '0%';
    if (textEl) textEl.textContent = 'Calibrating sensor...';
  }
}

// ── Mode switching (called by player.js) ──
function setMode(m) {
  mode = m || 'idle';
  if (useGLTF) playClip(mode);

  const config = MUSCLE_ACTIVATION[mode];
  if (config) {
    activeMuscleConfig = config;
    activeTargetPattern = config.namePattern;
    updateMuscleHUD(config);
  } else {
    activeMuscleConfig = null;
    activeTargetPattern = null;
    updateMuscleHUD(null);
  }
}
window.setMode = setMode;

// ── Render Loop ──
function animate() {
  requestAnimationFrame(animate);
  if (!threeOK) return;
  // Perf: don't burn GPU while the intro hero is showing (the arena is display:none, so
  // it has no offsetParent) or the tab is backgrounded. Rendering resumes instantly the
  // moment the user hits "Begin Forging" and #playerUI becomes visible.
  if (!curContainer || curContainer.offsetParent === null || document.hidden) return;

  // clamp dt so the mixer doesn't lurch after a skipped stretch (getDelta accumulates)
  const dt = Math.min(clock.getDelta(), 0.1), t = clock.getElapsedTime();

  // Smoothly reframe the camera per mode so floor work (push-ups, plank, crunches)
  // stays centred instead of sinking off the bottom of the view.
  if (window.CCARig && CCARig.cameraFor) {
    const cf = CCARig.cameraFor(mode);
    camera.position.x += (cf.pos[0] - camera.position.x) * 0.07;
    camera.position.y += (cf.pos[1] - camera.position.y) * 0.07;
    camera.position.z += (cf.pos[2] - camera.position.z) * 0.07;
    tmpLookAt.set(cf.target[0], cf.target[1], cf.target[2]);
    curLookAt.lerp(tmpLookAt, 0.07);
    camera.lookAt(curLookAt);
  }

  // Animate procedural humanoid if active
  if (window.proceduralHumanoid) {
    const humanoid = window.proceduralHumanoid;
    
    // Reset standard postures first
    humanoid.rotation.set(0, 0, 0);
    humanoid.position.set(0, 0.3, 0);
    
    humanoid.userData.parts.leftArm.rotation.set(0, 0, 0);
    humanoid.userData.parts.rightArm.rotation.set(0, 0, 0);
    humanoid.userData.parts.leftForearm.rotation.set(0.2, 0, 0);
    humanoid.userData.parts.rightForearm.rotation.set(0.2, 0, 0);
    
    humanoid.userData.parts.leftThigh.position.set(-0.18, 0.35, 0);
    humanoid.userData.parts.rightThigh.position.set(0.18, 0.35, 0);
    humanoid.userData.parts.leftShin.position.set(-0.18, -0.3, 0);
    humanoid.userData.parts.rightShin.position.set(0.18, -0.3, 0);
    
    humanoid.userData.parts.torso.rotation.set(0, 0, 0);
    humanoid.userData.parts.torso.scale.set(1, 1, 1);
    humanoid.userData.parts.head.position.set(0, 2.15, 0);

    const speed = 3.5;
    if (mode === 'squat') {
      const squatFactor = Math.sin(t * speed) * 0.5 + 0.5; // 0 to 1
      humanoid.position.y = 0.3 - squatFactor * 0.45;
      
      // Bend knees and torso
      humanoid.userData.parts.torso.rotation.x = squatFactor * 0.25;
      humanoid.userData.parts.leftThigh.position.y = 0.35 - squatFactor * 0.15;
      humanoid.userData.parts.rightThigh.position.y = 0.35 - squatFactor * 0.15;
      humanoid.userData.parts.leftShin.position.y = -0.3 + squatFactor * 0.1;
      humanoid.userData.parts.rightShin.position.y = -0.3 + squatFactor * 0.1;
    } else if (mode === 'pushup') {
      // Rotate horizontal for push-up pose
      humanoid.rotation.x = -Math.PI / 2.2;
      const pushFactor = Math.sin(t * speed) * 0.5 + 0.5; // 0 to 1
      humanoid.position.y = 0.5 - pushFactor * 0.35;
      humanoid.position.z = -pushFactor * 0.1;
    } else if (mode === 'jumpingjack') {
      const jackFactor = Math.sin(t * 5.0) * 0.5 + 0.5; // 0 to 1
      // Raise arms overhead
      humanoid.userData.parts.leftArm.rotation.z = -jackFactor * 2.2;
      humanoid.userData.parts.rightArm.rotation.z = jackFactor * 2.2;
      // Spread thighs
      humanoid.userData.parts.leftThigh.position.x = -0.18 - jackFactor * 0.2;
      humanoid.userData.parts.rightThigh.position.x = 0.18 + jackFactor * 0.2;
    } else if (mode === 'curl') {
      const curlFactor = Math.sin(t * 4.0) * 0.5 + 0.5; // 0 to 1
      // Move forearms up and down
      humanoid.userData.parts.leftForearm.rotation.x = 0.2 + curlFactor * 1.8;
      humanoid.userData.parts.rightForearm.rotation.x = 0.2 + curlFactor * 1.8;
    } else if (mode === 'press') {
      const pressFactor = Math.sin(t * 4.0) * 0.5 + 0.5; // 0 to 1
      // Arms up and down
      humanoid.userData.parts.leftArm.rotation.z = -1.5 - pressFactor * 1.2;
      humanoid.userData.parts.rightArm.rotation.z = 1.5 + pressFactor * 1.2;
    } else {
      // Breathing / Idle default posture
      const breath = Math.sin(t * 2.0);
      humanoid.userData.parts.torso.scale.set(1 + breath * 0.03, 1, 1 + breath * 0.03);
      humanoid.userData.parts.leftArm.rotation.z = 0.05 * breath;
      humanoid.userData.parts.rightArm.rotation.z = -0.05 * breath;
    }
  }

  if (useGLTF && window.CCARig && CCARig.attached) {
    /* PHASE 12 — fast preview pass.
       The trainer is driven procedurally by CCARig, not by an AnimationMixer,
       so there is no action to call setEffectiveTimeScale() on. The equivalent
       is to advance the rig's own clock faster: rigTime accumulates dt scaled
       by TF_PREVIEW.scale, which player.js raises to PREVIEW_TIMESCALE during
       the preview stage and returns to 1 before the real rep begins.
       Using a separate accumulator (not the raw elapsed time) means speeding up
       and slowing down never makes the pose jump — the clock only ever moves
       forward, just at a different rate. */
    var pv = window.TF_PREVIEW;
    rigTime += dt * ((pv && pv.active) ? pv.scale : 1);
    CCARig.update(mode, rigTime);                    // rigged human performs the workout
  } else if (useGLTF && mixer) {
    mixer.update(dt);
    if (gltfRoot) gltfRoot.rotation.y = Math.sin(t * 0.25) * 0.08;
  }

  // Particle drift
  if (stage.parts) {
    const p = stage.parts.geometry.attributes.position;
    for (let i = 0; i < p.count; i++) {
      let y = p.getY(i) + 0.005;
      if (y > 5) y = 0;
      p.setY(i, y);
    }
    p.needsUpdate = true;
  }

  if (stage.ring) stage.ring.rotation.z = t * 0.15;

  // ── Muscle activation glow ──
  // Tint ONLY meshes whose name genuinely matches the active muscle group, and keep it
  // subtle. The old build had a `/body|skin|.../` FALLBACK that fired whenever no muscle
  // mesh matched — which is always, since the real model's only skin mesh is literally
  // named "MocapGuy_Body". Result: the entire athlete flashed bright neon orange every
  // rep (the "unwanted orange glow"). Fallback removed; colour softened to deep red.
  if (loadedMeshes.length > 0) {
    const glow = 0.16 + 0.12 * (0.5 + 0.5 * Math.sin(t * 6));   // gentle 0.16–0.28 pulse
    for (let i = 0; i < loadedMeshes.length; i++) {
      const item = loadedMeshes[i], mesh = item.mesh;
      const isTarget = activeTargetPattern && activeTargetPattern.test(mesh.name);
      item.materials.forEach(mRecord => {
        const mat = mRecord.material;
        if (isTarget) {
          if (mat.emissive) {
            mat.emissive.setHex(0xB91C1C);   // deep muscle red, never neon orange
            mat.emissiveIntensity = glow;
          }
        } else {
          if (mat.emissive) {
            mat.emissive.copy(mRecord.originalEmissive);
            mat.emissiveIntensity = mRecord.originalEmissiveIntensity;
          }
        }
      });
    }
  }

  renderer.render(scene, camera);
}

// ── Leak prevention: free every GPU resource when the page goes away ──
function disposeArena() {
  if (!renderer || !scene) return;
  scene.traverse(o => {
    if (o.geometry) o.geometry.dispose();
    if (o.material) {
      (Array.isArray(o.material) ? o.material : [o.material]).forEach(m => {
        for (const k in m) { const v = m[k]; if (v && v.isTexture) v.dispose(); }
        m.dispose();
      });
    }
  });
  loadedMeshes.length = 0;
  renderer.dispose();
  threeOK = false;
}
window.addEventListener('pagehide', disposeArena, { once: true });

/* Read out the saved trainer choice on the intro screen. The picker itself is a
   full page (pages/trainer-studio.php) — a cramped inline panel was the wrong
   home for a 3D preview. */
(function () {
  const el = document.getElementById('loadoutSummary');
  if (!el || !window.CCAWardrobe) return;
  const ld = CCAWardrobe.load();
  const t = CCAWardrobe.trainerById(ld.trainer);
  const kit = CCAWardrobe.outfitById(ld.trainer, ld.outfit);
  const extras = (CCAWardrobe.extrasFor(ld.trainer) || []).filter(s => ld[s]);
  el.textContent = t.name + ' · ' + kit.name + (extras.length ? ' · ' + extras.join(' + ') : '');
})();
</script>
<script src="{$playerJs}"></script>
<script>
initThree('workout-3d-arena');
</script>
HTML;
include dirname(__DIR__) . '/includes/footer.php';
?>
