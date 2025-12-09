<?php
/**
 * The template for displaying 404 pages (not found)
 *
 * @link https://codex.wordpress.org/Creating_an_Error_404_Page
 *
 * @package headless
 */

get_header();
?>

------
<?php

// задаем нужные нам критерии выборки данных из БД
$args = array(
	'posts_per_page' => 100,
  'post_type' => 'works',
  'meta_query' => [
    // [
    //   'key'   => 'state',
    //   'value' => 'confirmed',
    //   'compare' => '='
    // ]
		// 'relation' => 'OR',
		// [
		// 	'key'   => 'date',
		// 	'value' => '20220519',
		// 	'compare' => '=',
		// 	'type' => 'NUMERIC',
		// ],
		// [
		// 	'key'   => 'date',
		// 	'value' => '05/19/2022',
		// 	'compare' => '=',
		// ]
		[
			'key'   => 'date',
			'value' => array( '20220518', '20220522'),
			'type' => 'NUMERIC',
			'compare' => 'BETWEEN'
		]
  ]
);

$query = new WP_Query( $args );

// Цикл
if ( $query->have_posts() ) { ?>
  <ol>
	<?php while ( $query->have_posts() ) {
		$query->the_post();
		?>
		<li><?php the_field('date'); ?> - <?php echo get_the_ID(); ?> - <?php echo get_the_date('m/d/Y'); ?></li>
		<?php
	} ?>
    </ol>
<?php }
else { ?>

пусто
  <?php }

// Возвращаем оригинальные данные поста. Сбрасываем $post.
wp_reset_postdata();
?>
<?php
get_footer();
