<?php
/**
 * Schema-driven meta boxes for Athletix entities.
 *
 * @package Athletix
 */

namespace Athletix\Meta;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Core\Validator;
use Athletix\Support\Keys;

/**
 * Registers, renders and securely saves the custom fields for teams, players,
 * matches, seasons and divisions from a declarative schema.
 */
class MetaBoxManager {

	const NONCE_ACTION = 'athletix_meta';
	const NONCE_NAME   = 'athletix_meta_nonce';

	/**
	 * Above this many candidate posts, a relationship field switches from a
	 * native <select> to the searchable AJAX picker.
	 */
	const PICKER_THRESHOLD = 30;

	/**
	 * Validator service.
	 *
	 * @var Validator
	 */
	private $validator;

	/**
	 * Constructor.
	 *
	 * @param Validator $validator Sanitizer/validator.
	 */
	public function __construct( Validator $validator ) {
		$this->validator = $validator;
	}

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'add_meta_boxes', array( $this, 'add' ) );
		add_action( 'save_post', array( $this, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );

		( new PostSearchAjax() )->register();
	}

	/**
	 * Enqueue the relationship-picker assets on the post editor for our types.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue( $hook ) {
		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->post_type, Keys::post_types(), true ) ) {
			return;
		}

		wp_enqueue_style( 'athletix-admin', ATHLETIX_URL . 'assets/css/admin.css', array(), ATHLETIX_VERSION );
		wp_enqueue_script( 'athletix-post-picker', ATHLETIX_URL . 'assets/js/post-picker.js', array(), ATHLETIX_VERSION, true );
		wp_localize_script(
			'athletix-post-picker',
			'AthletixPicker',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( PostSearchAjax::NONCE ),
			)
		);
	}

	/**
	 * Field schemas keyed by post type.
	 *
	 * @return array<string,array<string,array>>
	 */
	private function schemas() {
		return array(
			Keys::TEAM   => array(
				// League and Division are assigned via the native taxonomy boxes.
				Keys::TEAM_VENUE   => array(
					'label'    => __( 'Home Venue', 'athletix' ),
					'type'     => 'text',
					'sanitize' => 'text',
				),
				Keys::TEAM_FOUNDED => array(
					'label'    => __( 'Founded (year)', 'athletix' ),
					'type'     => 'number',
					'sanitize' => 'int',
				),
				Keys::TEAM_COLOR   => array(
					'label'    => __( 'Team Color', 'athletix' ),
					'type'     => 'color',
					'sanitize' => 'text',
				),
			),
			Keys::PLAYER => array(
				Keys::PLAYER_TEAM     => array(
					'label'     => __( 'Team', 'athletix' ),
					'type'      => 'post',
					'sanitize'  => 'int',
					'post_type' => Keys::TEAM,
				),
				Keys::PLAYER_POSITION => array(
					'label'    => __( 'Position', 'athletix' ),
					'type'     => 'text',
					'sanitize' => 'text',
				),
				Keys::PLAYER_NUMBER   => array(
					'label'    => __( 'Jersey Number', 'athletix' ),
					'type'     => 'number',
					'sanitize' => 'int',
				),
				Keys::PLAYER_HEIGHT   => array(
					'label'    => __( 'Height', 'athletix' ),
					'type'     => 'text',
					'sanitize' => 'text',
				),
				Keys::PLAYER_WEIGHT   => array(
					'label'    => __( 'Weight', 'athletix' ),
					'type'     => 'text',
					'sanitize' => 'text',
				),
				Keys::PLAYER_COUNTRY  => array(
					'label'    => __( 'Country', 'athletix' ),
					'type'     => 'text',
					'sanitize' => 'text',
				),
				Keys::PLAYER_DOB      => array(
					'label'    => __( 'Date of Birth', 'athletix' ),
					'type'     => 'date',
					'sanitize' => 'date',
				),
			),
			Keys::MATCH  => array(
				// League and Season are assigned via the native taxonomy boxes.
				Keys::MATCH_HOME_TEAM  => array(
					'label'     => __( 'Home Team', 'athletix' ),
					'type'      => 'post',
					'sanitize'  => 'int',
					'post_type' => Keys::TEAM,
				),
				Keys::MATCH_AWAY_TEAM  => array(
					'label'     => __( 'Away Team', 'athletix' ),
					'type'      => 'post',
					'sanitize'  => 'int',
					'post_type' => Keys::TEAM,
				),
				Keys::MATCH_HOME_SCORE => array(
					'label'    => __( 'Home Score', 'athletix' ),
					'type'     => 'number',
					'sanitize' => 'int',
				),
				Keys::MATCH_AWAY_SCORE => array(
					'label'    => __( 'Away Score', 'athletix' ),
					'type'     => 'number',
					'sanitize' => 'int',
				),
				Keys::MATCH_DATE       => array(
					'label'    => __( 'Match Date', 'athletix' ),
					'type'     => 'date',
					'sanitize' => 'date',
				),
				Keys::MATCH_STATUS     => array(
					'label'    => __( 'Status', 'athletix' ),
					'type'     => 'select',
					'sanitize' => 'key',
					'options'  => array(
						Keys::STATUS_SCHEDULED => __( 'Scheduled', 'athletix' ),
						Keys::STATUS_COMPLETED => __( 'Completed', 'athletix' ),
					),
				),
			),
			// Season and Division are taxonomies now; they are managed on the
			// native term-edit screens under the Athletix menu, not here.
		);
	}

	/**
	 * Register meta boxes for each schema.
	 *
	 * @return void
	 */
	public function add() {
		foreach ( array_keys( $this->schemas() ) as $post_type ) {
			add_meta_box(
				'athletix_' . $post_type . '_details',
				__( 'Details', 'athletix' ),
				array( $this, 'render' ),
				$post_type,
				'normal',
				'high'
			);
		}
	}

	/**
	 * Render the fields for the current post type.
	 *
	 * @param \WP_Post $post Post being edited.
	 * @return void
	 */
	public function render( $post ) {
		$schemas = $this->schemas();

		if ( ! isset( $schemas[ $post->post_type ] ) ) {
			return;
		}

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		echo '<table class="form-table" role="presentation"><tbody>';

		foreach ( $schemas[ $post->post_type ] as $key => $field ) {
			$value = get_post_meta( $post->ID, $key, true );
			echo '<tr><th scope="row"><label for="' . esc_attr( $key ) . '">' . esc_html( $field['label'] ) . '</label></th><td>';
			$this->render_field( $key, $field, $value );
			echo '</td></tr>';
		}

		echo '</tbody></table>';
	}

	/**
	 * Render a single input by type.
	 *
	 * @param string $key   Meta key / field name.
	 * @param array  $field Field definition.
	 * @param mixed  $value Current value.
	 * @return void
	 */
	private function render_field( $key, array $field, $value ) {
		$type = isset( $field['type'] ) ? $field['type'] : 'text';

		switch ( $type ) {
			case 'post':
				$this->render_post_select( $key, $field['post_type'], (int) $value );
				break;

			case 'select':
				echo '<select id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '">';
				foreach ( $field['options'] as $opt_value => $opt_label ) {
					echo '<option value="' . esc_attr( $opt_value ) . '" ' . selected( $value, $opt_value, false ) . '>' . esc_html( $opt_label ) . '</option>';
				}
				echo '</select>';
				break;

			case 'number':
				echo '<input type="number" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" class="small-text" />';
				break;

			case 'date':
				echo '<input type="date" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" />';
				break;

			case 'color':
				echo '<input type="text" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" class="regular-text" placeholder="#1a73e8" />';
				break;

			case 'text':
			default:
				echo '<input type="text" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" class="regular-text" />';
				break;
		}//end switch
	}

	/**
	 * Render a dropdown of posts of a given type.
	 *
	 * @param string $key       Field name.
	 * @param string $post_type Post type to list.
	 * @param int    $selected  Currently selected post id.
	 * @return void
	 */
	private function render_post_select( $key, $post_type, $selected ) {
		$counts = wp_count_posts( $post_type );
		$total  = isset( $counts->publish ) ? (int) $counts->publish : 0;

		if ( $total > self::PICKER_THRESHOLD ) {
			$this->render_post_picker( $key, $post_type, $selected );
			return;
		}

		$posts = get_posts(
			array(
				'post_type'      => $post_type,
				'posts_per_page' => self::PICKER_THRESHOLD,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'post_status'    => 'publish',
			)
		);

		echo '<select id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '">';
		echo '<option value="0">' . esc_html__( '— Select —', 'athletix' ) . '</option>';

		foreach ( $posts as $post ) {
			echo '<option value="' . esc_attr( $post->ID ) . '" ' . selected( $selected, $post->ID, false ) . '>' . esc_html( $post->post_title ) . '</option>';
		}

		echo '</select>';
	}

	/**
	 * Render the searchable AJAX picker for a large relationship field.
	 *
	 * A hidden input carries the selected id (so the form submits exactly as the
	 * <select> would); the text input drives the AJAX search once JS enhances it.
	 *
	 * @param string $key       Field name.
	 * @param string $post_type Post type to search.
	 * @param int    $selected  Currently selected post id.
	 * @return void
	 */
	private function render_post_picker( $key, $post_type, $selected ) {
		$current = $selected ? get_the_title( $selected ) : '';

		printf(
			'<span class="athletix-picker" data-post-type="%s">',
			esc_attr( $post_type )
		);
		printf(
			'<input type="hidden" class="athletix-picker__value" id="%1$s" name="%1$s" value="%2$d" />',
			esc_attr( $key ),
			(int) $selected
		);
		printf(
			'<input type="text" class="athletix-picker__search regular-text" value="%s" placeholder="%s" autocomplete="off" />',
			esc_attr( $current ),
			esc_attr__( 'Search…', 'athletix' )
		);
		echo '<span class="athletix-picker__results" role="listbox" hidden></span>';
		echo '</span>';
	}

	/**
	 * Securely persist submitted values.
	 *
	 * @param int      $post_id Post id.
	 * @param \WP_Post $post    Post object.
	 * @return void
	 */
	public function save( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		$schemas = $this->schemas();
		if ( ! isset( $schemas[ $post->post_type ] ) ) {
			return;
		}

		if ( ! isset( $_POST[ self::NONCE_NAME ] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) );
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		foreach ( $schemas[ $post->post_type ] as $key => $field ) {
			if ( ! isset( $_POST[ $key ] ) ) {
				continue;
			}

			// wp_unslash then delegate sanitization to the Validator by type.
			$raw   = wp_unslash( $_POST[ $key ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized on next line.
			$clean = $this->validator->sanitize( $raw, $field['sanitize'] );

			if ( '' === $clean || '0' === (string) $clean ) {
				// Keep explicit zero scores; only drop empty relationships/text.
				if ( 'int' === $field['sanitize'] && 0 === (int) $clean && false === strpos( $key, 'score' ) ) {
					delete_post_meta( $post_id, $key );
					continue;
				}
			}

			update_post_meta( $post_id, $key, $clean );
		}
	}
}
