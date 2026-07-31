# PHASE 10 — Card system audit (item 1)

**Status: item 1 (audit) AND item 2 (unification) complete and verified.**
**Approved direction:** cards must follow the theme · add icons where there is no image · redesign
all three card styles into one professional system.
**Date:** 2026-07-25 · **Method:** `class="…"` inventory across all 68 `.php` files (excl. `_audit/`, `node_modules/`)

---

## What I found

**106 card-like blocks exist. They are built three different ways.**

| Kind | Count | Where |
|---|---:|---|
| Uses `.cca-card` correctly | **68** | the 5 portals — `member/`, `trainer/`, `doctor/`, `patient/`, `admin/`, plus `about.php` and `features.php` |
| Hand-rolled **bespoke class** | **26** | `index.php` 15, `pricing.php` 3, `checkout*.php` 4, `workouts.php` 2, `receipt`/`billing-success` 2 |
| **Tailwind look-alike** (`rounded-2xl` + `bg-*` + `border`) | **12** | `player.php` 4, `scanner-body.php` 4, `scanner-food.php` 3, `shop.php` 1 |

### This is the "cards don't look put-together" complaint, precisely located

The split is not random — it falls exactly along one line:

- **Every logged-in portal page uses `.cca-card`.** That side is consistent.
- **Every public/marketing page rolls its own.** `index.php` alone has **15** bespoke cards.

So a visitor's first impression (home → workouts → pricing → shop) is built from a different card
system than the product they see after signing in. That is the "two different products stitched
together" effect, and it is concentrated in **7 files**.

### The 7 competing bespoke card classes

| Class | Uses | Defined in | Anatomy |
|---|---:|---|---|
| `.cat-card` | 10 | `index.php` `<style>` | photo background, bottom-anchored text, no footer row |
| `.portal-card` | 5 | `index.php` `<style>` | hardcoded dark panel `rgba(15,18,25,.55)`, own accent var |
| `.checkout-card` | 4 | `checkout.php` / `checkout-shop.php` | `--bg3` panel, own padding scale |
| `.plan-card` | 3 | `pricing.php` | own hover-lift + entrance animation |
| `.invoice-card` | 2 | `receipt.php` / `billing-success.php` | `--bg3` panel + print variant |
| `.wk-card` | 1 | `pages/workouts.php` | thumb + body + stats + CTA |
| `.hero-wk-card` | 1 | `pages/workouts.php` | large featured variant |

Plus 12 Tailwind-only panels with no class name at all, so they cannot be restyled centrally.

**Seven card definitions + `.cca-card` = eight anatomies for one concept.** Each has its own radius,
padding, border and hover behaviour, which is why spacing and corner rounding visibly differ between
pages.

### What `.cca-card` currently provides

7 selectors in `style.css`: `.cca-card`, `-header`, `-title`, `-value`, `-icon`, `-link`, `-footer`.
That matches the Phase 4 anatomy (media → header → primary value → meta → footer actions) but
**offers no photo/media slot**, which is why the photo-led marketing cards (`.cat-card`, `.wk-card`,
`.hero-wk-card`) could not adopt it and were hand-rolled instead.

---

## What I propose (item 2) — not yet done

1. **Add the missing media slot to `.cca-card`** (`.cca-card__media`) plus a photo-overlay variant.
   *Impact:* removes the actual reason the marketing cards diverged. *Risk:* low — additive only.
2. **Migrate the 26 bespoke cards** to `.cca-card` + variant modifiers, one file at a time, starting
   with `index.php` (15 of 26). *Impact:* the homepage stops looking like a different product.
   *Risk:* medium — these are the highest-traffic pages; each needs a light/dark screenshot pair.
3. **Give the 12 Tailwind look-alikes a real class** so they can be governed centrally.
   *Risk:* low.
4. **Keep `.invoice-card` separate.** *Reason:* it carries a `@media print` variant that `.cca-card`
   has no business owning. Recommend leaving it bespoke and documenting why.

**Recommended order:** item 1 (media slot) → `index.php` → `workouts.php` → `pricing.php` →
`checkout*.php` → the 12 Tailwind panels, with a verification pass after each file.

---

# ITEM 2 — What I built

## One card system: `assets/css/cca-cards.css` (new file)

One anatomy for all 106 cards: **media → icon → title → meta → footer**. Loaded last in
`includes/header.php` so it governs the legacy class names too.

