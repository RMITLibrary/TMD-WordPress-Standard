<?php
/**
 * Plugin Name: Headless Preview Role
 * Description: Adds a minimal role for headless preview access.
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Register or update a minimal preview role.
 */
add_action('init', function () {
	$role_key = 'headless_preview';
	$role_name = 'Headless Preview';

	$base_caps = [
		'read' => true,
		'edit_posts' => true,
	];

	$post_types = apply_filters('tmd_preview_role_post_types', ['material', 'fibre']);
	$custom_caps = [];

	foreach ($post_types as $post_type) {
		$post_type = sanitize_key($post_type);
		if (!$post_type) {
			continue;
		}
		$custom_caps["edit_{$post_type}"] = true;
		$custom_caps["edit_{$post_type}s"] = true;
		$custom_caps["edit_others_{$post_type}s"] = true;
		$custom_caps["edit_published_{$post_type}s"] = true;
		$custom_caps["read_private_{$post_type}s"] = true;
	}

	$caps = array_merge($base_caps, $custom_caps);

	$role = get_role($role_key);
	if (!$role) {
		add_role($role_key, $role_name, $caps);
		return;
	}

	foreach ($caps as $cap => $grant) {
		if ($grant && !$role->has_cap($cap)) {
			$role->add_cap($cap);
		}
	}
});
