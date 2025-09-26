<?php

/**
 * Plugin Name: Sozo Clinics Manager by TreonStudio
 * Description: Registers Clinic CPT, Region taxonomy, custom fields, and REST endpoints for clinics and regions (with clinic counts).
 * Version: 1.0.0
 * Author: Fail Amir & Irfan Chan
 */

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Ultra-early suppression for REST requests to prevent deprecated/warnings leaking into JSON
if ((defined('REST_REQUEST') && REST_REQUEST) || (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/wp-json/') !== false)) {
    if (function_exists('ini_set')) {
        @ini_set('display_errors', '0');
    }
    @error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_NOTICE & ~E_USER_NOTICE & ~E_WARNING & ~E_USER_WARNING);
    if (!ob_get_level()) {
        @ob_start();
    }
}

// Register Custom Post Type: clinic
function sozo_register_clinic_cpt()
{
    $labels = [
        'name' => 'Clinics',
        'singular_name' => 'Clinic',
        'menu_name' => 'Clinics',
    ];

    $args = [
        'labels' => $labels,
        'public' => true,
        'has_archive' => false,
        'show_in_rest' => true,
        'supports' => ['title', 'editor', 'thumbnail'],
        'menu_icon' => 'dashicons-location-alt',
    ];

    register_post_type('clinic', $args);
}
add_action('init', 'sozo_register_clinic_cpt');

// Register Taxonomy: clinic_region
function sozo_register_clinic_region_taxonomy()
{
    $labels = [
        'name' => 'Clinic Regions',
        'singular_name' => 'Clinic Region',
    ];

    $args = [
        'labels' => $labels,
        'public' => true,
        'hierarchical' => true,
        'show_in_rest' => true,
    ];

    register_taxonomy('clinic_region', ['clinic'], $args);
}
add_action('init', 'sozo_register_clinic_region_taxonomy');

// Register post meta for clinics
function sozo_register_clinic_meta()
{
    $meta_keys = [
        'address' => ['type' => 'string', 'single' => true],
        'city'    => ['type' => 'string', 'single' => true],
        'phone'   => ['type' => 'string', 'single' => true],
        'lat'     => ['type' => 'number', 'single' => true],
        'lng'     => ['type' => 'number', 'single' => true],
        'services' => ['type' => 'string', 'single' => true], // Comma-separated list; will be split in API
        'rating'  => ['type' => 'number', 'single' => true],
        'image_url' => ['type' => 'string', 'single' => true], // Optional external image URL
        'maps_url' => ['type' => 'string', 'single' => true],  // Optional manual Google Maps URL
    ];

    foreach ($meta_keys as $key => $args) {
        register_post_meta('clinic', $key, array_merge($args, [
            'show_in_rest' => true,
            'sanitize_callback' => null,
            'auth_callback' => function () {
                return current_user_can('edit_posts');
            },
        ]));
    }
}
add_action('init', 'sozo_register_clinic_meta');

// Activation/Deactivation hooks to flush rewrite rules and ensure CPT is registered
function sozo_clinics_activate()
{
    // Register CPT and taxonomy before flushing, so their rules are present
    sozo_register_clinic_cpt();
    sozo_register_clinic_region_taxonomy();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'sozo_clinics_activate');

function sozo_clinics_deactivate()
{
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'sozo_clinics_deactivate');

// Utility to get first region slug for a clinic
function sozo_get_clinic_region_slug($post_id)
{
    $terms = wp_get_post_terms($post_id, 'clinic_region');
    if (is_wp_error($terms) || empty($terms)) {
        return '';
    }
    return $terms[0]->slug;
}

