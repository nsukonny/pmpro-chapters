<?php
/**
 * Class PMPRO_Rebate_Reports
 * Reports shortcode for chapter presidents
 *
 * @since 1.0.3
 */

defined( 'ABSPATH' ) || exit;

class PMPRO_Rebate_Reports {

	/**
	 * All chapters
	 *
	 * @since 1.0.3
	 *
	 * @var WP_Post[]
	 */
	private $chapters;

	/**
	 * All members by chapter_id
	 *
	 * @since 1.0.3
	 *
	 * @var array
	 */
	private $members;

	/**
	 * Array of PMPRO orders grouped by users
	 *
	 * @since 1.0.3
	 *
	 * @var array
	 */
	private $pmpro_orders;

	/**
	 * Array of Legacy orders grouped by users
	 *
	 * @since 1.0.3
	 *
	 * @var array
	 */
	private $legacy_orders;

	/**
	 * List of all membership levels
	 *
	 * @since 1.0.3
	 *
	 * @var Object
	 */
	private $membership_levels;

	/**
	 * History of user subscriptions
	 *
	 * @var array
	 *
	 * @since 1.0.3
	 */
	private $pmpro_memberships_users_history;

	/**
	 * PMPRO_Rebate_Reports initialization class.
	 *
	 * @since 1.0.3
	 */
	public function init() {

		add_shortcode( 'pmpro-rebate-reports', array( $this, 'add_rebate_reports' ) );

	}

	/**
	 * Chapter rebate reports shortcode
	 *
	 * @return string
	 * @since 1.0.3
	 *
	 */
	public function add_rebate_reports() {

		$this->do_export();

		ob_start();

		$user = wp_get_current_user();
		if ( in_array( 'administrator', $user->roles ) ) {
			?>
            <form method="post" name="report_form">
                <input type="hidden" name="action" value="rebate_export">
                <h4>
                    <strong><?php _e( 'Rebate reports', 'pmpro-chapters' ); ?></strong>
                </h4>
                <p>
                    From: <input type="date" name="from" value="<?php echo date( 'Y' ); ?>-01-01">
                    To: <input type="date" name="to" value="<?php echo date( 'Y-m-d', time() ); ?>">
                </p>
                <p>
                    <input type="submit" value="<?php _e( 'Get report', 'pmpro-chapters' ); ?>">
                </p>
            </form>
			<?php
		} else {
			?>
            <h4>
                <strong><?php _e( 'Rebate reports', 'pmpro-chapters' ); ?></strong>
            </h4>
            <p>
                You must be administrator for take reports
            </p>
			<?php
		}

		return ob_get_clean();
	}

	/**
	 * Make export users from chapter
	 *
	 * @since 1.0.3
	 */
	private function do_export() {

		if ( ! isset( $_REQUEST['action'] ) || 'rebate_export' !== $_REQUEST['action'] ) {
			return;
		}

		$user = wp_get_current_user();
		if ( ! in_array( 'administrator', $user->roles ) ) {
			return;
		}

		$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

		ini_set( 'max_execution_time', '500' );
		$sheet_summary_page = new Rebate_Summary( $spreadsheet, $this->get_chapters(), $this->get_members(), $this->get_pmpro_orders(), $this->get_legacy_orders() );
		$spreadsheet        = $sheet_summary_page->make_sheet();

		$sheet_all_members = new Rebate_All_Members( $spreadsheet, $this->get_chapters(), $this->get_members(), $this->get_pmpro_orders(), $this->get_legacy_orders() );
		$sheet_all_members->set_membership_levels( $this->get_membership_levels() );
		$spreadsheet = $sheet_all_members->make_sheet();
		foreach ( $this->chapters as $chapter_key => $chapter ) {
			$chapter_sheet = new Rebate_All_Members( $spreadsheet, $this->get_chapters(), $this->get_members(), $this->get_pmpro_orders(), $this->get_legacy_orders() );
			$chapter_sheet->set_membership_levels( $this->get_membership_levels() );
			$chapter_sheet->set_chapter_id( $chapter->ID );
			$spreadsheet = $chapter_sheet->make_sheet();
		}

		$spreadsheet->setActiveSheetIndex( 0 );

		$writer        = new PhpOffice\PhpSpreadsheet\Writer\Xlsx( $spreadsheet );
		$date_interval = '_from_' . date( 'm_d_Y', strtotime( $_REQUEST['from'] ) );
		$date_interval .= '_to_' . date( 'm_d_Y', strtotime( $_REQUEST['to'] ) );
		$writer->save( PMPRO_CHAPTERS_PLUGIN_PATH . 'temp/rebate_report_' . $user->ID . $date_interval . '.xlsx' );

		?>
        <script language="JavaScript">
            function goTo() {
                window.location.href = '<?php echo esc_url( PMPRO_CHAPTERS_PLUGIN_URL . 'temp/rebate_report_' . $user->ID . $date_interval . '.xlsx' ); ?>'
            }

            goTo();
        </script>
		<?php
		//wp_safe_redirect( PMPRO_CHAPTERS_PLUGIN_URL . 'temp/rebate_report_' . $user->ID . $date_interval . '.xlsx' );
		exit;

	}

