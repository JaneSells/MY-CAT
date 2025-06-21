<?php
/*
Plugin Name: Cat Boarding SEO Plugin
Description: Programmatic SEO for cat boarding pages targeting U.S. states and cities.
Version: 2.0.3
Author: Grok
Text Domain: cbseo
*/

// Register Custom Post Types and Taxonomies
function cbseo_register_post_types() {
    $labels = array(
        'name' => 'Cat Boarding Locations',
        'singular_name' => 'Cat Boarding Location',
        'menu_name' => 'Boarding Locations',
        'add_new' => 'Add New Location',
        'add_new_item' => 'Add New Boarding Location',
        'edit_item' => 'Edit Boarding Location',
        'new_item' => 'New Boarding Location',
        'view_item' => 'View Boarding Location',
        'all_items' => 'All Boarding Locations',
        'search_items' => 'Search Boarding Locations',
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'rewrite' => array('slug' => 'cat-boarding'),
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
        'taxonomies' => array('state', 'city'),
        'menu_icon' => 'dashicons-location',
        'show_in_rest' => true,
    );
    register_post_type('cat_boarding', $args);

    $labels = array(
        'name' => 'Providers',
        'singular_name' => 'Provider',
        'menu_name' => 'Providers',
        'add_new' => 'Add New Provider',
        'add_new_item' => 'Add New Provider',
        'edit_item' => 'Edit Provider',
        'new_item' => 'New Provider',
        'view_item' => 'View Provider',
        'all_items' => 'All Providers',
        'search_items' => 'Search Providers',
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'rewrite' => array('slug' => 'providers'),
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
        'taxonomies' => array('state', 'city'),
        'menu_icon' => 'dashicons-businessman',
        'show_in_rest' => true,
    );
    register_post_type('provider', $args);
}
add_action('init', 'cbseo_register_post_types');

function cbseo_register_taxonomies() {
    $labels = array(
        'name' => 'States',
        'singular_name' => 'State',
        'search_items' => 'Search States',
        'all_items' => 'All States',
        'edit_item' => 'Edit State',
        'update_item' => 'Update State',
        'add_new_item' => 'Add New State',
        'new_item_name' => 'New State Name',
    );

    register_taxonomy('state', array('cat_boarding', 'provider'), array(
        'labels' => $labels,
        'hierarchical' => true,
        'show_ui' => true,
        'show_in_rest' => true,
        'rewrite' => array('slug' => 'state'),
    ));

    $labels = array(
        'name' => 'Cities',
        'singular_name' => 'City',
        'search_items' => 'Search Cities',
        'all_items' => 'All Cities',
        'edit_item' => 'Edit City',
        'update_item' => 'Update City',
        'add_new_item' => 'Add New City',
        'new_item_name' => 'New City Name',
    );

    register_taxonomy('city', array('cat_boarding', 'provider'), array(
        'labels' => $labels,
        'hierarchical' => true,
        'show_ui' => true,
        'show_in_rest' => true,
        'rewrite' => array('slug' => 'city'),
    ));
}
add_action('init', 'cbseo_register_taxonomies');

// Register Custom Fields for Providers
function cbseo_register_provider_fields() {
    add_action('add_meta_boxes', function() {
        add_meta_box(
            'cbseo_provider_details',
            'Provider Details',
            'cbseo_provider_details_callback',
            'provider',
            'normal',
            'high'
        );
    });

    add_action('save_post_provider', function($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $fields = [
            'tagline', 'phone_number', 'address', 'website', 'services_offered',
            'pet_types', 'membership_level', 'reply_rating', 'id_verified', 'rating',
            'rate', 'relationship_status', 'gallery_images', 'preferred_locations', 'trust_note'
        ];
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                if ($field === 'rating') {
                    $reviews_input = wp_kses_post($_POST[$field]);
                    $reviews = [];
                    $lines = array_filter(explode("\n", $reviews_input));
                    foreach ($lines as $line) {
                        $parts = array_map('trim', explode('|', $line));
                        if (count($parts) >= 5) {
                            $reviews[] = [
                                'name' => $parts[0],
                                'location' => $parts[1],
                                'duration' => $parts[2],
                                'comment' => $parts[3],
                                'ratings' => $parts[4]
                            ];
                        }
                    }
                    update_post_meta($post_id, "_cbseo_$field", wp_json_encode($reviews));
                } elseif ($field === 'pet_types' && is_array($_POST[$field])) {
                    $pet_types = array_map('sanitize_text_field', array_map('trim', $_POST[$field]));
                    $standardized_pet_types = array_map(function($type) {
                        $type = strtolower(trim($type));
                        $map = [
                            'dog' => 'Dogs',
                            'cat' => 'Cats',
                            'fish' => 'Fish',
                            'bird' => 'Birds',
                            'rabbit' => 'Rabbits/Guinea Pigs',
                            'chicken' => 'Chickens/Ducks/Geese',
                            'duck' => 'Chickens/Ducks/Geese',
                            'goose' => 'Chickens/Ducks/Geese'
                        ];
                        return $map[$type] ?? ucfirst($type);
                    }, $pet_types);
                    update_post_meta($post_id, "_cbseo_$field", implode(',', array_unique($standardized_pet_types)));
                } else {
                    update_post_meta($post_id, "_cbseo_$field", wp_kses_post($_POST[$field]));
                }
            }
        }
    });
}

function cbseo_provider_details_callback($post) {
    wp_nonce_field('cbseo_provider_details', 'cbseo_provider_nonce');
    $fields = [
        'tagline' => ['label' => 'Tagline', 'type' => 'text'],
        'phone_number' => ['label' => 'Phone Number', 'type' => 'text'],
        'address' => ['label' => 'Address', 'type' => 'textarea'],
        'website' => ['label' => 'Website', 'type' => 'url'],
        'services_offered' => ['label' => 'Services Offered', 'type' => 'textarea'],
        'pet_types' => ['label' => 'Pet Types Willing to Care For', 'type' => 'checkbox'],
        'membership_level' => ['label' => 'Membership Level (e.g., Silver, 12 months)', 'type' => 'text'],
        'reply_rating' => ['label' => 'Reply Rating (1-5)', 'type' => 'number', 'min' => 1, 'max' => 5],
        'id_verified' => ['label' => 'ID Verified (yes/no)', 'type' => 'text'],
        'rating' => ['label' => 'Reviews (one per line: Name|Location|Duration|Comment|Ratings e.g., House,Pet,Communication)', 'type' => 'textarea'],
        'rate' => ['label' => 'Rate (e.g., FREE or FROM $50/DAY)', 'type' => 'text'],
        'relationship_status' => ['label' => 'Relationship Status (e.g., Single, Couple, Family)', 'type' => 'text'],
        'gallery_images' => ['label' => 'Gallery Images (comma-separated URLs)', 'type' => 'textarea'],
        'preferred_locations' => ['label' => 'Preferred Locations (e.g., Anywhere in the United States)', 'type' => 'text'],
        'trust_note' => ['label' => 'Trust Note (max 50 words, e.g., a personal note about reliability)', 'type' => 'textarea']
    ];

    $settings = get_option('cbseo_plugin_settings', array('pet_types' => array('Cat')));
    $pet_types = $settings['pet_types'];
    $selected_pet_types = get_post_meta($post->ID, '_cbseo_pet_types', true);
    $selected_pet_types = $selected_pet_types ? explode(',', $selected_pet_types) : array();

    foreach ($fields as $key => $field) {
        $value = get_post_meta($post->ID, "_cbseo_$key", true);
        ?>
        <p>
            <label for="<?php echo $key; ?>"><?php echo esc_html($field['label']); ?>:</label><br>
            <?php if ($field['type'] === 'textarea') : ?>
                <textarea id="<?php echo $key; ?>" name="<?php echo $key; ?>" style="width: 100%; height: 100px;"><?php echo esc_textarea($value); ?></textarea>
                <?php if ($key === 'rating') : ?>
                    <p style="font-size: 0.9rem; color: #666;">Enter one review per line in this format: Name|Location|Duration|Comment|Ratings (e.g., John Doe|New York|1 week|Great service|5,5,5)</p>
                <?php endif; ?>
            <?php elseif ($field['type'] === 'number') : ?>
                <input type="number" id="<?php echo $key; ?>" name="<?php echo $key; ?>" value="<?php echo esc_attr($value); ?>" style="width: 100px;" min="<?php echo $field['min'] ?? 0; ?>" max="<?php echo $field['max'] ?? ''; ?>">
            <?php elseif ($field['type'] === 'checkbox') : ?>
                <div class="pet-type-buttons">
                    <?php foreach ($pet_types as $pet_type) : ?>
                        <label class="pet-type-button">
                            <input type="checkbox" name="pet_types[]" value="<?php echo esc_attr($pet_type); ?>" <?php checked(in_array($pet_type, $selected_pet_types)); ?>>
                            <span><?php echo esc_html($pet_type); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <style>
                    .pet-type-buttons {
                        display: flex;
                        flex-wrap: wrap;
                        gap: 10px;
                    }
                    .pet-type-button {
                        display: inline-flex;
                        align-items: center;
                        padding: 8px 16px;
                        background-color: #f0f0f0;
                        border: 1px solid #ccc;
                        border-radius: 4px;
                        cursor: pointer;
                        transition: background-color 0.3s, color 0.3s;
                    }
                    .pet-type-button input {
                        display: none;
                    }
                    .pet-type-button input:checked + span {
                        background-color: #4a90e2;
                        color: white;
                    }
                    .pet-type-button span {
                        margin-left: 5px;
                    }
                </style>
                <p style="font-size: 0.9rem; color: #666;">Check the buttons for the pet types you are willing to care for.</p>
            <?php else : ?>
                <input type="<?php echo $field['type']; ?>" id="<?php echo $key; ?>" name="<?php echo $key; ?>" value="<?php echo esc_attr($value); ?>" style="width: 100%;">
            <?php endif; ?>
        </p>
        <?php
    }
}
add_action('init', 'cbseo_register_provider_fields');

