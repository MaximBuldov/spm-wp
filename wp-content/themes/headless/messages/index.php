<?php
require get_template_directory() . '/messages/moveConfirmation.php';
require get_template_directory() . '/messages/moveCompleted.php';
require get_template_directory() . '/messages/moveConfirmed.php';
require get_template_directory() . '/messages/assignWorkers.php';
require get_template_directory() . '/messages/workerConfirmedJob.php';
require get_template_directory() . '/messages/followup.php';

require get_template_directory() . '/twillio/src/Twilio/autoload.php';
use Twilio\Rest\Client;

function restSendEmail($post, $request, $creating) {
  if (empty($post) || empty($post->ID)) return;
  $state          = get_field('state', $post);
  $foreman_info   = get_field('foreman_info', $post) ?: [];

  $confirmed_notified = get_post_meta( $post->ID, '_confirmed_notified', true );
  $foreman_notified = get_post_meta( $post->ID, '_foreman_notified', true );

  try {
    $client         = new Client(TWILLIO_ACCOUNT_SID, TWILLIO_AUTH_TOKEN);
    $twilio_number  = TWILLIO_PHONE;

    switch ($state) {
      case 'quote':
        if($creating) {
          moveConfirmationSms($post, $client, $twilio_number);
          moveConfirmationEmail($post);
        }
        break;

      case 'completed':
        moveCompletedEmail($post);
        break;

      case 'confirmed':
        if (empty($foreman_info['truck']) && !$confirmed_notified) {
          moveConfirmedEmail($post);
          moveConfirmedSms($post, $client, $twilio_number);

          update_post_meta( $post->ID, '_confirmed_notified', 1 );
        }
        break;

      case 'assignWorkers':
        if (!empty($foreman_info['status']) && $foreman_info['status'] === 'assignWorkers' && !$foreman_notified) {
          assignWorkersSms($post, $client, $twilio_number);
        }
        if (!empty($foreman_info['status']) && $foreman_info['status'] === 'confirmed') {
          workerConfirmedJobSms($post, $client, $twilio_number);
        }
        break;
    }
  } catch (\Twilio\Exceptions\RestException $e) {
    error_log('Twilio Error: ' . $e->getMessage());
  } catch (Exception $e) {
    error_log('General Error: ' . $e->getMessage());
  }
}

function sendReminder() {
  try {
    $client        = new Client(TWILLIO_ACCOUNT_SID, TWILLIO_AUTH_TOKEN);
    $twilio_number = TWILLIO_PHONE;

    $headers = ['Content-Type: text/html; charset=UTF-8'];
    $subject = 'Reminder for your upcoming move';

    $today = date('Y-m-d');
    $query = new WP_Query([
      'post_type'      => 'works',
      'posts_per_page' => -1,
      'meta_query'     => [[
        'key'     => 'date',
        'value'   => $today,
        'compare' => '=',
        'type'    => 'DATE'
      ]],
    ]);

    if ($query->have_posts()) {
      while ($query->have_posts()) {
        $query->the_post();
        $date          = get_field('date') ?: '';
        $ci            = get_field('customer_info') ?: [];
        $time          = isset($ci['time']) ? $ci['time'] : '';
        $email         = isset($ci['customer_email']) ? $ci['customer_email'] : '';
        $phone         = isset($ci['customer_phone']) ? $ci['customer_phone'] : '';

        if ($email) {
          $msgEmail = '<html><body>'
            . '<p>Hi, your move is scheduled for the date / time listed below.</p>'
            . '<p>' . esc_html($date) . ' / ' . esc_html($time) . '</p>'
            . '<p>If you need to reschedule or have any questions, please call '
            . '<a href="tel:+15105667471">(510) 566-7471</a>.</p>'
            . '<p>Thank you!</p>'
            . '<p>The Smart People Moving Team</p>'
            . '</body></html>';

          wp_mail($email, $subject, $msgEmail, $headers);
        }

        if ($phone) {
          $msgSms = "Hi, your move is scheduled for the date / time listed below.\n"
            . "{$date} / {$time}\n"
            . "If you need to reschedule or have any questions please call (510) 566-7471\n"
            . "Thank you!\n"
            . "The Smart People Moving Team";

          $client->messages->create($phone, [
            'from' => $twilio_number,
            'body' => $msgSms
          ]);
        }
      }
      wp_reset_postdata();
    }
  } catch (\Twilio\Exceptions\RestException $e) {
    error_log('Twilio Reminder Error: ' . $e->getMessage());
  } catch (Exception $e) {
    error_log('General Reminder Error: ' . $e->getMessage());
  }
}

function restSendFolloup($post, $request, $creating) {
  if (empty($post) || empty($post->ID)) return;
  $work_id    = get_field('work_id', $post);
  $message   = get_field('message', $post);

  if ( !$work_id ) {
    return;
  };
  $customer_info = get_field('customer_info', $work_id);

  if ( empty($customer_info) ) return;
  $phone = $customer_info['customer_phone'] ?? null;
  $email = $customer_info['customer_email'] ?? null;

  try {
    $client         = new Client(TWILLIO_ACCOUNT_SID, TWILLIO_AUTH_TOKEN);
    $twilio_number  = TWILLIO_PHONE;

    if($email) {
      followupEmail($email, $message);
    }
    if($phone) {
      followupSms($client, $twilio_number, $phone, $message);
    }
  } catch (\Twilio\Exceptions\RestException $e) {
    error_log('Twilio Error: ' . $e->getMessage());
  } catch (Exception $e) {
    error_log('General Error: ' . $e->getMessage());
  }
}
