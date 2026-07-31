<?php
require_once dirname(__DIR__) . '/config/config.php';
$pageTitle = 'Calculators';
include dirname(__DIR__) . '/includes/header.php';
?>
<!-- Cinematic Hero Image Section -->
<div class="relative h-[35vh] min-h-[300px] w-full bg-[url('https://images.unsplash.com/photo-1434682881908-b43d0467b798?w=1600&q=80&auto=format&fit=crop')] bg-cover bg-center bg-no-repeat flex items-center justify-center text-center">
  <!-- bg-black/70 overlay -->
  <div class="absolute inset-0 bg-black/70 z-0"></div>

  <div class="relative z-10 max-w-4xl mx-auto px-6 w-full">
    <span class="eyebrow text-[#38BDF8] font-bold tracking-widest uppercase text-xs md:text-sm block">Know Your Numbers</span>
    <h1 class="text-white font-black tracking-wider uppercase mt-2 text-3xl md:text-5xl font-['Russo_One',sans-serif]">Fitness Calculators</h1>
    <p class="text-gray-300 max-w-2xl mx-auto mt-3 leading-relaxed text-xs md:text-sm">Calculate your BMI, BMR, daily calories, water requirements, and macronutrient targets.</p>
  </div>
</div>

<!-- Main Body Wrapper -->
<div class="-mt-16 relative z-10 max-w-7xl mx-auto px-6 pb-24 text-gray-900 dark:text-white">
  <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
    
    <!-- BMI Card -->
    <div class="bg-white dark:bg-[#121212] border border-gray-200 dark:border-white/10 rounded-2xl p-8 flex flex-col justify-between shadow-xl">
      <div>
        <h3 class="text-lg font-bold mb-4 font-['Rajdhani',sans-serif] uppercase tracking-wider text-gray-900 dark:text-white">🧮 BMI &amp; Category</h3>
        <div class="grid grid-cols-2 gap-4">
          <div class="field flex flex-col"><label class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Height (cm)</label><input type="number" id="cH" class="w-full p-2.5 mt-1 bg-gray-100 dark:bg-[#0A0A0A] border border-gray-200 dark:border-white/10 text-gray-900 dark:text-white rounded-xl outline-none focus:ring-2 focus:ring-brand-accent" value="175"></div>
          <div class="field flex flex-col"><label class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Weight (kg)</label><input type="number" id="cW" class="w-full p-2.5 mt-1 bg-gray-100 dark:bg-[#0A0A0A] border border-gray-200 dark:border-white/10 text-gray-900 dark:text-white rounded-xl outline-none focus:ring-2 focus:ring-brand-accent" value="72"></div>
        </div>
      </div>
      <button class="btn btn-fire mt-6 w-full" onclick="calcBMI()">Calculate</button>
      <div class="calc-out mt-4 p-4 bg-gray-100 dark:bg-[#0A0A0A] border border-gray-200 dark:border-white/10 rounded-xl" id="cBMIout" style="display:none"><b id="cBMI" class="text-gray-900 dark:text-white text-xl">0</b><span id="cBMIcat" class="text-gray-600 dark:text-gray-400 ml-2">—</span></div>
    </div>

    <!-- BMR Card -->
    <div class="bg-white dark:bg-[#121212] border border-gray-200 dark:border-white/10 rounded-2xl p-8 flex flex-col justify-between shadow-xl">
      <div>
        <h3 class="text-lg font-bold mb-4 font-['Rajdhani',sans-serif] uppercase tracking-wider text-gray-900 dark:text-white">🔥 Daily Calories (BMR)</h3>
        <div class="grid grid-cols-2 gap-4">
          <div class="field flex flex-col"><label class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Age</label><input type="number" id="bAge" class="w-full p-2.5 mt-1 bg-gray-100 dark:bg-[#0A0A0A] border border-gray-200 dark:border-white/10 text-gray-900 dark:text-white rounded-xl outline-none focus:ring-2 focus:ring-brand-accent" value="24"></div>
          <div class="field flex flex-col"><label class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Gender</label><select id="bGen" class="w-full p-2.5 mt-1 bg-gray-100 dark:bg-[#0A0A0A] border border-gray-200 dark:border-white/10 text-gray-900 dark:text-white rounded-xl outline-none focus:ring-2 focus:ring-brand-accent"><option value="m">Male</option><option value="f">Female</option></select></div>
          <div class="field flex flex-col"><label class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Height (cm)</label><input type="number" id="bH" class="w-full p-2.5 mt-1 bg-gray-100 dark:bg-[#0A0A0A] border border-gray-200 dark:border-white/10 text-gray-900 dark:text-white rounded-xl outline-none focus:ring-2 focus:ring-brand-accent" value="175"></div>
          <div class="field flex flex-col"><label class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Weight (kg)</label><input type="number" id="bW" class="w-full p-2.5 mt-1 bg-gray-100 dark:bg-[#0A0A0A] border border-gray-200 dark:border-white/10 text-gray-900 dark:text-white rounded-xl outline-none focus:ring-2 focus:ring-brand-accent" value="72"></div>
        </div>
        <div class="field flex flex-col mt-3"><label class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Activity Level</label>
          <select id="bAct" class="w-full p-2.5 mt-1 bg-gray-100 dark:bg-[#0A0A0A] border border-gray-200 dark:border-white/10 text-gray-900 dark:text-white rounded-xl outline-none focus:ring-2 focus:ring-brand-accent"><option value="1.2">Sedentary</option><option value="1.375">Light</option><option value="1.55" selected>Moderate</option><option value="1.725">Very Active</option></select></div>
      </div>
      <button class="btn btn-fire mt-6 w-full" onclick="calcBMR()">Calculate</button>
      <div class="calc-out mt-4 p-4 bg-gray-100 dark:bg-[#0A0A0A] border border-gray-200 dark:border-white/10 rounded-xl" id="bOut" style="display:none"><b id="bCal" class="text-gray-900 dark:text-white text-xl">0</b><span class="text-gray-600 dark:text-gray-400 ml-2">KCAL / DAY TO MAINTAIN</span></div>
    </div>

    <!-- Water Intake Card -->
    <div class="bg-white dark:bg-[#121212] border border-gray-200 dark:border-white/10 rounded-2xl p-8 flex flex-col justify-between shadow-xl">
      <div>
        <h3 class="text-lg font-bold mb-4 font-['Rajdhani',sans-serif] uppercase tracking-wider text-gray-900 dark:text-white">💧 Water Intake</h3>
        <div class="field flex flex-col"><label class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Weight (kg)</label><input type="number" id="wW" class="w-full p-2.5 mt-1 bg-gray-100 dark:bg-[#0A0A0A] border border-gray-200 dark:border-white/10 text-gray-900 dark:text-white rounded-xl outline-none focus:ring-2 focus:ring-brand-accent" value="72"></div>
      </div>
      <button class="btn btn-fire mt-6 w-full" onclick="calcWater()">Calculate</button>
      <div class="calc-out mt-4 p-4 bg-gray-100 dark:bg-[#0A0A0A] border border-gray-200 dark:border-white/10 rounded-xl" id="wOut" style="display:none"><b id="wL" class="text-gray-900 dark:text-white text-xl">0</b><span class="text-gray-600 dark:text-gray-400 ml-2">LITERS PER DAY</span></div>
    </div>

    <!-- Macro Split Card -->
    <div class="bg-white dark:bg-[#121212] border border-gray-200 dark:border-white/10 rounded-2xl p-8 flex flex-col justify-between shadow-xl">
      <div>
        <h3 class="text-lg font-bold mb-4 font-['Rajdhani',sans-serif] uppercase tracking-wider text-gray-900 dark:text-white">🥩 Macro Split</h3>
        <div class="field flex flex-col"><label class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Daily Calories</label><input type="number" id="mCal" class="w-full p-2.5 mt-1 bg-gray-100 dark:bg-[#0A0A0A] border border-gray-200 dark:border-white/10 text-gray-900 dark:text-white rounded-xl outline-none focus:ring-2 focus:ring-brand-accent" value="2400"></div>
      </div>
      <button class="btn btn-fire mt-6 w-full" onclick="calcMacro()">Calculate</button>
      <div class="calc-out mt-4 p-4 bg-gray-100 dark:bg-[#0A0A0A] border border-gray-200 dark:border-white/10 rounded-xl" id="mOut" style="display:none"><b id="mTxt" class="text-gray-900 dark:text-white text-xl" style="font-size:22px">—</b><span class="text-gray-600 dark:text-gray-400 ml-2">PROTEIN / CARBS / FATS</span></div>
    </div>

  </div>
</div>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
