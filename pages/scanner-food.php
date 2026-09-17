<?php
require_once dirname(__DIR__) . '/config/config.php';
if (!is_logged_in()) { flash('warn', '🔒 The AI Food Scanner is a PRO tool — please log in first!'); redirect('auth/login.php?next=' . urlencode('pages/scanner-food.php')); }
if (($_SESSION['user']['role'] ?? '') !== 'member') { flash('warn', '🔒 The AI Food Scanner is exclusive to the Member Portal.'); redirect('portals/' . $_SESSION['user']['role'] . '.php'); }
if (!is_pro())       { flash('warn', '🔒 An active Pro subscription is required to use the AI Food Scanner.'); redirect('pages/pricing.php'); }

$pageTitle = 'AI Food Scanner';
include dirname(__DIR__) . '/includes/header.php';
?>
<style>
/* ── Google-Lens-style scan overlay (shared look with the body scanner) ── */
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
</style>
<!-- Cinematic Hero Section -->
<div class="relative h-[35vh] min-h-[300px] w-full bg-[url('https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=1600&q=80&auto=format&fit=crop')] bg-cover bg-center bg-no-repeat flex items-center justify-center text-center">
  <div class="absolute inset-0 bg-black/70 z-0"></div>
  <div class="relative z-10 max-w-4xl mx-auto px-6 w-full">
    <span class="eyebrow text-[#38BDF8] font-bold tracking-widest uppercase text-xs md:text-sm block">Member Portal · Live AI</span>
    <h1 class="text-white font-black tracking-wider uppercase mt-2 text-3xl md:text-5xl font-['Russo_One',sans-serif]">AI Food Scanner</h1>
    <p class="text-gray-300 max-w-2xl mx-auto mt-3 leading-relaxed text-xs md:text-sm">Scan any meal for instant macros or type manually to update your fitness log.</p>
  </div>
</div>

