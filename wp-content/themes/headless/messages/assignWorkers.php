<?php

function assignWorkersSms($post, $client, $twilio_number) {
  $customer_info = get_field('customer_info', $post);
  $name = $customer_info['customer_name'];
  $foreman_info = get_field('foreman_info', $post);
  $link = 'https://smartpeoplemoving.com/book.html';
  $message = "You have new job(s) scheduled.
To confirm your job please clicking the link below and click the confirmation button.
https://w.smartpeoplemoving.com/my-works/{$post->ID}
Call the office at (510) 566-7471 if you have questions.
The Smart People Moving Team
";

  //sms
  foreach ($foreman_info['workers'] as $worker) {
    if($worker['worker_role'] === 'foreman') {
      $id = $worker['worker'];
      $phone = get_field('phone', 'user_'.$id);
      $client->messages->create(
          $phone,
          array(
              'from' => $twilio_number,
              'body' => $message
          )
      );
    }
  }

}
