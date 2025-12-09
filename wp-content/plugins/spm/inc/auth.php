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
	$res = array(
    'token' => $data['token'],
		'user' => array(
			'email' => $user->data->user_email,
			'user_display_name' => $user->data->display_name,
			'user_nicename' => $user->data->user_nicename,
			'id' => $user->ID,
			'role' => get_userdata($user->ID)->roles
		),
		'prices' => $site_options
	);

	return $res;
}
add_filter('jwt_auth_token_before_dispatch', 'modify_token_response', 10, 2);

function add_email_to_rest_api($response, $user, $request) {
    $response->data['email'] = $user->user_email;
    return $response;
}

add_filter('rest_prepare_user', 'add_email_to_rest_api', 10, 3);

function custom_users_rest_api( $args ) {
    $args['who'] = ''; // Remove restriction
    return $args;
}

add_filter( 'rest_user_query', 'custom_users_rest_api' );
