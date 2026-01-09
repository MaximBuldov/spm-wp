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
  $admin_notified_about_foreman = get_post_meta( $post->ID, '_admin_notified_about_foreman', true );

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
        if (!empty($foreman_info['status']) && $foreman_info['status'] === 'pending' && !$foreman_notified) {
          assignWorkersSms($post, $client, $twilio_number);
          update_post_meta( $post->ID, '_foreman_notified', 1 );
        }
        if (!empty($foreman_info['status']) && $foreman_info['status'] === 'confirmed' && !$admin_notified_about_foreman) {
          workerConfirmedJobSms($post, $client, $twilio_number);
          update_post_meta( $post->ID, '_admin_notified_about_foreman', 1 );
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
      'meta_query'     => [
        'relation' => 'AND',
        [
          'key'     => 'date',
          'value'   => $today,
          'compare' => '=',
          'type'    => 'DATE',
        ],
        [
          'key'     => 'state',
          'value'   => 'confirmed',
          'compare' => '=',
        ],
      ],
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
            . '<a href="tel:+14158399391">(415) 839-9391</a>.</p>'
            . '<p>Thank you!</p>'
            . '<p>The Smart People Moving Team</p>'
            . '</body></html>';

          wp_mail($email, $subject, $msgEmail, $headers);
        }

        if ($phone) {
          $msgSms = "Hi, your move is scheduled for the date / time listed below.\n"
            . "{$date} / {$time}\n"
            . "If you need to reschedule or have any questions please call (415) 839-9391\n"
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
  $work_id    = get_field('work_id', $post) ?? null;
  $message   = get_field('message', $post);
  $phone   = get_field('customer_phone', $post) ?? null;

  if (!$work_id && $phone) {
      $q = new WP_Query([
      'post_type'      => 'works',
      'post_status'    => 'any',
      'posts_per_page' => 1,
      'orderby'        => 'date',
      'order'          => 'DESC',
      'fields'         => 'ids',
      'meta_query'     => [
        [
          'key'     => 'customer_info_customer_phone',
          'value'   => $phone,
          'compare' => '='
        ]
      ],
    ]);

    if ( !empty($q->posts) ) {
      $work = (int)$q->posts[0];
      followup_notify_managers($work, $phone, $message);
    }
     wp_reset_postdata();
  };

  $customer_info = get_field('customer_info', $work_id);

  if ( empty($customer_info) ) return;
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
