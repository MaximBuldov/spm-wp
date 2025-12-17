<?php
function followupEmail($email, $text) {
  $subject = 'Follow Up | Smart People Moving';

  $message = '<html><body>'
    . '<p>' . esc_html($text) . '</p>'
    . '<p><a href="tel:4158399391">(415) 839-9391</a></p>'
    . '<p>The Smart People Moving Team</p>'
    . '</body></html>';

  $headers = array('Content-Type: text/html; charset=UTF-8');

  wp_mail($email, $subject, $message, $headers);
}

function followupSms($client, $twilio_number, $phone, $text) {
  if (!$client || !$twilio_number) return;
  $client->messages->create($phone, array(
    'from' => $twilio_number,
    'body' => $text
  ));
}
