<?php
/**
 * Athletix companion theme functions.
 *
 * @package Athletix_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'athletix_theme_setup' ) ) {
	/**
	 * Theme setup.
	 *
	 * @return void
	 */
	function athletix_theme_setup() {
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support(
			'html5',
			array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' )
		);
		add_theme_support( 'custom-logo' );

		register_nav_menus(
			array(
				'primary' => __( 'Primary Menu', 'athletix-theme' ),
			)
		);
	}
}
add_action( 'after_setup_theme', 'athletix_theme_setup' );

if ( ! function_exists( 'athletix_theme_assets' ) ) {
	/**
	 * Enqueue theme styles (and the plugin stylesheet when available).
	 *
	 * @return void
	 */
	function athletix_theme_assets() {
		wp_enqueue_style( 'athletix-theme', get_stylesheet_uri(), array(), '1.0.0' );

		// Piggyback the plugin's stylesheet on Athletix views if it registered one.
		if ( wp_style_is( 'athletix', 'registered' ) ) {
			wp_enqueue_style( 'athletix' );
		}
	}
}
add_action( 'wp_enqueue_scripts', 'athletix_theme_assets' );

if ( ! function_exists( 'athletix_theme_shortcode' ) ) {
	/**
	 * Output a plugin shortcode only if it is registered (plugin active).
	 *
	 * @param string $tag  Shortcode tag.
	 * @param string $code Full shortcode string.
	 * @return void
	 */
	function athletix_theme_shortcode( $tag, $code ) {
		if ( shortcode_exists( $tag ) ) {
			echo do_shortcode( $code ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- shortcode output.
		}
	}
}
