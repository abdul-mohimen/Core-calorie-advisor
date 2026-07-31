/* CORE CALORIE ADVISOR scanner UI — one real request path for file upload and camera capture.
   The API, not the browser, is the source of scan results and persistence. */
(function () {
  'use strict';

  function text(id, value) { const node = document.getElementById(id); if (node) node.textContent = value == null ? '—' : value; }
  function show(node, visible) { if (node) node.classList.toggle('hidden', !visible); }
  function message(value) { if (window.toast) window.toast(value); else window.alert(value); }
  function isImage(file) { return file && ['image/jpeg', 'image/png', 'image/webp'].includes(file.type) && file.size <= 5 * 1024 * 1024; }

  function init(type) {
    const fileInput = document.getElementById('fileInput');
    const dropZone = document.getElementById('dropZone');
    const preview = document.getElementById('previewContainer');
    const image = document.getElementById('imagePreview');
    const remove = document.getElementById('removeImgBtn');
    const scanBtn = document.getElementById('scanBtn');
    const laser = document.getElementById('scannerLaser');
    const awaiting = document.getElementById('resultAwaiting');
    const loading = document.getElementById('resultLoading');
    const failed = document.getElementById('resultError');
    const data = document.getElementById('resultData');
    const loadingStatus = document.getElementById('loadingStatus');
    if (!fileInput || !dropZone || !scanBtn || scanBtn.dataset.tfBound) return;
    scanBtn.dataset.tfBound = '1';

    let activeFile = null;
    let stream = null;
    let cameraButton = null;

    function stopCamera() {
      if (stream) stream.getTracks().forEach(track => track.stop());
      stream = null;
      const video = dropZone.querySelector('video[data-tf-camera]');
      if (video) video.remove();
      if (cameraButton) cameraButton.textContent = 'Use live camera';
    }
    function resetResult() {
      show(awaiting, true); show(loading, false); show(failed, false); show(data, false);
      if (laser) laser.classList.add('hidden');
    }
    function setFile(file) {
      if (!isImage(file)) { message('Use a JPG, PNG or WebP image up to 5 MB.'); return; }
      stopCamera(); activeFile = file;
      const reader = new FileReader();
      reader.onload = event => { image.src = event.target.result; show(preview, true); scanBtn.disabled = false; };
      reader.readAsDataURL(file);
      resetResult();
    }
    function setError(value) {
      text('errorMsg', value); show(awaiting, false); show(loading, false); show(data, false); show(failed, true);
      if (laser) laser.classList.add('hidden');
    }
    function setLoading() {
      show(awaiting, false); show(failed, false); show(data, false); show(loading, true);
      if (laser) laser.classList.remove('hidden');
      if (loadingStatus) loadingStatus.textContent = type === 'food' ? 'Identifying meal and estimating macros…' : 'Assessing visible training markers…';
    }
    function renderBody(result, meta) {
      text('detectedVerdict', result.verdict || 'Training profile');
      text('detectedVerdictLabel', meta && meta.demo ? 'Goal-based plan' : 'AI estimate');
      text('statBodyType', result.body_type || 'Not determined');
      text('statWeight', result.weight_kg ? result.weight_kg + ' kg (estimate)' : 'Not estimated');
      text('statBodyFat', result.body_fat_pct != null ? result.body_fat_pct + '% (estimate)' : 'Not estimated');
      text('statBMI', result.bmi != null ? String(result.bmi) : 'Needs height');
      text('statMuscleMass', result.muscle_mass_kg ? result.muscle_mass_kg + ' kg (estimate)' : 'Not estimated');
      text('statTrainer', result.trainer || 'CCA coach matching');
      text('statProgram', result.program || 'Choose a suitable program');
      const health = document.getElementById('healthNoteContainer');
      show(health, Boolean(result.health_flag));
      text('statHealthNote', result.health_note || 'This is not medical advice. Please consult a qualified clinician for health concerns.');
      const program = document.getElementById('startWorkoutBtn');
      if (program) program.href = result.program_id ? TF.baseUrl + '/pages/workout-detail.php?id=' + encodeURIComponent(result.program_id) : TF.baseUrl + '/pages/workouts.php';
      if (meta && meta.demo) {
        const label = document.getElementById('detectedVerdictLabel');
        if (label) label.title = 'Connect the configured vision provider for a live image analysis.';
      }
      // Muscle focus breakdown — which muscles to develop + what to do
      const mfWrap = document.getElementById('muscleFocusWrap');
      const mfList = document.getElementById('muscleFocusList');
      if (mfWrap && mfList) {
        mfList.innerHTML = '';
        const rows = Array.isArray(result.muscle_focus) ? result.muscle_focus : [];
        rows.forEach(row => {
          const pct = row.priority === 'High' ? 90 : row.priority === 'Medium' ? 60 : 35;
          const div = document.createElement('div');
          div.className = 'mf-row';
          div.innerHTML = '<span class="mf-name"></span>' +
            '<div class="mf-bar"><div class="mf-fill" style="width:0%"></div></div>' +
            '<span class="mf-act"></span>';
          div.querySelector('.mf-name').textContent = row.muscle || '';
          div.querySelector('.mf-act').textContent = row.action || '';
          mfList.appendChild(div);
          requestAnimationFrame(() => { div.querySelector('.mf-fill').style.width = pct + '%'; });
        });
        show(mfWrap, rows.length > 0);
      }
      // Next steps checklist
      const nsWrap = document.getElementById('nextStepsWrap');
      const nsList = document.getElementById('nextStepsList');
      if (nsWrap && nsList) {
        nsList.innerHTML = '';
        const steps = Array.isArray(result.next_steps) ? result.next_steps : [];
        steps.forEach(step => {
          const li = document.createElement('li');
          li.textContent = '✓ ' + step;
          nsList.appendChild(li);
        });
        show(nsWrap, steps.length > 0);
      }
    }
    function renderFood(result, meta) {
      text('detectedMealName', result.name || 'Meal');
      text('detectedMealVerdict', meta && meta.demo ? 'Reference match' : (result.verdict || 'AI estimate'));
      text('statKcal', result.kcal != null ? String(result.kcal) : '—');
      text('statProtein', result.protein != null ? result.protein + 'g' : '—');
      text('statCarbs', result.carbs != null ? result.carbs + 'g' : '—');
      text('statFats', result.fats != null ? result.fats + 'g' : '—');
      text('tableKcal', result.kcal != null ? result.kcal + ' kcal' : '—');
      text('tableProtein', result.protein != null ? result.protein + ' g' : '—');
      text('tableCarbs', result.carbs != null ? result.carbs + ' g' : '—');
      text('tableFats', result.fats != null ? result.fats + ' g' : '—');
      text('tableFiber', result.fiber_g != null ? result.fiber_g + ' g' : 'Not available');
      text('tableSugars', result.sugars_g != null ? result.sugars_g + ' g' : 'Not available');
      text('tableSatFat', result.saturated_fat_g != null ? result.saturated_fat_g + ' g' : 'Not available');
      text('tableSodium', result.sodium_mg != null ? result.sodium_mg + ' mg' : 'Not available');
    }
    function render(result, meta) {
      show(loading, false); if (laser) laser.classList.add('hidden');
      if (type === 'food') renderFood(result, meta); else renderBody(result, meta);
      show(data, true);
    }
    function activeGoal() {
      const chip = document.querySelector('#goalChips .goal-chip.on');
      return chip ? chip.dataset.goal : 'recomp';
    }
    function runScan(file) {
      if (!file) { message('Choose or capture an image first.'); return; }
      setLoading();
      const form = new FormData();
      form.append('csrf', TF.csrf); form.append('type', type); form.append('goal', activeGoal());
      form.append('photo', file, file.name || 'camera-capture.jpg');
      fetch(TF.baseUrl + '/api/scan.php', { method: 'POST', body: form, credentials: 'same-origin' })
        .then(response => response.json().then(payload => ({ response, payload })))
        .then(({ response, payload }) => payload.ok ? render(payload.result || {}, payload) : setError(payload.error || ('Scan failed (' + response.status + ').')))
        .catch(() => setError('Network error. Please check your connection and try again.'));
    }
    async function openCamera() {
      if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) { message('Live camera is not available in this browser. Upload a photo instead.'); return; }
      stopCamera();
      try {
        stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: type === 'food' ? 'environment' : 'user' }, audio: false });
        const video = document.createElement('video');
        video.dataset.tfCamera = '1'; video.autoplay = true; video.playsInline = true;
        video.className = 'absolute inset-0 z-30 w-full h-full object-cover'; video.srcObject = stream;
        dropZone.appendChild(video); await video.play();
        cameraButton.textContent = 'Capture photo';
        cameraButton.onclick = () => {
          const canvas = document.createElement('canvas'); canvas.width = video.videoWidth || 720; canvas.height = video.videoHeight || 960;
          canvas.getContext('2d').drawImage(video, 0, 0); canvas.toBlob(blob => { if (blob) setFile(new File([blob], 'camera-capture.jpg', { type: 'image/jpeg' })); }, 'image/jpeg', .9);
        };
      } catch (error) { message('Camera permission was not granted. Upload a photo instead.'); }
    }

    document.querySelectorAll('#goalChips .goal-chip').forEach(chip => {
      chip.addEventListener('click', () => {
        document.querySelectorAll('#goalChips .goal-chip').forEach(other => other.classList.remove('on'));
        chip.classList.add('on');
      });
    });
    fileInput.addEventListener('change', event => setFile(event.target.files && event.target.files[0]));
    dropZone.addEventListener('dragover', event => { event.preventDefault(); dropZone.classList.add('border-brand-accent/70'); });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('border-brand-accent/70'));
    dropZone.addEventListener('drop', event => { event.preventDefault(); dropZone.classList.remove('border-brand-accent/70'); setFile(event.dataTransfer.files && event.dataTransfer.files[0]); });
    remove.addEventListener('click', event => { event.preventDefault(); event.stopPropagation(); activeFile = null; fileInput.value = ''; image.src = ''; show(preview, false); scanBtn.disabled = true; resetResult(); });
    scanBtn.addEventListener('click', () => runScan(activeFile));
    document.getElementById('scanResetBtn')?.addEventListener('click', resetResult);
    document.getElementById('errorResetBtn')?.addEventListener('click', resetResult);
    window.addEventListener('pagehide', stopCamera, { once: true });

    cameraButton = document.createElement('button');
    cameraButton.type = 'button'; cameraButton.className = 'mt-2 text-xs font-bold text-brand-accent underline underline-offset-4';
    cameraButton.textContent = 'Use live camera'; cameraButton.addEventListener('click', openCamera);
    dropZone.insertAdjacentElement('afterend', cameraButton);

    if (type === 'food') {
      const manualInput = document.getElementById('manualMealInput');
      document.getElementById('manualScanBtn')?.addEventListener('click', () => {
        const query = (manualInput && manualInput.value || '').trim();
        if (!query) { message('Describe your meal first.'); return; }
        setLoading();
        const form = new FormData(); form.append('csrf', TF.csrf); form.append('query', query);
        fetch(TF.baseUrl + '/api/food-lookup.php', { method: 'POST', body: form, credentials: 'same-origin' })
          .then(response => response.json()).then(payload => payload.ok ? render(payload.result, payload) : setError(payload.error || 'Meal was not found.'))
          .catch(() => setError('Network error. Please try again.'));
      });
      document.getElementById('addLogBtn')?.addEventListener('click', () => {
        const button = document.getElementById('addLogBtn'); button.disabled = true; button.textContent = 'Adding…';
        const form = new FormData(); form.append('csrf', TF.csrf); form.append('name', document.getElementById('detectedMealName').textContent);
        form.append('kcal', document.getElementById('statKcal').textContent); form.append('protein', document.getElementById('statProtein').textContent);
        form.append('carbs', document.getElementById('statCarbs').textContent); form.append('fats', document.getElementById('statFats').textContent);
        fetch(TF.baseUrl + '/api/log-food.php', { method: 'POST', body: form, credentials: 'same-origin' })
          .then(response => response.json()).then(payload => { if (!payload.ok) throw new Error(payload.error); button.textContent = 'Added to daily log'; message('Meal added to your daily log.'); })
          .catch(error => { button.disabled = false; button.textContent = 'Add to Daily Log'; message(error.message || 'Could not add meal.'); });
      });
    }
  }
  window.TFScanUI = { init };
})();
