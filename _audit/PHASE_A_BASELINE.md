# PHASE A — FORENSIC BASELINE
**Date:** 2026-07-31 · **Scope:** read-only, zero edits · **Prereq:** §0.4 pre-flight done (`ee427ab`)

Har number ke sath wo command hai jis se wo nikla (§A.2 requirement).

---

## 0. HEADLINE NUMBERS

| Metric | Value | Command |
|---|---|---|
| PHP files | **99** | `find . -name "*.php" -not -path "./_audit/*" \| wc -l` |
| PHP LOC | **11,550** | `awk -F'\|' '{s+=$2} END{print s}'` over route map |
| JS LOC | **4,628** | `cat assets/js/*.js \| wc -l` |
| DB tables | **33** | `grep -ciE "^CREATE TABLE" sql/core_calorie_advisor.sql` |
| Files including `header.php` | **61** | `awk -F'\|' '$3=="yes"'` |
| Files with a role guard | **32** | `awk -F'\|' '$5!="-"'` |
| Repo on disk (incl. `.git`) | **406 MB** | `du -sh .` |
| Repo before `git init` | **233 MB** | measured pre-flight |
| True orphans | **9** (not 8) | see §2 |

---

## 1. ROUTE MAP

Columns: `path | LOC | includes header? | auth check present? | role guard | # files referencing it`

> **Method note (important).** Pehli grep ne 45 "orphans" diye — **wo galat tha**. Wajah:
> links dynamically bante hain (`redirect('portals/' . $role . '.php')`), to `href=`-style
> regex unko miss karta hai. Final column ab plain basename reference count hai
> (`grep -rl "$basename" --include=*.php --include=*.js --include=*.html`), aur har
> zero-count file ko **manually verify** kiya gaya hai (§2). Ye §0.3.1 ke grep-trap ka
> dobara repeat na ho, is liye method yahan likha hai.

### admin/
| path | LOC | hdr | auth | role | refs |
|---|---|---|---|---|---|
| `admin/appointments-master.php` | 27 | yes | no | `require_role('admin')` | 5 |
| `admin/dashboard.php` | 70 | yes | no | `require_role('admin')` | 33 |
| `admin/exercise-library-admin.php` | 28 | yes | no | `require_role('admin')` | 5 |
| `admin/monetization-stripe.php` | 81 | yes | no | `require_role('admin')` | 6 |
| `admin/reviews-moderation.php` | 47 | yes | no | `require_role('admin')` | 5 |
| `admin/user-management.php` | 52 | yes | no | `require_role('admin')` | 5 |

### api/
| path | LOC | hdr | auth | role | refs |
|---|---|---|---|---|---|
| `api/admin-set-plan.php` | 32 | no | yes | inline admin check `:8` | **0 — ORPHAN** |
| `api/admin-warn.php` | 38 | no | yes | inline admin check `:8` | **0 — ORPHAN** |
| `api/appointment-action.php` | 26 | no | no | `require_role('trainer','doctor','admin')` | 3 |
| `api/book-appointment.php` | 26 | no | no | `require_role('member','patient','admin')` | 1 |
| `api/buy-item.php` | 87 | no | yes | - | 2 |
| `api/cart-add.php` | 55 | no | yes | - | 2 |
| `api/chat.php` | 105 | no | no | - (public by design, owner-approved) | 1 |
| `api/community-post.php` | 29 | no | yes | - | 1 |
| `api/create-appointment-checkout.php` | 51 | no | yes | - | **0 — ORPHAN** |
| `api/create-checkout.php` | 44 | no | yes | - | 1 |
| `api/feedback.php` | 21 | no | no | - | 3 |
| `api/food-lookup.php` | 33 | no | yes | pro+member `:8` | 1 |
| `api/log-food.php` | 26 | no | yes | member `:13` | 3 |
| `api/moderate-review.php` | 39 | no | no | `require_role('admin')` | **0 — ORPHAN** |
| `api/notifications.php` | 43 | no | yes | - | 3 |
| `api/report-issue.php` | 30 | no | yes | - | 3 |
| `api/request-payout.php` | 46 | no | no | `require_role('trainer','doctor','admin')` | **0 — ORPHAN** |
| `api/sandbox-checkout.php` | 43 | no | yes | - | 2 |
| `api/save-workout.php` | 26 | no | yes | - | 1 |
| `api/scan.php` | 186 | no | yes | pro+member `:11-12` | 3 |
| `api/stripe-webhook.php` | 106 | no | no | signature verify | **0 — ORPHAN (external)** |
| `api/submit-review.php` | 46 | no | yes | - | **0 — ORPHAN** |
| `api/wishlist-toggle.php` | 56 | no | yes | - | 1 |

