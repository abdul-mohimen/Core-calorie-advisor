-- Apply once to an existing Core Calorie Advisor database.
-- Stripe checkout session IDs are single-use ledger references. NULL remains
-- allowed for offline and sandbox records.
ALTER TABLE transactions ADD UNIQUE KEY uq_transactions_stripe_session (stripe_session_id);
