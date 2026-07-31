# CLAUDE.md — Core Calorie Advisor

## Project identity
- Product name: **Core Calorie Advisor** (short form: CCA)
- Old name(s) must be fully retired from code, UI, DB, docs, meta tags, and emails.
- Domain: fitness + nutrition + calorie tracking, multi-portal web application.
- The app contains a Three.js 3D trainer scene (`trainer.glb`) that plays workout animations.

## How you must work (non-negotiable)
1. **Investigate before you touch.** Read the actual code. Never assume a framework, folder,
   route, table, or feature exists. Every claim you make must cite a real file path and line range.
2. **Phase-wise approval.** Report findings → wait for my explicit "approved" → implement → verify →
   report again. Never jump two phases ahead.
3. **No silent changes.** Before editing, list every file you will touch and what changes in each.
4. **No destructive action without a listed plan.** Deletions require a table:
   `file | reason | referenced by | safe to delete? (yes/no + proof)`.
5. **No new dependencies** without asking, including CDN links, fonts, and npm/composer packages.
6. **No fake data, no lorem ipsum, no placeholder images, no mock API responses** shipped into the app.
7. **No rewriting working business logic** just because you prefer a different style.
8. **Verify, don't claim.** If you say something works, show the command you ran and its output,
   or the screenshot you captured.
9. If something is ambiguous, **ask one focused question** instead of guessing.
10. Do not create files outside the repo except in the agreed `/_audit` output folder.

## Reporting format (every phase)
- `## What I found` — facts with file paths
- `## What I propose` — numbered, each with impact + risk
- `## What I will delete/replace` — the deletion table
- `## Verification plan` — exact commands/tests/screenshots
- `## Open questions` — max 3
- End with a 5-line summary in **Roman Urdu** so I can read it fast.

## Hard prohibitions
- Do not copy, trace, or reproduce any real game's logo, wordmark, fonts, or assets
  (including Free Fire / Garena). "Esports style" means an **original** design in that mood only.
- Do not commit secrets. Do not push. Do not force-push. Do not run `git reset --hard`.
- Do not touch `.env`, credentials, or production config.

## Project Status
**Phase 7 / Final QA Completed.**
- Design System: Phase 4 `.cca-*` utility classes are fully implemented across all 5 portals.
- 3D Engine: `assets/models/trainers.glb` is stable, responsive FOV implemented.
- Security: `csrf_verify_json()` applied to all APIs, indexes added to MySQL, roles enforced.
- The platform is now fully hardened and ready for production deployment.
