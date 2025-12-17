<?php
/**
 * Plugin Name: Remove Yoast SEO Taxonomy Columns
 * Description: Removes Yoast SEO columns (readability, SEO score) from taxonomy admin tables
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Remove Yoast SEO columns from all taxonomy tables
 */
add_action('admin_init', 'remove_yoast_taxonomy_columns', 999);

function remove_yoast_taxonomy_columns() {
    // Get all registered taxonomies
    $taxonomies = get_taxonomies(array('public' => true), 'names');

    foreach ($taxonomies as $taxonomy) {
        // Remove SEO score column
        add_filter("manage_edit-{$taxonomy}_columns", 'remove_yoast_seo_columns', 999);

        // Remove the column content as well
        add_filter("manage_{$taxonomy}_custom_column", 'remove_yoast_seo_column_content', 999, 3);
    }
}

/**
 * Filter out Yoast SEO columns from taxonomy tables
 */
function remove_yoast_seo_columns($columns) {
    // Remove Yoast SEO score column
    if (isset($columns['wpseo-score'])) {
        unset($columns['wpseo-score']);
    }

    // Remove Yoast SEO score (alternative column name)
    if (isset($columns['wpseo-score-readability'])) {
        unset($columns['wpseo-score-readability']);
    }

    // Remove Yoast readability score
    if (isset($columns['wpseo-readability'])) {
        unset($columns['wpseo-readability']);
    }

    // Remove Yoast focus keyphrase
    if (isset($columns['wpseo-focuskw'])) {
        unset($columns['wpseo-focuskw']);
    }

    // Remove Yoast metadesc
    if (isset($columns['wpseo-metadesc'])) {
        unset($columns['wpseo-metadesc']);
    }

    return $columns;
}

/**
 * Prevent Yoast from rendering column content
 */
function remove_yoast_seo_column_content($content, $column_name, $term_id) {
    // If it's a Yoast column, return empty
    if (strpos($column_name, 'wpseo-') === 0) {
        return '';
    }

    return $content;
}
