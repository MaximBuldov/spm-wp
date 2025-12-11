<?php
/**
 * Debug: force and log revisions for CPT "works"
 * Put this into your plugin or theme functions.php (temporary for tests).
 */

if (!defined('SPM_REV_DEBUG')) {
    define('SPM_REV_DEBUG', true);
}

function spm_rev_log($msg) {
    if (!SPM_REV_DEBUG) return;
    error_log('[SPM_REV] ' . $msg);
}

function spm_try_revision($post_id, $ctx = '') {
    $post_id = (int) $post_id;

    if (!$post_id) {
        spm_rev_log("$ctx skip: empty post_id");
        return false;
    }

    $ptype = get_post_type($post_id);
    $supports = post_type_supports($ptype, 'revisions') ? 'yes' : 'no';
    $is_rev = wp_is_post_revision($post_id) ? 'yes' : 'no';

    spm_rev_log("$ctx try revision: post_id=$post_id post_type=$ptype supports_revisions=$supports is_revision=$is_rev");

    if ($is_rev === 'yes') {
        spm_rev_log("$ctx skip: target is a revision");
        return false;
    }

    $rev_id = wp_save_post_revision($post_id);

    if ($rev_id === false) {
        spm_rev_log("$ctx wp_save_post_revision => FALSE");
        return false;
    }
    if (is_wp_error($rev_id)) {
        spm_rev_log("$ctx wp_save_post_revision => WP_Error: " . $rev_id->get_error_message());
        return $rev_id;
    }

    spm_rev_log("$ctx wp_save_post_revision => created revision_id=$rev_id");
    return $rev_id;
}

/**
 * 1) REST: before update
 */
add_filter('rest_pre_insert_works', function($prepared_post, $request, $creating) {
    $method  = $request->get_method();
    $route   = method_exists($request, 'get_route') ? $request->get_route() : '';
    $post_id = isset($prepared_post->ID) ? (int) $prepared_post->ID : 0;

    spm_rev_log("[rest_pre_insert_works] creating=" . (int)$creating . " method=$method route=$route prepared_post_id=$post_id");

    if ($creating) {
        spm_rev_log("[rest_pre_insert_works] skip: creating");
        return $prepared_post;
    }

    // For debugging, DO NOT restrict by method yet.
    // If you want later: if ($method !== 'PUT' && $method !== 'PATCH' && $method !== 'POST') ...
    spm_try_revision($post_id, '[rest_pre_insert_works]');

    return $prepared_post;
}, 10, 3);

/**
 * 2) REST: after update
 */
add_action('rest_after_insert_works', function($post, $request, $creating) {
    $method = $request->get_method();
    $route  = method_exists($request, 'get_route') ? $request->get_route() : '';
    $pid    = !empty($post->ID) ? (int)$post->ID : 0;

    spm_rev_log("[rest_after_insert_works] creating=" . (int)$creating . " method=$method route=$route post_id=$pid");

    if ($creating) {
        spm_rev_log("[rest_after_insert_works] skip: creating");
        return;
    }

    spm_try_revision($pid, '[rest_after_insert_works]');
}, 5, 3);

/**
 * 3) ACF: after ACF save
 */
add_action('acf/save_post', function($post_id) {
    spm_rev_log('[acf/save_post] raw_post_id=' . print_r($post_id, true));

    if (!is_numeric($post_id)) {
        spm_rev_log('[acf/save_post] skip: non-numeric');
        return;
    }

    $post_id = (int)$post_id;

    if (wp_is_post_revision($post_id)) {
        spm_rev_log('[acf/save_post] skip: is revision');
        return;
    }

    $ptype = get_post_type($post_id);
    spm_rev_log("[acf/save_post] post_id=$post_id post_type=$ptype");

    if ($ptype !== 'works') {
        spm_rev_log('[acf/save_post] skip: not works');
        return;
    }

    spm_try_revision($post_id, '[acf/save_post]');
}, 20);

/**
 * 4) Startup checks: does "works" support revisions?
 * (Runs on every request; OK for temporary debug.)
 */
add_action('init', function() {
    $supports = post_type_supports('works', 'revisions') ? 'yes' : 'no';
    spm_rev_log("init: works supports revisions = $supports");
});