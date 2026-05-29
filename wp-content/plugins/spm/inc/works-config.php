<?php
add_filter('rest_pre_insert_works', function ($prepared_post, $request) {
    if (empty($prepared_post->ID)) {
        return $prepared_post;
    }

    $acf       = $request->get_param('acf');
    $new_state = $acf['state'] ?? null;

    if ($new_state !== 'quote') {
        return $prepared_post;
    }

    $current_state = get_field('state', $prepared_post->ID);
    $locked_states = ['confirmed', 'assignWorkers', 'completed', 'lost'];

    if (in_array($current_state, $locked_states, true)) {
        return new WP_Error(
            'state_transition_forbidden',
            'Cannot revert work state back to quote from ' . $current_state,
            ['status' => 400]
        );
    }

    return $prepared_post;
}, 10, 2);

add_action('rest_after_insert_works', function ( $post, $request, $creating ) {
    $post_id = (int) $post->ID;

    $snapshot = [
        'acf'    => function_exists('get_fields') ? (get_fields($post_id) ?: []) : [],
    ];

    $history_id = wp_insert_post([
        'post_type'   => 'history',
        'post_status' => 'publish',
        'post_title'  => 'Work #' . $post_id . ' snapshot',
        'post_author' => get_current_user_id() ?: 0,
    ], true);

    if ( is_wp_error($history_id) ) {
        error_log('[SPM_HISTORY] failed to create history post for work '.$post_id.': '.$history_id->get_error_message());
        return;
    }

    update_field('work_id', $post_id, $history_id);
    update_post_meta($history_id, 'snapshot', wp_json_encode($snapshot));
}, 20, 3);
