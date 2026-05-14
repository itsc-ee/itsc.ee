<?php
/**
 * Plugin Name: Site Header Code
 * Description: Adds site-wide custom tags to the document head.
 */

add_action('wp_head', function () {
	?>
	<link rel="icon" href="/wp-content/uploads/favicon-light.svg">
	<link rel="icon" href="/wp-content/uploads/favicon-light.svg" media="(prefers-color-scheme: light)">
	<link rel="icon" href="/wp-content/uploads/favicon-dark.svg" media="(prefers-color-scheme: dark)">
	<?php
}, 1);
