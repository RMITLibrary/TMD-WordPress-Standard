<?php
/**
 * Plugin Name: Custom Post Type Menu Dividers
 * Description: Adds visual separators in admin menus between post type actions and taxonomies
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Add separators to custom post type menus
 * Separates post management links from taxonomy links
 */
add_action('admin_menu', 'add_cpt_menu_separators', 999);

function add_cpt_menu_separators() {
    global $submenu;

    // Define which post types should have separators
    $post_types_with_separators = array(
        'material',
        'fibre'
    );

    foreach ($post_types_with_separators as $post_type) {
        // Check if this post type has a menu
        $menu_slug = 'edit.php?post_type=' . $post_type;

        if (!isset($submenu[$menu_slug])) {
            continue;
        }

        // Create a visual separator
        $separator = array(
            '———', // Separator label (em dashes)
            'read', // Capability (everyone can see it)
            '#', // URL (no link)
            'cpt-menu-separator' // CSS class
        );

        // Collect all existing menu items
        $menu_items = array();
        $separator_inserted = false;

        foreach ($submenu[$menu_slug] as $position => $item) {
            // Add items up to and including position 20 (Add New)
            if ($position <= 20) {
                $menu_items[$position] = $item;
            }
            // After position 20, insert separator once, then continue with other items
            else {
                if (!$separator_inserted) {
                    $menu_items[21] = $separator;
                    $separator_inserted = true;
                    $menu_items[$position + 1] = $item; // Shift taxonomy items down
                } else {
                    $menu_items[$position + 1] = $item;
                }
            }
        }

        // Replace the submenu with our reorganized version
        $submenu[$menu_slug] = $menu_items;

        // Re-sort the submenu to maintain proper order
        ksort($submenu[$menu_slug]);
    }
}

/**
 * Add CSS to style the menu separators
 */
add_action('admin_head', 'style_cpt_menu_separators');

function style_cpt_menu_separators() {
    ?>
    <style>
        /* Style for custom post type menu separators */
        #adminmenu .cpt-menu-separator {
            pointer-events: none;
            cursor: default;
            color: #a7aaad;
            font-size: 11px;
            text-align: center;
            margin: 5px 0;
            padding: 5px 0 !important;
            opacity: 0.5;
        }

        #adminmenu .cpt-menu-separator:hover {
            background-color: transparent !important;
            color: #a7aaad !important;
        }

        #adminmenu .cpt-menu-separator a {
            cursor: default !important;
            pointer-events: none;
            text-decoration: none !important;
        }

        /* Alternative: Use a line separator instead of dashes */
        #adminmenu .cpt-menu-separator.line-separator {
            height: 1px;
            background: linear-gradient(to right, transparent, #a7aaad, transparent);
            margin: 8px 12px;
            padding: 0 !important;
        }

        #adminmenu .cpt-menu-separator.line-separator a {
            display: none;
        }

        /* Style for taxonomy header label */
        #adminmenu .cpt-taxonomy-header {
            pointer-events: none;
            cursor: default;
            color: #8c8f94;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 8px 12px 4px !important;
            margin-top: 5px;
        }

        #adminmenu .cpt-taxonomy-header:hover {
            background-color: transparent !important;
            color: #8c8f94 !important;
        }

        #adminmenu .cpt-taxonomy-header a {
            cursor: default !important;
            pointer-events: none;
            text-decoration: none !important;
            color: #8c8f94 !important;
        }
    </style>
    <?php
}
