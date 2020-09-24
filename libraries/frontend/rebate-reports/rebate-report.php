<?php
/**
 * Class Rebate_Report
 * Fabric Method for Rebate reports
 *
 * @since 1.0.3
 */

abstract class Rebate_Report {

	/**
	 * Object for work with PHPOffice
	 *
	 * @since 1.0.3
	 *
	 * @var \PhpOffice\PhpSpreadsheet\Spreadsheet
	 */
	protected $spreadsheet;

	/**
	 * All chapters
	 *
	 * @since 1.0.3
	 *
	 * @var WP_Post[]
	 */
	protected $chapters;

	/**
	 * All members by chapter_id
	 *
	 * @since 1.0.3
	 *
	 * @var array
	 */
	protected $members;

	/**
	 * Array of PMPRO orders grouped by users
	 *
	 * @since 1.0.3
	 *
	 * @var array
	 */
	protected $pmpro_orders;

	/**
	 * Array of Legacy orders grouped by users
	 *
	 * @since 1.0.3
	 *
	 * @var array
	 */
	protected $legacy_orders;

	/**
	 * List of all membership levels
	 *
	 * @since 1.0.3
	 *
	 * @var Object
	 */
	protected $membership_levels;

	/**
	 * Selected chapter for this sheet
	 *
	 * @since 1.0.3
	 *
	 * @var int
	 */
	protected $chapter_id;

	/**
	 * Report constructor.
	 *
	 * @since 1.0.3
	 *
	 * @param \PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet
	 * @param $chapters
	 * @param $members
	 * @param $pmpro_orders
	 */
	public function __construct( \PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet, $chapters, $members, $pmpro_orders, $legacy_orders ) {

		$this->spreadsheet   = $spreadsheet;
		$this->chapters      = $chapters;
		$this->members       = $members;
		$this->pmpro_orders  = $pmpro_orders;
		$this->legacy_orders = $legacy_orders;

	}

	/**
	 * Safe set membership levels
	 *
	 * @since 1.0.3
	 *
	 * @param $membership_levels
	 */
	public function set_membership_levels( $membership_levels ) {

		$this->membership_levels = $membership_levels;

	}

	/**
	 * Add chapter ID for make sheet
	 *
	 * @since 1.0.3
	 *
	 * @param $chapter_id
	 */
	public function set_chapter_id( $chapter_id ) {

		$this->chapter_id = $chapter_id;

	}

	/**
	 * Get amount paid by chapter
	 *
	 * @since 1.0.3
	 *
	 * @param $members
	 *
	 * @return int
	 */
	protected function get_amount_paid( $members ) {

		$amount = 0;

		if ( is_array( $members ) && 0 < count( $members ) ) {
			foreach ( $members as $member ) {
				if ( ! isset( $this->pmpro_orders[ $member->ID ] ) ) {
					continue;
				}

				foreach ( $this->pmpro_orders[ $member->ID ] as $order ) {
					if ( strtotime( '01.07.2019' ) > strtotime( $order->timestamp ) ) {
						continue;
					}

					$amount += $order->billing_amount;
				}
			}
		}

		return $amount;
	}

