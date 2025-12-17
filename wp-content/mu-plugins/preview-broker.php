<?php
/**
 * Plugin Name: Headless Preview Broker
 * Description: Redirects WP previews to the Astro frontend with a short-lived token for authenticated draft/previews.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Config
 * Set FRONTEND_URL (or FRONTEND_ORIGIN) and FRONTEND_PREVIEW_URL in wp-config.php or env.
 * Example:
 * define('FRONTEND_URL', 'https://your-site.netlify.app');
 * define('FRONTEND_PREVIEW_URL', 'https://your-site.netlify.app/preview');
 */
function tmd_preview_frontend_url() {
    if (defined('FRONTEND_PREVIEW_URL') && FRONTEND_PREVIEW_URL) {
        return FRONTEND_PREVIEW_URL;
    }
    if ($env = getenv('FRONTEND_PREVIEW_URL')) {
        return $env;
    }
    // Fallback to FRONTEND_URL + /preview
    $base = '';
    if (defined('FRONTEND_URL') && FRONTEND_URL) {
        $base = rtrim(FRONTEND_URL, '/');
    } elseif ($env = getenv('FRONTEND_URL')) {
        $base = rtrim($env, '/');
    }
    if ($base) {
        return $base . '/preview';
    }
    return '';
}

/**
 * Generate a short-lived signed token (HMAC) without extra libs.
 */
function tmd_preview_token($payload, $ttl = 300) {
    $secret = defined('AUTH_KEY') ? AUTH_KEY : (defined('SECURE_AUTH_KEY') ? SECURE_AUTH_KEY : wp_salt());
    $payload['exp'] = time() + $ttl;
    $json = wp_json_encode($payload);
    $b64 = rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
    $sig = hash_hmac('sha256', $b64, $secret);
    return $b64 . '.' . $sig;
}

function tmd_preview_token_verify($token) {
    $secret = defined('AUTH_KEY') ? AUTH_KEY : (defined('SECURE_AUTH_KEY') ? SECURE_AUTH_KEY : wp_salt());
    $parts = explode('.', $token);
    if (count($parts) !== 2) {
        return false;
    }
    list($b64, $sig) = $parts;
    $calc = hash_hmac('sha256', $b64, $secret);
    if (!hash_equals($calc, $sig)) {
        return false;
    }
    $json = base64_decode(strtr($b64, '-_', '+/'));
    $payload = json_decode($json, true);
    if (!$payload || !isset($payload['exp']) || time() > intval($payload['exp'])) {
        return false;
    }
    return $payload;
}

/**
 * Override the preview link to point to Astro.
 */
add_filter('preview_post_link', function ($preview_link, $post) {
    $frontend = tmd_preview_frontend_url();
    if (!$frontend) {
        return $preview_link;
    }

    // Ensure user can edit and is logged in.
    if (!current_user_can('edit_post', $post->ID)) {
        return $preview_link;
    }

    // Nonce for extra protection.
    $nonce = wp_create_nonce('tmd_preview_nonce_' . $post->ID);

    $payload = [
        'id'   => $post->ID,
        'type' => $post->post_type,
        'nonce' => $nonce,
        'user' => get_current_user_id(),
    ];

    $token = tmd_preview_token($payload, 300); // 5 min
    $query = http_build_query([
        'id' => $post->ID,
        'type' => $post->post_type,
        'token' => $token,
    ]);

    return $frontend . '?' . $query;
}, 10, 2);

/**
 * Optionally expose a tiny verify endpoint for Astro to validate token (not strictly required if Astro trusts and only uses token server-side).
 */
add_action('rest_api_init', function () {
    register_rest_route('tmd/v1', '/preview-verify', [
        'methods' => 'POST',
        'callback' => function (\WP_REST_Request $request) {
            $token = $request->get_param('token');
            $id    = intval($request->get_param('id'));
            $type  = sanitize_text_field($request->get_param('type'));

            if (!$token || !$id || !$type) {
                return new \WP_Error('invalid', 'Missing data', ['status' => 400]);
            }

            $payload = tmd_preview_token_verify($token);
            if (!$payload || intval($payload['id']) !== $id || $payload['type'] !== $type) {
                return new \WP_Error('forbidden', 'Invalid token', ['status' => 403]);
            }

            if (!current_user_can('edit_post', $id)) {
                return new \WP_Error('forbidden', 'Insufficient capability', ['status' => 403]);
            }

            return [
                'valid' => true,
                'id' => $id,
                'type' => $type,
            ];
        },
        'permission_callback' => '__return_true', // Token is the gate; ensure HTTPS.
    ]);
});
