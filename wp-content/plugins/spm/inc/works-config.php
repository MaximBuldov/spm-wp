<?php
add_action('rest_after_insert_works', function ( $post, $request, $creating ) {
    // if ( $creating ) {
    //     return;
    // }

    // $method = $request->get_method();
    // if ( $method !== 'PUT' && $method !== 'PATCH' ) {
    //     return;
    // }

    $post_id = (int) $post->ID;

    if ( ! $post_id || wp_is_post_revision( $post_id ) ) {
        return;
    }

    $rev_id = wp_save_post_revision( $post_id );

    if ( ! $rev_id && function_exists('_wp_put_post_revision') ) {
        _wp_put_post_revision( $post_id );
    }
}, 20, 3);