// REST: /sozo/v1/clinics
function sozo_register_clinics_route()
{
    register_rest_route('sozo/v1', '/clinics', [
        'methods'  => 'GET',
        'permission_callback' => '__return_true',
        'callback' => function (WP_REST_Request $req) {
            $posts = get_posts([
                'post_type' => 'clinic',
                'numberposts' => -1,
                'post_status' => 'publish',
            ]);

            $data = [];
            foreach ($posts as $p) {
                $id = $p->ID;
                $services_raw = get_post_meta($id, 'services', true);
                $services = is_string($services_raw) && $services_raw !== '' ? array_values(array_filter(array_map('trim', explode(',', $services_raw)))) : [];

                $lat = (float) get_post_meta($id, 'lat', true);
                $lng = (float) get_post_meta($id, 'lng', true);
                $image_meta = trim((string) get_post_meta($id, 'image_url', true));
                $image = $image_meta !== '' ? $image_meta : get_the_post_thumbnail_url($id, 'large');
                $maps_meta = trim((string) get_post_meta($id, 'maps_url', true));

                $item = [
                    'id'      => (string) $id,
                    'name'    => get_the_title($id),
                    'address' => (string) get_post_meta($id, 'address', true),
                    'city'    => (string) get_post_meta($id, 'city', true),
                    'region'  => sozo_get_clinic_region_slug($id),
                    'phone'   => (string) get_post_meta($id, 'phone', true),
                    'coordinates' => [$lat, $lng],
                    'services' => $services,
                    'rating'  => (float) get_post_meta($id, 'rating', true),
                    'image'   => $image ? $image : '',
                    'maps'    => $maps_meta !== '' ? $maps_meta : '',
                ];
                $data[] = $item;
            }
            return rest_ensure_response($data);
        }
    ]);
}
add_action('rest_api_init', 'sozo_register_clinics_route');

