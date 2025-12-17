<?php
/**
 * Plugin Name: Bulk Taxonomy Term Insert
 * Description: Adds a simple interface to bulk insert taxonomy terms (one per line)
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add submenu page under Tools
 */
add_action('admin_menu', 'bulk_taxonomy_insert_menu');

function bulk_taxonomy_insert_menu() {
    add_management_page(
        'Bulk Insert Terms',
        'Bulk Insert Terms',
        'manage_categories',
        'bulk-taxonomy-insert',
        'bulk_taxonomy_insert_page'
    );
}

/**
 * Render the bulk insert page
 */
function bulk_taxonomy_insert_page() {
    // Handle form submission
    if (isset($_POST['bulk_insert_submit']) && check_admin_referer('bulk_taxonomy_insert_nonce')) {
        $taxonomy = sanitize_text_field($_POST['taxonomy']);
        $terms_text = $_POST['terms_text']; // Don't sanitize yet - we need to preserve formatting

        // Split by new lines and filter empty lines
        $lines = array_filter(array_map('rtrim', explode("\n", $terms_text)));

        $inserted = array();
        $errors = array();
        $parent_stack = array(); // Track parent hierarchy

        foreach ($lines as $line) {
            if (empty(trim($line))) {
                continue;
            }

            // Count leading dashes to determine nesting level
            preg_match('/^(-*)\s*(.+)$/', $line, $matches);
            $dash_count = strlen($matches[1]);
            $term_name = trim($matches[2]);

            // Sanitize the term name
            $term_name = sanitize_text_field($term_name);

            if (empty($term_name)) {
                continue;
            }

            // Determine parent based on nesting level
            $parent_id = 0;

            if ($dash_count > 0) {
                // This is a child term
                // Parent is at level (dash_count - 1)
                $parent_level = $dash_count - 1;

                if (isset($parent_stack[$parent_level])) {
                    $parent_id = $parent_stack[$parent_level];
                }
            }

            // Insert the term
            $args = array();
            if ($parent_id > 0) {
                $args['parent'] = $parent_id;
            }

            $result = wp_insert_term($term_name, $taxonomy, $args);

            if (is_wp_error($result)) {
                // Check if term already exists
                if (isset($result->error_data['term_exists'])) {
                    $term_id = $result->error_data['term_exists'];
                    $parent_stack[$dash_count] = $term_id;
                    $errors[] = $term_name . ': Already exists (reusing for hierarchy)';
                } else {
                    $errors[] = $term_name . ': ' . $result->get_error_message();
                }
            } else {
                $term_id = $result['term_id'];
                $parent_stack[$dash_count] = $term_id;

                // Build display name with hierarchy
                $display_name = str_repeat('  ', $dash_count) . $term_name;
                $inserted[] = $display_name;
            }

            // Clear deeper levels from parent stack
            foreach ($parent_stack as $level => $stored_id) {
                if ($level > $dash_count) {
                    unset($parent_stack[$level]);
                }
            }
        }

        // Display results
        if (!empty($inserted)) {
            echo '<div class="notice notice-success"><p><strong>Successfully inserted ' . count($inserted) . ' terms:</strong><br><pre>' . esc_html(implode("\n", $inserted)) . '</pre></p></div>';
        }

        if (!empty($errors)) {
            echo '<div class="notice notice-warning"><p><strong>Notices:</strong><br>' . esc_html(implode('<br>', $errors)) . '</p></div>';
        }
    }

    // Get all public taxonomies
    $taxonomies = get_taxonomies(array('public' => true), 'objects');

    ?>
    <div class="wrap">
        <h1>Bulk Insert Taxonomy Terms</h1>

        <form method="post" action="">
            <?php wp_nonce_field('bulk_taxonomy_insert_nonce'); ?>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="taxonomy">Select Taxonomy</label>
                    </th>
                    <td>
                        <select name="taxonomy" id="taxonomy" required onchange="loadParentTerms(this.value)">
                            <option value="">-- Select Taxonomy --</option>
                            <?php foreach ($taxonomies as $tax_slug => $tax_obj): ?>
                                <option value="<?php echo esc_attr($tax_slug); ?>">
                                    <?php echo esc_html($tax_obj->label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>

                <tr id="parent_term_row" style="display: none;">
                    <th scope="row">
                        <label for="parent_term">Parent Term (optional)</label>
                    </th>
                    <td>
                        <select name="parent_term" id="parent_term">
                            <option value="0">-- None (Top Level) --</option>
                        </select>
                        <p class="description">Only for hierarchical taxonomies</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="terms_text">Terms to Insert</label>
                    </th>
                    <td>
                        <textarea
                            name="terms_text"
                            id="terms_text"
                            rows="15"
                            cols="50"
                            class="large-text code"
                            placeholder="Natural Fibers&#10;-Cotton&#10;-Linen&#10;-Silk&#10;Synthetic Fibers&#10;-Polyester&#10;-Nylon&#10;--Nylon 6&#10;--Nylon 66"
                            required
                        ></textarea>
                        <p class="description">
                            <strong>Nested Terms:</strong> Use dashes (-) at the start of a line to create child terms.<br>
                            - One dash (-) = child of previous parent<br>
                            - Two dashes (--) = grandchild (child of previous child)<br>
                            - Three dashes (---) = great-grandchild, etc.<br>
                            <strong>Example:</strong><br>
                            <code>
                            Natural Fibers<br>
                            -Cotton<br>
                            -Linen<br>
                            Synthetic Fibers<br>
                            -Polyester<br>
                            -Nylon<br>
                            --Nylon 6<br>
                            --Nylon 66
                            </code>
                        </p>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <input type="submit" name="bulk_insert_submit" class="button button-primary" value="Insert Terms">
            </p>
        </form>
    </div>

    <script>
    const bulkTaxonomyNonce = '<?php echo esc_js( wp_create_nonce( 'bulk_taxonomy_nonce' ) ); ?>';

    function loadParentTerms(taxonomy) {
        var parentRow = document.getElementById('parent_term_row');
        var parentSelect = document.getElementById('parent_term');

        if (!taxonomy) {
            parentRow.style.display = 'none';
            return;
        }

        // Fetch taxonomy info via AJAX
        jQuery.post(ajaxurl, {
            action: 'get_taxonomy_info',
            taxonomy: taxonomy,
            _ajax_nonce: bulkTaxonomyNonce
        }, function(response) {
            if (response.success && response.data.hierarchical) {
                parentRow.style.display = 'table-row';

                // Load existing terms
                jQuery.post(ajaxurl, {
                    action: 'get_taxonomy_terms',
                    taxonomy: taxonomy,
                    _ajax_nonce: bulkTaxonomyNonce
                }, function(termsResponse) {
                    parentSelect.innerHTML = '<option value="0">-- None (Top Level) --</option>';

                    if (termsResponse.success && termsResponse.data.length > 0) {
                        termsResponse.data.forEach(function(term) {
                            var option = document.createElement('option');
                            option.value = term.term_id;
                            option.textContent = term.name;
                            parentSelect.appendChild(option);
                        });
                    }
                });
            } else {
                parentRow.style.display = 'none';
            }
        });
    }
    </script>
    <?php
}

/**
 * AJAX handler to get taxonomy info
 */
add_action('wp_ajax_get_taxonomy_info', 'ajax_get_taxonomy_info');

function ajax_get_taxonomy_info() {
    $taxonomy = sanitize_text_field($_POST['taxonomy']);
    $tax_obj = get_taxonomy($taxonomy);

    if ($tax_obj) {
        wp_send_json_success(array(
            'hierarchical' => $tax_obj->hierarchical
        ));
    } else {
        wp_send_json_error();
    }
}

/**
 * AJAX handler to get taxonomy terms
 */
add_action('wp_ajax_get_taxonomy_terms', 'ajax_get_taxonomy_terms');

function ajax_get_taxonomy_terms() {
    $taxonomy = sanitize_text_field($_POST['taxonomy']);

    $terms = get_terms(array(
        'taxonomy' => $taxonomy,
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC'
    ));

    if (!is_wp_error($terms)) {
        wp_send_json_success($terms);
    } else {
        wp_send_json_error();
    }
}
