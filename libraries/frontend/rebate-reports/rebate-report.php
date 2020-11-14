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
	 * @return array
	 */
	protected function get_amount_paid( $members ) {

		$amount = array(
			'paid'    => 0,
			'rebate'  => 0,
			'revenue' => 0,
		);

		if ( is_array( $members ) && 0 < count( $members ) ) {
			foreach ( $members as $member ) {
				$last_orders = $this->get_last_orders_info( $member->ID );

				$amount['paid']    += $last_orders[0]['amount'];
				$amount['rebate']  += $last_orders[0]['rebate'];
				$amount['revenue'] += $last_orders[0]['revenue'];
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

		$orders           = array();
		$pmpro_orders     = $this->pmpro_orders[ $user_id ];
		$member_legacy_id = get_user_meta( $user_id, 'member_legacy_ID', true );
		$legacy_orders    = isset( $this->legacy_orders[ $member_legacy_id ] ) ? $this->legacy_orders[ $member_legacy_id ] : array();

		if ( 2 > count( $orders ) && is_array( $pmpro_orders ) && 0 < count( $pmpro_orders ) ) {
			$orders = $this->orders_from_pmpro( $orders, $pmpro_orders, $legacy_orders );
		}

		if ( 2 > count( $orders ) && is_array( $legacy_orders ) && 0 < count( $legacy_orders ) ) {
			$orders = $this->orders_from_legacy( $orders, $user_id, $pmpro_orders, $legacy_orders );
		}

		if ( 2 > count( $orders ) ) {
			$orders = $this->orders_from_levels( $user_id, $orders, $pmpro_orders, $legacy_orders );
		}

		if ( 1 < count( $orders ) ) {
			usort( $orders, function ( $a, $b ) {

				return strtotime( $a['start_date'] ) <= strtotime( $b['start_date'] );

			} );
		}

		if ( 0 < count( $orders ) ) {
			$orders = $this->hide_international( $user_id, $orders );
			$orders = $this->hide_out_of_date( $orders );
		}

		return 0 !== count( $orders ) ? $orders : $this->get_empty_order();
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
	 * @param $legacy_order
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

	/**
	 * Get membership names from member levels history
	 *
	 * @since 1.0.3
	 *
	 * @param $user_id
	 * @param array $orders
	 * @param $pmpro_orders
	 * @param $legacy_orders
	 *
	 * @return array
	 */
	private function orders_from_levels( $user_id, array $orders, $pmpro_orders, $legacy_orders ) {

		$membership_levels = pmpro_getMembershipLevelsForUser( $user_id, true );

		if ( 0 < count( $membership_levels ) ) {
			for ( $key = count( $membership_levels ) - 1; $key >= 0; $key -- ) {
				$order                = $this->get_empty_order();
				$start_date           = ! empty( $membership_levels[ $key ]->startdate ) ?
					date( 'm/d/y', $membership_levels[ $key ]->startdate ) : '';
				$order['description'] = PMPRO_Chapters_Reports::check_description( $membership_levels[ $key ]->name, $pmpro_orders, $legacy_orders );
				$order['start_date']  = $start_date;
				$order['end_date']    = $membership_levels[ $key ]->enddate;
				$order['amount']      = round( $membership_levels[ $key ]->initial_payment );
				$order['rebate']      = 0 < $membership_levels[ $key ]->initial_payment ? round( $membership_levels[ $key ]->initial_payment / 3 ) : 0;
				$order['revenue']     = $order['amount'] - $order['rebate'];

				if ( '0000-00-00 00:00:00' == $order['start_date'] || strtotime( '01.01.1976' ) > strtotime( $order['start_date'] ) ) {
					$order['start_date'] = '';
				}

				if ( '0000-00-00 00:00:00' == $order['end_date'] || strtotime( '01.01.1976' ) > strtotime( $order['end_date'] ) ) {
					$order['end_date'] = '';
				}

				$orders[] = $order;
			}
		}

		return $orders;
	}

	/**
	 * @param $orders
	 * @param $pmpro_orders
	 * @param $legacy_orders
	 *
	 * @return array
	 */
	private function orders_from_pmpro( $orders, $pmpro_orders, $legacy_orders ) {

		foreach ( $pmpro_orders as $pmpro_order ) {
			$order = $this->get_empty_order();

			$order['description']  = PMPRO_Chapters_Reports::check_description( $pmpro_order->name, $pmpro_orders, $legacy_orders );
			$order['start_date']   = $pmpro_order->timestamp;
			$order['amount']       = round( $pmpro_order->billing_amount );
			$order['rebate']       = 0 < $pmpro_order->billing_amount ? round( $pmpro_order->billing_amount / 3 ) : 0;
			$order['revenue']      = $order['amount'] - $order['rebate'];
			$order['payment_type'] = $pmpro_order->order_details->gateway;
			$order['discount']     = ! empty( $pmpro_order->certificateamount ) ? $pmpro_order->certificateamount : $pmpro_order->couponamount;

			if ( ! empty( $pmpro_order->order_details->payment_type ) && 0 < strlen( $pmpro_order->order_details->payment_type ) ) {
				$order['payment_type'] = $pmpro_order->order_details->payment_type;
			}

			if ( '0000-00-00 00:00:00' == $order['start_date'] || strtotime( '01.01.1976' ) > strtotime( $order['start_date'] ) ) {
				$order['start_date'] = '';
			}

			if ( '0000-00-00 00:00:00' == $order['end_date'] || strtotime( '01.01.1976' ) > strtotime( $order['end_date'] ) ) {
				$order['end_date'] = '';
			}

			$orders[] = $order;
		}

		return $orders;
	}

	/**
	 * Get orders from legacy orders history
	 *
	 * @since 1.0.3
	 *
	 * @param $orders
	 * @param $user_id
	 * @param $pmpro_orders
	 * @param $legacy_orders
	 *
	 * @return array
	 */
	private function orders_from_legacy( $orders, $user_id, $pmpro_orders, $legacy_orders ) {

		$member_legacy_id = get_user_meta( $user_id, 'member_legacy_ID', true );
		if ( $member_legacy_id
		     && isset( $this->legacy_orders[ $member_legacy_id ] )
		     && 0 < count( $this->legacy_orders[ $member_legacy_id ] ) ) {

			foreach ( $this->legacy_orders[ $member_legacy_id ] as $legacy_order ) {
				$legacy_type = $this->get_legacy_type( $legacy_order );
				if ( isset( $legacy_type['description'] ) && ! empty( $legacy_type['description'] ) ) {
					$order                 = $this->get_empty_order();
					$order['description']  = PMPRO_Chapters_Reports::check_description( $legacy_type['description'], $pmpro_orders, $legacy_orders );
					$order['payment_type'] = '(legacy)';
					$order['start_date']   = $legacy_type['start_date'];
					$order['amount']       = round( $legacy_type['amount'] );
					$order['rebate']       = 0 < $legacy_type['amount'] ? round( $legacy_type['amount'] / 3 ) : 0;
					$order['revenue']      = $order['amount'] - $order['rebate'];
					$orders[]              = $order;
				}

				if ( 1 < count( $orders ) ) {
					break;
				}
			}
		}

		return $orders;
	}

	/**
	 * Reset amount for non USA users
	 *
	 * @since 1.0.1
	 *
	 * @param array $orders
	 *
	 * @return array
	 */
	private function hide_international( $user_id, array $orders ) {

		$country   = get_user_meta( $user_id, 'member_addr_country', true );
		$state     = get_user_meta( $user_id, 'member_addr_state', true );
		$states    = $this->get_all_states_list();
		$countries = array(
			'USA',
			'US',
			'United States',
		);

		if ( ! in_array( $country, $countries ) && ! in_array( $state, $states ) ) {
			foreach ( $orders as $key => $order ) {
				$orders[ $key ]['amount']  = 0;
				$orders[ $key ]['rebate']  = 0;
				$orders[ $key ]['revenue'] = 0;
			}
		}

		return $orders;
	}

	/**
	 * Get list of all states from USA
	 *
	 * @since 1.0.3
	 */
	private function get_all_states_list() {

		$states = array(
			'AL' => "Alabama",
			'AK' => "Alaska",
			'AZ' => "Arizona",
			'AR' => "Arkansas",
			'CA' => "California",
			'CO' => "Colorado",
			'CT' => "Connecticut",
			'DE' => "Delaware",
			'DC' => "District Of Columbia",
			'FL' => "Florida",
			'GA' => "Georgia",
			'HI' => "Hawaii",
			'ID' => "Idaho",
			'IL' => "Illinois",
			'IN' => "Indiana",
			'IA' => "Iowa",
			'KS' => "Kansas",
			'KY' => "Kentucky",
			'LA' => "Louisiana",
			'ME' => "Maine",
			'MD' => "Maryland",
			'MA' => "Massachusetts",
			'MI' => "Michigan",
			'MN' => "Minnesota",
			'MS' => "Mississippi",
			'MO' => "Missouri",
			'MT' => "Montana",
			'NE' => "Nebraska",
			'NV' => "Nevada",
			'NH' => "New Hampshire",
			'NJ' => "New Jersey",
			'NM' => "New Mexico",
			'NY' => "New York",
			'NC' => "North Carolina",
			'ND' => "North Dakota",
			'OH' => "Ohio",
			'OK' => "Oklahoma",
			'OR' => "Oregon",
			'PA' => "Pennsylvania",
			'RI' => "Rhode Island",
			'SC' => "South Carolina",
			'SD' => "South Dakota",
			'TN' => "Tennessee",
			'TX' => "Texas",
			'UT' => "Utah",
			'VT' => "Vermont",
			'VA' => "Virginia",
			'WA' => "Washington",
			'WV' => "West Virginia",
			'WI' => "Wisconsin",
			'WY' => "Wyoming"
		);

		return array_merge( array_keys( $states ), array_values( $states ) );
	}

	/**
	 * Set zeto if payment was out of report date
	 *
	 * @since 1.0.1
	 *
	 * @param array $orders
	 *
	 * @return array
	 */
	private function hide_out_of_date( array $orders ) {

		if ( isset( $orders[0]['start_date'] )
		     && ( strtotime( $_REQUEST['from'] . ' 00:00:00' ) > strtotime( $orders[0]['start_date'] )
		          || strtotime( $_REQUEST['to'] . ' 23:59:59' ) < strtotime( $orders[0]['start_date'] ) ) ) {
			$orders[0]['amount']  = 0;
			$orders[0]['rebate']  = 0;
			$orders[0]['revenue'] = 0;
		}

		return $orders;
	}

}