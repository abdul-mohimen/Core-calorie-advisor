<?php
require_once dirname(__DIR__) . '/config/config.php';
$pageTitle = 'Privacy Policy';
include dirname(__DIR__) . '/includes/header.php';
?>
<!-- Cinematic Hero Section -->
<div class="relative w-full h-[35vh] min-h-[300px] bg-[url('https://images.unsplash.com/photo-1507398941214-572c25f4b1dc?w=1600&q=80&auto=format&fit=crop')] bg-cover bg-center flex items-center justify-center text-center tf-hero-anim">
  <div class="absolute inset-0 bg-black/70 z-0"></div>
  <div class="relative z-10 px-6 max-w-4xl mx-auto w-full">
    <span class="eyebrow text-[#38BDF8] font-bold tracking-widest uppercase text-xs md:text-sm block">Your Trust · Our Duty</span>
    <h1 class="text-white text-3xl md:text-5xl font-black tracking-wider uppercase font-['Russo_One',sans-serif]">Privacy Policy</h1>
    <p class="mt-2 text-xs md:text-sm text-gray-300 max-w-xl mx-auto">Last updated: <?= date('F j, Y') ?></p>
  </div>
</div>

<div class="-mt-16 relative z-10 max-w-4xl mx-auto px-6 pb-24 text-gray-900 dark:text-white">
  <div class="bg-white dark:bg-[#121212] border border-gray-200 dark:border-white/10 rounded-2xl p-8 shadow-xl text-left">
      <div class="legal-body text-gray-700 dark:text-gray-300">
        <p class="mb-4">At <b>Core Calorie Advisor</b> ("we", "us", "our"), your privacy is forged into everything we build. This policy explains what we collect, why we collect it, and the control you keep over your data when you use our platform, portals, and AI tools.</p>

        <h3 class="text-lg font-bold text-gray-900 dark:text-white mt-6 mb-2">1. Information We Collect</h3>
        <ul class="list-disc pl-5 space-y-2 mb-4">
          <li><b>Account data</b> — your name, email, role (member, trainer, doctor, patient, admin) and subscription plan, created when you register.</li>
          <li><b>Fitness &amp; health data</b> — workout logs, calories burned, food logs, body-scan estimates, and any health condition you or your doctor record for disease-safe planning.</li>
          <li><b>AI Scanner uploads</b> — images are processed in memory to generate a report and are not retained by this application. If you connect an external vision provider, its own data terms also apply.</li>
          <li><b>Technical data</b> — session cookies required to keep you logged in and to protect forms against CSRF attacks.</li>
        </ul>

        <h3 class="text-lg font-bold text-gray-900 dark:text-white mt-6 mb-2">2. How We Use Your Data</h3>
        <ul class="list-disc pl-5 space-y-2 mb-4">
          <li>To power your dashboard, workout player, calculators, and progress tracking.</li>
          <li>To generate fitness guidance, estimate food nutrition, and recommend relevant trainers or programs. Scanner output is not a diagnosis and never replaces measured health data.</li>
          <li>To connect members with trainers and patients with doctors through the appointment system.</li>
          <li>To secure your account — passwords are stored using one-way <b>bcrypt</b> hashing and are never readable by staff.</li>
        </ul>

        <h3 class="text-lg font-bold text-gray-900 dark:text-white mt-6 mb-2">3. Health Data &amp; AI Disclaimer</h3>
        <p class="mb-4">Core Calorie Advisor AI scanners provide fitness estimates for informational and motivational purposes only. They are <b>not a medical diagnosis</b>. When a scan flags a possible health concern, we recommend consulting a qualified professional through the Doctor Portal or your own physician. Doctor-approved disease-safe plans are guidance, not a substitute for in-person medical care.</p>

        <h3 class="text-lg font-bold text-gray-900 dark:text-white mt-6 mb-2">4. Data Sharing</h3>
        <p class="mb-4">We do not sell your data. Health and fitness information is shared only with the trainers or doctors you actively book or consult, and only to the extent needed to deliver that service. We may disclose data if required by law.</p>

        <h3 class="text-lg font-bold text-gray-900 dark:text-white mt-6 mb-2">5. Data Security</h3>
        <p class="mb-4">We apply prepared-statement database access, CSRF tokens on every state-changing form, MIME-validated uploads, role-based access control, login throttling, and HttpOnly/SameSite session cookies. No system is perfectly secure, but we work continuously to protect your data and privacy.</p>

        <h3 class="text-lg font-bold text-gray-900 dark:text-white mt-6 mb-2">6. Your Rights</h3>
        <ul class="list-disc pl-5 space-y-2 mb-4">
          <li>Access, update, or delete your account data at any time from your portal.</li>
          <li>Request a full export or erasure of your data by contacting us.</li>
          <li>Withdraw consent for AI processing by discontinuing use of the scanners.</li>
        </ul>

        <h3 class="text-lg font-bold text-gray-900 dark:text-white mt-6 mb-2">7. Contact</h3>
        <p class="mb-4">Questions about this policy? Reach the Core Calorie Advisor team at <b>privacy@corecalorieadvisor.com</b>.</p>
      </div>
      
      <div class="mt-6 text-center">
        <a class="btn btn-ghost btn-sm" href="<?= url('index.php') ?>">← Back to Home</a>
      </div>
    </div>
</div>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
