11# 🔥 CORE CALORIE ADVISOR — Complete Gym & Fitness Platform
PHP 8 + MySQL + Three.js · XAMPP ready

---

## ⚡ Setup (5 minute)

1. **Files copy karo**
   Poora `Core calorie advisor` folder XAMPP ke `htdocs` me daalo:
   ```
   C:\xampp\htdocs\Core calorie advisor\
   ```

2. **XAMPP start karo** — Apache ✅ + MySQL ✅

3. **Database import karo**
   - Browser me kholo: `http://localhost/phpmyadmin`
   - Upar **Import** tab → **Choose File** → `sql/core_calorie_advisor.sql` select karo → **Go**
   - `core_calorie_advisor` database ban jayegi with saara demo data
   - Ye **ek hi** file poori schema (33 tables) + data rakhti hai — koi alag phase migration
     chalane ki zaroorat nahi. CLI: `mysql -u root core_calorie_advisor < sql/core_calorie_advisor.sql`

4. **.env check karo** (default XAMPP ke liye sahi hai)
   ```
   DB_USER=root
   DB_PASS=          # XAMPP me khali hota hai
   APP_URL=http://localhost/Core calorie advisor
   ```

5. **Site kholo** 👉 `http://localhost/Core calorie advisor`

---

## ⚠️ Project Status: NOT COMPLETE — remediation in progress (Phases 8–17)

> The previous "🎉 V1 COMPLETE / all 7 phases executed" claim on this line was **not accurate** and has
> been corrected. See **[`_audit/PHASE8_REALITY_REPORT.md`](_audit/PHASE8_REALITY_REPORT.md)** for the
> evidence behind every statement below.

| Phase | Claimed | Verified reality (Phase 8 audit, 2026-07-25) |
| :--- | :--- | :--- |
| **1-3** AI Body Scanner, Food Logs, Checkout | ✅ | Not re-audited in Phase 8 — status **unverified** |
| **4** Global Design System (`.cca-*`) | ✅ | ⚠️ **Partial.** 66 `.cca-*` classes exist and `.cca-card` is used 69×, but **575 `var()` references point at 27 tokens that are never defined** (13 core design tokens account for 534 of them) — the actual cause of unreadable text. 520 hardcoded hex + 596 inline `style=""` remain. Tailwind CDN still loads in **5** files alongside the token system. |
| **5** 3D Trainer Engine stabilization | ✅ | ⚠️ **Partial.** All 14 exercise modes animate procedurally (`titan-rig.js`) — that part works. But `trainers.glb` contains **no clothing geometry**, the outfit UI points at a `assets/models/clothes/` directory that **does not exist**, and 5 byte-identical 6 MB `.glb` copies (~31 MB) ship as "animations". |
| **6** All 5 Portals refactored | ✅ | ⚠️ **Partial.** Portal folders are the *cleanest* (7–16 hex each); `pages/` still holds 340 hex and 267 inline styles. |
| **7** Security Hardened, CSRF Enforced | ✅ | ✅ **Substantially true — better than the remediation doc alleged.** All 20 session-authenticated API endpoints verify CSRF before their write branch (`stripe-webhook.php` correctly exempt). Two narrow gaps: `api/feedback.php` and `api/chat.php` have no login check. |
| **7** "Final QA passed" | ✅ | ❌ **Not substantiated.** No contrast measurements or screenshot evidence exist for the PASS table. |

**Known to still be shipping:** the old product name renders on the live login and register pages
(`auth/login.php:44`, `auth/register.php:55`) and in `database.sql` seed data.

**Reporting rule from Phase 17 onward:** "done", "fixed", "complete", "PASS" and "production ready"
may only appear next to the command output or screenshot path that proves them.

---

## 🔑 Demo Logins
**Password sab ka: `cca123` — local demo only. Change or remove every demo account before deployment.**

| Role | Email | Kya dekhoge |
|---|---|---|
| Member | `member@corecalorieadvisor.com` | Pro plan, workout logs, appointments, AI scanners |
| Trainer | `trainer@corecalorieadvisor.com` | 2 pending requests — Accept/Reject karo |
| Doctor | `doctor@corecalorieadvisor.com` | Patients, disease-safe plans, consults |
| Patient | `patient@corecalorieadvisor.com` | Knee Pain — doctor-approved safe workout + reminders |
| Admin | `admin@corecalorieadvisor.com` | Sab users, revenue, stats + kisi bhi user ko PRO grant/revoke |

### 💎 Backend PRO Access (sab kuch unlocked)
| Email | Password | Access |
|---|---|---|
| `pro@corecalorieadvisor.com` | `CCAPro2026!` | Elite plan — tamam PRO workouts, AI body/food scanners, appointments, sab features |