// Admin Meta Box: Clinic Details
function sozo_add_clinic_meta_box()
{
    add_meta_box(
        'sozo_clinic_details',
        __('Clinic Details', 'sozo-clinics'),
        'sozo_render_clinic_meta_box',
        'clinic',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'sozo_add_clinic_meta_box');

function sozo_render_clinic_meta_box($post)
{
    wp_nonce_field('sozo_save_clinic_meta', 'sozo_clinic_meta_nonce');

    $address   = get_post_meta($post->ID, 'address', true);
    $city      = get_post_meta($post->ID, 'city', true);
    $phone     = get_post_meta($post->ID, 'phone', true);
    $lat       = get_post_meta($post->ID, 'lat', true);
    $lng       = get_post_meta($post->ID, 'lng', true);
    $services  = get_post_meta($post->ID, 'services', true);
    $rating    = get_post_meta($post->ID, 'rating', true);
    $image_url = get_post_meta($post->ID, 'image_url', true);
    $maps_url  = get_post_meta($post->ID, 'maps_url', true);

    echo '<p><label for="sozo_address"><strong>' . esc_html__('Address', 'sozo-clinics') . '</strong></label><br />';
    echo '<input type="text" id="sozo_address" name="sozo_address" class="widefat" value="' . esc_attr($address) . '" /></p>';

    echo '<p><label for="sozo_city"><strong>' . esc_html__('City', 'sozo-clinics') . '</strong></label><br />';
    echo '<input type="text" id="sozo_city" name="sozo_city" class="widefat" value="' . esc_attr($city) . '" /></p>';

    echo '<p><label for="sozo_phone"><strong>' . esc_html__('Phone', 'sozo-clinics') . '</strong></label><br />';
    echo '<input type="text" id="sozo_phone" name="sozo_phone" class="widefat" value="' . esc_attr($phone) . '" /></p>';

    echo '<div style="display:flex; gap:12px;">';
    echo '<p style="flex:1;"><label for="sozo_lat"><strong>' . esc_html__('Latitude', 'sozo-clinics') . '</strong></label><br />';
    echo '<input type="number" step="any" id="sozo_lat" name="sozo_lat" class="widefat" value="' . esc_attr($lat) . '" /></p>';
    echo '<p style="flex:1;"><label for="sozo_lng"><strong>' . esc_html__('Longitude', 'sozo-clinics') . '</strong></label><br />';
    echo '<input type="number" step="any" id="sozo_lng" name="sozo_lng" class="widefat" value="' . esc_attr($lng) . '" /></p>';
    echo '</div>';

    echo '<p><label for="sozo_services"><strong>' . esc_html__('Services (comma separated)', 'sozo-clinics') . '</strong></label><br />';
    echo '<input type="text" id="sozo_services" name="sozo_services" class="widefat" value="' . esc_attr($services) . '" placeholder="Perawatan Kulit, Konsultasi Dermatologi, Laser Treatment" /></p>';

    echo '<p><label for="sozo_rating"><strong>' . esc_html__('Rating (0-5)', 'sozo-clinics') . '</strong></label><br />';
    echo '<input type="number" min="0" max="5" step="0.1" id="sozo_rating" name="sozo_rating" class="widefat" value="' . esc_attr($rating) . '" /></p>';

    echo '<p><label for="sozo_image_url"><strong>' . esc_html__('External Image URL (optional)', 'sozo-clinics') . '</strong></label><br />';
    echo '<input type="url" id="sozo_image_url" name="sozo_image_url" class="widefat" value="' . esc_attr($image_url) . '" placeholder="https://..." />';
    echo '<em>' . esc_html__('If provided, this URL will be used in the API instead of the Featured Image.', 'sozo-clinics') . '</em></p>';

    echo '<p><label for="sozo_maps_url"><strong>' . esc_html__('Google Maps URL (optional)', 'sozo-clinics') . '</strong></label><br />';
    echo '<input type="url" id="sozo_maps_url" name="sozo_maps_url" class="widefat" value="' . esc_attr($maps_url) . '" placeholder="https://maps.google.com/..." />';
    echo '<em>' . esc_html__('If provided, this URL will be exposed as maps in the REST API. If empty, the frontend may build a maps link from coordinates or address.', 'sozo-clinics') . '</em></p>';

    echo '<p><strong>' . esc_html__('Region', 'sozo-clinics') . ':</strong> ' . esc_html__('Use the "Clinic Regions" box in the sidebar to assign a region (e.g., jawa).', 'sozo-clinics') . '</p>';
    echo '<p><strong>' . esc_html__('Image', 'sozo-clinics') . ':</strong> ' . esc_html__('You can also set a Featured Image which will be used if External Image URL is empty.', 'sozo-clinics') . '</p>';
}

function sozo_save_clinic_meta($post_id)
{
    // Verify nonce
    if (! isset($_POST['sozo_clinic_meta_nonce']) || ! wp_verify_nonce($_POST['sozo_clinic_meta_nonce'], 'sozo_save_clinic_meta')) {
        return;
    }

    // Autosave?
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Check permissions
    if (isset($_POST['post_type']) && 'clinic' === $_POST['post_type']) {
        if (! current_user_can('edit_post', $post_id)) {
            return;
        }
    } else {
        return;
    }

    // Sanitize and save fields
    $map = [
        'sozo_address'   => 'address',
        'sozo_city'      => 'city',
        'sozo_phone'     => 'phone',
        'sozo_lat'       => 'lat',
        'sozo_lng'       => 'lng',
        'sozo_services'  => 'services',
        'sozo_rating'    => 'rating',
        'sozo_image_url' => 'image_url',
        'sozo_maps_url'  => 'maps_url',
    ];

    foreach ($map as $posted => $meta_key) {
        if (isset($_POST[$posted])) {
            $value = $_POST[$posted];
            switch ($meta_key) {
                case 'lat':
                case 'lng':
                    $value = is_numeric($value) ? (float) $value : '';
                    break;
                case 'rating':
                    $value = is_numeric($value) ? max(0, min(5, (float) $value)) : '';
                    break;
                case 'image_url':
                    $value = esc_url_raw(trim($value));
                    break;
                case 'maps_url':
                    $value = esc_url_raw(trim($value));
                    break;
                case 'services':
                    // Normalize services: split by comma, trim, re-join
                    $parts = array_filter(array_map('trim', explode(',', (string) $value)));
                    $value = implode(', ', $parts);
                    break;
                default:
                    $value = sanitize_text_field($value);
            }
            if ($value === '') {
                delete_post_meta($post_id, $meta_key);
            } else {
                update_post_meta($post_id, $meta_key, $value);
            }
        }
    }
}
add_action('save_post', 'sozo_save_clinic_meta');

// ==========================
// Admin: CSV Import for Clinics
// ==========================
function sozo_register_import_submenu()
{
    add_submenu_page(
        'edit.php?post_type=clinic',
        __('Import Clinics (CSV)', 'sozo-clinics'),
        __('Import (CSV)', 'sozo-clinics'),
        'edit_posts',
        'sozo-import',
        'sozo_render_import_page'
    );
}
add_action('admin_menu', 'sozo_register_import_submenu');

function sozo_render_import_page()
{
    if (! current_user_can('edit_posts')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'sozo-clinics'));
    }

    $created = isset($_GET['created']) ? (int) $_GET['created'] : 0;
    $updated = isset($_GET['updated']) ? (int) $_GET['updated'] : 0;
    $errors  = isset($_GET['errors'])  ? (int) $_GET['errors']  : 0;

    echo '<div class="wrap">';
    echo '<h1>' . esc_html__('Import Clinics from CSV', 'sozo-clinics') . '</h1>';

    if (isset($_GET['import_result'])) {
        echo '<div class="notice notice-success"><p>' . sprintf(
            esc_html__('Import completed. Created: %d, Updated: %d, Errors: %d', 'sozo-clinics'),
            (int) $created,
            (int) $updated,
            (int) $errors
        ) . '</p></div>';
    }

    echo '<p>' . esc_html__('Upload a CSV file with the following headers:', 'sozo-clinics') . '</p>';
    echo '<pre style="background:#fff;border:1px solid #ccd0d4;padding:10px;">id,name,address,city,region,phone,lat,lng,services,rating,image_url,maps_url</pre>';
    echo '<p><a class="button" href="' . esc_url( admin_url( 'admin-post.php?action=sozo_download_csv_template' ) ) . '">' . esc_html__('Download Template CSV', 'sozo-clinics') . '</a></p>';
    echo '<p><em>' . esc_html__('Notes:', 'sozo-clinics') . '</em></p>';
    echo '<ul class="ul-disc">';
    echo '<li>' . esc_html__('id (optional): if provided and exists, that clinic will be updated. Otherwise, import will try to match by name.', 'sozo-clinics') . '</li>';
    echo '<li>' . esc_html__('name (required): the clinic title.', 'sozo-clinics') . '</li>';
    echo '<li>' . esc_html__('region: taxonomy term (slug or name). Will be created if not found.', 'sozo-clinics') . '</li>';
    echo '<li>' . esc_html__('services: comma-separated list.', 'sozo-clinics') . '</li>';
    echo '<li>' . esc_html__('image_url: direct image URL. If empty, you can set a Featured Image manually later.', 'sozo-clinics') . '</li>';
    echo '</ul>';

    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" enctype="multipart/form-data">';
    wp_nonce_field('sozo_import_csv', 'sozo_import_csv_nonce');
    echo '<input type="hidden" name="action" value="sozo_import_csv" />';
    echo '<table class="form-table"><tbody>';
    echo '<tr><th scope="row">' . esc_html__('CSV File', 'sozo-clinics') . '</th><td>';
    echo '<input type="file" name="csv_file" accept=".csv,text/csv" required />';
    echo '</td></tr>';
    echo '<tr><th scope="row">' . esc_html__('Dry Run (simulate only)', 'sozo-clinics') . '</th><td>';
    echo '<label><input type="checkbox" name="dry_run" value="1" /> ' . esc_html__('Do not write changes, just validate and show summary', 'sozo-clinics') . '</label>';
    echo '</td></tr>';
    echo '<tr><th scope="row">' . esc_html__('Custom Header Mapping (optional)', 'sozo-clinics') . '</th><td>';
    echo '<p class="description">' . esc_html__('If your CSV uses different header names, map them here. Leave blank to use defaults.', 'sozo-clinics') . '</p>';
    $fields = ['id','name','address','city','region','phone','lat','lng','services','rating','image_url','maps_url'];
    echo '<table class="widefat striped" style="max-width:700px;"><thead><tr><th>' . esc_html__('Field', 'sozo-clinics') . '</th><th>' . esc_html__('CSV Header Name', 'sozo-clinics') . '</th></tr></thead><tbody>';
    foreach ($fields as $f) {
        echo '<tr><td><code>' . esc_html($f) . '</code></td><td><input type="text" name="map_' . esc_attr($f) . '" placeholder="' . esc_attr($f) . '" class="regular-text" /></td></tr>';
    }
    echo '</tbody></table>';
    echo '</td></tr>';
    echo '</tbody></table>';
    submit_button(esc_html__('Import', 'sozo-clinics'));
    echo '</form>';
    echo '</div>';
}