### auth/ · config/ · includes/ · root
| path | LOC | hdr | auth | role | refs |
|---|---|---|---|---|---|
| `auth/forgot-password.php` | 70 | yes | no | - | 1 |
| `auth/login.php` | 84 | yes | yes | - | 17 |
| `auth/logout.php` | 11 | no | no | - | 2 |
| `auth/oauth-facebook.php` | 11 | no | no | - | 2 |
| `auth/oauth-google.php` | 11 | no | no | - | 2 |
| `auth/oauth-instagram.php` | 11 | no | no | - | 2 |
| `auth/register.php` | 168 | yes | yes | - | 3 |
| `config/config.php` | 100 | no | no | - | **94** (bootstrap) |
| `config/db.php` | 22 | no | no | - | 1 |
| `includes/auth_check.php` | 5 | no | yes | - | **0 — ORPHAN** |
| `includes/footer.php` | 100 | no | no | - | 60 |
| `includes/functions.php` | 331 | no | yes | (defines `require_role`) | 1 |
| `includes/header.php` | 340 | yes | yes | - | 61 |
| `includes/intro.php` | 352 | no | no | - | 1 |
| `index.php` | 487 | yes | no | - | 21 |

### doctor/ · member/ · patient/ · trainer/ · portals/
| path | LOC | hdr | auth | role | refs |
|---|---|---|---|---|---|
| `doctor/consultations.php` | 29 | yes | no | `require_role('doctor','admin')` | 4 |
| `doctor/dashboard.php` | 80 | yes | no | `require_role('doctor','admin')` | 33 |
| `doctor/financials.php` | 53 | yes | no | `require_role('doctor','admin')` | 4 |
| `doctor/patient-queue.php` | 40 | yes | no | `require_role('doctor','admin')` | 4 |
| `doctor/ratings.php` | 41 | yes | no | `require_role('doctor','admin')` | 10 |
| `member/appointments.php` | 83 | yes | no | `require_role('member','admin')` | 11 |
| `member/billing.php` | 105 | yes | no | `require_role('member','admin')` | 6 |
| `member/dashboard.php` | 189 | yes | no | `require_role('member','admin')` | 33 |
| `member/diet-planner.php` | 120 | yes | no | `require_role('member','admin')` | 5 |
| `member/trainers-doctors.php` | 120 | yes | no | `require_role('member','admin')` | 5 |
| `member/workouts.php` | 125 | yes | no | `require_role('member','admin')` | 21 |
| `patient/appointments.php` | 32 | yes | no | `require_role('patient','admin')` | 11 |
| `patient/dashboard.php` | 128 | yes | no | `require_role('patient','admin')` | 33 |
| `patient/doctors.php` | 40 | yes | no | `require_role('patient','admin')` | 11 |
| `patient/prescriptions.php` | 46 | yes | no | `require_role('patient','admin')` | 4 |
| `patient/vitals-log.php` | 89 | yes | no | `require_role('patient','admin')` | 4 |
| `trainer/client-roster.php` | 32 | yes | no | `require_role('trainer','admin')` | 4 |
| `trainer/dashboard.php` | 72 | yes | no | `require_role('trainer','admin')` | 33 |
| `trainer/earnings-payouts.php` | 58 | yes | no | `require_role('trainer','admin')` | 4 |
| `trainer/reviews-ratings.php` | 62 | yes | no | `require_role('trainer','admin')` | 5 |
| `trainer/routine-creator.php` | 89 | yes | no | `require_role('trainer','admin')` | 4 |
| `portals/admin.php` | 4 | no | no | - | 7 |
| `portals/doctor.php` | 4 | no | no | - | 0 → **live, dynamic** |
| `portals/member.php` | 4 | no | no | - | 0 → **live, dynamic** |
| `portals/patient.php` | 4 | no | no | - | 0 → **live, dynamic** |
| `portals/trainer.php` | 4 | no | no | - | 0 → **live, dynamic** |

