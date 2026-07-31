<?php
require_once dirname(__DIR__) . '/config/config.php';
$pageTitle = 'Platform Features - Core Calorie Advisor';
include dirname(__DIR__) . '/includes/header.php';
?>

<section class="cca-hero cca-hero--particles">
  <canvas id="heroParticles"></canvas>
  <div class="cca-hero__overlay"></div>
  <div class="cca-hero__content">
    <div class="cca-hero__text">
      <div class="cca-hero__breadcrumb"><a href="<?= url('index.php') ?>">Home</a> <span class="sep">›</span> Features</div>
      <div class="cca-hero__badge cca-hero__badge--pro"><span class="dot"></span> Platform Ecosystem</div>
      <h1 class="cca-hero__title">Next-Gen <span class="grad">Health Tech</span> Features</h1>
      <p class="cca-hero__subtitle">Explore our suite of 3D biomechanical animation engines, AI computer vision scanners, clinical medical vaults, and Stripe monetization pipelines.</p>
    </div>
    <div class="cca-hero__glass" id="featureHero3d" style="min-height:260px; display:flex; align-items:center; justify-content:center">
      <span class="h3d-tag" style="position:absolute; top:12px; left:16px; font-size:11px; opacity:0.7">● 3D Engine Sandbox</span>
    </div>
  </div>
</section>

