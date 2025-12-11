<?php
add_filter('rest_pre_insert_works', function( $prepared_post, $request, $creating ) {
    $method  = $request->get_method();
    $post_id = isset($prepared_post->ID) ? (int) $prepared_post->ID : 0;

    error_log(
        '[rest_pre_insert_works] creating=' . (int) $creating .
        ' method=' . $method .
        ' post_id=' . $post_id
    );

    if ( $creating ) {
        error_log('[rest_pre_insert_works] skip: creating');
        return $prepared_post;
    }

    if ( $method !== 'PUT' && $method !== 'PATCH' ) {
        error_log('[rest_pre_insert_works] skip: method not PUT/PATCH');
        return $prepared_post;
    }

    if ( ! $post_id ) {
        error_log('[rest_pre_insert_works] skip: no post_id in prepared_post');
        return $prepared_post;
    }

    if ( wp_is_post_revision( $post_id ) ) {
        error_log('[rest_pre_insert_works] skip: is revision');
        return $prepared_post;
    }

    error_log('[rest_pre_insert_works] calling wp_save_post_revision for post ' . $post_id);
    wp_save_post_revision( $post_id );

    return $prepared_post;
}, 10, 3);