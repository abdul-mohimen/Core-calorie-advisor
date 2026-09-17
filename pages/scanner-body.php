<?php
require_once dirname(__DIR__) . '/config/config.php';
/* ---- Access: login + member role + PRO plan (Member Portal exclusive tool) ---- */
if (!is_logged_in()) { flash('warn', '🔒 The AI Body Scanner is a PRO tool — please log in first!'); redirect('auth/login.php?next=' . urlencode('pages/scanner-body.php')); }
if (($_SESSION['user']['role'] ?? '') !== 'member') { flash('warn', '🔒 The AI Body Scanner is exclusive to the Member Portal.'); redirect('portals/' . $_SESSION['user']['role'] . '.php'); }
if (!is_pro())       { flash('warn', '🔒 An active Pro subscription is required to use the AI Body Scanner.'); redirect('pages/pricing.php'); }

$pageTitle = 'AI Body Scanner';
include dirname(__DIR__) . '/includes/header.php';
?>
<style>
/* ── Google-Lens-style scan overlay ── */
.lens-overlay { border-radius: 1rem; overflow: hidden; }
.lens-corner { position: absolute; width: 34px; height: 34px; border: 3px solid var(--accent-sky); filter: drop-shadow(0 0 6px rgba(56,189,248,.8)); }
.lens-tl { top: 10px; left: 10px; border-right: 0; border-bottom: 0; border-top-left-radius: 10px; }
.lens-tr { top: 10px; right: 10px; border-left: 0; border-bottom: 0; border-top-right-radius: 10px; }
.lens-bl { bottom: 10px; left: 10px; border-right: 0; border-top: 0; border-bottom-left-radius: 10px; }
.lens-br { bottom: 10px; right: 10px; border-left: 0; border-top: 0; border-bottom-right-radius: 10px; }
@keyframes lensSweep { 0%, 100% { top: 4%; } 50% { top: 92%; } }
.lens-beam { position: absolute; left: 6%; right: 6%; height: 3px; border-radius: 3px;
  background: linear-gradient(90deg, transparent, var(--accent-sky) 20%, var(--accent-sky-soft) 50%, var(--accent-sky) 80%, transparent);
  box-shadow: 0 0 18px 4px rgba(56,189,248,.55); animation: lensSweep 2.2s ease-in-out infinite; }
@keyframes lensGridPulse { 0%, 100% { opacity: .12; } 50% { opacity: .3; } }
.lens-grid { position: absolute; inset: 0;
  background-image: linear-gradient(rgba(56,189,248,.5) 1px, transparent 1px), linear-gradient(90deg, rgba(56,189,248,.5) 1px, transparent 1px);
  background-size: 36px 36px; animation: lensGridPulse 2.2s ease-in-out infinite; }
.goal-chip.on { border-color: var(--accent-sky) !important; background: rgba(56,189,248,.15) !important; color: var(--accent-sky-text) !important; }
/* muscle focus rows */
.mf-row { display: flex; align-items: center; gap: 12px; padding: 10px 14px; border: 1px solid var(--line); border-radius: 12px; background: var(--glass); }
.mf-name { font-weight: 800; font-size: 13.5px; min-width: 92px; }
.mf-bar { flex: 1; height: 7px; border-radius: 4px; background: rgba(125,125,125,.18); overflow: hidden; }
.mf-fill { height: 100%; border-radius: 4px; background: linear-gradient(90deg, var(--accent-sky), var(--accent-sky-soft)); transition: width .8s cubic-bezier(.22,.8,.3,1); }
.mf-act { font-size: 12px; color: var(--muted); flex: 1.4; line-height: 1.45; }
@media (max-width: 640px) { .mf-row { flex-wrap: wrap; } .mf-act { flex-basis: 100%; } }
</style>
<!-- Cinematic Hero Section -->
<div class="relative h-[35vh] min-h-[300px] w-full bg-[url('https://images.unsplash.com/photo-1541534741688-6078c6bfb5c5?w=1600&q=80&auto=format&fit=crop')] bg-cover bg-center bg-no-repeat flex items-center justify-center text-center">
  <div class="absolute inset-0 bg-black/70 z-0"></div>
  <div class="relative z-10 max-w-4xl mx-auto px-6 w-full">
    <span class="eyebrow text-[#38BDF8] font-bold tracking-widest uppercase text-xs md:text-sm block">Member Portal · Live AI</span>
    <h1 class="text-white font-black tracking-wider uppercase mt-2 text-3xl md:text-5xl font-['Russo_One',sans-serif]">AI Body Scanner</h1>
    <p class="text-gray-300 max-w-2xl mx-auto mt-3 leading-relaxed text-xs md:text-sm">Scan your physique for body composition stats and suggestions.</p>
  </div>