<div class="cca-page-container">
  <!-- Feature 1: 3D Workout Studio -->
  <div class="cca-card" style="margin-bottom:32px; padding:32px">
    <div class="cca-grid-2" style="align-items:center; gap:32px">
      <div>
        <span class="cca-badge cca-badge-pro" style="margin-bottom:12px">FEATURE #1</span>
        <h2 class="cca-h2" style="margin-bottom:12px">3D Interactive Workout Studio</h2>
        <p style="font-size:15px; color:var(--text-2); line-height:1.7; margin-bottom:16px">
          Unlike static videos or 2D diagrams, our Three.js GLTF WebGL engine renders a full 3D rigged trainer in real time. Rotate 360°, zoom into specific muscle groups, toggle wireframe skeletal mode, and control animation playback speed.
        </p>
        <div style="display:flex; gap:12px; flex-wrap:wrap">
          <span class="cca-badge" style="background:color-mix(in srgb, var(--primary) 10%, transparent); color:var(--primary-text)">360° Camera Control</span>
          <span class="cca-badge" style="background:color-mix(in srgb, var(--success) 10%, transparent); color:var(--success-text)">Muscle Emissive Glow</span>
          <span class="cca-badge" style="background:color-mix(in srgb, var(--info) 10%, transparent); color:var(--info-text)">Playback Speed Slider</span>
        </div>
      </div>
      <div style="background:var(--surface); border-radius:16px; border:1px solid var(--border); padding:24px; text-align:center">
        <div style="font-size:64px; margin-bottom:16px">🏋️‍♂️</div>
        <h4 style="font-size:16px; color:var(--text-1)">Biomechanical Rigging</h4>
        <p style="font-size:13px; color:var(--text-3); margin-top:6px">Mapped to skeletal bone matrices for true form replication.</p>
      </div>
    </div>
  </div>

  <!-- Feature 2: Interactive SVG Muscle Map -->
  <div class="cca-card" style="margin-bottom:32px; padding:32px">
    <div class="cca-grid-2" style="align-items:center; gap:32px">
      <div style="background:var(--surface); border-radius:16px; border:1px solid var(--border); padding:24px">
        <div id="featureMuscleMap"></div>
      </div>
      <div>
        <span class="cca-badge cca-badge-medical" style="margin-bottom:12px">FEATURE #2</span>
        <h2 class="cca-h2" style="margin-bottom:12px">Interactive Anatomical Muscle Map</h2>
        <p style="font-size:15px; color:var(--text-2); line-height:1.7; margin-bottom:16px">
          Select target muscle groups directly on an interactive SVG vector human model. Instantly filter exercises targeting chest, core, quads, deltoids, or glutes with dynamic lighting synchronization in the 3D studio.
        </p>
        <a class="cca-btn cca-btn-primary" href="<?= url('member/workouts.php') ?>">Try Muscle Map Studio</a>
      </div>
    </div>
  </div>

  <!-- Feature 3: AI Scanners -->
  <div class="cca-card" style="margin-bottom:32px; padding:32px">
    <div class="cca-grid-2" style="align-items:center; gap:32px">
      <div>
        <span class="cca-badge cca-badge-live" style="margin-bottom:12px">FEATURE #3</span>
        <h2 class="cca-h2" style="margin-bottom:12px">AI Food & Body Composition Scanners</h2>
        <p style="font-size:15px; color:var(--text-2); line-height:1.7; margin-bottom:16px">
          Snap a photo of your meal to calculate calorie and macronutrient breakdown instantly. Utilize our body scanner tool to measure body fat percentage and muscular symmetry metrics.
        </p>
        <div style="display:flex; gap:12px">
          <a class="cca-btn cca-btn-secondary" href="<?= url('pages/scanner-food.php') ?>">📷 Food Scanner</a>
          <a class="cca-btn cca-btn-ghost" href="<?= url('pages/scanner-body.php') ?>">🏋️ Body Scanner</a>
        </div>
      </div>
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px">
        <div class="cca-card" style="text-align:center; padding:20px">
          <div style="font-size:36px; margin-bottom:8px">🥗</div>
          <h4 style="font-size:14px">Food Vision AI</h4>
          <p style="font-size:11px; color:var(--text-3); margin-top:4px">Instant macro breakdown</p>
        </div>
        <div class="cca-card" style="text-align:center; padding:20px">
          <div style="font-size:36px; margin-bottom:8px">📏</div>
          <h4 style="font-size:14px">Body Scanner</h4>
          <p style="font-size:11px; color:var(--text-3); margin-top:4px">Visual metric tracking</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Feature 4: Clinical E-Prescription & Doctor Marketplace -->
  <div class="cca-card" style="margin-bottom:32px; padding:32px">
    <div class="cca-grid-2" style="align-items:center; gap:32px">
      <div style="background:color-mix(in srgb, var(--info) 5%, transparent); border-radius:16px; border:1px solid color-mix(in srgb, var(--info) 20%, transparent); padding:28px">
        <h3 style="font-size:18px; color:var(--info-text); margin-bottom:12px">🩺 Integrated Medical Pipeline</h3>
        <ul style="list-style:none; padding:0; display:flex; flex-direction:column; gap:12px; font-size:14px; color:var(--text-2)">
          <li style="display:flex; align-items:center; gap:10px"><span style="color:var(--success-text)">✓</span> Doctor-approved workout plans for chronic conditions</li>
          <li style="display:flex; align-items:center; gap:10px"><span style="color:var(--success-text)">✓</span> E-Prescription vault with dosage tracking</li>
          <li style="display:flex; align-items:center; gap:10px"><span style="color:var(--success-text)">✓</span> Interactive Vitals log with ApexCharts trends</li>
          <li style="display:flex; align-items:center; gap:10px"><span style="color:var(--success-text)">✓</span> Verified doctor badge ratings</li>
        </ul>
      </div>
      <div>
        <span class="cca-badge cca-badge-medical" style="margin-bottom:12px">FEATURE #4</span>
        <h2 class="cca-h2" style="margin-bottom:12px">Doctor-Supervised Safe Plans</h2>
        <p style="font-size:15px; color:var(--text-2); line-height:1.7; margin-bottom:16px">
          Patients with medical conditions (e.g. hypertension, diabetes, joint replacement) receive customized exercise routines reviewed and approved by certified medical practitioners.
        </p>
        <a class="cca-btn cca-btn-primary" style="background:var(--info)" href="<?= url('patient/doctors.php') ?>">Browse Doctors</a>
      </div>
    </div>
  </div>
</div>

<script src="<?= asset('js/cca-voice.js') ?>"></script>
<script src="<?= asset('js/cca-coach.js') ?>"></script>
<script src="<?= asset('js/cca-wardrobe.js') ?>"></script>
<script src="<?= asset('js/titan3d.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  if (typeof initThree === 'function') initThree('featureHero3d');
  if (typeof CCAMuscleMap !== 'undefined') CCAMuscleMap.init('#featureMuscleMap');
});
</script>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
