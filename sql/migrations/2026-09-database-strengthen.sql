-- ==========================================================
-- CORE CALORIE ADVISOR — Database Strengthening Migration
-- Date: 2026-09
-- Ensures all schema features, telemetry columns, and indexes
-- are fully applied to existing installations.
-- ==========================================================

-- 1. Workout Logs Telemetry Columns
ALTER TABLE `workout_logs`
  ADD COLUMN IF NOT EXISTS `target_seconds`     int(11) NOT NULL DEFAULT 0 AFTER `duration_sec`,
  ADD COLUMN IF NOT EXISTS `blocks_completed`   int(11) NOT NULL DEFAULT 0 AFTER `target_seconds`,
  ADD COLUMN IF NOT EXISTS `rest_added_seconds` int(11) NOT NULL DEFAULT 0 AFTER `blocks_completed`,
  ADD COLUMN IF NOT EXISTS `skips_used`         int(11) NOT NULL DEFAULT 0 AFTER `rest_added_seconds`;

-- 2. Transaction Session Idempotency
-- Allows NULL for sandbox/offline, enforces uniqueness on Stripe session references
SET @exist := (SELECT COUNT(*) FROM information_schema.STATISTICS 
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transactions' AND INDEX_NAME = 'uq_transactions_stripe_session');
SET @sqlstmt := IF(@exist = 0, 'ALTER TABLE `transactions` ADD UNIQUE KEY `uq_transactions_stripe_session` (`stripe_session_id`)', 'SELECT 1');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Demo User Accounts Alignment (all portals unlocked for demo)
UPDATE `users` SET `plan` = 'elite'
WHERE `email` IN (
  'member@corecalorieadvisor.com',
  'patient@corecalorieadvisor.com',
  'trainer@corecalorieadvisor.com',
  'doctor@corecalorieadvisor.com',
  'admin@corecalorieadvisor.com',
  'pro@corecalorieadvisor.com'
);

-- 4. Notification & Query Performance Indexes
SET @exist := (SELECT COUNT(*) FROM information_schema.STATISTICS 
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notifications' AND INDEX_NAME = 'idx_user_read');
SET @sqlstmt := IF(@exist = 0, 'ALTER TABLE `notifications` ADD KEY `idx_user_read` (`user_id`, `is_read`, `created_at`)', 'SELECT 1');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