	/**
	 * Get chapters list
	 *
	 * @return WP_Post[]
	 * @since 1.0.3
	 *
	 */
	private function get_chapters() {

		if ( ! $this->chapters ) {
			$this->chapters = get_transient( 'pmpro_chapters_list_usa' );

			if ( false === $this->chapters ) {

				$args           = array(
					'post_type'      => 'chapters',
					'numberposts'    => - 1,
					'posts_per_page' => - 1,
					'orderby'        => 'title',
					'order'          => 'ASC',
					'meta_query'     => array(
						'relation' => 'AND',
						array(
							'key'     => 'chapter_country',
							'value'   => 'US',
							'compare' => '=',
						),
						array(
							'relation' => 'OR',
							array(
								'key'     => 'chapter_closed',
								'value'   => 'yes',
								'compare' => '!=',
							),
							array(
								'key'     => 'chapter_closed',
								'compare' => 'NOT EXISTS',
							),
						),

					)
				);
				$query          = new WP_Query( $args );
				$this->chapters = $query->posts;

				set_transient( 'pmpro_chapters_list_usa', $this->chapters, 12 * HOUR_IN_SECONDS );
			}
		}

		return $this->chapters;
	}

	/**
	 * Get members grouped by chapter ID
	 *
	 * @return array Array of Chapters with members lists
	 *
	 * @since 1.0.3
	 */
	private function get_members() {

		if ( ! $this->members ) {

			$this->members = get_transient( 'pmpro_members_list' );
			if ( isset( $_GET['debug'] ) ) {
				$this->members = false;
			}
			if ( false === $this->members ) {

				$args = array(
					'orderby'    => array(
						'last_name' => 'ASC',
					),
					'meta_query' => array(
						'relation'  => 'AND',
						'last_name' => array(
							'key'     => 'last_name',
							'compare' => 'EXISTS',
						),
					),
				);

				$users_query = new WP_User_Query( $args );
				if ( $users_query->get_total() ) {

					foreach ( $users_query->get_results() as $user ) {
						$chapter_id = get_user_meta( $user->ID, 'chapter_id', true );
						if ( ! $chapter_id || 0 > $chapter_id ) {
							continue;
						}

						if ( ! isset( $this->members[ $chapter_id ] ) ) {
							$this->members[ $chapter_id ] = array();
						}

						$user->data->membership_level_name = '';
						$user->data->membership_level      = null;
						if ( function_exists( 'pmpro_getMembershipLevelForUser' ) ) {
							$user->data->membership_level      = pmpro_getMembershipLevelForUser( $user->ID );
							$user->data->membership_level_name = $this->get_membership_level( $user->data->membership_level );
						}

						$member_end_time                    = $this->get_member_expiration_time( $user );
						$user->data->member_expiration_time = $member_end_time;

						$member_start_date    = get_user_meta( $user->ID, 'member_member_since_date', true );
						$expired_before_range = ! is_numeric( $member_end_time )
						                        || ( $member_end_time < strtotime( $_REQUEST['from'] . ' 00:00:00' ) );
						$started_after_range  = $member_start_date && strtotime( $member_start_date ) >= strtotime( $_REQUEST['to'] . ' 23:59:59' );

						if ( $expired_before_range || $started_after_range ) {
							continue;
						}

						$this->members[ $chapter_id ][] = $user;
					}
				}

				set_transient( 'pmpro_members_list', $this->members, 5 * HOUR_IN_SECONDS );
			}
		}

		return $this->members;
	}

	/**
	 * Get user PMPRO orders history
	 *
	 * @return array
	 * @since 1.0.3
	 *
	 */
	private function get_pmpro_orders() {

		if ( ! $this->pmpro_orders ) {
			$this->pmpro_orders = get_transient( 'pmpro_orders_list' );

			if ( false === $this->pmpro_orders ) {
				global $wpdb;

				$this->pmpro_orders = array();

				$pmpro_orders_table = $wpdb->prefix . 'pmpro_membership_orders';
				$pmpro_orders       = $wpdb->get_results( "SELECT * FROM " . $pmpro_orders_table );
				foreach ( $pmpro_orders as $pmpro_order ) {
					if ( ! isset( $this->pmpro_orders[ $pmpro_order->user_id ] ) ) {
						$this->pmpro_orders[ $pmpro_order->user_id ] = array();
					}

					/*
					if ( strtotime( $_REQUEST['from'] . ' 00:00:00' ) >= strtotime( $pmpro_order->timestamp )
						 || strtotime( $_REQUEST['to'] . ' 23:59:59' ) <= strtotime( $pmpro_order->timestamp ) ) {
						continue;
					}*/

					if ( function_exists( 'pmpro_getLevel' ) ) {
						$level                                         = (array) pmpro_getLevel( $pmpro_order->membership_id );
						$level['timestamp']                            = $pmpro_order->timestamp;
						$level['order_details']                        = $pmpro_order;
						$this->pmpro_orders[ $pmpro_order->user_id ][] = (object) $level;
					}
				}

				set_transient( 'pmpro_orders_list', $this->pmpro_orders, 12 * HOUR_IN_SECONDS );
			}
		}

		return $this->pmpro_orders;
	}

