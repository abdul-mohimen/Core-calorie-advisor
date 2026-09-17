<?php
require_once dirname(__DIR__) . '/config/config.php';
/* ============ FACEBOOK OAUTH — Setup Guide ============
   1. Create an App in Meta for Developers and obtain App ID and App Secret.
   2. Define FACEBOOK_CLIENT_ID and FACEBOOK_CLIENT_SECRET in .env.
   3. Configure Authorized Redirect URI: BASE_URL/auth/oauth-facebook.php
   4. Install league/oauth2-client via Composer to complete the OAuth flow. */
flash('warn', 'Facebook login is not yet configured — set FACEBOOK_CLIENT_ID in your .env file (see guide in auth/oauth-facebook.php).');
redirect('auth/login.php');
