<?php
/**
 * Class PMPRO_Chapters_Reports
 * Reports shortcode for chapter presidents
 *
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class PMPRO_Chapters_Reports {

	/**
	 * All membership levels list
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	private $membership_levels;

	/**
	 * Legacy orders by user
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	private $legacy_orders;

	/**
	 * All PMPRO orders
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	private $pmpro_orders;

	/**
	 * History of user subscriptions
	 *
	 * @var array
	 *
	 * @since 1.0.3
	 */
	private $pmpro_memberships_users_history;

	/**
	 * PMPRO_Chapters_Reports initialization class.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		add_shortcode( 'pmpro-chapters-export', array( $this, 'add_chapter_export_page' ) );

	}

	/**
	 * Chapter export shortcode
	 *
	 * @return string
	 * @since 1.0.0
	 *
	 */
	public function add_chapter_export_page() {
		global $wp;

		$this->do_export();

		ob_start();

		$user = wp_get_current_user();
		if ( in_array( 'administrator', $user->roles ) ) {
			$chapters = get_posts( array(
				'post_type'   => 'chapters',
				'numberposts' => - 1,
				'orderby'     => 'title',
				'order'       => 'ASC',
			) );
			?>
            <form method="get" name="report_form">
                <input type="hidden" name="action" value="export">
                <h4>
                    <strong><?php _e( 'Welcome to chapter reports', 'pmpro-chapters' ); ?></strong>
                </h4>
                <p>
                    <strong>
						<?php _e( 'Please, select needed chapter', 'pmpro-chapters' ); ?>
                    </strong>
                </p>
                <p>
                    <select name="chapter_id" id="chapter_id">
						<?php foreach ( $chapters as $chapter ) { ?>
							<?php
							$closed = get_post_meta( $chapter->ID, 'chapter_closed', true );
							if ( 'yes' == $closed ) {
								continue;
							}
							?>
                            <option value="<?php esc_attr_e( $chapter->ID ); ?>">
								<?php esc_attr_e( $chapter->post_title ); ?>
                            </option>
						<?php } ?>
                    </select>
                </p>
                <p>
                    <input type="submit" value="<?php _e( 'Get report', 'pmpro-chapters' ); ?>">
                </p>
				<?php if ( isset( $_GET['debug'] ) ) { ?>
                    <input type="hidden" name="debug" value="1">
				<?php } ?>
            </form>
			<?php
		} else {
			$chapters = get_posts( array(
				'post_type'  => 'chapters',
				'meta_query' => array(
					array(
						'key'     => 'chapter_president_id',
						'value'   => $user->ID,
						'compare' => '=',
					),
				),
			) );

			if ( $chapters ) {
				foreach ( $chapters as $chapter ) {
					$export_link = add_query_arg(
						array(
							'action'     => 'export',
							'chapter_id' => $chapter->ID
						),
						home_url( $wp->request )
					);
					?>
                    <h4>
                        <strong>
							<?php _e( 'Welcome', 'pmpro-chapters' ); ?>
							<?php esc_attr_e( $chapter->post_title ); ?>
							<?php _e( 'Chapter President!', 'pmpro-chapters' ); ?>
                        </strong>
                    </h4>
                    <p>
                        <a href="<?php echo esc_url( $export_link ); ?>">
                            <strong>
								<?php _e( 'Click here to download your chapter report as an excel file.',
									'pmpro-chapters' ); ?>
                            </strong>
                        </a>
                    </p>
					<?php
				}
			} else {
				?>
                <p>
                    <strong>
						<?php _e( 'Sorry, only chapter president can take report.', 'pmpro-chapters' ); ?>
                    </strong>
                </p>
				<?php
			}
		}

		return ob_get_clean();
	}

	/**
	 * Make export users from chapter
	 *
	 * @since 1.0.0
	 */
	private function do_export() {

		if ( ! isset( $_REQUEST['action'] ) || 'export' !== $_REQUEST['action'] ) {
			return;
		}

		$user              = wp_get_current_user();
		$chapter_id        = isset( $_GET['chapter_id'] ) ? (int) $_GET['chapter_id'] : 0;
		$chapter_president = get_post_meta( $chapter_id, 'chapter_president_id', true );

		if ( 0 === $chapter_id ||
		     ( ! in_array( 'administrator', $user->roles ) && $chapter_president != $user->ID ) ) {
			return;
		}

		$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
		$sheet       = $spreadsheet->getActiveSheet();
		$chapter     = get_post( $chapter_id );

		$row = 1;
		$this->set_chapter_name( $sheet, $chapter, $row );

		$this->set_titles( $sheet, $row );
		$this->set_users( $sheet, $chapter_id, $row );
		$this->set_autosize( $sheet );
		//$sheet->freezePane( 'A1' );

		$writer = new PhpOffice\PhpSpreadsheet\Writer\Xlsx( $spreadsheet );
		$writer->save( PMPRO_CHAPTERS_PLUGIN_PATH . 'temp/chapter_' . $chapter->post_name . '_' . $user->ID . '.xlsx' );

		wp_safe_redirect( PMPRO_CHAPTERS_PLUGIN_URL . 'temp/chapter_' . $chapter->post_name . '_' . $user->ID . '.xlsx' );
		exit;

	}

	/**
	 * Get membership level for user
	 *
	 * @return string
	 * @since 1.0.0
	 *
	 */
	private function get_membership_level( $user_id ) {

		$levels           = $this->get_membership_levels();
		$current_level    = __( 'None', 'pmpro-chapters' );
		$membership_level = __( 'None', 'pmpro-chapters' );
		if ( function_exists( 'pmpro_getMembershipLevelForUser' ) ) {
			$membership_level = pmpro_getMembershipLevelForUser( $user_id );
		}

		if ( isset( $membership_level->ID ) && $levels ) {
			foreach ( $levels as $level ) {
				if ( $level->id == $membership_level->ID ) {
					$current_level = $level->name . ' - ' . $level->initial_payment . ' per year.';
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
	 * @since 1.0.0
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
	 * Get data from last order for that user
	 *
	 * @param $user_id
	 *
	 * @return array
	 * @since 1.0.0
	 *
	 */
	public function get_last_order_info( $user_id ) {
		global $wpdb;

		$order          = array(
			'description' => '',
			'start_date'  => '',
			'end_date'    => '',
			'amount'      => '',
		);
		$min_date       = strtotime( '2/2/20' );
		$levels_history = $this->get_levels_history( $user_id );
		if ( 0 < count( $levels_history ) ) {
			foreach ( $levels_history as $level_history ) {
				if ( 'active' !== $level_history->status ) {
					continue;
				}

				$level = pmpro_getLevel( $level_history->membership_id );
				$order = array(
					'description' => $level->name,
					'start_date'  => $level_history->startdate,
					'end_date'    => $level_history->enddate,
					'amount'      => $level->billing_amount,
				);

				return $order;
			}
		}

		$pmpro_orders  = $this->get_pmpro_orders( $user_id );
		$legacy_orders = $this->get_legacy_orders( $user_id );

		if ( count( $pmpro_orders ) ) {

			foreach ( $pmpro_orders as $pmpro_order ) {
				if ( strtotime( $pmpro_order->timestamp ) >= $min_date ) {
					$order['description'] = $pmpro_order->name;
					$order['start_date']  = $pmpro_order->timestamp;
					$order['amount']      = $pmpro_order->billing_amount;
				}

				break;
			}

		}

		if ( count( $legacy_orders )
		     && ( empty( $order['start_date'] )
		          || ( strtotime( $order['start_date'] ) < $min_date ) ) ) {

			$legacy_orders_type_table = $wpdb->prefix . 'leg_activity_types';
			foreach ( $legacy_orders as $legacy_order ) {
				$legacy_order_type = $wpdb->get_row( "SELECT * FROM " . $legacy_orders_type_table .
				                                     " WHERE activity_type_ID=" . $legacy_order->activity_type_ID );
				if ( false !== strpos( strtolower( $legacy_order_type->activity_type_description ), 'renew' )
				     || false !== strpos( strtolower( $legacy_order_type->activity_type_description ), 'join' ) ) {

					$order['description'] = $legacy_order_type->activity_type_description;
					$order['start_date']  = $legacy_order->activity_date;
					$order['amount']      = $legacy_order->activity_amount;
					break;
				}
			}
		}

		if ( 0 === count( $pmpro_orders ) && 0 === count( $legacy_orders ) ) {
			$membership_levels = pmpro_getMembershipLevelsForUser( $user_id, true );
			if ( count( $membership_levels ) ) {
				$start_date           = ! empty( $membership_levels[ count( $membership_levels ) - 1 ]->startdate ) ?
					date( 'm/d/y', $membership_levels[ count( $membership_levels ) - 1 ]->startdate ) : '';
				$order['description'] = $membership_levels[ count( $membership_levels ) - 1 ]->name;
				$order['start_date']  = $start_date;
				$order['end_date']    = $membership_levels[ count( $membership_levels ) - 1 ]->enddate;
				$order['amount']      = $membership_levels[ count( $membership_levels ) - 1 ]->billing_amount;
			}
		}

		$order['description'] = self::check_description( $order['description'], $pmpro_orders, $legacy_orders );
		if ( '0000-00-00 00:00:00' == $order['start_date'] || strtotime( '01.01.1976' ) > strtotime( $order['start_date'] ) ) {
			$order['start_date'] = '';
		}
		if ( '0000-00-00 00:00:00' == $order['end_date'] || strtotime( '01.01.1976' ) > strtotime( $order['end_date'] ) ) {
			$order['end_date'] = '';
		}

		return $order;
	}

	/**
	 * Get since date for user
	 *
	 * @param $user_id
	 *
	 * @return false|string
	 * @since 1.0.0
	 *
	 */
	public static function get_member_since_date( $user_id ) {

		$member_legacy_ID = get_user_meta( $user_id, 'member_legacy_ID', true );
		if ( ! empty( $member_legacy_ID ) && is_numeric( $member_legacy_ID ) && 0 < $member_legacy_ID ) {
			$member_since_date = get_user_meta( $user_id, 'member_member_since_date', true );
			$member_since_date = self::remove_wrong_since_dates( $member_since_date, $user_id );
			if ( ! empty( $member_since_date ) ) {
				$since_date = date_i18n( 'm/d/y', strtotime( $member_since_date ) );
			}
		} else {
			$user_data = get_userdata( $user_id );
			if ( isset( $user_data->user_registered ) ) {
				$since_date = date_i18n( 'm/d/y', strtotime( $user_data->user_registered ) );
			}
		}

		return isset( $since_date ) ? $since_date : '';
	}

	/**
	 * Get end date for membership
	 *
	 * @param $membership_level
	 *
	 * @param $user_id
	 * @param $last_order_info
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	private function get_end_date( $membership_level, $user_id, $last_order_info ): string {

		$expiration_time = null;

		if ( $membership_level && ! empty( $membership_level->enddate ) ) {
			$expiration_time = $membership_level->enddate;
		}

		if ( null === $expiration_time ) {
			if ( in_array( $membership_level->ID, array( 1, 4, 5, 6, 7, 8 ) ) ) {
				$membership_level_years = 1;
			} else if ( in_array( $membership_level->ID, array( 2, 9, 10, 11, 12, 13 ) ) ) {
				$membership_level_years = 3;
			}

			if ( isset( $membership_level_years ) && ! empty( $membership_level->startdate ) ) {
				$expiration_time = strtotime( '+' . $membership_level_years . ' years', $membership_level->startdate );
			}
		}

		if ( empty( $membership_level ) && strtotime( '01.01.1975' ) > $expiration_time ) {
			$expiration_time = strtotime( $last_order_info['start_date'] );
		}

		if ( null === $expiration_time ) {

			$levels_history = $this->get_levels_history( $user_id );

			if ( 0 < count( $levels_history ) ) {
				$last_level = array_pop( $levels_history );
				if ( strtotime( '01.01.1975' ) < strtotime( $last_level->enddate ) ) {
					$expiration_time = strtotime( $last_level->enddate );
				}
			}
		}

		if ( null === $expiration_time ) {
			$expiration_time = strtotime( '01.01.2100' );
		}

		return date_i18n( 'm/d/Y', $expiration_time );
	}

	/**
	 * Get user PMPRO orders history
	 *
	 * @param $user_id
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	private function get_pmpro_orders( $user_id ): array {

		if ( ! $this->pmpro_orders ) {
			global $wpdb;

			$pmpro_orders_table = $wpdb->prefix . 'pmpro_membership_orders';
			$pmpro_orders       = $wpdb->get_results( "SELECT * FROM " . $pmpro_orders_table );
			foreach ( $pmpro_orders as $pmpro_order ) {
				if ( ! isset( $this->pmpro_orders[ $pmpro_order->user_id ] ) ) {
					$this->pmpro_orders[ $pmpro_order->user_id ] = array();
				}

				if ( function_exists( 'pmpro_getLevel' ) ) {
					$level                                         = (array) pmpro_getLevel( $pmpro_order->membership_id );
					$level['timestamp']                            = $pmpro_order->timestamp;
					$this->pmpro_orders[ $pmpro_order->user_id ][] = (object) $level;
				}

			}
		}

		return isset( $this->pmpro_orders[ $user_id ] ) ? $this->pmpro_orders[ $user_id ] : array();
	}

	/**
	 * Get user Legacy orders history
	 *
	 * @param $user_id
	 *
	 * @return array
	 * @since 1.0.0
	 *
	 */
	private function get_legacy_orders( $user_id ) {

		$member_legacy_id = get_user_meta( $user_id, 'member_legacy_ID', true );
		if ( ! $this->legacy_orders && $member_legacy_id ) {
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
		}

		return isset( $this->legacy_orders[ $member_legacy_id ] ) ? $this->legacy_orders[ $member_legacy_id ] : array();
	}

	/**
	 * Set columns size by content
	 *
	 * @param $sheet
	 *
	 * @since 1.0.0
	 *
	 */
	private function set_autosize( &$sheet ) {

		$sheet->getColumnDimension( 'A' )->setAutoSize( true );
		$sheet->getColumnDimension( 'B' )->setAutoSize( true );
		$sheet->getColumnDimension( 'C' )->setAutoSize( true );
		$sheet->getColumnDimension( 'D' )->setAutoSize( true );
		$sheet->getColumnDimension( 'E' )->setAutoSize( true );
		$sheet->getColumnDimension( 'F' )->setAutoSize( true );
		$sheet->getColumnDimension( 'G' )->setAutoSize( true );
		$sheet->getColumnDimension( 'H' )->setAutoSize( true );
		$sheet->getColumnDimension( 'I' )->setAutoSize( true );
		$sheet->getColumnDimension( 'J' )->setAutoSize( true );
		$sheet->getColumnDimension( 'K' )->setAutoSize( true );
		$sheet->getColumnDimension( 'L' )->setAutoSize( true );
		$sheet->getColumnDimension( 'M' )->setAutoSize( true );
		$sheet->getColumnDimension( 'N' )->setAutoSize( true );
		$sheet->getColumnDimension( 'O' )->setAutoSize( true );
		$sheet->getColumnDimension( 'P' )->setAutoSize( true );
		$sheet->getColumnDimension( 'Q' )->setAutoSize( true );
		$sheet->getColumnDimension( 'R' )->setAutoSize( true );

		$sheet->getStyle( 'F2:F' . $sheet->getHighestRow() )
		      ->getNumberFormat()
		      ->setFormatCode( 'mm/dd/yyyy' );
		$sheet->getStyle( 'H2:H' . $sheet->getHighestRow() )
		      ->getNumberFormat()
		      ->setFormatCode( 'mm/dd/yyyy' );
		$sheet->getStyle( 'Q2:Q' . $sheet->getHighestRow() )
		      ->getNumberFormat()
		      ->setFormatCode( 'mm/dd/yyyy' );

		$align_left = \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT;
		$sheet->getStyle( 'M2:M' . $sheet->getHighestRow() )
		      ->getAlignment()
		      ->setHorizontal( $align_left );
		$sheet->getStyle( 'N2:N' . $sheet->getHighestRow() )
		      ->getAlignment()
		      ->setHorizontal( $align_left );

	}

	/**
	 * Prepare status for xls
	 *
	 * @param $user_id int
	 *
	 * @return string
	 * @since 1.0.0
	 *
	 */
	private function get_status( $user_id ) {

		$member_status = get_user_meta( $user_id, 'member_status', true );

		return ! empty( $member_status ) ? $member_status : '';
	}

	/**
	 * Set titles for all columns
	 *
	 * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
	 *
	 * @since 1.0.0
	 *
	 */
	private function set_titles( \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet &$sheet, &$row ) {

		$row += 0;
		$sheet->setCellValue( 'B' . $row, __( 'Last Name', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'C' . $row, __( 'First Name', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'D' . $row, __( 'Membership Level', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'E' . $row, __( 'Activity Type', 'pmpro-chapters' ) );

		$sheet->setCellValue( 'F' . $row, __( "Current Start \nDate", 'pmpro-chapters' ) );
		$sheet->getStyle( 'F' . $row )->getAlignment()->setWrapText( true );

		$sheet->setCellValue( 'G' . $row, __( "Current \nAmount Paid ($)", 'pmpro-chapters' ) );
		$sheet->getStyle( 'G' . $row )->getAlignment()->setWrapText( true );
		$sheet->setCellValue( 'H' . $row, __( "Current \nExpiration Date", 'pmpro-chapters' ) );
		$sheet->getStyle( 'H' . $row )->getAlignment()->setWrapText( true );
		//$sheet->setCellValue( 'G' . $row, __( 'Previous Expiration Date', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'I' . $row, __( 'Address Line 1', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'J' . $row, __( 'City', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'K' . $row, __( 'State', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'L' . $row, __( 'Country	', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'M' . $row, __( 'Zip Code', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'N' . $row, __( 'Phone Number 1', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'O' . $row, __( 'Combined Name', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'P' . $row, __( 'Email', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'Q' . $row, __( "Active Since \nDate", 'pmpro-chapters' ) );
		$sheet->getStyle( 'Q' . $row )->getAlignment()->setWrapText( true );

		$sheet->setCellValue( 'R' . $row, __( 'Deceased Members', 'pmpro-chapters' ) );
		$sheet->getStyle( 'A' . $row . ':R' . $row )->getFont()->setSize( 14 );

	}

	/**
	 * Add users to xls file
	 *
	 * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
	 *
	 * @throws \PhpOffice\PhpSpreadsheet\Exception
	 * @since 1.0.0
	 *
	 */
	private function set_users( \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet &$sheet, $chapter_id, &$row ) {

		$args        = array(
			'orderby'    => array(
				'member_status' => 'ASC',
				'last_name'     => 'ASC',
			),
			'meta_query' => array(
				'relation'      => 'AND',
				'last_name'     => array(
					'key'     => 'last_name',
					'compare' => 'EXISTS'
				),
				'chapter_id'    => array(
					'key'   => 'chapter_id',
					'value' => sanitize_text_field( $chapter_id )
				),
				'member_status' => array(
					'key'     => 'member_status',
					'compare' => 'EXISTS'
				),
			)
		);
		$users_query = new WP_User_Query( $args );

		$live_users = 0;
		$row ++;
		if ( 0 < $users_query->get_total() ) {
			$users_array = array();
			foreach ( $users_query->get_results() as $user ) {

				$user_info               = array();
				$user_info['user_id']    = $user->ID;
				$user_info['last_name']  = $user->last_name;
				$user_info['first_name'] = $user->first_name;

				$temp_membership = $this->get_membership_level( $user->ID );
				if ( $temp_membership != 'None' ) {
					$pos             = strpos( $temp_membership, 'Year Membership' );
					$temp_membership = substr( $temp_membership, 0, $pos ) . 'Year Membership 1';
				}
				$user_info['membership'] = $temp_membership;

				$last_order_info         = $this->get_last_order_info( $user->ID );
				$user_info['activity']   = $last_order_info['description'];
				$user_info['start_date'] = $last_order_info['start_date'];


				if ( empty( $user_info['activity'] ) ) {
					continue;
				}

				if ( function_exists( 'pmpro_getMembershipLevelForUser' ) ) {
					$membership_level = pmpro_getMembershipLevelForUser( $user->ID );

					if ( isset( $membership_level->name ) ) {
						$user_info['membership'] = $membership_level->name;
					}

					$user_info['amount'] = 0 < $last_order_info['amount'] ? $last_order_info['amount']
						: $this->get_billed_amount( $membership_level );

					$user_info['end_date'] = $this->get_end_date( $membership_level, $user->ID, $last_order_info );

					if ( empty( $user_info['end_date'] ) ) {
						$user_info['end_date_day'] = $user_info['end_date_year'] = '';
					} else {
						$days                       = explode( '/', $user_info['end_date'] );
						$user_info['end_date_day']  = $days[0] . '/' . $days[1];
						$user_info['end_date_year'] = $days[2];
					}

					$user_info['since'] = self::get_member_since_date( $user->ID );

				}

				$user_info['street']   = get_user_meta( $user->ID, 'member_addr_street_1', true );
				$user_info['city']     = get_user_meta( $user->ID, 'member_addr_city', true );
				$user_info['state']    = get_user_meta( $user->ID, 'member_addr_state', true );
				$user_info['country']  = get_user_meta( $user->ID, 'member_addr_country', true );
				$user_info['zip']      = get_user_meta( $user->ID, 'member_addr_zip', true );
				$user_info['phone']    = get_user_meta( $user->ID, 'member_addr_phone', true );
				$user_info['all_name'] = $user->first_name . ' ' . $user->last_name;
				$user_info['email']    = $user->user_email;

				$status              = $this->get_status( $user->ID );
				$user_info['status'] = $status;

				if ( 'deceased' !== strtolower( $status ) ) {
					$live_users ++;
				}

				if ( strtotime( $user_info['end_date'] ) < time() ) {
					$user_info['membership'] = __( 'none', 'pmpro-chapters' );
				}

				array_push( $users_array, $user_info );
			}

			$users_array1 = $this->array_msort( $users_array, array(
				'status'        => SORT_ASC,
				'end_date_year' => SORT_DESC,
				'end_date_day'  => SORT_DESC,
				'last_name'     => SORT_ASC
			) );

			foreach ( $users_array1 as $user ) {
				if ( isset( $_GET['debug'] ) ) {
					$sheet->setCellValue( 'A' . $row, $user['user_id'] );
				}

				$sheet->setCellValue( 'B' . $row, $user['last_name'] );
				$sheet->setCellValue( 'C' . $row, $user['first_name'] );
				$sheet->setCellValue( 'D' . $row, $user['membership'] );

				if ( ! empty( $user['start_date'] ) ) {
					$start_date = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(
						strtotime( $user['start_date'] ) );
					$sheet->setCellValue( 'F' . $row, $start_date );
				}

				$sheet->setCellValue( 'G' . $row, $user['amount'] );

				if ( ! empty( $user['end_date'] ) && 2 < mb_strlen( $user['end_date'] ) ) {
					$end_date = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(
						strtotime( $user['end_date'] ) );
					$sheet->setCellValue( 'H' . $row, $end_date );
				}

				if ( ! empty( $user['since'] ) ) {
					$since_date = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(
						strtotime( $user['since'] ) );
				}

				$phone = trim( get_user_meta( $user['user_id'], 'pmpro_bphone', true ) );
				$phone = empty( $phone ) ? trim( get_user_meta( $user['user_id'], 'billing_phone', true ) ) : $phone;
				$phone = empty( $phone ) ? trim( get_user_meta( $user['user_id'], 'member_phone_1', true ) ) : $phone;
				$phone = empty( $phone ) ? get_user_meta( $user['user_id'], 'member_phone_2', true ) : $phone;

				$sheet->setCellValue( 'Q' . $row, $since_date );
				$sheet->setCellValue( 'E' . $row, $user['activity'] );

				$sheet->setCellValue( 'I' . $row, $user['street'] );
				$sheet->setCellValue( 'J' . $row, $user['city'] );
				$sheet->setCellValue( 'K' . $row, $user['state'] );
				$sheet->setCellValue( 'L' . $row, $user['country'] );
				$sheet->setCellValue( 'M' . $row, $user['zip'] );
				$sheet->setCellValue( 'N' . $row, $phone );
				$sheet->setCellValue( 'O' . $row, $user['all_name'] );
				$sheet->setCellValue( 'P' . $row, $user['email'] );
				$sheet->setCellValue( 'R' . $row, $user['status'] );

				$row ++;
			}

		} else {

			$sheet->setCellValue( 'A' . $row, __( 'Chapter don`t have users', 'pmpro-chapters' ) .
			                                  ' = ' . $live_users );
			$sheet->mergeCells( 'A' . $row . ':E' . $row );

		}

	}

	/**
	 * Multisort results
	 *
	 * @param $array
	 * @param $cols
	 *
	 * @return array
	 * @since 1.0.0
	 *
	 */
	private function array_msort( $array, $cols ) {

		$colarr = array();
		foreach ( $cols as $col => $order ) {
			$colarr[ $col ] = array();
			foreach ( $array as $k => $row ) {
				$colarr[ $col ][ '_' . $k ] = strtolower( $row[ $col ] );
			}
		}

		$eval = 'array_multisort(';
		foreach ( $cols as $col => $order ) {
			$eval .= '$colarr[\'' . $col . '\'],' . $order . ',';
		}

		$eval = substr( $eval, 0, - 1 ) . ');';
		eval( $eval );
		$ret = array();

		foreach ( $colarr as $col => $arr ) {
			foreach ( $arr as $k => $v ) {
				$k = substr( $k, 1 );
				if ( ! isset( $ret[ $k ] ) ) {
					$ret[ $k ] = $array[ $k ];
				}
				$ret[ $k ][ $col ] = $array[ $k ][ $col ];
			}
		}

		return $ret;
	}


	/**
	 * Set title for xls document
	 *
	 * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
	 * @param $chapter
	 *
	 * @throws \PhpOffice\PhpSpreadsheet\Exception
	 * @since 1.0.0
	 *
	 */
	private function set_chapter_name( \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet &$sheet, $chapter, &$row ) {

		$title = esc_attr( $chapter->post_title );
		//$title = __( 'NCGR CHAPTER REPORT for ', 'pmpro-chapters' ) . esc_attr( $chapter->post_title );
		//$title .= __( ' as of ', 'pmpro-chapters' ) . date_i18n( 'm/d/y H:i' );

		$sheet->setCellValue( 'A' . $row, $title );
		//$sheet->mergeCells( 'A' . $row . ':E' . $row );
		$sheet->getStyle( 'A' . $row . ':E' . $row )->getFont()->setSize( 22 );

	}

	/**
	 * Return total billed summ for that level
	 *
	 * @param $membership_level
	 *
	 * @return string
	 * @since 1.0.0
	 *
	 */
	private function get_billed_amount( $membership_level ) {

		if ( ! empty( $membership_level ) ) {
			$membership_level->original_initial_payment = $membership_level->initial_payment;
			$membership_level->initial_payment          = $membership_level->billing_amount;
		}

		if ( empty( $membership_level ) || pmpro_isLevelFree( $membership_level ) ) {
			if ( ! empty( $membership_level->original_initial_payment ) && $membership_level->original_initial_payment > 0 ) {
				$total = $membership_level->original_initial_payment;
			} else {
				$total = 0;
			}
		} else {
			$total = $membership_level->initial_payment;
		}

		return $total;
	}

	/**
	 * Remove wrong dates like 1970
	 *
	 * @param $member_since_date
	 * @param $user_id
	 *
	 * @return string
	 * @since 1.0.0
	 *
	 */
	public static function remove_wrong_since_dates( $member_since_date, $user_id ) {

		if ( strtotime( "1980" ) > strtotime( $member_since_date ) ) {
			update_user_meta( $user_id, 'member_member_since_date', '' );

			return '';
		}

		return $member_since_date;
	}

	/**
	 * Add for this type word "Renewal" or "Join"
	 *
	 * @param $activity_type
	 *
	 * @param $pmpro_orders
	 * @param $legacy_orders
	 *
	 * @return string
	 * @since 1.0.0
	 *
	 */
	public static function check_description( $activity_type, $pmpro_orders, array $legacy_orders = array() ) {

		if ( ! is_array( $pmpro_orders ) ) {
			$pmpro_orders = array();
		}

		if ( ! empty( $activity_type )
		     && false === strpos( strtolower( $activity_type ), 'renew' )
		     && false === strpos( strtolower( $activity_type ), 'join' ) ) {
			$is_renewal = ( 2 <= count( $pmpro_orders )
			                || ( 1 === count( $pmpro_orders ) && 0 < count( $legacy_orders ) ) );
			$order_type = $is_renewal ? ' - Renew ' : ' - Join ';

			if ( false !== strpos( strtolower( $activity_type ), 'individual' ) ) {
				$type_name = explode( '- Individual', $activity_type );
				if ( 2 <= count( $type_name ) ) {
					$activity_type = $type_name[0] . $order_type . '- Individual' . $type_name[1];
				}
			} elseif ( false !== strpos( strtolower( $activity_type ), 'couple' ) ) {
				$type_name = explode( '- Couple', $activity_type );
				if ( 2 <= count( $type_name ) ) {
					$activity_type = $type_name[0] . $order_type . '- Couple' . $type_name[1];
				}
			}
		}

		return $activity_type;
	}

	/**
	 * Remove from text words like Renewal and Join
	 *
	 * @param $description
	 *
	 * @return string
	 * @since 1.0.3
	 *
	 */
	public static function clear_description( $description ) {

		if ( ! empty( $description ) ) {
			$description = str_replace( 'Join - ', '', $description );
			$description = str_replace( 'Renew - ', '', $description );
		}

		return $description;
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
	private function get_levels_history( $user_id ): array {

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
			if ( $user_id == $user_history->user_id && 'active' == $user_history->status ) {
				$levels_history[] = $user_history;
			}
		}

		return $levels_history;
	}

}

function pmpro_chapters_reports_runner() {

	$reports = new PMPRO_Chapters_Reports;
	$reports->init();

	return true;
}

pmpro_chapters_reports_runner();