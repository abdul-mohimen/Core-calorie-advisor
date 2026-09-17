# 🔥 CORE CALORIE ADVISOR — Complete Gym & Fitness Platform
PHP 8 + MySQL + Three.js · XAMPP ready

---

## ⚡ Setup (5 minutes)

1. **Deploy to htdocs**
   Place the `Core calorie advisor` directory into your XAMPP `htdocs`:
   ```
   C:\xampp\htdocs\Core calorie advisor\
   ```

2. **Start XAMPP** — Apache ✅ + MySQL ✅

3. **Import Database**
   - Open in browser: `http://localhost/phpmyadmin`
   - Click the **Import** tab → **Choose File** → select `sql/core_calorie_advisor.sql` → **Go**
   - Creates the `core_calorie_advisor` database with schema (33 tables) + seed data.
   - Or via CLI: `mysql -u root core_calorie_advisor < sql/core_calorie_advisor.sql`

4. **Verify `.env` configuration** (pre-configured for standard XAMPP)
   ```
   DB_USER=root
   DB_PASS=          # Empty by default in XAMPP
   APP_URL=http://localhost/Core calorie advisor
   APP_ENV=development
   ALLOW_SANDBOX_CHECKOUT=true  # Local demo only
   ```

5. **Launch Application** 👉 `http://localhost/Core calorie advisor`

---

## 🔑 Demo Accounts
**Universal Demo Password: `cca123` (Development only — change or purge before production deployment).**

| Role | Email | Features & Scope |
|---|---|---|
| Member | `member@corecalorieadvisor.com` | Pro plan, workout telemetry logs, trainer bookings, AI scanners |
| Trainer | `trainer@corecalorieadvisor.com` | Client roster, pending appointment requests (Accept/Reject), earnings |
| Doctor | `doctor@corecalorieadvisor.com` | Patient consultation queue, disease-safe plans, prescriptions |
| Patient | `patient@corecalorieadvisor.com` | Knee Pain safe plan, doctor consultations, vitals tracker |
| Admin | `admin@corecalorieadvisor.com` | Global analytics, user management, plan elevation/revocation, moderation |

### 💎 Master Pro Demo Access (All Features Unlocked)
| Email | Password | Access |
|---|---|---|
| `pro@corecalorieadvisor.com` | `cca123` | Elite plan — all 120+ workouts, 3D coach, AI vision scanners, consultations |

Administrators can also elevate any user between Free, Pro, and Elite tiers via the **All Users** table.

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

1. **Stripe subscription checkout** — `.env` me `STRIPE_SECRET_KEY`, `STRIPE_WEBHOOK_SECRET`, `STRIPE_PRICE_PRO_MONTHLY`, aur `STRIPE_PRICE_ELITE_MONTHLY` add karo. Stripe webhook ko `api/stripe-webhook.php` par point karo. Plan sirf signed webhook se active hota hai. Production mein `ALLOW_SANDBOX_CHECKOUT` ko remove/false rakho; sandbox endpoint fail closed rehta hai.
2. **Real emails** — `.env` me `MAIL_FROM` aur server SMTP configure karo. Development reset links protected `logs/password-reset.log` me hain; UI par kabhi show nahi hote.
3. **Real OAuth** — `auth/oauth-*.php` me guide comments hain. `composer require league/oauth2-client`.
4. **Real AI scanning** — `.env` me `ANTHROPIC_API_KEY` (aur zarurat par `ANTHROPIC_MODEL`) add karo. Body scanner photo-only estimates ko medical diagnosis nahi banata; raw scan photos server par retain nahi hotin.
5. `.env` me `APP_ENV=production` karo (errors chhup jayenge).

---

Forged with 🔥 — Core Calorie Advisor
