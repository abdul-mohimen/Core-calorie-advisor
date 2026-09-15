# CORE CALORIE ADVISOR — MASTER PROMPT v4 (MOTION + TYPE + CARD REDESIGN)

> **Extends, does not replace:** `CLAUDE.md` and `MASTER_PROMPT_v3_PREMIUM.md` are still in
> force. Where this document is silent, those two win. Where this document explicitly
> overrides a rule (see §0.2), **this document wins for this task only.**

> ### How to run this in Google Antigravity
> Two ways to load it — do either, not both:
> 1. **One-off task:** open a new Agent Manager task in Antigravity and paste:
>    ```
>    Read CLAUDE.md, MASTER_PROMPT_v3_PREMIUM.md, and MASTER_PROMPT_v4_MOTION_TYPE_REDESIGN.md
>    end to end. Then execute PHASE MR-0 only. Stop and report using the format in §0.4.
>    Wait for my literal reply "APPROVED MR-0" before starting MR-1.
>    ```
> 2. **Persistent context:** save this file's content into `AGENTS.md` (or `GEMINI.md`) at the
>    repo root. Antigravity reads it automatically at the start of every session — you won't
>    need to re-paste it, but you should still gate each phase with an explicit "APPROVED".
>
> Either way: **one phase at a time, wait for written approval between phases.** This is the
> same discipline `MASTER_PROMPT_v3_PREMIUM.md` already uses — keep it, don't let the agent
> batch phases because it "seems efficient."

---

## 0. CONTRACT ADDENDUM

### 0.1 What this task is
Add a **motion + type + card layer** on top of the existing 2026 visual redesign (primary
`#FF5722`, Bricolage Grotesque / Space Grotesk / Manrope, `--card-radius: 20px`, etc. — all
already live in `assets/css/style.css`). This task does **not** touch colors or fonts again;
it touches *how things move and how text is sized*.

Trigger: the owner wants the scroll/reveal/hover **feel** of `arstraumur.music/remixes` — a
dark, editorial music-artist site (card grid of releases, generous whitespace, smooth scroll,
staggered reveal-on-scroll, restrained hover micro-interactions) — applied to this fitness
platform's cards, headings, and font sizing.

### 0.2 Explicit rule override — read this before touching `assets/js/`
`MASTER_PROMPT_v3_PREMIUM.md` §0.1.4 bans new dependencies. **That rule is not being broken —
it's being satisfied a different way.** Everything specified below is implemented in **plain
CSS + vanilla JS, zero new `<script src>` tags to a CDN, zero npm/composer packages.** No GSAP,
no Lenis, no new webfonts. If a future phase genuinely wants to add GSAP/Lenis for a richer
feel, that requires a separate, explicit "APPROVED — dependency exception" from the owner,
same as any other §0.1.4 exception. Default path below does not need that.

*(Why zero-dependency is the right call here, not just the safe one: the last pass took the
homepage hero video from 136 MB to 7.3 MB specifically to fix load performance. Bolting 45–90 KB
of animation libraries onto that same page would spend back a chunk of what was just saved,
for an effect vanilla `IntersectionObserver` + CSS transitions can already deliver at ~95% of
the feel. If Lighthouse numbers after MR-6 say otherwise, that's a legitimate reason to revisit
— but prove it with a number, not a preference — per Rule 0.1.1.)*

### 0.3 IP boundary (same spirit as v3 §0.1.7)
`arstraumur.music` is a real, independent artist's commercial site. Nothing here means: reuse
their images, their copy, their exact color values, their exact layout grid, or their code (we
don't have their code — no one fetched it, it's JS-rendered and wasn't inspected beyond public
page content). What's being borrowed is a **motion vocabulary** that's common across the whole
genre of editorial/music/agency sites — smooth scroll, staggered fade-up reveal, image-scale
hover — described from first principles below, not copied from a source file. Original
implementation only, same as always.

