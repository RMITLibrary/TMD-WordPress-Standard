<?php
/**
 * Plugin Name: Menu Support
 * Description: Adds menu support and registers menu locations for headless navigation exports.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('after_setup_theme', function () {
    // Ensure menus are supported even if the current theme is a block theme.
    add_theme_support('menus');

    // Register common locations so classic Menus UI can assign them.
    register_nav_menus([
        'primary' => __('Primary Menu', 'default'),
        'footer'  => __('Footer Menu', 'default'),
    ]);
});
