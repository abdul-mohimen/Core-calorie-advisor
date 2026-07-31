<?php
/* ============ CORE CALORIE ADVISOR — portal hero + patti (PHASE I) ============

   Renders a portal page's hero section immediately followed by the quick-access
   strip, so the two stay glued together instead of being copy-pasted as ~6 lines
   of identical markup into every portal page.

   Usage (replaces the hero <section> + the portal_nav() call):

       $portal = 'admin'; $pageTitle = 'Review Moderation';
       include dirname(__DIR__) . '/includes/header.php';
       portal_hero('admin', 'Review', 'Moderation',
                   'Approve or reject member and patient reviews.',
                   ['Dashboard' => url('admin/dashboard.php')], 'Reviews');

   Existing pages are NOT rewritten to use this: they already render correctly
   and rule 0.1.7 says working markup is not rewritten for style. Adopt it for
   new portal pages, and for old ones only when they are being edited anyway.

   $crumbs is [label => href]; $crumbTail is the current page, rendered as plain
   text because a breadcrumb should not link to the page you are already on.
   $links defaults to portal_links($portal) — pass an array only for a genuine
   one-off. */

if (!function_exists('portal_hero')) {

    function portal_hero(
        string  $portal,
        string  $titleLead,
        string  $titleAccent = '',
        string  $subtitle    = '',
        array   $crumbs      = [],
        string  $crumbTail   = '',
        ?array  $links       = null
    ): void {
        echo '<section class="cca-hero cca-hero--gradient">'
           . '<div class="cca-hero__overlay"></div>'
           . '<div class="cca-hero__content"><div class="cca-hero__text">';

        if ($crumbs || $crumbTail !== '') {
            echo '<div class="cca-hero__breadcrumb">';
            foreach ($crumbs as $label => $href) {
                echo '<a href="' . e($href) . '">' . e($label) . '</a> <span class="sep">›</span> ';
            }
            echo e($crumbTail) . '</div>';
        }

        echo '<h1 class="cca-hero__title">' . e($titleLead)
           . ($titleAccent !== '' ? ' <span class="grad">' . e($titleAccent) . '</span>' : '')
           . '</h1>';

        if ($subtitle !== '') {
            echo '<p class="cca-hero__subtitle">' . e($subtitle) . '</p>';
        }

        echo '</div></div></section>';

        /* The whole point: the patti can never be forgotten or drift away from
           its hero, because one call emits both. */
        echo portal_nav($portal, $links);
    }
}
