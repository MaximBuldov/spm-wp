<?php
add_action('rest_after_insert_works', function ( $post, $request, $creating ) {
    $method = $request->get_method();
    $route  = method_exists($request, 'get_route') ? $request->get_route() : '';

    error_log('[SPM_REV] rest_after_insert_works');
    error_log('[SPM_REV] creating='.(int)$creating.' method='.$method.' route='.$route.' post_id='.$post->ID);

    if ( $creating ) {
        error_log('[SPM_REV] skip: creating');
        return;
    }

    // Keep this while testing; if your client uses POST for updates, you can add it later.
    if ( $method !== 'PUT' && $method !== 'PATCH' ) {
        error_log('[SPM_REV] skip: method');
        return;
    }

    if ( wp_is_post_revision( $post->ID ) ) {
        error_log('[SPM_REV] skip: is revision');
        return;
    }

    // 1) Try normal API (will return false if only meta changed)
    $rev_id = wp_save_post_revision( $post->ID );
    error_log('[SPM_REV] wp_save_post_revision => ' . var_export($rev_id, true));

    // 2) Force revision if WP skipped it
    if ( ! $rev_id && function_exists('_wp_put_post_revision') ) {
        $rev_id = _wp_put_post_revision( $post->ID );
        error_log('[SPM_REV] _wp_put_post_revision => ' . var_export($rev_id, true));
    }

    if ( ! $rev_id ) {
        error_log('[SPM_REV] revision still NOT created (likely only meta changed + no force available)');
    } else {
        error_log('[SPM_REV] revision CREATED id=' . $rev_id);
    }

}, 20, 3);