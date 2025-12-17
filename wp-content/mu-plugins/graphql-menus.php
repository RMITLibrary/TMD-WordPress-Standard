<?php
/**
 * Plugin Name: GraphQL Menus
 * Description: Expose WordPress navigation menus and items to WPGraphQL for headless clients.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('graphql_register_types', function () {
    if (!function_exists('register_graphql_object_type')) {
        return;
    }

    register_graphql_object_type('MenuItem', [
        'description' => 'Navigation menu item',
        'fields' => [
            'id' => ['type' => 'Int'],
            'title' => ['type' => 'String'],
            'url' => ['type' => 'String'],
            'target' => ['type' => 'String'],
            'description' => ['type' => 'String'],
            'order' => ['type' => 'Int'],
            'parentId' => ['type' => 'Int'],
            'attrTitle' => ['type' => 'String'],
            'classes' => ['type' => ['list_of' => 'String']],
            'type' => ['type' => 'String'],
            'typeLabel' => ['type' => 'String'],
        ],
    ]);

    register_graphql_object_type('Menu', [
        'description' => 'Navigation menu',
        'fields' => [
            'id' => ['type' => 'Int'],
            'name' => ['type' => 'String'],
            'slug' => ['type' => 'String'],
            'description' => ['type' => 'String'],
            'count' => ['type' => 'Int'],
            'items' => ['type' => ['list_of' => 'MenuItem']],
        ],
    ]);

    register_graphql_field('RootQuery', 'menus', [
        'type' => ['list_of' => 'Menu'],
        'description' => 'List of navigation menus',
        'resolve' => function () {
            $menus = wp_get_nav_menus();
            if (empty($menus)) {
                return [];
            }

            return array_map(function ($menu) {
                $items = wp_get_nav_menu_items($menu->term_id, ['orderby' => 'menu_order', 'order' => 'ASC']) ?: [];
                return [
                    'id' => intval($menu->term_id),
                    'name' => $menu->name,
                    'slug' => $menu->slug,
                    'description' => $menu->description,
                    'count' => intval($menu->count),
                    'items' => array_map(function ($item) {
                        return [
                            'id' => intval($item->ID),
                            'title' => $item->title,
                            'url' => $item->url,
                            'target' => $item->target,
                            'description' => $item->description,
                            'order' => intval($item->menu_order),
                            'parentId' => intval($item->menu_item_parent),
                            'attrTitle' => $item->post_excerpt,
                            'classes' => array_filter(explode(' ', $item->classes ?? '')),
                            'type' => $item->type,
                            'typeLabel' => $item->type_label,
                        ];
                    }, $items),
                ];
            }, $menus);
        },
    ]);

    register_graphql_field('RootQuery', 'menu', [
        'type' => 'Menu',
        'description' => 'Single navigation menu by slug or ID',
        'args' => [
            'id' => ['type' => 'Int'],
            'slug' => ['type' => 'String'],
        ],
        'resolve' => function ($root, $args) {
            $menu = null;
            if (!empty($args['id'])) {
                $menu = wp_get_nav_menu_object(intval($args['id']));
            } elseif (!empty($args['slug'])) {
                $menu = wp_get_nav_menu_object(sanitize_title($args['slug']));
            }

            if (!$menu) {
                return null;
            }

            $items = wp_get_nav_menu_items($menu->term_id, ['orderby' => 'menu_order', 'order' => 'ASC']) ?: [];

            return [
                'id' => intval($menu->term_id),
                'name' => $menu->name,
                'slug' => $menu->slug,
                'description' => $menu->description,
                'count' => intval($menu->count),
                'items' => array_map(function ($item) {
                    return [
                        'id' => intval($item->ID),
                        'title' => $item->title,
                        'url' => $item->url,
                        'target' => $item->target,
                        'description' => $item->description,
                        'order' => intval($item->menu_order),
                        'parentId' => intval($item->menu_item_parent),
                        'attrTitle' => $item->post_excerpt,
                        'classes' => array_filter(explode(' ', $item->classes ?? '')),
                        'type' => $item->type,
                        'typeLabel' => $item->type_label,
                    ];
                }, $items),
            ];
        },
    ]);
});
