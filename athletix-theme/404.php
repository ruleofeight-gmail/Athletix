<?php
/**
 * 404 template.
 *
 * @package Athletix_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<article class="ax-card">
	<h1 class="ax-entry-title"><?php esc_html_e( 'Page not found', 'athletix-theme' ); ?></h1>
	<p><?php esc_html_e( 'The page you were looking for could not be found.', 'athletix-theme' ); ?></p>
	<p><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Back to home', 'athletix-theme' ); ?></a></p>
</article>
<?php
get_footer();
