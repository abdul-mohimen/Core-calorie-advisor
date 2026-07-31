<?php
require_once dirname(__DIR__) . '/config/config.php';
require_role('patient', 'admin');
$uid = $_SESSION['user']['id'];
$vitals = [];
try { $vst = db()->prepare('SELECT * FROM vitals_logs WHERE user_id = ? ORDER BY logged_at DESC LIMIT 30'); $vst->execute([$uid]); $vitals = $vst->fetchAll(); } catch (Throwable $e) {}

$portal = 'patient'; $pageTitle = 'Vitals Log';
include dirname(__DIR__) . '/includes/header.php';
/* Chart data for ApexCharts */
$chartDates = []; $chartHR = []; $chartSugar = []; $chartWeight = [];
foreach (array_reverse($vitals) as $v) {
    $chartDates[] = date('M d', strtotime($v['logged_at']));
    $chartHR[] = (int)$v['heart_rate'];
    $chartSugar[] = (float)$v['blood_sugar'];
    $chartWeight[] = (float)$v['weight_kg'];
}
?>
<section class="cca-hero cca-hero--gradient"><div class="cca-hero__overlay"></div><div class="cca-hero__content"><div class="cca-hero__text">
  <div class="cca-hero__breadcrumb"><a href="<?= url('patient/dashboard.php') ?>">Dashboard</a> <span class="sep">›</span> Vitals</div>
  <h1 class="cca-hero__title">Health <span class="grad">Vitals Log</span></h1>
  <p class="cca-hero__subtitle">Interactive health metrics charts. Track heart rate, blood sugar, weight, and BMI over time.</p>
</div></div></section>
<?= portal_nav('patient') ?>
<div class="cca-page-container">
  <!-- ═══ CHARTS ═══ -->
  <div class="cca-grid-2" style="margin-bottom:24px">
    <div class="cca-card"><h3 class="cca-h3">❤️ Heart Rate</h3><div id="chartHR" style="height:240px"></div></div>
    <div class="cca-card"><h3 class="cca-h3">🩸 Blood Sugar</h3><div id="chartSugar" style="height:240px"></div></div>
  </div>
  <div class="cca-card" style="margin-bottom:24px"><h3 class="cca-h3">⚖️ Weight Trend</h3><div id="chartWeight" style="height:240px"></div></div>

  <!-- ═══ DATA TABLE ═══ -->
  <div class="cca-card">
    <h3 class="cca-h3">📋 Full Vitals History</h3>
    <div class="cca-table-wrap"><table class="cca-table">
      <tr><th>Date</th><th>Heart Rate</th><th>Blood Sugar</th><th>BP</th><th>Weight</th><th>BMI</th><th>Notes</th></tr>
      <?php if (!$vitals): ?><tr><td colspan="7" style="color:var(--text-3)">No vitals logged yet.</td></tr><?php endif; ?>
      <?php foreach ($vitals as $v): ?>
      <tr>
        <td><?= date('M d, Y', strtotime($v['logged_at'])) ?></td>
        <td><?= $v['heart_rate'] ? $v['heart_rate'] . ' bpm' : '—' ?></td>
        <td><?= $v['blood_sugar'] ? $v['blood_sugar'] . ' mg/dL' : '—' ?></td>
        <td><?= e($v['blood_pressure'] ?? '—') ?></td>
        <td><?= $v['weight_kg'] ? $v['weight_kg'] . ' kg' : '—' ?></td>
        <td><?= $v['bmi'] ? number_format((float)$v['bmi'], 1) : '—' ?></td>
        <td style="color:var(--text-3); font-size:12px"><?= e($v['notes'] ?? '') ?></td>
      </tr>
      <?php endforeach; ?>
    </table></div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.44.0/dist/apexcharts.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const dates = <?= json_encode($chartDates) ?>;
  const baseOpts = {
    chart: { toolbar: { show: false }, background: 'transparent' },
    theme: { mode: 'dark' },
    grid: { borderColor: 'rgba(255,255,255,0.06)' },
    xaxis: { categories: dates, labels: { style: { colors: '#6B7280', fontSize: '11px' } } },
    yaxis: { labels: { style: { colors: '#6B7280', fontSize: '11px' } } },
    stroke: { curve: 'smooth', width: 3 },
    tooltip: { theme: 'dark' },
  };

  new ApexCharts(document.getElementById('chartHR'), {
    ...baseOpts, series: [{ name: 'Heart Rate', data: <?= json_encode($chartHR) ?> }],
    chart: { ...baseOpts.chart, type: 'area', height: 240 },
    colors: ['#EF4444'], fill: { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0 } }
  }).render();

  new ApexCharts(document.getElementById('chartSugar'), {
    ...baseOpts, series: [{ name: 'Blood Sugar', data: <?= json_encode($chartSugar) ?> }],
    chart: { ...baseOpts.chart, type: 'area', height: 240 },
    colors: ['#3B82F6'], fill: { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0 } }
  }).render();

  new ApexCharts(document.getElementById('chartWeight'), {
    ...baseOpts, series: [{ name: 'Weight (kg)', data: <?= json_encode($chartWeight) ?> }],
    chart: { ...baseOpts.chart, type: 'line', height: 240 },
    colors: ['#10B981']
  }).render();
});
</script>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