	/**
	 * Get user Legacy orders history
	 *
	 * @return array
	 * @since 1.0.3
	 *
	 */
	private function get_legacy_orders() {

		if ( ! $this->legacy_orders ) {
			$this->legacy_orders = get_transient( 'legacy_orders_list' );

			if ( false === $this->legacy_orders ) {
				global $wpdb;

				$legacy_orders_table = $wpdb->prefix . 'leg_activity_history';
				$legacy_orders       = $wpdb->get_results( "SELECT * FROM " . $legacy_orders_table .
				                                           " ORDER BY activity_date DESC" );
				foreach ( $legacy_orders as $legacy_order ) {
					if ( ! isset( $this->legacy_orders[ $legacy_order->activity_member_ID ] ) ) {
						$this->legacy_orders[ $legacy_order->activity_member_ID ] = array();
					}

					$this->legacy_orders[ $legacy_order->activity_member_ID ][] = $legacy_order;
				}

				set_transient( 'legacy_orders_list', $this->legacy_orders, 12 * HOUR_IN_SECONDS );
			}
		}

		return $this->legacy_orders;
	}

	/**
	 * Get membership level for user
	 *
	 * @param $membership_level
	 *
	 * @return string
	 * @since 1.0.3
	 *
	 */
	private function get_membership_level( $membership_level ) {

		$current_level = 'None';

		if ( isset( $membership_level->ID ) && $this->get_membership_levels() ) {
			foreach ( $this->get_membership_levels() as $level ) {
				if ( $level->id == $membership_level->ID ) {
					$current_level = $level->name . ' - $' . ceil( $level->initial_payment ) . ' per year.';
					break;
				}
			}
		}

		return $current_level;
	}

	/**
	 * Get list of membership levels
	 *
	 * @return array|object|null
	 * @since 1.0.3
	 *
	 */
	private function get_membership_levels() {
		global $wpdb;

		if ( ! $this->membership_levels ) {
			$this->membership_levels = $wpdb->get_results( "SELECT * FROM {$wpdb->pmpro_membership_levels}",
				OBJECT );
		}

		return $this->membership_levels;
	}

	/**
	 * Get Member expiration time
	 *
	 * @param $user
	 *
	 * @return int
	 *
	 * @since 1.0.4
	 */
	private function get_member_expiration_time( $user ): int {

		$expiration_time = null;

		if ( $user->data->membership_level && ! empty( $user->data->membership_level->enddate ) ) {
			$expiration_time = $user->data->membership_level->enddate;
		}

		if ( null === $expiration_time ) {
			$member_expiration_date = get_user_meta( $user->ID, 'member_expiration_date', true );
			if ( ! empty( $member_expiration_date ) ) {
				$expiration_time = strtotime( $member_expiration_date );
			}
		}

		if ( null === $expiration_time ) {
			if ( in_array( $user->data->membership_level->ID, array( 1, 4, 5, 6, 7, 8 ) ) ) {
				$membership_level_years = 1;
			} else if ( in_array( $user->data->membership_level->ID, array( 2, 9, 10, 11, 12, 13 ) ) ) {
				$membership_level_years = 3;
			}

			if ( isset( $membership_level_years ) && ! empty( $user->data->membership_level->startdate ) ) {
				$expiration_time = strtotime( '+' . $membership_level_years . ' years', $user->data->membership_level->startdate );
			}
		}

		if ( null === $expiration_time ) {

			$levels_history = $this->get_levels_history( $user->ID );

			if ( 0 < count( $levels_history ) ) {
				$last_level      = array_pop( $levels_history );
				$expiration_time = strtotime( $last_level->enddate );
			}
		}

		if ( null === $expiration_time ) {
			$expiration_time = strtotime( '01.01.2100' );
		}

		return $expiration_time;
	}

	/**
	 * Get levels history for user
	 *
	 * @param $user_id
	 *
	 * @return array
	 *
	 * @since 1.0.3
	 */
	public function get_levels_history( $user_id ): array {

		if ( ! $this->pmpro_memberships_users_history ) {

			$this->pmpro_memberships_users_history = get_transient( 'pmpro_levels_history' );
			if ( isset( $_GET['debug'] ) ) {
				$this->pmpro_memberships_users_history = false;
			}

			if ( false === $this->pmpro_memberships_users_history ) {
				global $wpdb;

				$this->pmpro_memberships_users_history = $wpdb->get_results( "SELECT * FROM $wpdb->pmpro_memberships_users WHERE user_id != '0' ORDER BY id DESC" );
			}
		}

		$levels_history = array();

		foreach ( $this->pmpro_memberships_users_history as $user_history ) {
			if ( $user_id == $user_history->user_id ) {
				$levels_history[] = $user_history;
			}
		}

		return $levels_history;
	}

}

function pmpro_rebate_reports_runner() {

	$rebate = new PMPRO_Rebate_Reports;
	$rebate->init();

	return true;
}

pmpro_rebate_reports_runner();