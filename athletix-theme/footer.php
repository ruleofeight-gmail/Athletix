<?php
/**
 * Theme footer.
 *
 * @package Athletix_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
	</div><!-- .ax-container -->
</main><!-- .ax-main -->
<footer class="ax-site-footer">
	<div class="ax-container ax-site-footer__inner">
		<div class="ax-footer-brand">
			<span class="ax-site-title"><?php bloginfo( 'name' ); ?></span>
			<p><?php echo esc_html( get_bloginfo( 'description' ) ? get_bloginfo( 'description' ) : __( 'Standings, fixtures and stats — powered by Athletix.', 'athletix-theme' ) ); ?></p>
		</div>

		<?php if ( is_active_sidebar( 'footer-1' ) ) : ?>
			<div class="widget-area"><?php dynamic_sidebar( 'footer-1' ); ?></div>
		<?php endif; ?>

		<?php if ( is_active_sidebar( 'footer-2' ) ) : ?>
			<div class="widget-area"><?php dynamic_sidebar( 'footer-2' ); ?></div>
		<?php endif; ?>

		<?php if ( ! is_active_sidebar( 'footer-1' ) && ! is_active_sidebar( 'footer-2' ) && has_nav_menu( 'primary' ) ) : ?>
			<nav aria-label="<?php esc_attr_e( 'Footer', 'athletix-theme' ); ?>">
				<h4><?php esc_html_e( 'Explore', 'athletix-theme' ); ?></h4>
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => false,
						'depth'          => 1,
					)
				);
				?>
			</nav>
		<?php endif; ?>
	</div>

	<div class="ax-colophon">
		<div class="ax-container">
			<?php
			printf(
				/* translators: 1: year, 2: site name. */
				esc_html__( '© %1$s %2$s — built with the Athletix theme.', 'athletix-theme' ),
				esc_html( gmdate( 'Y' ) ),
				esc_html( get_bloginfo( 'name' ) )
			);
			?>
		</div>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