### pages/ (29 files)
| path | LOC | hdr | auth | refs |
|---|---|---|---|---|
| `pages/about.php` | 85 | yes | no | **0 — ORPHAN** |
| `pages/billing-success.php` | 212 | yes | yes | 2 |
| `pages/calculators.php` | 74 | yes | no | 3 |
| `pages/cart.php` | 141 | yes | yes | 1 |
| `pages/checkout.php` | 227 | yes | yes | 5 |
| `pages/checkout-shop.php` | 227 | yes | yes | 4 |
| `pages/community.php` | 116 | yes | yes | 1 |
| `pages/contact.php` | 136 | yes | yes | 1 |
| `pages/endorsements.php` | 57 | yes | no | 1 |
| `pages/faq.php` | 43 | yes | no | 1 |
| `pages/features.php` | 128 | yes | no | 1 |
| `pages/feedback.php` | 47 | yes | no | 3 |
| `pages/notifications.php` | 105 | yes | yes | 2 |
| `pages/nutrition.php` | 96 | yes | no | 2 |
| `pages/player.php` | **1660** | yes | yes | 7 |
| `pages/pricing.php` | 165 | yes | yes | 17 |
| `pages/privacy-policy.php` | 62 | yes | no | 4 |
| `pages/profile.php` | 199 | yes | yes | 1 |
| `pages/receipt.php` | 226 | yes | yes | 2 |
| `pages/report-issue.php` | 66 | yes | yes | 2 |
| `pages/scanner-body.php` | 358 | yes | yes | 5 |
| `pages/scanner-food.php` | 419 | yes | yes | 5 |
| `pages/shop.php` | 314 | yes | yes | 6 |
| `pages/terms-and-conditions.php` | 65 | yes | no | 4 |
| `pages/trainers.php` | 77 | yes | no | 7 |
| `pages/trainer-studio.php` | 348 | yes | no | 1 |
| `pages/wishlist.php` | 91 | yes | yes | 1 |
| `pages/workout-detail.php` | 178 | yes | yes | 5 |
| `pages/workouts.php` | 373 | yes | no | 21 |

---

## 2. ORPHAN LIST — VERIFIED, WITH VERDICTS

Doc ne 8 claim kiye thay. **Saare 8 confirmed**, plus 1 aur (`includes/auth_check.php`) = **9**.
Aur 4 **false orphans** mile jo doc me nahi thay (portal shims) — wo live hain.

### 2.a False orphans (grep artefact — DELETE MAT KARNA)

| file | why grep missed it | proof |
|---|---|---|
| `portals/doctor.php` | dynamic path build | `auth/login.php:3` `redirect('portals/' . $_SESSION['user']['role'] . '.php')` |
| `portals/member.php` | same | `api/book-appointment.php:26`, `api/appointment-action.php:14,19,20,26` |
| `portals/patient.php` | same | `api/chat.php:82,85` `"$B/portals/$role.php"` |
| `portals/trainer.php` | same | `api/admin-warn.php:30` `BASE_URL . '/portals/' . $target['role'] . '.php'` |

> Ye rule 0.1.3 ka *"external/dynamic callers grep me nahi aate"* ka live example hai.

Isi tarah ye 4 APIs bhi pehli pass me orphan lagey thay lekin **live hain**:
`cart-add.php` ← `pages/shop.php:305` · `wishlist-toggle.php` ← `pages/shop.php:295` ·
`feedback.php` ← `assets/js/forms.js:23` · `report-issue.php` ← `assets/js/forms.js:24`
(sab relative `call('x.php')` ya `/api/x.php` se).

### 2.b True orphans — 9 files

