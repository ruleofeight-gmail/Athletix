<?php
/**
 * Team media gallery.
 *
 * @package Athletix
 */

namespace Athletix\Media;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Support\Keys;

/**
 * Renders a gallery of images attached to a team (or explicit attachment ids)
 * via [athletix_gallery].
 */
class Gallery {

	/**
	 * Register the shortcode.
	 *
	 * @return void
	 */
	public function register() {
		add_shortcode( 'athletix_gallery', array( $this, 'render' ) );
	}

	/**
	 * [athletix_gallery team="5" columns="4"] or [athletix_gallery ids="1,2,3"]
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function render( $atts ) {
		$atts = shortcode_atts(
			array(
				'team'    => 0,
				'ids'     => '',
				'columns' => 4,
				'size'    => 'medium',
			),
			$atts,
			'athletix_gallery'
		);

		$ids = $this->resolve_ids( $atts );

		if ( empty( $ids ) ) {
			return '';
		}

		wp_enqueue_style( 'athletix' );
		$columns = max( 1, absint( $atts['columns'] ) );
		$size    = sanitize_key( $atts['size'] );

		ob_start();
		echo '<div class="athletix-gallery athletix-roster--cols-' . esc_attr( $columns ) . '">';
		foreach ( $ids as $id ) {
			$img = wp_get_attachment_image( $id, $size, false, array( 'loading' => 'lazy' ) );
			if ( $img ) {
				echo '<figure class="athletix-gallery__item">' . $img . '</figure>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}
		echo '</div>';

		return (string) ob_get_clean();
	}

	/**
	 * Resolve the attachment ids from attributes.
	 *
	 * @param array $atts Attributes.
	 * @return int[]
	 */
	private function resolve_ids( array $atts ) {
		if ( '' !== $atts['ids'] ) {
			return array_filter( array_map( 'absint', explode( ',', $atts['ids'] ) ) );
		}

		$team = absint( $atts['team'] );
		if ( ! $team || get_post_type( $team ) !== Keys::TEAM ) {
			return array();
		}

		$attachments = get_attached_media( 'image', $team );

		return array_map(
			static function ( $post ) {
				return (int) $post->ID;
			},
			array_values( $attachments )
		);
	}
}
