/* ============ CORE CALORIE ADVISOR — Interactive Muscle Map ============
   SVG-based anatomical map with clickable muscle groups.
   Usage: CCAMuscleMap.init('#muscleMapContainer', {
     onSelect: (muscle) => { ... filter workouts ... }
   })
   =================================================================== */

const CCAMuscleMap = (() => {
  'use strict';

  const MUSCLES = {
    chest:       { label: 'Chest',       color: '#FF6B1A', exercises: ['pushup','press'] },
    back:        { label: 'Back',        color: '#F59E0B', exercises: ['curl','press'] },
    shoulders:   { label: 'Shoulders',   color: '#3B82F6', exercises: ['press'] },
    arms:        { label: 'Arms',        color: '#10B981', exercises: ['curl'] },
    core:        { label: 'Core',        color: '#EF4444', exercises: ['crunch','twist','legraise','plank','mountain'] },
    legs:        { label: 'Legs',        color: '#8B5CF6', exercises: ['squat','highknees','legraise'] },
    glutes:      { label: 'Glutes',      color: '#EC4899', exercises: ['squat'] },
    'full-body': { label: 'Full Body',   color: '#14B8A6', exercises: ['jumpingjack','mountain','warmup'] },
    flexibility: { label: 'Flexibility', color: '#6366F1', exercises: ['yoga'] },
  };

  /* Simplified anatomical SVG paths (front view silhouette with muscle regions) */
  const SVG_TEMPLATE = `
    <svg viewBox="0 0 200 440" fill="none" xmlns="http://www.w3.org/2000/svg" class="cca-muscle-svg">
      <!-- Body outline silhouette -->
      <path class="body-outline" d="M100 20 C120 20 135 35 135 55 L135 70 C150 75 165 90 165 110 L160 115 C165 120 170 135 170 150 L165 155 L168 200 L175 250 L180 260 L172 265 L165 255 L160 260 L155 310 L158 360 L155 400 L145 420 L135 420 L130 400 L125 360 L120 310 L115 280 L100 270 L85 280 L80 310 L75 360 L70 400 L65 420 L55 420 L45 400 L48 360 L45 310 L40 260 L35 255 L28 265 L20 260 L25 250 L32 200 L35 155 L30 150 C30 135 35 120 40 115 L35 110 C35 90 50 75 65 70 L65 55 C65 35 80 20 100 20Z"
        fill="rgba(255,255,255,0.03)" stroke="rgba(255,255,255,0.12)" stroke-width="1"/>

      <!-- HEAD -->
      <ellipse cx="100" cy="35" rx="22" ry="25" fill="rgba(255,255,255,0.05)" stroke="rgba(255,255,255,0.1)" stroke-width="0.8"/>

      <!-- CHEST (clickable muscle region) -->
      <path data-muscle="chest" d="M72 95 L128 95 L135 130 L65 130Z"
        fill="rgba(255,107,26,0.08)" stroke="rgba(255,107,26,0.2)" stroke-width="1" class="muscle-region"/>

      <!-- SHOULDERS -->
      <ellipse data-muscle="shoulders" cx="60" cy="95" rx="15" ry="12"
        fill="rgba(59,130,246,0.08)" stroke="rgba(59,130,246,0.2)" stroke-width="1" class="muscle-region"/>
      <ellipse data-muscle="shoulders" cx="140" cy="95" rx="15" ry="12"
        fill="rgba(59,130,246,0.08)" stroke="rgba(59,130,246,0.2)" stroke-width="1" class="muscle-region"/>

      <!-- ARMS (biceps area) -->
      <rect data-muscle="arms" x="35" y="110" width="18" height="50" rx="9"
        fill="rgba(16,185,129,0.08)" stroke="rgba(16,185,129,0.2)" stroke-width="1" class="muscle-region"/>
      <rect data-muscle="arms" x="147" y="110" width="18" height="50" rx="9"
        fill="rgba(16,185,129,0.08)" stroke="rgba(16,185,129,0.2)" stroke-width="1" class="muscle-region"/>

      <!-- CORE (abs area) -->
      <rect data-muscle="core" x="78" y="135" width="44" height="60" rx="8"
        fill="rgba(239,68,68,0.08)" stroke="rgba(239,68,68,0.2)" stroke-width="1" class="muscle-region"/>

      <!-- LEGS (quads / hamstrings) -->
      <rect data-muscle="legs" x="65" y="220" width="25" height="90" rx="10"
        fill="rgba(139,92,246,0.08)" stroke="rgba(139,92,246,0.2)" stroke-width="1" class="muscle-region"/>
      <rect data-muscle="legs" x="110" y="220" width="25" height="90" rx="10"
        fill="rgba(139,92,246,0.08)" stroke="rgba(139,92,246,0.2)" stroke-width="1" class="muscle-region"/>

      <!-- GLUTES -->
      <ellipse data-muscle="glutes" cx="100" cy="210" rx="28" ry="15"
        fill="rgba(236,72,153,0.08)" stroke="rgba(236,72,153,0.2)" stroke-width="1" class="muscle-region"/>

      <!-- CALVES -->
      <rect data-muscle="legs" x="68" y="330" width="18" height="50" rx="8"
        fill="rgba(139,92,246,0.06)" stroke="rgba(139,92,246,0.15)" stroke-width="1" class="muscle-region"/>
      <rect data-muscle="legs" x="114" y="330" width="18" height="50" rx="8"
        fill="rgba(139,92,246,0.06)" stroke="rgba(139,92,246,0.15)" stroke-width="1" class="muscle-region"/>

      <!-- Muscle labels (appear on hover via CSS) -->
      <text x="100" y="118" text-anchor="middle" class="muscle-label" data-for="chest">CHEST</text>
      <text x="100" y="170" text-anchor="middle" class="muscle-label" data-for="core">CORE</text>
      <text x="40" y="140" text-anchor="middle" class="muscle-label" data-for="arms">ARMS</text>
      <text x="160" y="140" text-anchor="middle" class="muscle-label" data-for="arms">ARMS</text>
      <text x="55" y="85" text-anchor="middle" class="muscle-label" data-for="shoulders">DELTS</text>
      <text x="145" y="85" text-anchor="middle" class="muscle-label" data-for="shoulders">DELTS</text>
      <text x="100" y="215" text-anchor="middle" class="muscle-label" data-for="glutes">GLUTES</text>
      <text x="78" y="270" text-anchor="middle" class="muscle-label" data-for="legs">LEGS</text>
      <text x="122" y="270" text-anchor="middle" class="muscle-label" data-for="legs">LEGS</text>
    </svg>
  `;

  const STYLE = `
    <style>
      .cca-muscle-map { user-select: none; }
      .cca-muscle-svg { width: 100%; max-width: 220px; margin: 0 auto; display: block; }
      .muscle-region {
        cursor: pointer;
        transition: all 0.3s ease;
      }
      .muscle-region:hover {
        filter: brightness(2.5) drop-shadow(0 0 8px currentColor);
        transform-origin: center;
      }
      .muscle-region.active {
        filter: brightness(3) drop-shadow(0 0 12px currentColor);
        stroke-width: 2;
      }
      .muscle-label {
        font-family: 'Rajdhani', sans-serif;
        font-size: 8px;
        font-weight: 700;
        letter-spacing: 1px;
        fill: rgba(255,255,255,0.4);
        pointer-events: none;
        transition: fill 0.2s;
      }
      .muscle-region:hover ~ .muscle-label,
      .muscle-region.active ~ .muscle-label {
        fill: rgba(255,255,255,0.9);
      }
      .cca-muscle-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        justify-content: center;
        margin-top: 16px;
      }
      .cca-muscle-btn {
        padding: 6px 14px;
        border-radius: 8px;
        border: 1px solid var(--border);
        background: var(--surface);
        color: var(--text-2);
        font-family: var(--font-tech);
        font-size: 12px;
        font-weight: 600;
        letter-spacing: 0.5px;
        cursor: pointer;
        transition: all 0.25s;
      }
      .cca-muscle-btn:hover {
        background: color-mix(in srgb, var(--primary) 12%, transparent);
        border-color: color-mix(in srgb, var(--primary) 30%, transparent);
        color: var(--text-1);
      }
      /* --primary-text, not --primary: the raw brand orange measured 2.44:1 as
         text on the light-theme tint here, failing WCAG AA. */
      .cca-muscle-btn.active {
        background: color-mix(in srgb, var(--primary) 15%, transparent);
        border-color: var(--primary);
        color: var(--primary-text);
      }
      .cca-muscle-btn .btn-dot {
        display: inline-block;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        margin-right: 6px;
      }
    </style>
  `;

  let currentMuscle = null;
  let onSelectCb = null;

  function init(containerSelector, opts = {}) {
    const container = typeof containerSelector === 'string'
      ? document.querySelector(containerSelector)
      : containerSelector;
    if (!container) return;

    onSelectCb = opts.onSelect || null;

    /* Build HTML */
    const anatomyMarkup = opts.showAnatomy === false ? '' : SVG_TEMPLATE;
    container.innerHTML = STYLE + `
      <div class="cca-muscle-map">
        ${anatomyMarkup}
        <div class="cca-muscle-buttons" id="muscleButtons"></div>
      </div>
    `;

    /* Create filter buttons */
    const btnContainer = container.querySelector('#muscleButtons');
    const allBtn = document.createElement('button');
    allBtn.className = 'cca-muscle-btn active';
    allBtn.innerHTML = 'All Muscles';
    allBtn.addEventListener('click', () => selectMuscle(null, container));
    btnContainer.appendChild(allBtn);

    Object.entries(MUSCLES).forEach(([key, data]) => {
      const btn = document.createElement('button');
      btn.className = 'cca-muscle-btn';
      btn.dataset.muscle = key;
      btn.innerHTML = `<span class="btn-dot" style="background:${data.color}"></span>${data.label}`;
      btn.addEventListener('click', () => selectMuscle(key, container));
      btnContainer.appendChild(btn);
    });

    /* SVG click handling */
    container.querySelectorAll('.muscle-region').forEach(el => {
      el.addEventListener('click', () => {
        const muscle = el.dataset.muscle;
        selectMuscle(muscle === currentMuscle ? null : muscle, container);
      });
    });
  }

  function selectMuscle(muscle, container) {
    currentMuscle = muscle;

    /* Update SVG highlights */
    container.querySelectorAll('.muscle-region').forEach(el => {
      el.classList.toggle('active', muscle && el.dataset.muscle === muscle);
    });

    /* Update button states */
    container.querySelectorAll('.cca-muscle-btn').forEach(btn => {
      if (!btn.dataset.muscle) {
        btn.classList.toggle('active', !muscle);
      } else {
        btn.classList.toggle('active', btn.dataset.muscle === muscle);
      }
    });

    /* Callback */
    if (onSelectCb) onSelectCb(muscle, MUSCLES[muscle] || null);
  }

  function getSelected() { return currentMuscle; }
  function getMuscleData() { return MUSCLES; }

  return { init, getSelected, getMuscleData };
})();
