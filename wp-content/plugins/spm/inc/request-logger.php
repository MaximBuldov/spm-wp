<?php
defined('ABSPATH') || exit;

add_filter('rest_pre_dispatch', function ($result, $server, $request) {
    if (strpos($request->get_route(), '/wp/v2/works') === false) {
        return $result;
    }

    $GLOBALS['spm_req_start'] = microtime(true);

    return $result;
}, 10, 3);

add_filter('rest_post_dispatch', function ($result, $server, $request) {
    if (strpos($request->get_route(), '/wp/v2/works') === false) {
        return $result;
    }

    if ($request->get_method() === 'OPTIONS') {
        return $result;
    }

    $ms     = isset($GLOBALS['spm_req_start'])
        ? round((microtime(true) - $GLOBALS['spm_req_start']) * 1000)
        : '?';
    $status = $result->get_status();

    $user_id = get_current_user_id();
    $user    = $user_id ? get_userdata($user_id) : null;
    $who     = $user ? sprintf('%s (id=%d)', $user->display_name, $user_id) : 'unauthenticated';

    error_log(sprintf(
        '[SPM] %s %s | %s | → %d (%dms)',
        $request->get_method(),
        $request->get_route(),
        $who,
        $status,
        $ms
    ));

    if ($status >= 400) {
        error_log('[SPM] error: ' . wp_json_encode($result->get_data()));
    }

    return $result;
}, 10, 3);
