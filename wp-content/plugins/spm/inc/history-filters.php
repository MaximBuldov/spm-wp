<?php
add_filter( 'rest_history_query', function( $args ) {
  $ignore = array('page', 'per_page', 'search', 'order', 'orderby', 'slug', '_fields', 'author');

	if ( empty( $args['meta_query'] ) || ! is_array( $args['meta_query'] ) ) {
		$args['meta_query'] = array(
				'relation' => 'AND',
		);
	}

  foreach ( $_GET as $key => $value ) {
    if (!in_array($key, $ignore)) {
      $args['meta_query'][] = array(
        'key'   => $key,
        'value' => $value,
      );
    }
  }

  return $args;
});