</div>

<div class="-mt-16 relative z-10 max-w-7xl mx-auto px-6 pb-24">
  <!-- Split screen layout -->
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
    
    <!-- Left Column (Upload Area) -->
    <div class="flex flex-col gap-6">
      <h3 class="text-xl font-bold tracking-wide uppercase font-['Rajdhani',sans-serif] text-gray-900 dark:text-white">Physique Upload</h3>
      
      <!-- Dashed Drag & Drop Box -->
      <div id="dropZone" class="border-2 border-dashed border-gray-300 dark:border-white/20 hover:border-brand-accent/50 rounded-2xl p-10 flex flex-col items-center justify-center gap-4 bg-white dark:bg-[#121212] transition-all cursor-pointer relative min-h-[320px] shadow-xl overflow-hidden">
        <input type="file" id="fileInput" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer z-20">
        
        <!-- Google-Lens-style scan overlay: corner brackets + sweeping beam + grid shimmer -->
        <div id="scannerLaser" class="hidden absolute inset-0 z-30 pointer-events-none lens-overlay">
          <span class="lens-corner lens-tl"></span><span class="lens-corner lens-tr"></span>
          <span class="lens-corner lens-bl"></span><span class="lens-corner lens-br"></span>
          <div class="lens-beam"></div>
          <div class="lens-grid"></div>
        </div>

        <div class="text-center flex flex-col items-center justify-center gap-3" id="uploadPrompt">
          <div class="w-16 h-16 rounded-full bg-brand-accent/10 flex items-center justify-center text-brand-accent mb-2">
            <svg class="w-10 h-10 text-brand-accent" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
          </div>
          <p class="text-sm font-semibold text-gray-900 dark:text-white">Drag &amp; drop physique image here, or <span class="text-brand-accent underline">browse</span></p>
          <p class="text-xs text-gray-600 dark:text-gray-400">Supports JPG, PNG (Max 5MB)</p>
        </div>
        <!-- Preview container -->
        <div id="previewContainer" class="hidden absolute inset-0 rounded-2xl overflow-hidden bg-black flex items-center justify-center z-10">
          <img id="imagePreview" class="w-full h-full object-cover" src="">
          <button id="removeImgBtn" class="absolute top-4 right-4 bg-black/60 text-white rounded-full p-2 hover:bg-red-600 transition-colors z-25">✕</button>
        </div>
      </div>

      <!-- Training goal — scan guidance is tailored to this -->
      <div>
        <p class="text-xs font-bold uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-2">Training Goal</p>
        <div id="goalChips" class="flex gap-2 flex-wrap">
          <button type="button" data-goal="gain"   class="goal-chip px-4 py-2 rounded-full border text-xs font-bold uppercase tracking-wider transition-all border-gray-300 dark:border-white/15 text-gray-600 dark:text-gray-300 hover:border-brand-accent">💪 Muscle Gain</button>
          <button type="button" data-goal="recomp" class="goal-chip on px-4 py-2 rounded-full border text-xs font-bold uppercase tracking-wider transition-all border-brand-accent bg-brand-accent/15 text-brand-accent">⚖️ Recomposition</button>
          <button type="button" data-goal="cut"    class="goal-chip px-4 py-2 rounded-full border text-xs font-bold uppercase tracking-wider transition-all border-gray-300 dark:border-white/15 text-gray-600 dark:text-gray-300 hover:border-brand-accent">🔥 Fat Loss</button>
        </div>
      </div>

      <button id="scanBtn" class="w-full py-4 bg-brand-accent text-gray-950 font-black tracking-widest uppercase rounded-xl hover:bg-[#7dd3fc] transition-all shadow-lg shadow-brand-accent/20 active:scale-[0.99]" disabled>Scan Physique ⚡</button>
    </div>

    <!-- Right Column (Result Area) -->
    <div class="flex flex-col gap-6">
      <h3 class="text-xl font-bold tracking-wide uppercase font-['Rajdhani',sans-serif] text-gray-900 dark:text-white">Composition Report</h3>
      
      <!-- Dark Glassmorphism Result Card -->
      <div id="resultCard" class="cca-card flex flex-col justify-center items-center min-h-[320px] relative backdrop-blur-md shadow-xl text-gray-900 dark:text-white">
        
        <!-- Default State: Awaiting Scan -->
        <div id="resultAwaiting" class="text-center flex flex-col items-center gap-3">
          <div class="text-gray-500 mb-4">
            <svg class="w-16 h-16 text-brand-accent animate-pulse" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <h4 class="text-lg font-bold text-gray-900 dark:text-white">Awaiting Scan</h4>
          <p class="text-xs text-gray-600 dark:text-gray-400 max-w-sm">Upload a full body or physique image and hit "Scan Physique" to see composition analysis and suggestions.</p>
        </div>

        <!-- Loading Spinner State -->
        <div id="resultLoading" class="hidden text-center flex flex-col items-center gap-4">
          <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-brand-accent"></div>
          <p class="text-brand-accent font-bold uppercase tracking-widest text-xs" id="loadingStatus">Detecting physique…</p>
        </div>

        <!-- Error Fallback Alert State -->
        <div id="resultError" class="hidden w-full text-center flex flex-col items-center gap-4">
          <div class="w-16 h-16 rounded-full bg-red-500/10 flex items-center justify-center text-red-500 mb-2">
            <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
          </div>
          <h4 class="text-lg font-bold text-red-500">Scan Failed</h4>
          <div class="bg-red-500/15 border border-red-500/20 text-red-800 dark:text-red-300 text-sm px-6 py-4 rounded-xl max-w-md w-full" id="errorMsg">
            Error: No body detected. Please ensure you are fully in frame with clear lighting.
          </div>
          <button id="errorResetBtn" class="mt-4 px-6 py-2 bg-gray-100 dark:bg-white/10 hover:bg-gray-200 dark:hover:bg-white/15 border border-gray-200 dark:border-white/10 rounded-xl text-gray-900 dark:text-white font-bold text-xs uppercase tracking-wider">Try Again</button>
        </div>

        <!-- Success Results State -->
        <div id="resultData" class="hidden w-full flex flex-col gap-6">
          <div class="flex justify-between items-center border-b border-gray-200 dark:border-white/10 pb-4">
            <div>
              <span class="text-xs font-bold text-brand-accent uppercase tracking-wider">AI Composition Verdict</span>
              <h4 class="text-2xl font-black text-gray-950 dark:text-white uppercase font-['Rajdhani',sans-serif] mt-1" id="detectedVerdict">Lean &amp; Tone</h4>
            </div>
            <span id="detectedVerdictLabel" class="px-3 py-1 bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 rounded-full text-xs font-bold uppercase tracking-wider">Maintain</span>
          </div>

          <!-- Stats table -->
          <div class="border border-gray-200 dark:border-white/5 rounded-xl overflow-hidden">
            <div class="grid grid-cols-2 bg-gray-100 dark:bg-white/5 px-4 py-2 text-xs font-bold uppercase tracking-widest text-gray-600 dark:text-gray-400 border-b border-gray-200 dark:border-white/5">
              <span>Physique Attribute</span>
              <span class="text-right">Measurement</span>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-white/5 text-sm">
              <div class="grid grid-cols-2 px-4 py-2.5">
                <span class="text-gray-600 dark:text-gray-400">Body Type</span>
                <b class="text-right text-gray-900 dark:text-white" id="statBodyType">Mesomorph</b>
              </div>
              <div class="grid grid-cols-2 px-4 py-2.5">
                <span class="text-gray-600 dark:text-gray-400">Estimated Weight</span>
                <b class="text-right text-gray-900 dark:text-white" id="statWeight">72.4 kg</b>
              </div>
              <div class="grid grid-cols-2 px-4 py-2.5">
                <span class="text-gray-600 dark:text-gray-400">Body Fat Estimate</span>
                <b class="text-right text-gray-900 dark:text-white" id="statBodyFat">14.5%</b>
              </div>
              <div class="grid grid-cols-2 px-4 py-2.5">
                <span class="text-gray-600 dark:text-gray-400">BMI</span>
                <b class="text-right text-gray-900 dark:text-white" id="statBMI">23.6</b>
              </div>
              <div class="grid grid-cols-2 px-4 py-2.5">
                <span class="text-gray-600 dark:text-gray-400">Muscle Mass</span>
                <b class="text-right text-gray-900 dark:text-white" id="statMuscleMass">34.8 kg</b>
              </div>
              <div class="grid grid-cols-2 px-4 py-2.5">
                <span class="text-gray-600 dark:text-gray-400">Suggested Trainer</span>
                <b class="text-right text-gray-900 dark:text-white" id="statTrainer">Trainer Alex</b>
              </div>
              <div class="grid grid-cols-2 px-4 py-2.5">
                <span class="text-gray-600 dark:text-gray-400">Suggested Program</span>
                <b class="text-right text-gray-900 dark:text-white" id="statProgram">CCA Hypertrophy</b>
              </div>
            </div>
          </div>

          <!-- Muscle Focus breakdown: which muscles to develop and what to do -->
          <div id="muscleFocusWrap" class="hidden">
            <div class="flex items-center gap-2 mb-3">
              <span class="w-2 h-2 rounded-full bg-brand-accent animate-pulse"></span>
              <span class="text-xs font-black uppercase tracking-widest text-brand-accent">Muscle Focus — kya develop karna hai</span>
            </div>
            <div id="muscleFocusList" class="flex flex-col gap-2"></div>
          </div>

          <!-- Next steps checklist -->
          <div id="nextStepsWrap" class="hidden">
            <div class="text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-2">Next Steps</div>
            <ul id="nextStepsList" class="flex flex-col gap-1.5 text-sm text-gray-700 dark:text-gray-300 list-none p-0 m-0"></ul>
          </div>

          <!-- Health Note Banner -->
          <div id="healthNoteContainer" class="bg-red-500/10 border border-red-500/20 text-red-800 dark:text-red-300 p-4 rounded-xl text-xs flex flex-col gap-2">
            <div class="flex items-center gap-2">
              <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
              <b class="text-sm font-bold text-red-800 dark:text-red-300">Health Consultation Advised</b>
            </div>
            <p id="statHealthNote" class="text-gray-700 dark:text-gray-300">Photo analysis is not a diagnosis. Consult a qualified clinician for pain, injury, or health concerns.</p>
            <a href="<?= url('pages/trainers.php') ?>" id="doctorConsultBtn" class="text-[#38BDF8] hover:text-[#7dd3fc] underline mt-1">Book consultation →</a>
          </div>

          <!-- Action buttons -->
          <div class="flex gap-4">
            <a href="<?= url('pages/workouts.php') ?>" id="startWorkoutBtn" class="flex-1 py-3 bg-brand-accent text-gray-950 font-bold uppercase tracking-wider text-xs rounded-xl hover:bg-[#7dd3fc] transition-all text-center flex items-center justify-center no-underline">Start Suggested Workout →</a>
            <button id="scanResetBtn" class="px-6 py-3 bg-gray-100 dark:bg-white/10 hover:bg-gray-200 dark:hover:bg-white/15 border border-gray-200 dark:border-white/10 rounded-xl text-gray-900 dark:text-white font-bold text-xs uppercase tracking-wider">Scan Again ↺</button>
          </div>
        </div>

      </div>
    </div>

  </div>