### The four things you asked for

**1. Cards follow the theme.** `.portal-card` was a hardcoded `rgba(15,18,25,.55)` panel that stayed
dark in light theme. Every card surface is now `var(--surface)` with `var(--border)`, so it flips
correctly. Light theme also gets a softer, paper-like two-stop shadow instead of the dark-theme glow.

**2. Icons where there is no image.** A single icon treatment — `44px`, `13px` radius, tinted from the
card's own accent, one `stroke-width: 2` across the whole set. Applied to `.cca-card__icon`,
`.portal-icon` and `.cat-card-icon` so a card without a photo never renders as an empty slab.

**3. All three designs replaced.** The old `.cat-card` put text *directly on the photo* under a heavy
black gradient; `.portal-card` was a dark slab; `.wk-card` was a third thing. All three now use the
same structure: photo on top, content on a real card surface underneath, meta chip, then the action.
That is what makes the homepage and the portals finally read as one product.

**4. Professional detailing.** Numbers are the hero: `--card-value` uses tight `-.02em` tracking and
**tabular figures** so digits don't jitter and columns align. Delta chips are semantic and
deliberately much smaller than the value they annotate. One accent, used only on the hairline, icon
and CTA. Consistent 16px radius, 20px padding, one hover lift, and `prefers-reduced-motion` disables
lift and photo-zoom.

## Deletions (CLAUDE.md rule 4)

| Removed | Reason | Referenced by | Safe? |
|---|---|---|---|
| `index.php` — 54 lines of `.cat-card*` anatomy | Competing card design; superseded by `cca-cards.css` | 10 `.cat-card` call sites, all still working | **Yes** — verified by screenshot, contrast 0-fail, HTTP 200 |
| `index.php` — 39 lines of `.portal-*` anatomy | Hardcoded dark panel + inaccessible badge colour | 5 `.portal-card` call sites | **Yes** — same verification |
| `index.php:409` inline `style="color: var(--gold…)"` | Made the Admin title gold while the other four were dark | 1 site | **Yes** |

`.cat-grid` and `.portal-grid` layout rules were **kept** in `index.php` — they are page layout, not
card anatomy. A comment marks each removal and points at `cca-cards.css`.

## Four defects found and fixed during the work

1. **Titles overlapped the photo.** `index.php`'s `.cat-card-bg { position:absolute; inset:0 }` still
   won over the new rules because page `<style>` loads after `<head>`. That is what forced the
   deletions above rather than a specificity hack.
2. **"EXPLORE →" sat at three different heights** across a row of five cards — the meta row wrapped on
   some cards and not others. Fixed with `nowrap` + `space-between`.
3. **"ENTER PORTAL" wrapped to two lines** on the three narrower portal cards, and the badge truncated
   to "ADMIN C". Fixed by adopting the `.wk-card` footer anatomy: chip on its own line, full-width CTA.
4. **Portal badges failed WCAG AA in light theme** (`Admin Control` **1.73:1**, `Doctor Access`
   2.43:1). `.portal-badge` coloured its label with the raw accent. Each card now passes
   `--portal-accent` *and* `--portal-accent-text`; added `--accent-cyan-text` `#0E7490` (4.83:1).

## Verified

| Check | Result |
|---|---|
| Contrast, 7 pages × 2 themes | dark **33/33**, light **63/63** — **0 fail** ✓ |
| Theme changes pixels | **7/7 pages DIFFER** ✓ |
| `php -l` on `index.php` after both deletions | No syntax errors ✓ |
| HTTP status, 9 public routes | all **200** ✓ |
| Portal side not regressed | `admin/dashboard.php` captured both themes — stat tiles intact ✓ |

**Screenshots** in `_audit/after/phase10/`: `home-categories`, `home-portals`, `workouts-cards`,
`pricing-plans`, `admin-dashboard` — each `-light` and `-dark`.

---

# ITEM 3 — the three outstanding items, closed

## 1. Teko font — my earlier claim was WRONG, corrected

I previously wrote "Teko is not loaded." That was only half true. Teko **is** loaded — by
`pages/player.php:25` and `pages/workout-detail.php`, each via its own `<link>`. It is **not** loaded
on `pages/workouts.php` or `pages/pricing.php`, which rely on the global header font link
(Russo One / Rajdhani / Manrope). So **9 references on those two pages** silently fell back to generic
sans-serif.

