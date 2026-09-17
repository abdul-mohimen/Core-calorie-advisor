<?php
require_once dirname(__DIR__) . '/config/config.php';
/* ============ INSTAGRAM OAUTH — Setup Guide ============
   1. Create an App in Meta for Developers with Instagram Basic Display.
   2. Define INSTAGRAM_CLIENT_ID and INSTAGRAM_CLIENT_SECRET in .env.
   3. Configure Authorized Redirect URI: BASE_URL/auth/oauth-instagram.php
   4. Install league/oauth2-client via Composer to complete the OAuth flow. */
flash('warn', 'Instagram login is not yet configured — set INSTAGRAM_CLIENT_ID in your .env file (see guide in auth/oauth-instagram.php).');
redirect('auth/login.php');
