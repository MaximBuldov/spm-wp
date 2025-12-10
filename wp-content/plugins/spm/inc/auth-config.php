<?php
add_action('rest_api_init', function () {
    remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
    add_filter('rest_pre_serve_request', 'spm_rest_cors', 15, 4);
});

function spm_rest_cors( $served, $result, $request, $server ) {
    $allowed_origins = [
				'http://localhost:3000',
				'https://w.smartpeoplemoving.com',
				'https://smartpeoplemoving.com'
		];

    $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

    if (in_array($origin, $allowed_origins, true)) {
        header("Access-Control-Allow-Origin: {$origin}");
        header('Access-Control-Allow-Credentials: true');
        header('Vary: Origin');
    }

    header('Access-Control-Allow-Headers: Authorization, Content-Type');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');

    return $served;
}

add_filter('rest_authentication_errors', function ($result) {
    if (!empty($result)) {
        return $result;
    }

    if (is_user_logged_in()) {
        return $result;
    }

    $allowed = ['/spm/v1/login', '/spm/v3/login', 'spm/v2/login'];

    $request_uri = $_SERVER['REQUEST_URI'] ?? '';

    foreach ($allowed as $route) {
        if (stripos($request_uri, $route) !== false) {
            return $result;
        }
    }

    return new WP_Error(
        'rest_forbidden',
        'REST API is restricted.',
        ['status' => 401]
    );
});