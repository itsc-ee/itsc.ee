<?php
/**
 * Plugin Name: Services ACF Template
 * Description: Renders single service posts from ACF fields.
 * Version: 1.0.0
 * Author: ITSC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function services_acf_is_service_post( $post_id = 0 ) {
	$post_id = $post_id ?: get_the_ID();

	return $post_id && is_singular( 'post' ) && has_category( 'services', $post_id );
}

function services_acf_get_field( $field, $post_id ) {
	if ( ! function_exists( 'get_field' ) ) {
		return null;
	}

	return get_field( $field, $post_id );
}

function services_acf_get_repeater_items( $field, $post_id, $sub_field = 'item' ) {
	$rows = services_acf_get_field( $field, $post_id );
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

function services_acf_get_groups( $field, $post_id ) {
	$rows = services_acf_get_field( $field, $post_id );
	if ( empty( $rows ) || ! is_array( $rows ) ) {
		return array();
	}

	$items = array();
	foreach ( $rows as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$title       = isset( $row['title'] ) ? trim( (string) $row['title'] ) : '';
		$description = isset( $row['description'] ) ? trim( (string) $row['description'] ) : '';

		if ( '' !== $title || '' !== $description ) {
			$items[] = array(
				'title'       => $title,
				'description' => $description,
			);
		}
	}

	return $items;
}

function services_acf_escape_title( $title ) {
	$escaped = esc_html( $title );

	return preg_replace( '/(?<=[[:alnum:]])-(?=[[:alnum:]])/', '&#8209;', $escaped );
}

function services_acf_render_chips( $items, $class = '' ) {
	if ( ! $items ) {
		return '';
	}

	$html = '<div class="services-acf-chips ' . esc_attr( $class ) . '">';
	foreach ( $items as $item ) {
		$html .= '<span class="services-acf-chip">' . esc_html( $item ) . '</span>';
	}
	$html .= '</div>';

	return $html;
}

function services_acf_get_wp_tags( $post_id ) {
	$terms = get_the_terms( $post_id, 'post_tag' );
	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return array();
	}

	return wp_list_pluck( $terms, 'name' );
}

function services_acf_render_list( $items ) {
	if ( ! $items ) {
		return '';
	}

	$html = '<ul class="services-acf-list">';
	foreach ( $items as $item ) {
		$html .= '<li>' . esc_html( $item ) . '</li>';
	}
	$html .= '</ul>';

	return $html;
}

function services_acf_render_related_cases( $posts ) {
	if ( empty( $posts ) || ! is_array( $posts ) ) {
		return '';
	}

	$html = '<div class="services-acf-related-cases">';
	foreach ( $posts as $post ) {
		if ( ! $post instanceof WP_Post ) {
			continue;
		}

		$highlight = services_acf_get_repeater_items( 'highlight', $post->ID );

		$html .= '<a class="services-acf-related-case" href="' . esc_url( get_permalink( $post ) ) . '">';
		if ( $highlight ) {
			$html .= '<span class="services-acf-related-case-highlight">' . esc_html( implode( ' / ', $highlight ) ) . '</span>';
		}
		$html .= '<span class="services-acf-related-case-title">' . services_acf_escape_title( get_the_title( $post ) ) . '</span>';
		$html .= '</a>';
	}
	$html .= '</div>';

	return $html;
}

function services_acf_render_template( $post_id ) {
	$title                = get_the_title( $post_id );
	$short_description    = services_acf_get_field( 'short_description', $post_id );
	$tags                 = services_acf_get_wp_tags( $post_id );
	$help_groups          = services_acf_get_groups( 'what_can_i_help_with', $post_id );
	$typical_solutions    = services_acf_get_repeater_items( 'typical_solutions', $post_id );
	$technologies         = services_acf_get_repeater_items( 'technologies', $post_id );
	$expertise            = services_acf_get_repeater_items( 'expertise', $post_id );
	$related_cases        = services_acf_get_field( 'related_cases', $post_id );

	ob_start();
	?>
	<article class="services-acf-service">
		<header class="services-acf-hero">
			<div class="services-acf-eyebrow"><?php esc_html_e( 'Service', 'services-acf' ); ?></div>
			<h2><?php echo services_acf_escape_title( $title ); ?></h2>
			<?php if ( $short_description ) : ?>
				<p class="services-acf-summary"><?php echo esc_html( $short_description ); ?></p>
			<?php endif; ?>
			<?php echo services_acf_render_chips( $tags, 'services-acf-tags' ); ?>
		</header>

		<?php if ( $help_groups ) : ?>
			<section class="services-acf-section">
				<h3><?php esc_html_e( 'What can I help with', 'services-acf' ); ?></h3>
				<div class="services-acf-help-grid">
					<?php foreach ( $help_groups as $group ) : ?>
						<div class="services-acf-help-item">
							<?php if ( $group['title'] ) : ?>
								<h4><?php echo esc_html( $group['title'] ); ?></h4>
							<?php endif; ?>
							<?php if ( $group['description'] ) : ?>
								<p><?php echo esc_html( $group['description'] ); ?></p>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>

		<div class="services-acf-two-column">
			<?php if ( $typical_solutions ) : ?>
				<section class="services-acf-section">
					<h3><?php esc_html_e( 'Typical solutions', 'services-acf' ); ?></h3>
					<?php echo services_acf_render_list( $typical_solutions ); ?>
				</section>
			<?php endif; ?>

			<?php if ( $technologies || $expertise ) : ?>
				<section class="services-acf-section">
					<?php if ( $technologies ) : ?>
						<h3><?php esc_html_e( 'Technologies', 'services-acf' ); ?></h3>
						<?php echo services_acf_render_chips( $technologies, 'services-acf-technologies' ); ?>
					<?php endif; ?>

					<?php if ( $expertise ) : ?>
						<h3 class="services-acf-subsection-title"><?php esc_html_e( 'Expertise', 'services-acf' ); ?></h3>
						<?php echo services_acf_render_chips( $expertise, 'services-acf-expertise' ); ?>
					<?php endif; ?>
				</section>
			<?php endif; ?>
		</div>

		<?php if ( $related_cases ) : ?>
			<section class="services-acf-section">
				<h3><?php esc_html_e( 'Related cases', 'services-acf' ); ?></h3>
				<?php echo services_acf_render_related_cases( $related_cases ); ?>
			</section>
		<?php endif; ?>
	</article>
	<?php

	return ob_get_clean();
}

add_filter(
	'the_content',
	function ( $content ) {
		if ( is_admin() || ! in_the_loop() || ! is_main_query() || ! services_acf_is_service_post() ) {
			return $content;
		}

		return services_acf_render_template( get_the_ID() );
	},
	30
);

add_filter(
	'astra_single_post_navigation_enabled',
	function ( $enabled ) {
		if ( services_acf_is_service_post( get_queried_object_id() ) ) {
			return false;
		}

		return $enabled;
	}
);

add_filter(
	'astra_single_post_meta',
	function ( $meta ) {
		if ( services_acf_is_service_post( get_queried_object_id() ) ) {
			return '';
		}

		return $meta;
	}
);

add_filter(
	'astra_page_layout',
	function ( $layout ) {
		if ( services_acf_is_service_post( get_queried_object_id() ) ) {
			return 'no-sidebar';
		}

		return $layout;
	}
);

add_filter(
	'astra_site_content_layout',
	function ( $layout ) {
		if ( services_acf_is_service_post( get_queried_object_id() ) ) {
			return 'page-builder';
		}

		return $layout;
	}
);

add_action(
	'wp_head',
	function () {
		if ( ! services_acf_is_service_post( get_queried_object_id() ) ) {
			return;
		}
		?>
		<style id="services-acf-template-css">
			body.single-post.category-services .entry-header,
			body.single-post.category-services .entry-meta,
			body.single-post.category-services .ast-breadcrumbs-wrapper,
			body.single-post.category-services .post-navigation,
			body.single-post.category-services .ast-single-post-order,
			body.single-post.category-services .ast-single-post-order + .entry-meta {
				display: none !important;
			}
			.services-acf-service {
				max-width: 1120px;
				margin: 0 auto;
				padding: 72px 20px 88px;
			}
			.services-acf-hero {
				max-width: 880px;
				margin-bottom: 44px;
			}
			.services-acf-eyebrow {
				margin-bottom: 14px;
				color: var(--ast-global-color-2);
				font-size: 14px;
				font-weight: 700;
				text-transform: uppercase;
			}
			.services-acf-service h2 {
				margin: 0 0 20px;
				font-size: clamp(32px, 5vw, 52px);
				line-height: 1.15;
				overflow-wrap: normal;
				word-break: normal;
				hyphens: none;
			}
			.services-acf-summary {
				max-width: 780px;
				font-size: 20px;
				line-height: 1.65;
			}
			.services-acf-chips {
				display: flex;
				flex-wrap: wrap;
				gap: 8px;
				margin-top: 24px;
			}
			.services-acf-chip {
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
			}
			.services-acf-section {
				margin-top: 44px;
			}
			.services-acf-section h3 {
				margin-bottom: 18px;
				font-size: 28px;
			}
			.services-acf-section h3.services-acf-subsection-title {
				margin-top: 28px;
			}
			.services-acf-help-grid,
			.services-acf-related-cases {
				display: grid;
				grid-template-columns: repeat(2, minmax(0, 1fr));
				gap: 18px;
			}
			.services-acf-help-item,
			.services-acf-related-case {
				display: block;
				padding: 22px;
				border: 1px solid var(--ast-border-color);
				background: var(--ast-global-color-5);
				text-decoration: none;
			}
			.services-acf-help-item h4 {
				margin: 0 0 10px;
				font-size: 19px;
			}
			.services-acf-help-item p {
				margin: 0;
				color: var(--ast-global-color-3);
				line-height: 1.65;
			}
			.services-acf-two-column {
				display: grid;
				grid-template-columns: repeat(2, minmax(0, 1fr));
				gap: 40px;
			}
			.services-acf-list {
				margin: 0;
				padding-left: 20px;
			}
			.services-acf-list li {
				margin-bottom: 12px;
			}
			.services-acf-related-case {
				color: inherit;
				transition: border-color 160ms ease, box-shadow 160ms ease, transform 160ms ease;
			}
			.services-acf-related-case:hover,
			.services-acf-related-case:focus {
				border-color: var(--ast-global-color-2);
				box-shadow: 0 16px 36px rgba(15, 23, 42, 0.1);
				color: inherit;
				transform: translateY(-2px);
			}
			.services-acf-related-case-highlight {
				display: block;
				margin-bottom: 10px;
				color: var(--ast-global-color-2);
				font-size: 13px;
				font-weight: 700;
				text-transform: uppercase;
			}
			.services-acf-related-case-title {
				display: block;
				font-size: 20px;
				font-weight: 700;
				line-height: 1.3;
			}
			@media (max-width: 767px) {
				.services-acf-service {
					padding-top: 44px;
					padding-bottom: 56px;
				}
				.services-acf-help-grid,
				.services-acf-related-cases,
				.services-acf-two-column {
					grid-template-columns: 1fr;
					gap: 24px;
				}
				.services-acf-summary {
					font-size: 18px;
				}
			}
		</style>
		<?php
	}
);
