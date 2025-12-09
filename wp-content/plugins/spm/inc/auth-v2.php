<?php
add_action('rest_api_init', function () {
    register_rest_route('spm/v1', '/login', [
        'methods'             => 'POST',
        'callback'            => 'spm_rest_login',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('spm/v1', '/me', [
        'methods'             => 'GET',
        'callback'            => 'spm_rest_me',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ]);
});

function spm_rest_login( WP_REST_Request $request ) {
    $username = $request->get_param('username');
    $password = $request->get_param('password');

    if ( empty($username) || empty($password) ) {
        return new WP_Error('invalid_request', 'Username and password are required', ['status' => 400]);
    }

    $creds = [
        'user_login'    => $username,
        'user_password' => $password,
        'remember'      => true,
    ];

    $user = wp_signon($creds, false);

    if ( is_wp_error($user) ) {
        return new WP_Error('invalid_credentials', 'Invalid login or password', ['status' => 401]);
    }

    wp_set_current_user($user->ID);
    wp_set_auth_cookie($user->ID, true, is_ssl());

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

		$wp_users = get_users(['fields' => ['ID', 'display_name', 'user_email', 'user_login', 'acf', 'roles'],]);

    return [
			'user' => array(
				'email' => $user->user_email,
				'user_display_name' => $user->display_name,
				'user_nicename' => $user->user_nicename,
				'id' => $user->ID,
				'role' => $user->roles
			),
			'prices' => $site_options,
			'users' => $wp_users
    ];
}

function spm_rest_me( WP_REST_Request $request ) {
    $user = wp_get_current_user();
    if ( !$user || !$user->ID ) {
        return new WP_Error('not_logged_in', 'Not logged in', ['status' => 401]);
    }

    return [
        'id'    => $user->ID,
        'name'  => $user->display_name,
        'email' => $user->user_email,
        'roles' => $user->roles,
    ];
}