Fixed **without adding a dependency** (CLAUDE.md rule 5): those 9 now use the display font the design
system already loads, via a new `.cca-font-disp` utility and `font-family: var(--font-disp)`.
`grep -c Teko` on both files is now **0**.

**Left alone:** the 24 Teko references on `player.php` and `workout-detail.php`, because those pages
genuinely load the font. Unifying them onto `--font-disp` would drop a webfont dependency but changes
the 3D player's look — **your call**, not a silent change.

## 2. The 12 unnamed Tailwind panels

Inspecting them showed most were **not cards at all** — they are buttons, inputs and chips that merely
use `rounded-xl`. The genuine panels were converted:

| File | Element | Now |
|---|---|---|
| `pages/shop.php` | product card | `.cca-card` + `.cca-card__media` + `.cca-card__badge--left` + shared CTA |
| `pages/shop.php` | empty state | `.cca-card` |
| `pages/scanner-body.php` | result panel | `.cca-card` |
| `pages/scanner-food.php` | result panel + input panel | `.cca-card` |

**`pages/player.php`'s panels were deliberately NOT converted.** They live inside the always-dark 3D
arena; giving them `.cca-card`'s theme-driven surface would turn them white in light theme while their
labels stayed `text-white`. Same reasoning as the `--stage-bg` decision in Phase 9.

Card inventory moved **68 → 73 `.cca-card`**, and unnamed Tailwind look-alikes **12 → 10** (the 10
remaining are player-arena panels and buttons, correctly excluded).

## 3. `.invoice-card` — stays bespoke, but no longer looks different

Kept as its own class **because of its `@media print` variant**, which `.cca-card` has no business
owning. But its *visual* design is now aligned to the shared system — same `--surface`, `--border`,
16px radius and `--shadow-1`. Moving it off `--bg3` also removes the light-theme grey panel that
caused the invisible-invoice-text bug. The print block is intact and untouched.

## One more contrast fix this surfaced

`.wk-btn-open` was white on brand orange — **2.85:1**, a fail. The app's own `.btn-fire` already uses
near-black on orange, so my card button was the inconsistent one. Both card CTAs now use
`var(--on-accent)`: **6.95:1** on orange, **11.42:1** on gold, with the fills fully saturated.

## Verified after item 3

| Check | Result |
|---|---|
| Contrast, 7 pages × 2 themes | dark **35/35**, light **65/65** — **0 fail** ✓ |
| Legacy token resolution | 24/24 both themes ✓ |
| Theme changes pixels | 7/7 ✓ |
| `php -l` on every edited file | No syntax errors ✓ |
| HTTP status, 11 public routes | all **200** ✓ |
| `grep -c Teko` on workouts/pricing | **0** ✓ |

New screenshots: `_audit/after/phase10/shop-cards-{light,dark}.png`

---

# ITEM 4 — test order, shop actions, and two bugs it exposed

## Test order created (at your instruction)

`shop_orders` was empty, so `pages/receipt.php` and `pages/billing-success.php` could not render and
stayed "fixed but unverified". One real order was created:

```
order id=1  user_id=2 (member@corecalorieadvisor.com)  item_id=1  $149.99  completed
```

**This is test data and must be removed before production**, exactly like the `cca123` demo accounts
already flagged in `README.md`. `pages/receipt.php?order_id=1` now returns **200** and is captured in
`_audit/after/phase10/receipt-{light,dark}.png` — confirming the invoice-text fix from Phase 9 Part C.

## Bug 1 — the shipping address was being thrown away

Rendering the real receipt immediately exposed a defect no amount of code-reading had caught:

- `api/buy-item.php:14` reads the address, `:17` **validates** it (min 10 chars)…
- …and `:39` then **INSERTs without it**. `shop_orders` had no `address` column at all.
- `pages/receipt.php:98` printed `$order['address']`, so every invoice rendered a raw
  **PHP warning in the middle of the "Billed To" block**.

