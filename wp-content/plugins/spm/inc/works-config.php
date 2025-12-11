<?php
add_action('acf/save_post', function( $post_id ) {
    if ( ! is_numeric( $post_id ) ) {
        return;
    }

    if ( wp_is_post_revision( $post_id ) ) {
        return;
    }

    $post_type = get_post_type( $post_id );
    if ( $post_type !== 'works' ) {
        return;
    }

    wp_save_post_revision( $post_id );

}, 20);