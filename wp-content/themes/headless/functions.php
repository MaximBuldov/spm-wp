<?php
/**
 * headless functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package headless
 */
require get_template_directory() . '/messages/index.php';

if ( ! defined( '_S_VERSION' ) ) {
	// Replace the version number of the theme on each release.
	define( '_S_VERSION', '1.0.0' );
}


add_action( 'rest_after_insert_works', 'restSendEmail', 100, 3);

add_action( 'react_sender_cron', 'sendReminder' );

add_filter('manage_edit-works_columns', function ($columns) {
    $new = [];

    foreach ($columns as $key => $label) {
        if ($key === 'title') {
            $new['post_id'] = 'ID';
        } else {
            $new[$key] = $label;
        }
    }

    return $new;
});

add_action('manage_works_posts_custom_column', function ($column, $post_id) {
    if ($column === 'post_id') {
        echo $post_id;
    }
}, 10, 2);

function maximum_api_filter($query_params) {
    $query_params['per_page']["maximum"]=200;
    return $query_params;
}
add_filter('rest_works_collection_params', 'maximum_api_filter');