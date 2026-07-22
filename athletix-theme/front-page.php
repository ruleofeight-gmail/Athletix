<?php
/**
 * Front page: hero + live league sections (when the plugin is active) + features.
 *
 * @package Athletix_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$athletix_active = shortcode_exists( 'athletix_standings' );
$athletix_teams  = post_type_exists( 'ax_team' ) ? get_post_type_archive_link( 'ax_team' ) : '';
$athletix_match  = post_type_exists( 'ax_match' ) ? get_post_type_archive_link( 'ax_match' ) : '';
?>

<section class="ax-hero" style="margin: -1px calc(50% - 50vw) 0; padding-left: calc(50vw - 50%); padding-right: calc(50vw - 50%);">
	<div class="ax-container ax-hero__inner">
		<p class="ax-eyebrow" style="color: rgba(255,255,255,.85);"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
		<h1><?php echo esc_html( get_bloginfo( 'description' ) ? get_bloginfo( 'description' ) : __( 'Your league. Standings, fixtures & stats.', 'athletix-theme' ) ); ?></h1>
		<p><?php esc_html_e( 'Follow every team, match and player in one place — live tables, upcoming fixtures and top scorers, always up to date.', 'athletix-theme' ); ?></p>
		<div class="ax-hero__actions">
			<?php if ( $athletix_match ) : ?>
				<a class="ax-btn ax-btn--primary" href="<?php echo esc_url( $athletix_match ); ?>"><?php esc_html_e( 'View fixtures', 'athletix-theme' ); ?></a>
			<?php endif; ?>
			<?php if ( $athletix_teams ) : ?>
				<a class="ax-btn ax-btn--ghost" href="<?php echo esc_url( $athletix_teams ); ?>"><?php esc_html_e( 'Browse teams', 'athletix-theme' ); ?></a>
			<?php endif; ?>
		</div>
	</div>
</section>

<?php if ( $athletix_active ) : ?>
	<div class="ax-grid ax-grid--2 ax-section">
		<section>
			<div class="ax-section__head"><h2><?php esc_html_e( 'League Table', 'athletix-theme' ); ?></h2></div>
			<div class="ax-plugin-embed"><?php athletix_theme_shortcode( 'athletix_standings', '[athletix_standings]' ); ?></div>
		</section>
		<section>
			<div class="ax-section__head"><h2><?php esc_html_e( 'Upcoming Fixtures', 'athletix-theme' ); ?></h2></div>
			<div class="ax-plugin-embed"><?php athletix_theme_shortcode( 'athletix_schedule', '[athletix_schedule limit="6"]' ); ?></div>
		</section>
	</div>
<?php endif; ?>

<section class="ax-section">
	<div class="ax-section__head">
		<div>
			<p class="ax-eyebrow"><?php esc_html_e( 'Everything you need', 'athletix-theme' ); ?></p>
			<h2><?php esc_html_e( 'Run the whole competition', 'athletix-theme' ); ?></h2>
		</div>
	</div>
	<div class="ax-grid ax-grid--3">
		<?php
		$athletix_features = array(
			array( '🏆', __( 'Standings', 'athletix-theme' ), __( 'Automatic league tables with configurable points and tie-breakers.', 'athletix-theme' ) ),
			array( '📅', __( 'Fixtures & results', 'athletix-theme' ), __( 'Schedule matches and record scores — tables update instantly.', 'athletix-theme' ) ),
			array( '👤', __( 'Player profiles', 'athletix-theme' ), __( 'Rosters, positions and per-match statistics for every player.', 'athletix-theme' ) ),
			array( '📊', __( 'Top scorers', 'athletix-theme' ), __( 'Leaderboards across goals, assists and any metric you track.', 'athletix-theme' ) ),
			array( '🗂️', __( 'Competitions', 'athletix-theme' ), __( 'Round-robin schedules and knockout brackets, generated for you.', 'athletix-theme' ) ),
			array( '⚡', __( 'Blocks & shortcodes', 'athletix-theme' ), __( 'Drop any view into a page with a block or a single shortcode.', 'athletix-theme' ) ),
		);
		foreach ( $athletix_features as $athletix_feature ) :
			?>
			<div class="ax-card ax-feature">
				<div class="ax-feature__icon" aria-hidden="true"><?php echo esc_html( $athletix_feature[0] ); ?></div>
				<h3><?php echo esc_html( $athletix_feature[1] ); ?></h3>
				<p><?php echo esc_html( $athletix_feature[2] ); ?></p>
			</div>
		<?php endforeach; ?>
	</div>
</section>

<?php
// Latest news / posts, if any exist.
$athletix_news = new WP_Query(
	array(
		'post_type'           => 'post',
		'posts_per_page'      => 3,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	)
);

if ( $athletix_news->have_posts() ) :
	?>
	<section class="ax-section">
		<div class="ax-section__head"><h2><?php esc_html_e( 'Latest News', 'athletix-theme' ); ?></h2></div>
		<div class="ax-grid ax-grid--3">
			<?php
			while ( $athletix_news->have_posts() ) :
				$athletix_news->the_post();
				?>
				<a class="ax-card ax-card--link" href="<?php the_permalink(); ?>">
					<?php if ( has_post_thumbnail() ) : ?>
						<span class="ax-card__media"><?php the_post_thumbnail( 'medium_large' ); ?></span>
					<?php endif; ?>
					<h3 class="ax-card__title"><?php the_title(); ?></h3>
					<p class="ax-card__meta"><?php echo esc_html( get_the_date() ); ?></p>
				</a>
				<?php
			endwhile;
			wp_reset_postdata();
			?>
		</div>
	</section>
	<?php
endif;

get_footer();