add_action('admin_post_sozo_import_csv', 'sozo_handle_csv_import');
function sozo_handle_csv_import()
{
    if (! current_user_can('edit_posts')) {
        wp_die(__('Insufficient permissions.', 'sozo-clinics'));
    }
    if (! isset($_POST['sozo_import_csv_nonce']) || ! wp_verify_nonce($_POST['sozo_import_csv_nonce'], 'sozo_import_csv')) {
        wp_die(__('Invalid request.', 'sozo-clinics'));
    }

    if (! isset($_FILES['csv_file']) || empty($_FILES['csv_file']['tmp_name'])) {
        wp_redirect(add_query_arg(['post_type' => 'clinic', 'page' => 'sozo-import', 'import_result' => 1, 'created' => 0, 'updated' => 0, 'errors' => 1], admin_url('edit.php')));
        exit;
    }

    $file = $_FILES['csv_file']['tmp_name'];
    $fh = fopen($file, 'r');
    if (! $fh) {
        wp_redirect(add_query_arg(['post_type' => 'clinic', 'page' => 'sozo-import', 'import_result' => 1, 'created' => 0, 'updated' => 0, 'errors' => 1], admin_url('edit.php')));
        exit;
    }

    $created = 0; $updated = 0; $errors = 0;
    $is_dry = !empty($_POST['dry_run']);

    // Build custom header mapping (normalize to lowercase underscore)
    $expected = ['id','name','address','city','region','phone','lat','lng','services','rating','image_url','maps_url'];
    $custom_map = [];
    foreach ($expected as $ek) {
        $val = isset($_POST['map_'.$ek]) ? trim((string) $_POST['map_'.$ek]) : '';
        if ($val !== '') {
            $val = strtolower(preg_replace('/\s+/', '_', $val));
            $custom_map[$ek] = $val;
        }
    }
    $headers = fgetcsv($fh);
    if (! $headers) { fclose($fh); $errors++; goto sozo_import_done; }

    // Normalize headers and build index map
    $map = [];
    $headerIndex = [];
    foreach ($headers as $idx => $h) {
        $key = strtolower(trim($h));
        $key = preg_replace('/\s+/', '_', $key);
        $map[$idx] = $key;
        $headerIndex[$key] = $idx;
    }
    // Resolve indices for each expected field using custom mapping if provided
    $fieldIndex = [];
    foreach ($expected as $ek) {
        $target = isset($custom_map[$ek]) ? $custom_map[$ek] : $ek;
        $fieldIndex[$ek] = isset($headerIndex[$target]) ? (int)$headerIndex[$target] : -1;
    }

    while (($row = fgetcsv($fh)) !== false) {
        $item = [];
        foreach ($expected as $ek) {
            $idx = $fieldIndex[$ek];
            $item[$ek] = ($idx >= 0 && isset($row[$idx])) ? trim($row[$idx]) : '';
        }

        // Required: name
        if (empty($item['name'])) { $errors++; continue; }

        $post_id = 0;
        if (! empty($item['id'])) {
            $maybe = absint($item['id']);
            if ($maybe && get_post_type($maybe) === 'clinic') {
                $post_id = $maybe;
            }
        }
        // Fallback: find by exact title
        if (! $post_id) {
            $existing = get_page_by_title(wp_strip_all_tags($item['name']), OBJECT, 'clinic');
            if ($existing) { $post_id = (int) $existing->ID; }
        }

        if ($is_dry) {
            // Simulate create/update
            if ($post_id) { $updated++; } else { $created++; }
            // Skip writes in dry-run
        } else {
            $postarr = [
                'post_type' => 'clinic',
                'post_status' => 'publish',
                'post_title' => sanitize_text_field($item['name']),
            ];
            if ($post_id) {
                $postarr['ID'] = $post_id;
                $post_id = wp_update_post($postarr, true);
                if (is_wp_error($post_id)) { $errors++; continue; } else { $updated++; }
            } else {
                $post_id = wp_insert_post($postarr, true);
                if (is_wp_error($post_id)) { $errors++; continue; } else { $created++; }
            }
        }

        // Meta fields
        $meta_fields = [
            'address' => 'address',
            'city' => 'city',
            'phone' => 'phone',
            'services' => 'services',
            'image_url' => 'image_url',
            'maps_url' => 'maps_url',
        ];
        foreach ($meta_fields as $csv_key => $meta_key) {
            if (isset($item[$csv_key]) && $item[$csv_key] !== '') {
                $val = $item[$csv_key];
                if ($meta_key === 'services') {
                    $parts = array_filter(array_map('trim', explode(',', (string) $val)));
                    $val = implode(', ', $parts);
                } elseif ($meta_key === 'image_url') {
                    $val = esc_url_raw(trim($val));
                } elseif ($meta_key === 'maps_url') {
                    $val = esc_url_raw(trim($val));
                } else {
                    $val = sanitize_text_field($val);
                }
                if (! $is_dry) { update_post_meta($post_id, $meta_key, $val); }
            }
        }
        // Numeric meta
        if (!$is_dry) {
            if (isset($item['lat']) && $item['lat'] !== '') { update_post_meta($post_id, 'lat', (float) $item['lat']); }
            if (isset($item['lng']) && $item['lng'] !== '') { update_post_meta($post_id, 'lng', (float) $item['lng']); }
            if (isset($item['rating']) && $item['rating'] !== '') { update_post_meta($post_id, 'rating', max(0, min(5, (float) $item['rating'])) ); }
        }

        // Taxonomy: clinic_region
        if (isset($item['region']) && $item['region'] !== '') {
            $region_val = trim($item['region']);
            $term = get_term_by('slug', sanitize_title($region_val), 'clinic_region');
            if (! $term) { $term = get_term_by('name', $region_val, 'clinic_region'); }
            if (! $term) {
                if (!$is_dry) {
                    $inserted = wp_insert_term($region_val, 'clinic_region', ['slug' => sanitize_title($region_val)]);
                    if (! is_wp_error($inserted)) {
                        $term = get_term_by('id', (int) $inserted['term_id'], 'clinic_region');
                    }
                }
            }
            if (!$is_dry && $term && ! is_wp_error($term)) {
                wp_set_post_terms($post_id, [ (int) $term->term_id ], 'clinic_region', false);
            }
        }
    }

    fclose($fh);

    sozo_import_done:
    wp_redirect(add_query_arg([
        'post_type' => 'clinic',
        'page' => 'sozo-import',
        'import_result' => 1,
        'created' => $created,
        'updated' => $updated,
        'errors'  => $errors,
        'dry_run' => $is_dry ? 1 : 0,
    ], admin_url('edit.php')));
    exit;
}

