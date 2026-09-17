<?php
require_once dirname(__DIR__) . '/config/config.php';
/* ============ GOOGLE OAUTH — Setup Guide ============
   1. Create an OAuth app in Google Cloud Console to obtain Client ID/Secret.
   2. Define GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET in .env.
   3. Configure Authorized Redirect URI: BASE_URL/auth/oauth-google.php
   4. Install league/oauth2-client via Composer to handle token handshake:
      authorize -> callback -> user info -> find/create user in DB -> set session. */
flash('warn', 'Google login is not yet configured — set GOOGLE_CLIENT_ID in your .env file (see guide in auth/oauth-google.php).');
redirect('auth/login.php');
