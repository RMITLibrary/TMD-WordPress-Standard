<?php
/**
 * Plugin Name: Classic Editor Enforcer
 * Description: Forces the classic editor for all post types (headless content editing).
 */

if (!defined('ABSPATH')) {
    exit;
}

// Disable block editor sitewide.
add_filter('use_block_editor_for_post_type', '__return_false', 10, 2);
add_filter('use_block_editor_for_post', '__return_false', 10, 2);

// Keep the classic meta boxes/UI enabled.
add_filter('classic_editor_network_default_settings', function ($settings) {
    $settings['editor'] = 'classic';
    $settings['allow-users'] = 0;
    return $settings;
});
