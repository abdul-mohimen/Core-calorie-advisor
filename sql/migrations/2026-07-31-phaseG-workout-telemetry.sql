-- PHASE G — workout session telemetry
-- Adds the four columns api/save-workout.php now writes. Without these the
-- INSERT fails and every completed session is lost, so run this before using
-- the updated player.
--
-- Apply:  mysql -u root core_calorie_advisor < sql/migrations/2026-07-31-phaseG-workout-telemetry.sql
--
-- Idempotent: MariaDB/MySQL 8 support IF NOT EXISTS on ADD COLUMN.

ALTER TABLE `workout_logs`
  ADD COLUMN IF NOT EXISTS `target_seconds`     int(11) NOT NULL DEFAULT 0 AFTER `duration_sec`,
  ADD COLUMN IF NOT EXISTS `blocks_completed`   int(11) NOT NULL DEFAULT 0 AFTER `target_seconds`,
  ADD COLUMN IF NOT EXISTS `rest_added_seconds` int(11) NOT NULL DEFAULT 0 AFTER `blocks_completed`,
  ADD COLUMN IF NOT EXISTS `skips_used`         int(11) NOT NULL DEFAULT 0 AFTER `rest_added_seconds`;
