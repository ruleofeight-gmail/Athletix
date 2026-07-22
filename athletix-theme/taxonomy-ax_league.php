<?php
/**
 * League term archive: table + fixtures + teams for the league.
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
	<p class="ax-eyebrow"><?php esc_html_e( 'League', 'athletix-theme' ); ?></p>
	<h1 class="ax-entry-title"><?php echo esc_html( $athletix_term ? $athletix_term->name : '' ); ?></h1>
	<?php if ( term_description() ) : ?>
		<div class="ax-archive-desc"><?php echo wp_kses_post( term_description() ); ?></div>
	<?php endif; ?>
</header>

<div class="ax-grid ax-grid--2 ax-section">
	<section>
		<div class="ax-section__head"><h2><?php esc_html_e( 'Table', 'athletix-theme' ); ?></h2></div>
		<div class="ax-plugin-embed"><?php athletix_theme_shortcode( 'athletix_standings', '[athletix_standings league="' . $athletix_id . '"]' ); ?></div>
	</section>
	<section>
		<div class="ax-section__head"><h2><?php esc_html_e( 'Fixtures', 'athletix-theme' ); ?></h2></div>
		<div class="ax-plugin-embed"><?php athletix_theme_shortcode( 'athletix_schedule', '[athletix_schedule league="' . $athletix_id . '" limit="10"]' ); ?></div>
	</section>
</div>

<?php
athletix_theme_taxonomy_teams( $athletix_id, __( 'Teams', 'athletix-theme' ) );

get_footer();
