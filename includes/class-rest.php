<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class CL_REST {
    public static function init() {
        add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
    }

    public static function register_routes() {
        register_rest_route( 'configurator/v1', '/token/(?P<token>[a-z0-9]+)', [
            'methods' => 'GET',
            'callback' => function( $request ) {
                $token = sanitize_text_field( $request['token'] );
                return rest_ensure_response( [ 'token' => $token, 'valid' => false ] );
            },
            'permission_callback' => '__return_true',
        ] );
    }
}
