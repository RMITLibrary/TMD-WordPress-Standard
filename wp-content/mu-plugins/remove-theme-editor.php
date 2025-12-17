<?php
/**
 * Plugin Name: Remove Site Editor
 * Description: Removes the Site Editor (Full Site Editing) from the admin menu
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Remove Site Editor from Appearance menu
 *
 * Note: DISALLOW_FILE_EDIT handles theme/plugin file editors
 * Note: DISALLOW_FILE_MODS handles plugin/theme installation
 * This plugin removes the Site Editor which isn't covered by those constants
 */
add_action('admin_menu', function() {
    // Remove Site Editor (site-editor.php) - Full Site Editing / Block Theme Editor
    remove_submenu_page('themes.php', 'site-editor.php');
}, 999);