### 0.4 Per-phase report format
Reuse `MASTER_PROMPT_v3_PREMIUM.md` §0.2 exactly: **What I found / What I propose / Files I
will touch / What I will delete / Verification plan / Open questions / Roman Urdu summary (5
lines).** Do not invent a new format for this task.

### 0.5 Environment facts specific to this task (don't rediscover these)
- CSS load order (`includes/header.php`): `style.css` → `layout.css` → `hero-system.css` →
  `portals/{portal}.css` → `cca-tw-bridge.css` → `cca-cards.css` → `premium-ui.css` (loaded
  last → wins the cascade on anything it touches).
- Card systems already share tokens: `--card-radius`, `--card-pad`, `--card-gap`,
  `--card-media-h`, `--card-lift`, `--shadow-glow` (all defined once, consumed by
  `.cca-card`, `.portal-card`, `.plan-card`, `.checkout-card`, `.wk-card`, `.cat-card`).
  **Reuse these tokens. Do not invent parallel ones.**
- No build step, no bundler, no `node_modules` in the live app. Any new JS is a plain file
  loaded via `<script defer src="...">`. **Find where existing page JS is loaded** (check
  `includes/footer.php` first, that's the conventional spot in this codebase for end-of-body
  scripts) and match that pattern — don't assume, verify with `grep -n "<script" includes/footer.php includes/header.php`.
- `prefers-reduced-motion` is not currently handled anywhere in the CSS. It must be added as
  part of this work, not treated as optional polish — see §3.5.

---

## 1. THE REFERENCE, TRANSLATED

