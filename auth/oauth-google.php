<?php
require_once dirname(__DIR__) . '/config/config.php';
/* ============ GOOGLE OAUTH — Setup Guide ============
   1. Google developer console me app banao aur Client ID/Secret lo
   2. .env me GOOGLE_CLIENT_ID / GOOGLE_CLIENT_SECRET dalo
   3. Redirect URI set karo:  BASE_URL/auth/oauth-google.php
   4. Composer se league/oauth2-client install karo aur yahan flow likho:
      authorize -> callback -> user info -> users table me find/create -> session set
   Abhi demo message dikhate hain: */
flash('warn', 'Google login abhi configure nahi hua — .env me GOOGLE_CLIENT_ID set karo (auth/oauth-google.php me guide hai).');
redirect('auth/login.php');
