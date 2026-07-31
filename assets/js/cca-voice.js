/* ============ CORE CALORIE ADVISOR — shared coach-voice selection ============
   Both 3D engines (assets/js/titan3d.js and the inline exactfit3d engine in
   pages/player.php) each carried their own copy of a VoiceManager with its own,
   slightly different voice-priority list. They drifted: one preferred
   "Guy Online (Natural)" by exact name, the other matched /Google UK English
   Male/ by regex, and their pitch defaults disagreed (0.85 vs 0.9). This file is
   the single source of truth for WHICH voice is used; each engine keeps its own
   speak/stop plumbing.

   Reality check (measured 2026-07-30 on this machine): Chrome exposed 6 English
   voices and NOT ONE of them was neural — no "… Online (Natural)" at all. The
   Web Speech API can only use voices installed on the visitor's OS, so the
   biggest available quality win is installing the Windows Natural voices:
     Settings -> Time & Language -> Speech -> Manage voices -> Add voices
   Once installed they appear here automatically and rank first, with no code
   change. Until then the best of what exists is picked. */
(function (w) {
  'use strict';

  var STORE_KEY = 'cca-voice-name';

  /* Highest quality first. Neural/Natural engines beat every legacy SAPI voice
     by a wide margin, so they are matched before any specific name. */
  var RANK = [
    /Online \(Natural\)/i,          // Microsoft neural (Guy, Andrew, Christopher, Brian…)
    /Natural|Neural/i,              // any other vendor's neural voice
    /Google (UK|US) English Male/i, // Google network voices — decent, not neural
    /Google (UK|US) English/i,
    /Microsoft (Mark|David|Guy|Andrew|Christopher)/i,
    /English.*Male/i,
    /^en[-_]/i
  ];

  function englishOnly(v) {
    return v && typeof v.lang === 'string' && /^en/i.test(v.lang);
  }

  /* The visitor's explicit choice always wins over the ranking. */
  function saved() {
    try { return w.localStorage.getItem(STORE_KEY) || ''; } catch (e) { return ''; }
  }

  function pick(voices) {
    if (!voices || !voices.length) return null;
    var en = voices.filter(englishOnly);
    var pool = en.length ? en : voices;

    var want = saved();
    if (want) {
      var exact = pool.find(function (v) { return v.name === want; })
               || voices.find(function (v) { return v.name === want; });
      if (exact) return exact;
    }
    for (var i = 0; i < RANK.length; i++) {
      var hit = pool.find(function (v) { return RANK[i].test(v.name) || RANK[i].test(v.lang); });
      if (hit) return hit;
    }
    return pool[0] || null;
  }

  function list() {
    if (!('speechSynthesis' in w)) return [];
    return w.speechSynthesis.getVoices().filter(englishOnly);
  }

  function setVoice(name) {
    try { w.localStorage.setItem(STORE_KEY, name || ''); } catch (e) {}
    if (typeof w.CCAVoiceChanged === 'function') w.CCAVoiceChanged(name);
  }

  /* Whether any genuinely neural voice exists — used to tell the user the
     truth in the UI instead of silently sounding robotic. */
  function hasNeural() {
    return list().some(function (v) { return /Online \(Natural\)|Natural|Neural/i.test(v.name); });
  }

  /* Renders a <select> of the installed English voices into `mount`.
     Returns the element, or null if speech is unavailable. */
  function buildPicker(mount, onChange) {
    if (!mount || !('speechSynthesis' in w)) return null;
    var voices = list();
    if (!voices.length) return null;

    var wrap = document.createElement('label');
    wrap.className = 'cca-voice-picker';
    wrap.style.cssText = 'display:flex;align-items:center;gap:8px;font-size:12px;opacity:.9';

    var span = document.createElement('span');
    span.textContent = 'Coach voice';

    var sel = document.createElement('select');
    sel.className = 'cca-voice-select';
    sel.style.cssText = 'max-width:210px;padding:4px 6px;border-radius:6px';

    var current = pick(voices);
    voices.forEach(function (v) {
      var o = document.createElement('option');
      o.value = v.name;
      o.textContent = v.name.replace(/ - English \(.*\)$/, '');
      if (current && v.name === current.name) o.selected = true;
      sel.appendChild(o);
    });

    sel.addEventListener('change', function () {
      setVoice(sel.value);
      if (typeof onChange === 'function') onChange(sel.value);
    });

    wrap.appendChild(span); wrap.appendChild(sel);

    if (!hasNeural()) {
      var hint = document.createElement('span');
      hint.className = 'cca-voice-hint';
      hint.style.cssText = 'font-size:11px;opacity:.65';
      hint.title = 'Windows Settings → Time & Language → Speech → Manage voices → Add voices';
      hint.textContent = '(install Windows Natural voices for a human-sounding coach)';
      wrap.appendChild(hint);
    }
    mount.appendChild(wrap);
    return wrap;
  }

  /* Deliberately does NOT touch window.CCAVoice — pages/player.php already owns
     that name (aliased to TitanVoice at player.php:639). Only CCAVoice* helpers. */
  w.CCAVoicePick = pick;          // used by both engines' initVoices()
  w.CCAVoiceList = list;
  w.CCAVoiceSet = setVoice;
  w.CCAVoiceHasNeural = hasNeural;
  w.CCAVoicePicker = buildPicker;
})(window);
