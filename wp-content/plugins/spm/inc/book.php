<?php
add_action('rest_api_init', function () {

    register_rest_route('spm/v1', '/book', [
        'methods'  => 'POST',
        'callback' => 'spm_rest_book',
        'permission_callback' => '__return_true',
    ]);

});

function spm_rest_book( WP_REST_Request $request ) {
    $prices = [
        'price'         => (int) get_field( 'price', 'option' ),
        'moverPrice'    => (int) get_field( 'moverPrice', 'option' ),
        'nonCashPrice'  => (int) get_field( 'nonCashPrice', 'option' ),
        'weekendPrice'  => (int) get_field( 'weekendPrice', 'option' ),
        'smallBox'      => (int) get_field( 'smallBox', 'option' ),
        'mediumBox'     => (int) get_field( 'mediumBox', 'option' ),
        'wrappingPaper' => (int) get_field( 'wrappingPaper', 'option' ),
        'heavyItems'    => (int) get_field( 'heavyItems', 'option' ),
        'truckFee'      => (int) get_field( 'truckFee', 'option' ),
    ];

    $work_id = (int) $request->get_param('work');
    $phone   = $request->get_param('phone');

    if (empty($work_id) || empty($phone)) {
        return [
            'prices' => $prices,
            'work'   => null
        ];
    }
    $post = get_post($work_id);

    if (!$post || $post->post_type !== 'works') {
        return new WP_Error(
            'not_found',
            'Work not found',
            [ 'status' => 404 ]
        );
    }

    $ci = get_field('customer_info', $work_id) ?: [];
    $phone_from_wp = isset($ci['customer_phone']) ? (string) $ci['customer_phone'] : '';

    $normalize = function($p) {
        return preg_replace('/\D+/', '', (string) $p);
    };

    if ($normalize($phone) !== $normalize($phone_from_wp)) {
        return new WP_Error(
            'forbidden',
            'Phone does not match',
            [ 'status' => 403 ]
        );
    }

    $watched = get_field('watched', $work_id);
    if (!$watched) {
        update_field('watched', true, $work_id);
        $watched = true;
    }

    $acf = get_fields($work_id) ?: [];

    $work_data = [
        'id'     => $post->ID,
        'author' => (int) $post->post_author,
        'date'   => $post->post_date,
        'acf'    => [
            'customer_info' => $acf['customer_info'] ?? null,
            'date'          => $acf['date'] ?? null,
            'state'         => $acf['state'] ?? null,
            'watched'       => $watched,
            'paid'          => $acf['paid'] ?? null,
            'deposit'       => $acf['deposit'] ?? null,
        ],
    ];

    return [
        'prices' => $prices,
        'work'   => $work_data
    ];
}