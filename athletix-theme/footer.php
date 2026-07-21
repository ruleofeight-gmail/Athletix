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
</div><!-- .ax-main -->
<footer class="ax-site-footer">
	<div class="ax-container">
		<?php
		printf(
			/* translators: 1: year, 2: site name. */
			esc_html__( '© %1$s %2$s', 'athletix-theme' ),
			esc_html( gmdate( 'Y' ) ),
			esc_html( get_bloginfo( 'name' ) )
		);
		?>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
