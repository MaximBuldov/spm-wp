<?php
defined('ABSPATH') || exit;

add_filter('rest_pre_dispatch', function ($result, $server, $request) {
    if (strpos($request->get_route(), '/wp/v2/works') === false) {
        return $result;
    }

    $GLOBALS['spm_req_start'] = microtime(true);

    $body_keys = array_keys($request->get_json_params() ?? []);
    error_log(sprintf(
        '[SPM] %s %s | user_id=%d | fields=%s',
        $request->get_method(),
        $request->get_route(),
        get_current_user_id(),
        implode(',', $body_keys)
    ));

    return $result;
}, 10, 3);

add_filter('rest_post_dispatch', function ($result, $server, $request) {
    if (strpos($request->get_route(), '/wp/v2/works') === false) {
        return $result;
    }

    $ms = isset($GLOBALS['spm_req_start'])
        ? round((microtime(true) - $GLOBALS['spm_req_start']) * 1000)
        : '?';

    $status = $result->get_status();
    error_log(sprintf(
        '[SPM] %s %s → %d (%dms)',
        $request->get_method(),
        $request->get_route(),
        $status,
        $ms
    ));

    if ($status >= 400) {
        error_log('[SPM] error response: ' . wp_json_encode($result->get_data()));
    }

    return $result;
}, 10, 3);