// Download template CSV
add_action('admin_post_sozo_download_csv_template', function() {
    if (! current_user_can('edit_posts')) {
        wp_die(__('Insufficient permissions.', 'sozo-clinics'));
    }
    $filename = 'clinics-template.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    $out = fopen('php://output', 'w');
    fputcsv($out, ['id','name','address','city','region','phone','lat','lng','services','rating','image_url','maps_url']);
    fputcsv($out, ['', 'Sozo Skin Clinic Yogyakarta', 'Jl. Pangeran Diponegoro No.58', 'Yogyakarta', 'yogyakarta', '0851...', '-7.783', '110.367', 'Perawatan Kulit, Konsultasi Dermatologi, Laser Treatment', '4.8', 'https://example.com/image.jpg', 'https://maps.google.com/?q=-7.783,110.367']);
    fclose($out);
    exit;
});

// ==========================
// Admin: CSV Export for Clinics
// ==========================
function sozo_register_export_submenu()
{
    add_submenu_page(
        'edit.php?post_type=clinic',
        __('Export Clinics (CSV)', 'sozo-clinics'),
        __('Export (CSV)', 'sozo-clinics'),
        'edit_posts',
        'sozo-export',
        'sozo_render_export_page'
    );
}
add_action('admin_menu', 'sozo_register_export_submenu');

