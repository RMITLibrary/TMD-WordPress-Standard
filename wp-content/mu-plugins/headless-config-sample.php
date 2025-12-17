<?php
/**
 * Plugin Name: Headless Config (Sample)
 * Description: Central place to define headless-related constants for frontend/CORS/Netlify/preview. Copy values into wp-config.php and remove this sample file from production.
 */

// Uncomment and set your values in wp-config.php (not here):
// define('FRONTEND_URL', 'https://your-site.netlify.app');
// define('FRONTEND_PREVIEW_URL', 'https://your-site.netlify.app/preview');
// define('NETLIFY_BUILD_HOOK_URL', 'https://api.netlify.com/build_hooks/xxxxxxxx');
// define('FRONTEND_ORIGIN', 'https://your-site.netlify.app'); // optional alias

// Notes:
// - FRONTEND_URL is used by GraphQL CORS, preview broker fallback, and general frontend references.
// - FRONTEND_PREVIEW_URL is the redirect target for WP preview links (Astro /preview route).
// - NETLIFY_BUILD_HOOK_URL triggers builds on content changes.
// - Keep secrets (JWT) in wp-config.php; don’t commit them.
