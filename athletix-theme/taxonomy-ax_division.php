<?php
/**
 * Division term archive: the division's teams.
 *
 * @package Athletix_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$athletix_term = get_queried_object();
$athletix_id   = ( $athletix_term && isset( $athletix_term->term_id ) ) ? (int) $athletix_term->term_id : 0;
?>
<header class="ax-page-header">
	<p class="ax-eyebrow"><?php esc_html_e( 'Division', 'athletix-theme' ); ?></p>
	<h1 class="ax-entry-title"><?php echo esc_html( $athletix_term ? $athletix_term->name : '' ); ?></h1>
	<?php if ( term_description() ) : ?>
		<div class="ax-archive-desc"><?php echo wp_kses_post( term_description() ); ?></div>
	<?php endif; ?>
</header>

<?php
athletix_theme_taxonomy_teams( $athletix_id, __( 'Teams', 'athletix-theme' ), 'ax_division' );

get_footer();
