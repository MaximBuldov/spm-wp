<?php
add_action('rest_api_init', function () {
  register_rest_route('myplugin/v1', '/users/(?P<id>\d+)', [
    'methods'  => 'POST',
    'permission_callback' => function (WP_REST_Request $r) {
      $id = (int) $r['id'];
      return (get_current_user_id() === $id) || current_user_can('delete_users');
    },
    'callback' => function (WP_REST_Request $r) {
      $id = (int) $r['id'];

      $user = get_userdata($id);
      if (!$user) {
        return new WP_Error('not_found', 'User not found', ['status' => 404]);
      }

      $u = new WP_User($id);
      $u->set_role('deactivated');

      if (function_exists('wp_destroy_all_sessions')) {
        wp_destroy_all_sessions($id);
      }

      return $u;
    },
  ]);
});

add_filter('rest_user_query', function (array $args, WP_REST_Request $request) {
  $args['role__not_in'] = array_values(array_unique(array_merge(
    $args['role__not_in'] ?? [],
    ['deactivated']
  )));
  return $args;
}, 10, 2);