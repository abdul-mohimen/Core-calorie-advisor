<?php
require_once dirname(__DIR__) . '/config/config.php';
$pageTitle = 'Terms & Conditions';
include dirname(__DIR__) . '/includes/header.php';
?>
<!-- Cinematic Hero Section -->
<div class="relative w-full h-[35vh] min-h-[300px] bg-[url('https://images.unsplash.com/photo-1447452001602-7090c7ab2db3?w=1600&q=80&auto=format&fit=crop')] bg-cover bg-center flex items-center justify-center text-center">
  <div class="absolute inset-0 bg-black/70 z-0"></div>
  <div class="relative z-10 px-6 max-w-4xl mx-auto w-full">
    <span class="eyebrow text-[#38BDF8] font-bold tracking-widest uppercase text-xs md:text-sm block">The Warrior's Code</span>
    <h1 class="text-white text-3xl md:text-5xl font-black tracking-wider uppercase font-['Russo_One',sans-serif]">Terms &amp; Conditions</h1>
    <p class="mt-2 text-xs md:text-sm text-gray-300 max-w-xl mx-auto">Last updated: <?= date('F j, Y') ?></p>
  </div>
</div>

<div class="-mt-16 relative z-10 max-w-4xl mx-auto px-6 pb-24 text-gray-900 dark:text-white">
  <div class="bg-white dark:bg-[#121212] border border-gray-200 dark:border-white/10 rounded-2xl p-8 shadow-xl text-left">
      <div class="legal-body text-gray-700 dark:text-gray-300">
        <p class="mb-4">Welcome to <b>Core Calorie Advisor</b>. By creating an account or using our platform, portals, and AI tools, you agree to these Terms. Please read them — they keep our platform fair for every member.</p>

        <h3 class="text-lg font-bold text-gray-900 dark:text-white mt-6 mb-2">1. Eligibility &amp; Accounts</h3>
        <ul class="list-disc pl-5 space-y-2 mb-4">
          <li>You must be at least 16 years old to create an account.</li>
          <li>You are responsible for keeping your login credentials secure and for all activity under your account.</li>
          <li>Provide accurate information. Roles (member, trainer, doctor, patient, admin) grant different access and responsibilities.</li>
        </ul>

        <h3 class="text-lg font-bold text-gray-900 dark:text-white mt-6 mb-2">2. Subscriptions &amp; Payments</h3>
        <ul class="list-disc pl-5 space-y-2 mb-4">
          <li>Free (Recruit), CCA Pro, and CCA Elite plans unlock different features, including the AI Scanners on Pro and above.</li>
          <li>Paid plans renew on a recurring basis until cancelled. Plan access is activated only after the payment provider confirms the subscription.</li>
          <li>Feature availability may change as the platform evolves.</li>
        </ul>

        <h3 class="text-lg font-bold text-gray-900 dark:text-white mt-6 mb-2">3. Health &amp; Fitness Disclaimer</h3>
        <p class="mb-4">Core Calorie Advisor provides fitness content, AI estimates, and training programs for general informational purposes. <b>Consult a physician before starting any exercise or nutrition program</b>, especially if you have a medical condition. AI scan results are estimates, not medical advice. You train at your own risk and are responsible for exercising within your limits.</p>

        <h3 class="text-lg font-bold text-gray-900 dark:text-white mt-6 mb-2">4. Trainers, Doctors &amp; Consultations</h3>
        <p class="mb-4">Trainers and doctors on the platform are independent professionals. Core Calorie Advisor facilitates bookings but does not guarantee outcomes and is not liable for advice given during private sessions. Doctor-approved plans are guidance and do not replace in-person care.</p>

        <h3 class="text-lg font-bold text-gray-900 dark:text-white mt-6 mb-2">5. Acceptable Use</h3>
        <ul class="list-disc pl-5 space-y-2 mb-4">
          <li>Do not upload unlawful, harmful, or infringing content to the scanners or profiles.</li>
          <li>Do not attempt to breach security, access other users' data, or disrupt the service.</li>
          <li>Do not misuse another user's role or impersonate staff.</li>
        </ul>

        <h3 class="text-lg font-bold text-gray-900 dark:text-white mt-6 mb-2">6. Intellectual Property</h3>
        <p class="mb-4">The Core Calorie Advisor name, logo, design system, 3D content, and software are our property. You may not copy, resell, or redistribute them without permission. Content you upload remains yours; you grant us a limited licence to process it to deliver the service.</p>

        <h3 class="text-lg font-bold text-gray-900 dark:text-white mt-6 mb-2">7. Limitation of Liability</h3>
        <p class="mb-4">Core Calorie Advisor is provided "as is". To the fullest extent permitted by law, we are not liable for injury, loss, or damages arising from use of the platform, training programs, or AI recommendations.</p>

        <h3 class="text-lg font-bold text-gray-900 dark:text-white mt-6 mb-2">8. Changes &amp; Termination</h3>
        <p class="mb-4">We may update these Terms or suspend accounts that violate them. Continued use after changes means you accept the revised Terms.</p>

        <h3 class="text-lg font-bold text-gray-900 dark:text-white mt-6 mb-2">9. Contact</h3>
        <p class="mb-4">Questions? Contact the Core Calorie Advisor team at <b>support@corecalorieadvisor.com</b>.</p>
      </div>

      <div class="mt-6 text-center">
        <a class="btn btn-ghost btn-sm" href="<?= url('index.php') ?>">← Back to Home</a>
      </div>    </div>
</div>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
