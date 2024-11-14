<?php
/*
Plugin Name: Custom Post Type Creator
Description: A plugin to create custom post types and set taxonomies from the WordPress dashboard.
Version: 1.0
Author: mr abhishek kushwaha
*/

// Add menu and submenu pages
function cpt_creator_menu() {
    add_menu_page(
        'Custom Post Type Creator',  // Page title
        'CPT Creator',               // Menu title
        'manage_options',            // Capability
        'cpt-creator',               // Menu slug
        'cpt_creator_main_page',     // Callback function for the main page
        'dashicons-admin-post'       // Icon
    );

    add_submenu_page(
        'cpt-creator',               // Parent slug
        'Create Custom Post Type',   // Page title
        'Create CPT',                // Submenu title
        'manage_options',            // Capability
        'create-cpt',                // Submenu slug
        'cpt_creator_create_page'    // Callback function for the create page
    );

    add_submenu_page(
        'cpt-creator',
        'Settings',
        'Settings',
        'manage_options',
        'cpt-settings',
        'cpt_creator_settings_page'
    );
}
add_action('admin_menu', 'cpt_creator_menu');

// Main admin page for Custom Post Type Creator
function cpt_creator_main_page() {
    ?>
    <div class="wrap">
        <h1>Welcome to Custom Post Type Creator</h1>
        <p>Manage your custom post types and create new ones here.</p>
    </div>
    <?php
}

// Form page to create a new custom post type
function cpt_creator_create_page() {
    ?>
    <div class="cpt-creator-container">
        <h1>Create a New Custom Post Type</h1>
        <form method="post" action="">
            <label for="cpt_name" class="cpt-creator-label">Custom Post Type Name:</label><br>
            <input type="text" name="cpt_name" id="cpt_name" class="cpt-creator-input" required><br><br>

            <label for="cpt_plural_name" class="cpt-creator-label">Custom Post Type Plural Name:</label><br>
            <input type="text" name="cpt_plural_name" id="cpt_plural_name" class="cpt-creator-input" required><br><br>

            <label for="cpt_slug" class="cpt-creator-label">Post Type Slug:</label><br>
            <input type="text" name="cpt_slug" id="cpt_slug" class="cpt-creator-input" required><br><br>

            <label for="cpt_taxonomies" class="cpt-creator-label">Enter Taxonomies (comma separated):</label><br>
            <input type="text" id="taxonomy_input" class="cpt-creator-input" placeholder="Type taxonomy and press enter" /><br>
            <button type="button" id="add_taxonomy_button" class="cpt-creator-button">Add Taxonomy</button><br><br>

            <ul id="taxonomy_list" class="cpt-creator-taxonomy-list"></ul><br>

            <input type="hidden" name="cpt_taxonomies" id="cpt_taxonomies" />

            <input type="submit" name="create_cpt" class="cpt-creator-submit-button" value="Create Custom Post Type">
        </form>
    </div>

    <script type="text/javascript">
        document.addEventListener("DOMContentLoaded", function() {
            const taxonomyInput = document.getElementById("taxonomy_input");
            const taxonomyList = document.getElementById("taxonomy_list");
            const taxonomyHiddenField = document.getElementById("cpt_taxonomies");

            document.getElementById("add_taxonomy_button").addEventListener("click", function() {
                let taxonomy = taxonomyInput.value.trim();
                if (taxonomy && !taxonomyExists(taxonomy)) {
                    const listItem = document.createElement("li");
                    listItem.textContent = taxonomy;
                    const removeButton = document.createElement("button");
                    removeButton.textContent = "Remove";
                    removeButton.addEventListener("click", function() {
                        listItem.remove();
                        updateHiddenField();
                    });
                    listItem.appendChild(removeButton);
                    taxonomyList.appendChild(listItem);
                    taxonomyInput.value = "";
                    updateHiddenField();
                }
            });

            function updateHiddenField() {
                const taxonomies = [];
                const listItems = taxonomyList.getElementsByTagName("li");
                for (let item of listItems) {
                    taxonomies.push(item.textContent.replace(" ", ""));
                }
                taxonomyHiddenField.value = taxonomies.join(",");
            }

            function taxonomyExists(taxonomy) {
                const listItems = taxonomyList.getElementsByTagName("li");
                for (let item of listItems) {
                    if (item.textContent.replace(" Remove", "") === taxonomy) {
                        return true;
                    }
                }
                return false;
            }
        });
    </script>
    <?php

    if (isset($_POST['create_cpt'])) {
        $cpt_name = sanitize_text_field($_POST['cpt_name']);
        $cpt_slug = sanitize_title($_POST['cpt_slug']);
        $taxonomies_input = isset($_POST['cpt_taxonomies']) ? sanitize_text_field($_POST['cpt_taxonomies']) : '';
        $taxonomies = array_map('trim', explode(',', $taxonomies_input));

        // Get all registered custom post types
        $registered_cpts = get_option('registered_custom_post_types', []);

        // Add the new custom post type to the array
        $registered_cpts[$cpt_slug] = [
            'name' => $cpt_name,
            'slug' => $cpt_slug,
            'taxonomies' => $taxonomies,
        ];

        // Save updated list back to the options table
        update_option('registered_custom_post_types', $registered_cpts);

        echo '<div class="updated"><p>' . esc_html($cpt_name) . ' custom post type created successfully!</p></div>';
    }
}

