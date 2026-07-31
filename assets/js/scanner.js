/* ============ CORE CALORIE ADVISOR — Live Camera AI Scanner (Google-Lens style) ============
   Body + Food. getUserMedia live feed -> lock-on capture -> POST to api/scan.php.
   Falls back to file upload when the camera is unavailable/denied.
   Requires window.TF = {baseUrl, csrf}.                                              */
(function () {
  const lens = document.getElementById('lens');
  if (!lens) return;

  const type    = lens.dataset.scanType === 'food' ? 'food' : 'body';
  const cam     = document.getElementById('lensCam');
  const video   = document.getElementById('lensVideo');
  const frame   = document.getElementById('lensFrame');
  const status  = document.getElementById('lensStatus');
  const result  = document.getElementById('lensResult');
  const startB  = document.getElementById('lensStart');
  const capB    = document.getElementById('lensCapture');
  const againB  = document.getElementById('lensAgain');
  const fileIn  = document.getElementById('lensFile');

  const STEPS = type === 'food'
    ? ['Detecting meal…', 'Reading nutrition…', 'Calculating macros…']
    : ['Detecting physique…', 'Measuring composition…', 'Matching trainer & program…'];

  let stream = null;
  let stepTimer = null;

  function esc(s) { return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c])); }

  function stopStream() {
    if (stream) { stream.getTracks().forEach(t => t.stop()); stream = null; }
  }

  function setButtons({ start, capture, again }) {
    startB.style.display = start ? 'inline-flex' : 'none';
    capB.style.display   = capture ? 'inline-flex' : 'none';
    againB.style.display = again ? 'inline-flex' : 'none';
  }

  /* ---------- Live camera ---------- */
  async function startCamera() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      toast('📷 Camera not supported — use Upload Instead.');
      return;
    }
    startB.disabled = true;
    startB.textContent = 'Starting…';
    try {
      stream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: type === 'food' ? 'environment' : 'user' }, audio: false
      });
      video.srcObject = stream;
      video.hidden = false;
      frame.hidden = true;
      await video.play();
      cam.classList.remove('captured', 'locked');
      cam.classList.add('live');
      status.textContent = 'Align in frame & hold steady…';
      setButtons({ start: false, capture: true, again: false });
    } catch (err) {
      toast(err && err.name === 'NotAllowedError'
        ? '📷 Camera permission denied — use Upload Instead.'
        : '📷 Camera unavailable — use Upload Instead.');
      cam.classList.remove('live');
    } finally {
      startB.disabled = false;
      startB.textContent = '📷 Start Camera';
    }
  }

  /* ---------- Capture current video frame -> blob -> scan ---------- */
  function captureFromVideo() {
    const w = video.videoWidth || 720, h = video.videoHeight || 900;
    const canvas = document.createElement('canvas');
    canvas.width = w; canvas.height = h;
    canvas.getContext('2d').drawImage(video, 0, 0, w, h);

    // freeze the frame visually, run lock-on, then scan
    frame.src = canvas.toDataURL('image/jpeg', 0.9);
    frame.hidden = false;
    video.hidden = true;
    cam.classList.remove('live');
    cam.classList.add('captured', 'locked');
    setButtons({ start: false, capture: false, again: false });
    stopStream();

    setTimeout(() => {
      cam.classList.remove('locked');
      canvas.toBlob(b => scan(b), 'image/jpeg', 0.9);
    }, 850);
  }

  /* ---------- Upload fallback ---------- */
  function handleFile(file) {
    if (!file) return;
    if (file.size > 5 * 1024 * 1024) { toast('⚠ Max 5MB image.'); return; }
    stopStream();
    const reader = new FileReader();
    reader.onload = () => {
      frame.src = reader.result;
      frame.hidden = false;
      video.hidden = true;
      cam.classList.remove('live');
      cam.classList.add('captured', 'locked');
      setButtons({ start: false, capture: false, again: false });
      setTimeout(() => { cam.classList.remove('locked'); scan(file); }, 700);
    };
    reader.readAsDataURL(file);
  }

  /* ---------- Send to backend ---------- */
  function scan(blob) {
    result.classList.remove('on');
    result.innerHTML = '';
    let step = 0;
    status.textContent = STEPS[0];
    stepTimer = setInterval(() => { step = (step + 1) % STEPS.length; status.textContent = STEPS[step]; }, 700);

    const fd = new FormData();
    fd.append('csrf', TF.csrf);
    fd.append('type', type);
    fd.append('photo', blob, 'scan.jpg');

    const started = Date.now();
    fetch(TF.baseUrl + '/api/scan.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(j => {
        const wait = Math.max(0, 1500 - (Date.now() - started));
        setTimeout(() => finish(j), wait);
      })
      .catch(() => finish({ ok: false, error: 'Network error — scan nahi ho saka.' }));
  }

  function finish(j) {
    clearInterval(stepTimer);
    cam.classList.remove('captured', 'locked');
    setButtons({ start: false, capture: false, again: true });
    if (j.ok) renderResult(j.result); else renderError(j.error || 'Scan failed — try again.');
  }

  function reset() {
    stopStream();
    frame.hidden = true;
    video.hidden = true;
    cam.classList.remove('live', 'captured', 'locked');
    result.classList.remove('on');
    result.innerHTML = '';
    fileIn.value = '';
    setButtons({ start: true, capture: false, again: false });
  }

  /* ---------- Result rendering ---------- */
  function renderError(msg) {
    result.innerHTML = '<div class="flash flash-err" style="margin:0 auto;max-width:560px">' + esc(msg) + '</div>';
    result.classList.add('on');
  }

  function renderResult(r) {
    result.innerHTML = type === 'food' ? foodHTML(r) : bodyHTML(r);
    result.classList.add('on');
    if (type === 'food') wireLogButton(r);
    result.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  function row(label, value) {
    return '<div class="sr-row"><span>' + label + '</span><b>' + value + '</b></div>';
  }
  function verdict(text) { return '<span style="color:#2ECC71">' + esc(text) + '</span>'; }

  function bodyHTML(r) {
    let html = '<div class="card" style="text-align:left"><div class="cbody">'
      + '<h3 style="color:var(--molten)">⚡ Body Composition Report</h3>'
      + row('Body Type', esc(r.body_type))
      + row('Exact Weight', esc(r.weight_kg) + ' kg')
      + row('Body Fat %', esc(r.body_fat_pct) + '%')
      + row('BMI', esc(r.bmi))
      + row('Muscle Mass', esc(r.muscle_mass_kg) + ' kg')
      + row('Recommendation', verdict(r.verdict));
    if (r.advice) html += '<p style="padding:12px 16px;color:var(--muted);line-height:1.6;font-size:14px">' + esc(r.advice) + '</p>';
    html += row('Suggested Trainer', esc(r.trainer))
      + row('Suggested Program', esc(r.program));
    if (r.health_flag && r.health_note) {
      html += '<div class="scan-health"><b>🩺 Health Alert</b><p>' + esc(r.health_note) + '</p>'
        + '<a class="btn btn-ghost btn-sm" href="' + esc(r.doctor_url) + '">Consult ' + esc(r.doctor || 'a Doctor') + ' →</a></div>';
    }
    html += '<div style="padding:16px"><a class="btn btn-fire btn-sm" href="' + TF.baseUrl + '/pages/'
      + (r.program_id ? 'workout-detail.php?id=' + encodeURIComponent(r.program_id) : 'workouts.php')
      + '">Start Suggested Workout →</a></div>'
      + (r.saved ? '<p style="padding:0 16px 14px;color:var(--faint);font-size:12px">✔ Saved to your scan history</p>' : '')
      + '</div></div>';
    return html;
  }

  function foodHTML(r) {
    return '<div class="card" style="text-align:left"><div class="cbody">'
      + '<h3 style="color:var(--molten)">⚡ Detected: ' + esc(r.name) + '</h3>'
      + (r.serving ? row('Serving', esc(r.serving)) : '')
      + row('Calories', esc(r.kcal) + ' kcal')
      + row('Protein', esc(r.protein) + ' g')
      + row('Carbs', esc(r.carbs) + ' g')
      + row('Fats', esc(r.fats) + ' g')
      + row('AI Verdict', verdict(r.verdict))
      + '<div style="padding:16px"><button class="btn btn-fire btn-sm" id="lensLogBtn">➕ Add to Daily Log</button></div>'
      + '</div></div>';
  }

  function wireLogButton(r) {
    const btn = document.getElementById('lensLogBtn');
    if (!btn) return;
    btn.addEventListener('click', () => {
      btn.disabled = true; btn.textContent = 'Logging…';
      const fd = new FormData();
      fd.append('csrf', TF.csrf);
      fd.append('name', r.name); fd.append('kcal', r.kcal);
      fd.append('protein', r.protein); fd.append('carbs', r.carbs); fd.append('fats', r.fats);
      fetch(TF.baseUrl + '/api/log-food.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(j => {
          if (j.ok) { btn.textContent = '✔ Added to Daily Log'; toast('✔ ' + r.name + ' logged — ' + r.kcal + ' kcal'); }
          else { btn.disabled = false; btn.textContent = '➕ Add to Daily Log'; toast('⚠ ' + (j.error || 'Could not log.')); }
        })
        .catch(() => { btn.disabled = false; btn.textContent = '➕ Add to Daily Log'; toast('⚠ Network error.'); });
    });
  }

  /* ---------- Wire up ---------- */
  startB.addEventListener('click', startCamera);
  capB.addEventListener('click', captureFromVideo);
  againB.addEventListener('click', reset);
  fileIn.addEventListener('change', () => handleFile(fileIn.files && fileIn.files[0]));
  window.addEventListener('pagehide', stopStream);
})();
