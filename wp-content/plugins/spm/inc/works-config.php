<?php
add_filter('rest_pre_insert_works', function( $prepared_post, $request, $creating ) {
    if ( $creating ) {
        return $prepared_post;
    }

    $method = $request->get_method();
    if ( $method !== 'PUT' && $method !== 'PATCH' ) {
        return $prepared_post;
    }

    $post_id = isset($prepared_post->ID) ? (int)$prepared_post->ID : 0;
    if ( ! $post_id ) {
        return $prepared_post;
    }

    if ( wp_is_post_revision( $post_id ) ) {
        return $prepared_post;
    }

    wp_save_post_revision( $post_id );

    return $prepared_post;
}, 10, 3);