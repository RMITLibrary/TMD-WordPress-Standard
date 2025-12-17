<?php
/**
 * Plugin Name: Classic Menus Toggle
 * Description: Restores the Appearance → Menus screen for block themes.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', function () {
    // Add the classic Menus page under Appearance, even for block themes.
    add_theme_page(
        __('Menus', 'default'),
        __('Menus', 'default'),
        'edit_theme_options',
        'nav-menus.php'
    );
}, 5);
