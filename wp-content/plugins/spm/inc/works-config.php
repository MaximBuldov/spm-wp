<?php
add_action('rest_after_insert_works', function ( $post, $request, $creating ) {

    $method = $request->get_method();
    $route  = method_exists($request, 'get_route') ? $request->get_route() : '';

    error_log('[SPM_REV] rest_after_insert_works');
    error_log('[SPM_REV] creating=' . (int)$creating . ' method=' . $method . ' route=' . $route . ' post_id=' . $post->ID);

    // We only want updates, not creation
    if ( $creating ) {
        error_log('[SPM_REV] skipped (creating=true)');
        return;
    }

    // Only for PUT / PATCH
    if ( $method !== 'PUT' && $method !== 'PATCH' ) {
        error_log('[SPM_REV] skipped (method)');
        return;
    }

    // Safety
    if ( wp_is_post_revision( $post->ID ) ) {
        error_log('[SPM_REV] skipped (is revision)');
        return;
    }

    // Create revision
    $rev_id = wp_save_post_revision( $post->ID );

    if ( $rev_id ) {
        error_log('[SPM_REV] revision created id=' . $rev_id);
    } else {
        error_log('[SPM_REV] revision NOT created');
    }

}, 20, 3);

add_action('acf/save_post', function ( $post_id ) {

    if ( ! is_numeric($post_id) ) return;
    if ( wp_is_post_revision($post_id) ) return;

    $type = get_post_type($post_id);
    if ( $type !== 'works' ) return;

    error_log('[SPM_REV] acf/save_post post_id=' . $post_id);

}, 5);