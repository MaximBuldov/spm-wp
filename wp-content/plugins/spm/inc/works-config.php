<?php
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
    update_field('snapshot', wp_json_encode($snapshot), $history_id);
}, 20, 3);

function restrict_works_rest_api($result, $server, $request) {
    $route = $request->get_route();
    
    if (strpos($route, '/wp/v2/works') !== false) {
        if (!is_user_logged_in()) {
            return new WP_Error(
                'rest_forbidden',
                'You must be logged in to access works.',
                ['status' => 401]
            );
        }
    }
    
    return $result;
}
add_filter('rest_pre_dispatch', 'restrict_works_rest_api', 10, 3);