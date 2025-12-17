<?php
/**
 * Plugin Name: GraphQL CORS Headers
 * Description: Adds CORS headers for the GraphQL endpoint for headless frontends.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Allow the frontend origin (set via constant/env) to call /graphql.
 * Set FRONTEND_URL (or FRONTEND_ORIGIN) in wp-config.php or the environment, e.g.:
 * define('FRONTEND_URL', 'https://your-astro-site.netlify.app');
 */
add_filter('graphql_response_headers_to_send', function ($headers) {
    $origin = '';

    // Prefer FRONTEND_URL, fall back to FRONTEND_ORIGIN, else wildcard.
    if (defined('FRONTEND_URL') && FRONTEND_URL) {
        $origin = FRONTEND_URL;
    } elseif (defined('FRONTEND_ORIGIN') && FRONTEND_ORIGIN) {
        $origin = FRONTEND_ORIGIN;
    } elseif (getenv('FRONTEND_URL')) {
        $origin = getenv('FRONTEND_URL');
    } elseif (getenv('FRONTEND_ORIGIN')) {
        $origin = getenv('FRONTEND_ORIGIN');
    }

    // Safer fallback: same-origin (site URL) instead of wildcard.
    if (!$origin) {
        $origin = home_url();
    }

    $headers['Access-Control-Allow-Origin'] = $origin;
    $headers['Access-Control-Allow-Methods'] = 'GET, POST, OPTIONS';
    $headers['Access-Control-Allow-Credentials'] = 'true';
    $headers['Access-Control-Allow-Headers'] = 'Authorization, Content-Type';

    return $headers;
});
