<?php
/**
 * Theme header.
 *
 * @package Athletix_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="screen-reader-text" href="#ax-content"><?php esc_html_e( 'Skip to content', 'athletix-theme' ); ?></a>
<header class="ax-site-header">
	<div class="ax-container ax-site-header__inner">
		<div class="ax-brand">
			<?php if ( has_custom_logo() ) : ?>
				<?php the_custom_logo(); ?>
			<?php else : ?>
				<span class="ax-brand__mark" aria-hidden="true"><?php echo esc_html( strtoupper( substr( get_bloginfo( 'name' ), 0, 1 ) ) ); ?></span>
			<?php endif; ?>
			<span>
				<span class="ax-site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></span>
				<?php $athletix_desc = get_bloginfo( 'description', 'display' ); ?>
				<?php if ( $athletix_desc ) : ?>
					<span class="ax-site-desc"><?php echo esc_html( $athletix_desc ); ?></span>
				<?php endif; ?>
			</span>
		</div>

		<button class="ax-nav-toggle" aria-expanded="false" aria-controls="ax-primary-nav">
			<span aria-hidden="true">&#9776;</span>
			<span class="screen-reader-text"><?php esc_html_e( 'Menu', 'athletix-theme' ); ?></span>
		</button>

		<nav id="ax-primary-nav" class="ax-nav" aria-label="<?php esc_attr_e( 'Primary', 'athletix-theme' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'fallback_cb'    => 'athletix_theme_nav_fallback',
					'depth'          => 1,
				)
			);
			?>
		</nav>
	</div>
</header>
<main id="ax-content" class="ax-main">
	<div class="ax-container">