What was actually observed at `arstraumur.music/remixes` (page content/structure, fetched
2026-09-15 — the JS/CSS implementation itself was **not** inspected, it wasn't accessible):
dark theme (`#04060c`), a card grid where each card is *cover art + date + artist + title +
an embedded player*, minimal top nav, generous negative space, "Deep space · collaborations"
as the tagline. It's built by a boutique studio that specializes in artist websites — that
genre of site is near-universally built on: inertia/smooth scroll, elements fading/sliding up
as they cross into view (usually staggered across a grid), and a restrained hover state on
each card (slight image scale + shadow lift, nothing flashy).

**Translation to this app** — same motion vocabulary, different content:

| Their pattern | This app's equivalent |
|---|---|
| Remix card (cover + meta + player) fades up on scroll | `.cca-card` / `.wk-card` / `.plan-card` / `.portal-card` fades up on scroll |
| Smooth inertia scroll down the release list | Smooth scroll site-wide, same rAF technique |
| Cover art scales slightly on hover | Card media (`.wk-card__media`, hero images) scales slightly on hover |
| Minimal, editorial headline treatment | Hero `<h1>` and every section `<h2>` gets a reveal-on-scroll, not just the hero |
| — (no direct equivalent) | **New idea, not in the reference:** kcal/macro/stat numbers count up from 0 when they scroll into view. This is the fitness-specific extension of the same "editorial reveal" idea — their content is music metadata, this app's content is numbers, so the reveal should animate the numbers, not just fade a static digit in. |

---

## 2. TYPE SCALE — fluid, `clamp()`-based

Add to `assets/css/style.css` `:root`, near the existing `--font-disp/--font-tech/--font-body`
tokens (do not remove those — this adds *sizes*, they already own *families*):

```css
/* FLUID TYPE SCALE — MR-1. clamp(min, preferred, max); preferred uses vw so it scales
   smoothly between the min at ~360px viewport and max at ~1600px+, no breakpoint jumps. */
--fs-display:  clamp(2.75rem, 2rem + 3.5vw, 5.5rem);    /* hero H1 */
--fs-h2:       clamp(2rem, 1.65rem + 1.6vw, 3.25rem);   /* section headers */
--fs-h3:       clamp(1.375rem, 1.2rem + 0.8vw, 1.875rem); /* card/portal titles */
--fs-h4:       clamp(1.125rem, 1.05rem + 0.35vw, 1.375rem); /* card headings */
--fs-body:     clamp(0.9375rem, 0.9rem + 0.15vw, 1rem);  /* barely moves, protects readability */
--fs-small:    clamp(0.8125rem, 0.8rem + 0.1vw, 0.875rem); /* meta rows, labels */
--fs-stat:     clamp(2.25rem, 1.8rem + 2vw, 3.75rem);    /* big kcal/macro numbers */

--tracking-display: -0.03em;
--tracking-h2:      -0.02em;
--tracking-label:   0.04em;   /* uppercase eyebrow/meta labels */
```

Apply via `font-size: var(--fs-h2)` etc. Do **not** hunt-and-replace every hardcoded `font-size`
in one pass — that's how the last codebase got 226 stray hex codes. Instead: grep every
`font-size:` in `assets/css/*.css`, group by which tier each one is closest to, and replace
group-by-group so each swap is checkable in one diff. `--fs-body` moves by less than 1px across
the whole viewport range on purpose — this pass is about hierarchy (making H1/H2/stat numbers
noticeably bigger and more confident), not about relitigating body-text readability, which the
existing WCAG contrast work already handles.

---

## 3. MOTION SYSTEM — new file, zero dependencies

### 3.1 New file: `assets/js/motion.js`

Single responsibility file. Three independent pieces, each guarded by
`prefers-reduced-motion`. Skeleton (Antigravity: flesh this out, keep the structure and the
reduced-motion guards intact):

```js
// assets/js/motion.js
(function () {
  'use strict';
  const REDUCED = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ---------- 1. Scroll-reveal (IntersectionObserver, no library) ---------- */
  function initReveal() {
    const targets = document.querySelectorAll(
      '.cca-card, .portal-card, .plan-card, .checkout-card, .wk-card, .cat-card, ' +
      '.cca-hero__title, section > h2, .cca-hero__kpi'
    );
    if (REDUCED || !('IntersectionObserver' in window)) {
      targets.forEach(el => el.classList.add('is-visible'));
      return;
    }
    // Stagger: index within each parent, not global — so each grid restarts its own count.
    const groups = new Map();
    targets.forEach(el => {
      const key = el.parentElement;
      const i = groups.get(key) || 0;
      el.style.setProperty('--reveal-i', i);
      groups.set(key, i + 1);
      el.classList.add('reveal');
    });
    const io = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          io.unobserve(entry.target);
        }
      });
    }, { threshold: 0.15, rootMargin: '0px 0px -8% 0px' });
    targets.forEach(el => io.observe(el));
  }

  /* ---------- 2. Smooth scroll (hand-rolled rAF lerp, ~40 lines, no Lenis) ---------- */
  function initSmoothScroll() {
    if (REDUCED) return; // native instant scroll is the CORRECT reduced-motion behavior
    let current = window.scrollY, target = window.scrollY, ticking = false;
    const ease = 0.09;
    window.addEventListener('scroll', () => { target = window.scrollY; }, { passive: true });
    function loop() {
      current += (target - current) * ease;
      if (Math.abs(target - current) < 0.5) current = target;
      // Applied via a wrapper transform, NOT overriding native scroll — see note below.
      document.documentElement.style.setProperty('--scroll-y', current.toFixed(1) + 'px');
      requestAnimationFrame(loop);
    }
    requestAnimationFrame(loop);
  }

  /* ---------- 3. Count-up stat numbers ---------- */
  function initCountUp() {
    const nodes = document.querySelectorAll('[data-count-to]');
    if (!nodes.length) return;
    const run = (el) => {
      const to = parseFloat(el.dataset.countTo);
      const decimals = (el.dataset.countTo.split('.')[1] || '').length;
      if (REDUCED) { el.textContent = to.toLocaleString(undefined, {maximumFractionDigits: decimals}); return; }
      const dur = 900, start = performance.now(), from = 0;
      function tick(now) {
        const p = Math.min(1, (now - start) / dur);
        const eased = 1 - Math.pow(1 - p, 3); // cubic ease-out
        el.textContent = (from + (to - from) * eased).toLocaleString(undefined, {maximumFractionDigits: decimals});
        if (p < 1) requestAnimationFrame(tick);
      }
      requestAnimationFrame(tick);
    };
    const io = new IntersectionObserver((entries) => {
      entries.forEach(e => { if (e.isIntersecting) { run(e.target); io.unobserve(e.target); } });
    }, { threshold: 0.5 });
    nodes.forEach(el => io.observe(el));
  }

  document.addEventListener('DOMContentLoaded', () => {
    initReveal();
    initSmoothScroll();
    initCountUp();
  });
})();
```

**Important implementation note for §3.1.2 (smooth scroll):** driving smooth scroll via a
`--scroll-y` custom property + `transform: translateY(calc(var(--scroll-y) * -1))` on a
`.smooth-wrapper` element (with `position: fixed` sizing tricks) is the standard
library-free technique — but it changes how the page's height/scrollbar works and needs care
around `position: sticky`/`fixed` children (the nav, the sidebar in portal layouts). **This is
the one piece of §3 with real layout risk.** Antigravity: prototype this in isolation on the
homepage only first, verify the portal sidebars (`layout.css`) still behave, *then* roll out —
don't apply site-wide in the same commit as the reveal/count-up pieces, which carry no such
risk. If it fights the sticky sidebar, the fallback is `html { scroll-behavior: smooth; }`
(one line, native, zero risk, weaker effect) — ship that instead rather than fighting it for
hours. Report which one shipped in the MR-4 phase report.

### 3.2 CSS for the reveal system — add to `assets/css/style.css`

```css
.reveal {
  opacity: 0;
  transform: translateY(36px);
  transition: opacity 640ms var(--ease-in-out), transform 640ms var(--ease-in-out);
  transition-delay: calc(var(--reveal-i, 0) * 70ms);
}
.reveal.is-visible { opacity: 1; transform: translateY(0); }

@media (prefers-reduced-motion: reduce) {
  .reveal { opacity: 1; transform: none; transition: none; }
}
```

### 3.3 Hover micro-interaction — extend existing card CSS, don't replace it

`premium-ui.css` already has the hover shadow-glow from the previous pass. Add an image-scale
companion (this is the specific "cover art zooms slightly on hover" feel from the reference):

```css
.wk-card__media, .cca-card__media, .portal-card__media { overflow: hidden; }
.wk-card__media img, .cca-card__media img, .portal-card__media img {
  transition: transform 480ms var(--ease-in-out);
}
.wk-card:hover .wk-card__media img,
.cca-card:hover .cca-card__media img,
.portal-card:hover .portal-card__media img { transform: scale(1.055); }

@media (prefers-reduced-motion: reduce) {
  .wk-card__media img, .cca-card__media img, .portal-card__media img { transition: none; }
}
```

*(Verify the actual media-wrapper class names before pasting — `.wk-card__media` etc. are the
pattern used elsewhere in `cca-cards.css`; confirm exact names with `grep -n "__media" assets/css/cca-cards.css` since this master prompt is written from memory of the architecture, not a fresh read of every class on this date.)*

### 3.4 Hero headline reveal — `hero-system.css`

The `.cca-hero__title` already exists and already has the `.grad` gradient span from the last
pass. Add it to the `initReveal()` target list (already done above) and give it a slightly
larger translate distance than cards for a more "headline" feel:

```css
.cca-hero__title.reveal { transform: translateY(28px); }
```

### 3.5 `prefers-reduced-motion` — mandatory, not optional
Every rule added in this phase that animates `transform`/`opacity` must have a
`prefers-reduced-motion: reduce` counterpart that removes the transition and shows the final
state immediately. This is already written into every snippet above — **do not strip it out
to "simplify" the diff.** This is a hard rule for this task, same weight as v3's §0.1 rules.

---

## 4. CARD LAYOUT REFINEMENT

- Swap fixed `--card-media-h: 196px` for `aspect-ratio: 4 / 3` on card media containers where
  the image is decorative (not on anything showing a data chart/table). Fixed height + `object-fit:
  cover` already handles cropping; `aspect-ratio` just makes the grid feel more editorial/consistent
  across different source-image dimensions, matching the reference's uniform grid rhythm.
- Meta rows (date/kcal/tag/category) get `font-size: var(--fs-small)`, `letter-spacing:
  var(--tracking-label)`, `text-transform: uppercase`, and reduced opacity (`color:
  var(--text-3)` — already-existing WCAG-safe token, don't invent a new muted color).
- Card titles get `font-size: var(--fs-h4)` (or `--fs-h3` for the larger portal/plan cards —
  match by current visual weight, not blindly).

---

## 5. PHASED PLAN

| Phase | Scope | Depends on |
|---|---|---|
| **MR-0** | Pre-flight: confirm zero-dependency path (§0.2), locate the correct script-loading file, grep the real `__media` class names, report back before writing any code | — |
| **MR-1** | Add the fluid type-scale tokens (§2), apply to headings + card titles across `style.css`/`cca-cards.css`/`premium-ui.css`/`hero-system.css`. No JS yet. | MR-0 |
| **MR-2** | Ship `assets/js/motion.js` with **reveal + count-up only** (§3.1.1, §3.1.3, §3.2). Wire `[data-count-to]` onto the existing kcal/macro number displays (find them, don't guess — `grep -rn "kcal\|macro" member/ pages/` first). | MR-1 |
| **MR-3** | Card hover media-scale (§3.3). | MR-1 |
| **MR-4** | Smooth scroll (§3.1.2) — homepage-only first per the risk note, then decide site-wide rollout. | MR-2 |
| **MR-5** | Card layout refinement (§4). | MR-1, MR-3 |
| **MR-6** | Cross-portal QA — see §6. | all above |

Standard gate: **stop after each phase, wait for "APPROVED MR-\<n\>".**

---

## 6. QA CHECKLIST (MR-6 — Antigravity self-verifies against this before reporting done)

- [ ] `prefers-reduced-motion: reduce` tested (DevTools → Rendering tab → Emulate CSS media
      feature) on: homepage, one portal dashboard, the workout player. All three: no motion,
      content visible immediately, nothing stuck at `opacity: 0`.
- [ ] All 5 portals checked (`admin`, `doctor`, `member`, `trainer`, `patient`) — reveal
      classes apply cleanly, no card stuck invisible because its parent never got observed.
- [ ] Mobile viewport (375px) — stagger delays don't make single-column card lists feel
      sluggish (if grid collapses to 1 column, consider capping `--reveal-i` or shortening the
      70ms step on narrow viewports).
- [ ] Lighthouse performance score, homepage, before/after — report the actual numbers, per
      Rule 0.1.1 (evidence or silence). This is the number that would justify (or not) ever
      revisiting the GSAP/Lenis exception in §0.2.
- [ ] No new `<script src="https://...">` CDN tags were added anywhere (`grep -rn "cdn\." includes/ pages/` should show the same results as before this task started).
- [ ] Existing 3D player (`titan3d.js`) and workout player (`player.js`) still function —
      this task didn't touch them, confirm nothing else did either.
- [ ] Count-up numbers show the *correct final value* even if JS fails to load (i.e. the
      static HTML value in `data-count-to`'s sibling text node, or the element's own text
      content before JS runs, must already be the real number — count-up is progressive
      enhancement, not the only source of truth for a number this app displays).

---

## 7. ROMAN URDU — ek line summary is doc ka

Ye doc sirf **motion + typography + card polish** ke liye hai — koi naya library nahi, koi
color/font dobara nahi badla ja raha (woh pichli pass mein ho chuka), aur har animation
`prefers-reduced-motion` ko respect karti hai. Antigravity ko MR-0 se start karwana, aur har
phase ke baad khud "APPROVED MR-X" likhna — jaldi mein saari phases ek sath mat chalwana.
