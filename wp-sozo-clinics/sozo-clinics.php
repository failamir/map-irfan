<?php
/**
 * Plugin Name: Sozo Clinics
 * Description: Registers Clinic CPT, Region taxonomy, custom fields, and REST endpoints for clinics and regions (with clinic counts).
 * Version: 1.0.0
 * Author: Windsurf
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Register Custom Post Type: clinic
function sozo_register_clinic_cpt() {
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
        'supports' => [ 'title', 'editor', 'thumbnail' ],
        'menu_icon' => 'dashicons-location-alt',
    ];

    register_post_type( 'clinic', $args );
}
add_action( 'init', 'sozo_register_clinic_cpt' );

// Register Taxonomy: clinic_region
function sozo_register_clinic_region_taxonomy() {
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

    register_taxonomy( 'clinic_region', [ 'clinic' ], $args );
}
add_action( 'init', 'sozo_register_clinic_region_taxonomy' );

// Register post meta for clinics
function sozo_register_clinic_meta() {
    $meta_keys = [
        'address' => [ 'type' => 'string', 'single' => true ],
        'city'    => [ 'type' => 'string', 'single' => true ],
        'phone'   => [ 'type' => 'string', 'single' => true ],
        'lat'     => [ 'type' => 'number', 'single' => true ],
        'lng'     => [ 'type' => 'number', 'single' => true ],
        'services'=> [ 'type' => 'string', 'single' => true ], // Comma-separated list; will be split in API
        'rating'  => [ 'type' => 'number', 'single' => true ],
        'image_url' => [ 'type' => 'string', 'single' => true ], // Optional external image URL
    ];

    foreach ( $meta_keys as $key => $args ) {
        register_post_meta( 'clinic', $key, array_merge( $args, [
            'show_in_rest' => true,
            'sanitize_callback' => null,
            'auth_callback' => function() { return current_user_can( 'edit_posts' ); },
        ] ) );
    }
}
add_action( 'init', 'sozo_register_clinic_meta' );

// Activation/Deactivation hooks to flush rewrite rules and ensure CPT is registered
function sozo_clinics_activate() {
    // Register CPT and taxonomy before flushing, so their rules are present
    sozo_register_clinic_cpt();
    sozo_register_clinic_region_taxonomy();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'sozo_clinics_activate' );

function sozo_clinics_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'sozo_clinics_deactivate' );

// Utility to get first region slug for a clinic
function sozo_get_clinic_region_slug( $post_id ) {
    $terms = wp_get_post_terms( $post_id, 'clinic_region' );
    if ( is_wp_error( $terms ) || empty( $terms ) ) {
        return '';
    }
    return $terms[0]->slug;
}

// REST: /sozo/v1/clinics
function sozo_register_clinics_route() {
    register_rest_route( 'sozo/v1', '/clinics', [
        'methods'  => 'GET',
        'permission_callback' => '__return_true',
        'callback' => function( WP_REST_Request $req ) {
            $posts = get_posts( [
                'post_type' => 'clinic',
                'numberposts' => -1,
                'post_status' => 'publish',
            ] );

            $data = [];
            foreach ( $posts as $p ) {
                $id = $p->ID;
                $services_raw = get_post_meta( $id, 'services', true );
                $services = is_string( $services_raw ) && $services_raw !== '' ? array_values( array_filter( array_map( 'trim', explode( ',', $services_raw ) ) ) ) : [];

                $lat = (float) get_post_meta( $id, 'lat', true );
                $lng = (float) get_post_meta( $id, 'lng', true );
                $image_meta = trim( (string) get_post_meta( $id, 'image_url', true ) );
                $image = $image_meta !== '' ? $image_meta : get_the_post_thumbnail_url( $id, 'large' );

                $item = [
                    'id'      => (string) $id,
                    'name'    => get_the_title( $id ),
                    'address' => (string) get_post_meta( $id, 'address', true ),
                    'city'    => (string) get_post_meta( $id, 'city', true ),
                    'region'  => sozo_get_clinic_region_slug( $id ),
                    'phone'   => (string) get_post_meta( $id, 'phone', true ),
                    'coordinates' => [ $lat, $lng ],
                    'services'=> $services,
                    'rating'  => (float) get_post_meta( $id, 'rating', true ),
                    'image'   => $image ? $image : '',
                ];
                $data[] = $item;
            }
            return rest_ensure_response( $data );
        }
    ] );
}
add_action( 'rest_api_init', 'sozo_register_clinics_route' );

// Admin Meta Box: Clinic Details
function sozo_add_clinic_meta_box() {
    add_meta_box(
        'sozo_clinic_details',
        __( 'Clinic Details', 'sozo-clinics' ),
        'sozo_render_clinic_meta_box',
        'clinic',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'sozo_add_clinic_meta_box' );

function sozo_render_clinic_meta_box( $post ) {
    wp_nonce_field( 'sozo_save_clinic_meta', 'sozo_clinic_meta_nonce' );

    $address   = get_post_meta( $post->ID, 'address', true );
    $city      = get_post_meta( $post->ID, 'city', true );
    $phone     = get_post_meta( $post->ID, 'phone', true );
    $lat       = get_post_meta( $post->ID, 'lat', true );
    $lng       = get_post_meta( $post->ID, 'lng', true );
    $services  = get_post_meta( $post->ID, 'services', true );
    $rating    = get_post_meta( $post->ID, 'rating', true );
    $image_url = get_post_meta( $post->ID, 'image_url', true );

    echo '<p><label for="sozo_address"><strong>' . esc_html__( 'Address', 'sozo-clinics' ) . '</strong></label><br />';
    echo '<input type="text" id="sozo_address" name="sozo_address" class="widefat" value="' . esc_attr( $address ) . '" /></p>';

    echo '<p><label for="sozo_city"><strong>' . esc_html__( 'City', 'sozo-clinics' ) . '</strong></label><br />';
    echo '<input type="text" id="sozo_city" name="sozo_city" class="widefat" value="' . esc_attr( $city ) . '" /></p>';

    echo '<p><label for="sozo_phone"><strong>' . esc_html__( 'Phone', 'sozo-clinics' ) . '</strong></label><br />';
    echo '<input type="text" id="sozo_phone" name="sozo_phone" class="widefat" value="' . esc_attr( $phone ) . '" /></p>';

    echo '<div style="display:flex; gap:12px;">';
    echo '<p style="flex:1;"><label for="sozo_lat"><strong>' . esc_html__( 'Latitude', 'sozo-clinics' ) . '</strong></label><br />';
    echo '<input type="number" step="any" id="sozo_lat" name="sozo_lat" class="widefat" value="' . esc_attr( $lat ) . '" /></p>';
    echo '<p style="flex:1;"><label for="sozo_lng"><strong>' . esc_html__( 'Longitude', 'sozo-clinics' ) . '</strong></label><br />';
    echo '<input type="number" step="any" id="sozo_lng" name="sozo_lng" class="widefat" value="' . esc_attr( $lng ) . '" /></p>';
    echo '</div>';

    echo '<p><label for="sozo_services"><strong>' . esc_html__( 'Services (comma separated)', 'sozo-clinics' ) . '</strong></label><br />';
    echo '<input type="text" id="sozo_services" name="sozo_services" class="widefat" value="' . esc_attr( $services ) . '" placeholder="Perawatan Kulit, Konsultasi Dermatologi, Laser Treatment" /></p>';

    echo '<p><label for="sozo_rating"><strong>' . esc_html__( 'Rating (0-5)', 'sozo-clinics' ) . '</strong></label><br />';
    echo '<input type="number" min="0" max="5" step="0.1" id="sozo_rating" name="sozo_rating" class="widefat" value="' . esc_attr( $rating ) . '" /></p>';

    echo '<p><label for="sozo_image_url"><strong>' . esc_html__( 'External Image URL (optional)', 'sozo-clinics' ) . '</strong></label><br />';
    echo '<input type="url" id="sozo_image_url" name="sozo_image_url" class="widefat" value="' . esc_attr( $image_url ) . '" placeholder="https://..." />';
    echo '<em>' . esc_html__( 'If provided, this URL will be used in the API instead of the Featured Image.', 'sozo-clinics' ) . '</em></p>';

    echo '<p><strong>' . esc_html__( 'Region', 'sozo-clinics' ) . ':</strong> ' . esc_html__( 'Use the "Clinic Regions" box in the sidebar to assign a region (e.g., jawa).', 'sozo-clinics' ) . '</p>';
    echo '<p><strong>' . esc_html__( 'Image', 'sozo-clinics' ) . ':</strong> ' . esc_html__( 'You can also set a Featured Image which will be used if External Image URL is empty.', 'sozo-clinics' ) . '</p>';
}

function sozo_save_clinic_meta( $post_id ) {
    // Verify nonce
    if ( ! isset( $_POST['sozo_clinic_meta_nonce'] ) || ! wp_verify_nonce( $_POST['sozo_clinic_meta_nonce'], 'sozo_save_clinic_meta' ) ) {
        return;
    }

    // Autosave?
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // Check permissions
    if ( isset( $_POST['post_type'] ) && 'clinic' === $_POST['post_type'] ) {
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
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
    ];

    foreach ( $map as $posted => $meta_key ) {
        if ( isset( $_POST[ $posted ] ) ) {
            $value = $_POST[ $posted ];
            switch ( $meta_key ) {
                case 'lat':
                case 'lng':
                    $value = is_numeric( $value ) ? (float) $value : '';
                    break;
                case 'rating':
                    $value = is_numeric( $value ) ? max( 0, min( 5, (float) $value ) ) : '';
                    break;
                case 'image_url':
                    $value = esc_url_raw( trim( $value ) );
                    break;
                case 'services':
                    // Normalize services: split by comma, trim, re-join
                    $parts = array_filter( array_map( 'trim', explode( ',', (string) $value ) ) );
                    $value = implode( ', ', $parts );
                    break;
                default:
                    $value = sanitize_text_field( $value );
            }
            if ( $value === '' ) {
                delete_post_meta( $post_id, $meta_key );
            } else {
                update_post_meta( $post_id, $meta_key, $value );
            }
        }
    }
}
add_action( 'save_post', 'sozo_save_clinic_meta' );

// REST: /sozo/v1/regions (with clinic counts and city counts)
function sozo_register_regions_route() {
    register_rest_route( 'sozo/v1', '/regions', [
        'methods'  => 'GET',
        'permission_callback' => '__return_true',
        'callback' => function( WP_REST_Request $req ) {
            $terms = get_terms( [
                'taxonomy' => 'clinic_region',
                'hide_empty' => false,
            ] );

            if ( is_wp_error( $terms ) ) {
                return rest_ensure_response( [] );
            }

            $regions = [];
            $all_cities = [];
            $total_clinics = 0;

            foreach ( $terms as $term ) {
                $posts = get_posts( [
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
                ] );

                $clinic_count = is_array( $posts ) ? count( $posts ) : 0;
                $total_clinics += $clinic_count;

                $cities = [];
                foreach ( $posts as $pid ) {
                    $city = trim( (string) get_post_meta( $pid, 'city', true ) );
                    if ( $city !== '' ) {
                        if ( ! isset( $cities[ $city ] ) ) {
                            $cities[ $city ] = 0;
                        }
                        $cities[ $city ]++;

                        if ( ! isset( $all_cities[ $city ] ) ) {
                            $all_cities[ $city ] = 0;
                        }
                        $all_cities[ $city ]++;
                    }
                }

                $cities_with_counts = [];
                foreach ( $cities as $city_name => $count ) {
                    $cities_with_counts[] = [ 'name' => $city_name, 'count' => (int) $count ];
                }
                // sort cities alphabetically
                usort( $cities_with_counts, function( $a, $b ) { return strcasecmp( $a['name'], $b['name'] ); } );

                $regions[] = [
                    'id' => $term->slug,
                    'name' => $term->name,
                    'clinic_count' => (int) $clinic_count,
                    'cities' => $cities_with_counts,
                ];
            }

            // Sort regions alphabetically by name (optional)
            usort( $regions, function( $a, $b ) { return strcasecmp( $a['name'], $b['name'] ); } );

            // Build ALL region entry
            $all_cities_list = [];
            foreach ( $all_cities as $city_name => $count ) {
                $all_cities_list[] = [ 'name' => $city_name, 'count' => (int) $count ];
            }
            usort( $all_cities_list, function( $a, $b ) { return strcasecmp( $a['name'], $b['name'] ); } );

            array_unshift( $regions, [
                'id' => 'all',
                'name' => 'Semua Cabang',
                'clinic_count' => (int) $total_clinics,
                'cities' => $all_cities_list,
            ] );

            return rest_ensure_response( $regions );
        }
    ] );
}
add_action( 'rest_api_init', 'sozo_register_regions_route' );

// CORS for REST API (allow all origins). Adjust if you need stricter rules.
function sozo_rest_cors_allow_all( $value ) {
    return true;
}
add_filter( 'rest_enabled', 'sozo_rest_cors_allow_all' );
add_filter( 'rest_jsonp_enabled', 'sozo_rest_cors_allow_all' );

function sozo_add_cors_headers() {
    $origin = get_http_origin();
    if ( $origin ) {
        header( 'Access-Control-Allow-Origin: ' . esc_url_raw( $origin ) );
    } else {
        header( 'Access-Control-Allow-Origin: *' );
    }
    header( 'Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS' );
    header( 'Access-Control-Allow-Credentials: true' );
    header( 'Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce' );
}
add_action( 'rest_api_init', function() {
    add_filter( 'rest_pre_serve_request', function( $value ) {
        sozo_add_cors_headers();
        return $value;
    } );
}, 15 );

// Handle OPTIONS requests quickly
add_action( 'init', function() {
    if ( 'OPTIONS' === $_SERVER['REQUEST_METHOD'] ) {
        sozo_add_cors_headers();
        status_header( 200 );
        exit;
    }
} );
