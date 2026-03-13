<?php
function get_next_work_number() {
    global $wpdb;
    
    $wpdb->query("UPDATE {$wpdb->options} SET option_value = option_value + 1 WHERE option_name = 'work_number_counter'");
    
    if ($wpdb->rows_affected === 0) {
        $max = $wpdb->get_var("
            SELECT MAX(CAST(meta_value AS UNSIGNED)) 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = 'work_id'
        ");
        $next = $max ? $max + 1 : 1;
        add_option('work_number_counter', $next, '', 'no');
        return $next;
    }
    
    return (int) get_option('work_number_counter');
}

function assign_work_number_on_publish($new_status, $old_status, $post) {
    if ($post->post_type !== 'works') return;
    if ($new_status !== 'publish') return;
    
    $existing = get_post_meta($post->ID, 'work_id', true);
    if ($existing) return;
    
    $number = get_next_work_number();
    update_post_meta($post->ID, 'work_id', $number);
}
add_action('transition_post_status', 'assign_work_number_on_publish', 10, 3);

function migrate_old_works_numbers() {
    if (get_option('works_migration_done')) return;
    
    $works = get_posts([
        'post_type'      => 'works',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'ID',
        'order'          => 'ASC',
        'meta_query'     => [[
            'key'     => 'work_id',
            'compare' => 'NOT EXISTS',
        ]],
    ]);
    
    foreach ($works as $i => $work) {
        update_post_meta($work->ID, 'work_id', $i + 1);
    }
    
    if (!empty($works)) {
        update_option('work_number_counter', count($works), 'no');
    }
    
    update_option('works_migration_done', true);
}
add_action('init', 'migrate_old_works_numbers');

function add_work_number_column($columns) {
    $new = [];
    foreach ($columns as $key => $value) {
        if ($key === 'title') {
            $new['work_id'] = '№';
        }
        $new[$key] = $value;
    }
    return $new;
}
add_filter('manage_work_posts_columns', 'add_work_number_column');

function render_work_number_column($column, $post_id) {
    if ($column === 'work_id') {
        echo get_post_meta($post_id, 'work_id', true) ?: '—';
    }
}
add_action('manage_work_posts_custom_column', 'render_work_number_column', 10, 2);

function register_work_number_for_rest() {
    register_post_meta('works', 'work_id', [
        'show_in_rest'  => true,
        'single'        => true,
        'type'          => 'integer',
        'auth_callback' => function() {
            return current_user_can('edit_posts');
        },
    ]);
}
add_action('init', 'register_work_number_for_rest');