<?php
require_once dirname(__DIR__) . '/config/config.php';
$pageTitle = 'Feedback';
$u = current_user();
include dirname(__DIR__) . '/includes/header.php';
?>
<!-- Cinematic Hero Section -->
<div class="relative w-full h-[35vh] min-h-[300px] bg-[url('https://images.unsplash.com/photo-1552664730-d307ca884978?w=1600&q=80&auto=format&fit=crop')] bg-cover bg-center flex items-center justify-center text-center">
  <div class="absolute inset-0 bg-black/70 z-0"></div>
  <div class="relative z-10 px-6 max-w-4xl mx-auto w-full">
    <span class="eyebrow text-[#38BDF8] font-bold tracking-widest uppercase text-xs md:text-sm block">We're Listening</span>
    <h1 class="text-white text-3xl md:text-5xl font-black tracking-wider uppercase font-['Russo_One',sans-serif]">Share Your Feedback</h1>
    <p class="mt-2 text-xs md:text-sm text-gray-300 max-w-xl mx-auto">Ideas, bugs, suggestions — tell us how to make Core Calorie Advisor better. Every message reaches the development team.</p>
  </div>
</div>

<div class="-mt-16 relative z-10 max-w-4xl mx-auto px-6 pb-24 text-gray-900 dark:text-white">
  <div class="w-full max-w-3xl mx-auto mt-12 bg-white dark:bg-[#121212] p-8 rounded-2xl shadow-xl border border-gray-200 dark:border-white/10 text-gray-900 dark:text-white">
    <form id="fbForm">
      <?= csrf_field() ?>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="field flex flex-col">
          <label class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-2">Name</label>
          <input type="text" name="name" value="<?= e($u['name'] ?? '') ?>" class="w-full p-3.5 bg-gray-100 dark:bg-[#0A0A0A] border border-gray-200 dark:border-white/10 text-gray-900 dark:text-white rounded-xl outline-none focus:ring-2 focus:ring-brand-accent" placeholder="Your name">
        </div>
        <div class="field flex flex-col">
          <label class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-2">Email</label>
          <input type="email" name="email" value="<?= e($u['email'] ?? '') ?>" class="w-full p-3.5 bg-gray-100 dark:bg-[#0A0A0A] border border-gray-200 dark:border-white/10 text-gray-900 dark:text-white rounded-xl outline-none focus:ring-2 focus:ring-brand-accent" placeholder="you@email.com">
        </div>
      </div>
      <div class="field flex flex-col mb-6">
        <label class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-2">Your Feedback</label>
        <textarea rows="5" name="message" class="w-full h-32 p-4 bg-gray-100 dark:bg-[#0A0A0A] border border-gray-200 dark:border-white/10 text-gray-900 dark:text-white rounded-xl outline-none focus:ring-2 focus:ring-brand-accent resize-none" placeholder="What's on your mind?" required></textarea>
      </div>
      <button class="w-full py-4 bg-brand-accent text-gray-950 font-black tracking-widest uppercase rounded-xl hover:bg-[#7dd3fc] transition-all shadow-lg shadow-brand-accent/20 active:scale-[0.99]" type="submit">Send Feedback →</button>
    </form>
    <div id="fbDone" style="display:none;text-align:center;padding:20px 0">
      <div class="w-16 h-16 rounded-full bg-emerald-500/10 flex items-center justify-center text-emerald-500 text-3xl mx-auto mb-4">✓</div>
      <h3 class="text-xl font-bold text-gray-900 dark:text-white" style="margin:8px 0">Thank you!</h3>
      <p class="text-gray-600 dark:text-gray-400 text-sm max-w-md mx-auto">Your feedback has been forged into our roadmap.</p>
    </div>
  </div>
</div>
<?php
$extraScripts = '<script src="' . asset('js/forms.js') . '"></script>';
include dirname(__DIR__) . '/includes/footer.php';
?>
