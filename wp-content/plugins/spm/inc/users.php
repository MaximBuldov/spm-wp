<?php
add_action( 'rest_api_init', function(){
	register_rest_route( 'myplug/v2', '/users/(?P<id>\d+)', array(
		'methods'  => 'GET',
		'callback' => 'get_works_by_user',
		'permission_callback' => '__return_true',
	) );

	  register_rest_field('user', 'roles', array(
			'get_callback' => 'get_user_roles',
			'update_callback' => null,
			'schema' => array(
				'type' => 'array'
			)
		));
} );

function get_works_by_user(WP_REST_Request $request){
	$items = array();
	if (get_field('works', 'user_'.$request['id'])) {
		$posts = get_posts( array (
			'post_status' => 'publish',
			'numberposts' => -1,
			'include' => get_field('works', 'user_'.$request['id']),
			'post_type' => 'works',
			'meta_key' => 'date',
			'orderby' => 'meta_value',
      'order' => 'DESC',
			'meta_query' => [ [
				'key' => 'state',
				'value' => $request['state'],
			] ],
		) ) ;

		foreach( $posts as $post ){
			$field = get_field('foreman_info', $post->ID);
			$arr = $field['workers'];
			$workers = array_column($arr, 'worker');
			$found_key = array_search($request['id'], array_column($workers , 'ID'));
			$user = $arr[$found_key];
			$items[] = array(
				'id'      => $post->ID,
				'created_date' => $post->post_date,
				'date' => get_field('date', $post->ID),
				'time' => get_field('customer_info', $post->ID)['time'],
				'status' => $field['status'],
				'state' => get_field('state', $post->ID),
				'workers_count' => $field['workers_count'],
				'total_time' => $field['total_time'],
				'workers' => $arr,
				'worker' => $user,
				'tips' => $field['tips']
			);
		}
	}

	$user = array(
		'name' => get_author_name((int) $request['id']),
		'works' => $items
	);

	return $user;
}

function get_user_roles($object, $field_name, $request) {
  return get_userdata($object['id'])->roles;
}