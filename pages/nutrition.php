<?php
require_once dirname(__DIR__) . '/config/config.php';
$pageTitle = 'Nutrition';
$q = get('q');
if ($q !== '') {
    $st = db()->prepare('SELECT * FROM foods WHERE name LIKE ? ORDER BY name');
    $st->execute(['%' . $q . '%']); $foods = $st->fetchAll();
} else {
    $foods = db()->query('SELECT * FROM foods ORDER BY name')->fetchAll();
}
include dirname(__DIR__) . '/includes/header.php';
?>
<style>
  .page-hero::before, .page-hero::after { display: none !important; }
</style>


<!-- Isolated Hero Header Section -->
<div class="relative h-[35vh] min-h-[300px] w-full bg-[url('https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=1600&q=80&auto=format&fit=crop')] bg-cover bg-center bg-no-repeat flex items-center tf-hero-anim">
  <!-- Heavy dark overlay -->
  <div class="absolute inset-0 bg-black/80 z-0"></div>

  <div class="relative z-10 max-w-7xl mx-auto px-6 w-full text-left">
    <span class="eyebrow text-[#38BDF8] font-bold tracking-widest uppercase text-xs md:text-sm">Fuel Your Performance</span>
    <h1 class="text-white font-black tracking-wider uppercase mt-2 text-2xl md:text-4xl">Nutrition Database</h1>
    <p class="text-gray-300 max-w-2xl mt-3 leading-relaxed text-xs md:text-sm">Optimize your diet with our clean food library. High-quality fuel for legendary performance.</p>
  </div>
</div>

<!-- Solid Dark Background Content Area -->
<div class="-mt-16 relative z-10 max-w-7xl mx-auto px-6 pb-24 text-gray-900 dark:text-white">
  
  <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
    <!-- Search Form -->
    <form method="get" class="w-full max-w-md">
      <div class="field bg-white dark:bg-[#121212] border border-gray-200 dark:border-white/10 rounded-xl p-3 shadow-md">
        <label class="text-gray-600 dark:text-gray-400 text-xs font-bold uppercase tracking-wider block mb-1">Search Food</label>
        <input type="text" name="q" value="<?= e($q ?? '') ?>" placeholder="chicken, rice, daal..." class="w-full bg-transparent border-0 text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-0">
      </div>
    </form>

    <?php if (($u ?? null) && ($u['role'] ?? '') === 'member'): ?>
      <a class="btn btn-fire btn-sm" href="<?= url('pages/scanner-food.php') ?>">🍎 AI Food Scanner</a>
    <?php else: ?>
      <a class="btn btn-ghost btn-sm" href="<?= url('pages/pricing.php') ?>">🍎 AI Food Scanner — Member Only</a>
    <?php endif; ?>
  </div>

  <!-- Cards Grid -->
  <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
    <?php if (!$foods): ?>
      <p class="text-gray-600 dark:text-gray-400 col-span-full text-center py-10 font-semibold">No food items found matching "<?= e($q ?? '') ?>".</p>
    <?php endif; ?>
    
    <?php foreach ($foods as $f): ?>
    <div class="bg-white dark:bg-[#121212] border border-gray-200 dark:border-white/10 rounded-xl overflow-hidden hover:border-brand-accent transition-all duration-300 flex flex-col h-full relative shadow-xl">
      <!-- Thumbnail -->
      <div class="h-48 w-full overflow-hidden bg-neutral-900 relative">
        <img src="<?= e(!empty($f['image']) ? $f['image'] : 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=500&q=60&auto=format&fit=crop') ?>" alt="<?= e($f['name'] ?? 'Food') ?>" class="w-full h-48 object-cover transition-transform duration-500 hover:scale-105" loading="lazy">
      </div>

      <!-- Content -->
      <div class="p-5 flex flex-col flex-1 justify-between">
        <div>
          <h3 class="text-base font-extrabold text-gray-900 dark:text-white mb-1.5 leading-tight tracking-tight"><?= e($f['name'] ?? 'Food Item') ?></h3>
          <div class="text-xs text-gray-500 dark:text-gray-400 font-semibold mb-3">Serving: <b class="text-gray-700 dark:text-gray-300"><?= e($f['serving'] ?? '1 serving') ?></b></div>
        </div>
        
        <div class="border-t border-gray-200 dark:border-white/5 pt-3.5 mt-auto">
          <div class="flex items-center justify-between text-xs text-gray-600 dark:text-gray-400 font-semibold mb-2">
            <span>Calories: <b class="text-[#38BDF8]"><?= (int)($f['kcal'] ?? 0) ?> kcal</b></span>
          </div>
          <div class="grid grid-cols-3 gap-1 text-center text-[10px] text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-white/5 rounded-lg py-1.5 font-bold uppercase tracking-wider">
            <div>P: <span class="text-gray-900 dark:text-white"><?= e((string)($f['protein'] ?? 0)) ?>g</span></div>
            <div>C: <span class="text-gray-900 dark:text-white"><?= e((string)($f['carbs'] ?? 0)) ?>g</span></div>
            <div>F: <span class="text-gray-900 dark:text-white"><?= e((string)($f['fats'] ?? 0)) ?>g</span></div>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
