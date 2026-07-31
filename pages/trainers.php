<?php
require_once dirname(__DIR__) . '/config/config.php';
$pageTitle = 'Trainers';
$trainers = db()->query('SELECT tp.*, u.id AS uid, u.name FROM trainer_profiles tp JOIN users u ON u.id = tp.user_id ORDER BY tp.rating DESC')->fetchAll();
$u = current_user();
include dirname(__DIR__) . '/includes/header.php';
?>
<style>
  .page-hero::before, .page-hero::after { display: none !important; }
</style>
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    corePlugins: { preflight: false },
    theme: {
      extend: {
        colors: {
          'brand-accent': '#38BDF8',
        }
      }
    }
  }
</script>

<!-- Isolated Hero Header Section -->
<div class="relative h-[35vh] min-h-[300px] w-full bg-[url('https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?w=1600&q=80&auto=format&fit=crop')] bg-cover bg-center bg-no-repeat flex items-center">
  <!-- Heavy dark overlay -->
  <div class="absolute inset-0 bg-black/80 z-0"></div>

  <div class="relative z-10 max-w-7xl mx-auto px-6 w-full text-left">
    <span class="eyebrow text-[#38BDF8] font-bold tracking-widest uppercase text-xs md:text-sm">Book A Session</span>
    <h1 class="text-white font-black tracking-wider uppercase mt-2 text-2xl md:text-4xl">Trainers &amp; Doctors</h1>
    <p class="text-gray-300 max-w-2xl mt-3 leading-relaxed text-xs md:text-sm">Work 1-on-1 with elite coaches and medical experts to safely hit your training goals.</p>
  </div>
</div>

<!-- Solid Dark Background Content Area -->
<div class="-mt-16 relative z-10 max-w-7xl mx-auto px-6 pb-24 text-gray-900 dark:text-white">
  
  <!-- Cards Grid -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
    <?php foreach ($trainers as $t): ?>
    <div class="rv bg-white dark:bg-[#121212] rounded-2xl overflow-hidden border border-gray-200 dark:border-white/10 flex flex-col hover:border-brand-accent transition-all shadow-xl">
      <img src="<?= e(!empty($t['photo']) ? $t['photo'] : 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=500&q=60&auto=format&fit=crop') ?>" alt="<?= e($t['name'] ?? 'Trainer') ?>" class="w-full h-48 object-cover" loading="lazy">
      
      <div class="p-5 flex flex-col gap-2 flex-1 justify-between">
        <div>
          <h3 class="text-lg font-bold text-gray-900 dark:text-white leading-tight tracking-tight"><?= e($t['name'] ?? 'Trainer') ?></h3>
          <p class="text-xs text-[#38BDF8] font-semibold uppercase tracking-wider"><?= e($t['specialty'] ?? 'Expert Coach') ?></p>
          <div class="text-xs text-amber-400 font-semibold mt-1">★ ★ ★ ★ ★ <?= e((string)($t['rating'] ?? '5.0')) ?></div>
        </div>
        
        <div class="border-t border-gray-200 dark:border-white/5 pt-4 mt-2">
          <?php if (($u ?? null) && in_array($u['role'] ?? '', ['member', 'patient'], true)): ?>
            <form method="post" action="<?= url('api/book-appointment.php') ?>" class="space-y-3">
              <?= csrf_field() ?>
              <input type="hidden" name="trainer_id" value="<?= (int)($t['uid'] ?? 0) ?>">
              <div class="space-y-1">
                <label class="text-[10px] text-gray-600 dark:text-gray-400 uppercase tracking-wider font-bold">Your Goal</label>
                <input type="text" name="goal" placeholder="Muscle gain" class="w-full bg-gray-100 dark:bg-[#181818] border border-gray-200 dark:border-white/10 rounded-lg px-3 py-1.5 text-xs text-gray-900 dark:text-white focus:outline-none focus:border-brand-accent" required>
              </div>
              <div class="space-y-1">
                <label class="text-[10px] text-gray-600 dark:text-gray-400 uppercase tracking-wider font-bold">Date & Time</label>
                <input type="datetime-local" name="appt_date" class="w-full bg-gray-100 dark:bg-[#181818] border border-gray-200 dark:border-white/10 rounded-lg px-3 py-1.5 text-xs text-gray-900 dark:text-white focus:outline-none focus:border-brand-accent" required>
              </div>
              <button class="w-full text-center block px-4 py-2 bg-gradient-to-r from-[#FF3D00] to-[#FF6B1A] hover:from-[#FF6B1A] hover:to-[#FFB800] text-white font-extrabold rounded-lg shadow-md transition-all active:scale-[0.98] text-xs uppercase tracking-wider mt-2" type="submit">Book Session →</button>
            </form>
          <?php else: ?>
            <a class="w-full text-center block px-4 py-2.5 bg-gray-100 hover:bg-gray-200 dark:bg-white/5 dark:hover:bg-white/10 text-gray-900 dark:text-white font-extrabold rounded-lg border border-gray-200 dark:border-white/10 hover:border-white/20 transition-all active:scale-[0.98] text-xs uppercase tracking-wider no-underline" href="<?= url('auth/login.php?next=' . urlencode('pages/trainers.php')) ?>">Book Session (Login)</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