function sozo_render_export_page()
{
    if (! current_user_can('edit_posts')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'sozo-clinics'));
    }
    echo '<div class="wrap">';
    echo '<h1>' . esc_html__('Export Clinics to CSV', 'sozo-clinics') . '</h1>';
    echo '<p>' . esc_html__('Download clinics as CSV. You can filter by region (optional).', 'sozo-clinics') . '</p>';
    $terms = get_terms([
        'taxonomy' => 'clinic_region',
        'hide_empty' => false,
    ]);
    $selected = isset($_GET['region']) ? sanitize_text_field($_GET['region']) : '';
    echo '<form method="get" action="' . esc_url(admin_url('admin-post.php')) . '" class="">';
    echo '<input type="hidden" name="action" value="sozo_export_csv" />';
    echo '<table class="form-table"><tbody>';
    echo '<tr><th scope="row">' . esc_html__('Region', 'sozo-clinics') . '</th><td>';
    echo '<select name="region">';
    echo '<option value="">' . esc_html__('All Regions', 'sozo-clinics') . '</option>';
    if (!is_wp_error($terms)) {
        foreach ($terms as $t) {
            $sel = selected($selected, $t->slug, false);
            echo '<option value="' . esc_attr($t->slug) . '" ' . $sel . '>' . esc_html($t->name) . '</option>';
        }
    }
    echo '</select>';
    echo '</td></tr>';
    echo '</tbody></table>';
    submit_button(esc_html__('Download Clinics CSV', 'sozo-clinics'));
    echo '</form>';
    echo '</div>';
}

