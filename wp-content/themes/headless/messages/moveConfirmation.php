<?php
function spm_get_info( $post ): array {
    if ( empty( $post ) ) {
        return ['name' => '', 'email' => '', 'phone' => ''];
    }

    $ci = get_field( 'customer_info', $post ) ?: [];

    $name  = isset( $ci['customer_name'] )  ? trim( (string) $ci['customer_name'] )  : '';
    $email = isset( $ci['customer_email'] ) ? trim( (string) $ci['customer_email'] ) : '';
    $phone = isset( $ci['customer_phone'] ) ? trim( (string) $ci['customer_phone'] ) : '';

    return compact( 'name', 'email', 'phone' );
}

function spm_get_url( $post, string $token ): string {
    $work_id = is_object( $post ) ? (int) $post->ID : (int) $post;

    return add_query_arg(
        [
            'work'  => $work_id,
            'token' => $token,
        ],
        'https://smartpeoplemoving.com/book.html'
    );
}


function moveConfirmationEmail($post) {
  if (empty($post) || empty($post->ID)) return;

  $info  = spm_get_info( $post );
  $name  = $info['name'];
  $email = $info['email'];
  $phone = $info['phone'];
  if (!$email) return;

  $url = spm_get_url( $post, $phone );

  $subject = 'Move confirmation | Smart People Moving';
  $paid    = (bool) get_field('paid', $post);
  $deposit = get_field('deposit', $post);
  $hasDeposit = $deposit !== null && $deposit !== '' && floatval($deposit) > 0;

  $depositBlock = '';
  if (!$paid && $hasDeposit) {
    $depositBlock =
      '<p><strong>Deposit required:</strong> Submit your deposit securely to reserve your crew, truck, and exact time slot on our schedule.</p>'
    . '<p>This payment simply holds your booking and is fully refundable as long as you contact us at least 24 hours before your scheduled moving date to make changes or cancel.</p>';
  }


  $message = '<html><body>'
    . '<p>Hi ' . esc_html($name) . '! This is Smart People Moving quote you have requested '
    . 'To confirm your booking request please fill out the form by clicking the link below.</p>'
    . '<p><a href="' . esc_url($url) . '">Confirm Move</a></p>'
    . $depositBlock
    . '<p>If you have any questions or concerns please contact us at '
    . '<a href="tel:4158399391">(415) 839-9391</a></p>'
    . '<p>The Smart People Moving Team</p>'
    . '</body></html>';

  $to = $name ? sprintf('%s <%s>', $name, $email) : $email;
  $headers = array('Content-Type: text/html; charset=UTF-8');

  wp_mail($to, $subject, $message, $headers);
}

function moveConfirmationSms($post, $client, $twilio_number) {
  if (empty($post) || empty($post->ID) || !$client || !$twilio_number) return;

  $info  = spm_get_info( $post );
  $name  = $info['name'];
  $phone = $info['phone'];
  if (!$phone) return;

  $url = spm_get_url( $post, $phone );

  $paid    = (bool) get_field('paid', $post);
  $deposit = get_field('deposit', $post);
  $hasDeposit = $deposit !== null && $deposit !== '' && floatval($deposit) > 0;

  $depositBlock = '';
  if (!$paid && $hasDeposit) {
    $depositBlock =
      "\nSubmit your deposit securely to reserve your crew, truck, and exact time slot on our schedule.\n"
    . "This payment simply holds your booking and is fully refundable as long as you contact us at least 24 hours before your scheduled moving date to make changes or cancel.\n";
  }

  $message = "Hi {$name}! This is Smart People Moving quote you've requested "
    . "To confirm your booking request please open the link below:\n"
    . "{$url}\n"
    . $depositBlock
    . "If you have any questions or concerns please contact us at (415) 839-9391\n"
    . "The Smart People Moving Team";

  $client->messages->create($phone, array(
    'from' => $twilio_number,
    'body' => $message
  ));
}
