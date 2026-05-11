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
		#primary.cases-cards-archive,
		.cases-cards-archive {
			box-sizing: border-box;
			width: 100%;
			max-width: var(--ast-normal-container-width, 1200px);
			margin: 0 auto;
			padding: 72px 20px 88px;
		}
		.cases-cards-archive-header {
			max-width: 760px;
			margin-bottom: 34px;
		}
		.cases-cards-archive-title {
			margin: 0 0 14px;
			font-size: clamp(34px, 5vw, 58px);
			line-height: 1.12;
		}
		.cases-cards-archive-description {
			margin: 0;
			color: var(--ast-global-color-3);
			font-size: 18px;
			line-height: 1.65;
		}
		.itsc-cases-filters {
			display: flex;
			flex-wrap: wrap;
			gap: 8px;
			margin: 0 0 24px;
		}
		.itsc-cases-filter {
			display: inline-flex;
			align-items: center;
			min-height: 32px;
			padding: 5px 12px;
			border: 1px solid var(--ast-border-color);
			background: var(--ast-global-color-5);
			border-radius: 999px;
			color: var(--ast-global-color-3);
			cursor: pointer;
			font: inherit;
			font-size: 13px;
			font-weight: 600;
			line-height: 1.3;
			transition: background-color 160ms ease, border-color 160ms ease, color 160ms ease;
		}
		.itsc-cases-filter:hover,
		.itsc-cases-filter.is-active {
			border-color: var(--ast-global-color-2);
			background: var(--ast-global-color-4);
			color: var(--ast-global-color-2);
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
		.itsc-cases-card.is-hidden {
			display: none;
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
			.cases-cards-archive {
				padding-top: 44px;
				padding-right: 20px;
				padding-bottom: 56px;
				padding-left: 20px;
			}
			.itsc-cases-cards {
				grid-template-columns: 1fr;
			}
		}
	</style>
	<script>
		(function() {
			document.addEventListener('click', function(event) {
				var button = event.target.closest('.itsc-cases-filter');
				if (!button) {
					return;
				}

				var wrapper = button.closest('.itsc-cases-shortcode');
				if (!wrapper) {
					return;
				}

				var filter = button.getAttribute('data-case-filter');
				var buttons = wrapper.querySelectorAll('.itsc-cases-filter');
				var cards = wrapper.querySelectorAll('.itsc-cases-card');

				buttons.forEach(function(item) {
					item.classList.toggle('is-active', item === button);
				});

				cards.forEach(function(card) {
					var highlights = (card.getAttribute('data-case-highlights') || '').split(' ');
					card.classList.toggle('is-hidden', filter !== 'all' && highlights.indexOf(filter) === -1);
				});
			});
		})();
	</script>
	<?php

	return ob_get_clean();
}

