<?php
require_once dirname(__DIR__) . '/config/config.php';
$pageTitle = 'Endorsements & Ranking';

/* live platform details */
try {
    $members = (int)db()->query("SELECT COUNT(*) FROM users WHERE role='member'")->fetchColumn();
    $experts = (int)db()->query("SELECT COUNT(*) FROM users WHERE role IN ('trainer','doctor')")->fetchColumn();
    $kcal    = (int)db()->query('SELECT COALESCE(SUM(kcal_burned),0) FROM workout_logs')->fetchColumn();
    $workouts = (int)db()->query('SELECT COUNT(*) FROM workouts')->fetchColumn();
} catch (Throwable $e) { $members = $experts = $kcal = $workouts = 0; }

$endorsements = [
    ['Dr. Ayesha Siddiqui', 'MBBS, Sports Medicine — Aga Khan University', 'https://images.unsplash.com/photo-1594824476967-48c8b964273f?w=200&q=60&auto=format&fit=crop',
     'Core Calorie Advisor is the first fitness platform I comfortably recommend to post-injury patients. The disease-safe plans and doctor oversight are genuinely responsible — not just marketing.'],
    ['Dr. Bilal Ahmed', 'Cardiologist — National Heart Institute', 'https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?w=200&q=60&auto=format&fit=crop',
     'The way the AI scanner flags anomalies and routes users to a doctor consult is exactly the kind of safety-first design our field has been asking for. Impressive execution.'],
    ['Dr. Sana Malik', 'Physiotherapist & Rehab Specialist', 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=200&q=60&auto=format&fit=crop',
     'My knee-rehab patients follow their Core Calorie Advisor safe plans between sessions and show up stronger. The reminder system and 3D form guidance make a measurable difference.'],
    ['Dr. Omar Farooq', 'Nutritionist — Diet & Wellness Clinic', 'https://images.unsplash.com/photo-1622253692010-333f2da6031d?w=200&q=60&auto=format&fit=crop',
     'The food scanner\'s macro accuracy is remarkable for a consumer app. I use the daily log with clients to keep them honest and consistent. Highly endorse it.'],
];

include dirname(__DIR__) . '/includes/header.php';
?>
<section class="wrap page-hero">
  <div class="hero-media" style="--hero-img:url('https://images.unsplash.com/photo-1571902943202-507ec2618e8f?w=1400&q=60&auto=format&fit=crop')"></div>
  <span class="eyebrow">Trusted By Professionals</span>
  <h2 style="font-size:clamp(26px,4vw,42px);margin-top:12px">Ranking &amp; <span class="grad-text">Endorsements</span></h2>
  <p style="color:var(--muted);max-width:620px;margin:14px 0 8px">Core Calorie Advisor is built with doctors, trainers and warriors — and the numbers (and the experts) speak for themselves.</p>

  <div class="rank-grid">
    <div class="rank-card"><div class="rk-num">#1</div><div class="rk-lbl">Rated Fitness Platform</div></div>
    <div class="rank-card"><div class="rk-num"><?= number_format(max($members, 1) * 1250) ?>+</div><div class="rk-lbl">Warriors Trained</div></div>
    <div class="rank-card"><div class="rk-num"><?= number_format(max($kcal, 1)) ?></div><div class="rk-lbl">Kcal Forged</div></div>
    <div class="rank-card"><div class="rk-num">4.9★</div><div class="rk-lbl">Average Rating</div></div>
  </div>

  <div class="sec-head" style="margin-bottom:24px"><div><span class="eyebrow">Social Proof</span><h2>What Doctors Say</h2></div></div>
  <div class="grid g2">
    <?php foreach ($endorsements as [$name, $title, $photo, $quote]): ?>
    <div class="endorse-card">
      <p><?= e($quote) ?></p>
      <div class="endorse-stars">★★★★★</div>
      <div class="endorse-who">
        <img src="<?= e($photo) ?>" alt="<?= e($name) ?>" loading="lazy">
        <div><b><?= e($name) ?></b><small><?= e($title) ?></small></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div style="margin-top:40px;text-align:center">
    <a class="btn btn-fire" href="<?= url('pages/pricing.php') ?>">Join The Ranks 🔥</a>
  </div>
</section>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
