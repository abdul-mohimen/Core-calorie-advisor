/* ============ CORE CALORIE ADVISOR — Hero Particle Engine ============
   Lightweight WebGL particle system for portal hero backgrounds.
   Usage: CCAParticles.init(canvasElement, { portal: 'member' })
   Falls back to CSS animated gradient if WebGL unavailable.
   ================================================================== */

const CCAParticles = (() => {
  'use strict';

  /* Portal accent colour palette */
  const PALETTE = {
    member:  { primary: [1.0, 0.42, 0.10], secondary: [1.0, 0.75, 0.18], ambient: [0.08, 0.06, 0.02] },
    patient: { primary: [0.23, 0.51, 0.97], secondary: [0.36, 0.73, 0.98], ambient: [0.02, 0.04, 0.08] },
    trainer: { primary: [0.06, 0.73, 0.50], secondary: [0.20, 0.83, 0.60], ambient: [0.02, 0.07, 0.04] },
    doctor:  { primary: [0.55, 0.36, 0.96], secondary: [0.73, 0.50, 1.0],  ambient: [0.05, 0.03, 0.08] },
    admin:   { primary: [0.96, 0.62, 0.04], secondary: [1.0, 0.85, 0.25],  ambient: [0.08, 0.06, 0.01] },
    default: { primary: [1.0, 0.42, 0.10], secondary: [0.22, 0.74, 0.97], ambient: [0.05, 0.04, 0.06] },
  };

  const PARTICLE_COUNT = 80;
  const MAX_SPEED = 0.4;

  let gl, program, particles, posBuffer, colBuffer, sizeBuffer;
  let width, height, dpr, animId;
  let canvas, isRunning = false;

  /* ---- GLSL shaders (minimal, performant) ---- */
  const VERT = `
    attribute vec2 aPos;
    attribute vec3 aCol;
    attribute float aSize;
    varying vec3 vCol;
    void main() {
      vCol = aCol;
      gl_Position = vec4(aPos, 0.0, 1.0);
      gl_PointSize = aSize;
    }
  `;

  const FRAG = `
    precision mediump float;
    varying vec3 vCol;
    void main() {
      vec2 uv = gl_PointCoord - 0.5;
      float d = length(uv);
      if (d > 0.5) discard;
      float alpha = smoothstep(0.5, 0.15, d) * 0.6;
      gl_FragColor = vec4(vCol, alpha);
    }
  `;

  function createShader(type, src) {
    const s = gl.createShader(type);
    gl.shaderSource(s, src);
    gl.compileShader(s);
    if (!gl.getShaderParameter(s, gl.COMPILE_STATUS)) {
      console.warn('CCAParticles shader error:', gl.getShaderInfoLog(s));
      gl.deleteShader(s);
      return null;
    }
    return s;
  }

  function initGL(cvs) {
    canvas = cvs;
    gl = canvas.getContext('webgl', { alpha: true, antialias: false, premultipliedAlpha: false });
    if (!gl) return false;

    const vs = createShader(gl.VERTEX_SHADER, VERT);
    const fs = createShader(gl.FRAGMENT_SHADER, FRAG);
    if (!vs || !fs) return false;

    program = gl.createProgram();
    gl.attachShader(program, vs);
    gl.attachShader(program, fs);
    gl.linkProgram(program);
    if (!gl.getProgramParameter(program, gl.LINK_STATUS)) return false;

    posBuffer = gl.createBuffer();
    colBuffer = gl.createBuffer();
    sizeBuffer = gl.createBuffer();

    return true;
  }

  function createParticles(portal) {
    const pal = PALETTE[portal] || PALETTE.default;
    particles = [];

    for (let i = 0; i < PARTICLE_COUNT; i++) {
      const useSecondary = Math.random() > 0.6;
      const col = useSecondary ? pal.secondary : pal.primary;
      const brightness = 0.5 + Math.random() * 0.5;

      particles.push({
        x: Math.random() * 2 - 1,
        y: Math.random() * 2 - 1,
        vx: (Math.random() - 0.5) * MAX_SPEED * 0.01,
        vy: (Math.random() - 0.5) * MAX_SPEED * 0.01,
        r: col[0] * brightness,
        g: col[1] * brightness,
        b: col[2] * brightness,
        size: 2 + Math.random() * 5,
        phase: Math.random() * Math.PI * 2,
      });
    }
  }

  function resize() {
    dpr = Math.min(window.devicePixelRatio || 1, 2);
    width = canvas.clientWidth;
    height = canvas.clientHeight;
    canvas.width = width * dpr;
    canvas.height = height * dpr;
    if (gl) gl.viewport(0, 0, canvas.width, canvas.height);
  }

  function draw(time) {
    if (!isRunning) return;
    animId = requestAnimationFrame(draw);

    const t = time * 0.001;
    const posArr = new Float32Array(PARTICLE_COUNT * 2);
    const colArr = new Float32Array(PARTICLE_COUNT * 3);
    const sizeArr = new Float32Array(PARTICLE_COUNT);

    for (let i = 0; i < PARTICLE_COUNT; i++) {
      const p = particles[i];

      /* Organic drift motion */
      p.x += p.vx + Math.sin(t * 0.3 + p.phase) * 0.0003;
      p.y += p.vy + Math.cos(t * 0.2 + p.phase) * 0.0003;

      /* Wrap edges */
      if (p.x > 1.1) p.x = -1.1;
      if (p.x < -1.1) p.x = 1.1;
      if (p.y > 1.1) p.y = -1.1;
      if (p.y < -1.1) p.y = 1.1;

      /* Breathing size animation */
      const sizeScale = 1 + Math.sin(t * 0.8 + p.phase) * 0.3;

      posArr[i * 2]     = p.x;
      posArr[i * 2 + 1] = p.y;
      colArr[i * 3]     = p.r;
      colArr[i * 3 + 1] = p.g;
      colArr[i * 3 + 2] = p.b;
      sizeArr[i]        = p.size * sizeScale * dpr;
    }

    gl.clearColor(0, 0, 0, 0);
    gl.clear(gl.COLOR_BUFFER_BIT);
    gl.enable(gl.BLEND);
    gl.blendFunc(gl.SRC_ALPHA, gl.ONE);

    gl.useProgram(program);

    /* Position */
    const aPosLoc = gl.getAttribLocation(program, 'aPos');
    gl.bindBuffer(gl.ARRAY_BUFFER, posBuffer);
    gl.bufferData(gl.ARRAY_BUFFER, posArr, gl.DYNAMIC_DRAW);
    gl.enableVertexAttribArray(aPosLoc);
    gl.vertexAttribPointer(aPosLoc, 2, gl.FLOAT, false, 0, 0);

    /* Color */
    const aColLoc = gl.getAttribLocation(program, 'aCol');
    gl.bindBuffer(gl.ARRAY_BUFFER, colBuffer);
    gl.bufferData(gl.ARRAY_BUFFER, colArr, gl.DYNAMIC_DRAW);
    gl.enableVertexAttribArray(aColLoc);
    gl.vertexAttribPointer(aColLoc, 3, gl.FLOAT, false, 0, 0);

    /* Size */
    const aSizeLoc = gl.getAttribLocation(program, 'aSize');
    gl.bindBuffer(gl.ARRAY_BUFFER, sizeBuffer);
    gl.bufferData(gl.ARRAY_BUFFER, sizeArr, gl.DYNAMIC_DRAW);
    gl.enableVertexAttribArray(aSizeLoc);
    gl.vertexAttribPointer(aSizeLoc, 1, gl.FLOAT, false, 0, 0);

    gl.drawArrays(gl.POINTS, 0, PARTICLE_COUNT);
  }

  /* ---- Public API ---- */
  function init(canvasEl, opts = {}) {
    if (!canvasEl) return;
    const portal = opts.portal || 'default';

    /* Reduced motion: skip particles entirely */
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    if (!initGL(canvasEl)) {
      /* Fallback: CSS animated gradient (add class, no WebGL needed) */
      canvasEl.parentElement?.classList.add('cca-hero--gradient-fallback');
      canvasEl.style.display = 'none';
      return;
    }

    createParticles(portal);
    resize();
    window.addEventListener('resize', resize);
    isRunning = true;
    requestAnimationFrame(draw);
  }

  function destroy() {
    isRunning = false;
    if (animId) cancelAnimationFrame(animId);
    window.removeEventListener('resize', resize);
    if (gl) {
      gl.deleteProgram(program);
      gl.deleteBuffer(posBuffer);
      gl.deleteBuffer(colBuffer);
      gl.deleteBuffer(sizeBuffer);
    }
  }

  return { init, destroy };
})();

/* Auto-initialize on DOM ready if a hero particle canvas exists */
document.addEventListener('DOMContentLoaded', () => {
  const cvs = document.getElementById('heroParticles');
  if (cvs) {
    const portal = document.getElementById('app')?.dataset?.portal || 'default';
    CCAParticles.init(cvs, { portal });
  }
});
