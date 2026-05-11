<?php
/**
 * Plugin Name: Cases Cards Shortcode
 * Description: Adds Elementor-friendly shortcodes for rendering case cards.
 * Version: 1.0.0
 * Author: ITSC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function itsc_cases_shortcode_get_repeater_items( $field, $post_id, $sub_field = 'item' ) {
	if ( ! function_exists( 'get_field' ) ) {
		return array();
	}

	$rows = get_field( $field, $post_id );
	if ( empty( $rows ) || ! is_array( $rows ) ) {
		return array();
	}

	$items = array();
	foreach ( $rows as $row ) {
		if ( is_array( $row ) && isset( $row[ $sub_field ] ) && '' !== $row[ $sub_field ] ) {
			$items[] = (string) $row[ $sub_field ];
		}
	}

	return $items;
}

function itsc_cases_shortcode_escape_title( $title ) {
	$escaped = esc_html( $title );

	return preg_replace( '/(?<=[[:alnum:]])-(?=[[:alnum:]])/', '&#8209;', $escaped );
}

function itsc_cases_shortcode_styles() {
	static $printed = false;

	if ( $printed ) {
		return '';
	}

	$printed = true;

	ob_start();
	?>
	<style id="itsc-cases-shortcode-css">
		.itsc-cases-cards {
			display: grid;
			grid-template-columns: repeat(var(--itsc-cases-columns, 3), minmax(0, 1fr));
			gap: 22px;
		}
		.itsc-cases-card {
			display: flex;
			flex-direction: column;
			min-height: 100%;
			padding: 24px;
			border: 1px solid var(--ast-border-color);
			background: var(--ast-global-color-5);
			color: inherit;
			text-decoration: none;
			transition: border-color 160ms ease, box-shadow 160ms ease, transform 160ms ease;
		}
		.itsc-cases-card:hover,
		.itsc-cases-card:focus {
			border-color: var(--ast-global-color-2);
			box-shadow: 0 16px 36px rgba(15, 23, 42, 0.1);
			color: inherit;
			transform: translateY(-2px);
		}
		.itsc-cases-card-highlight {
			margin-bottom: 12px;
			color: var(--ast-global-color-2);
			font-size: 13px;
			font-weight: 700;
			letter-spacing: 0;
			text-transform: uppercase;
		}
		.itsc-cases-card-title {
			margin: 0 0 12px;
			font-size: 22px;
			line-height: 1.28;
			overflow-wrap: normal;
			word-break: normal;
			hyphens: none;
		}
		.itsc-cases-card-summary {
			margin: 0 0 18px;
			color: var(--ast-global-color-3);
			font-size: 15px;
			line-height: 1.65;
		}
		.itsc-cases-card-stack {
			display: flex;
			flex-wrap: wrap;
			gap: 7px;
			margin-top: auto;
		}
		.itsc-cases-card-stack-item {
			display: inline-flex;
			align-items: center;
			min-height: 26px;
			padding: 3px 9px;
			border: 1px solid var(--ast-border-color);
			background: var(--ast-global-color-4);
			border-radius: 999px;
			font-size: 12px;
			font-weight: 600;
			line-height: 1.3;
		}
		@media (max-width: 921px) {
			.itsc-cases-cards {
				grid-template-columns: repeat(2, minmax(0, 1fr));
			}
		}
		@media (max-width: 640px) {
			.itsc-cases-cards {
				grid-template-columns: 1fr;
			}
		}
	</style>
	<?php

	return ob_get_clean();
}

function itsc_cases_shortcode_render( $atts ) {
	$atts = shortcode_atts(
		array(
			'limit'   => '-1',
			'columns' => '3',
			'ids'     => '',
			'orderby' => 'date',
			'order'   => 'DESC',
		),
		$atts,
		'itsc_cases'
	);

	$limit   = (int) $atts['limit'];
	$columns = min( 4, max( 1, (int) $atts['columns'] ) );
	$order   = strtoupper( $atts['order'] ) === 'ASC' ? 'ASC' : 'DESC';
	$orderby = in_array( $atts['orderby'], array( 'date', 'title', 'menu_order', 'ID' ), true ) ? $atts['orderby'] : 'date';
	$ids     = array_filter( array_map( 'absint', preg_split( '/[\s,]+/', $atts['ids'] ) ) );

	$query_args = array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => $limit,
		'category_name'       => 'cases',
		'orderby'             => $orderby,
		'order'               => $order,
		'ignore_sticky_posts' => true,
	);

	if ( $ids ) {
		$query_args['post__in']       = $ids;
		$query_args['orderby']        = 'post__in';
		$query_args['posts_per_page'] = count( $ids );
	}

	$query = new WP_Query( $query_args );

	if ( ! $query->have_posts() ) {
		return '';
	}

	ob_start();
	echo itsc_cases_shortcode_styles();
	?>
	<div class="itsc-cases-cards" style="<?php echo esc_attr( '--itsc-cases-columns:' . $columns ); ?>">
		<?php
		while ( $query->have_posts() ) :
			$query->the_post();

			$post_id           = get_the_ID();
			$short_description = function_exists( 'get_field' ) ? get_field( 'short_description', $post_id ) : '';
			$highlight         = itsc_cases_shortcode_get_repeater_items( 'highlight', $post_id );
			$stack             = array_slice( itsc_cases_shortcode_get_repeater_items( 'stack', $post_id ), 0, 4 );
			?>
			<a class="itsc-cases-card" href="<?php the_permalink(); ?>">
				<?php if ( $highlight ) : ?>
					<div class="itsc-cases-card-highlight"><?php echo esc_html( implode( ' / ', $highlight ) ); ?></div>
				<?php endif; ?>
				<h3 class="itsc-cases-card-title"><?php echo itsc_cases_shortcode_escape_title( get_the_title() ); ?></h3>
				<?php if ( $short_description ) : ?>
					<p class="itsc-cases-card-summary"><?php echo esc_html( wp_trim_words( $short_description, 26 ) ); ?></p>
				<?php endif; ?>
				<?php if ( $stack ) : ?>
					<div class="itsc-cases-card-stack">
						<?php foreach ( $stack as $stack_item ) : ?>
							<span class="itsc-cases-card-stack-item"><?php echo esc_html( $stack_item ); ?></span>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</a>
			<?php
		endwhile;
		wp_reset_postdata();
		?>
	</div>
	<?php

	return ob_get_clean();
}

add_shortcode( 'itsc_cases', 'itsc_cases_shortcode_render' );
add_shortcode( 'cases_cards', 'itsc_cases_shortcode_render' );
add_shortcode( 'case_cards', 'itsc_cases_shortcode_render' );
