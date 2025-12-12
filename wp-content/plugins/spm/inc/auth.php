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
