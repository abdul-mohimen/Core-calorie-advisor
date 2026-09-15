-- Apply once to an existing local demo database.
-- All portal demo accounts use password: cca123
UPDATE users SET plan = 'elite'
WHERE email IN (
  'member@corecalorieadvisor.com',
  'patient@corecalorieadvisor.com',
  'trainer@corecalorieadvisor.com',
  'doctor@corecalorieadvisor.com',
  'admin@corecalorieadvisor.com',
  'pro@corecalorieadvisor.com'
);
