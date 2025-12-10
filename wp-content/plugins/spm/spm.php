<?php
/**
 * Plugin Name: SPM
 * Description: Custom REST endpoints, JWT response, and helpers for the headless app.
 * Version: 1.0.0
 * Author: Maksim Buldau
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'SPM_PATH', plugin_dir_path( __FILE__ ) );

require SPM_PATH . 'inc/auth.php';
require SPM_PATH . 'inc/auth-v2.php';
require SPM_PATH . 'inc/auth-config.php';
require SPM_PATH . 'inc/works.php';
require SPM_PATH . 'inc/users.php';
require SPM_PATH . 'inc/options.php';
require SPM_PATH . 'inc/works-search-endpoint.php';