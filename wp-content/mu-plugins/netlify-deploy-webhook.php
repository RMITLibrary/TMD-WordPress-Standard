<?php
/**
 * Plugin Name: Netlify Deploy Webhook
 * Description: Triggers a Netlify build hook when content changes. Uses NETLIFY_BUILD_HOOK_URL or NETLIFY_DEPLOY_HOOK env/constant.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get the Netlify webhook URL from constants/env.
 */
function tmd_netlify_webhook_url() {
    if (defined('NETLIFY_BUILD_HOOK_URL') && NETLIFY_BUILD_HOOK_URL) {
        return NETLIFY_BUILD_HOOK_URL;
    }
    if (defined('NETLIFY_DEPLOY_HOOK') && NETLIFY_DEPLOY_HOOK) {
        return NETLIFY_DEPLOY_HOOK;
    }
    if ($env = getenv('NETLIFY_BUILD_HOOK_URL')) {
        return $env;
    }
    if ($env = getenv('NETLIFY_DEPLOY_HOOK')) {
        return $env;
    }
    return '';
}

/**
 * Trigger the webhook (throttled to avoid storms).
 */
function tmd_netlify_trigger_build($context = 'content-change') {
    $hook_url = tmd_netlify_webhook_url();
    if (!$hook_url) {
        return;
    }

    // Throttle: only one trigger per 60s
    $lock_key = 'tmd_netlify_build_lock';
    if (get_transient($lock_key)) {
        return;
    }
    set_transient($lock_key, 1, 60);

    $body = [
        'triggered_by' => $context,
        'timestamp' => time(),
        'site_url' => get_site_url(),
    ];

    wp_remote_post($hook_url, [
        'timeout' => 10,
        'blocking' => false,
        'headers' => ['Content-Type' => 'application/json'],
        'body' => wp_json_encode($body),
    ]);
}

/**
 * Hook into content changes: publish/update/delete posts and taxonomy term changes.
 */
add_action('transition_post_status', function ($new_status, $old_status, $post) {
    // Only fire when something moves into or out of publish, or updates while published.
    if ($new_status === 'publish' || $old_status === 'publish') {
        tmd_netlify_trigger_build('post:' . $post->post_type);
    }
}, 10, 3);

add_action('edit_terms', function ($term_id, $tt_id, $taxonomy) {
    tmd_netlify_trigger_build('term:' . $taxonomy);
}, 10, 3);

add_action('created_term', function ($term_id, $tt_id, $taxonomy) {
    tmd_netlify_trigger_build('term:' . $taxonomy);
}, 10, 3);

add_action('delete_term', function ($term, $tt_id, $taxonomy, $deleted_term, $object_ids) {
    tmd_netlify_trigger_build('term:' . $taxonomy);
}, 10, 5);