// Add Featured Provider Meta Box with Payment Integration
function cbseo_add_featured_provider_meta_box() {
    add_meta_box(
        'cbseo_featured_provider',
        'Featured Provider',
        'cbseo_featured_provider_callback',
        'provider',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'cbseo_add_featured_provider_meta_box');

function cbseo_featured_provider_callback($post) {
    $featured = get_post_meta($post->ID, '_cbseo_featured_provider', true);
    $payment_status = get_post_meta($post->ID, '_cbseo_payment_status', true);
    $settings = get_option('cbseo_plugin_settings', ['featured_price' => 10.00]);
    $feature_price = floatval($settings['featured_price']);
    ?>
    <label for="cbseo_featured_provider">Enable Featured Provider Badge:</label>
    <select name="cbseo_featured_provider" id="cbseo_featured_provider">
        <option value="no" <?php selected($featured, 'no'); ?>>No</option>
        <option value="yes" <?php selected($featured, 'yes'); ?>>Yes</option>
    </select>
    <p><em>Requires payment of $<?php echo number_format($feature_price, 2); ?> to activate.</em></p>
    <?php if ($payment_status !== 'paid') : ?>
        <p><a href="<?php echo esc_url(add_query_arg('action', 'cbseo_payment', admin_url('post.php?post=' . $post->ID . '&action=edit'))); ?>" class="cbseo-pay-btn">Pay Now</a></p>
    <?php else : ?>
        <p><strong>Payment Confirmed</strong></p>
    <?php endif; ?>
    <?php
}

function cbseo_save_featured_provider_meta($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    if (array_key_exists('cbseo_featured_provider', $_POST)) {
        $featured = sanitize_text_field($_POST['cbseo_featured_provider']);
        update_post_meta($post_id, '_cbseo_featured_provider', $featured);

        if ($featured === 'yes' && get_post_meta($post_id, '_cbseo_payment_status', true) !== 'paid') {
            wp_redirect(add_query_arg('action', 'cbseo_payment', admin_url('post.php?post=' . $post_id . '&action=edit')));
            exit;
        }
    }
}
add_action('save_post_provider', 'cbseo_save_featured_provider_meta', 10, 1);

// Handle Payment Processing
function cbseo_handle_payment() {
    if (isset($_GET['action']) && $_GET['action'] === 'cbseo_payment') {
        $post_id = isset($_GET['post']) ? intval($_GET['post']) : 0;
        if ($post_id && current_user_can('edit_post', $post_id)) {
            if (!class_exists('WooCommerce')) {
                echo '<p>Please install and activate WooCommerce to enable payment processing.</p>';
                return;
            }

            $settings = get_option('cbseo_plugin_settings', ['featured_price' => 10.00]);
            $product_id = cbseo_create_payment_product($post_id, floatval($settings['featured_price']));
            if ($product_id) {
                $checkout_url = wc_get_checkout_url() . '?add-to-cart=' . $product_id;
                wp_redirect($checkout_url);
                exit;
            }
        }
    }
}
add_action('admin_init', 'cbseo_handle_payment');

function cbseo_create_payment_product($post_id, $price) {
    $product = wc_get_product($post_id);
    if (!$product) {
        $product = new WC_Product_Simple();
        $product->set_name('Featured Provider Badge for ' . get_the_title($post_id));
        $product->set_regular_price($price);
        $product->set_description('Activate the Featured Provider badge for your listing.');
        $product->set_status('publish');
        $product->save();
        update_post_meta($post_id, '_cbseo_payment_product_id', $product->get_id());
    }
    return $product->get_id();
}

function cbseo_process_payment_completion($order_id) {
    $order = wc_get_order($order_id);
    foreach ($order->get_items() as $item) {
        $product_id = $item->get_product_id();
        $provider_posts = get_posts(array(
            'post_type' => 'provider',
            'meta_key' => '_cbseo_payment_product_id',
            'meta_value' => $product_id,
        ));
        if ($provider_posts) {
            foreach ($provider_posts as $post) {
                update_post_meta($post->ID, '_cbseo_payment_status', 'paid');
                update_post_meta($post->ID, '_cbseo_featured_provider', 'yes');
            }
        }
    }
}
add_action('woocommerce_order_status_completed', 'cbseo_process_payment_completion');

// Add Admin Menu and Settings Page
function cbseo_admin_menu() {
    add_menu_page(
        'Cat Boarding SEO',
        'Cat Boarding SEO',
        'manage_options',
        'cbseo-dashboard',
        'cbseo_admin_page',
        'dashicons-location'
    );
    add_submenu_page(
        'cbseo-dashboard',
        'Cat Boarding SEO Generator',
        'Generator',
        'manage_options',
        'cbseo-generate',
        'cbseo_admin_page'
    );
    add_submenu_page(
        'cbseo-dashboard',
        'Cat Boarding SEO Settings',
        'Settings',
        'manage_options',
        'cbseo-settings',
        'cbseo_settings_page_callback'
    );
    add_submenu_page(
        'cbseo-dashboard',
        'Cat Boarding SEO Content Templates',
        'Content Templates',
        'manage_options',
        'cbseo-content-templates',
        'cbseo_content_templates_callback'
    );
}
add_action('admin_menu', 'cbseo_admin_menu', 10);

function cbseo_admin_page() {
    $progress = get_option('cbseo_generation_progress', array('current_index' => 0, 'completed' => false));
    $settings = get_option('cbseo_plugin_settings', array('locations' => array('states' => array())));
    $total_states = count(array_keys($settings['locations']['states']));
    $current_index = $progress['current_index'];
    $percent_complete = $total_states > 0 ? min(100, ($current_index / $total_states) * 100) : 0;

    if (isset($_POST['cbseo_generate']) && check_admin_referer('cbseo_generate_action')) {
        $batch_size = isset($settings['batch_size']) ? max(1, intval($settings['batch_size'])) : 3;
        cbseo_generate_location_pages($batch_size);
        $progress = get_option('cbseo_generation_progress');
    }
    ?>
    <div class="wrap">
        <h1>Cat Boarding SEO Generator</h1>
        <form method="post">
            <?php wp_nonce_field('cbseo_generate_action'); ?>
            <p>Click the button to generate cat boarding pages for U.S. states and major cities in batches.</p>
            <div class="cbseo-progress">
                <label>Progress: <?php echo number_format($percent_complete, 1); ?>%</label>
                <div class="cbseo-progress-bar">
                    <div class="cbseo-progress-fill" style="width: <?php echo $percent_complete; ?>%;"></div>
                </div>
                <p>Processed <?php echo $current_index; ?> of <?php echo $total_states; ?> states.</p>
            </div>
            <input type="submit" name="cbseo_generate" class="button button-primary" value="Generate Next Batch" <?php echo $progress['completed'] ? 'disabled' : ''; ?>>
        </form>
    </div>
    <?php
}



function cbseo_settings_page_callback() {
    if (isset($_POST['cbseo_save_settings']) && check_admin_referer('cbseo_save_settings', 'cbseo_settings_nonce')) {
        // Save reCAPTCHA keys
        update_option('cbseo_recaptcha_site_key', sanitize_text_field($_POST['cbseo_recaptcha_site_key'] ?? ''));
        update_option('cbseo_recaptcha_secret_key', sanitize_text_field($_POST['cbseo_recaptcha_secret_key'] ?? ''));

        // Existing settings handling
        $locations = array(
            'states' => array(),
            'data' => array(),
        );
        $states = isset($_POST['cbseo_states']) ? $_POST['cbseo_states'] : array();
        $cities = isset($_POST['cbseo_cities']) ? $_POST['cbseo_cities'] : array();
        $capitals = isset($_POST['cbseo_capitals']) ? $_POST['cbseo_capitals'] : array();
        $populations = isset($_POST['cbseo_populations']) ? $_POST['cbseo_populations'] : array();

        $state_count = count($states);
        for ($i = 0; $i < $state_count; $i++) {
            $state = sanitize_text_field($states[$i]);
            if ($state) {
                $locations['states'][$state] = array_map('sanitize_text_field', explode(',', trim($cities[$i])));
                $locations['data'][$state] = array(
                    'capital' => sanitize_text_field($capitals[$i]),
                    'population' => sanitize_text_field($populations[$i]),
                );
            }
        }

        $pet_types = isset($_POST['cbseo_pet_types']) ? array_map('sanitize_text_field', array_map('trim', $_POST['cbseo_pet_types'])) : array('Cat');
        $standardized_pet_types = array_map(function($type) {
            $type = strtolower(trim($type));
            $map = [
                'dog' => 'Dogs',
                'cat' => 'Cats',
                'fish' => 'Fish',
                'bird' => 'Birds',
                'rabbit' => 'Rabbits/Guinea Pigs',
                'chicken' => 'Chickens/Ducks/Geese',
                'duck' => 'Chickens/Ducks/Geese',
                'goose' => 'Chickens/Ducks/Geese'
            ];
            return $map[$type] ?? ucfirst($type);
        }, $pet_types);
        $settings = array(
            'featured_price' => floatval($_POST['cbseo_featured_price']),
            'locations' => $locations,
            'pet_types' => array_unique($standardized_pet_types),
            'batch_size' => max(1, intval($_POST['cbseo_batch_size'])),
        );
        update_option('cbseo_plugin_settings', $settings);
        echo '<div class="updated"><p>Settings saved successfully!</p></div>';
    }

    if (isset($_FILES['cbseo_locations_csv']) && check_admin_referer('cbseo_save_settings', 'cbseo_settings_nonce')) {
        if ($_FILES['cbseo_locations_csv']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['cbseo_locations_csv']['tmp_name'];
            $locations = array('states' => array(), 'data' => array());
            if (($handle = fopen($file, 'r')) !== false) {
                $header = fgetcsv($handle);
                if ($header && in_array('state', $header) && in_array('cities', $header)) {
                    $keys = array_flip($header);
                    while (($data = fgetcsv($handle)) !== false) {
                        $state = sanitize_text_field($data[$keys['state']]);
                        if ($state) {
                            $locations['states'][$state] = array_map('sanitize_text_field', explode(',', trim($data[$keys['cities']])));
                            $locations['data'][$state] = array();
                            foreach ($header as $col) {
                                if ($col !== 'state' && $col !== 'cities') {
                                    $locations['data'][$state][$col] = sanitize_text_field($data[$keys[$col]] ?? '');
                                }
                            }
                        }
                    }
                    fclose($handle);
                    $settings = get_option('cbseo_plugin_settings', array('featured_price' => 10.00, 'pet_types' => array('Cat')));
                    $settings['locations'] = $locations;
                    update_option('cbseo_plugin_settings', $settings);
                    echo '<div class="updated"><p>CSV file uploaded and locations updated successfully!</p></div>';
                } else {
                    echo '<div class="error"><p>Invalid CSV format. Please use at least columns: state, cities.</p></div>';
                }
            }
        } else {
            echo '<div class="error"><p>Error uploading CSV file.</p></div>';
        }
    }

    $settings = get_option('cbseo_plugin_settings', array(
        'featured_price' => 10.00,
        'batch_size' => 3,
        'locations' => array(
            'states' => array(
                'Alabama' => array('Birmingham', 'Montgomery'),
                'California' => array('Los Angeles', 'San Francisco', 'San Diego'),
                'Florida' => array('Miami', 'Orlando', 'Tampa'),
            ),
            'data' => array(
                'Alabama' => array('capital' => 'Montgomery', 'population' => '4.9M'),
                'California' => array('capital' => 'Sacramento', 'population' => '39.5M'),
                'Florida' => array('capital' => 'Tallahassee', 'population' => '21.5M'),
            ),
        ),
        'pet_types' => array('Cat')
    ));
    $locations = $settings['locations'];
    $pet_types = $settings['pet_types'];
    $batch_size = isset($settings['batch_size']) ? intval($settings['batch_size']) : 3;
    ?>
    <div class="wrap">
        <h1>Cat Boarding SEO Settings</h1>
        <form method="post" action="" enctype="multipart/form-data">
            <?php wp_nonce_field('cbseo_save_settings', 'cbseo_settings_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="cbseo_recaptcha_site_key">Google reCAPTCHA v3 Site Key</label></th>
                    <td>
                        <input type="text" name="cbseo_recaptcha_site_key" id="cbseo_recaptcha_site_key" value="<?php echo esc_attr(get_option('cbseo_recaptcha_site_key', '')); ?>" class="regular-text">
                        <p class="description">Enter your Google reCAPTCHA v3 site key for review form spam protection. Get it from <a href="https://www.google.com/recaptcha/admin" target="_blank">Google reCAPTCHA</a>.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="cbseo_recaptcha_secret_key">Google reCAPTCHA v3 Secret Key</label></th>
                    <td>
                        <input type="text" name="cbseo_recaptcha_secret_key" id="cbseo_recaptcha_secret_key" value="<?php echo esc_attr(get_option('cbseo_recaptcha_secret_key', '')); ?>" class="regular-text">
                        <p class="description">This should be kept confidential.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="cbseo_featured_price">Featured Provider Badge Price ($)</label></th>
                    <td><input type="number" step="0.01" min="0" name="cbseo_featured_price" id="cbseo_featured_price" value="<?php echo esc_attr($settings['featured_price']); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="cbseo_batch_size">Batch Size for Page Generation</label></th>
                    <td>
                        <input type="number" step="1" min="1" max="50" name="cbseo_batch_size" id="cbseo_batch_size" value="<?php echo esc_attr($batch_size); ?>" class="regular-text">
                        <p class="description">Set the number of states to process per batch when generating location pages. Recommended: 1-10 to avoid server timeouts.</p>
                    </td>
                </tr>
                <tr>
                    <th><label>Upload Locations CSV</label></th>
                    <td>
                        <input type="file" name="cbseo_locations_csv" accept=".csv">
                        <p>Upload a CSV file with at least columns: state, cities. Optional columns (e.g., capital, population, climate) will be used as dynamic placeholders.</p>
                        <p><a href="<?php echo admin_url('admin-post.php?action=cbseo_download_sample_csv'); ?>" download="sample-locations.csv">Download Sample CSV</a></p>
                    </td>
                </tr>
                <tr>
                    <th><label>Pet Types</label></th>
                    <td>
                        <?php
                        $j = 0;
                        foreach ($pet_types as $pet_type) {
                            ?>
                            <div style="margin-bottom: 10px;">
                                <input type="text" name="cbseo_pet_types[]" value="<?php echo esc_attr($pet_type); ?>" style="width: 200px; margin-right: 10px;">
                            </div>
                            <?php
                            $j++;
                        }
                        ?>
                        <p><a href="#" id="cbseo-add-pet-type" class="button">Add New Pet Type</a></p>
                        <script>
                            document.getElementById('cbseo-add-pet-type').addEventListener('click', function(e) {
                                e.preventDefault();
                                const container = document.querySelector('.form-table td:last-child');
                                const div = document.createElement('div');
                                div.style.marginBottom = '10px';
                                div.innerHTML = `<input type="text" name="cbseo_pet_types[]" style="width: 200px; margin-right: 10px;">`;
                                container.appendChild(div);
                            });
                        </script>
                    </td>
                </tr>
            </table>
            <?php submit_button('Save Changes', 'primary', 'cbseo_save_settings'); ?>
        </form>
    </div>
    <?php
}





// Content Templates Callback
function cbseo_content_templates_callback() {
    if (isset($_POST['cbseo_save_templates']) && check_admin_referer('cbseo_save_templates', 'cbseo_templates_nonce')) {
        // Custom sanitization function for templates
        function cbseo_sanitize_template($input) {
            $input = wp_unslash($input);
            $allowed_html = array(
                'h2' => array(),
                'h3' => array(),
                'p' => array(),
                'ul' => array(),
                'li' => array(),
                'a' => array('href' => true, 'target' => true),
                'strong' => array(),
                'em' => array(),
                'br' => array(),
            );
            $sanitized = wp_kses($input, $allowed_html);
            $sanitized = preg_replace_callback(
                '/(\[cbseo_providers[^\]]*\])|(\{[a-zA-Z0-9_]+\})/',
                function ($matches) {
                    return $matches[0];
                },
                $sanitized
            );
            return $sanitized;
        }

        $templates = array(
            'state_template' => cbseo_sanitize_template($_POST['cbseo_state_template']),
            'city_template' => cbseo_sanitize_template($_POST['cbseo_city_template']),
        );
        update_option('cbseo_content_templates', $templates);
        echo '<div class="updated"><p>Templates saved successfully!</p></div>';
    }

    $templates = get_option('cbseo_content_templates', array(
        'state_template' => '[cbseo_providers state="{state}"]<h2>Cat Boarding in {state}</h2><p>Explore premier cat boarding options across {state}, home to {population} residents and the capital city of {capital}. Whether you\'re near {capital} or {major_cities}, find trusted care for your feline friend, even amidst {climate} weather. Take advantage of {special_offer} to enhance your pet\'s stay. Our curated listings ensure a safe stay during {peak_seasons}.</p><h2>Frequently Asked Questions</h2><h3>How does {climate} weather affect cat boarding in {state}?</h3><p>{climate} conditions require facilities to offer climate control and shaded areas, especially in cities like {major_cities}.</p><h3>Are there special regulations for cat boarding in {state}?</h3><p>Yes, {state} enforces {regulations}, including regular inspections. Verify provider compliance.</p><h3>What are the busiest cat boarding seasons in {state}?</h3><p>Peak times are {peak_seasons}, particularly in {major_cities}. Book 4-6 weeks ahead.</p><h3>Can I find luxury cat boarding in {state}?</h3><p>{luxury_available}, with rates from {avg_cost} in urban areas like {major_cities}.</p><h3>What should I do if my cat needs medication in {state}?</h3><p>Provide a vet note and medications. {vet_access} is common, especially in cities.</p><h3>How can I tour a cat boarding facility in {state}?</h3><p>{tour_option} are available, especially in {major_cities}. Schedule in advance.</p><h3>What special offers are available for cat boarding in {state}?</h3><p>Check for {special_offer} at participating facilities to save on your booking.</p>',
        'city_template' => '[cbseo_providers state="{state}" city="{city}"]<h2>Cat Boarding in {city}, {state}</h2><p>Discover top-quality cat boarding services in {city}, {state}, a vibrant city with {population} residents. Enjoy peace of mind near {local_attraction}, even with {weather_pattern} weather. Take advantage of {special_offer} to enhance your pet\'s stay. Our curated listings provide safe care during {peak_seasons}, perfect for exploring {state}.</p><h2>Frequently Asked Questions</h2><h3>How does {weather_pattern} weather impact cat boarding in {city}?</h3><p>{weather_pattern} conditions necessitate climate-controlled environments and outdoor shelters, especially in {city} neighborhoods.</p><h3>Are there local regulations for cat boarding in {city}?</h3><p>Yes, {city} follows {state}\'s {regulations}, with additional city-specific permits. Check provider credentials.</p><h3>What are the peak cat boarding seasons in {city}?</h3><p>The busiest times are {peak_seasons}, especially around {local_attraction} events. Reserve 4-6 weeks in advance.</p><h3>Is luxury cat boarding available in {city}?</h3><p>{luxury_available}, with rates starting at {avg_cost} near {local_attraction}.</p><h3>What if my cat requires medication in {city}?</h3><p>Bring a vet note and medications. {vet_access} is widely available, particularly in {city} clinics.</p><h3>Can I visit a cat boarding facility in {city}?</h3><p>{tour_option} are offered, often near {local_attraction}. Contact providers to schedule.</p><h3>What special offers are available for cat boarding in {city}?</h3><p>Look out for {special_offer} at local facilities to enjoy added benefits or discounts.</p>'
    ));
    ?>
    <div class="wrap">
        <h1>Cat Boarding SEO Content Templates</h1>
        <form method="post" action="">
            <?php wp_nonce_field('cbseo_save_templates', 'cbseo_templates_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="cbseo_state_template">State Page Template</label></th>
                    <td>
                        <textarea name="cbseo_state_template" id="cbseo_state_template" rows="10" cols="50" style="width: 100%;"><?php echo esc_textarea($templates['state_template']); ?></textarea>
                        <p>Use placeholders like {state}, {city}, {location}, and any additional placeholders from your CSV (e.g., {capital}, {population}, {climate}, {special_offer}, etc.). Include [cbseo_providers] shortcode with state="{state}".</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="cbseo_city_template">City Page Template</label></th>
                    <td>
                        <textarea name="cbseo_city_template" id="cbseo_city_template" rows="10" cols="50" style="width: 100%;"><?php echo esc_textarea($templates['city_template']); ?></textarea>
                        <p>Use placeholders like {state}, {city}, {location}, and any additional placeholders from your CSV (e.g., {population}, {local_attraction}, {weather_pattern}, {special_offer}, etc.). Include [cbseo_providers] shortcode with state="{state}" city="{city}".</p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Save Templates', 'primary', 'cbseo_save_templates'); ?>
        </form>
    </div>
    <?php
}




// Register custom query variable for pagination
add_filter('query_vars', 'cbseo_register_query_vars');
function cbseo_register_query_vars($vars) {
    $vars[] = 'cbseo_page'; // Changed from cbseo_paged
    return $vars;
}




// Updated Providers Shortcode
add_shortcode('cbseo_providers', 'cbseo_providers_shortcode');
function cbseo_providers_shortcode($atts) {
    global $post, $wp_query;

    // Store original post and query state
    $original_post = $post;
    $original_query = $wp_query;
    $original_post_id = is_singular('cat_boarding') ? $original_post->ID : null;
    $base_page_url = $original_post_id ? rtrim(get_permalink($original_post_id), '/') : rtrim(home_url(add_query_arg(null, null)), '/');

    $atts = shortcode_atts(array(
        'state' => '',
        'city' => '',
        'posts_per_page' => 10,
        'paged' => get_query_var('cbseo_page') ? get_query_var('cbseo_page') : (isset($_GET['cbseo_page']) ? intval($_GET['cbseo_page']) : 1),
    ), $atts);

    // Debug: Log shortcode inputs and base URL
    error_log('CBSEO Shortcode: Base URL = ' . $base_page_url);
    error_log('CBSEO Shortcode: Paged = ' . $atts['paged']);
    error_log('CBSEO Shortcode: Atts = ' . print_r($atts, true));

    // Generate cache key
    $cache_key = 'cbseo_providers_' . md5(serialize($atts));
    $cached_result = get_transient($cache_key);

    if ($cached_result !== false) {
        $post = $original_post;
        $wp_query = $original_query;
        return $cached_result;
    }

    $args = array(
        'post_type' => 'provider',
        'posts_per_page' => intval($atts['posts_per_page']),
        'paged' => intval($atts['paged']),
        'tax_query' => array('relation' => 'AND'),
    );

    if (!empty($atts['state'])) {
        $args['tax_query'][] = array(
            'taxonomy' => 'state',
            'field' => 'slug',
            'terms' => sanitize_title($atts['state']),
        );
    }

    if (!empty($atts['city'])) {
        $args['tax_query'][] = array(
            'taxonomy' => 'city',
            'field' => 'slug',
            'terms' => sanitize_title($atts['city']),
        );
    }

    // Debug: Log query args
    error_log('CBSEO Shortcode: Query Args = ' . print_r($args, true));

    $providers_query = new WP_Query($args);
    ob_start();
    ?>
    <div class="cbseo-provider-grid">
        <?php if ($providers_query->have_posts()) : ?>
            <?php while ($providers_query->have_posts()) : $providers_query->the_post(); ?>
                <?php
                $meta = get_post_meta(get_the_ID(), '', true);
                $id_verified = isset($meta['_cbseo_id_verified'][0]) ? $meta['_cbseo_id_verified'][0] : '';
                $reply_rating = isset($meta['_cbseo_reply_rating'][0]) ? $meta['_cbseo_reply_rating'][0] : '';
                $relationship_status = isset($meta['_cbseo_relationship_status'][0]) ? $meta['_cbseo_relationship_status'][0] : '';
                $membership_level = isset($meta['_cbseo_membership_level'][0]) ? $meta['_cbseo_membership_level'][0] : '';
                $rate = isset($meta['_cbseo_rate'][0]) ? $meta['_cbseo_rate'][0] : '';
                $tagline = isset($meta['_cbseo_tagline'][0]) ? $meta['_cbseo_tagline'][0] : '';
                $rating = isset($meta['_cbseo_rating'][0]) && is_string($meta['_cbseo_rating'][0]) ? json_decode($meta['_cbseo_rating'][0], true) : [];
                $featured = isset($meta['_cbseo_featured_provider'][0]) ? $meta['_cbseo_featured_provider'][0] : '';
                ?>
                <div class="cbseo-provider-card card primary with-header search-listing">
                    <div class="cbseo-listing-photo listing-photo">
                        <a href="<?php the_permalink(); ?>" class="photo-link">
                            <?php if (has_post_thumbnail()) : ?>
                                <?php the_post_thumbnail('cbseo-provider-thumb', array('alt' => get_the_title() . ' Profile Image')); ?>
                            <?php else : ?>
                                <img src="<?php echo plugins_url('assets/placeholder.png', __FILE__); ?>" alt="Placeholder Image" width="125" height="125">
                            <?php endif; ?>
                        </a>
                    </div>
                    <div class="cbseo-card-header card-header">
                        <div class="cbseo-listing-tools listing-tools">
                            <button type="button" class="cbseo-icon-button icon-button" aria-label="Add to favourites">
                                <i class="fas fa-heart" style="color: #fff;"></i>
                            </button>
                        </div>
                        <div class="cbseo-listing-icons listing-icons">
                            <?php if (strtolower($id_verified) === 'yes') : ?>
                                <div class="cbseo-verified verified">
                                    <i class="fas fa-shield-alt cbseo-verified-icon" style="color: #fff; font-size: 25px;"></i>
                                </div>
                            <?php endif; ?>
                            <?php if ($relationship_status) : ?>
                                <div class="cbseo-sitter-type">
                                    <?php
                                    $status_icon = '';
                                    switch (strtolower($relationship_status)) {
                                        case 'single':
                                            $status_icon = 'fas fa-user';
                                            break;
                                        case 'married':
                                            $status_icon = 'fas fa-users';
                                            break;
                                        default:
                                            $status_icon = 'fas fa-user';
                                    }
                                    ?>
                                    <i class="<?php echo $status_icon; ?>" style="color: #fff;"></i>
                                </div>
                            <?php endif; ?>
                            <?php if ($membership_level) : ?>
                                <div class="cbseo-membership-level">
                                    <?php
                                    $level_icon = '';
                                    switch (strtolower($membership_level)) {
                                        case 'starter':
                                            $level_icon = 'fas fa-star';
                                            break;
                                        case 'silver':
                                            $level_icon = 'fas fa-medal';
                                            break;
                                        case 'gold':
                                            $level_icon = 'fas fa-trophy';
                                            break;
                                        case 'platinum':
                                            $level_icon = 'fas fa-gem';
                                            break;
                                        default:
                                            $level_icon = 'fas fa-star';
                                    }
                                    ?>
                                    <i class="<?php echo $level_icon; ?>" style="color: #fff;"></i>
                                </div>
                            <?php endif; ?>
                            <?php if ($reply_rating) : ?>
                                <div class="cbseo-reply-rating reply-rating">
                                    <?php
                                    $rating = min(5, max(1, round($reply_rating)));
                                    for ($i = 1; $i <= 5; $i++) {
                                        $color = ($i <= $rating) ? '#fff' : '#000';
                                        echo '<i class="fas fa-circle" style="color: ' . $color . '; margin-right: 4px;"></i>';
                                    }
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="cbseo-card-body card-body with-pay-info">
                        <div class="cbseo-pay-info pay-info">
                            <?php if ($rate) : ?>
                                <span class="cbseo-chip chip primary-active with-icon">
                                    <i class="fas fa-bill" style="color: #fff;"></i>
                                    <span class="cbseo-label label"><?php echo esc_html($rate); ?></span>
                                </span>
                            <?php endif; ?>
                        </div>
                        <h3>
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h3>
                        <?php if ($tagline) : ?>
                            <div class="cbseo-listing-tag listing-tag"><?php echo esc_html($tagline); ?></div>
                        <?php endif; ?>
                        <p class="cbseo-listing-intro listing-intro">
                            <?php echo wp_trim_words(get_the_excerpt(), 20); ?> <a href="<?php the_permalink(); ?>">View</a>
                        </p>
                        <?php if (!empty($rating) && is_array($rating)) : ?>
                            <ul class="cbseo-icon-list icon-list">
                                <li>
                                    <i class="fas fa-star" style="color: #C487A5;"></i>
                                    <a href="<?php the_permalink(); ?>#rating"><?php echo count($rating); ?> reviews</a>
                                </li>
                            </ul>
                        <?php endif; ?>
                        <?php if ($featured === 'yes') : ?>
                            <span class="featured-badge">Featured Provider</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
            <div class="cbseo-pagination">
                <?php
                $wp_query = $original_query;
                $post = $original_post;
                if ($original_post_id) {
                    setup_postdata($post);
                }

                $total_pages = $providers_query->max_num_pages;
                $current_page = max(1, $atts['paged']);
                if ($total_pages > 1) {
                    $query_args = array('cbseo_page' => '%#%');
                    if (!empty($atts['state'])) $query_args['state'] = $atts['state'];
                    if (!empty($atts['city'])) $query_args['city'] = $atts['city'];

                    $pagination_links = '<div class="cbseo-custom-pagination">';
                    if ($current_page > 1) {
                        $prev_page = $current_page - 1;
                        $pagination_links .= '<a class="prev page-numbers" href="' . esc_url(add_query_arg(array_merge($query_args, ['cbseo_page' => $prev_page]), $base_page_url)) . '">Previous</a>';
                    }
                    for ($i = 1; $i <= $total_pages; $i++) {
                        if ($i == $current_page) {
                            $pagination_links .= '<span class="cbseo-page-number current">' . $i . '</span>';
                        } else {
                            $pagination_links .= '<a class="page-numbers" href="' . esc_url(add_query_arg(array_merge($query_args, ['cbseo_page' => $i]), $base_page_url)) . '">' . $i . '</a>';
                        }
                    }
                    if ($current_page < $total_pages) {
                        $next_page = $current_page + 1;
                        $pagination_links .= '<a class="next page-numbers" href="' . esc_url(add_query_arg(array_merge($query_args, ['cbseo_page' => $next_page]), $base_page_url)) . '">Next</a>';
                    }
                    $pagination_links .= '</div>';

                    // Debug: Log pagination links
                    error_log('CBSEO Shortcode: Pagination Links = ' . $pagination_links);

                    echo $pagination_links;
                }
                ?>
            </div>
        <?php else : ?>
            <p>No providers found for this location. Contact us to list your service!</p>
        <?php endif; ?>
    </div>
    <?php
    wp_reset_postdata();
    $post = $original_post;
    $wp_query = $original_query;
    $output = ob_get_clean();
    // set_transient($cache_key, $output, 12 * HOUR_IN_SECONDS); // Temporarily disable caching
    return $output;
}





// Template Loading for Provider Archive
add_action('template_include', 'cbseo_provider_archive_template', 99);
function cbseo_provider_archive_template($template) {
    if (is_post_type_archive('provider')) {
        $new_template = plugin_dir_path(__FILE__) . 'templates/provider-archive.php';
        if (!file_exists($new_template)) {
            if (!is_dir(plugin_dir_path(__FILE__) . 'templates')) {
                mkdir(plugin_dir_path(__FILE__) . 'templates', 0755, true);
            }
            $archive_template = <<<EOD
<?php
get_header();
\$state_filter = isset(\$_GET['state']) ? sanitize_text_field(\$_GET['state']) : '';
\$city_filter = isset(\$_GET['city']) ? sanitize_text_field(\$_GET['city']) : '';
\$args = array(
    'post_type' => 'provider',
    'posts_per_page' => 10,
    'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
    'tax_query' => array('relation' => 'AND'),
);
if (\$state_filter) {
    \$args['tax_query'][] = array(
        'taxonomy' => 'state',
        'field' => 'slug',
        'terms' => sanitize_title(\$state_filter),
    );
}
if (\$city_filter) {
    \$args['tax_query'][] = array(
        'taxonomy' => 'city',
        'field' => 'slug',
        'terms' => sanitize_title(\$city_filter),
    );
}
\$providers = new WP_Query(\$args);
?>
<div class="cbseo-provider-archive">
    <h1>All Cat Boarding Providers</h1>
    <div class="cbseo-filter-form">
        <form method="get" action="">
            <label for="state">Filter by State:</label>
            <select name="state" id="state" onchange="this.form.submit()">
                <option value="">All States</option>
                <?php
                \$states = get_terms(array('taxonomy' => 'state', 'hide_empty' => false));
                foreach (\$states as \$state) {
                    echo '<option value="' . esc_attr(\$state->slug) . '" ' . selected(\$state_filter, \$state->slug, false) . '>' . esc_html(\$state->name) . '</option>';
                }
                ?>
            </select>
            <label for="city">Filter by City:</label>
            <select name="city" id="city" onchange="this.form.submit()">
                <option value="">All Cities</option>
                <?php
                \$cities = get_terms(array('taxonomy' => 'city', 'hide_empty' => false));
                foreach (\$cities as \$city) {
                    echo '<option value="' . esc_attr(\$city->slug) . '" ' . selected(\$city_filter, \$city->slug, false) . '>' . esc_html(\$city->name) . '</option>';
                }
                ?>
            </select>
            <input type="hidden" name="paged" value="1">
        </form>
    </div>
    <?php echo do_shortcode('[cbseo_providers state="' . esc_attr(\$state_filter) . '" city="' . esc_attr(\$city_filter) . '"]'); ?>
</div>
<?php
get_footer();
EOD;
            file_put_contents($new_template, $archive_template);
            error_log('Provider archive template generated at: ' . $new_template);
        }
        return $new_template;
    }
    return $template;
}



// Template Loading for Cat Boarding Archive  NEW NEWWWW
add_action('template_include', 'cbseo_cat_boarding_archive_template', 99);
function cbseo_cat_boarding_archive_template($template) {
    if (is_post_type_archive('cat_boarding')) {
        $new_template = plugin_dir_path(__FILE__) . 'templates/archive-cat_boarding.php';
        if (!file_exists($new_template)) {
            if (!is_dir(plugin_dir_path(__FILE__) . 'templates')) {
                mkdir(plugin_dir_path(__FILE__) . 'templates', 0755, true);
            }
            $archive_template = <<<EOD
<?php
get_header();
\$state_filter = isset(\$_GET['state']) ? sanitize_text_field(\$_GET['state']) : '';
\$city_filter = isset(\$_GET['city']) ? sanitize_text_field(\$_GET['city']) : '';
\$args = array(
    'post_type' => 'cat_boarding',
    'posts_per_page' => 10,
    'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
    'tax_query' => array('relation' => 'AND'),
);
if (\$state_filter) {
    \$args['tax_query'][] = array(
        'taxonomy' => 'state',
        'field' => 'slug',
        'terms' => sanitize_title(\$state_filter),
    );
}
if (\$city_filter) {
    \$args['tax_query'][] = array(
        'taxonomy' => 'city',
        'field' => 'slug',
        'terms' => sanitize_title(\$city_filter),
    );
}
\$locations = new WP_Query(\$args);
?>
<div class="cbseo-cat-boarding-archive">
    <h1>Cat Boarding Locations</h1>
    <div class="cbseo-filter-form">
        <form method="get" action="">
            <label for="state">Filter by State:</label>
            <select name="state" id="state" onchange="this.form.submit()">
                <option value="">All States</option>
                <?php
                \$states = get_terms(array('taxonomy' => 'state', 'hide_empty' => false));
                foreach (\$states as \$state) {
                    echo '<option value="' . esc_attr(\$state->slug) . '" ' . selected(\$state_filter, \$state->slug, false) . '>' . esc_html(\$state->name) . '</option>';
                }
                ?>
            </select>
            <label for="city">Filter by City:</label>
            <select name="city" id="city" onchange="this.form.submit()">
                <option value="">All Cities</option>
                <?php
                \$cities = get_terms(array('taxonomy' => 'city', 'hide_empty' => false));
                foreach (\$cities as \$city) {
                    echo '<option value="' . esc_attr(\$city->slug) . '" ' . selected(\$city_filter, \$city->slug, false) . '>' . esc_html(\$city->name) . '</option>';
                }
                ?>
            </select>
            <input type="hidden" name="paged" value="1">
        </form>
    </div>
    <div class="cbseo-location-grid">
        <?php if (\$locations->have_posts()) : ?>
            <?php while (\$locations->the_post()) : ?>
                <div class="cbseo-location-card">
                    <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                    <p><?php echo wp_trim_words(get_the_excerpt(), 20); ?> <a href="<?php the_permalink(); ?>">Learn More</a></p>
                </div>
            <?php endwhile; ?>
            <div class="cbseo-pagination">
                <?php
                echo paginate_links(array(
                    'total' => \$locations->max_num_pages,
                    'current' => max(1, get_query_var('paged')),
                    'prev_text' => __('Previous'),
                    'next_text' => __('Next'),
                ));
                ?>
            </div>
        <?php else : ?>
            <p>No cat boarding locations found.</p>
        <?php endif; ?>
    </div>
</div>
<?php
get_footer();
EOD;
            file_put_contents($new_template, $archive_template);
            error_log('Cat boarding archive template generated at: ' . $new_template);
        }
        return $new_template;
    }
    return $template;
}


// Template Loading for Single Cat Boarding NEW NEWWWWWW
add_action('template_include', 'cbseo_single_cat_boarding_template', 99);
function cbseo_single_cat_boarding_template($template) {
    if (is_singular('cat_boarding')) {
        $new_template = plugin_dir_path(__FILE__) . 'templates/single-cat_boarding.php';
        if (!file_exists($new_template)) {
            if (!is_dir(plugin_dir_path(__FILE__) . 'templates')) {
                mkdir(plugin_dir_path(__FILE__) . 'templates', 0755, true);
            }
            $single_template = <<<EOD
<?php
get_header();
if (have_posts()) : while (have_posts()) : the_post();
?>
<div class="cbseo-single-cat-boarding">
    <h1><?php the_title(); ?></h1>
    <?php if (has_post_thumbnail()) : ?>
        <div class="cbseo-featured-image">
            <?php the_post_thumbnail('large', array('alt' => get_the_title() . ' Featured Image')); ?>
        </div>
    <?php endif; ?>
    <div class="cbseo-content">
        <?php the_content(); ?>
    </div>
</div>
<?php
endwhile; endif;
get_footer();
EOD;
            file_put_contents($new_template, $single_template);
            error_log('Single cat boarding template generated at: ' . $new_template);
        }
        return $new_template;
    }
    return $template;
}

// Template Loading for Single Provider  NEW NEWWWWWW
add_action('template_include', 'cbseo_single_provider_template', 99);
function cbseo_single_provider_template($template) {
    if (is_singular('provider')) {
        $new_template = plugin_dir_path(__FILE__) . 'templates/single-provider.php';
        if (!file_exists($new_template)) {
            if (!is_dir(plugin_dir_path(__FILE__) . 'templates')) {
                mkdir(plugin_dir_path(__FILE__) . 'templates', 0755, true);
            }
            $single_template = <<<EOD
<?php
get_header();
if (have_posts()) : while (have_posts()) : the_post();
\$meta = get_post_meta(get_the_ID(), '', true);
\$id_verified = isset(\$meta['_cbseo_id_verified'][0]) ? \$meta['_cbseo_id_verified'][0] : '';
\$reply_rating = isset(\$meta['_cbseo_reply_rating'][0]) ? \$meta['_cbseo_reply_rating'][0] : '';
\$relationship_status = isset(\$meta['_cbseo_relationship_status'][0]) ? \$meta['_cbseo_relationship_status'][0] : '';
\$membership_level = isset(\$meta['_cbseo_membership_level'][0]) ? \$meta['_cbseo_membership_level'][0] : '';
\$rate = isset(\$meta['_cbseo_rate'][0]) ? \$meta['_cbseo_rate'][0] : '';
\$tagline = isset(\$meta['_cbseo_tagline'][0]) ? \$meta['_cbseo_tagline'][0] : '';
\$rating = isset(\$meta['_cbseo_rating'][0]) && is_string(\$meta['_cbseo_rating'][0]) ? json_decode(\$meta['_cbseo_rating'][0], true) : [];
\$featured = isset(\$meta['_cbseo_featured_provider'][0]) ? \$meta['_cbseo_featured_provider'][0] : '';
\$phone_number = isset(\$meta['_cbseo_phone_number'][0]) ? \$meta['_cbseo_phone_number'][0] : '';
\$address = isset(\$meta['_cbseo_address'][0]) ? \$meta['_cbseo_address'][0] : '';
\$website = isset(\$meta['_cbseo_website'][0]) ? \$meta['_cbseo_website'][0] : '';
\$services_offered = isset(\$meta['_cbseo_services_offered'][0]) ? \$meta['_cbseo_services_offered'][0] : '';
\$pet_types = isset(\$meta['_cbseo_pet_types'][0]) ? \$meta['_cbseo_pet_types'][0] : '';
\$gallery_images = isset(\$meta['_cbseo_gallery_images'][0]) ? \$meta['_cbseo_gallery_images'][0] : '';
\$trust_note = isset(\$meta['_cbseo_trust_note'][0]) ? \$meta['_cbseo_trust_note'][0] : '';
?>
<div class="cbseo-single-provider">
    <h1><?php the_title(); ?><?php if (\$featured === 'yes') : ?> <span class="featured-badge">Featured Provider</span><?php endif; ?></h1>
    <?php if (has_post_thumbnail()) : ?>
        <div class="cbseo-featured-image">
            <?php the_post_thumbnail('large', array('alt' => get_the_title() . ' Profile Image')); ?>
        </div>
    <?php endif; ?>
    <div class="cbseo-provider-details">
        <?php if (\$tagline) : ?>
            <p class="cbseo-tagline"><?php echo esc_html(\$tagline); ?></p>
        <?php endif; ?>
        <?php if (\$rate) : ?>
            <p class="cbseo-rate"><strong>Rate:</strong> <?php echo esc_html(\$rate); ?></p>
        <?php endif; ?>
        <?php if (\$phone_number) : ?>
            <p class="cbseo-phone"><strong>Phone:</strong> <a href="tel:<?php echo esc_attr(\$phone_number); ?>"><?php echo esc_html(\$phone_number); ?></a></p>
        <?php endif; ?>
        <?php if (\$address) : ?>
            <p class="cbseo-address"><strong>Address:</strong> <?php echo esc_html(\$address); ?></p>
        <?php endif; ?>
        <?php if (\$website) : ?>
            <p class="cbseo-website"><strong>Website:</strong> <a href="<?php echo esc_url(\$website); ?>" target="_blank"><?php echo esc_html(\$website); ?></a></p>
        <?php endif; ?>
        <?php if (\$services_offered) : ?>
            <p class="cbseo-services"><strong>Services Offered:</strong> <?php echo esc_html(\$services_offered); ?></p>
        <?php endif; ?>
        <?php if (\$pet_types) : ?>
            <p class="cbseo-pet-types"><strong>Pet Types:</strong> <?php echo esc_html(\$pet_types); ?></p>
        <?php endif; ?>
        <?php if (\$trust_note) : ?>
            <p class="cbseo-trust-note"><strong>Trust Note:</strong> <?php echo esc_html(\$trust_note); ?></p>
        <?php endif; ?>
        <?php if (!empty(\$rating) && is_array(\$rating)) : ?>
            <div class="cbseo-reviews" id="rating">
                <h2>Reviews</h2>
                <?php foreach (\$rating as \$review) : ?>
                    <div class="cbseo-review">
                        <p><strong><?php echo esc_html(\$review['name']); ?> (<?php echo esc_html(\$review['location']); ?>)</strong></p>
                        <p><em><?php echo esc_html(\$review['duration']); ?></em></p>
                        <p><?php echo esc_html(\$review['comment']); ?></p>
                        <p><strong>Ratings:</strong> <?php echo esc_html(\$review['ratings']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if (\$gallery_images) : ?>
            <div class="cbseo-gallery">
                <h2>Gallery</h2>
                <?php
                \$images = array_map('trim', explode(',', \$gallery_images));
                foreach (\$images as \$image) : ?>
                    <img src="<?php echo esc_url(\$image); ?>" alt="Gallery Image" style="max-width: 200px; margin: 10px;">
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="cbseo-content">
        <?php the_content(); ?>
    </div>
</div>
<?php
endwhile; endif;
get_footer();
EOD;
            file_put_contents($new_template, $single_template);
            error_log('Single provider template generated at: ' . $new_template);
        }
        return $new_template;
    }
    return $template;
}







// Register Custom Image Size
add_action('after_setup_theme', function() {
    add_image_size('cbseo-provider-thumb', 100, 100, true);
});




add_action('wp_enqueue_scripts', 'cbseo_enqueue_scripts');
function cbseo_enqueue_scripts() {
    // Always enqueue the primary stylesheet
    wp_enqueue_style('cbseo-style', plugin_dir_url(__FILE__) . 'assets/style.css', [], '2.0.4');
    
    // Enqueue Font Awesome and scripts for single provider, provider archive, state/city taxonomy pages, and single cat_boarding pages
    if (is_singular('provider') || is_post_type_archive('provider') || is_tax('state') || is_tax('city') || is_singular('cat_boarding')) {
        wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css', [], '5.15.4');
        wp_enqueue_script('cbseo-scripts', plugin_dir_url(__FILE__) . 'scripts.js', ['jquery', 'jquery-ui-core', 'jquery-ui-widget', 'jquery-ui-slider'], '2.0.4', true);
        wp_script_add_data('cbseo-scripts', 'defer', true);
        wp_enqueue_script('recaptcha', 'https://www.google.com/recaptcha/api.js?render=' . esc_js(get_option('cbseo_recaptcha_site_key', '')), [], null, true);
        wp_localize_script('cbseo-scripts', 'cbseo_data', [
            'recaptcha_site_key' => get_option('cbseo_recaptcha_site_key', ''),
            'ajax_url' => admin_url('admin-ajax.php')
        ]);
    }

    // Enqueue cat.css for provider archive, state/city taxonomy pages, and single cat_boarding pages
    if (is_post_type_archive('provider') || is_tax('state') || is_tax('city') || is_singular('cat_boarding')) {
        wp_enqueue_style('cbseo-provider-style', plugin_dir_url(__FILE__) . 'assets/cat.css', ['cbseo-style'], '2.0.4');
    }

    // Add inline CSS for single provider page and to ensure verified icon is visible
    $custom_css = "
        .cbseo-single-provider {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .cbseo-single-provider h1 {
            font-size: 2em;
            margin-bottom: 10px;
            color: #333;
        }
        .cbseo-featured-image img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .cbseo-provider-details p {
            margin: 10px 0;
            font-size: 1em;
            color: #555;
        }
        .cbseo-provider-details strong {
            color: #333;
        }
        .cbseo-reviews, .cbseo-gallery {
            margin-top: 20px;
        }
        .cbseo-reviews h2, .cbseo-gallery h2 {
            font-size: 1.5em;
            color: #333;
            margin-bottom: 10px;
        }
        .cbseo-review {
            border-bottom: 1px solid #eee;
            padding: 10px 0;
        }
        .cbseo-gallery img {
            max-width: 200px;
            margin: 10px;
            border-radius: 4px;
        }
        .featured-badge {
            background: #ffd700;
            color: #000;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.9em;
            margin-left: 10px;
        }
        .cbseo-provider-grid .cbseo-listing-icons .cbseo-verified.verified {
            display: inline-flex !important;
            visibility: visible !important;
            opacity: 1 !important;
            margin-right: 8px;
            align-items: center;
            justify-content: center;
        }
        .cbseo-provider-grid .cbseo-listing-icons .cbseo-verified.verified .cbseo-verified-icon.fas.fa-shield-alt {
            font-family: 'Font Awesome 5 Free' !important;
            font-weight: 900 !important;
            font-size: 25px !important;
            color: #fff !important;
            display: inline-block !important;
            visibility: visible !important;
            opacity: 1 !important;
            margin: 0 !important;
            line-height: 1 !important;
            width: 25px !important;
            height: 25px !important;
            text-align: center !important;
        }
        .cbseo-provider-grid .cbseo-listing-icons .cbseo-verified.verified .cbseo-verified-icon.fas.fa-shield-alt::before {
            content: '\f3ed' !important; /* Unicode for fa-shield-alt */
        }
    ";
    wp_add_inline_style('cbseo-style', $custom_css);
}



// Yoast SEO for Providers
add_action('save_post_provider', function($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $state_terms = get_the_terms($post_id, 'state');
    $city_terms = get_the_terms($post_id, 'city');
    $state_name = !empty($state_terms) && is_array($state_terms) ? $state_terms[0]->name : '';
    $city_name = !empty($city_terms) && is_array($city_terms) ? $city_terms[0]->name : '';
    $location = $city_name ? "$city_name, $state_name" : $state_name;

    $title = get_the_title($post_id) . " - Cat Boarding Provider in " . $location;
    $description = "Book trusted cat boarding with " . get_the_title($post_id) . " in " . $location . ". Safe and caring services for your pet.";
    $keyword = "cat boarding " . $location;

    update_post_meta($post_id, '_yoast_wpseo_title', $title);
    update_post_meta($post_id, '_yoast_wpseo_metadesc', $description);
    update_post_meta($post_id, '_yoast_wpseo_focuskw', $keyword);
});

// Yoast SEO for Cat Boarding Locations
add_action('save_post_cat_boarding', function($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $state_terms = get_the_terms($post_id, 'state');
    $city_terms = get_the_terms($post_id, 'city');
    $state_name = !empty($state_terms) && is_array($state_terms) ? $state_terms[0]->name : '';
    $city_name = !empty($city_terms) && is_array($city_terms) ? $city_terms[0]->name : '';

    $location = $city_name ? "$city_name, $state_name" : $state_name;

    $title = "Cat Boarding in $location - Trusted Pet Care Services";
    $description = $city_name ? "Find the best cat boarding in $city_name, $state_name. Safe, comfortable, and trusted pet care services for your feline friend." :
                               "Discover top cat boarding services in $state_name. Reliable and caring facilities for your pet's comfort.";
    $keyword = "cat boarding $location";

    update_post_meta($post_id, '_yoast_wpseo_title', $title);
    update_post_meta($post_id, '_yoast_wpseo_metadesc', $description);
    update_post_meta($post_id, '_yoast_wpseo_focuskw', $keyword);
});

// Programmatic Page Generation
function cbseo_generate_location_pages($batch_size = 3) {
    $settings = get_option('cbseo_plugin_settings', array(
        'locations' => array(
            'states' => array(
                'Alabama' => array('Birmingham', 'Montgomery'),
                'California' => array('Los Angeles', 'San Francisco', 'San Diego'),
                'Florida' => array('Miami', 'Orlando', 'Tampa'),
            ),
            'data' => array(
                'Alabama' => array('capital' => 'Montgomery', 'population' => '4.9M'),
                'California' => array('capital' => 'Sacramento', 'population' => '39.5M'),
                'Florida' => array('capital' => 'Tallahassee', 'population' => '21.5M'),
            ),
        ),
        'pet_types' => array('Cat'),
        'batch_size' => 3
    ));
    $batch_size = isset($settings['batch_size']) ? max(1, intval($settings['batch_size'])) : $batch_size; // Use saved batch size
    $locations = $settings['locations'];
    $templates = get_option('cbseo_content_templates', array(
        'state_template' => '<h2>Cat Boarding in {state}</h2><p>Explore premier cat boarding options across {state}, home to {population} residents and the capital city of {capital}. Whether you\'re near {capital} or {major_cities}, find trusted care for your feline friend, even amidst {climate} weather. Our curated listings ensure a safe stay during {peak_seasons}.</p>[cbseo_providers state="{state}"]<h2>Frequently Asked Questions</h2><h3>How does {climate} weather affect cat boarding in {state}?</h3><p>{climate} conditions require facilities to offer climate control and shaded areas, especially in cities like {major_cities}.</p><h3>Are there special regulations for cat boarding in {state}?</h3><p>Yes, {state} enforces {regulations}, including regular inspections. Verify provider compliance.</p><h3>What are the busiest cat boarding seasons in {state}?</h3><p>Peak times are {peak_seasons}, particularly in {major_cities}. Book 4-6 weeks ahead.</p><h3>Can I find luxury cat boarding in {state}?</h3><p>{luxury_available}, with rates from {avg_cost} in urban areas like {major_cities}.</p><h3>What should I do if my cat needs medication in {state}?</h3><p>Provide a vet note and medications. {vet_access} is common, especially in cities.</p><h3>How can I tour a cat boarding facility in {state}?</h3><p>{tour_option} are available, especially in {major_cities}. Schedule in advance.</p>',
        'city_template' => '<h2>Cat Boarding in {city}, {state}</h2><p>Discover top-quality cat boarding services in {city}, {state}, a vibrant city with {population} residents. Enjoy peace of mind near {local_attraction}, even with {weather_pattern} weather. Our curated listings provide safe care during {peak_seasons}, perfect for exploring {state}.</p>[cbseo_providers state="{state}" city="{city}"]<h2>Frequently Asked Questions</h2><h3>How does {weather_pattern} weather impact cat boarding in {city}?</h3><p>{weather_pattern} conditions necessitate climate-controlled environments and outdoor shelters, especially in {city} neighborhoods.</p><h3>Are there local regulations for cat boarding in {city}?</h3><p>Yes, {city} follows {state}’s {regulations}, with additional city-specific permits. Check provider credentials.</p><h3>What are the peak cat boarding seasons in {city}?</h3><p>The busiest times are {peak_seasons}, especially around {local_attraction} events. Reserve 4-6 weeks in advance.</p><h3>Is luxury cat boarding available in {city}?</h3><p>{luxury_available}, with rates starting at {avg_cost} near {local_attraction}.</p><h3>What if my cat requires medication in {city}?</h3><p>Bring a vet note and medications. {vet_access} is widely available, particularly in {city} clinics.</p><h3>Can I visit a cat boarding facility in {city}?</h3><p>{tour_option} are offered, often near {local_attraction}. Contact providers to schedule.</p>',
    ));
    $progress = get_option('cbseo_generation_progress', array('current_index' => 0, 'completed' => false));

    $states = array_keys($locations['states']);
    $total_states = count($states);
    $start_index = $progress['current_index'];
    $end_index = min($start_index + $batch_size, $total_states);

    // Check if all pages are already generated
    $all_pages_exist = true;
    foreach ($states as $state) {
        $state_slug = sanitize_title($state);
        if (!get_page_by_path($state_slug, OBJECT, 'cat_boarding')) {
            $all_pages_exist = false;
            break;
        }
        foreach ($locations['states'][$state] as $city) {
            $city_slug = sanitize_title($city);
            if (!get_page_by_path($city_slug, OBJECT, 'cat_boarding')) {
                $all_pages_exist = false;
                break 2;
            }
        }
    }

    if ($all_pages_exist) {
        delete_option('cbseo_generation_progress');
        echo '<div class="updated"><p>All location pages are already generated!</p></div>';
        return;
    }

    for ($i = $start_index; $i < $end_index; $i++) {
        $state = $states[$i];
        $state_slug = sanitize_title($state);
        if (!get_page_by_path($state_slug, OBJECT, 'cat_boarding')) {
            $state_content = cbseo_generate_content($state, '', $locations['data'][$state] ?? [], $templates['state_template']);
            $state_post_id = wp_insert_post(array(
                'post_title' => 'Cat Boarding in ' . $state,
                'post_content' => $state_content,
                'post_type' => 'cat_boarding',
                'post_status' => 'publish',
                'post_name' => $state_slug,
            ));

            if ($state_post_id) {
                wp_set_object_terms($state_post_id, $state, 'state');
                cbseo_set_yoast_seo($state_post_id, $state, '');
            }
        }

        foreach ($locations['states'][$state] as $city) {
            $city_slug = sanitize_title($city);
            if (!get_page_by_path($city_slug, OBJECT, 'cat_boarding')) {
                $city_content = cbseo_generate_content($state, $city, $locations['data'][$state] ?? [], $templates['city_template']);
                $city_post_id = wp_insert_post(array(
                    'post_title' => 'Cat Boarding in ' . $city,
                    'post_content' => $city_content,
                    'post_type' => 'cat_boarding',
                    'post_status' => 'publish',
                    'post_name' => $city_slug,
                ));

                if ($city_post_id) {
                    wp_set_object_terms($city_post_id, $state, 'state');
                    wp_set_object_terms($city_post_id, $city, 'city');
                    cbseo_set_yoast_seo($city_post_id, $state, $city);
                }
            }
        }
    }

    $progress['current_index'] = $end_index;
    if ($end_index >= $total_states) {
        $progress['completed'] = true;
        delete_option('cbseo_generation_progress');
        echo '<div class="updated"><p>All location pages generated successfully!</p></div>';
    } else {
        update_option('cbseo_generation_progress', $progress);
        echo '<div class="updated"><p>Generated batch up to ' . $state . '. <a href="' . admin_url('admin.php?page=cbseo-generate') . '">Continue</a></p></div>';
    }
}

function cbseo_generate_content($state, $city = '', $data = array(), $template = '') {
    $location = $city ?: $state;
    $replacements = array(
        '{state}' => $state,
        '{city}' => $city,
        '{location}' => $location,
    );

    // Dynamically add all data fields as placeholders
    foreach ($data as $key => $value) {
        $replacements['{' . $key . '}'] = $value ?: '';
    }

    return str_replace(array_keys($replacements), array_values($replacements), $template);
}

function cbseo_set_yoast_seo($post_id, $state, $city = '') {
    $location = $city ?: $state;
    $title = "Cat Boarding in $location - Trusted Pet Care Services";
    $description = $city ? "Find the best cat boarding in $city, $state. Safe, comfortable, and trusted pet care services for your feline friend." :
                          "Discover top cat boarding services in $state. Reliable and caring facilities for your pet's comfort.";
    $keyword = "cat boarding $location";

    update_post_meta($post_id, '_yoast_wpseo_title', $title);
    update_post_meta($post_id, '_yoast_wpseo_metadesc', $description);
    update_post_meta($post_id, '_yoast_wpseo_focuskw', $keyword);
}

// Add Schema Markup for Provider Post Type
add_action('wp_head', 'cbseo_add_provider_schema');

function cbseo_add_provider_schema() {
    if (is_singular('provider')) {
        global $post;
        $phone_number = get_post_meta($post->ID, '_cbseo_phone_number', true) ?: '';
        $address = get_post_meta($post->ID, '_cbseo_address', true) ?: '';
        $rating = get_post_meta($post->ID, '_cbseo_rating', true);
        $rating = $rating && is_string($rating) ? json_decode($rating, true) : [];
        $rating_count = count($rating);
        $rating_value = 0;
        if ($rating_count > 0) {
            $total_rating = array_sum(array_map(function($r) {
                $ratings = explode(',', $r['ratings']);
                return array_sum($ratings) / count($ratings);
            }, $rating));
            $rating_value = $total_rating / $rating_count;
        }

        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => get_the_title(),
            'url' => get_permalink(),
            'telephone' => $phone_number,
            'address' => array(
                '@type' => 'PostalAddress',
                'streetAddress' => $address,
                'addressLocality' => wp_get_post_terms($post->ID, 'city', array('fields' => 'names'))[0] ?? '',
                'addressRegion' => wp_get_post_terms($post->ID, 'state', array('fields' => 'names'))[0] ?? '',
                'addressCountry' => 'US'
            ),
            'image' => get_the_post_thumbnail_url($post->ID, 'full') ?: '',
            'priceRange' => get_post_meta($post->ID, '_cbseo_rate', true) ?: '',
            'aggregateRating' => array(
                '@type' => 'AggregateRating',
                'ratingValue' => number_format($rating_value, 1),
                'reviewCount' => $rating_count
            )
        );

        if (!empty($rating)) {
            $schema['review'] = array_map(function($r) {
                return array(
                    '@type' => 'Review',
                    'author' => array(
                        '@type' => 'Person',
                        'name' => $r['name']
                    ),
                    'reviewBody' => $r['comment'],
                    'reviewRating' => array(
                        '@type' => 'Rating',
                        'ratingValue' => array_sum(explode(',', $r['ratings'])) / count(explode(',', $r['ratings']))
                    )
                );
            }, $rating);
        }

        echo '<script type="application/ld+json">' . json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . '</script>';
    }
}



// Initialize Default Provider if None Exist
function cbseo_initialize_default_provider() {
    $provider_count = wp_count_posts('provider')->publish;
    if ($provider_count == 0) {
        $post_id = wp_insert_post(array(
            'post_title' => 'Default Provider',
            'post_content' => 'A default provider for testing.',
            'post_type' => 'provider',
            'post_status' => 'publish',
        ));
        if ($post_id) {
            wp_set_object_terms($post_id, 'California', 'state');
            wp_set_object_terms($post_id, 'Los Angeles', 'city');
            update_post_meta($post_id, '_cbseo_tagline', 'Trusted Cat Boarding');
            update_post_meta($post_id, '_cbseo_rating', wp_json_encode([]));
            update_post_meta($post_id, '_cbseo_id_verified', 'No');
            update_post_meta($post_id, '_cbseo_reply_rating', '0');
            update_post_meta($post_id, '_cbseo_relationship_status', 'Single');
            update_post_meta($post_id, '_cbseo_membership_level', 'Starter');
            update_post_meta($post_id, '_cbseo_rate', 'FREE');
            update_post_meta($post_id, '_cbseo_featured_provider', 'no');
            error_log('Default provider created with ID: ' . $post_id);
        }
    }
}
add_action('init', 'cbseo_initialize_default_provider', 11);

// Generate Sample Locations CSV
function cbseo_generate_sample_csv() {
    $sample_data = "state,cities,capital,population,climate,major_cities,peak_seasons,regulations,luxury_available,avg_cost,vet_access,tour_option,local_attraction\nAlabama,Birmingham,Montgomery,4.9M,warm,Birmingham,Summer,vaccination checks,Yes,$60/day,24/7 vet services,Virtual tours,Gulf Shores\nCalifornia,Los Angeles,Sacramento,39.5M,mild,Los Angeles,Summer-Fall,health certificates,Yes,$75/day,vet on call,In-person tours,Hollywood\nFlorida,Miami,Orlando,Tampa,21.5M,warm,Miami,Winter-Spring,licensing requirements,Yes,$70/day,emergency vet services,Virtual tours,Miami Beach\nTexas,Austin,Houston,Dallas,29.5M,hot,Houston,Summer,inspection mandates,Yes,$65/day,24/7 vet services,In-person tours,Austin Riverwalk\nNew York,New York City,Albany,19.5M,cool,New York City,Winter,housing standards,Yes,$80/day,vet on call,Virtual tours,Statue of Liberty\nIllinois,Chicago,Springfield,19.1M,cold,Chicago,Winter-Spring,vaccination checks,Yes,$65/day,emergency vet services,In-person tours,Millennium Park\nPennsylvania,Philadelphia,Harrisburg,13.0M,temperate,Philadelphia,Fall,health certificates,Yes,$70/day,24/7 vet services,Virtual tours,Liberty Bell\nOhio,Columbus,Columbus,11.8M,moderate,Columbus,Summer-Fall,licensing requirements,Yes,$55/day,vet on call,In-person tours,Rock & Roll Hall of Fame\nGeorgia,Atlanta,Atlanta,10.7M,humid,Atlanta,Summer,inspection mandates,Yes,$60/day,emergency vet services,Virtual tours,Georgia Aquarium\nNorth Carolina,Charlotte,Raleigh,10.5M,mild,Charlotte,Spring-Summer,vaccination checks,Yes,$65/day,24/7 vet services,In-person tours,Biltmore Estate";
    $file_path = plugin_dir_path(__FILE__) . 'sample-locations.csv';
    if (!file_exists($file_path)) {
        file_put_contents($file_path, $sample_data);
    }
}
add_action('init', 'cbseo_generate_sample_csv');

// Handle Sample CSV Download
function cbseo_download_sample_csv() {
    if (isset($_GET['action']) && $_GET['action'] === 'cbseo_download_sample_csv') {
        $file_path = plugin_dir_path(__FILE__) . 'sample-locations.csv';
        if (file_exists($file_path)) {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="sample-locations.csv"');
            header('Content-Length: ' . filesize($file_path));
            readfile($file_path);
            exit;
        } else {
            wp_die('Sample CSV file not found.');
        }
    }
}
add_action('admin_post_cbseo_download_sample_csv', 'cbseo_download_sample_csv');



add_action('admin_post_nopriv_cbseo_submit_review', 'cbseo_handle_review_submission');
add_action('admin_post_cbseo_submit_review', 'cbseo_handle_review_submission');

function cbseo_handle_review_submission() {
    header('Content-Type: application/json');

    if (!isset($_POST['cbseo_review_nonce']) || !wp_verify_nonce($_POST['cbseo_review_nonce'], 'cbseo_submit_review')) {
        wp_send_json(array('success' => false, 'message' => __('Invalid nonce.', 'cbseo')));
        return;
    }

    if (!is_user_logged_in()) {
        wp_send_json(array('success' => false, 'message' => __('You must be logged in to submit a review.', 'cbseo')));
        return;
    }

    $recaptcha_secret = get_option('cbseo_recaptcha_secret_key', '');
    $recaptcha_response = sanitize_text_field($_POST['g-recaptcha-response']);
    $recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
    $recaptcha_data = array(
        'secret' => $recaptcha_secret,
        'response' => $recaptcha_response,
        'remote_ip' => $_SERVER['REMOTE_ADDR']
    );
    $recaptcha_request = wp_remote_post($recaptcha_url, array('body' => $recaptcha_data));
    if (is_wp_error($recaptcha_request)) {
        wp_send_json(array('success' => false, 'message' => __('reCAPTCHA verification failed.', 'cbseo')));
        return;
    }
    $recaptcha_result = json_decode(wp_remote_retrieve_body($recaptcha_request), true);
    if (!$recaptcha_result['success'] || $recaptcha_result['score'] < 0.3) {
        wp_send_json(array('success' => false, 'message' => __('reCAPTCHA score too low. Please try again.', 'cbseo')));
        return;
    }

    $post_id = intval($_POST['post_id']);
    if (get_post_type($post_id) !== 'provider') {
        wp_send_json(array('success' => false, 'message' => __('Invalid provider.', 'cbseo')));
        return;
    }

    $user = wp_get_current_user();
    $existing_reviews = get_post_meta($post_id, '_cbseo_rating', true);
    $existing_reviews = $existing_reviews ? json_decode($existing_reviews, true) : [];
    foreach ($existing_reviews as $review) {
        if ($review['name'] === $user->display_name) {
            wp_send_json(array('success' => false, 'message' => __('You have already submitted a review for this provider.', 'cbseo')));
            return;
        }
    }

    $review = array(
        'name' => sanitize_text_field($user->display_name),
        'location' => sanitize_text_field($_POST['review_location']),
        'duration' => sanitize_text_field($_POST['review_duration']),
        'comment' => sanitize_text_field($_POST['review_comment']),
        'ratings' => implode(',', array_map('intval', array(
            sanitize_text_field($_POST['rating_house']),
            sanitize_text_field($_POST['rating_pet']),
            sanitize_text_field($_POST['rating_communication'])
        ))),
    );

    $existing_reviews[] = $review;
    update_post_meta($post_id, '_cbseo_rating', wp_json_encode($existing_reviews));

    wp_send_json(array('success' => true, 'message' => __('Review submitted successfully!', 'cbseo')));
}


add_filter('rewrite_rules_array', 'cbseo_custom_rewrite_rules');
function cbseo_custom_rewrite_rules($rules) {
    $new_rules = array();
    // Add rule for cat_boarding pagination
    $new_rules['cat-boarding/([^/]+)/?$'] = 'index.php?post_type=cat_boarding&name=$matches[1]&cbseo_page=$matches[2]';
    $new_rules['cat-boarding/([^/]+)/page/([0-9]+)/?$'] = 'index.php?post_type=cat_boarding&name=$matches[1]&cbseo_page=$matches[2]';
    return $new_rules + $rules;
}

add_action('wp_loaded', function() {
    flush_rewrite_rules();
    global $wp_rewrite;
    error_log('CBSEO Rewrite Rules: ' . print_r($wp_rewrite->rules, true));
});

?>



