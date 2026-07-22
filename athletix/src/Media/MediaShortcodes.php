<?php
/**
 * Additional media shortcodes: logo, video, content feed.
 *
 * @package Athletix
 */

namespace Athletix\Media;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Plugin;
use Athletix\Support\Keys;

/**
 * Team logo, oEmbed video, and a recent-results content feed.
 */
class MediaShortcodes {

	/**
	 * Plugin.
	 *
	 * @var Plugin
	 */
	private $plugin;

	/**
	 * Constructor.
	 *
	 * @param Plugin $plugin Plugin instance.
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Register the shortcodes.
	 *
	 * @return void
	 */
	public function register() {
		add_shortcode( 'athletix_logo', array( $this, 'logo' ) );
		add_shortcode( 'athletix_video', array( $this, 'video' ) );
		add_shortcode( 'athletix_feed', array( $this, 'feed' ) );
	}

	/**
	 * [athletix_logo team="5" size="thumbnail"]
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function logo( $atts ) {
		$atts = shortcode_atts(
			array(
				'team' => 0,
				'size' => 'thumbnail',
			),
			$atts,
			'athletix_logo'
		);

		$team = absint( $atts['team'] );
		if ( ! $team || ! has_post_thumbnail( $team ) ) {
			return '';
		}

		return '<span class="athletix-logo">' . get_the_post_thumbnail( $team, sanitize_key( $atts['size'] ) ) . '</span>';
	}

	/**
	 * [athletix_video url="https://..."]
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function video( $atts ) {
		$atts = shortcode_atts(
			array(
				'url'   => '',
				'width' => 640,
			),
			$atts,
			'athletix_video'
		);

		$url = esc_url_raw( $atts['url'] );
		if ( '' === $url ) {
			return '';
		}

		$embed = wp_oembed_get( $url, array( 'width' => absint( $atts['width'] ) ) );
		if ( ! $embed ) {
			return '';
		}

		// wp_oembed_get returns provider markup (iframe); wrap it responsively.
		return '<div class="athletix-video">' . $embed . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * [athletix_feed league="12" limit="5"]
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function feed( $atts ) {
		$atts = shortcode_atts(
			array(
				'league' => 0,
				'limit'  => 5,
			),
			$atts,
			'athletix_feed'
		);

		wp_enqueue_style( 'athletix' );

		$matches = $this->plugin->make( 'repo.match' );
		$args    = array(
			'posts_per_page' => absint( $atts['limit'] ),
			'orderby'        => 'meta_value',
			'meta_key'       => Keys::MATCH_DATE, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'order'          => 'DESC',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'   => Keys::MATCH_STATUS,
					'value' => Keys::STATUS_COMPLETED,
				),
			),
		);

		if ( $atts['league'] ) {
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => Keys::LEAGUE,
					'field'    => 'term_id',
					'terms'    => absint( $atts['league'] ),
				),
			);
		}

		$posts = $matches->all( $args );
		if ( empty( $posts ) ) {
			return '<p class="athletix-empty">' . esc_html__( 'No recent results.', 'athletix' ) . '</p>';
		}

		ob_start();
		echo '<ul class="athletix-feed">';
		foreach ( $posts as $post ) {
			$d = $matches->details( $post->ID );
			printf(
				'<li><strong>%s %d–%d %s</strong></li>',
				esc_html( get_the_title( $d['home'] ) ),
				(int) $d['home_score'],
				(int) $d['away_score'],
				esc_html( get_the_title( $d['away'] ) )
			);
		}
		echo '</ul>';

		return (string) ob_get_clean();
	}
}