<div class="-mt-16 relative z-10 max-w-7xl mx-auto px-6 pb-24">
  <!-- Split screen layout -->
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
    
    <!-- Left Column (Upload Area) -->
    <div class="flex flex-col gap-6">
      <h3 class="text-xl font-bold tracking-wide uppercase font-['Rajdhani',sans-serif] text-gray-900 dark:text-white">Upload &amp; Analyze</h3>
      
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
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
          </div>
          <p class="text-sm font-semibold text-gray-900 dark:text-white">Drag &amp; drop meal image here, or <span class="text-brand-accent underline">browse</span></p>
          <p class="text-xs text-gray-600 dark:text-gray-400">Supports JPG, PNG (Max 5MB)</p>
        </div>
        <!-- Preview container -->
        <div id="previewContainer" class="hidden absolute inset-0 rounded-2xl overflow-hidden bg-black flex items-center justify-center z-10">
          <img id="imagePreview" class="w-full h-full object-cover" src="">
          <button id="removeImgBtn" class="absolute top-4 right-4 bg-black/60 text-white rounded-full p-2 hover:bg-red-600 transition-colors z-25">✕</button>
        </div>
      </div>

      <!-- Manual text entry -->
      <div class="cca-card">
        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-2 uppercase tracking-widest">Or type your meal manually</label>
        <div class="flex gap-2">
          <input type="text" id="manualMealInput" placeholder="e.g. 200g Grilled Chicken Breast and Rice" class="flex-1 bg-gray-100 dark:bg-[#0A0A0A] border border-gray-200 dark:border-white/10 rounded-xl px-4 py-3 text-gray-900 dark:text-white placeholder-gray-500 text-sm focus:outline-none focus:border-brand-accent focus:ring-2 focus:ring-brand-accent">
          <button id="manualScanBtn" class="px-6 py-3 bg-brand-accent text-gray-950 font-bold uppercase tracking-wider text-xs rounded-xl hover:bg-[#7dd3fc] transition-colors">Analyze</button>
        </div>
      </div>

      <button id="scanBtn" class="w-full py-4 bg-brand-accent text-gray-950 font-black tracking-widest uppercase rounded-xl hover:bg-[#7dd3fc] transition-all shadow-lg shadow-brand-accent/20 active:scale-[0.99]" disabled>Scan Meal ⚡</button>
    </div>

    <!-- Right Column (Result Area) -->
    <div class="flex flex-col gap-6">
      <h3 class="text-xl font-bold tracking-wide uppercase font-['Rajdhani',sans-serif] text-gray-900 dark:text-white">Analysis Results</h3>
      
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
          <p class="text-xs text-gray-600 dark:text-gray-400 max-w-sm">Upload an image or write your meal manually and hit "Scan Meal" or "Analyze" to see nutritional breakdown.</p>
        </div>

        <!-- Loading Spinner State -->
        <div id="resultLoading" class="hidden text-center flex flex-col items-center gap-4">
          <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-brand-accent"></div>
          <p class="text-brand-accent font-bold uppercase tracking-widest text-xs" id="loadingStatus">Detecting meal…</p>
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
            Error: No food detected. Please ensure the image shows a clear meal or type manually.
          </div>
          <button id="errorResetBtn" class="mt-4 px-6 py-2 bg-gray-100 dark:bg-white/10 hover:bg-gray-200 dark:hover:bg-white/15 border border-gray-200 dark:border-white/10 rounded-xl text-gray-900 dark:text-white font-bold text-xs uppercase tracking-wider">Try Again</button>
        </div>

        <!-- Success Results State -->
        <div id="resultData" class="hidden w-full flex flex-col gap-6">
          <div class="flex justify-between items-center border-b border-gray-200 dark:border-white/10 pb-4">
            <div>
              <span class="text-xs font-bold text-brand-accent uppercase tracking-wider">AI Identification</span>
              <h4 id="detectedMealName" class="text-2xl font-black text-gray-950 dark:text-white uppercase font-['Rajdhani',sans-serif] mt-1">Chicken &amp; Rice</h4>
            </div>
            <span id="detectedMealVerdict" class="px-3 py-1 bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 rounded-full text-xs font-bold uppercase tracking-wider">Healthy</span>
          </div>

          <!-- Nutritional stats grid -->
          <div class="grid grid-cols-4 gap-4 text-center">
            <div class="bg-gray-100 dark:bg-[#0A0A0A] border border-gray-200 dark:border-white/10 p-3 rounded-xl">
              <span class="block text-[10px] text-gray-600 dark:text-gray-400 font-bold uppercase tracking-wider">Calories</span>
              <b id="statKcal" class="text-xl font-bold font-['Rajdhani',sans-serif] text-gray-950 dark:text-white">450</b>
              <span class="block text-[10px] text-gray-500">kcal</span>
            </div>
            <div class="bg-gray-100 dark:bg-[#0A0A0A] border border-gray-200 dark:border-white/10 p-3 rounded-xl">
              <span class="block text-[10px] text-gray-600 dark:text-gray-400 font-bold uppercase tracking-wider">Protein</span>
              <b id="statProtein" class="text-xl font-bold font-['Rajdhani',sans-serif] text-brand-accent">35g</b>
              <span class="block text-[10px] text-gray-500">Target: 40%</span>
            </div>
            <div class="bg-gray-100 dark:bg-[#0A0A0A] border border-gray-200 dark:border-white/10 p-3 rounded-xl">
              <span class="block text-[10px] text-gray-600 dark:text-gray-400 font-bold uppercase tracking-wider">Carbs</span>
              <b id="statCarbs" class="text-xl font-bold font-['Rajdhani',sans-serif] text-amber-500">45g</b>
              <span class="block text-[10px] text-gray-500">Target: 45%</span>
            </div>
            <div class="bg-gray-100 dark:bg-[#0A0A0A] border border-gray-200 dark:border-white/10 p-3 rounded-xl">
              <span class="block text-[10px] text-gray-600 dark:text-gray-400 font-bold uppercase tracking-wider">Fats</span>
              <b id="statFats" class="text-xl font-bold font-['Rajdhani',sans-serif] text-pink-500">12g</b>
              <span class="block text-[10px] text-gray-500">Target: 15%</span>
            </div>
          </div>

          <!-- Detailed breakdown table -->
          <div class="border border-gray-200 dark:border-white/5 rounded-xl overflow-hidden">
            <div class="grid grid-cols-2 bg-gray-100 dark:bg-white/5 px-4 py-2 text-xs font-bold uppercase tracking-widest text-gray-600 dark:text-gray-400 border-b border-gray-200 dark:border-white/5">
              <span>Nutrient</span>
              <span class="text-right">Amount</span>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-white/5 text-sm">
              <div class="grid grid-cols-2 px-4 py-2.5">
                <span class="text-gray-600 dark:text-gray-400">Calories</span>
                <b class="text-right text-gray-900 dark:text-white" id="tableKcal">450 kcal</b>
              </div>
              <div class="grid grid-cols-2 px-4 py-2.5">
                <span class="text-gray-600 dark:text-gray-400">Protein</span>
                <b class="text-right text-gray-900 dark:text-white" id="tableProtein">35 g</b>
              </div>
              <div class="grid grid-cols-2 px-4 py-2.5">
                <span class="text-gray-600 dark:text-gray-400">Carbohydrates</span>
                <b class="text-right text-gray-900 dark:text-white" id="tableCarbs">45 g</b>
              </div>
              <div class="grid grid-cols-2 px-4 py-2.5">
                <span class="text-gray-600 dark:text-gray-400">Dietary Fiber</span>
                <b class="text-right text-gray-900 dark:text-white" id="tableFiber">4 g</b>
              </div>
              <div class="grid grid-cols-2 px-4 py-2.5">
                <span class="text-gray-600 dark:text-gray-400">Sugars</span>
                <b class="text-right text-gray-900 dark:text-white" id="tableSugars">2 g</b>
              </div>
              <div class="grid grid-cols-2 px-4 py-2.5">
                <span class="text-gray-600 dark:text-gray-400">Fats</span>
                <b class="text-right text-gray-900 dark:text-white" id="tableFats">12 g</b>
              </div>
              <div class="grid grid-cols-2 px-4 py-2.5">
                <span class="text-gray-600 dark:text-gray-400">Saturated Fat</span>
                <b class="text-right text-gray-900 dark:text-white" id="tableSatFat">2.5 g</b>
              </div>
              <div class="grid grid-cols-2 px-4 py-2.5">
                <span class="text-gray-600 dark:text-gray-400">Sodium</span>
                <b class="text-right text-gray-900 dark:text-white" id="tableSodium">320 mg</b>
              </div>
            </div>
          </div>

          <!-- Action buttons -->
          <div class="flex gap-4">
            <button id="addLogBtn" class="flex-1 py-3 bg-brand-accent text-gray-950 font-bold uppercase tracking-wider text-xs rounded-xl hover:bg-[#7dd3fc] transition-all">Add to Daily Log ➕</button>
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
  if (window.TFScanUI) { window.TFScanUI.init('food'); return; }
  const fileInput = document.getElementById('fileInput');
  const dropZone = document.getElementById('dropZone');
  const previewContainer = document.getElementById('previewContainer');
  const imagePreview = document.getElementById('imagePreview');
  const removeImgBtn = document.getElementById('removeImgBtn');
  const scanBtn = document.getElementById('scanBtn');
  const manualMealInput = document.getElementById('manualMealInput');
  const manualScanBtn = document.getElementById('manualScanBtn');
  const simulateError = document.getElementById('simulateError');
  const scannerLaser = document.getElementById('scannerLaser');

  const resultAwaiting = document.getElementById('resultAwaiting');
  const resultLoading = document.getElementById('resultLoading');
  const resultError = document.getElementById('resultError');
  const resultData = document.getElementById('resultData');
  const loadingStatus = document.getElementById('loadingStatus');

  const detectedMealName = document.getElementById('detectedMealName');
  const detectedMealVerdict = document.getElementById('detectedMealVerdict');
  const statKcal = document.getElementById('statKcal');
  const statProtein = document.getElementById('statProtein');
  const statCarbs = document.getElementById('statCarbs');
  const statFats = document.getElementById('statFats');

  const tableKcal = document.getElementById('tableKcal');
  const tableProtein = document.getElementById('tableProtein');
  const tableCarbs = document.getElementById('tableCarbs');
  const tableFats = document.getElementById('tableFats');

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

  window.simulateScan = (manualText = null) => {
    resultAwaiting.classList.add('hidden');
    resultError.classList.add('hidden');
    resultData.classList.add('hidden');
    resultLoading.classList.remove('hidden');
    scannerLaser.classList.remove('hidden');

    const statuses = ['Detecting meal…', 'Reading nutrition…', 'Calculating macros…'];
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

      let mealName = 'Premium Mixed Gym Meal';
      let kcal = 520;
      let protein = 40;
      let carbs = 55;
      let fats = 14;
      let verdict = 'Healthy';

      if (manualText) {
        mealName = manualText;
        const textLower = manualText.toLowerCase();
        if (textLower.includes('chicken')) {
          protein = 45; kcal = 480; carbs = 30; fats = 10;
        } else if (textLower.includes('rice') || textLower.includes('carbs')) {
          carbs = 70; protein = 15; kcal = 450; fats = 5;
        } else if (textLower.includes('shake') || textLower.includes('protein')) {
          protein = 50; carbs = 10; kcal = 280; fats = 3;
        } else if (textLower.includes('pizza') || textLower.includes('burger')) {
          protein = 25; carbs = 80; kcal = 850; fats = 45; verdict = 'High Calorie';
        }
      }

      detectedMealName.textContent = mealName;
      detectedMealVerdict.textContent = verdict;
      detectedMealVerdict.className = `px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider ${verdict === 'Healthy' ? 'bg-emerald-500/20 text-emerald-600 dark:text-emerald-400' : 'bg-amber-500/20 text-amber-600 dark:text-amber-400'}`;

      statKcal.textContent = kcal;
      statProtein.textContent = protein + 'g';
      statCarbs.textContent = carbs + 'g';
      statFats.textContent = fats + 'g';

      tableKcal.textContent = kcal + ' kcal';
      tableProtein.textContent = protein + ' g';
      tableCarbs.textContent = carbs + ' g';
      tableFats.textContent = fats + ' g';
      
      document.getElementById('tableFiber').textContent = Math.round(carbs * 0.08) + ' g';
      document.getElementById('tableSugars').textContent = Math.round(carbs * 0.04) + ' g';
      document.getElementById('tableSatFat').textContent = Math.round(fats * 0.2) + ' g';
      document.getElementById('tableSodium').textContent = Math.round(kcal * 0.7) + ' mg';

      resultData.classList.remove('hidden');
    }, 2200);
  };

  scanBtn.addEventListener('click', () => {
    simulateScan();
  });

  manualScanBtn.addEventListener('click', () => {
    const val = manualMealInput.value.trim();
    if (val) {
      simulateScan(val);
    }
  });

  document.getElementById('scanResetBtn').addEventListener('click', () => {
    resultData.classList.add('hidden');
    resultAwaiting.classList.remove('hidden');
  });

  document.getElementById('errorResetBtn').addEventListener('click', () => {
    resultError.classList.add('hidden');
    resultAwaiting.classList.remove('hidden');
  });

  document.getElementById('addLogBtn').addEventListener('click', () => {
    const btn = document.getElementById('addLogBtn');
    btn.disabled = true;
    btn.textContent = 'Logging…';
    
    const fd = new FormData();
    fd.append('csrf', TF.csrf);
    fd.append('name', detectedMealName.textContent);
    fd.append('kcal', parseInt(statKcal.textContent));
    fd.append('protein', parseInt(statProtein.textContent));
    fd.append('carbs', parseInt(statCarbs.textContent));
    fd.append('fats', parseInt(statFats.textContent));

    fetch(TF.baseUrl + '/api/log-food.php', { method: 'POST', body: fd })
      .then(res => res.json())
      .then(j => {
        if (j.ok) {
          btn.textContent = '✔ Added to Daily Log';
          if (window.toast) window.toast('✔ Meal logged successfully!');
        } else {
          btn.disabled = false;
          btn.textContent = 'Add to Daily Log ➕';
          alert('Error: ' + (j.error || 'Could not log meal.'));
        }
      })
      .catch(() => {
        btn.disabled = false;
        btn.textContent = 'Add to Daily Log ➕';
        alert('Network error logged.');
      });
  });
});
</script>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