| file | LOC | verdict | reason (evidence) |
|---|---|---|---|
| `api/stripe-webhook.php` | 106 | **KEEP (external caller)** | Stripe isay bahar se call karta hai. Grep me kabhi nahi aayega. **DELETE MAT KARNA.** |
| `api/admin-set-plan.php` | 32 | **WIRE UP** (button missing) | `admin/user-management.php:38` plan ko sirf **read-only** dikhata hai (`<?= strtoupper($u['plan']) ?>`), badalne ka koi UI nahi. API ka apna comment `:2-3` kehta hai *"Used by the Forge Control Room's user table"* — wo UI mojood nahi. |
| `api/request-payout.php` | 46 | **WIRE UP** (button missing) | `trainer/earnings-payouts.php:17` subtitle kehta hai *"request payouts"*, aur `:7` `payout_requests` table read karta hai — lekin koi `fetch` nahi. Doc §L.2 ka shak **confirmed**. |
| `api/create-appointment-checkout.php` | 51 | **WIRE UP** (feature incomplete) | Ye `create-checkout.php` ka duplicate **nahi** hai — wo subscription plans ke liye hai (`:10` `$plan`), ye appointments ke liye (`:11-12` `provider_id`, `appt_date`). Appointment payment flow kahin se call nahi hota. |
| `api/submit-review.php` | 46 | **WIRE UP** (button missing) | `trainer/reviews-ratings.php` + `doctor/ratings.php` reviews **dikhate** hain, lekin member/patient side par koi submit form nahi mila. Reviews DB me aa hi nahi sakte. |
| `api/admin-warn.php` | 38 | **WIRE UP** (button missing) | `warnings` table mojood, API kaam karta hai, admin UI nahi. Doc §L.2 confirmed. |
| `api/moderate-review.php` | 39 | **WIRE UP — aur ye P1 bug fix karta hai** | Dekho §7 Deferred Finding #1: `admin/reviews-moderation.php` ka apna inline handler **kabhi chalta hi nahi**. Ye API kaam karta hai. |
| `pages/about.php` | 85 | **WIRE UP** (footer link) | Site ko about page chahiye; `includes/footer.php` me link nahi. 1-line fix. |
| `includes/auth_check.php` | 5 | **DELETE** | Poori file `require_login()` ka wrapper hai. **Zero includers** — `grep -rl "auth_check"` → sirf khud. Doc §M.1.5 ka prediction **confirmed**. |

**Faisla summary:** 7 = wire up · 1 = keep (external) · 1 = delete.
**Koi bhi API "dead feature" nahi nikla** — sab ka backend kaam karta hai, sirf UI missing hai.
Ye Phase L/M ka kaam hai, Phase K ka nahi.

---

## 3. SECURITY MATRIX — remaining 2 columns

CSRF/auth columns §J.1 me already verified hain (`csrf_verify()` 6 · `csrf_verify_json()` 7 ·
inline `hash_equals` 9 · `stripe-webhook` signature = 23). Yahan sirf wo 2 columns jo baqi thay.

### 3.a Prepared statements

```
$ grep -rhoE "\bprepare\(" --include=*.php . | wc -l     → 147
$ grep -rhoE "\bquery\("   --include=*.php . | wc -l     →  47
$ grep -rnE '(query|exec)\(["'"'"'][^"'"'"']*\$' --include=*.php .
  → 1 hit: api/scan.php:169
```

**Verdict: PASS.** Sirf ek query me variable interpolation hai:

```php
api/scan.php:162   $goalCat = $goal === 'cut' ? "('hiit','cardio')" : "('strength','weights')";
api/scan.php:169   db()->query("SELECT id, name FROM workouts WHERE category IN $goalCat …")
```

`$goalCat` ek **hardcoded ternary literal** hai — user input kabhi is string me nahi jaata.
`$goal` sirf do fixed strings me se ek chunta hai. **Ye SQL injection nahi hai.**
Baqi 46 `query()` calls me koi variable nahi (static SQL).

### 3.b Output escaping

