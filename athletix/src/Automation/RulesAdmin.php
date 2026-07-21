<?php
/**
 * Automation rules admin screen.
 *
 * @package Athletix
 */

namespace Athletix\Automation;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Support\Keys;

/**
 * Lets an administrator create and remove automation rules.
 */
class RulesAdmin {

	const PAGE          = 'athletix-rules';
	const ACTION_ADD    = 'athletix_rule_add';
	const ACTION_DELETE = 'athletix_rule_delete';

	/**
	 * Rule manager.
	 *
	 * @var RuleManager
	 */
	private $rules;

	/**
	 * Constructor.
	 *
	 * @param RuleManager $rules Rule manager.
	 */
	public function __construct( RuleManager $rules ) {
		$this->rules = $rules;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_post_' . self::ACTION_ADD, array( $this, 'handle_add' ) );
		add_action( 'admin_post_' . self::ACTION_DELETE, array( $this, 'handle_delete' ) );
	}

	/**
	 * Add the submenu.
	 *
	 * @return void
	 */
	public function menu() {
		add_submenu_page(
			'edit.php?post_type=' . Keys::TEAM,
			__( 'Automation Rules', 'athletix' ),
			__( 'Automation', 'athletix' ),
			Keys::capability(),
			self::PAGE,
			array( $this, 'render' )
		);
	}

	/**
	 * Render the screen.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! current_user_can( Keys::capability() ) ) {
			return;
		}

		$action = esc_url( admin_url( 'admin-post.php' ) );
		$rules  = $this->rules->rules();
		$events = RuleManager::events();
		$types  = RuleManager::actions();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Automation Rules', 'athletix' ); ?></h1>
			<p class="description"><?php esc_html_e( 'Run an action when a plugin event occurs. Templates support {placeholders} such as {home}, {away}, {home_score}, {away_score}, {team_id}.', 'athletix' ); ?></p>

			<table class="widefat striped">
				<thead><tr>
					<th><?php esc_html_e( 'When', 'athletix' ); ?></th>
					<th><?php esc_html_e( 'Do', 'athletix' ); ?></th>
					<th><?php esc_html_e( 'Details', 'athletix' ); ?></th>
					<th></th>
				</tr></thead>
				<tbody>
				<?php if ( empty( $rules ) ) : ?>
					<tr><td colspan="4"><?php esc_html_e( 'No rules yet.', 'athletix' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $rules as $index => $rule ) : ?>
						<tr>
							<td><?php echo esc_html( isset( $events[ $rule['event'] ] ) ? $events[ $rule['event'] ] : $rule['event'] ); ?></td>
							<td><?php echo esc_html( isset( $types[ $rule['action'] ] ) ? $types[ $rule['action'] ] : $rule['action'] ); ?></td>
							<td><?php echo esc_html( $rule['subject'] ? $rule['subject'] : $rule['body'] ); ?></td>
							<td>
								<form method="post" action="<?php echo esc_url( $action ); ?>">
									<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION_DELETE ); ?>" />
									<input type="hidden" name="index" value="<?php echo esc_attr( $index ); ?>" />
									<?php wp_nonce_field( self::ACTION_DELETE ); ?>
									<button class="button-link delete"><?php esc_html_e( 'Delete', 'athletix' ); ?></button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>

			<h2><?php esc_html_e( 'Add Rule', 'athletix' ); ?></h2>
			<form method="post" action="<?php echo esc_url( $action ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION_ADD ); ?>" />
				<?php wp_nonce_field( self::ACTION_ADD ); ?>
				<table class="form-table" role="presentation"><tbody>
					<tr>
						<th scope="row"><?php esc_html_e( 'When', 'athletix' ); ?></th>
						<td>
							<select name="event">
								<?php foreach ( $events as $value => $label ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Do', 'athletix' ); ?></th>
						<td>
							<select name="rule_action">
								<?php foreach ( $types as $value => $label ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="ax-rule-recipient"><?php esc_html_e( 'Email recipient', 'athletix' ); ?></label></th>
						<td><input type="email" id="ax-rule-recipient" name="recipient" class="regular-text" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="ax-rule-subject"><?php esc_html_e( 'Subject template', 'athletix' ); ?></label></th>
						<td><input type="text" id="ax-rule-subject" name="subject" class="regular-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="ax-rule-body"><?php esc_html_e( 'Message template', 'athletix' ); ?></label></th>
						<td><textarea id="ax-rule-body" name="body" rows="3" class="large-text"></textarea></td>
					</tr>
				</tbody></table>
				<?php submit_button( __( 'Add Rule', 'athletix' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Handle adding a rule.
	 *
	 * @return void
	 */
	public function handle_add() {
		check_admin_referer( self::ACTION_ADD );
		$this->authorize();

		$this->rules->add(
			array(
				'event'     => isset( $_POST['event'] ) ? sanitize_key( wp_unslash( $_POST['event'] ) ) : '',
				'action'    => isset( $_POST['rule_action'] ) ? sanitize_key( wp_unslash( $_POST['rule_action'] ) ) : '',
				'recipient' => isset( $_POST['recipient'] ) ? sanitize_email( wp_unslash( $_POST['recipient'] ) ) : '',
				'subject'   => isset( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) ) : '',
				'body'      => isset( $_POST['body'] ) ? sanitize_textarea_field( wp_unslash( $_POST['body'] ) ) : '',
			)
		);

		$this->redirect();
	}

	/**
	 * Handle deleting a rule.
	 *
	 * @return void
	 */
	public function handle_delete() {
		check_admin_referer( self::ACTION_DELETE );
		$this->authorize();

		$index = isset( $_POST['index'] ) ? absint( $_POST['index'] ) : -1;
		if ( $index >= 0 ) {
			$this->rules->delete( $index );
		}

		$this->redirect();
	}

	/**
	 * Ensure the current user may manage automation, or die. The nonce is
	 * verified inline by each handler before this call.
	 *
	 * @return void
	 */
	private function authorize() {
		if ( ! current_user_can( Keys::capability() ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'athletix' ) );
		}
	}

	/**
	 * Redirect back to the rules screen.
	 *
	 * @return void
	 */
	private function redirect() {
		wp_safe_redirect(
			add_query_arg(
				array(
					'post_type' => Keys::TEAM,
					'page'      => self::PAGE,
				),
				admin_url( 'edit.php' )
			)
		);
		exit;
	}
}
