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
    // Allow on local hosts (.test, localhost)
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if (in_array($host, ['localhost', '127.0.0.1'], true)) {
        return true;
    }
    if ($host && preg_match('/\\.test$/', $host)) {
        return true;
    }
    return false;
}

// Hide SCF/ACF admin UI outside allowed environments.
add_filter('acf/settings/show_admin', function ($show) {
    return tmd_scf_admin_allowed();
});

add_action('admin_init', function () {
    if (tmd_scf_admin_allowed()) {
        return;
    }

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    $is_scf_screen = false;

    if ($screen && isset($screen->post_type) && $screen->post_type === 'scf_field_group') {
        $is_scf_screen = true;
    }
    // Secure Custom Fields uses ACF-style post types.
    if ($screen && isset($screen->post_type) && in_array($screen->post_type, ['acf-field-group', 'acf-field'], true)) {
        $is_scf_screen = true;
    }
    if (isset($_GET['post_type']) && in_array($_GET['post_type'], ['acf-field-group', 'acf-field', 'scf_field_group'], true)) {
        $is_scf_screen = true;
    }
    if (isset($_GET['post'])) {
        $post_id = absint($_GET['post']);
        $post = get_post($post_id);
        if ($post && in_array($post->post_type, ['acf-field-group', 'acf-field', 'scf_field_group'], true)) {
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
    // Hide SCF/ACF menu and CPT UI.
    remove_menu_page('edit.php?post_type=acf-field-group');
    remove_menu_page('edit.php?post_type=acf-field');
    remove_menu_page('edit.php?post_type=scf_field_group');
}, 5);