Admin portal ke **All Users** table se bhi kisi bhi account ko FREE/PRO/ELITE par switch kiya ja sakta hai.

---

## 📁 File Structure

```
core_calorie_advisor/
├── .env                    ← DB creds, API keys
├── .htaccess               ← security rules
├── index.php               ← home (esports-style intro + 3D trainer)
│
├── sql/
│   └── core_calorie_advisor.sql   ← full schema (33 tables) + seed data, single import
│
├── config/
│   ├── config.php          ← .env loader, session, constants
│   └── db.php              ← PDO connection (SQL injection safe)
│
├── includes/
│   ├── functions.php       ← e(), csrf, auth helpers, upload validation
│   ├── header.php          ← navbar + sidebar + A–Z search
│   ├── footer.php
│   └── auth_check.php
│
├── assets/
│   ├── css/style.css       ← poora design system
│   └── js/
│       ├── titan3d.js      ← 3D titan (13 animations)
│       ├── player.js       ← workout engine + rest + congrats
│       └── main.js         ← sidebar, theme, search, calculators
│
├── pages/
│   ├── workouts.php        ├── nutrition.php
│   ├── workout-detail.php  ├── trainers.php
│   ├── player.php          ├── pricing.php
│   ├── calculators.php     ├── scanner-body.php
│   └── scanner-food.php
│
├── auth/
│   ├── login.php           ├── register.php
│   ├── forgot-password.php ├── logout.php
│   └── oauth-google.php / oauth-facebook.php / oauth-instagram.php
│
├── portals/
│   ├── member.php  ├── trainer.php  ├── doctor.php
│   ├── patient.php └── admin.php
│
├── api/
│   ├── book-appointment.php    ← member books
│   ├── appointment-action.php  ← trainer accepts/rejects
│   ├── save-workout.php        ← player saves kcal to DB
│   └── scan.php                ← Claude Vision integration point
│
├── uploads/                ← scanned photos (PHP execution blocked)
└── logs/                   ← db-error.log
```

---

## ✅ Features

- **Esports-style intro** — embers, loading bar, Tap To Enter (session me ek dafa). Original design
  in that mood only — koi real game ka logo/wordmark/font use nahi hua.
- **3D CCA** (Three.js) — clothes + six pack, 13 animations, har exercise pe alag move
- **Workout Player** — timer ring → 15s rest → Congratulations + calories (DB me save)
- **PRO gating** — free workouts sab ke liye, PRO click → login → subscription
- **AI Scanners** — body + food, PRO only, secure image upload (MIME check, 5MB max)
- **5 Portals** — Member / Trainer / Doctor / Patient / Admin (role-based access)
- **Appointments** — member book kare → trainer accept/reject kare (real DB)
- **Disease-safe plans** — patient ki condition ke hisaab se doctor-approved workout
- **A–Z Search** — pages + workouts + trainers + foods, sab searchable
- **Dark / Light theme** — localStorage me save
- **Calculators** — BMI, BMR (Mifflin-St Jeor), water intake, macro split

---

## 🔒 Security

| Threat | Protection |
|---|---|
| SQL Injection | PDO prepared statements (`EMULATE_PREPARES = false`) |
| XSS | `e()` = `htmlspecialchars()` har output pe |
| CSRF | `csrf_field()` + `hash_equals()` har POST form pe |
| Password theft | `password_hash()` bcrypt + `password_verify()` |
| Session hijack | `session_regenerate_id()` login pe, httponly + samesite cookie |
| Malicious upload | `mime_content_type()` check + 5MB limit + PHP execution blocked in uploads/ |
| Broken access | `require_role()` har portal pe + ownership check APIs me |

---

## 🚀 Next Steps (production ke liye)

1. **Stripe subscription checkout** — `.env` me `STRIPE_SECRET_KEY`, `STRIPE_WEBHOOK_SECRET`, `STRIPE_PRICE_PRO_MONTHLY`, aur `STRIPE_PRICE_ELITE_MONTHLY` add karo. Stripe webhook ko `api/stripe-webhook.php` par point karo. Plan sirf signed webhook se active hota hai.
2. **Real emails** — `.env` me `MAIL_FROM` aur server SMTP configure karo. Development reset links protected `logs/password-reset.log` me hain; UI par kabhi show nahi hote.
3. **Real OAuth** — `auth/oauth-*.php` me guide comments hain. `composer require league/oauth2-client`.
4. **Real AI scanning** — `.env` me `ANTHROPIC_API_KEY` (aur zarurat par `ANTHROPIC_MODEL`) add karo. Body scanner photo-only estimates ko medical diagnosis nahi banata; raw scan photos server par retain nahi hotin.
5. `.env` me `APP_ENV=production` karo (errors chhup jayenge).

---

Forged with 🔥 — Core Calorie Advisor
