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

function followup_notify_managers(int $work_id, ?string $from_phone, ?string $text): void {
  $from_phone = (string)($from_phone ?? '');
  $text = (string)($text ?? '');
  $to = ['smart.people.move@gmail.com'];

  if (!$to) return;

  $subject = 'New customer SMS (Work #' . $work_id . ')';

  $body =
    "New SMS received for Work #{$work_id}\n\n" .
    ($from_phone ? "From: {$from_phone}\n\n" : '') .
    "Message:\n{$text}\n";

  $headers = ['Content-Type: text/plain; charset=UTF-8'];

  wp_mail($to, $subject, $body, $headers);
}