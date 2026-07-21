<?php
/**
 * Meta boxes for athlete and event details.
 *
 * @package Athletix
 */

namespace Athletix;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and saves custom fields for the Athletix post types.
 */
final class Meta_Boxes {

	/**
	 * Shared instance.
	 *
	 * @var Meta_Boxes|null
	 */
	private static $instance = null;

	/**
	 * Nonce action.
	 */
	const NONCE_ACTION = 'athletix_save_meta';

	/**
	 * Nonce field name.
	 */
	const NONCE_NAME = 'athletix_meta_nonce';

	/**
	 * Athlete meta fields: key => label.
	 *
	 * @var array<string,string>
	 */
	private $athlete_fields = array();

	/**
	 * Event meta fields: key => label.
	 *
	 * @var array<string,string>
	 */
	private $event_fields = array();

	/**
	 * Retrieve the shared instance.
	 *
	 * @return Meta_Boxes
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->athlete_fields = array(
			'_athletix_position'  => __( 'Position', 'athletix' ),
			'_athletix_number'    => __( 'Jersey Number', 'athletix' ),
			'_athletix_height'    => __( 'Height', 'athletix' ),
			'_athletix_weight'    => __( 'Weight', 'athletix' ),
			'_athletix_country'   => __( 'Country', 'athletix' ),
			'_athletix_dob'       => __( 'Date of Birth', 'athletix' ),
		);

		$this->event_fields = array(
			'_athletix_event_date'     => __( 'Event Date', 'athletix' ),
			'_athletix_event_location' => __( 'Location', 'athletix' ),
			'_athletix_event_home'     => __( 'Home Team', 'athletix' ),
			'_athletix_event_away'     => __( 'Away Team', 'athletix' ),
			'_athletix_event_score'    => __( 'Result / Score', 'athletix' ),
		);

		add_action( 'add_meta_boxes', array( $this, 'add' ) );
		add_action( 'save_post', array( $this, 'save' ), 10, 2 );
	}

	/**
	 * Register the meta boxes.
	 *
	 * @return void
	 */
	public function add() {
		add_meta_box(
			'athletix_athlete_details',
			__( 'Athlete Details', 'athletix' ),
			array( $this, 'render_athlete' ),
			'athletix_athlete',
			'normal',
			'high'
		);

		add_meta_box(
			'athletix_event_details',
			__( 'Event Details', 'athletix' ),
			array( $this, 'render_event' ),
			'athletix_event',
			'normal',
			'high'
		);
	}

	/**
	 * Render athlete fields.
	 *
	 * @param \WP_Post $post Current post.
	 * @return void
	 */
	public function render_athlete( $post ) {
		$this->render_fields( $post, $this->athlete_fields );
	}

	/**
	 * Render event fields.
	 *
	 * @param \WP_Post $post Current post.
	 * @return void
	 */
	public function render_event( $post ) {
		$this->render_fields( $post, $this->event_fields );
	}

	/**
	 * Render a table of text fields.
	 *
	 * @param \WP_Post              $post   Current post.
	 * @param array<string,string> $fields Field map.
	 * @return void
	 */
	private function render_fields( $post, $fields ) {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		echo '<table class="form-table" role="presentation"><tbody>';

		foreach ( $fields as $key => $label ) {
			$value = get_post_meta( $post->ID, $key, true );
			$type  = ( false !== strpos( $key, 'date' ) || '_athletix_dob' === $key ) ? 'date' : 'text';

			printf(
				'<tr><th scope="row"><label for="%1$s">%2$s</label></th>' .
				'<td><input type="%3$s" id="%1$s" name="%1$s" value="%4$s" class="regular-text" /></td></tr>',
				esc_attr( $key ),
				esc_html( $label ),
				esc_attr( $type ),
				esc_attr( $value )
			);
		}

		echo '</tbody></table>';
	}

	/**
	 * Persist the submitted meta values.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @return void
	 */
	public function save( $post_id, $post ) {
		// Bail on autosave / revisions.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Verify the nonce.
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) );
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return;
		}

		// Capability check.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Only handle our post types.
		$fields = array();
		if ( 'athletix_athlete' === $post->post_type ) {
			$fields = $this->athlete_fields;
		} elseif ( 'athletix_event' === $post->post_type ) {
			$fields = $this->event_fields;
		} else {
			return;
		}

		foreach ( array_keys( $fields ) as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				$value = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
				update_post_meta( $post_id, $key, $value );
			} else {
				delete_post_meta( $post_id, $key );
			}
		}
	}
}
