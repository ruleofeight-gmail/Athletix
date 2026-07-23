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

use Athletix\Admin\Hub;
use Athletix\Admin\Tables\FilterableTable;
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
		add_filter( 'athletix/admin_tabs', array( $this, 'tab' ) );
		add_action( 'admin_post_' . self::ACTION_ADD, array( $this, 'handle_add' ) );
		add_action( 'admin_post_' . self::ACTION_DELETE, array( $this, 'handle_delete' ) );
	}

	/**
	 * Contribute the Automation tab to the hub.
	 *
	 * @param array $tabs Tabs.
	 * @return array
	 */
	public function tab( array $tabs ) {
		$tabs['automation'] = array(
			'label'    => __( 'Automation', 'athletix' ),
			'cap'      => Keys::capability(),
			'order'    => 60,
			'callback' => array( $this, 'render' ),
		);

		return $tabs;
	}

	/**
	 * Render the screen.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! Keys::can_manage() ) {
			return;
		}

		$action = esc_url( admin_url( 'admin-post.php' ) );
		$events = RuleManager::events();
		$types  = RuleManager::actions();

		$table = new FilterableTable(
			array(
				'columns'    => array(
					'when'    => __( 'When', 'athletix' ),
					'do'      => __( 'Do', 'athletix' ),
					'details' => __( 'Details', 'athletix' ),
					'actions' => '',
				),
				'sortable'   => array( 'when', 'do' ),
				'searchable' => array( 'when', 'do', 'details' ),
				'filters'    => array(
					'event' => array(
						'label'   => __( 'All events', 'athletix' ),
						'options' => $events,
					),
				),
				'per_page'   => 20,
				'base_url'   => Hub::tab_url( 'automation' ),
				'rows'       => array( $this, 'rows' ),
				'render'     => array(
					'actions' => array( $this, 'render_actions' ),
				),
			)
		);
		?>
		<h2><?php esc_html_e( 'Automation Rules', 'athletix' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Run an action when a plugin event occurs. Templates support {placeholders} such as {home}, {away}, {home_score}, {away_score}, {team_id}.', 'athletix' ); ?></p>

		<?php $table->render(); ?>

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
		<?php
	}

	/**
	 * Build the rule rows for the table.
	 *
	 * Each row keeps its original index so the delete action targets the right
	 * rule after the DataSet filters/sorts.
	 *
	 * @return array[]
	 */
	public function rows() {
		$events = RuleManager::events();
		$types  = RuleManager::actions();
		$rows   = array();

		foreach ( $this->rules->rules() as $index => $rule ) {
			$rows[] = array(
				'index'   => (int) $index,
				'event'   => $rule['event'],
				'when'    => isset( $events[ $rule['event'] ] ) ? $events[ $rule['event'] ] : $rule['event'],
				'do'      => isset( $types[ $rule['action'] ] ) ? $types[ $rule['action'] ] : $rule['action'],
				'details' => $rule['subject'] ? $rule['subject'] : $rule['body'],
			);
		}

		return $rows;
	}

	/**
	 * Render the delete-action cell.
	 *
	 * @param array $row Row.
	 * @return string
	 */
	public function render_actions( array $row ) {
		return sprintf(
			'<form method="post" action="%1$s">
				<input type="hidden" name="action" value="%2$s" />
				<input type="hidden" name="index" value="%3$d" />
				%4$s
				<button class="button-link delete">%5$s</button>
			</form>',
			esc_url( admin_url( 'admin-post.php' ) ),
			esc_attr( self::ACTION_DELETE ),
			(int) $row['index'],
			wp_nonce_field( self::ACTION_DELETE, '_wpnonce', true, false ),
			esc_html__( 'Delete', 'athletix' )
		);
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
		if ( ! Keys::can_manage() ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'athletix' ) );
		}
	}

	/**
	 * Redirect back to the rules screen.
	 *
	 * @return void
	 */
	private function redirect() {
		wp_safe_redirect( Hub::tab_url( 'automation' ) );
		exit;
	}
}
