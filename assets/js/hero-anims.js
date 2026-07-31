/* ============ CORE CALORIE ADVISOR — Interactive 3D Particle Net Backgrounds ============
   Automatically looks for elements with class .tf-hero-anim and creates a highly
   performant, responsive 3D floating particle net on an HTML5 canvas.
   Responsive to light/dark themes and mouse movement. */
(function() {
  'use strict';

  function initHeroAnim(container) {
    if (container.querySelector('.tf-hero-canvas')) return; // already loaded

    const canvas = document.createElement('canvas');
    canvas.className = 'tf-hero-canvas';
    canvas.style.cssText = 'position:absolute;top:0;left:0;width:100%;height:100%;z-index:1;pointer-events:none;opacity:0.65;';
    container.style.position = 'relative';
    
    // Ensure existing text content remains above the canvas.
    // NOTE: absolutely/fixed-positioned children are skipped entirely. They are
    // overlay scrims (e.g. `class="absolute inset-0 bg-black/80 z-0"`), and
    // forcing position:relative on them cancelled their inset-0 stretch, so they
    // collapsed to 0x0 and stopped darkening the hero photo — which made white
    // hero headings unreadable over bright images (pages/pricing.php). Their own
    // z-index is also left alone so the authored stacking order is preserved.
    Array.from(container.children).forEach(child => {
      const cs = window.getComputedStyle(child);
      if (cs.position === 'absolute' || cs.position === 'fixed') return;
      if (cs.zIndex === 'auto' || cs.zIndex === '0') {
        child.style.position = 'relative';
        child.style.zIndex = '2';
      }
    });

    container.insertBefore(canvas, container.firstChild);

    const ctx = canvas.getContext('2d');
    let w = canvas.width = container.clientWidth;
    let h = canvas.height = container.clientHeight;

    const points = [];
    const maxPoints = Math.min(60, Math.floor((w * h) / 12000)); // density-based point limit
    const connectionDist = 110;

    let mouse = { x: null, y: null, targetX: null, targetY: null };

    // Create points with 3D projection parameters
    for (let i = 0; i < maxPoints; i++) {
      points.push({
        x: Math.random() * w,
        y: Math.random() * h,
        z: Math.random() * 200 + 50, // 3D depth
        vx: (Math.random() - 0.5) * 0.5,
        vy: (Math.random() - 0.5) * 0.5,
        vz: (Math.random() - 0.5) * 0.2,
        r: Math.random() * 1.5 + 1.2
      });
    }

    // Tracks mouse pos relative to canvas bounds
    container.addEventListener('mousemove', (e) => {
      const rect = container.getBoundingClientRect();
      mouse.targetX = e.clientX - rect.left;
      mouse.targetY = e.clientY - rect.top;
    });

    container.addEventListener('mouseleave', () => {
      mouse.targetX = null;
      mouse.targetY = null;
    });

    function draw() {
      if (!container.isConnected) return; // skip if container removed
      
      // Smooth mouse lerp
      if (mouse.targetX !== null) {
        if (mouse.x === null) { mouse.x = mouse.targetX; mouse.y = mouse.targetY; }
        else {
          mouse.x += (mouse.targetX - mouse.x) * 0.1;
          mouse.y += (mouse.targetY - mouse.y) * 0.1;
        }
      } else {
        mouse.x = null;
        mouse.y = null;
      }

      ctx.clearRect(0, 0, w, h);

      // Fetch dynamic colors based on theme tokens
      const isLight = document.documentElement.dataset.theme === 'light';
      const particleColor = isLight ? 'rgba(232, 93, 0, 0.45)' : 'rgba(255, 107, 26, 0.5)';
      const lineColor = isLight ? 'rgba(232, 93, 0, 0.12)' : 'rgba(255, 107, 26, 0.15)';
      const hoverLineColor = isLight ? 'rgba(232, 93, 0, 0.25)' : 'rgba(255, 184, 0, 0.35)';

      // Update positions and project onto 2D screen
      for (let i = 0; i < points.length; i++) {
        const p = points[i];

        p.x += p.vx;
        p.y += p.vy;
        p.z += p.vz;

        // 3D box boundary collision
        if (p.x < 0 || p.x > w) p.vx *= -1;
        if (p.y < 0 || p.y > h) p.vy *= -1;
        if (p.z < 50 || p.z > 250) p.vz *= -1;

        // Interaction with mouse cursor (drift away slightly)
        if (mouse.x !== null) {
          const dx = mouse.x - p.x;
          const dy = mouse.y - p.y;
          const dist = Math.sqrt(dx * dx + dy * dy);
          if (dist < 130) {
            const force = (130 - dist) / 130;
            p.x -= (dx / dist) * force * 1.5;
            p.y -= (dy / dist) * force * 1.5;
          }
        }

        // Project radius based on 3D depth
        const scale = 150 / p.z;
        const screenR = p.r * scale;

        ctx.beginPath();
        ctx.arc(p.x, p.y, screenR, 0, Math.PI * 2);
        ctx.fillStyle = particleColor;
        ctx.fill();
      }

      // Draw connection lines
      for (let i = 0; i < points.length; i++) {
        const p1 = points[i];
        for (let j = i + 1; j < points.length; j++) {
          const p2 = points[j];
          const dx = p1.x - p2.x;
          const dy = p1.y - p2.y;
          const dist = Math.sqrt(dx * dx + dy * dy);

          if (dist < connectionDist) {
            const alpha = (1 - dist / connectionDist) * 0.8;
            ctx.beginPath();
            ctx.moveTo(p1.x, p1.y);
            ctx.lineTo(p2.x, p2.y);
            
            // Highlight connections near mouse
            let isNearMouse = false;
            if (mouse.x !== null) {
              const m1 = Math.sqrt(Math.pow(mouse.x - p1.x, 2) + Math.pow(mouse.y - p1.y, 2));
              const m2 = Math.sqrt(Math.pow(mouse.x - p2.x, 2) + Math.pow(mouse.y - p2.y, 2));
              if (m1 < 90 || m2 < 90) isNearMouse = true;
            }

            ctx.strokeStyle = isNearMouse ? hoverLineColor : lineColor;
            ctx.lineWidth = (isNearMouse ? 0.9 : 0.5) * alpha;
            ctx.stroke();
          }
        }
      }

      requestAnimationFrame(draw);
    }

    // Resize handler
    function resize() {
      w = canvas.width = container.clientWidth;
      h = canvas.height = container.clientHeight;
    }
    window.addEventListener('resize', resize);

    // Stop execution when scrolled out of view (saves battery/perf)
    let isVisible = true;
    if ('IntersectionObserver' in window) {
      const io = new IntersectionObserver((entries) => {
        isVisible = entries[0].isIntersecting;
      }, { threshold: 0.05 });
      io.observe(container);
    }

    function loop() {
      if (isVisible) draw();
      else requestAnimationFrame(loop);
    }

    loop();
  }

  // Auto-scan document for targets
  function scan() {
    document.querySelectorAll('.tf-hero-anim').forEach(initHeroAnim);
  }

  // Export & Initialize
  window.TF_HeroAnims = { scan: scan };
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', scan);
  } else {
    scan();
  }
})();
