<?php
/**
 * Plugin Name: SCF Admin Lock
 * Description: Disables Secure Custom Fields UI outside local/dev to prevent production edits. Override with ALLOW_SCF_ADMIN=true.
 */

if (!defined('ABSPATH')) {
    exit;
}

function tmd_scf_admin_allowed() {
    if (defined('ALLOW_SCF_ADMIN') && ALLOW_SCF_ADMIN) {
        return true;
    }
    if (defined('WP_ENV') && in_array(strtolower(WP_ENV), ['development', 'local'], true)) {
        return true;
    }
    // Allow on localhost loopback
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if (in_array($host, ['localhost', '127.0.0.1'], true)) {
        return true;
    }
    return false;
}

add_action('admin_init', function () {
    if (tmd_scf_admin_allowed()) {
        return;
    }

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    $is_scf_screen = false;

    if ($screen && isset($screen->post_type) && $screen->post_type === 'scf_field_group') {
        $is_scf_screen = true;
    }
    if (isset($_GET['post_type']) && $_GET['post_type'] === 'scf_field_group') {
        $is_scf_screen = true;
    }
    if (isset($_GET['post'])) {
        $post_id = absint($_GET['post']);
        $post = get_post($post_id);
        if ($post && $post->post_type === 'scf_field_group') {
            $is_scf_screen = true;
        }
    }

    if ($is_scf_screen) {
        wp_die(
            __('SCF editing is disabled on this environment.', 'default'),
            __('Access denied', 'default'),
            ['response' => 403]
        );
    }
});

add_action('admin_menu', function () {
    if (tmd_scf_admin_allowed()) {
        return;
    }
    // Hide SCF menu and any CPT UI for SCF.
    remove_menu_page('edit.php?post_type=scf_field_group');
}, 5);
