<?php
require_once dirname(__DIR__) . '/config/config.php';
/* ============ INSTAGRAM OAUTH — Setup Guide ============
   1. Instagram developer console me app banao aur Client ID/Secret lo
   2. .env me INSTAGRAM_CLIENT_ID / INSTAGRAM_CLIENT_SECRET dalo
   3. Redirect URI set karo:  BASE_URL/auth/oauth-instagram.php
   4. Composer se league/oauth2-client install karo aur yahan flow likho:
      authorize -> callback -> user info -> users table me find/create -> session set
   Abhi demo message dikhate hain: */
flash('warn', 'Instagram login abhi configure nahi hua — .env me INSTAGRAM_CLIENT_ID set karo (auth/oauth-instagram.php me guide hai).');
redirect('auth/login.php');
