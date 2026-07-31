<?php
require_once dirname(__DIR__) . '/config/config.php';
if (!is_logged_in()) { flash('warn', '🔒 Report Issue portals ke andar available hai — pehle login karo!'); redirect('auth/login.php?next=' . urlencode('pages/report-issue.php')); }
$me = current_user();

/* pull doctors + trainers so the reporter can name a target */
$targets = db()->query("SELECT id, name, role FROM users WHERE role IN ('doctor','trainer') ORDER BY role, name")->fetchAll();

$pageTitle = 'Report an Issue';
include dirname(__DIR__) . '/includes/header.php';
?>
<!-- Cinematic Hero Section -->
<div class="relative w-full h-[35vh] min-h-[300px] bg-[url('https://images.unsplash.com/photo-1521737604893-d14cc237f11d?w=1600&q=80&auto=format&fit=crop')] bg-cover bg-center flex items-center justify-center text-center">
  <div class="absolute inset-0 bg-black/70 z-0"></div>
  <div class="relative z-10 px-6 max-w-4xl mx-auto w-full">
    <span class="text-xs font-bold text-[#F59E0B] tracking-widest uppercase block mb-2"><?= e(ucfirst($me['role'])) ?> Portal · Support</span>
    <h1 class="text-white text-3xl md:text-5xl font-black tracking-wider uppercase font-['Russo_One',sans-serif]">Contact &amp; Support</h1>
    <p class="mt-2 text-xs md:text-sm text-gray-300 max-w-xl mx-auto">Something wrong with a trainer, doctor or the platform? Tell the Admin team — serious reports can result in an official warning to the party involved.</p>
  </div>
</div>

<div class="-mt-16 relative z-10 max-w-4xl mx-auto px-6 pb-24">
  <div class="w-full max-w-3xl mx-auto mt-12 bg-white dark:bg-[#121212] p-8 rounded-2xl shadow-xl border border-gray-200 dark:border-white/10 text-gray-900 dark:text-white">
    <form id="issueForm">
      <?= csrf_field() ?>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="field flex flex-col">
          <label class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-2">Concerning</label>
          <select name="target_type" id="issType" class="w-full p-3.5 bg-gray-100 dark:bg-[#0A0A0A] border border-gray-200 dark:border-white/10 text-gray-900 dark:text-white rounded-xl outline-none focus:ring-2 focus:ring-brand-accent">
            <option value="general">General / Platform</option>
            <option value="trainer">A Trainer</option>
            <option value="doctor">A Doctor</option>
            <option value="hospital">A Hospital / Clinic</option>
          </select>
        </div>
        <div class="field flex flex-col">
          <label class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-2">Which person? (optional)</label>
          <select name="target_id" id="issTarget" class="w-full p-3.5 bg-gray-100 dark:bg-[#0A0A0A] border border-gray-200 dark:border-white/10 text-gray-900 dark:text-white rounded-xl outline-none focus:ring-2 focus:ring-brand-accent">
            <option value="">— select —</option>
            <?php foreach ($targets as $t): ?>
              <option value="<?= (int)$t['id'] ?>" data-role="<?= e($t['role']) ?>"><?= e($t['name']) ?> (<?= e(ucfirst($t['role'])) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="field flex flex-col mb-6">
        <label class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-2">Subject</label>
        <input type="text" name="subject" class="w-full p-3.5 bg-gray-100 dark:bg-[#0A0A0A] border border-gray-200 dark:border-white/10 text-gray-900 dark:text-white rounded-xl outline-none focus:ring-2 focus:ring-brand-accent" maxlength="150" placeholder="Short summary" required>
      </div>
      <div class="field flex flex-col mb-6">
        <label class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-2">Details</label>
        <textarea rows="5" name="message" class="w-full h-32 p-4 bg-gray-100 dark:bg-[#0A0A0A] border border-gray-200 dark:border-white/10 text-gray-900 dark:text-white rounded-xl outline-none focus:ring-2 focus:ring-brand-accent resize-none" placeholder="Describe what happened, dates, and any details that help us act." required></textarea>
      </div>
      <button class="w-full py-4 bg-brand-accent text-gray-950 font-black tracking-widest uppercase rounded-xl hover:bg-[#7dd3fc] transition-all shadow-lg shadow-brand-accent/20 active:scale-[0.99]" type="submit">Submit Report →</button>
    </form>
    <div id="issDone" style="display:none;text-align:center;padding:20px 0">
      <div class="w-16 h-16 rounded-full bg-emerald-500/10 flex items-center justify-center text-emerald-500 text-3xl mx-auto mb-4">✓</div>
      <h3 class="text-xl font-bold text-gray-900 dark:text-white" style="margin:8px 0">Report submitted</h3>
      <p class="text-gray-600 dark:text-gray-400 text-sm max-w-md mx-auto">The Admin team has been notified and will review it. Thank you for keeping the forge safe.</p>
    </div>
  </div>
</div>
<?php
$extraScripts = '<script src="' . asset('js/forms.js') . '"></script>';
include dirname(__DIR__) . '/includes/footer.php';
?>
