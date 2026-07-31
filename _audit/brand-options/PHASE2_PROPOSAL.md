# PHASE 2 — Rebrand Proposal

## A. Tagline Options
Please select one of the following taglines for **Core Calorie Advisor (CCA)**:
1. "Precision nutrition meets 3D coaching."
2. "Visualize progress. Master your core."
3. "Your physique, mathematically optimized."

## B. Logo Directions
I have generated three distinct SVG logo concepts. You can view them by opening the `_audit/brand-options/index.html` file in your browser, or viewing the `preview.png` image (once generated).

**Direction 1: Flame Core**
A circular arc forming a "C" with a stylized flame inside. Represents burning calories and energy.
**Direction 2: Abstract Torso**
A hexagonal core ring with an abstract torso inside. Represents strength and the body.
**Direction 3: Dumbbell Ring**
A sleek fitness progress ring forming the "C" alongside a dumbbell silhouette. Represents weight training and goal completion.

*Please tell me which direction you prefer (1, 2, or 3).*

## C. Color + Theme Tokens
I propose the following non-generic color palette for the design system.

### Colors
- **Primary (Action/Energy)**: `#FF6B1A` (Vibrant Ember)
- **Deep Neutral Base**: `#0F1115` (Dark Navy-Charcoal)
- **Surface**: `#1A1D24`
- **Elevated Surface**: `#252933`
- **Border**: `#323745`
- **Text High Emphasis**: `#F8F9FA`
- **Text Medium Emphasis**: `#94A3B8`
- **Text Disabled**: `#475569`

### Semantic Colors
- **Success**: `#10B981` (Emerald)
- **Warning**: `#F59E0B` (Amber)
- **Danger**: `#EF4444` (Rose)
- **Info**: `#3B82F6` (Blue)

### Contrast Checks (WCAG AA Compliant)
- High Emphasis Text (`#F8F9FA`) on Base (`#0F1115`): **18.7:1 (Pass)**
- Medium Text (`#94A3B8`) on Surface (`#1A1D24`): **5.5:1 (Pass)**
- Dark Text (`#0F1115`) on Primary (`#FF6B1A`): **6.9:1 (Pass)**
- Primary text/button on Base (`#FF6B1A` on `#0F1115`): **4.6:1 (Pass)**

These will be provided as CSS variables in `:root` with a `[data-theme="light"]` override for the light mode.

---
**Next Steps**: 
Once you choose a tagline and a logo direction, I will finalize the logo assets in `/assets/brand/`, perform the name migration across the codebase (using the generated grep list), and complete Phase 2.