```
$ grep -rhoE "<\?= *e\(" --include=*.php . | wc -l                        → 255
$ grep -rnoE "<\?= *\$[a-zA-Z_]+\[[^]]+\] *\?>" --include=*.php . | wc -l  →   0
```

**Verdict: PASS.** `e()` (`functions.php:5`, `htmlspecialchars` + `ENT_QUOTES`) 255 jagah use hota hai,
aur raw `<?= $row['col'] ?>` ka **ek bhi** case nahi mila.

> **Caveat, honestly:** `<?= $u['plan'] ?>` jaise cases jahan value `strtoupper()`/`number_format()`/
> `match()` se guzarti hai wo mere regex me nahi aate. Wo mostly enum/numeric hain (safe),
> lekin ye column **"spot-check pass"** hai, "exhaustive proof" nahi. Poora sweep §J.2.6 ka kaam hai.

---

## 4. WEIGHT REPORT

```
$ du -sh assets/videos assets/models assets/images assets/js assets/css assets/brand _audit uploads logs sql
```

| folder | size | doc baseline | match? |
|---|---|---|---|
| `assets/videos` | **153 MB** | 153 MB | ✅ |
| `assets/models` | **22 MB** | 22 MB | ✅ |
| `_audit` | **57 MB** | 57 MB | ✅ |
| `assets/images` | 245 KB | — | |
| `assets/js` | 245 KB | — | |
| `assets/css` | 188 KB | — | |
| `assets/brand` | 26 KB | — | |
| `sql` | 68 KB | — | |
| `uploads` | 12 KB | — | |
| `logs` | 2 KB | — | |
| **repo total (pre-git)** | **233 MB** | — | |
| **repo total (now, incl `.git`)** | **406 MB** | — | `.git` ≈ 173 MB |

### 4.a Bloat ka asal source — ye doc me nahi tha

```
$ ls -laS assets/videos/
141,942,690  hero-loop.mp4          ← 142 MB, EK file
  8,987,314  Athletic_body_training_in_gym_202607221638.mp4
  8,452,980  hero-loop-mobile.mp4
```

**`hero-loop.mp4` akela 142 MB hai — poore videos folder ka 93%.**
Aur ye 6 jagah load hota hai:

```
$ grep -rln "hero-loop.mp4" --include=*.php .
index.php · admin/dashboard.php · doctor/dashboard.php
member/dashboard.php · patient/dashboard.php · trainer/dashboard.php
```

Yani **har portal ka dashboard 142 MB ka video reference karta hai.** Ye sirf repo bloat nahi,
ye ek live performance problem hai. Doc §K.1 kehta hai *"hero loop 10 MB se zyada nahi hona chahiye"* —
abhi wo 14× over hai. Compress karne se ~132 MB bachega.

`Athletic_body_training_in_gym_202607221638.mp4` ka naam generated lagta hai **lekin wo live hai**
(`index.php:411`) — doc §K.1 ka *"grep karo ke kaunsi videos actually reference hoti hain"*
ka jawab: **teeno use ho rahi hain, koi orphan video nahi.**

---

## 5. DOC DRIFT

| # | drift | evidence | severity |
|---|---|---|---|
| 1 | `trainer.glb` (singular) — **aisi koi file nahi hai** | `assets/js/titan3d.js:6,9,10,60`, `assets/js/titan-rig.js:6`. Asli files: `trainers.glb`, `trainer-street.glb`, `trainer-pro.glb`, `trainer-ch06.glb` (`ls assets/models/*.glb`) | med |
| 2 | `assets/models/anims/` khali hai | `ls -A assets/models/anims/` → sirf `README.md`. `titan3d.js:10` khud kehta hai *"Absent anim files 404 silently"* | med — §M.1.2 confirmed |
| 3 | `CLAUDE.md` "Project Status": *"`csrf_verify_json()` applied to all APIs"* | Sirf 7/23 wo helper use karte hain. **Protection real hai**, wording ghalat. `CLAUDE.md` **edit nahi kiya** (scope se bahar) | low |
| 4 | `MASTER_PROMPT` §0.3 `pages/` = 26 | Asal 29 — v3.1 me fix ho chuka | low (fixed) |
| 5 | `trainer-ch06.glb` (2.4 MB) mojood, roster me nahi | `cca-wardrobe.js:33,42` sirf `street` + `pro` list karta hai | Phase E ka scope |

