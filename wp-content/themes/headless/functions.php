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

function maximum_api_filter($query_params) {
    $query_params['per_page']["maximum"]=200;
    return $query_params;
}
add_filter('rest_works_collection_params', 'maximum_api_filter');