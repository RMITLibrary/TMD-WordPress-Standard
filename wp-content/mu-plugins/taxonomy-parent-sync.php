<?php
/**
 * Plugin Name: Taxonomy Parent Sync Helpers
 * Description: Keeps hierarchical taxonomy checklists ordered and auto-selects parents.
 */

if (!function_exists('tmd_target_taxonomies')) {
    function tmd_target_taxonomies()
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
        ];
    }
}

/**
 * Preserve hierarchy order in wp_terms_checklist for the target taxonomies.
 */
add_filter('wp_terms_checklist_args', function ($args) {
    if (!empty($args['taxonomy']) && in_array($args['taxonomy'], tmd_target_taxonomies(), true)) {
        $args['checked_ontop'] = false;
    }
    return $args;
}, 10);

if (!function_exists('tmd_build_term_chain')) {
    /**
     * Ensure term array includes ancestors while preserving original order.
     *
     * @param array  $term_ids Ordered selection from SCF/ACF taxonomy field.
     * @param string $taxonomy Taxonomy slug.
     * @return array
     */
    function tmd_build_term_chain(array $term_ids, $taxonomy)
    {
        $ordered = [];

        foreach ($term_ids as $term_id) {
            $term_id = intval($term_id);
            if ($term_id <= 0) {
                continue;
            }

            $ancestors = array_reverse(get_ancestors($term_id, $taxonomy));
            foreach ($ancestors as $ancestor_id) {
                if (!in_array($ancestor_id, $ordered, true)) {
                    $ordered[] = $ancestor_id;
                }
            }

            if (!in_array($term_id, $ordered, true)) {
                $ordered[] = $term_id;
            }
        }

        return $ordered;
    }
}

/**
 * Hook into ACF/SCF taxonomy field saving so parents are added without reordering UI selections.
 */
add_filter('acf/update_value/type=taxonomy', function ($value, $post_id, $field) {
    if (empty($field['taxonomy']) || !in_array($field['taxonomy'], tmd_target_taxonomies(), true)) {
        return $value;
    }

    $value = (array) $value;
    if (empty($value)) {
        return $value;
    }

    $value = array_map('intval', $value);
    $value = array_filter($value);

    if (empty($value)) {
        return $value;
    }

    return tmd_build_term_chain($value, $field['taxonomy']);
}, 10, 3);

/**
 * Ensure parents are saved when terms are assigned via default WordPress UI.
 */
add_action('save_post', function ($post_id) {
    if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
        return;
    }

    foreach (tmd_target_taxonomies() as $taxonomy) {
        if (!taxonomy_exists($taxonomy)) {
            continue;
        }

        $selected_terms = wp_get_object_terms($post_id, $taxonomy, ['fields' => 'ids']);
        if (empty($selected_terms)) {
            continue;
        }

        $parents_to_add = [];
        foreach ($selected_terms as $term_id) {
            $ancestors = get_ancestors($term_id, $taxonomy);
            if (!empty($ancestors)) {
                $parents_to_add = array_merge($parents_to_add, $ancestors);
            }
        }

        if (!empty($parents_to_add)) {
            $parents_to_add = array_unique($parents_to_add);
            $parents_to_add = array_diff($parents_to_add, $selected_terms);
            if (!empty($parents_to_add)) {
                wp_add_object_terms($post_id, $parents_to_add, $taxonomy);
            }
        }
    }
}, 20, 1);
