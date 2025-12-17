<?php

/**
 * Plugin Name: ACF JSON Location
 * Description: Stores ACF field group JSON in /wp-content/acf-json (not the theme).
 */

add_action('plugins_loaded', function () {
  $acf_json_dir = WP_CONTENT_DIR . '/acf-json';

  if (!is_dir($acf_json_dir)) {
    wp_mkdir_p($acf_json_dir);
  }

  add_filter('acf/settings/save_json', function ($path) use ($acf_json_dir) {
    return $acf_json_dir;
  });

  add_filter('acf/settings/load_json', function ($paths) use ($acf_json_dir) {
    // Keep existing paths too (theme/plugin JSON), just add ours first.
    array_unshift($paths, $acf_json_dir);
    return $paths;
  });
}, 1);
