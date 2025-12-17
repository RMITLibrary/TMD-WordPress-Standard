<?php

/**
 * Plugin Name: Hide Taxonomy Metaboxes
 * Description: Hides taxonomy meta boxes in the sidebar for specified post types (taxonomies managed via SCF fields instead).
 */

if (!defined('ABSPATH')) {
  exit;
}

/**
 * Taxonomies to hide in sidebar meta boxes.
 */
function tmd_hidden_taxonomies()
{
  return [
    // Material taxonomies
    'material_category',
    'recommended_uses',
    'limitations',
    'pattern_orientation',
    'opacity',
    'lustre',
    'fray_tendency',
    'care_instructions',

    // Fibre taxonomies
    'classification',
    'fineness_unit',
    'form',
    'cross-section_shape',
    'hydrophilicity',
    'chemical_resistance',
    'heat_sensitivity',
    'hand___handle_descriptors',
    'lustre___sheen',
    'warmth___insulation_tendency',
    'breathability_tendency',
    'wicking_tendency',
    'drying_rate',
    'odour_retention_tendency',
    'static_propensity',
    'prickle___irritation_risk',
    'stretch___recovery',
    'uv_resistance',
    'sustainability_flags_',
    'primary_uses',
    'common_applications',
    'flammability___class',
    'type__origin_source',
  ];
}

/**
 * Post types to apply the hiding to (adjust as needed).
 */
function tmd_hide_taxonomies_for_post_types()
{
  return [
    'material',
    'fibre',
  ];
}

add_action('add_meta_boxes', function ($post_type) {
  if (!in_array($post_type, tmd_hide_taxonomies_for_post_types(), true)) {
    return;
  }

  foreach (tmd_hidden_taxonomies() as $tax) {
    remove_meta_box("{$tax}div", $post_type, 'side');
    remove_meta_box("{$tax}div", $post_type, 'normal');
  }
}, 20);
