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
                $image = get_the_post_thumbnail_url( $id, 'large' );

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