add_action('admin_post_sozo_export_csv', function() {
    if (! current_user_can('edit_posts')) {
        wp_die(__('Insufficient permissions.', 'sozo-clinics'));
    }
    $filename = 'clinics-export-' . date('Ymd-His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    $out = fopen('php://output', 'w');
    // Headers
    $headers = ['id','name','address','city','region','phone','lat','lng','services','rating','image_url','maps_url'];
    fputcsv($out, $headers);

    // Fetch all clinics
    $args = [
        'post_type' => 'clinic',
        'numberposts' => -1,
        'post_status' => 'any',
    ];
    $region = isset($_REQUEST['region']) ? sanitize_text_field($_REQUEST['region']) : '';
    if ($region !== '') {
        $args['tax_query'] = [
            [
                'taxonomy' => 'clinic_region',
                'field' => 'slug',
                'terms' => $region,
            ]
        ];
    }
    $posts = get_posts($args);

    foreach ($posts as $p) {
        $id = (int) $p->ID;
        $address = (string) get_post_meta($id, 'address', true);
        $city    = (string) get_post_meta($id, 'city', true);
        $phone   = (string) get_post_meta($id, 'phone', true);
        $lat     = (string) get_post_meta($id, 'lat', true);
        $lng     = (string) get_post_meta($id, 'lng', true);
        $services= (string) get_post_meta($id, 'services', true);
        $rating  = (string) get_post_meta($id, 'rating', true);
        $image   = (string) get_post_meta($id, 'image_url', true);
        $mapsurl = (string) get_post_meta($id, 'maps_url', true);

        // First region slug if available
        $terms = wp_get_post_terms($id, 'clinic_region');
        $region = '';
        if (!is_wp_error($terms) && !empty($terms)) {
            $region = $terms[0]->slug;
        }

        $row = [
            $id,
            get_the_title($id),
            $address,
            $city,
            $region,
            $phone,
            $lat,
            $lng,
            $services,
            $rating,
            $image,
            $mapsurl,
        ];
        fputcsv($out, $row);
    }

    fclose($out);
    exit;
});

// REST: /sozo/v1/regions (with clinic counts and city counts)
function sozo_register_regions_route()
{
    register_rest_route('sozo/v1', '/regions', [
        'methods'  => 'GET',
        'permission_callback' => '__return_true',
        'callback' => function (WP_REST_Request $req) {
            $terms = get_terms([
                'taxonomy' => 'clinic_region',
                'hide_empty' => false,
            ]);

            if (is_wp_error($terms)) {
                return rest_ensure_response([]);
            }

            $regions = [];
            $all_cities = [];
            $total_clinics = 0;

            foreach ($terms as $term) {
                $posts = get_posts([
                    'post_type' => 'clinic',
                    'numberposts' => -1,
                    'post_status' => 'publish',
                    'tax_query' => [
                        [
                            'taxonomy' => 'clinic_region',
                            'field'    => 'slug',
                            'terms'    => $term->slug,
                        ],
                    ],
                    'fields' => 'ids',
                ]);

                $clinic_count = is_array($posts) ? count($posts) : 0;
                $total_clinics += $clinic_count;

                $cities = [];
                foreach ($posts as $pid) {
                    $city = trim((string) get_post_meta($pid, 'city', true));
                    if ($city !== '') {
                        if (! isset($cities[$city])) {
                            $cities[$city] = 0;
                        }
                        $cities[$city]++;

                        if (! isset($all_cities[$city])) {
                            $all_cities[$city] = 0;
                        }
                        $all_cities[$city]++;
                    }
                }

                $cities_with_counts = [];
                foreach ($cities as $city_name => $count) {
                    $cities_with_counts[] = ['name' => $city_name, 'count' => (int) $count];
                }
                // sort cities alphabetically
                usort($cities_with_counts, function ($a, $b) {
                    return strcasecmp($a['name'], $b['name']);
                });

                $regions[] = [
                    'id' => $term->slug,
                    'name' => $term->name,
                    'clinic_count' => (int) $clinic_count,
                    'cities' => $cities_with_counts,
                ];
            }

            // Sort regions alphabetically by name (optional)
            usort($regions, function ($a, $b) {
                return strcasecmp($a['name'], $b['name']);
            });

            // Build ALL region entry
            $all_cities_list = [];
            foreach ($all_cities as $city_name => $count) {
                $all_cities_list[] = ['name' => $city_name, 'count' => (int) $count];
            }
            usort($all_cities_list, function ($a, $b) {
                return strcasecmp($a['name'], $b['name']);
            });

            array_unshift($regions, [
                'id' => 'all',
                'name' => 'Semua Cabang',
                'clinic_count' => (int) $total_clinics,
                'cities' => $all_cities_list,
            ]);

            return rest_ensure_response($regions);
        }
    ]);
}
add_action('rest_api_init', 'sozo_register_regions_route');

// Hard-disable deprecated REST disabling filters if any plugin/theme adds them
add_action('plugins_loaded', function () {
    // Remove any callbacks attached to deprecated filters to avoid notices in output
    remove_all_filters('rest_enabled');
    remove_all_filters('rest_jsonp_enabled');
}, 0);

// Ensure no PHP notices/warnings leak into REST responses by suppressing output early
function sozo_is_rest_request()
{
    return (defined('REST_REQUEST') && REST_REQUEST)
        || (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/wp-json/') !== false);
}

add_action('plugins_loaded', function () {
    if (sozo_is_rest_request()) {
        if (function_exists('ini_set')) {
            @ini_set('display_errors', '0');
        }
        // Suppress deprecated, warnings, and notices from being printed
        @error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_NOTICE & ~E_USER_NOTICE & ~E_WARNING & ~E_USER_WARNING);
        // Start output buffering so we can clean any accidental output before JSON
        if (!ob_get_level()) {
            @ob_start();
        }
    }
}, 0);

function sozo_add_cors_headers()
{
    $origin = get_http_origin();
    if ($origin) {
        header('Access-Control-Allow-Origin: ' . esc_url_raw($origin));
    } else {
        header('Access-Control-Allow-Origin: *');
    }
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce');
}
// Ensure REST responses are clean JSON (no PHP notices) and include CORS headers
add_action('rest_api_init', function () {
    // Make sure PHP notices/warnings are not displayed in REST output
    if (function_exists('ini_set')) {
        @ini_set('display_errors', '0');
    }
    // Before serving response, clear any prior accidental output and add CORS
    add_filter('rest_pre_serve_request', function ($value) {
        if (ob_get_length()) {
            @ob_clean();
        }
        sozo_add_cors_headers();
        return $value;
    }, 0);
}, 0);

// Handle OPTIONS requests quickly
add_action('init', function () {
    if ('OPTIONS' === $_SERVER['REQUEST_METHOD']) {
        sozo_add_cors_headers();
        status_header(200);
        exit;
    }
});