// Register all custom post types and taxonomies on every page load
function cpt_creator_register_all_custom_post_types() {
    $registered_cpts = get_option('registered_custom_post_types', []);
    foreach ($registered_cpts as $cpt_data) {
        $args = array(
            'labels' => array(
                'name' => $cpt_data['name'],
                'singular_name' => $cpt_data['name'],
                'add_new_item' => "Add New " . $cpt_data['name'],
                'edit_item' => "Edit " . $cpt_data['name'],
                'new_item' => "New " . $cpt_data['name'],
                'view_item' => "View " . $cpt_data['name'],
                'search_items' => "Search " . $cpt_data['name'],
                'not_found' => "No " . $cpt_data['name'] . " found",
            ),
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'supports' => array('title', 'editor', 'thumbnail'),
            'has_archive' => true,
            'rewrite' => array('slug' => $cpt_data['slug']),
            'menu_icon'=> 'dashicons-menu-alt',
        );

        register_post_type($cpt_data['slug'], $args);

        foreach ($cpt_data['taxonomies'] as $taxonomy) {
            if ($taxonomy === 'Remove' ) {
                break;
            }   

            $taxonomy_slug = sanitize_title($taxonomy);
            $taxonomy_args = array(
                'hierarchical' => false,
                'labels' => array(
                    'name' => ucfirst($taxonomy),
                    'singular_name' => ucfirst($taxonomy),
                ),
                'show_ui' => true,
                'show_admin_column' => true,
                'query_var' => true,
                'rewrite' => array('slug' => $taxonomy_slug),
            );
        
            // Register the taxonomy for the custom post type
            register_taxonomy($taxonomy_slug, $cpt_data['slug'], $taxonomy_args);
            register_taxonomy_for_object_type($taxonomy_slug, $cpt_data['slug']);
        }
        
    }
}
add_action('init', 'cpt_creator_register_all_custom_post_types');

// Create the settings page (Optional)
function cpt_creator_settings_page() {
    ?>
    <div class="cpt-creator-wrap">
        <h1 class="cpt-creator-header">Custom Post Type Settings</h1>
        <p class="cpt-creator-description">Here you can manage and remove custom post types.</p>

        <h2 class="cpt-creator-subheader">Existing Custom Post Types</h2>
        <?php
        $registered_cpts = get_option('registered_custom_post_types', []);
        
        if (!empty($registered_cpts)) {
            echo '<ul class="cpt-creator-list">';
            foreach ($registered_cpts as $slug => $cpt_data) {
                echo '<li class="cpt-creator-item">';
                echo '<strong>' . esc_html($cpt_data['name']) . '</strong> (' . esc_html($slug) . ')';
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>No custom post types found.</p>';
        }
        ?>
    </div>
    <?php
}


// Register the shortcode to display custom post types and taxonomies
function cpt_creator_shortcode($atts) {
    // Get all registered custom post types
    $registered_cpts = get_option('registered_custom_post_types', []);

    if (empty($registered_cpts)) {
        return '<p>No custom post types found.</p>';
    }

    // Start building the output
    $output = '<div class="cpt-creator-list-container">';
    foreach ($registered_cpts as $slug => $cpt_data) {
        $output .= '<div class="cpt-creator-item">';
        $output .= '<h3>' . esc_html($cpt_data['name']) . ' (' . esc_html($slug) . ')</h3>';

        // Check if taxonomies are associated with this CPT
        if (!empty($cpt_data['taxonomies'])) {
            $output .= '<ul class="cpt-creator-taxonomies">';
            foreach ($cpt_data['taxonomies'] as $taxonomy) {
                $output .= '<li>' . esc_html(ucfirst($taxonomy)) . '</li>';
            }
            $output .= '</ul>';
        } else {
            $output .= '<p>No taxonomies assigned.</p>';
        }
        $output .= '</div>';
    }
    $output .= '</div>';

    return $output;
}
add_shortcode('cpt_creator_details', 'cpt_creator_shortcode');

?>
