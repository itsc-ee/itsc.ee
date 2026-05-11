<?php
/**
 * Category archive template for case cards.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="primary" class="site-main cases-cards-archive">
	<header class="cases-cards-archive-header">
		<h1 class="cases-cards-archive-title"><?php single_cat_title(); ?></h1>
		<?php if ( category_description() ) : ?>
			<div class="cases-cards-archive-description"><?php echo wp_kses_post( category_description() ); ?></div>
		<?php endif; ?>
	</header>

	<?php echo do_shortcode( '[case_cards]' ); ?>
</main>

<?php
get_footer();
