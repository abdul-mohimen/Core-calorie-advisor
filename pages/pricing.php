<?php
require_once dirname(__DIR__) . '/config/config.php';
$pageTitle = 'Pricing';
/* A plan can only be activated by a verified billing flow (Stripe webhook, or the
   clearly-labeled sandbox checkout when Stripe is not configured). Never accept a
   bare browser form value on THIS page as proof of payment. */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    flash('warn', 'Choose a plan to continue to secure checkout.');
    redirect('pages/pricing.php');
}
$plan = user_plan();
include dirname(__DIR__) . '/includes/header.php';

function plan_btn(string $p, string $label, bool $featured = false): void {
    global $plan;
    $currentPlan = $plan ?? 'free';
    if (is_logged_in() && $currentPlan === $p) {
        echo '<button class="w-full text-center block px-4 py-3 bg-black/5 dark:bg-white/5 border border-black/10 dark:border-white/10 rounded-xl text-gray-400 dark:text-gray-500 text-sm font-extrabold uppercase tracking-wider mt-auto cursor-not-allowed" disabled>✔ Current Plan</button>';
        return;
    }
    if ($p === 'free') {
        $href = is_logged_in() ? url('pages/workouts.php') : url('auth/register.php');
        echo '<a class="w-full text-center block px-4 py-3 bg-black/5 dark:bg-white/10 hover:bg-black/10 dark:hover:bg-white/15 border border-black/10 dark:border-white/10 text-gray-900 dark:text-white font-extrabold rounded-xl transition-all active:scale-[0.98] text-sm uppercase tracking-wider mt-auto" href="' . e($href) . '">' . e($label) . '</a>';
        return;
    }
    // A plan selection always goes to the protected checkout route.
    $btnClass = 'w-full text-center block px-4 py-3 text-white font-extrabold rounded-xl shadow-lg transition-all active:scale-[0.98] text-sm uppercase tracking-wider mt-auto';
    if ($featured) {
        $btnClass .= ' bg-gradient-to-r from-[#FF6B1A] to-[#FF3D00] hover:from-[#FF8833] hover:to-[#FF6B1A] shadow-[#FF6B1A]/30';
    } else {
        $btnClass .= ' bg-gray-900 dark:bg-white/10 hover:bg-gray-800 dark:hover:bg-white/15 border border-transparent dark:border-white/10';
    }
    echo '<a class="' . $btnClass . '" href="' . e(url('pages/checkout.php?plan=' . rawurlencode($p))) . '">' . e($label) . '</a>';
}
?>
<style>
  .page-hero::before, .page-hero::after { display: none !important; }
  /* Card entrance + hover lift (works in light & dark) */
  @keyframes planRise { from { opacity: 0; transform: translateY(26px); } to { opacity: 1; transform: none; } }
  .plan-card { animation: planRise .6s cubic-bezier(.22,.8,.3,1) both; transition: transform .35s cubic-bezier(.22,.8,.3,1), box-shadow .35s, border-color .35s; }
  .plan-card:nth-child(2) { animation-delay: .1s; }
  .plan-card:nth-child(3) { animation-delay: .2s; }
  .plan-card:hover { transform: translateY(-6px); }
</style>

<!-- Hero -->
<div class="relative h-[32vh] min-h-[280px] w-full bg-[url('https://images.unsplash.com/photo-1541534741688-6078c6bfb5c5?w=1600&q=80&auto=format&fit=crop')] bg-cover bg-center bg-no-repeat flex items-center tf-hero-anim">
  <div class="absolute inset-0 bg-black/80 z-0"></div>
  <div class="relative z-10 max-w-7xl mx-auto px-6 w-full text-center">
    <span class="text-[#FFB800] font-bold tracking-[0.25em] uppercase text-xs md:text-sm block">Choose Your Power</span>
    <h1 class="text-white font-black tracking-wider uppercase mt-2 text-3xl md:text-5xl">Subscription Plans</h1>
    <p class="text-gray-300 max-w-2xl mx-auto mt-3 leading-relaxed text-xs md:text-sm">Find the plan that matches your training frequency and intensity. Cancel anytime.</p>
  </div>
</div>

