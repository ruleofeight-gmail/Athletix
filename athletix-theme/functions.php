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

		add_theme_support( 'align-wide' );
		add_theme_support(
			'custom-logo',
			array(
				'height'      => 48,
				'width'       => 48,
				'flex-height' => true,
				'flex-width'  => true,
			)
		);
		add_editor_style( 'style.css' );

		register_nav_menus(
			array(
				'primary' => __( 'Primary Menu', 'athletix-theme' ),
			)
		);

		if ( ! isset( $GLOBALS['content_width'] ) ) {
			$GLOBALS['content_width'] = 1140;
		}
	}
}
add_action( 'after_setup_theme', 'athletix_theme_setup' );

if ( ! function_exists( 'athletix_theme_widgets' ) ) {
	/**
	 * Register the footer widget areas.
	 *
	 * @return void
	 */
	function athletix_theme_widgets() {
		foreach ( array( 'footer-1', 'footer-2' ) as $index => $id ) {
			register_sidebar(
				array(
					'name'          => sprintf(
						/* translators: %d: footer column number. */
						__( 'Footer %d', 'athletix-theme' ),
						$index + 1
					),
					'id'            => $id,
					'before_widget' => '<div class="widget %2$s">',
					'after_widget'  => '</div>',
					'before_title'  => '<h4>',
					'after_title'   => '</h4>',
				)
			);
		}
	}
}
add_action( 'widgets_init', 'athletix_theme_widgets' );

if ( ! function_exists( 'athletix_theme_nav_fallback' ) ) {
	/**
	 * Simple fallback nav when no primary menu is assigned: link to the plugin's
	 * archives if present, otherwise just Home.
	 *
	 * @return void
	 */
	function athletix_theme_nav_fallback() {
		echo '<ul>';
		printf( '<li><a href="%s">%s</a></li>', esc_url( home_url( '/' ) ), esc_html__( 'Home', 'athletix-theme' ) );
		foreach ( array(
			'ax_team'   => __( 'Teams', 'athletix-theme' ),
			'ax_player' => __( 'Players', 'athletix-theme' ),
			'ax_match'  => __( 'Matches', 'athletix-theme' ),
		) as $type => $label ) {
			$link = post_type_exists( $type ) ? get_post_type_archive_link( $type ) : '';
			if ( $link ) {
				printf( '<li><a href="%s">%s</a></li>', esc_url( $link ), esc_html( $label ) );
			}
		}
		echo '</ul>';
	}
}

if ( ! function_exists( 'athletix_theme_assets' ) ) {
	/**
	 * Enqueue theme styles (and the plugin stylesheet when available).
	 *
	 * @return void
	 */
	function athletix_theme_assets() {
		wp_enqueue_style( 'athletix-theme', get_stylesheet_uri(), array(), '1.2.0' );

		wp_enqueue_script(
			'athletix-theme-nav',
			get_template_directory_uri() . '/js/nav.js',
			array(),
			'1.1.0',
			true
		);

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

if ( ! function_exists( 'athletix_theme_taxonomy_teams' ) ) {
	/**
	 * Render the teams belonging to a league/division term as a card grid.
	 *
	 * @param int    $term_id  Term id.
	 * @param string $heading  Section heading.
	 * @param string $taxonomy Taxonomy slug (default league).
	 * @return void
	 */
	function athletix_theme_taxonomy_teams( $term_id, $heading, $taxonomy = 'ax_league' ) {
		if ( ! $term_id || ! post_type_exists( 'ax_team' ) ) {
			return;
		}

		$athletix_teams = new WP_Query(
			array(
				'post_type'      => 'ax_team',
				'posts_per_page' => 100,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'no_found_rows'  => true,
				'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array(
						'taxonomy' => $taxonomy,
						'field'    => 'term_id',
						'terms'    => (int) $term_id,
					),
				),
			)
		);

		if ( ! $athletix_teams->have_posts() ) {
			wp_reset_postdata();
			return;
		}

		echo '<section class="ax-section"><div class="ax-section__head"><h2>' . esc_html( $heading ) . '</h2></div><div class="ax-grid ax-grid--3">';
		while ( $athletix_teams->have_posts() ) {
			$athletix_teams->the_post();
			printf( '<a class="ax-card ax-card--link" href="%s">', esc_url( get_permalink() ) );
			if ( has_post_thumbnail() ) {
				echo '<span class="ax-card__media">' . get_the_post_thumbnail( get_the_ID(), 'medium_large' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			echo '<h3 class="ax-card__title">' . esc_html( get_the_title() ) . '</h3></a>';
		}
		echo '</div></section>';

		wp_reset_postdata();
	}
}
