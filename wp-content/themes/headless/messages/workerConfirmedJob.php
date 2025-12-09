<?php
function workerConfirmedJobSms($post, $client, $twilio_number) {
  if (empty($post) || empty($post->ID) || !$client || !$twilio_number) return;

  $date   = get_field('date', $post) ?: '';
  $fields = get_field('foreman_info', $post->ID);
  if (empty($fields['workers']) || !is_array($fields['workers'])) return;

  // Find the worker with role "foreman"
  $arr       = $fields['workers'];
  $found_key = array_search('foreman', array_column($arr, 'worker_role'));
  if ($found_key === false || empty($arr[$found_key]['worker'])) return;

  $user_id   = $arr[$found_key]['worker'];
  $user      = get_userdata($user_id);
  $user_name = $user && !empty($user->display_name) ? $user->display_name : 'Foreman';

  $message = sprintf('%s confirmed job #%d at %s', $user_name, intval($post->ID), $date);

  $phone = '+15105667471';

  try {
    $client->messages->create($phone, array(
      'from' => $twilio_number,
      'body' => $message
    ));
  } catch (Exception $e) {
    error_log('Twilio SMS failed: ' . $e->getMessage());
  }
}
