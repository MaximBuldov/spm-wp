<?php
function spm_area_from_zip($f) {
  $zip = '';
  if (!empty($f['pickup_address']) && is_array($f['pickup_address']) && !empty($f['pickup_address'][0]['zip'])) {
    $zip = trim((string)$f['pickup_address'][0]['zip']);
  }
  if ($zip === '') return '';
  // Normalize
  if (strpos($zip, '942') === 0 || strpos($zip, '95') === 0) return 'SC';
  $p2 = substr($zip, 0, 2);
  if ($p2 === '90' || $p2 === '91') return 'LA';
  if ($p2 === '94') return 'SF';
  return '';
}

function spm_build($post, $opts = []) {
  $o = array_merge(['html' => true, 'client' => true], $opts);
  $f = get_field('customer_info', $post) ?: [];
  $H = $o['html'];

  $get = fn($k, $d='') => isset($f[$k]) ? $f[$k] : $d;
  $nf  = fn($v) => number_format((float)$v, 2, '.', '');

  // time label
  $mapTime = ['07:00'=>'7–7:30am','08:00'=>'8–8:30am','09:00'=>'9–9:30am','10:00'=>'10–10:30am','11:00'=>'11–11:30am','12:00'=>'12–12:30pm','13:00'=>'1-1:30pm','14:00'=>'2-2:30pm','15:00'=>'3-3:30pm','16:00'=>'4-4:30pm','17:00'=>'5-5:30pm','18:00'=>'6-6:30pm','19:00'=>'7-7:30pm'];
  $time     = $get('time');
  $timeLbl  = $mapTime[$time] ?? '';

  // rates
  $res = (float)$get('result', 0);
  $pay = $get('payment');
  [$cash, $credit] = $pay === 'cash' ? [$res, $res+10] : [$res-10, $res];

  // heavy items note
  $heavy = $get('heavyItems');
  $heavyNote = ($heavy && $heavy !== 'No') ? ($H ? ' ($250)' : ' ($250)') : '';

  // helpers for html/text
  $line = fn($k,$v) => $H ? esc_html($k).': '.($v)."<br />\n" : "$k: $v\n";
  $tag  = fn($s) => $H ? $s."<br />\n" : $s."\n";
  $safe = fn($s) => $H ? esc_html($s) : $s;

  // addresses
$addr = function($list, $label) use ($H, $safe) {
  if (empty($list) || !is_array($list)) return '';
  $out = '';

  foreach ($list as $row) {
    $a = $row['full_address'] ?? '';
    $u = $row['unit'] ?? '';
    $z = $row['zip'] ?? '';

    if (!$a && !$u && !$z) continue;

    $suffix = [];
    if ($u) $suffix[] = 'Unit: ' . ($H ? esc_html($u) : $u);
    if ($z) $suffix[] = 'ZIP: ' . ($H ? esc_html($z) : $z);
    $suffix = $suffix ? ', ' . implode(', ', $suffix) : '';

    if ($H) {
      $url = esc_url('https://maps.google.com/?q=' . rawurlencode(trim("$a $z")));
      $out .= sprintf(
        "%s: <a rel='noreferrer' target='_blank' href='%s'>%s</a>%s<br />\n",
        esc_html($label),
        $url,
        esc_html($a),
        $suffix
      );
    } else {
      $out .= $label . ': ' . $a . $suffix . "\n";
    }
  }

  return $out;
};

  // supplies
  $supplies = '';
  if ($get('supplies') === 'yes') {
    if ($get('small_boxes'))  $supplies .= $line('Small Boxes',  $safe($get('small_boxes')));
    if ($get('medium_boxes')) $supplies .= $line('Medium Boxes', $safe($get('medium_boxes')));
    if ($get('wrapping_paper')) $supplies .= $line('Wrapping Paper', $safe($get('wrapping_paper')));
  }

  // contact
  $contact = '';
  if ($get('contact_name') || $get('contact_phone') || $get('contact_email')) {
    if ($H) {
      if ($get('contact_name'))  $contact .= $line('Contact Name',  $safe($get('contact_name')));
      if ($get('contact_phone')) $contact .= 'Contact Phone: <a href="tel:'.esc_attr($get('contact_phone')).'">'.esc_html($get('contact_phone'))."</a><br />\n";
      if ($get('contact_email')) $contact .= 'Contact Email: <a href="mailto:'.antispambot($get('contact_email')).'">'.esc_html($get('contact_email'))."</a><br />\n";
    } else {
      if ($get('contact_name'))  $contact .= "Contact Name: ".$get('contact_name')."\n";
      if ($get('contact_phone')) $contact .= "Contact Phone: ".$get('contact_phone')."\n";
      if ($get('contact_email')) $contact .= "Contact Email: ".$get('contact_email')."\n";
    }
  }

  $note = $get('note');
  $area = spm_area_from_zip($f);
  if ($H && $note) $note = nl2br(esc_html($note));

  // body core
  $core  = '';
  if ($o['client'] && $H) $core .= "<p>Thank you for choosing Smart People Moving!</p>\n";
  $core .= $line('Request', '#'.intval($post->ID));
  $core .= $line('Status', 'Confirmed');
  if ($area) $core .= $line('Area', $safe($area));
  $core .= $line('Move Date', $safe(get_field('date', $post) ?: ''));
  $core .= $line('Start Time', $safe($timeLbl));
  $core .= $line('Crew Size', $safe($get('movers')));
  $core .= $line('Hourly rate', '$'.$nf($cash).' (cash)/ $'.$nf($credit).' (card payment)');
  $core .= $line('Payment', $safe($pay));
  $core .= $line('Service fee', '$'.$safe($get('truck_fee')));
  $core .= $line('Size of move', $safe($get('bedroom')));
  $core .= $line('Type of residency', $safe($get('typeofresidency')));
  $core .= $line('Truck', $safe($get('truck')));
  $core .= $line('Heavy items', $safe($heavy.$heavyNote));
  $core .= ($H ? "<br />\n" : "\n");
  $core .= $addr($get('pickup_address', []), 'From');
  $core .= $addr($get('dropoff_address', []), 'To');
  $core .= ($H ? "<br />\n" : "\n");
  $core .= $contact;
  $core .= $line('Packing', $safe($get('packing')));
  $core .= $supplies;
  $core .= ($H ? "<br />\n" : "\n");
  if ($H) {
    $core .= $line('Customer name', $safe($get('customer_name')));
    $core .= 'Customer Phone: <a href="tel:'.esc_attr($get('customer_phone')).'">'.esc_html($get('customer_phone'))."</a><br />\n";
    $core .= 'Customer Email: <a href="mailto:'.antispambot($get('customer_email')).'">'.esc_html($get('customer_email'))."</a><br />\n";
  } else {
    $core .= "Customer name: ".$get('customer_name')."\n";
    $core .= "Customer Phone: ".$get('customer_phone')."\n";
    $core .= "Customer Email: ".$get('customer_email')."\n";
  }
  $core .= $line('From', $safe($get('howfrom')));
  if ($note) $core .= $line('Additional information', $note);

  // policy (client only)
  if ($o['client']) {
    if ($H) {
      $core .= "<div><p></p><strong>Attention:</strong><ul>
        <li>3 hours minimum mandatory.</li>
        <li>If the distance between pickup and drop-off address is more than 10 miles, driving time will be doubled.</li>
        <li>Total cost = hourly rate + service fee + driving time.</li>
      </ul></div>
      <div><strong>Attention:</strong><p>The customer must confirm or cancel at least 48 hours before the move date.</p>
      <div style='font-size: 9px'>
      <div>Notes for Customer: Please note that our standard service covers everything agreed upon at the time of booking. Once our professional crew arrives on-site, the team leader will carefully review the specific conditions of your move. If there are additional factors that require extra effort, they may discuss with you possible additional charges.</div>
      <div>Examples include (but are not limited to): Long distance from the truck to the door; Stairs or steps; Items heavier than 250 lbs; Extra fragile or antique items needing special protection; Very dirty or unsafe working conditions; Additional packing for unique or antique pieces; Vehicle transportation; Parking difficulties, steep driveways, narrow access roads; Low branches, bushes, or other obstacles affecting truck access</div>
      <div>Our goal is always to be transparent, fair, and professional, while ensuring your move is completed safely and efficiently.</div></div>
      </div>";
    } else {
      $core .= "Attention:\n• 3 hours minimum mandatory\n• If pickup–dropoff distance > 10 miles, driving time is doubled\n• Total cost = hourly rate + service fee + driving time\nAttention:\n• Confirm/cancel ≥ 48h before move date\n";
    }
  }

  return $H ? "<html><body>$core</body></html>" : $core;
}

function moveConfirmedEmail($post) {
  if (empty($post) || empty($post->ID)) return;
  $f = get_field('customer_info', $post) ?: [];
  $toEmail = $f['customer_email'] ?? '';
  $toName  = $f['customer_name']  ?? '';
  $area    = spm_area_from_zip($f);
  $subject = sprintf('Order #%s | Smart People Moving%s', intval($post->ID), $area ? ' ['.$area.']' : '');
  $headers = ['Content-Type: text/html; charset=UTF-8'];

  if ($toEmail) {
    $to = $toName ? sprintf('%s <%s>', $toName, $toEmail) : $toEmail;
    wp_mail($to, $subject, spm_build($post, ['html'=>true,'client'=>true]), $headers);
  }
  wp_mail('smart.people.move@gmail.com', $subject, spm_build($post, ['html'=>true,'client'=>false]), $headers);
}

function moveConfirmedSms($post, $client, $twilio_number) {
  if (empty($post) || empty($post->ID)) return;
  $f = get_field('customer_info', $post) ?: [];
  $phone = $f['customer_phone'] ?? '';
  if (!$phone) return;
  $client->messages->create($phone, ['from'=>$twilio_number, 'body'=>spm_build($post, ['html'=>false,'client'=>true])]);
}
