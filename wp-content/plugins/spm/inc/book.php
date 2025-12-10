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
    $phone   = $request->get_param('token');

    if (empty($work_id) || empty($phone)) {
        return [
            'prices' => $prices,
            'work'   => null
        ];
    }
    $post = get_post($work_id);

    if (!$post || $post->post_type !== 'works') {
        return [
            'prices' => $prices,
            'work'   => null
        ];
    }

    $ci = get_field('customer_info', $work_id) ?: [];
    $phone_from_wp = isset($ci['customer_phone']) ? (string) $ci['customer_phone'] : '';

    if ($phone !== $phone_from_wp) {
        return [
            'prices' => $prices,
            'work'   => null
        ];
    }

    $watched = get_field('watched', $work_id);
    if (!$watched) {
        update_field('watched', true, $work_id);
        $watched = true;
    }

    $controller = new WP_REST_Posts_Controller('works');
    $response   = $controller->prepare_item_for_response($post, $request);
    $data       = $controller->prepare_response_for_collection($response);

    return [
        'prices' => $prices,
        'work'   => $data
    ];
}