---

## 6. LINE-ENDING DRIFT — item ab measurable nahi jaisa doc ne socha tha

```
$ git ls-files --eol | awk '{print $1, $2}' | sort | uniq -c
    143 i/lf    w/lf
     14 i/-text w/-text     (binary: glb, mp4, png, jpg)
      2 i/lf    w/crlf
$ git ls-files --eol | grep "i/crlf"
    (zero hits)
```

**Verdict: index me koi CRLF nahi — sab LF par normalized hai.**

Doc ka original claim (*"`includes/header.php` CRLF hai, baqi LF"*) ab **reproduce nahi ho sakta**,
kyunke §0.4 ke `git add` ne `core.autocrlf=true` ke tehat sab normalize kar diya
(150+ *"LF will be replaced by CRLF"* warnings us commit me).

Sirf 2 files working-tree me CRLF hain lekin index me LF — wo harmless hai.

**Open decision (owner):** `.gitattributes` (`* text=auto eol=lf`) add karein?
Abhi kuch toota nahi hai; ye future spurious-diff insurance hai. Maine **khud nahi banai** —
naya file + repo-wide rule = tumhari approval chahiye.

---

## 7. DEFERRED FINDINGS (rule 0.1.2 — mila, **fix nahi kiya**)

### 🔴 #1 — Do admin POST handlers kabhi chalte hi nahi (P1, functional)

```php
admin/reviews-moderation.php:6   if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify_json()) {
admin/monetization-stripe.php:6  if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify_json()) {
```

`includes/functions.php:24` → `function csrf_verify_json(): void`.

PHP me `void` function expression me `null` deta hai → `true && null` = **false** →
**block kabhi execute nahi hota.**

Asar:
- **Admin review approve/reject kaam nahi karta.** Button dabao, page reload hota hai, DB me kuch nahi badalta.
- `admin/monetization-stripe.php` ka POST handler bhi dead hai.

Ye §2.b me `api/moderate-review.php` ke verdict se juda hua hai — wo API **sahi likha hua hai**
(`:3` `require_role('admin')`, `:9` `csrf_verify_json()` alag statement par). Yani fix ka
sab se saaf raasta: inline handler hatao, page ko working API par point karo.

**Scope:** Phase L (admin wiring). **Phase A me fix nahi kiya.**

### 🟡 #2 — `hero-loop.mp4` 142 MB, har dashboard par
§4.a. **Scope:** Phase K.

### 🟡 #3 — `pages/player.php` 1660 LOC
Repo ki sab se bari file, doc §G ka target. **Scope:** Phase G.

---

## 8. ACCEPTANCE (§A.2)

| criterion | status | proof |
|---|---|---|
| Har number ke sath command | ✅ | har section me inline |
| Koi existing file edit nahi hui | ✅ | `git status --short` → khali (neeche) |
| Report `_audit/` me | ✅ | ye file |

```
$ git status --short
(khali — working tree clean)
```

> `_audit/` §0.4 me gitignore ho chuka hai, is liye ye report untracked bhi nahi dikhti —
> wo expected hai, doc ka *"sirf `_audit/PHASE_A_BASELINE.md` untracked"* pre-flight se pehle likha gaya tha.

---

## 9. ROMAN URDU SUMMARY

1. 99 PHP files ka poora map ban gaya; 11,550 lines PHP aur 33 DB tables confirm huay.
2. Doc ke 8 orphans sahi nikle, ek aur mila (`auth_check.php`) — lekin 4 portal files jhoote orphan thay, wo live hain.
3. Saat APIs ka code theek hai, sirf unke buttons UI me mojood nahi — feature adhoora hai, dead nahi.
4. Ek asli bug pakra: admin ka review approve/reject button kaam hi nahi karta (`csrf_verify_json()` void hai).
5. Sab se bara bloat `hero-loop.mp4` hai — 142 MB akela, aur har dashboard par load hota hai.
