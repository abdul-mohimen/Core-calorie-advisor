# PHASE 0 — Discovery & Baseline Report

## What I found

### 1. REPO INVENTORY
- **Framework/Language**: Custom PHP/HTML/CSS/JS (No major frontend/backend framework like Laravel or React).
- **Environment**: XAMPP / Apache / Node / MySQL.
- **3D Setup**: Three.js (r128) located in `assets/js/titan3d.js` and `assets/js/titan-rig.js`. GLTFLoader + DRACOLoader.
- **Database**: MySQL/MariaDB (schema in `database.sql`).
- **Entry Points**: 
  - Main: `index.php`
  - Portals: `portals/admin.php`, `portals/member.php`, `portals/doctor.php`, `portals/patient.php`, `portals/trainer.php`
  - API: `api/*.php`
  - Auth: `auth/*.php`

### 2. ROUTE / PAGE MAP
- **Public**: `index.php`, `pages/pricing.php`, `pages/contact.php`, `pages/faq.php`, `pages/privacy-policy.php`, `pages/terms-and-conditions.php`, `auth/login.php`, `auth/register.php`
- **Portals (Auth Required)**: `portals/admin.php`, `portals/member.php`, `portals/patient.php`, `portals/trainer.php`, `portals/doctor.php`
- **Features (Auth Required)**: `pages/workouts.php`, `pages/scanner-body.php`, `pages/scanner-food.php`, `pages/nutrition.php`, `pages/player.php`

### 3. THE 5 PORTALS
Determined from `index.php` and `database.sql` (users table `role` enum):
- **Member**: 3D workouts, calorie tracking, AI scanners.
- **Patient**: Doctor-approved disease-safe plans, reminders.
- **Trainer**: Manage bookings, assign plans.
- **Doctor**: Authorize safe plans, review patient health.
- **Admin**: Full system control.

### 4. DATABASE
- **Schema**: 19 tables. `users`, `trainer_profiles`, `workouts`, `exercises`, `foods`, `appointments`, `workout_logs`, `food_logs`, `body_scans`, `notifications`, `issue_reports`, `warnings`, `feedback`, `community_posts`, `diseases`, `disease_plans`, `reminders`, `password_resets`, `auth_attempts`, `subscriptions`, `shop_items`, `shop_orders`, `reviews`.
- **Integrity**: Mostly enforced by Foreign Keys (`ON DELETE CASCADE`).

### 5. DEAD WEIGHT REPORT
- Unused directories/files: `.gitkeep` in `logs` and `uploads`.
- Duplicate files: `assets/css/style-premium.css` vs `assets/css/style.css` may contain overlaps.

### 6. 3D SCENE INVENTORY
- **Files**: `assets/js/titan3d.js`, `assets/js/titan-rig.js`, `assets/models/trainers.glb`, `assets/models/anims/`.
- **Ground Creation**: `assets/js/titan3d.js` L125-138 (PlaneGeometry, MeshStandardMaterial, rotated -Math.PI / 2).
- **Camera Movement**: Fixed at `camera.position.set(0, 2.1, 6.4)` initially.
- **Clips**: Mapped in `TRAINER_MODEL.clips`.

### 7. QUALITY BASELINE
- **Security**: Raw SQL might exist in some older files, needs prepared statements check.
- **Performance**: High amount of assets loaded on `index.php` (multiple videos, Three.js).
- **Accessibility**: Missing focus rings on some custom buttons, hardcoded px sizes.

### 8. SCREENSHOT BASELINE ("BEFORE" set)
- Created `/_audit/shots.mjs` to automate Playwright screenshots for all roles and 3D scenes.

## What I propose
1. Move forward with Phase 1 to define the rebuild plan and verdict.
2. Address 3D floor clipping by anchoring the model and implementing strict camera clamps (Phase 5).

## What I will delete/replace
*No deletions in Phase 0.*

## Verification plan
- Run the Playwright script to capture the baseline.

## Open questions
1. Should the `assets/css/style-premium.css` completely replace `style.css` in the final build?
2. Are all 3D animation files (`assets/models/anims/*.glb`) currently used or can we prune them?

Maine repo ka pura structure, 5 portals ki details, database schema, aur 3D setup scan kar liya hai. Playwright screenshot script bhi ready hai. Phase 0 complete ho gaya hai aur koi file change nahi ki. Ab aapki approval ka wait hai Phase 1 start karne ke liye.
