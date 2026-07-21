<?php
/**
 * Admin dashboard page.
 *
 * @package Athletix
 */

namespace Athletix\Dashboard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Plugin;
use Athletix\Support\Keys;

/**
 * A simple "at a glance" overview: entity counts, recent matches and recent
 * audit entries.
 */
class DashboardPage {

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
	 * Render the page.
	 *
	 * The menu (and its capability) is owned by Admin\AdminMenu; this method is
	 * the landing-page callback.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		$counts = array(
			__( 'Leagues', 'athletix' ) => wp_count_posts( Keys::LEAGUE )->publish,
			__( 'Teams', 'athletix' )   => wp_count_posts( Keys::TEAM )->publish,
			__( 'Players', 'athletix' ) => wp_count_posts( Keys::PLAYER )->publish,
			__( 'Matches', 'athletix' ) => wp_count_posts( Keys::MATCH )->publish,
		);
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Athletix Dashboard', 'athletix' ); ?></h1>

			<div style="display:flex;gap:1rem;flex-wrap:wrap;margin:1rem 0;">
				<?php foreach ( $counts as $label => $count ) : ?>
					<div style="background:#fff;border:1px solid #dcdcde;border-radius:6px;padding:1rem 1.5rem;min-width:120px;">
						<div style="font-size:2rem;font-weight:700;"><?php echo esc_html( (int) $count ); ?></div>
						<div style="color:#646970;"><?php echo esc_html( $label ); ?></div>
					</div>
				<?php endforeach; ?>
			</div>

			<h2><?php esc_html_e( 'Recent Matches', 'athletix' ); ?></h2>
			<?php $this->recent_matches(); ?>

			<h2><?php esc_html_e( 'Recent Activity', 'athletix' ); ?></h2>
			<?php $this->recent_activity(); ?>
		</div>
		<?php
	}

	/**
	 * Recent matches table.
	 *
	 * @return void
	 */
	private function recent_matches() {
		$matches = $this->plugin->make( 'repo.match' )->all(
			array(
				'posts_per_page' => 8,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		if ( empty( $matches ) ) {
			echo '<p>' . esc_html__( 'No matches yet.', 'athletix' ) . '</p>';
			return;
		}

		echo '<ul class="ul-disc">';
		foreach ( $matches as $match ) {
			printf(
				'<li><a href="%s">%s</a></li>',
				esc_url( get_edit_post_link( $match->ID ) ),
				esc_html( get_the_title( $match ) )
			);
		}
		echo '</ul>';
	}

	/**
	 * Recent audit activity.
	 *
	 * @return void
	 */
	private function recent_activity() {
		if ( ! current_user_can( Keys::capability() ) ) {
			echo '<p>' . esc_html__( 'Activity is visible to managers only.', 'athletix' ) . '</p>';
			return;
		}

		if ( ! $this->plugin->container()->has( 'security.audit' ) ) {
			echo '<p>' . esc_html__( 'Activity logging is not available.', 'athletix' ) . '</p>';
			return;
		}

		$audit   = $this->plugin->make( 'security.audit' );
		$entries = array_reverse( $audit->recent() );

		if ( empty( $entries ) ) {
			echo '<p>' . esc_html__( 'No recent activity.', 'athletix' ) . '</p>';
			return;
		}

		echo '<table class="widefat striped"><tbody>';
		foreach ( array_slice( $entries, 0, 15 ) as $entry ) {
			printf(
				'<tr><td>%s</td><td>%s</td></tr>',
				esc_html( wp_date( 'Y-m-d H:i', (int) $entry['time'] ) ),
				esc_html( $entry['message'] )
			);
		}
		echo '</tbody></table>';
	}
}
