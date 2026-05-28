<?php
function modify_token_response($data, $user) {
	$site_options = array(
		'price' => intval(get_field('price', 'option')),
		'moverPrice' => intval(get_field('moverPrice', 'option')),
		'nonCashPrice' => intval(get_field('nonCashPrice', 'option')),
		'weekendPrice' => intval(get_field('weekendPrice', 'option')),
		'smallBox' => intval(get_field('smallBox', 'option')),
		'mediumBox' => intval(get_field('mediumBox', 'option')),
		'wrappingPaper' => intval(get_field('wrappingPaper', 'option')),
		'heavyItems' => intval(get_field('heavyItems', 'option')),
		'truckFee' => intval(get_field('truckFee', 'option')),
		'defaultdeposit' => intval(get_field('defaultdeposit', 'option')),
    );

    $users = array_map(
    function ( WP_User $u ) {
        return [
            'id'    => $u->ID,
            'name'  => $u->display_name,
            'email' => $u->user_email,
            'roles' => $u->roles,
            'phone' => get_field('phone', 'user_' . $u->ID),
        ];
    },
    get_users()
	);
	$res = array(
    'token' => $data['token'],
		'user' => array(
			'email' => $user->data->user_email,
			'user_display_name' => $user->data->display_name,
			'user_nicename' => $user->data->user_nicename,
			'id' => $user->ID,
			'role' => get_userdata($user->ID)->roles
		),
		'prices' => $site_options,
        'workers' => $users,
	);

	return $res;
}
add_filter('jwt_auth_token_before_dispatch', 'modify_token_response', 10, 2);

add_filter('jwt_auth_expire', function () {
    return time() + DAY_IN_SECONDS * 7;
});

add_action('rest_api_init', function () {
    register_rest_route('spm/v1', '/renew-token', [
        'methods'             => 'POST',
        'callback'            => 'spm_handle_renew_token',
        'permission_callback' => '__return_true',
    ]);
});

function spm_jwt_verify(string $token): ?object {
    if (!defined('JWT_AUTH_SECRET_KEY')) return null;

    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;

    [$header, $body, $sig] = $parts;
    $expected = rtrim(strtr(base64_encode(hash_hmac('sha256', "$header.$body", JWT_AUTH_SECRET_KEY, true)), '+/', '-_'), '=');

    if (!hash_equals($expected, $sig)) return null;

    $payload = json_decode(base64_decode(strtr($body, '-_', '+/')));
    if (!$payload || (isset($payload->exp) && $payload->exp < time())) return null;

    return $payload;
}

function spm_jwt_encode(array $payload): ?string {
    if (!defined('JWT_AUTH_SECRET_KEY')) return null;

    $header = rtrim(strtr(base64_encode('{"alg":"HS256","typ":"JWT"}'), '+/', '-_'), '=');
    $body   = rtrim(strtr(base64_encode(wp_json_encode($payload)), '+/', '-_'), '=');
    $sig    = rtrim(strtr(base64_encode(hash_hmac('sha256', "$header.$body", JWT_AUTH_SECRET_KEY, true)), '+/', '-_'), '=');

    return "$header.$body.$sig";
}

function spm_handle_renew_token(WP_REST_Request $request): WP_REST_Response {
    $auth = $request->get_header('authorization') ?? '';

    if (stripos($auth, 'Bearer ') !== 0) {
        return new WP_REST_Response(['error' => 'No token'], 401);
    }

    $payload = spm_jwt_verify(trim(substr($auth, 7)));

    if (!$payload) {
        return new WP_REST_Response(['error' => 'Invalid or expired token'], 401);
    }

    $user_id = $payload->data->user->id ?? 0;

    if (!get_userdata($user_id)) {
        return new WP_REST_Response(['error' => 'User not found'], 401);
    }

    $issued_at = time();
    $new_token = spm_jwt_encode([
        'iss'  => get_bloginfo('url'),
        'iat'  => $issued_at,
        'nbf'  => $issued_at,
        'exp'  => apply_filters('jwt_auth_expire', $issued_at + DAY_IN_SECONDS * 7),
        'data' => ['user' => ['id' => $user_id]],
    ]);

    if (!$new_token) {
        return new WP_REST_Response(['error' => 'Could not generate token'], 500);
    }

    return new WP_REST_Response(['token' => $new_token], 200);
}

function add_email_to_rest_api($response, $user, $request) {
    $response->data['email'] = $user->user_email;
    return $response;
}

add_filter('rest_prepare_user', 'add_email_to_rest_api', 10, 3);

add_filter( 'rest_user_query', function( $args, $request ) {
    if ( ! is_user_logged_in() ) {
        return $args;
    }

    $args['who'] = '';

    return $args;
}, 10, 2 );

add_filter( 'rest_authentication_errors', function( $result ) {
    if ( ! empty( $result ) ) {
        return $result;
    }

    if ( is_user_logged_in() ) {
        return $result;
    }

    if ( 'OPTIONS' === $_SERVER['REQUEST_METHOD'] ) {
        return $result;
    }

    $public_routes = [
        '/wp-json/jwt-auth/v1/token',
        '/wp-json/jwt-auth/v1/token/validate',
    ];

    $request_uri = $_SERVER['REQUEST_URI'] ?? '';

    foreach ( $public_routes as $route ) {
        if ( strpos( $request_uri, $route ) === 0 ) {
            return $result;
        }
    }

    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if ( stripos( $auth, 'Bearer ' ) === 0 ) {
        return $result;
    }

    return new WP_Error(
        'rest_forbidden',
        'REST API is restricted.',
        [ 'status' => 401 ]
    );
}, 99 );
