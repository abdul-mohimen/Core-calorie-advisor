<?php
require_once dirname(__DIR__) . '/config/config.php';
$pageTitle = 'FAQ';

$faqs = [
    ['Is Core Calorie Advisor free to use?', 'Yes — the Recruit (Free) plan gives you free workout programs, the 3D workout player and all calculators. AI Body & Food scanners and PRO workouts unlock with CCA Pro ($9.99/mo), and doctor consults with CCA Elite.'],
    ['How do the AI Body & Food scanners work?', 'They are Member-Portal Pro tools. The Body Scanner uses your live camera (Google-Lens style) to estimate weight, body-fat %, BMI and muscle mass, then recommends a plan, a trainer, and flags a doctor consult if it detects a health anomaly. The Food Scanner reads calories and macros from a meal photo and lets you add it to your daily log.'],
    ['Are the 3D workouts accurate?', 'Every exercise in the player is animated by a live 3D athlete that transitions to the exact movement — push-ups, squats, yoga, dumbbell curls and more — with Fitify-style rest timers between blocks so it feels like a real coached session.'],
    ['How do trainer and doctor bookings work?', 'Members and patients book a session from the Trainers page. Trainers accept or reject from their portal. Doctors approve disease-safe plans for patients with conditions like knee pain, heart issues or diabetes.'],
    ['What is the Warriors\' Circle community?', 'A private feed for logged-in members, trainers, doctors and patients to share wins, tips and climb the calorie leaderboard. Every post is stored securely and tied to your account.'],
    ['How do I report a problem or a bad actor?', 'Inside your portal, open “Report Issue”. Your report goes straight to the Admin, who can review it and, if needed, issue an official warning to the trainer or doctor involved — they are notified instantly via their bell.'],
    ['Is my health data safe?', 'Yes. Passwords use bcrypt, every form is CSRF-protected, uploads are MIME-validated, and database access uses prepared statements. Health data is only shared with the trainer or doctor you actively consult. See our Privacy Policy for details.'],
    ['Can I cancel my subscription anytime?', 'Absolutely. Plans are month-to-month and you can downgrade to Free whenever you like — you keep your logs and history.'],
];

include dirname(__DIR__) . '/includes/header.php';
?>
<!-- Cinematic Hero Section -->
<div class="relative w-full h-[35vh] min-h-[300px] bg-[url('https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=1600&q=80&auto=format&fit=crop')] bg-cover bg-center flex items-center justify-center text-center">
  <div class="absolute inset-0 bg-black/70 z-0"></div>
  <div class="relative z-10 px-6 max-w-4xl mx-auto w-full">
    <span class="text-xs font-bold text-[#F59E0B] tracking-widest uppercase block mb-2">Need Answers?</span>
    <h1 class="text-white text-3xl md:text-5xl font-black tracking-wider uppercase font-['Russo_One',sans-serif]">Frequently Asked Questions</h1>
    <p class="mt-2 text-xs md:text-sm text-gray-300 max-w-xl mx-auto">Everything you need to know about training, AI tools, portals and safety on Core Calorie Advisor.</p>
  </div>
</div>

<div class="-mt-16 relative z-10 max-w-4xl mx-auto px-6 pb-24 text-gray-900 dark:text-white">
  <div class="faq-list">
    <?php foreach ($faqs as $i => [$q, $a]): ?>
    <div class="faq-item<?= $i === 0 ? ' open' : '' ?>">
      <button class="faq-q" type="button"><?= e($q) ?> <span class="faq-ico">+</span></button>
      <div class="faq-a"><p style="padding-top:2px"><?= e($a) ?></p></div>
    </div>
    <?php endforeach; ?>
  </div>

  <div style="margin-top:30px;color:var(--muted)">Still stuck? <a href="<?= url('pages/feedback.php') ?>" style="color:var(--molten)">Send us feedback →</a></div>
</div>
<?php
$extraScripts = '<script src="' . asset('js/faq.js') . '"></script>';
include dirname(__DIR__) . '/includes/footer.php';
?>
