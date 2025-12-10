<?php
add_action('rest_api_init', function () {

    register_rest_route('spm/v1', '/book', [
        'methods'  => 'POST',
        'callback' => 'spm_rest_book',
        'permission_callback' => '__return_true',
    ]);

});

function spm_rest_book() {
    $prices = [
        'price'          => (int) get_field('price', 'option'),
        'moverPrice'     => (int) get_field('moverPrice', 'option'),
        'nonCashPrice'   => (int) get_field('nonCashPrice', 'option'),
        'weekendPrice'   => (int) get_field('weekendPrice', 'option'),
        'smallBox'       => (int) get_field('smallBox', 'option'),
        'mediumBox'      => (int) get_field('mediumBox', 'option'),
        'wrappingPaper'  => (int) get_field('wrappingPaper', 'option'),
        'heavyItems'     => (int) get_field('heavyItems', 'option'),
        'truckFee'       => (int) get_field('truckFee', 'option'),
    ];

    return [
        'prices' => $prices,
    ];
}