	/**
	 * Get data from last order for that user
	 *
	 * @since 1.0.3
	 *
	 * @param $user_id
	 *
	 * @return array
	 */
	protected function get_last_orders_info( $user_id ) {

		$orders        = array();
		$pmpro_orders  = $this->pmpro_orders[ $user_id ];
		$legacy_orders = isset( $this->legacy_orders[ $user_id ] ) ? $this->legacy_orders[ $user_id ] : array();

		if ( is_array( $pmpro_orders ) && 0 < count( $pmpro_orders ) ) {
			foreach ( $pmpro_orders as $pmpro_order ) {
				$order = $this->get_empty_order();

				$order['description']  = PMPRO_Chapters_Reports::check_description( $pmpro_order->name, $pmpro_orders, $legacy_orders );
				$order['start_date']   = $pmpro_order->timestamp;
				$order['amount']       = $pmpro_order->billing_amount;
				$order['payment_type'] = $pmpro_order->order_details->gateway;
				if ( ! empty( $pmpro_order->order_details->payment_type ) ) {
					$order['payment_type'] = $pmpro_order->order_details->gateway;
				}
				$order['discount'] = ! empty( $pmpro_order->certificateamount ) ? $pmpro_order->certificateamount : $pmpro_order->couponamount;

				if ( '0000-00-00 00:00:00' == $order['start_date'] || strtotime( '01.01.1976' ) > strtotime( $order['start_date'] ) ) {
					$order['start_date'] = '';
				}

				if ( '0000-00-00 00:00:00' == $order['end_date'] || strtotime( '01.01.1976' ) > strtotime( $order['end_date'] ) ) {
					$order['end_date'] = '';
				}

				$orders[] = $order;

				if ( 1 < count( $orders ) ) {
					break;
				}
			}
		}

		if ( 0 === count( $orders ) ) {
			$membership_levels = pmpro_getMembershipLevelsForUser( $user_id, true );
			if ( count( $membership_levels ) ) {
				$order = $this->get_empty_order();

				for ( $key = count( $membership_levels ) - 1; $key > 0; $key -- ) {
					$start_date           = ! empty( $membership_levels[ $key ]->startdate ) ?
						date( 'm/d/y', $membership_levels[ $key ]->startdate ) : '';
					$order['description'] = PMPRO_Chapters_Reports::check_description( $membership_levels[ $key ]->name, $pmpro_orders, $legacy_orders );
					$order['start_date']  = $start_date;
					$order['end_date']    = $membership_levels[ $key ]->enddate;
					$order['amount']      = $membership_levels[ $key ]->billing_amount;

					if ( '0000-00-00 00:00:00' == $order['start_date'] || strtotime( '01.01.1976' ) > strtotime( $order['start_date'] ) ) {
						$order['start_date'] = '';
					}

					if ( '0000-00-00 00:00:00' == $order['end_date'] || strtotime( '01.01.1976' ) > strtotime( $order['end_date'] ) ) {
						$order['end_date'] = '';
					}

					$orders[] = $order;

					if ( 1 < count( $orders ) ) {
						break;
					}
				}
			}
		}

		if ( 1 <= count( $orders ) ) {
			$member_legacy_id = get_user_meta( $user_id, 'member_legacy_ID', true );
			if ( $member_legacy_id && isset( $this->legacy_orders[ $user_id ][0] ) ) {
				$legacy_type          = $this->get_legacy_type( $this->legacy_orders[ $user_id ][0] );
				$order                = $this->get_empty_order();
				$order['description'] = PMPRO_Chapters_Reports::check_description( $legacy_type['description'], $pmpro_orders, $legacy_orders );
				$order['start_date']  = $legacy_type['start_date'];
				$order['amount']      = $legacy_type['amount'];
			}
		}

		if ( 0 === count( $orders ) ) {
			$orders[] = $this->get_empty_order();
		}

		return $orders;
	}

	/**
	 * Make empty order array
	 *
	 * @since 1.0.3
	 *
	 * @return array
	 */
	private function get_empty_order() {

		return array(
			'description'      => '',
			'start_date'       => '',
			'end_date'         => '',
			'amount'           => 0,
			'payment_type'     => '',
			'transaction_date' => '',
			'amount_rebate'    => 0,
		);
	}

	/**
	 * Get legacy order type
	 *
	 * @since 1.0.3
	 *
	 * @return array
	 */
	private function get_legacy_type( $legacy_order ) {
		global $wpdb;

		$order = $this->get_empty_order();

		$legacy_orders_type_table = $wpdb->prefix . 'leg_activity_types';
		$legacy_order_type        = $wpdb->get_row( "SELECT * FROM " . $legacy_orders_type_table .
		                                            " WHERE activity_type_ID=" . $legacy_order->activity_type_ID );
		if ( false !== strpos( strtolower( $legacy_order_type->activity_type_description ), 'renew' )
		     || false !== strpos( strtolower( $legacy_order_type->activity_type_description ), 'join' ) ) {

			$order['description'] = $legacy_order_type->activity_type_description;
			$order['start_date']  = $legacy_order->activity_date;
			$order['amount']      = $legacy_order->activity_amount;
		}

		return $order;
	}

}