</div>

<script src="<?= asset('js/scan-ui.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  if (window.TFScanUI) { window.TFScanUI.init('body'); return; }
  const fileInput = document.getElementById('fileInput');
  const dropZone = document.getElementById('dropZone');
  const previewContainer = document.getElementById('previewContainer');
  const imagePreview = document.getElementById('imagePreview');
  const removeImgBtn = document.getElementById('removeImgBtn');
  const scanBtn = document.getElementById('scanBtn');
  const simulateError = document.getElementById('simulateError');
  const scannerLaser = document.getElementById('scannerLaser');

  const resultAwaiting = document.getElementById('resultAwaiting');
  const resultLoading = document.getElementById('resultLoading');
  const resultError = document.getElementById('resultError');
  const resultData = document.getElementById('resultData');
  const loadingStatus = document.getElementById('loadingStatus');

  const detectedVerdict = document.getElementById('detectedVerdict');
  const detectedVerdictLabel = document.getElementById('detectedVerdictLabel');
  const statBodyType = document.getElementById('statBodyType');
  const statWeight = document.getElementById('statWeight');
  const statBodyFat = document.getElementById('statBodyFat');
  const statBMI = document.getElementById('statBMI');
  const statMuscleMass = document.getElementById('statMuscleMass');
  const statTrainer = document.getElementById('statTrainer');
  const statProgram = document.getElementById('statProgram');

  let activePhotoBlob = null;

  const handleFileSelect = (file) => {
    if (!file) return;
    if (file.size > 5 * 1024 * 1024) {
      alert('File too large. Max 5MB allowed.');
      return;
    }
    const reader = new FileReader();
    reader.onload = (e) => {
      imagePreview.src = e.target.result;
      previewContainer.classList.remove('hidden');
      scanBtn.disabled = false;
      activePhotoBlob = file;
    };
    reader.readAsDataURL(file);
  };

  fileInput.addEventListener('change', (e) => {
    handleFileSelect(e.target.files[0]);
  });

  dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.classList.add('border-brand-accent/70', 'bg-gray-100', 'dark:bg-white/10');
  });

  dropZone.addEventListener('dragleave', () => {
    dropZone.classList.remove('border-brand-accent/70', 'bg-gray-100', 'dark:bg-white/10');
  });

  dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('border-brand-accent/70', 'bg-gray-100', 'dark:bg-white/10');
    if (e.dataTransfer.files.length) {
      handleFileSelect(e.dataTransfer.files[0]);
    }
  });

  removeImgBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    fileInput.value = '';
    imagePreview.src = '';
    previewContainer.classList.add('hidden');
    scanBtn.disabled = true;
    activePhotoBlob = null;
    scannerLaser.classList.add('hidden');
  });

  window.simulateScan = () => {
    resultAwaiting.classList.add('hidden');
    resultError.classList.add('hidden');
    resultData.classList.add('hidden');
    resultLoading.classList.remove('hidden');
    scannerLaser.classList.remove('hidden');

    const statuses = ['Detecting physique…', 'Measuring composition…', 'Matching trainer & program…'];
    let step = 0;
    loadingStatus.textContent = statuses[0];
    const statusInterval = setInterval(() => {
      step++;
      if (step < statuses.length) {
        loadingStatus.textContent = statuses[step];
      }
    }, 700);

    setTimeout(() => {
      clearInterval(statusInterval);
      resultLoading.classList.add('hidden');
      scannerLaser.classList.add('hidden');

      if (simulateError.checked) {
        resultError.classList.remove('hidden');
        return;
      }

      // Generate body composition results
      detectedVerdict.textContent = 'Athletic Mesomorph';
      detectedVerdictLabel.textContent = 'Optimal';
      detectedVerdictLabel.className = 'px-3 py-1 bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 rounded-full text-xs font-bold uppercase tracking-wider';

      statBodyType.textContent = 'Mesomorph';
      statWeight.textContent = '74.2 kg';
      statBodyFat.textContent = '12.8%';
      statBMI.textContent = '22.9';
      statMuscleMass.textContent = '38.5 kg';
      statTrainer.textContent = 'Trainer Alex';
      statProgram.textContent = 'CCA Hypertrophy Level 2';

      resultData.classList.remove('hidden');
    }, 2200);
  };

  scanBtn.addEventListener('click', () => {
    simulateScan();
  });

  document.getElementById('scanResetBtn').addEventListener('click', () => {
    resultData.classList.add('hidden');
    resultAwaiting.classList.remove('hidden');
  });

  document.getElementById('errorResetBtn').addEventListener('click', () => {
    resultError.classList.add('hidden');
    resultAwaiting.classList.remove('hidden');
  });
});
</script>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
