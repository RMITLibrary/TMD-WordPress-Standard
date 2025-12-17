<?php
/**
 * Plugin Name: GraphQL Redirection Export
 * Description: Expose Redirection plugin rules to WPGraphQL for the headless frontend.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('graphql_register_types', function () {
    // Bail if WPGraphQL not active.
    if (!function_exists('register_graphql_object_type')) {
        return;
    }
    // Bail if Redirection isn’t active.
    if (!class_exists('Red_Item')) {
        return;
    }

    register_graphql_object_type('RedirectRule', [
        'description' => 'Redirection rule (enabled only)',
        'fields' => [
            'id' => ['type' => 'Int'],
            'source' => ['type' => 'String'],
            'target' => ['type' => 'String'],
            'statusCode' => ['type' => 'Int'],
            'regex' => ['type' => 'Boolean'],
            'position' => ['type' => 'Int'],
            'groupId' => ['type' => 'Int'],
            'groupName' => ['type' => 'String'],
        ],
    ]);

    register_graphql_field('RootQuery', 'redirects', [
        'type' => ['list_of' => 'RedirectRule'],
        'description' => 'List of enabled redirects from the Redirection plugin',
        'args' => [
            'limit' => [
                'type' => 'Int',
                'description' => 'Max results (default 200, max 1000)',
            ],
            'search' => [
                'type' => 'String',
                'description' => 'Search source or target URL (LIKE)',
            ],
            'groupId' => [
                'type' => 'Int',
                'description' => 'Filter by Redirection group ID',
            ],
        ],
        'resolve' => function ($root, $args) {
            global $wpdb;

            // Map group IDs to names (optional).
            $groups = [];
            $group_table = $wpdb->prefix . 'redirection_groups';
            $group_rows = $wpdb->get_results("SELECT id, name FROM {$group_table}");
            if ($group_rows) {
                foreach ($group_rows as $g) {
                    $groups[intval($g->id)] = $g->name;
                }
            }

            $limit = isset($args['limit']) ? intval($args['limit']) : 200;
            if ($limit < 1) {
                $limit = 1;
            }
            if ($limit > 1000) {
                $limit = 1000;
            }

            $items = Red_Item::get_all();
            if (!$items) {
                return [];
            }

            $results = [];
            foreach ($items as $item) {
                /** @var Red_Item $item */
                if (!$item->is_enabled()) {
                    continue;
                }
                if ($item->get_action_type() !== 'url') {
                    continue;
                }

                $source = $item->get_url(); // original/source pattern
                $target = $item->get_action_data();
                if (!empty($args['search'])) {
                    $needle = strtolower($args['search']);
                    if (strpos(strtolower($source), $needle) === false && strpos(strtolower($target), $needle) === false) {
                        continue;
                    }
                }

                $group_id = $item->get_group_id();
                if (!empty($args['groupId']) && intval($args['groupId']) !== $group_id) {
                    continue;
                }

                $results[] = [
                    'id' => $item->get_id(),
                    'source' => $source,
                    'target' => $target,
                    'statusCode' => $item->get_action_code(),
                    'regex' => $item->is_regex(),
                    'position' => $item->get_position(),
                    'groupId' => $group_id ?: null,
                    'groupName' => $group_id && isset($groups[$group_id]) ? $groups[$group_id] : null,
                ];

                if (count($results) >= $limit) {
                    break;
                }
            }

            return $results;
        },
    ]);
});
