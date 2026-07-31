# PHASE 1 — Analysis, Verdict & Rebuild Plan

## 1. HONEST VERDICT

- **Architecture**: 5/10. The app relies on custom vanilla PHP with scattered front controllers (`index.php`, `portals/*.php`). There is no strict MVC or separation of concerns, which makes scaling difficult.
- **Security**: 4/10. Based on the Phase 0 audit, there are missing CSRF tokens in forms and potential unescaped outputs. The auth model (`auth/*.php`) needs strict hardening (Secure sessions, CSRF, rate-limiting).
- **Data Model**: 8/10. The `database.sql` is well-structured with 19 tables, using appropriate types and `ON DELETE CASCADE` foreign keys. This is a solid foundation.
- **UI Consistency**: 5/10. The styling is fragmented (e.g., inline styles in `index.php`, multiple stylesheets like `style.css` and `style-premium.css`). There is no unified token system.
- **UX Clarity**: 6/10. The portals are somewhat defined but features overlap and the hierarchy is muddy. The onboarding and navigation lack a clear "one job" focus.
- **Performance**: 4/10. `index.php` loads heavy assets (background video, Three.js engine) upfront, severely impacting LCP (Largest Contentful Paint) and TTI (Time to Interactive).
- **Accessibility**: 4/10. Missing focus rings, insufficient contrast in some badges, and lack of ARIA landmarks across the portal views.
- **3D Quality**: 6/10. The `titan3d.js` engine is functional and supports animations, but suffers from the reported "floating floor" issue and lacks realistic lighting/shadow anchoring.

**What is genuinely good and must be preserved**: 
The database schema is solid and relational integrity is intact. The integration of Three.js via `titan-rig.js` is a strong technical baseline that just needs specific visual and logic refinements.

## 2. KEEP / FIX / REPLACE / DELETE

| Category | Item | Classification | Reason |
|---|---|---|---|
| Core | Database Schema | KEEP | Relational structure is sound and comprehensive. |
| 3D | `titan3d.js` / `titan-rig.js` | FIX | Core logic works, but floor and camera tracking need the Phase 5 overhaul. |
| UI | Portals Layout | REPLACE | Needs unified design system (Phase 4) and strict information architecture. |
| Auth | Login / Register | FIX | Needs security hardening (CSRF, prepared statements review). |

### Deletion Safety Proof

| file | reason | referenced by | safe to delete? |
|---|---|---|---|
| `assets/css/style-premium.css` | Duplicate / fragmented styling. | `index.php` | Yes (Once Phase 4 design system is implemented). |
| `logs/.gitkeep` / `uploads/.gitkeep` | Empty useless files. | Nothing | Yes. |

## 3. THE 5 PORTALS — FINAL SPEC

### 1. Member Portal
- **Primary User**: Gym Goer / Fitness Enthusiast.
- **ONE Job**: Track workouts and calories accurately.
- **Default Landing**: Member Dashboard.
- **Top 3 Metrics**: Calories Burned, Active Streak, Workout Count.
- **Screens**: Dashboard, 3D Workouts, AI Scanner (Food & Body), Nutrition Log, Appointments.

### 2. Patient Portal
- **Primary User**: Individual with medical limitations (e.g., knee pain, heart issues).
- **ONE Job**: Follow doctor-approved, disease-safe workout plans safely.
- **Default Landing**: Health Dashboard.
- **Top 3 Metrics**: Completed Safe Plans, Health Reminders, BMI.
- **Screens**: Health Dashboard, Safe Workouts, Medical Reminders, Profile.

### 3. Trainer Portal
- **Primary User**: Fitness Coach.
- **ONE Job**: Manage client progress and bookings.
- **Default Landing**: Trainer Dashboard.
- **Top 3 Metrics**: Active Clients, Upcoming Appointments, Average Rating.
- **Screens**: Trainer Dashboard, Client List, Appointments, Reviews, Profile.

### 4. Doctor Portal
- **Primary User**: Medical Professional.
- **ONE Job**: Review and authorize safe workout plans for patients.
- **Default Landing**: Doctor Dashboard.
- **Top 3 Metrics**: Pending Approvals, Total Patients, Active Plans.
- **Screens**: Doctor Dashboard, Patient Directory, Plan Approvals, Profile.

### 5. Admin Portal
- **Primary User**: System Administrator.
- **ONE Job**: Manage platform health, users, and revenue.
- **Default Landing**: Admin Dashboard.
- **Top 3 Metrics**: Total Users, MRR (Revenue), Open Issues.
- **Screens**: Admin Dashboard, User Management, Revenue/Subscriptions, Issues & Warnings.

### Cut Features
| Feature | Portal | Justification |
|---|---|---|
| Redundant "Community" Feed | All | Doesn't serve the core job of the app. Better to focus on core tracking. |

## 4. INFORMATION ARCHITECTURE

**New Nav Setup (Max 7 top-level, max 2 deep)**:
- **Member**: Home, Workouts, Nutrition (Scan/Log), Appointments, Settings.
- **Patient**: Home, Safe Plans, Reminders, Settings.
- **Trainer**: Home, Clients, Schedule, Reviews, Settings.
- **Doctor**: Home, Patients, Approvals, Settings.
- **Admin**: Home, Users, Revenue, Reports, System.

**Consistent Page Skeleton**:
- **Header**: Page Title, Context (Breadcrumbs), Primary Action (e.g., "Log Food" button).
- **Filters/Tabs**: Placed below the header if applicable.
- **Content Area**: Grid/List of cards following the Phase 4 anatomy.
- **Empty State**: Illustration, 1 sentence, 1 primary action.

## 5. PHASED ROADMAP

1. **Phase 2: Rebrand (Core Calorie Advisor)**: Update all naming, meta tags, and create the SVG logo system. (Low Risk)
2. **Phase 3: Intro/Loading Screen**: Build the esports-style cinematic intro. (Low Risk)
3. **Phase 4: Design System & Cards**: Implement unified tokens and the card system. (Medium Risk)
4. **Phase 5: 3D Trainer Scene**: Fix the floor clipping and enhance rendering. (High Risk - Requires Three.js precision)
5. **Phase 6: Portals Rebuild**: Rebuild the 5 portals one by one using the design system. (High Risk - Requires extensive UI refactoring)
6. **Phase 7: Hardening & Final QA**: Security fixes, performance tuning, and cross-role testing. (Medium Risk)

## 6. RISK REGISTER

1. **3D Floor Fix Breaks Animations**: Mitigation: Build isolated tests (`orbit-test.mjs`) before integrating into the main viewer.
2. **Design System Conflicts**: Mitigation: Namespace the new CSS tokens and apply them portal by portal.
3. **Authentication Bypass during Refactor**: Mitigation: Retain current session logic while hardening, test all 5 roles extensively.
4. **Mobile Performance Drop with 3D**: Mitigation: Implement quality tiers based on device capability detection.
5. **Database Queries Regressing**: Mitigation: Use EXPLAIN for all new or refactored queries.
6. **Loss of File Upload Integrity**: Mitigation: Validate MIME types and enforce storage outside webroot.
7. **Playwright Tests Flaking**: Mitigation: Add explicit waits for networkidle instead of arbitrary timeouts.
8. **Missing Assets for Portals**: Mitigation: Rely on existing Unsplash links or generate necessary minimal SVGs.
9. **Role Confusion in UI**: Mitigation: Strict server-side role checks on every controller.
10. **Form State Loss on Error**: Mitigation: Ensure PHP re-populates form values on validation failure.