function itsc_cases_shortcode_render( $atts ) {
	$atts = shortcode_atts(
		array(
			'limit'   => '-1',
			'columns' => '3',
			'ids'     => '',
			'category' => 'cases',
			'filters' => 'yes',
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
	$filters = ! in_array( strtolower( $atts['filters'] ), array( '0', 'false', 'no', 'off' ), true );
	$category = sanitize_title( $atts['category'] );

	if ( 'current' === $category && is_category() ) {
		$category = get_queried_object()->slug;
	}

	if ( '' === $category ) {
		$category = 'cases';
	}

	$query_args = array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => $limit,
		'category_name'       => $category,
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

	$cases      = array();
	$highlights = array();

	while ( $query->have_posts() ) {
		$query->the_post();

		$post_id           = get_the_ID();
		$case_highlights   = itsc_cases_shortcode_get_repeater_items( 'highlight', $post_id );
		$short_description = function_exists( 'get_field' ) ? get_field( 'short_description', $post_id ) : '';
		$stack             = array_slice( itsc_cases_shortcode_get_repeater_items( 'stack', $post_id ), 0, 4 );

		foreach ( $case_highlights as $highlight ) {
			$highlight_key = sanitize_title( $highlight );
			if ( $highlight_key ) {
				$highlights[ $highlight_key ] = $highlight;
			}
		}

		$cases[] = array(
			'id'                => $post_id,
			'title'             => get_the_title(),
			'url'               => get_permalink(),
			'short_description' => $short_description,
			'highlights'        => $case_highlights,
			'stack'             => $stack,
		);
	}
	wp_reset_postdata();

	ob_start();
	echo itsc_cases_shortcode_styles();
	?>
	<div class="itsc-cases-shortcode">
		<?php if ( $filters && $highlights ) : ?>
			<div class="itsc-cases-filters" aria-label="<?php esc_attr_e( 'Filter cases by highlight', 'cases-cards' ); ?>">
				<button class="itsc-cases-filter is-active" type="button" data-case-filter="all"><?php esc_html_e( 'All', 'cases-cards' ); ?></button>
				<?php foreach ( $highlights as $highlight_key => $highlight_label ) : ?>
					<button class="itsc-cases-filter" type="button" data-case-filter="<?php echo esc_attr( $highlight_key ); ?>"><?php echo esc_html( $highlight_label ); ?></button>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<div class="itsc-cases-cards" style="<?php echo esc_attr( '--itsc-cases-columns:' . $columns ); ?>">
			<?php foreach ( $cases as $case ) : ?>
				<?php
				$highlight_keys = array();
				foreach ( $case['highlights'] as $highlight ) {
					$highlight_key = sanitize_title( $highlight );
					if ( $highlight_key ) {
						$highlight_keys[] = $highlight_key;
					}
				}
				?>
				<a class="itsc-cases-card" href="<?php echo esc_url( $case['url'] ); ?>" data-case-highlights="<?php echo esc_attr( implode( ' ', $highlight_keys ) ); ?>">
					<?php if ( $case['highlights'] ) : ?>
						<div class="itsc-cases-card-highlight"><?php echo esc_html( implode( ' / ', $case['highlights'] ) ); ?></div>
					<?php endif; ?>
					<h3 class="itsc-cases-card-title"><?php echo itsc_cases_shortcode_escape_title( $case['title'] ); ?></h3>
					<?php if ( $case['short_description'] ) : ?>
						<p class="itsc-cases-card-summary"><?php echo esc_html( wp_trim_words( $case['short_description'], 26 ) ); ?></p>
					<?php endif; ?>
					<?php if ( $case['stack'] ) : ?>
						<div class="itsc-cases-card-stack">
							<?php foreach ( $case['stack'] as $stack_item ) : ?>
								<span class="itsc-cases-card-stack-item"><?php echo esc_html( $stack_item ); ?></span>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
	<?php

	return ob_get_clean();
}

add_shortcode( 'itsc_cases', 'itsc_cases_shortcode_render' );
add_shortcode( 'cases_cards', 'itsc_cases_shortcode_render' );
add_shortcode( 'case_cards', 'itsc_cases_shortcode_render' );
add_shortcode( 'category_cards', 'itsc_cases_shortcode_render' );

function cases_cards_is_cases_archive() {
	return is_category( 'cases' );
}

add_filter(
	'astra_page_layout',
	function ( $layout ) {
		if ( cases_cards_is_cases_archive() ) {
			return 'no-sidebar';
		}

		return $layout;
	}
);

add_filter(
	'astra_site_content_layout',
	function ( $layout ) {
		if ( cases_cards_is_cases_archive() ) {
			return 'page-builder';
		}

		return $layout;
	}
);

add_filter(
	'astra_get_content_layout',
	function ( $layout ) {
		if ( cases_cards_is_cases_archive() ) {
			return 'page-builder';
		}

		return $layout;
	}
);

add_filter(
	'template_include',
	function ( $template ) {
		if ( cases_cards_is_cases_archive() ) {
			$archive_template = __DIR__ . '/templates/cases-cards-archive-template.php';

			if ( file_exists( $archive_template ) ) {
				return $archive_template;
			}
		}

		return $template;
	}
);
