<?php
/**
 * Plugin Name: ITSC Case ACF Template
 * Description: Renders single case posts from ACF fields.
 * Version: 1.0.0
 * Author: ITSC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function itsc_is_case_post( $post_id = 0 ) {
	$post_id = $post_id ?: get_the_ID();

	return $post_id && is_singular( 'post' ) && has_category( 'cases', $post_id );
}

function itsc_case_get_field( $field, $post_id ) {
	if ( ! function_exists( 'get_field' ) ) {
		return null;
	}

	return get_field( $field, $post_id );
}

function itsc_case_get_repeater_items( $field, $post_id, $sub_field = 'item' ) {
	$rows = itsc_case_get_field( $field, $post_id );
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

function itsc_case_render_list( $items, $class = '' ) {
	if ( ! $items ) {
		return '';
	}

	$html = '<ul class="itsc-case-list ' . esc_attr( $class ) . '">';
	foreach ( $items as $item ) {
		$html .= '<li>' . esc_html( $item ) . '</li>';
	}
	$html .= '</ul>';

	return $html;
}

function itsc_case_escape_title( $title ) {
	$escaped = esc_html( $title );

	return preg_replace( '/(?<=[[:alnum:]])-(?=[[:alnum:]])/', '&#8209;', $escaped );
}

function itsc_case_render_template( $post_id ) {
	$title                 = get_the_title( $post_id );
	$short_description     = itsc_case_get_field( 'short_description', $post_id );
	$what_was_implemented = itsc_case_get_repeater_items( 'what_was_implemented', $post_id );
	$results               = itsc_case_get_repeater_items( 'results', $post_id );
	$stack                 = itsc_case_get_repeater_items( 'stack', $post_id );
	$highlight             = itsc_case_get_repeater_items( 'highlight', $post_id );
	$screenshot            = itsc_case_get_field( 'architecture_screenshot', $post_id );
	$screenshot_url        = is_array( $screenshot ) && ! empty( $screenshot['url'] ) ? $screenshot['url'] : '';
	$screenshot_preview    = is_array( $screenshot ) && ! empty( $screenshot['sizes']['large'] ) ? $screenshot['sizes']['large'] : $screenshot_url;
	$screenshot_alt        = is_array( $screenshot ) && ! empty( $screenshot['alt'] ) ? $screenshot['alt'] : $title;
	$screenshot_lightbox   = 'itsc-case-screenshot-' . absint( $post_id );

	ob_start();
	?>
	<article class="itsc-case">
		<header class="itsc-case-hero">
			<?php if ( $highlight ) : ?>
				<div class="itsc-case-eyebrow"><?php echo esc_html( implode( ' / ', $highlight ) ); ?></div>
			<?php endif; ?>
			<h2><?php echo itsc_case_escape_title( $title ); ?></h2>
			<?php if ( $short_description ) : ?>
				<p class="itsc-case-summary"><?php echo esc_html( $short_description ); ?></p>
			<?php endif; ?>
			<?php if ( $stack ) : ?>
				<div class="itsc-case-stack">
					<span class="itsc-case-stack-label"><?php esc_html_e( 'Stack:', 'itsc' ); ?></span>
					<div class="itsc-case-stack-tags">
						<?php foreach ( $stack as $stack_item ) : ?>
							<span class="itsc-case-stack-tag"><?php echo esc_html( $stack_item ); ?></span>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( $screenshot_url ) : ?>
				<figure class="itsc-case-screenshot-preview">
					<a href="#<?php echo esc_attr( $screenshot_lightbox ); ?>" aria-label="<?php esc_attr_e( 'Open architecture preview', 'itsc' ); ?>">
						<img src="<?php echo esc_url( $screenshot_preview ); ?>" alt="<?php echo esc_attr( $screenshot_alt ); ?>">
					</a>
				</figure>
				<div id="<?php echo esc_attr( $screenshot_lightbox ); ?>" class="itsc-case-lightbox" aria-hidden="true">
					<a class="itsc-case-lightbox-backdrop" href="#" aria-label="<?php esc_attr_e( 'Close architecture preview', 'itsc' ); ?>"></a>
					<figure class="itsc-case-lightbox-content">
						<a class="itsc-case-lightbox-close" href="#" aria-label="<?php esc_attr_e( 'Close architecture preview', 'itsc' ); ?>">×</a>
						<img src="<?php echo esc_url( $screenshot_url ); ?>" alt="<?php echo esc_attr( $screenshot_alt ); ?>">
					</figure>
				</div>
			<?php endif; ?>
		</header>

		<div class="itsc-case-grid">
			<?php if ( $what_was_implemented ) : ?>
				<section class="itsc-case-section">
					<h3><?php esc_html_e( 'What was implemented', 'itsc' ); ?></h3>
					<?php echo itsc_case_render_list( $what_was_implemented, 'itsc-case-list-implemented' ); ?>
				</section>
			<?php endif; ?>

			<?php if ( $results ) : ?>
				<section class="itsc-case-section">
					<h3><?php esc_html_e( 'Result', 'itsc' ); ?></h3>
					<?php echo itsc_case_render_list( $results, 'itsc-case-list-results' ); ?>
				</section>
			<?php endif; ?>
		</div>

	</article>
	<?php

	return ob_get_clean();
}

add_filter(
	'the_content',
	function ( $content ) {
		if ( is_admin() || ! in_the_loop() || ! is_main_query() || ! itsc_is_case_post() ) {
			return $content;
		}

		return itsc_case_render_template( get_the_ID() );
	},
	30
);

add_filter(
	'astra_single_post_navigation_enabled',
	function ( $enabled ) {
		if ( itsc_is_case_post( get_queried_object_id() ) ) {
			return false;
		}

		return $enabled;
	}
);

add_filter(
	'astra_single_post_meta',
	function ( $meta ) {
		if ( itsc_is_case_post( get_queried_object_id() ) ) {
			return '';
		}

		return $meta;
	}
);

add_action(
	'wp_head',
	function () {
		if ( ! itsc_is_case_post( get_queried_object_id() ) ) {
			return;
		}
		?>
		<style id="itsc-case-acf-template-css">
			body.single-post.category-cases .entry-header,
			body.single-post.category-cases .entry-meta,
			body.single-post.category-cases .ast-breadcrumbs-wrapper,
			body.single-post.category-cases .post-navigation,
			body.single-post.category-cases .ast-single-post-order,
			body.single-post.category-cases .ast-single-post-order + .entry-meta {
				display: none !important;
			}
			.itsc-case {
				max-width: 1120px;
				margin: 0 auto;
				padding: 72px 20px 88px;
			}
			.itsc-case-hero {
				max-width: 880px;
				margin-bottom: 44px;
			}
			.itsc-case-eyebrow {
				margin-bottom: 14px;
				color: var(--ast-global-color-2);
				font-size: 14px;
				font-weight: 700;
				text-transform: uppercase;
			}
			.itsc-case h2 {
				margin: 0 0 20px;
				font-size: clamp(32px, 5vw, 52px);
				line-height: 1.15;
				overflow-wrap: normal;
				word-break: normal;
				hyphens: none;
			}
			.itsc-case-summary {
				max-width: 780px;
				font-size: 20px;
				line-height: 1.65;
			}
			.itsc-case-stack {
				margin-top: 26px;
				padding: 18px 0;
				border-top: 1px solid var(--ast-border-color);
				border-bottom: 1px solid var(--ast-border-color);
				line-height: 1.7;
			}
			.itsc-case-stack-label {
				display: block;
				margin-bottom: 12px;
				font-weight: 700;
			}
			.itsc-case-stack-tags {
				display: flex;
				flex-wrap: wrap;
				gap: 8px;
			}
			.itsc-case-stack-tag {
				display: inline-flex;
				align-items: center;
				min-height: 28px;
				padding: 3px 10px;
				border: 1px solid var(--ast-border-color);
				background: var(--ast-global-color-4);
				border-radius: 999px;
				font-size: 13px;
				font-weight: 600;
				line-height: 1.3;
				transition: background-color 160ms ease, border-color 160ms ease;
			}
			.itsc-case-stack-tag:hover {
				border-color: var(--ast-global-color-2);
				background: var(--ast-global-color-5);
			}
			.itsc-case-screenshot-preview {
				max-width: 500px;
				margin: 28px auto 0;
			}
			.itsc-case-screenshot-preview a {
				display: block;
				cursor: zoom-in;
			}
			.itsc-case-screenshot-preview img {
				display: block;
				width: 100%;
				height: auto;
				max-height: 360px;
				object-fit: cover;
				object-position: top center;
				border: 1px solid var(--ast-border-color);
				background: var(--ast-global-color-5);
				box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
				transition: border-color 160ms ease, box-shadow 160ms ease;
			}
			.itsc-case-screenshot-preview a:hover img {
				border-color: var(--ast-global-color-2);
				box-shadow: 0 16px 36px rgba(15, 23, 42, 0.12);
			}
			.itsc-case-lightbox {
				position: fixed;
				inset: 0;
				z-index: 99999;
				display: none;
				padding: 32px;
			}
			.itsc-case-lightbox:target {
				display: flex;
				align-items: center;
				justify-content: center;
			}
			.itsc-case-lightbox-backdrop {
				position: absolute;
				inset: 0;
				background: rgba(15, 23, 42, 0.82);
			}
			.itsc-case-lightbox-content {
				position: relative;
				z-index: 1;
				width: min(1180px, 100%);
				max-height: calc(100vh - 64px);
				margin: 0;
				background: var(--ast-global-color-5);
				box-shadow: 0 24px 70px rgba(0, 0, 0, 0.35);
			}
			.itsc-case-lightbox-content img {
				display: block;
				width: 100%;
				max-height: calc(100vh - 64px);
				object-fit: contain;
			}
			.itsc-case-lightbox-close {
				position: absolute;
				top: 10px;
				right: 10px;
				z-index: 2;
				display: inline-flex;
				align-items: center;
				justify-content: center;
				width: 34px;
				height: 34px;
				border-radius: 50%;
				background: rgba(255, 255, 255, 0.92);
				color: var(--ast-global-color-2);
				font-size: 26px;
				line-height: 1;
				text-decoration: none;
			}
			.itsc-case-grid {
				display: grid;
				grid-template-columns: repeat(2, minmax(0, 1fr));
				gap: 40px;
			}
			.itsc-case-section h3 {
				margin-bottom: 18px;
				font-size: 28px;
			}
			.itsc-case-list {
				margin: 0;
				padding-left: 20px;
			}
			.itsc-case-list li {
				margin-bottom: 12px;
			}
			.itsc-case-list-implemented {
				color: var(--ast-global-color-3);
			}
			.itsc-case-list-results {
				color: var(--ast-global-color-2);
				font-weight: 500;
			}
			@media (max-width: 767px) {
				.itsc-case {
					padding-top: 44px;
					padding-bottom: 56px;
				}
				.itsc-case-screenshot-preview img {
					max-height: 260px;
				}
				.itsc-case-lightbox {
					padding: 16px;
				}
				.itsc-case-lightbox-content,
				.itsc-case-lightbox-content img {
					max-height: calc(100vh - 32px);
				}
				.itsc-case-grid {
					grid-template-columns: 1fr;
					gap: 28px;
				}
				.itsc-case-summary {
					font-size: 18px;
				}
			}
		</style>
		<?php
	}
);
