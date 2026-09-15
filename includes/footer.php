</main>
<?php $authMinimal = $authMinimal ?? false; ?>
<?php if (!$authMinimal): ?>

<footer>
  <div class="wrap foot-mini">
    <a class="brand" href="<?= $u ? url("portals/$role.php") : url('index.php') ?>">
      <?= $BRAND_SVG ?? '' ?>
      <b>CORE<em>CALORIE</em></b></a>
    <nav class="foot-links">
      <a href="<?= url('pages/workouts.php') ?>">Workouts</a>
      <a href="<?= url('pages/pricing.php') ?>">Pricing</a>
      <a href="<?= url('pages/privacy-policy.php') ?>">Privacy</a>
      <a href="<?= url('pages/terms-and-conditions.php') ?>">Terms</a>
    </nav>
    <span class="foot-copy">© <?= date('Y') ?> Core Calorie Advisor</span>
  </div>
</footer>

<?php
/* ---- Premium AI assistant avatar (neural brain icon — modern AI design) ---- */
$TF_AVATAR = <<<SVG
<svg class="tf-ava" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
  <defs>
    <linearGradient id="aiGrad" x1="0%" y1="100%" x2="100%" y2="0%">
      <stop offset="0%" stop-color="#FF3D00" />
      <stop offset="45%" stop-color="#FF6B1A" />
      <stop offset="100%" stop-color="#FFC02E" />
    </linearGradient>
    <radialGradient id="aiEye" cx="50%" cy="50%" r="50%">
      <stop offset="0%" stop-color="#FFFFFF" />
      <stop offset="60%" stop-color="#FFE08A" />
      <stop offset="100%" stop-color="#FF8A00" />
    </radialGradient>
    <filter id="aiGlow" x="-40%" y="-40%" width="180%" height="180%">
      <feGaussianBlur stdDeviation="1.4" result="blur" />
      <feComposite in="SourceGraphic" in2="blur" operator="over" />
    </filter>
  </defs>
  <!-- Clean friendly robot assistant — reads clearly even at 24px FAB size -->
  <!-- Antenna -->
  <line x1="32" y1="7" x2="32" y2="15" stroke="url(#aiGrad)" stroke-width="3" stroke-linecap="round" />
  <circle cx="32" cy="6" r="3" fill="#FFC02E" filter="url(#aiGlow)" />
  <!-- Head shell -->
  <rect x="12" y="15" width="40" height="34" rx="12" fill="url(#aiGrad)" />
  <!-- Side ears -->
  <rect x="8" y="27" width="4.5" height="10" rx="2.25" fill="url(#aiGrad)" />
  <rect x="51.5" y="27" width="4.5" height="10" rx="2.25" fill="url(#aiGrad)" />
  <!-- Dark face screen -->
  <rect x="18" y="21" width="28" height="22" rx="9" fill="#141422" />
  <!-- Glowing eyes -->
  <circle cx="26" cy="31" r="3.6" fill="url(#aiEye)" filter="url(#aiGlow)" />
  <circle cx="38" cy="31" r="3.6" fill="url(#aiEye)" filter="url(#aiGlow)" />
  <!-- Friendly smile -->
  <path d="M25 38 Q32 42 39 38" stroke="#FFC02E" stroke-width="2.2" fill="none" stroke-linecap="round" />
</svg>
SVG;
?>
<!-- ============ CCA ASSISTANT — premium AI chatbot ============ -->
<div id="tf-chat" class="fixed bottom-6 right-6 z-50" data-open="false">
  <button id="tfChatToggle" class="tf-chat-fab shadow-[0_0_20px_rgba(255,107,26,0.4)]" aria-label="Open CCA Assistant">
    <span class="tf-chat-fab-ava"><?= $TF_AVATAR ?></span>
    <span class="tf-chat-ping"></span>
  </button>
  <section class="tf-chat-panel" id="tfChatPanel" aria-live="polite">
    <header class="tf-chat-head">
      <span class="tf-chat-head-ava"><?= $TF_AVATAR ?></span>
      <div class="tf-chat-head-meta">
        <b>CCA Assistant</b>
        <small><i class="tf-dot"></i> AI Online · replies instantly</small>
      </div>
      <button id="tfChatClose" class="tf-chat-x" aria-label="Close">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M6 6 18 18M18 6 6 18"/></svg>
      </button>
    </header>
    <div class="tf-chat-body" id="tfChatBody"></div>
    <div class="tf-chat-quick" id="tfChatQuick"></div>
    <form class="tf-chat-input" id="tfChatForm" autocomplete="off">
      <input id="tfChatText" type="text" placeholder="Ask CCA Assistant anything…" maxlength="300">
      <button type="submit" class="tf-chat-send" aria-label="Send">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2 11 13"/><path d="M22 2 15 22 11 13 2 9 22 2Z"/></svg>
      </button>
    </form>
  </section>
</div>

<?php endif; ?>
</div><!-- /#app -->
<script src="<?= asset('js/main.js') ?>"></script>
<?php if (!$authMinimal): ?>
<script src="<?= asset('js/chatbot.js') ?>"></script>
<script src="<?= asset('js/hero-anims.js') ?>"></script>
<script src="<?= asset('js/particles.js') ?>"></script>
<script src="<?= asset('js/muscle-map.js') ?>"></script>
<?php if ($u): ?><script src="<?= asset('js/notifications.js') ?>"></script>
<script src="<?= asset('js/account-menu.js') ?>"></script><?php endif; ?>
<?php endif; ?>
<script defer src="<?= asset('js/motion.js') ?>"></script>
<?= $extraScripts ?? '' ?>
</body>
</html>
