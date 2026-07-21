<?php
/**
 * Unified settings screen.
 *
 * @package Athletix
 */

namespace Athletix\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Core\Config;
use Athletix\Support\Keys;

/**
 * Renders and saves the plugin's settings via the WordPress Settings API,
 * backed by the single Config option.
 */
class SettingsPage {

	const PAGE  = 'athletix-settings';
	const GROUP = 'athletix_settings_group';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'settings' ) );
	}

	/**
	 * Add the submenu.
	 *
	 * @return void
	 */
	public function menu() {
		add_submenu_page(
			Keys::MENU,
			__( 'Athletix Settings', 'athletix' ),
			__( 'Settings', 'athletix' ),
			Keys::capability(),
			self::PAGE,
			array( $this, 'render' )
		);
	}

	/**
	 * Register the setting, section and fields.
	 *
	 * @return void
	 */
	public function settings() {
		register_setting(
			self::GROUP,
			Config::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => array(),
			)
		);

		add_settings_section(
			'athletix_general',
			__( 'General', 'athletix' ),
			'__return_false',
			self::PAGE
		);

		$this->field( 'active_sport', __( 'Active Sport', 'athletix' ), 'text', 'athletix_general' );
		$this->field( 'points_win', __( 'Points for a Win', 'athletix' ), 'number', 'athletix_general' );
		$this->field( 'points_draw', __( 'Points for a Draw', 'athletix' ), 'number', 'athletix_general' );
		$this->field( 'points_loss', __( 'Points for a Loss', 'athletix' ), 'number', 'athletix_general' );

		add_settings_section(
			'athletix_notifications',
			__( 'Notifications', 'athletix' ),
			'__return_false',
			self::PAGE
		);

		$this->field( 'notify_results', __( 'Email match results', 'athletix' ), 'checkbox', 'athletix_notifications' );
		$this->field( 'notify_reminders', __( 'Email match reminders', 'athletix' ), 'checkbox', 'athletix_notifications' );
		$this->field( 'notify_email', __( 'Notification email', 'athletix' ), 'email', 'athletix_notifications' );

		add_settings_section(
			'athletix_advanced',
			__( 'Advanced', 'athletix' ),
			'__return_false',
			self::PAGE
		);

		$this->field( 'delete_data', __( 'Delete all data on uninstall', 'athletix' ), 'checkbox', 'athletix_advanced' );
	}

	/**
	 * Register a single field.
	 *
	 * @param string $key     Config key.
	 * @param string $label   Field label.
	 * @param string $type    Input type.
	 * @param string $section Section id.
	 * @return void
	 */
	private function field( $key, $label, $type, $section ) {
		add_settings_field(
			$key,
			$label,
			array( $this, 'render_field' ),
			self::PAGE,
			$section,
			array(
				'key'   => $key,
				'type'  => $type,
				'label' => $label,
			)
		);
	}

	/**
	 * Render an individual field.
	 *
	 * @param array $args Field args.
	 * @return void
	 */
	public function render_field( $args ) {
		$config = get_option( Config::OPTION, array() );
		$value  = isset( $config[ $args['key'] ] ) ? $config[ $args['key'] ] : '';
		$name   = Config::OPTION . '[' . $args['key'] . ']';

		switch ( $args['type'] ) {
			case 'checkbox':
				printf(
					'<label><input type="checkbox" name="%s" value="1" %s /> %s</label>',
					esc_attr( $name ),
					checked( ! empty( $value ), true, false ),
					esc_html( $args['label'] )
				);
				break;

			case 'number':
				printf(
					'<input type="number" name="%s" value="%s" class="small-text" />',
					esc_attr( $name ),
					esc_attr( $value )
				);
				break;

			case 'email':
				printf(
					'<input type="email" name="%s" value="%s" class="regular-text" placeholder="%s" />',
					esc_attr( $name ),
					esc_attr( $value ),
					esc_attr( get_option( 'admin_email' ) )
				);
				break;

			default:
				printf(
					'<input type="text" name="%s" value="%s" class="regular-text" />',
					esc_attr( $name ),
					esc_attr( $value )
				);
				break;
		}//end switch
	}

	/**
	 * Sanitize submitted settings, merged over the stored option so
	 * programmatically-set keys are preserved.
	 *
	 * @param mixed $input Raw submitted values.
	 * @return array
	 */
	public function sanitize( $input ) {
		$input    = is_array( $input ) ? $input : array();
		$existing = get_option( Config::OPTION, array() );
		$existing = is_array( $existing ) ? $existing : array();

		$clean = array(
			'active_sport'     => isset( $input['active_sport'] ) ? sanitize_text_field( $input['active_sport'] ) : 'soccer',
			'points_win'       => isset( $input['points_win'] ) ? (int) $input['points_win'] : 3,
			'points_draw'      => isset( $input['points_draw'] ) ? (int) $input['points_draw'] : 1,
			'points_loss'      => isset( $input['points_loss'] ) ? (int) $input['points_loss'] : 0,
			'notify_results'   => ! empty( $input['notify_results'] ),
			'notify_reminders' => ! empty( $input['notify_reminders'] ),
			'notify_email'     => isset( $input['notify_email'] ) ? sanitize_email( $input['notify_email'] ) : '',
			'delete_data'      => ! empty( $input['delete_data'] ),
		);

		return array_merge( $existing, $clean );
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! current_user_can( Keys::capability() ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Athletix Settings', 'athletix' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( self::GROUP );
				do_settings_sections( self::PAGE );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
