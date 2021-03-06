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
	 * @since 1.0.0
	 *
	 * @return string
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
								<?php _e( 'Click here to download your chapter report as an excel file.', 'pmpro-chapters' ); ?>
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
		     ( ! in_array( 'administrator', $user->roles ) && $chapter_president !== $user->ID ) ) {
			return;
		}

		$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
		$sheet       = $spreadsheet->getActiveSheet();
		$chapter     = get_post( $chapter_id );

		$row         = 1;
		$this->set_chapter_name( $sheet, $chapter, $row );

		$this->set_titles( $sheet, $row );
		$this->set_users( $sheet, $chapter_id, $row );
		$this->set_autosize( $sheet );

		$writer = new PhpOffice\PhpSpreadsheet\Writer\Xlsx( $spreadsheet );
		$writer->save( PMPRO_CHAPTERS_PLUGIN_PATH . 'temp/chapter_' . $chapter->post_name . '_' . $user->ID . '.xlsx' );

		wp_safe_redirect( PMPRO_CHAPTERS_PLUGIN_URL . 'temp/chapter_' . $chapter->post_name . '_' . $user->ID . '.xlsx' );
		exit;

	}

	/**
	 * Get membership level for user
	 *
	 * @since 1.0.0
	 *
	 * @return string
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
	 * @since 1.0.0
	 *
	 * @return array|object|null
	 */
	private function get_membership_levels() {
		global $wpdb;

		if ( ! $this->membership_levels ) {
			$this->membership_levels = $wpdb->get_results( "SELECT * FROM {$wpdb->pmpro_membership_levels}", OBJECT );
		}

		return $this->membership_levels;
	}

	/**
	 * Get activity type for membership plan
	 *
	 * @since 1.0.0
	 *
	 * @param $membership_levels
	 *
	 * @return string
	 */
	private function get_activity_type( $membership_levels ) {

		if ( 1 < count( $membership_levels ) ) {
			$activity_type = 'Renewal';
		} else {
			$activity_type = 'Join';
		}

		$activity_type .= ' ' . $membership_levels[0]->name;

		return $activity_type;
	}

	/**
	 * Get since date for user
	 *
	 * @since 1.0.0
	 *
	 * @param $user_id
	 *
	 * @return false|string
	 */
	private function get_member_since_date( $user_id ) {

		$member_legacy_ID = get_user_meta( $user_id, 'member_legacy_ID', true );
		if ( ! empty( $member_legacy_ID ) && is_numeric( $member_legacy_ID ) && 0 < $member_legacy_ID ) {
			$member_since_date = get_user_meta( $user_id, 'member_member_since_date', true );
			$since_date        = date_i18n( 'm/d/y', strtotime( $member_since_date ) );
		} else {
			$user_data  = get_userdata( $user_id );
			$since_date = date_i18n( 'm/d/y', strtotime( $user_data->user_registered ) );
		}

		return isset( $since_date ) ? $since_date : '';
	}

	/**
	 * Get end date for membership
	 *
	 * @since 1.0.0
	 *
	 * @param $membership_level
	 *
	 * @return string
	 */
	private function get_end_date( $membership_level ) {

		$end_date = empty( $membership_level->enddate ) ? __( '01/01/2100', 'pmpro-chapters' )
			: date_i18n( 'm/d/y', $membership_level->enddate );

		return $end_date;
	}

	/**
	 * Get expiration date for previous membership
	 *
	 * @since 1.0.0
	 *
	 * @param $membership_levels
	 *
	 * @return string
	 */
	private function get_previous_end_date( $membership_levels ) {

		$end_date = ! isset( $membership_levels[1] ) || empty( $membership_levels[1]->enddate )
			? '' : date_i18n( 'm/d/y', $membership_levels[1]->enddate );

		return $end_date;
	}

	/**
	 * Get user PMPRO orders history
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	private function get_pmpro_orders( $user_id ) {

		if ( ! $this->pmpro_orders ) {
			global $wpdb;

			$pmpro_orders_table = $wpdb->prefix . 'pmpro_membership_orders';
			$pmpro_orders       = $wpdb->get_results( "SELECT * FROM " . $pmpro_orders_table );
			foreach ( $pmpro_orders as $pmpro_order ) {
				if ( ! isset( $this->pmpro_orders[ $pmpro_order->user_id ] ) ) {
					$this->pmpro_orders[ $pmpro_order->user_id ] = array();
				}

				if ( function_exists( 'pmpro_getLevel' ) ) {
					$this->pmpro_orders[ $pmpro_order->user_id ][] = pmpro_getLevel( $pmpro_order->membership_id );
				}

			}
		}

		return isset( $this->pmpro_orders[ $user_id ] ) ? $this->pmpro_orders[ $user_id ] : array();
	}

	/**
	 * Get user Legacy orders history
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	private function get_legacy_orders( $member_legacy_id ) {

		if ( ! $this->legacy_orders ) {
			global $wpdb;

			$legacy_orders_table = 'wp87_leg_activity_history';
			$legacy_orders       = $wpdb->get_results( "SELECT * FROM " . $legacy_orders_table . " ORDER BY activity_date DESC" );
			foreach ( $legacy_orders as $legacy_order ) {
				if ( ! isset( $this->legacy_orders[ $legacy_order->activity_member_ID ] ) ) {
					$this->legacy_orders[ $legacy_order->activity_member_ID ] = array();
				}

				$this->legacy_orders[ $legacy_order->activity_member_ID ][] = $legacy_order;

			}
		}

		return isset( $this->pmpro_orders[ $member_legacy_id ] ) ? $this->pmpro_orders[ $member_legacy_id ] : array();
	}

	/**
	 * Set columns size by content
	 *
	 * @since 1.0.0
	 *
	 * @param $sheet
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

	}

	/**
	 * Prepare status for xls
	 *
	 * @since 1.0.0
	 *
	 * @param $user_id int
	 *
	 * @return string
	 */
	private function get_status( $user_id ) {

		$member_status = get_user_meta( $user_id, 'member_status', true );

		return ! empty( $member_status ) ? $member_status : '';
	}

	/**
	 * Set titles for all columns
	 *
	 * @since 1.0.0
	 *
	 * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
	 */
	private function set_titles( \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet &$sheet, &$row ) {

		$row += 0;
		$sheet->setCellValue( 'B' . $row, __( 'Last Name', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'C' . $row, __( 'First Name', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'D' . $row, __( 'Membership Level', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'E' . $row, __( 'Activity Type', 'pmpro-chapters' ) );

		$sheet->setCellValue( 'F' . $row, __( "Current Start \nDate", 'pmpro-chapters' ) );
        $sheet->getStyle('F'. $row)->getAlignment()->setWrapText(true);

		$sheet->setCellValue( 'G' . $row, __( "Current \nAmount Paid", 'pmpro-chapters' ) );
        $sheet->getStyle('G'. $row)->getAlignment()->setWrapText(true);
		$sheet->setCellValue( 'H' . $row, __( "Current \nExpiration Date", 'pmpro-chapters' ) );
        $sheet->getStyle('H'. $row)->getAlignment()->setWrapText(true);
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
        $sheet->getStyle('Q'. $row)->getAlignment()->setWrapText(true);

		$sheet->setCellValue( 'R' . $row, __( 'Deceased Members', 'pmpro-chapters' ) );
		$sheet->getStyle( 'A' . $row . ':R' . $row )->getFont()->setSize( 14 );

	}

	/**
	 * Add users to xls file
	 *
	 * @since 1.0.0
	 *
	 * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
	 *
	 * @throws \PhpOffice\PhpSpreadsheet\Exception
	 */
	private function set_users( \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet &$sheet, $chapter_id, &$row ) {

        $args = array(
            'orderby' => array(
                'member_status' => 'ASC',
                'last_name' => 'ASC',
            ),
            'meta_query' => array(
                'relation' => 'AND',
                'last_name' => array(
                    'key' => 'last_name',
                    'compare' => 'EXISTS'
                ),
                'chapter_id' => array(
                    'key' => 'chapter_id',
                    'value' => sanitize_text_field( $chapter_id )
                ),
                'member_status' => array(
                    'key' => 'member_status',
                    'compare' => 'EXISTS'
                )
            )
        );
		$users_query = new WP_User_Query( $args );

		$live_users  = 0;
		$row ++;
		if ( 0 < $users_query->get_total() ) {
            $users_array = array();
            foreach ( $users_query->get_results() as $user ) {
                $user_info = array();
                $user_info['last_name'] = $user->last_name;
                $user_info['first_name'] = $user->first_name;

                $temp_membership = $this->get_membership_level( $user->ID );
                if($temp_membership != 'None') {
                    $pos = strpos($temp_membership, 'Year Membership');
                    $temp_membership = substr($temp_membership, 0, $pos) . 'Year Membership';
                }
                $user_info['membership'] = $temp_membership;

                if ( function_exists( 'pmpro_getMembershipLevelsForUser' ) ) {
                    $membership_levels = pmpro_getMembershipLevelsForUser( $user->ID, true );

                    if ( 0 < count( $membership_levels ) ) {
                        $membership_level = $membership_levels[0];

                        if ( isset( $membership_level->startdate ) && ! empty( $membership_level->startdate ) ) {
                            $user_info['start_date'] = date_i18n( 'm/d/y', $membership_level->startdate );
                        }
                        $user_info['amount'] = '$' . $membership_level->billing_amount;
                        $user_info['end_date'] = $this->get_end_date( $membership_level );
                        if($user_info['end_date'] == ''){
                            $user_info['end_date_day'] = $user_info['end_date_year'] = '';
                        } else {
                            $days = explode('/', $user_info['end_date']);
                            $user_info['end_date_day'] = $days[0] . '/' . $days[1];
                            $user_info['end_date_year'] = $days[2];
                        }

                        $user_info['since'] = $this->get_member_since_date( $user->ID );
                        $user_info['activity'] = $this->get_activity_type( $membership_levels );
                    }
                }
                $user_info['street'] = get_user_meta( $user->ID, 'member_addr_street_1', true );
                $user_info['city'] = get_user_meta( $user->ID, 'member_addr_city', true );
                $user_info['state'] = get_user_meta( $user->ID, 'member_addr_state', true );
                $user_info['country'] = get_user_meta( $user->ID, 'member_addr_country', true );
                $user_info['zip'] = get_user_meta( $user->ID, 'member_addr_zip', true );
                $user_info['phone'] = get_user_meta( $user->ID, 'member_addr_phone', true );
                $user_info['all_name'] = $user->first_name . ' ' . $user->last_name;
                $user_info['email'] = $user->user_email ;

                $status = $this->get_status( $user->ID );
                $user_info['status'] = $status ;

                if ( 'deceased' !== strtolower( $status ) ) {
                    $live_users ++;
                }

                array_push($users_array, $user_info);
            }

            // sorting part
            $users_array1 = $this->array_msort($users_array, array('status'=>SORT_ASC, 'end_date_year'=>SORT_DESC, 'end_date_day'=>SORT_DESC, 'last_name'=>SORT_ASC));
            //
			foreach ( $users_array1 as $user ) {
                
				$sheet->setCellValue( 'B' . $row, $user['last_name'] );
				$sheet->setCellValue( 'C' . $row, $user['first_name'] );
				$sheet->setCellValue( 'D' . $row, $user['membership'] );

                $sheet->setCellValue( 'F' . $row, $user['start_date'] );
                $sheet->setCellValue( 'G' . $row, $user['amount'] );
                $sheet->setCellValue( 'H' . $row, $user['end_date'] );
                $sheet->setCellValue( 'Q' . $row, $user['since'] );
                $sheet->setCellValue( 'E' . $row, $user['activity']);

				$sheet->setCellValue( 'I' . $row, $user['street'] );
				$sheet->setCellValue( 'J' . $row, $user['city'] );
				$sheet->setCellValue( 'K' . $row, $user['state'] );
				$sheet->setCellValue( 'L' . $row, $user['country'] );
				$sheet->setCellValue( 'M' . $row, $user['zip'] );
				$sheet->setCellValue( 'N' . $row, $user['phone'] );
				$sheet->setCellValue( 'O' . $row, $user['all_name'] );
				$sheet->setCellValue( 'P' . $row, $user['email']  );
				$sheet->setCellValue( 'R' . $row, $user['status']  );

				$row ++;
			}

		} else {

			$sheet->setCellValue( 'A' . $row, __( 'Chapter don`t have users', 'pmpro-chapters' ) . ' = ' . $live_users );
			$sheet->mergeCells( 'A' . $row . ':E' . $row );

		}

//die();
//		$row += 3;
//		$sheet->setCellValue( 'A' . $row, __( 'CURRENT MEMBERS', 'pmpro-chapters' ) . ' = ' . $live_users );
//		$sheet->mergeCells( 'A' . $row . ':E' . $row );
//		$sheet->getStyle( 'A' . $row . ':E' . $row )->getFont()->setSize( 16 );

	}


    private function array_msort($array, $cols)
    {
        $colarr = array();
        foreach ($cols as $col => $order) {
            $colarr[$col] = array();
            foreach ($array as $k => $row) { $colarr[$col]['_'.$k] = strtolower($row[$col]); }
        }
        $eval = 'array_multisort(';
        foreach ($cols as $col => $order) {
            $eval .= '$colarr[\''.$col.'\'],'.$order.',';
        }
        $eval = substr($eval,0,-1).');';
        eval($eval);
        $ret = array();
        foreach ($colarr as $col => $arr) {
            foreach ($arr as $k => $v) {
                $k = substr($k,1);
                if (!isset($ret[$k])) $ret[$k] = $array[$k];
                $ret[$k][$col] = $array[$k][$col];
            }
        }
        return $ret;

    }


	/**
	 * Set title for xls document
	 *
	 * @since 1.0.0
	 *
	 * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
	 * @param $chapter
	 *
	 * @throws \PhpOffice\PhpSpreadsheet\Exception
	 */
	private function set_chapter_name( \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet &$sheet, $chapter, &$row ) {

		$title = esc_attr( $chapter->post_title );
		//$title = __( 'NCGR CHAPTER REPORT for ', 'pmpro-chapters' ) . esc_attr( $chapter->post_title );
		//$title .= __( ' as of ', 'pmpro-chapters' ) . date_i18n( 'm/d/y H:i' );

		$sheet->setCellValue( 'A' . $row, $title );
        //$sheet->mergeCells( 'A' . $row . ':E' . $row );
		$sheet->getStyle( 'A' . $row . ':E' . $row )->getFont()->setSize( 22 );

	}

}

function pmpro_chapters_reports_runner() {

	$reports = new PMPRO_Chapters_Reports;
	$reports->init();

	return true;
}

pmpro_chapters_reports_runner();