The customer typed a shipping address, the app validated it, and then silently discarded it. Fixed:
column added (following `shop.php`'s existing auto-create convention), the INSERT now persists it, and
the receipt degrades to `—` rather than warning.

## Bug 2 — two API endpoints were dead on every request ⚠️

`api/submit-review.php:3` and `api/create-appointment-checkout.php:3` both called
**`require_logged_in()` — a function that does not exist.** The real helper is `require_login()`
(`includes/functions.php:116`). Every request to either endpoint died with:

```
Fatal error: Uncaught Error: Call to undefined function require_logged_in()
```

So **"submit a review" and "book a paid appointment" were completely broken**, and the fatal error
leaked the full server path to the client. One-word fix in each; both now correctly return `302` to
login when anonymous and reject a bad CSRF token when authenticated.

### This corrects my own Phase 8 audit

Phase 8 §5 marked **both files "OK"**. I had read them for the presence and ordering of CSRF/auth
guards — which were fine — but I never executed them, so a fatal error on line 3 was invisible to a
reading-based audit. Phase 8's own rule was that presence "somewhere in the file" is not proof; I
applied that to guard *placement* but not to whether the file runs at all. **The §5 table's "OK" for
those two rows was wrong.**

## Shop cards: wishlist + add to cart

Two tables (`shop_wishlist`, `shop_cart`), created via `shop.php`'s existing auto-create convention.
Both carry `UNIQUE(user_id, item_id)`, which is what makes the behaviour correct rather than merely
likely:

- **Wishlist** toggles via `DELETE` → `rowCount()` → conditional `INSERT IGNORE`. No
  SELECT-then-INSERT, so a double-click cannot create two rows.
- **Cart** uses `INSERT … ON DUPLICATE KEY UPDATE quantity = LEAST(99, quantity + VALUES(quantity))`,
  so re-adding increments atomically instead of duplicating, and a client-supplied quantity is clamped.

Two endpoints, both following the `csrf_verify_json()` helper pattern (the 12-file convention, not the
8-file hand-rolled one), in the order Phase 8 §5 established: **POST-only → CSRF → auth → write**, plus
an existence check so a client can never write an arbitrary `item_id`.

**Logged-out behaviour is explicit, not a silent no-op:** the shop page is public, so the buttons
render for anonymous visitors; the API returns `401 {login:true}` and the client redirects to login.

UI lives in the shared card system (`.cca-card__wish`, `.cca-card__actions`, `.cca-btn-soft`) — not as
one-off Tailwind on `shop.php`, which is exactly what this phase spent its time removing. Add to Cart
is outlined so **Buy Now remains the single filled CTA** on the card. The heart renders in its saved
state server-side from `$wishIds`, so it does not flicker after JS loads.

### Endpoint tests actually run

| Case | Result |
|---|---|
| Anonymous + valid CSRF | **401** `{"ok":false,"error":"Login required","login":true}` ✓ |
| Authenticated + **bad** CSRF | rejected, `{"ok":false,"error":"CSRF invalid"}` ✓ |
| `GET` instead of `POST` | **405** `POST only` ✓ |
| Cart add ×2 | `count 1` → `count 2`; DB shows **one row, qty 2** ✓ |
| Wishlist toggle on → off | `saved:true` → `saved:false`; DB empty after ✓ |
| Bogus `item_id=999999` | `{"ok":false,"error":"Product nahi mila"}` ✓ |

**One environment note:** `csrf_verify_json()` sets `419`, but Apache rewrites that non-standard code
to **500** on the way out. The request is still correctly rejected and the JSON body is right — only
the status code is mangled. This is **pre-existing** behaviour of the shared helper (reproduced on
`api/save-workout.php` too), not something these new endpoints introduced. Worth switching to `403`
in a later pass; I have not changed a shared helper mid-task.

## Still bespoke, deliberately

- **`.invoice-card`** — carries a `@media print` variant that the shared card has no business owning.
- **12 Tailwind-only panels** (`player.php` 4, `scanner-body.php` 4, `scanner-food.php` 3, `shop.php` 1)
  still have no class name, so they cannot be governed centrally. They render correctly through the
  Phase 9 bridge, but giving them real classes is outstanding work.
- The `.cat-card` / `.portal-card` / `.wk-card` **class names remain in markup** — only their
  definitions moved. Renaming 106 call sites to `.cca-card` buys nothing over this and risks a lot.

## Verification plan

Same harness as Phase 9 (`_audit/phase9_verify.mjs`, extended): per file, light+dark screenshots with
MD5-distinctness assertion, contrast re-run, and `php -l`. Cards are visual, so every step needs a
before/after pair — no "looks fine" claims.

## Open questions

1. `pages/player.php` and `pages/workout-detail.php` still load **Teko** via their own `<link>` tags
   (24 references). Unify them onto `--font-disp` — dropping a webfont dependency but changing the 3D
   player's look — or leave them as-is?
2. `pages/receipt.php` and `pages/billing-success.php` are **fixed but still unverified**: the
   `shop_orders` table has no rows, so neither page can render. I will not seed fake orders to
   manufacture a screenshot. Add a real test order yourself, or accept them unverified?

---

## Roman Urdu summary

1. **106 card mile — magar teen alag tareeqon se banaye gaye hain:** 68 sahi `.cca-card` use karte hain, 26 apni alag bespoke class, aur 12 sirf Tailwind se bane hain jinka koi naam hi nahi.
2. **Masla theek theek kahan hai wo mil gaya:** saare **portal pages (login ke baad) bilkul consistent hain** — sab `.cca-card` use karte hain. Lekin **saare public pages (home, workouts, pricing, shop) apna apna card banate hain**. Sirf `index.php` mein 15 alag cards hain.
3. Isi liye client ko lagta hai "do alag products jod diye gaye hain" — pehla impression alag system se bana hai, andar ka product alag se.
4. **Asli wajah bhi mil gayi:** `.cca-card` mein **photo/media ka koi slot hi nahi hai**, is liye jin cards pe tasveer chahiye thi (workouts, categories) wo majboori mein alag banane pare.
5. **Ab kaam mukammal ho gaya hai** — neeche natija hai.

---

## Roman Urdu — item 2 (jo kar diya)

1. **Ek hi card system bana diya** (`assets/css/cca-cards.css`): har card ka aik hi dhaancha — tasveer → icon → title → meta → button. Teeno purane designs khatam.
2. **Cards ab theme follow karte hain.** `.portal-card` pehle hamesha kaala rehta tha (hardcoded), ab light theme mein safed aur dark mein dark — jaisa aap ne kaha.
3. **Jahan tasveer nahi thi wahan icon laga diya** — aik hi size, aik hi radius, aik hi stroke width, card ke apne accent colour se rangaa hua.
4. **Char masle kaam ke doran mile aur theek kiye:** title tasveer ke upar chadh raha tha; "EXPLORE" button teen alag heights pe tha; "ENTER PORTAL" do lines mein tootta tha aur badge "ADMIN C" ban jata tha; aur portal badges light theme mein parhe nahi ja rahe thay (Admin Control sirf **1.73:1**).
5. **Purane rules delete kiye** — `index.php` se 54 lines `.cat-card` ki aur 39 lines `.portal-*` ki, kyunke wo naye system ko override kar rahe thay. Sirf grid layout chhora. Har jagah comment likh diya ke ab yeh kahan hai.
6. **Verify:** contrast dark **33/33**, light **63/63** — sifar fail. 9 routes 200. Portal dashboards pe koi nuqsan nahi. Screenshots `_audit/after/phase10/` mein hain.

---

## Roman Urdu — item 3 (teeno baaki kaam)

1. **Teko font — meri pichli baat ghalat thi, theek kar raha hoon.** Teko **load hota hai**, magar sirf `player.php` aur `workout-detail.php` pe (unke apne link se). `workouts.php` aur `pricing.php` pe nahi — wahan 9 jagah chup chaap generic font chal raha tha. Ab wo 9 jagah system ka apna display font use karti hain. **Koi nayi dependency add nahi ki.**
2. **12 Tailwind panels dekhe to pata chala zyada tar card hain hi nahi** — wo buttons aur inputs hain. Jo asli cards/panels thay (shop ka product card, dono scanners ke result panels, shop ka empty state) unhe system mein le aaya. Ab `.cca-card` **68 se 73** ho gaye.
3. **`player.php` ke panels jaan boojh kar nahi chhue** — wo 3D arena ke andar hain jo hamesha kaala rehta hai. Unhe theme-following banata to light mode mein safed ho jate aur unka safed text ghayab ho jata.
4. **`.invoice-card` alag hi rakha** kyunke uska `@media print` version hai — magar uski **shakal** ab baqi cards jaisi kar di (wahi surface, border, radius). Isse light theme ka grey panel wala masla bhi khatam.
5. **Aik aur contrast bug mila:** "OPEN" button orange pe safed text tha — sirf **2.85:1**. App ka apna `.btn-fire` pehle se kaala text use karta hai, to mera button hi ghalat tha. Ab **6.95:1**.
6. **Verify:** contrast dark **35/35**, light **65/65** — sifar fail. 11 routes 200. Har edit ki hui file pe `php -l` clean.