<div class="relative z-10 max-w-7xl mx-auto px-6 pt-16 pb-10 text-gray-900 dark:text-white">

    <!-- Cards Grid — pt-16 leaves room for the floating "Most Popular" badge -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 items-stretch">

      <!-- Free Plan Card -->
      <div class="plan-card bg-white dark:bg-[#121212] border border-gray-200 dark:border-white/10 rounded-2xl hover:border-[#FF6B1A]/60 hover:shadow-[0_18px_40px_-18px_rgba(0,0,0,0.35)] p-8 flex flex-col justify-between h-full relative">
        <div>
          <span class="text-xs font-bold text-gray-500 uppercase tracking-widest block mb-2">Recruit</span>
          <div class="text-gray-950 dark:text-white font-black text-4xl mb-4 cca-font-disp tracking-wide">FREE</div>
          <p class="text-xs text-gray-500 dark:text-gray-400 mb-6">Start your fitness journey</p>
          <ul class="space-y-3 text-sm text-gray-700 dark:text-gray-300 mb-8">
            <li class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✔</span> Free workout programs</li>
            <li class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✔</span> BMI / BMR calculators</li>
            <li class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✔</span> Community access</li>
            <li class="flex items-center gap-2 opacity-40"><span class="text-red-500 font-bold">✕</span> AI Body Scanner</li>
            <li class="flex items-center gap-2 opacity-40"><span class="text-red-500 font-bold">✕</span> AI Food Scanner</li>
            <li class="flex items-center gap-2 opacity-40"><span class="text-red-500 font-bold">✕</span> PRO workouts</li>
          </ul>
        </div>
        <?php plan_btn('free', 'Join Free'); ?>
      </div>

      <!-- Pro Plan Card (featured) -->
      <div class="plan-card bg-white dark:bg-[#121212] border-2 border-[#FF6B1A]/70 rounded-2xl hover:border-[#FF6B1A] p-8 flex flex-col justify-between h-full relative shadow-[0_0_30px_rgba(255,107,26,0.18)]">
        <span class="absolute -top-4 left-1/2 -translate-x-1/2 px-5 py-1.5 bg-gradient-to-r from-[#FF6B1A] to-[#FF3D00] text-white text-xs font-black uppercase tracking-wider rounded-full z-10 shadow-lg shadow-[#FF6B1A]/40 whitespace-nowrap">★ Most Popular</span>
        <div>
          <span class="text-xs font-bold text-[#FF6B1A] uppercase tracking-widest block mb-2">Card MemPro (CCA Pro)</span>
          <div class="text-gray-950 dark:text-white font-black text-4xl mb-4 cca-font-disp tracking-wide">$9<span class="text-xs text-gray-500 font-medium">.99/mo</span></div>
          <p class="text-xs text-gray-500 dark:text-gray-400 mb-6">Full arsenal unlocked</p>
          <ul class="space-y-3 text-sm text-gray-700 dark:text-gray-300 mb-8">
            <li class="flex items-center gap-2"><span class="text-[#FF6B1A] font-bold">✔</span> Everything in Recruit</li>
            <li class="flex items-center gap-2"><span class="text-[#FF6B1A] font-bold">✔</span> AI Body Scanner (unlimited)</li>
            <li class="flex items-center gap-2"><span class="text-[#FF6B1A] font-bold">✔</span> AI Food Scanner</li>
            <li class="flex items-center gap-2"><span class="text-[#FF6B1A] font-bold">✔</span> All PRO workouts</li>
            <li class="flex items-center gap-2"><span class="text-[#FF6B1A] font-bold">✔</span> Trainer appointments</li>
            <li class="flex items-center gap-2"><span class="text-[#FF6B1A] font-bold">✔</span> Progress reports</li>
          </ul>
        </div>
        <?php plan_btn('pro', 'Go Pro 💎', true); ?>
      </div>

      <!-- Elite Plan Card -->
      <div class="plan-card bg-white dark:bg-[#121212] border border-gray-200 dark:border-white/10 rounded-2xl hover:border-[#FFB800]/60 hover:shadow-[0_18px_40px_-18px_rgba(0,0,0,0.35)] p-8 flex flex-col justify-between h-full relative">
        <div>
          <span class="text-xs font-bold text-[#FFB800] uppercase tracking-widest block mb-2">CCA Elite</span>
          <div class="text-gray-950 dark:text-white font-black text-4xl mb-4 cca-font-disp tracking-wide">$19<span class="text-xs text-gray-500 font-medium">.99/mo</span></div>
          <p class="text-xs text-gray-500 dark:text-gray-400 mb-6">For serious warriors</p>
          <ul class="space-y-3 text-sm text-gray-700 dark:text-gray-300 mb-8">
            <li class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✔</span> Everything in Pro</li>
            <li class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✔</span> Personal doctor consults</li>
            <li class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✔</span> Disease-safe custom plans</li>
            <li class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✔</span> 1-on-1 video sessions</li>
            <li class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✔</span> Custom meal plans</li>
            <li class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✔</span> Priority support</li>
          </ul>
        </div>
        <?php plan_btn('elite', 'Go Elite ⚡'); ?>
      </div>
    </div>

    <!-- Trust strip -->
    <div class="mt-14 grid grid-cols-1 sm:grid-cols-3 gap-4 text-center">
      <div class="rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/[0.03] px-5 py-4">
        <div class="text-lg">🔒</div>
        <div class="text-sm font-bold mt-1">Secure payments</div>
        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Card details handled by the payment provider — never stored on Core Calorie Advisor.</div>
      </div>
      <div class="rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/[0.03] px-5 py-4">
        <div class="text-lg">↩️</div>
        <div class="text-sm font-bold mt-1">Cancel anytime</div>
        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">No lock-in. Downgrade or cancel from your dashboard in two clicks.</div>
      </div>
      <div class="rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/[0.03] px-5 py-4">
        <div class="text-lg">⚡</div>
        <div class="text-sm font-bold mt-1">Instant access</div>
        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">PRO features unlock the moment your payment is confirmed.</div>
      </div>
    </div>

    <!-- Billing FAQ -->
    <div class="mt-14 max-w-3xl mx-auto">
      <h2 class="text-center text-2xl font-black uppercase tracking-wider mb-6">Billing FAQ</h2>
      <details class="group border border-gray-200 dark:border-white/10 rounded-xl bg-white dark:bg-white/[0.03] px-5 py-4 mb-3">
        <summary class="cursor-pointer font-bold text-sm flex items-center justify-between">Kya main kabhi bhi cancel kar sakta hoon? <span class="text-[#FF6B1A] group-open:rotate-45 transition-transform text-lg leading-none">+</span></summary>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-3 leading-relaxed">Haan — subscription kisi bhi waqt cancel karein. Aap ka access billing period ke aakhir tak chalta rahega, us ke baad koi charge nahi hoga.</p>
      </details>
      <details class="group border border-gray-200 dark:border-white/10 rounded-xl bg-white dark:bg-white/[0.03] px-5 py-4 mb-3">
        <summary class="cursor-pointer font-bold text-sm flex items-center justify-between">Payment kaise process hoti hai? <span class="text-[#FF6B1A] group-open:rotate-45 transition-transform text-lg leading-none">+</span></summary>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-3 leading-relaxed">Payments secure checkout ke zariye process hoti hain. Core Calorie Advisor kabhi bhi aap ke card numbers store nahi karta — sirf payment provider ki signed confirmation par plan activate hota hai.</p>
      </details>
      <details class="group border border-gray-200 dark:border-white/10 rounded-xl bg-white dark:bg-white/[0.03] px-5 py-4 mb-3">
        <summary class="cursor-pointer font-bold text-sm flex items-center justify-between">Pro aur Elite mein farq kya hai? <span class="text-[#FF6B1A] group-open:rotate-45 transition-transform text-lg leading-none">+</span></summary>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-3 leading-relaxed">Pro mein AI scanners, tamam PRO workouts aur trainer appointments milte hain. Elite mein us ke oopar doctor consults, disease-safe custom plans, 1-on-1 video sessions aur custom meal plans shamil hain.</p>
      </details>
      <details class="group border border-gray-200 dark:border-white/10 rounded-xl bg-white dark:bg-white/[0.03] px-5 py-4">
        <summary class="cursor-pointer font-bold text-sm flex items-center justify-between">Plan upgrade instantly milta hai? <span class="text-[#FF6B1A] group-open:rotate-45 transition-transform text-lg leading-none">+</span></summary>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-3 leading-relaxed">Ji haan — payment confirm hote hi PRO features foran unlock ho jate hain aur aap ko notification mil jati hai.</p>
      </details>
    </div>
</div>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
