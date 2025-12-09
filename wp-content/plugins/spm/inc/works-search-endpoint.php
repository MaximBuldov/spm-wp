<?php
/**
 * Plugin Name: Works Search Endpoint
 * Version: 1.0.0
 * Author: Maksim Buldau
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Works_Search_Endpoint {

    const POST_TYPE   = 'works';
    const NAMESPACE   = 'my/v1';
    const ROUTE       = 'works';

    const META_NAME  = 'customer_info_customer_name';
    const META_PHONE = 'customer_info_customer_phone';
    const META_EMAIL = 'customer_info_customer_email';

    public static function init() {
        add_action( 'rest_api_init', [ __CLASS__, 'register_route' ] );
    }

    public static function register_route() {
        register_rest_route(
            self::NAMESPACE,
            '/' . self::ROUTE,
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ __CLASS__, 'handle_search' ],
                'permission_callback' => [ __CLASS__, 'permission_check' ],
                'args'                => [
                    'search'   => [
                        'description' => 'Search term for ID, customer_info (name, phone, email)',
                        'type'        => 'string',
                        'required'    => false,
                    ],
                    'page'     => [
                        'description' => 'Page number',
                        'type'        => 'integer',
                        'required'    => false,
                        'default'     => 1,
                    ],
                    'per_page' => [
                        'description' => 'Items per page',
                        'type'        => 'integer',
                        'required'    => false,
                        'default'     => 12,
                    ],
                    '_fields' => [
                        'description' => 'Limit response to specific fields. Comma-separated, e.g. id,title,acf.customer_info',
                        'type'        => 'string',
                        'required'    => false,
                    ],
                ],
            ]
        );
    }

    public static function permission_check() {
        return current_user_can( 'edit_posts' );
    }

    public static function handle_search( WP_REST_Request $request ) {
        $search   = trim( (string) $request->get_param( 'search' ) );
        $page     = max( 1, (int) $request->get_param( 'page' ) );
        $per_page = min( 100, max( 1, (int) $request->get_param( 'per_page' ) ) );

        $args = [
            'post_type'      => self::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => $per_page,
            'paged'          => $page,
            'fields'         => 'ids',
        ];

        if ( $search !== '' ) {
            $args['meta_query'] = [
                'relation' => 'OR',
                [
                    'key'     => self::META_NAME,
                    'value'   => $search,
                    'compare' => 'LIKE',
                ],
                [
                    'key'     => self::META_PHONE,
                    'value'   => $search,
                    'compare' => 'LIKE',
                ],
                [
                    'key'     => self::META_EMAIL,
                    'value'   => $search,
                    'compare' => 'LIKE',
                ],
            ];
        }
        $id_post = null;
        if ( $search !== '' && ctype_digit( $search ) ) {
            $maybe_id = (int) $search;
            if ( $maybe_id > 0 ) {
                $post = get_post( $maybe_id );
                if ( $post && $post->post_type === self::POST_TYPE && $post->post_status === 'publish' ) {
                    $id_post = $post->ID;
                }
            }
        }

        $query = new WP_Query( $args );
        $ids = [];

        if ( $id_post ) {
            $ids[ $id_post ] = true;
        }

        foreach ( $query->posts as $post_id ) {
            $ids[ $post_id ] = true;
        }


        $ids = array_keys( $ids );
        $result = [];
        foreach ( $ids as $post_id ) {
            $post_obj   = get_post( $post_id );
            $controller = new WP_REST_Posts_Controller( self::POST_TYPE );

            $response = $controller->prepare_item_for_response( $post_obj, $request );
            $data     = $response->get_data();

            if ( function_exists( 'get_fields' ) ) {
                $data['acf'] = get_fields( $post_id );
            }

            $result[] = $data;
        }

        $response = new WP_REST_Response( $result );
        $response->header( 'X-WP-Total', (int)$query->found_posts );
        $response->header( 'X-WP-TotalPages', (int)$query->max_num_pages );

        if ( function_exists( 'rest_filter_response_fields' ) ) {
            $fields_param = $request->get_param('_fields');
            if ( $fields_param ) {
                $response = rest_filter_response_fields( $response, $request, $fields_param );
            }
        }

        return $response;
    }
}

Works_Search_Endpoint::init();