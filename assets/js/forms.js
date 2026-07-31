/* ============ CORE CALORIE ADVISOR — Feedback + Report-Issue AJAX forms ============ */
(function () {
  function wire(formId, endpoint, doneId) {
    const form = document.getElementById(formId);
    if (!form) return;
    form.addEventListener('submit', e => {
      e.preventDefault();
      const btn = form.querySelector('button[type=submit]');
      btn.disabled = true; const label = btn.textContent; btn.textContent = 'Sending…';
      fetch(TF.baseUrl + endpoint, { method: 'POST', body: new FormData(form) })
        .then(r => r.json())
        .then(j => {
          if (j.ok) {
            form.style.display = 'none';
            const done = document.getElementById(doneId);
            if (done) done.style.display = 'block';
            toast('✔ Submitted — thank you!');
          } else { btn.disabled = false; btn.textContent = label; toast('⚠ ' + (j.error || 'Could not submit.')); }
        })
        .catch(() => { btn.disabled = false; btn.textContent = label; toast('⚠ Network error.'); });
    });
  }
  wire('fbForm', '/api/feedback.php', 'fbDone');
  wire('issueForm', '/api/report-issue.php', 'issDone');
})();
