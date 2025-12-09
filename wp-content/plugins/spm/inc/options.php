<?php
add_action('rest_api_init', function () {
    register_rest_route('myplugin/v1', '/update-option', array(
        'methods'  => 'POST',
        'callback' => 'myplugin_update_option_callback',
        'permission_callback' => function () {
            return current_user_can('manage_options');
        },
        'args' => array(
            'name' => array(
                'required' => true,
                'type'     => 'string',
            ),
            'value' => array(
                'required' => true,
                'type'     => 'number',
            ),
        ),
    ));
});

function myplugin_update_option_callback($request) {
    $option_name  = sanitize_text_field($request->get_param('name'));
    $option_value = $request->get_param('value');

		$success = update_field($option_name, $option_value, 'option');

    if ($success) {
        return rest_ensure_response(array(
            'name' => $option_name,
            'value' => $option_value
        ));
    } else {
        return new WP_Error('update_failed', 'Failed to update the option.', array('status' => 500));
    }
}