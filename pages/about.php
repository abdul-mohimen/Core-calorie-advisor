<?php
require_once dirname(__DIR__) . '/config/config.php';
$pageTitle = 'About Core Calorie Advisor';
include dirname(__DIR__) . '/includes/header.php';
?>

<section class="cca-hero cca-hero--particles">
  <canvas id="heroParticles"></canvas>
  <div class="cca-hero__overlay"></div>
  <div class="cca-hero__content">
    <div class="cca-hero__text">
      <div class="cca-hero__breadcrumb"><a href="<?= url('index.php') ?>">Home</a> <span class="sep">›</span> About Us</div>
      <div class="cca-hero__badge cca-hero__badge--pro"><span class="dot"></span> Next-Gen Health</div>
      <h1 class="cca-hero__title">Pioneering <span class="grad">Precision Fitness</span> & Medicine</h1>
      <p class="cca-hero__subtitle">Core Calorie Advisor bridges the gap between biomechanical 3D training, clinical nutrition, and doctor-supervised wellness.</p>
    </div>
    <div class="cca-hero__glass">
      <div class="cca-hero__glass-title">Platform Standards</div>
      <div class="cca-hero__kpi-row"><span class="cca-hero__kpi-icon c1">🧬</span><div><div class="cca-hero__kpi-val">100%</div><div class="cca-hero__kpi-lbl">Doctor Verified</div></div></div>
      <div class="cca-hero__kpi-row"><span class="cca-hero__kpi-icon c2">🏋️</span><div><div class="cca-hero__kpi-val">3D Studio</div><div class="cca-hero__kpi-lbl">Biomechanical Rigging</div></div></div>
      <div class="cca-hero__kpi-row"><span class="cca-hero__kpi-icon c3">🔒</span><div><div class="cca-hero__kpi-val">HIPAA</div><div class="cca-hero__kpi-lbl">Medical Vault Security</div></div></div>
    </div>
  </div>
</section>

<div class="cca-page-container">
  <!-- Mission Statement -->
  <div class="cca-card" style="margin-bottom:32px; padding:36px">
    <h2 class="cca-h2" style="margin-bottom:16px">🎯 Our Mission</h2>
    <p style="font-size:16px; color:var(--text-2); line-height:1.8; max-width:900px">
      Core Calorie Advisor was built on a singular imperative: eliminate guesswork in fitness and medical wellness. By combining WebGL 3D anatomical modeling, real-time computer vision scanners, and a direct marketplace connecting members with board-certified physicians and elite trainers, we empower millions to achieve optimal physical performance safely.
    </p>
  </div>

  <!-- Medical Board & Trainer Certification Showcase -->
  <h2 class="cca-h2" style="margin-bottom:20px">🩺 Medical Board & Lead Advisory</h2>
  <div class="cca-grid-3" style="margin-bottom:40px">
    <div class="cca-card" style="padding:24px; text-align:center">
      <img src="<?= asset('img/avatars/doctor-1.jpg') ?>" alt="Dr. Sarah Jenkins" onerror="this.src='https://images.unsplash.com/photo-1559839734-2b71ea197ec2?w=300'" style="width:100px; height:100px; border-radius:50%; object-fit:cover; margin:0 auto 16px; border:3px solid var(--info); box-shadow:0 0 20px color-mix(in srgb, var(--info) 30%, transparent)">
      <h3 style="font-size:18px; margin-bottom:4px">Dr. Sarah Jenkins, MD</h3>
      <p style="font-size:13px; color:var(--info-text); font-weight:600; margin-bottom:12px">Chief Medical Officer · Endocrinology</p>
      <p style="font-size:13px; color:var(--text-3); line-height:1.6">Harvard Medical School alumnus specializing in metabolic syndrome, diabetic nutrition protocols, and hormone optimization.</p>
    </div>

    <div class="cca-card" style="padding:24px; text-align:center">
      <img src="<?= asset('img/avatars/trainer-1.jpg') ?>" alt="Marcus Vance" onerror="this.src='https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=300'" style="width:100px; height:100px; border-radius:50%; object-fit:cover; margin:0 auto 16px; border:3px solid var(--success); box-shadow:0 0 20px color-mix(in srgb, var(--success) 30%, transparent)">
      <h3 style="font-size:18px; margin-bottom:4px">Marcus Vance, CSCS</h3>
      <p style="font-size:13px; color:var(--success-text); font-weight:600; margin-bottom:12px">Head of Biomechanics & 3D Rigging</p>
      <p style="font-size:13px; color:var(--text-3); line-height:1.6">Master Strength & Conditioning Specialist with 15+ years training Olympic athletes and calibrating 3D motion capture animation.</p>
    </div>

    <div class="cca-card" style="padding:24px; text-align:center">
      <img src="<?= asset('img/avatars/doctor-2.jpg') ?>" alt="Dr. Aris Thorne" onerror="this.src='https://images.unsplash.com/photo-1622253692010-333f2da6031d?w=300'" style="width:100px; height:100px; border-radius:50%; object-fit:cover; margin:0 auto 16px; border:3px solid var(--accent-violet); box-shadow:0 0 20px color-mix(in srgb, var(--accent-violet) 30%, transparent)">
      <h3 style="font-size:18px; margin-bottom:4px">Dr. Aris Thorne, DPT</h3>
      <p style="font-size:13px; color:var(--accent-violet-text); font-weight:600; margin-bottom:12px">Director of Physical Therapy</p>
      <p style="font-size:13px; color:var(--text-3); line-height:1.6">Doctor of Physical Therapy focused on joint rehabilitation, movement screening, and injury prevention protocols.</p>
    </div>
  </div>

  <!-- Key Pillars -->
  <div class="cca-grid-4" style="margin-bottom:32px">
    <div class="cca-card">
      <div style="font-size:32px; margin-bottom:12px">🧬</div>
      <h4 style="font-size:16px; margin-bottom:8px">Clinical Safety</h4>
      <p style="font-size:13px; color:var(--text-3); line-height:1.5">Disease-specific workout filters curated and verified by doctors for hypertension, diabetes, and joint conditions.</p>
    </div>
    <div class="cca-card">
      <div style="font-size:32px; margin-bottom:12px">🎮</div>
      <h4 style="font-size:16px; margin-bottom:8px">3D Interactive Engine</h4>
      <p style="font-size:13px; color:var(--text-3); line-height:1.5">Real-time Three.js 3D avatar that demonstrates exact anatomical exercise form from any 360° camera angle.</p>
    </div>
    <div class="cca-card">
      <div style="font-size:32px; margin-bottom:12px">📷</div>
      <h4 style="font-size:16px; margin-bottom:8px">AI Scanners</h4>
      <p style="font-size:13px; color:var(--text-3); line-height:1.5">Instant computer vision food recognition and body composition analysis for automated macro tracking.</p>
    </div>
    <div class="cca-card">
      <div style="font-size:32px; margin-bottom:12px">💎</div>
      <h4 style="font-size:16px; margin-bottom:8px">Monetization Ecosystem</h4>
      <p style="font-size:13px; color:var(--text-3); line-height:1.5">Integrated Stripe checkout with automated commission splits, wallet balances, and verified provider rankings.</p>
    </div>
  </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
