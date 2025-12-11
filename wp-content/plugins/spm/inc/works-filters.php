<?php
add_filter( 'rest_works_query', function( $args ) {
  $ignore = array('page', 'per_page', 'search', 'order', 'orderby', 'slug', '_fields', 'author', 'startd', 'endd', 'sortbydate', 'notpending', 'foreman', 'state');

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
	if(isset($_GET['startd'])) {
		$args['meta_query'][] = array(
			'key'   => 'date',
			'value' => array( $_GET['startd'], $_GET['endd']),
			'type' => 'DATE',
			'compare' => 'BETWEEN'
		);
	}

		if(isset($_GET['sortbydate'])) {
			$args['meta_key'] = 'date';
			$args['orderby'] = 'meta_value';
		}

    if ( isset( $_GET['state'] ) && $_GET['state'] !== '' ) {
        $raw = $_GET['state'];

        if ( is_array( $raw ) ) {
            $states = $raw;
        } else {
            $states = explode( ',', $raw );
        }

        $states = array_filter( array_map( 'trim', (array) $states ) );

        if ( ! empty( $states ) ) {
            $args['meta_query'][] = array(
                'key'     => 'state',
                'value'   => $states,
                'compare' => 'IN',
            );
        }
    } else {
        if ( isset( $_GET['notpending'] ) ) {
            $args['meta_query'][] = array(
                'key'     => 'state',
                'value'   => array( 'pending', 'quote', 'lost' ),
                'compare' => 'NOT IN',
            );
        }
    }

		if (isset($_GET['foreman']) && $_GET['foreman'] !== '') {
				$foreman_id = absint($_GET['foreman']);

				$or = ['relation' => 'OR'];
				for ($i = 0; $i < 5; $i++) {
						$or[] = [
								'key'     => "foreman_info_workers_{$i}_worker",
								'value'   => $foreman_id,
								'compare' => '=',
						];
				}

				$args['meta_query'][] = $or;
		}

  return $args;
});