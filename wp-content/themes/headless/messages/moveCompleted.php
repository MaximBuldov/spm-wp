<?php
function moveCompletedSms($post, $client, $twilio_number) {
  if (empty($post) || empty($post->ID) || !$client || !$twilio_number) return;

  $work_id = get_post_meta($post->ID, 'work_id', true);
  $message = sprintf('Work #%s completed', $work_id ?: $post->ID);

  try {
    $client->messages->create('+15105667471', array(
      'from' => $twilio_number,
      'body' => $message
    ));
  } catch (Exception $e) {
    error_log('Twilio SMS failed: ' . $e->getMessage());
  }
}

function moveCompletedEmail($post) {
  if (empty($post) || empty($post->ID)) return;

  $ci    = get_field('customer_info', $post) ?: array();
  $name  = isset($ci['customer_name'])  ? $ci['customer_name']  : '';
  $email = isset($ci['customer_email']) ? $ci['customer_email'] : '';
  if (!$email) return;

  $subject = 'Move Completed | Smart People Moving';

  $message = '<html><body>'
    . '<p>Thank you for choosing Smart People Moving! Your move was completed.</p>'
    . '<p>If you have any questions or concerns, please call '
    . '<a href="tel:+15105667471">(510) 566-7471</a> or email '
    . '<a href="mailto:smart.people.move@gmail.com">smart.people.move@gmail.com</a>.'
    . '</p>'
    . '<p>The Smart People Moving Team</p>'
    . '</body></html>';

  $to = $name ? sprintf('%s <%s>', $name, $email) : $email;
  $headers = array('Content-Type: text/html; charset=UTF-8');

  wp_mail($to, $subject, $message, $headers);
}
