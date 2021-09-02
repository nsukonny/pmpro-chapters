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
	 * LIst of last member orders by member_id
	 * @var array
	 */
	protected $last_orders;

	/**
	 * Report constructor.
	 *
	 * @param \PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet
	 * @param $chapters
	 * @param $members
	 * @param $pmpro_orders
	 *
	 * @since 1.0.3
	 *
	 */
	public function __construct( \PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet, $chapters, $members, $pmpro_orders, $legacy_orders ) {

		$this->spreadsheet   = $spreadsheet;
		$this->chapters      = $chapters;
		$this->members       = $members;
		$this->pmpro_orders  = $pmpro_orders;
		$this->legacy_orders = $legacy_orders;
		$this->last_orders   = array();

	}

	/**
	 * Safe set membership levels
	 *
	 * @param $membership_levels
	 *
	 * @since 1.0.3
	 *
	 */
	public function set_membership_levels( $membership_levels ) {

		$this->membership_levels = $membership_levels;

	}

	/**
	 * Add chapter ID for make sheet
	 *
	 * @param $chapter_id
	 *
	 * @since 1.0.3
	 *
	 */
	public function set_chapter_id( $chapter_id ) {

		$this->chapter_id = $chapter_id;

	}

	/**
	 * Get amount paid by chapter
	 *
	 * @param $members
	 *
	 * @return array
	 * @since 1.0.3
	 *
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
	 * @param $user_id
	 *
	 * @return array
	 *
	 * @since 1.0.3
	 * @since 1.0.4
	 */
	protected function get_last_orders_info( $user_id ): array {

		if ( ! isset( $this->last_orders[ $user_id ] ) ) {

			$pmpro_orders     = $this->pmpro_orders[ $user_id ];
			$member_legacy_id = get_user_meta( $user_id, 'member_legacy_ID', true );
			$legacy_orders    = isset( $this->legacy_orders[ $member_legacy_id ] ) ? $this->legacy_orders[ $member_legacy_id ] : array();
			$orders           = $this->orders_from_levels( $user_id, $pmpro_orders, $legacy_orders );

			if ( is_array( $pmpro_orders ) && 0 < count( $pmpro_orders ) ) {
				$orders = $this->orders_from_pmpro( $orders, $pmpro_orders, $legacy_orders );
			}

			if ( is_array( $legacy_orders ) && 0 < count( $legacy_orders ) ) {
				$orders = $this->orders_from_legacy( $orders, $user_id, $pmpro_orders, $legacy_orders );
			}

			if ( 1 < count( $orders ) ) {
				usort( $orders, function ( $a, $b ) {

					return strtotime( $a['start_date'] ) <= strtotime( $b['start_date'] );

				} );
			}

			if ( 0 < count( $orders ) ) {
				$orders = $this->hide_international( $user_id, $orders );
				$orders = $this->fill_empty_dates( $orders );
				$orders = $this->remove_wrong_dates( $orders );
				$orders = $this->hide_out_of_date( $orders );
			}

			$this->last_orders[ $user_id ] = 0 !== count( $orders ) ? $orders : $this->get_empty_order();
		}

		return $this->last_orders[ $user_id ];
	}

	/**
	 * Get two last orders by selected period
	 *
	 * @param $last_orders
	 *
	 * @return array
	 * @since 1.0.3
	 *
	 */
	protected function get_last_two_orders( $last_orders ) {

		$orders = array();

		if ( 0 < count( $last_orders ) ) {
			$reversed_orders = array_reverse( $last_orders );

			foreach ( $reversed_orders as $order_key => $order ) {

				if ( ! isset( $order['start_date'] ) ) {
					continue;
				}

				$start_in_range         = strtotime( $_REQUEST['from'] . ' 00:00:00' ) <= strtotime( $order['start_date'] )
				                          && strtotime( $_REQUEST['to'] . ' 23:59:59' ) >= strtotime( $order['start_date'] );
				$end_in_range           = strtotime( $_REQUEST['from'] . ' 00:00:00' ) <= strtotime( $order['end_date'] )
				                          && strtotime( $_REQUEST['to'] . ' 23:59:59' ) >= strtotime( $order['end_date'] );
				$start_early_than_range = strtotime( $_REQUEST['from'] . ' 00:00:00' ) > strtotime( $order['start_date'] );
				$end_after_than_range   = strtotime( $_REQUEST['to'] . ' 23:59:59' ) < strtotime( $order['end_date'] );

				if ( ( $start_in_range || $end_in_range )
				     || ( $start_early_than_range && $end_after_than_range ) ) {
					$orders['last'] = $order;

					if ( isset( $reversed_orders[ $order_key - 1 ] )
					     && date( 'dmY', strtotime( $reversed_orders[ $order_key - 1 ]['start_date'] ) ) != date( 'dmY', strtotime( $order['start_date'] ) ) ) {
						$orders['prev'] = $reversed_orders[ $order_key - 1 ];
					} else if ( isset( $reversed_orders[ $order_key - 2 ] ) ) {
						$orders['prev'] = $reversed_orders[ $order_key - 2 ];
					}
				}
			}

			//if member don`t have an orders in range, get last two, but with zero totals
			if ( 0 == count( $orders ) && isset( $last_orders[0] ) ) {
				$orders['last'] = $last_orders[0];

				if ( isset( $last_orders[1] ) ) {
					$orders['prev'] = $last_orders[1];
				}
			}

		}

		return $orders;
	}

	/**
	 * Make empty order array
	 *
	 * @return array
	 * @since 1.0.3
	 *
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
			'type'             => '',
		);
	}

	/**
	 * Get legacy order type
	 *
	 * @param $legacy_order
	 *
	 * @return array
	 * @since 1.0.3
	 *
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
	 * @param $user_id
	 * @param $pmpro_orders
	 * @param $legacy_orders
	 *
	 * @return array
	 *
	 * @since 1.0.3
	 * @since 1.0.4 Add payment type
	 *              Add transaction date
	 */
	private function orders_from_levels( $user_id, $pmpro_orders, $legacy_orders ) {

		$orders = array();

		$membership_levels = pmpro_getMembershipLevelsForUser( $user_id, true );
		$min_date_time     = strtotime( '2/2/20' );

		if ( 0 < count( $membership_levels ) ) {
			for ( $key = count( $membership_levels ) - 1; $key >= 0; $key -- ) {
				//All orders before february 2020 is wrong. PMPRO started from that date, so skip them.
				if ( $membership_levels[ $key ]->startdate < $min_date_time ) {
					continue;
				}

				$start_date = ! empty( $membership_levels[ $key ]->startdate ) ?
					date( 'm/d/Y', $membership_levels[ $key ]->startdate ) : '';
				$end_date   = ! empty( $membership_levels[ $key ]->enddate ) ?
					date( 'm/d/Y', $membership_levels[ $key ]->enddate ) : '';

				if ( $start_date === $end_date ) {
					continue;
				}

				$order                          = $this->get_empty_order();
				$order['type']                  = 'orders_from_levels';
				$order['description']           = PMPRO_Chapters_Reports::check_description( $membership_levels[ $key ]->name, $pmpro_orders, $legacy_orders );
				$order['start_date']            = $start_date;
				$order['start_timestamp']       = ! empty( $membership_levels[ $key ]->startdate ) ?
					$membership_levels[ $key ]->startdate : '';
				$order['end_date']              = ! empty( $membership_levels[ $key ]->enddate ) ?
					date( 'm/d/Y', $membership_levels[ $key ]->enddate ) : '';
				$order['end_timestamp']         = ! empty( $membership_levels[ $key ]->enddate ) ?
					$membership_levels[ $key ]->enddate : '';
				$order['amount']                = round( $membership_levels[ $key ]->initial_payment );
				$order['rebate']                = 0 < $membership_levels[ $key ]->initial_payment ? round( $membership_levels[ $key ]->initial_payment / 3 ) : 0;
				$order['revenue']               = $order['amount'] - $order['rebate'];
				$order['membership_level_name'] = $membership_levels[ $key ]->name . ( ( 0 < $order['amount'] ) ? ' $' . $order['amount'] . ' per year.' : '' );

				$transaction_detail        = $this->get_transaction_details_for_level( $membership_levels[ $key ], $pmpro_orders, $legacy_orders, $user_id );
				$order['payment_type']     = $transaction_detail['payment_type'];
				$order['transaction_date'] = $transaction_detail['transaction_date'];

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

		$min_date_time = strtotime( '2/2/20' );
		$skip_dates    = array_column( $orders, 'start_date' );

		foreach ( $pmpro_orders as $pmpro_order ) {
			//All orders before february 2020 is wrong. PMPRO started from that date, so skip them.
			if ( strtotime( $pmpro_order->timestamp ) < $min_date_time ) {
				continue;
			}

			$start_date = ! empty( $pmpro_order->timestamp ) ? date( 'm/d/Y', strtotime( $pmpro_order->timestamp ) ) : '';
			if ( in_array( $start_date, $skip_dates, true ) ) {
				continue;
			}

			$period = 1 <= $pmpro_order->cycle_number ? ' per ' . $pmpro_order->cycle_number . ' year' : ' per ' . $pmpro_order->cycle_number . ' years';

			$order['type']                  = 'orders_from_pmpro';
			$order['description']           = PMPRO_Chapters_Reports::check_description( $pmpro_order->name, $pmpro_orders, $legacy_orders );
			$order['start_date']            = $start_date;
			$order['start_timestamp']       = ! empty( $pmpro_order->timestamp ) ? strtotime( $pmpro_order->timestamp ) : '';
			$order['end_date']              = ! empty( $pmpro_order->order_details->expirationyear ) ?
				date( 'm/d/Y', strtotime( $pmpro_order->order_details->expirationmonth . '/01/' . $pmpro_order->order_details->expirationyear ) ) : ''; //TODO this is wrong. Means end date of subscription but here we have end date of card
			$order['end_timestamp']         = strtotime( $pmpro_order->order_details->expirationmonth . '/01/' . $pmpro_order->order_details->expirationyear );
			$order['amount']                = round( $pmpro_order->billing_amount );
			$order['rebate']                = 0 < $pmpro_order->billing_amount ? round( $pmpro_order->billing_amount / 3 ) : 0;
			$order['revenue']               = $order['amount'] - $order['rebate'];
			$order['payment_type']          = $pmpro_order->order_details->gateway;
			$order['discount']              = ! empty( $pmpro_order->certificateamount ) ? $pmpro_order->certificateamount : $pmpro_order->couponamount;
			$order['membership_level_name'] = $pmpro_order->name . ( ( 0 < $order['amount'] ) ? ' $' . $order['amount'] . $period : '' );

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
	 * @param $orders
	 * @param $user_id
	 * @param $pmpro_orders
	 * @param $legacy_orders
	 *
	 * @return array
	 * @since 1.0.3
	 *
	 */
	private function orders_from_legacy( $orders, $user_id, $pmpro_orders, $legacy_orders ) {

		$member_legacy_id = get_user_meta( $user_id, 'member_legacy_ID', true );
		if ( $member_legacy_id
		     && isset( $this->legacy_orders[ $member_legacy_id ] )
		     && 0 < count( $this->legacy_orders[ $member_legacy_id ] ) ) {

			foreach ( $this->legacy_orders[ $member_legacy_id ] as $legacy_order ) {
				$legacy_type = $this->get_legacy_type( $legacy_order );

				$start_date      = ! empty( $legacy_type['start_date'] ) ? date( 'm/d/Y', strtotime( $legacy_type['start_date'] ) ) : '';
				$start_timestamp = ! empty( $legacy_type['start_date'] ) ? strtotime( $legacy_type['start_date'] ) : '';
				if ( empty( $start_date ) && isset( $legacy_order->activity_date ) && ! empty( $legacy_order->activity_date ) ) {
					$start_date      = date( 'm/d/Y', strtotime( $legacy_order->activity_date ) );
					$start_timestamp = strtotime( $legacy_order->activity_date );
				}

				$order                          = $this->get_empty_order();
				$order['type']                  = 'orders_from_legacy';
				$order['description']           = ! empty( $legacy_type['description'] ) ? PMPRO_Chapters_Reports::check_description( $legacy_type['description'], $pmpro_orders, $legacy_orders ) : '';
				$order['payment_type']          = '(Legacy)';
				$order['start_date']            = $start_date;
				$order['start_timestamp']       = $start_timestamp;
				$order['amount']                = round( $legacy_type['amount'] );
				$order['rebate']                = 0 < $legacy_type['amount'] ? round( $legacy_type['amount'] / 3 ) : 0;
				$order['revenue']               = $order['amount'] - $order['rebate'];
				$order['membership_level_name'] = PMPRO_Chapters_Reports::clear_description( $legacy_type['description'] ) . ( ( 0 < $order['amount'] ) ? ' $' . $order['amount'] . ' per year.' : '' );

				$orders[] = $order;
			}
		}

		return $orders;
	}

	/**
	 * Reset amount for non USA users
	 *
	 * @param array $orders
	 *
	 * @return array
	 * @since 1.0.1
	 *
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
	 * @param array $orders
	 *
	 * @return array
	 * @since 1.0.1
	 *
	 */
	private function hide_out_of_date( array $orders ) {

		if ( 0 < count( $orders ) ) {
			foreach ( $orders as $order_key => $order ) {
				if ( isset( $order['start_date'] )
				     && ( strtotime( $_REQUEST['from'] . ' 00:00:00' ) > strtotime( $order['start_date'] )
				          || strtotime( $_REQUEST['to'] . ' 23:59:59' ) < strtotime( $order['start_date'] ) ) ) {
					$orders[ $order_key ]['amount']  = 0;
					$orders[ $order_key ]['rebate']  = 0;
					$orders[ $order_key ]['revenue'] = 0;
				}
			}
		}

		return $orders;
	}

	/**
	 * Remove zero and empty dates
	 *
	 * @param array $orders
	 *
	 * @return array
	 */
	private function remove_wrong_dates( array $orders ) {

		if ( 0 < count( $orders ) ) {
			foreach ( $orders as $order_key => $order ) {

				if ( '0000-00-00 00:00:00' == $order['start_date'] || strtotime( '01.01.1976' ) > strtotime( $order['start_date'] ) ) {
					$orders[ $order_key ]['start_date'] = '';
				}

				if ( '0000-00-00 00:00:00' == $order['end_date'] || strtotime( '01.01.1976' ) > strtotime( $order['end_date'] ) ) {
					$orders[ $order_key ]['end_date'] = '';
				}

			}
		}

		return $orders;
	}

	/**
	 * Fill empty expiration dates from next transaction start date
	 *
	 * @param array $orders
	 *
	 * @return array
	 * @since 1.0.3
	 *
	 */
	private function fill_empty_dates( array $orders ) {

		if ( 0 < count( $orders ) ) {
			foreach ( $orders as $order_key => $order ) {
				if ( empty( $order['end_date'] ) && isset( $orders[ $order_key - 1 ]['start_date'] ) ) {
					$orders[ $order_key ]['end_date'] = $orders[ $order_key - 1 ]['start_date'];
				}
			}
		}

		return $orders;
	}

	/**
	 * Get transaction type for level from same order, who pay in same day
	 *
	 * @param Object $level Object of level membership
	 * @param array $pmpro_orders List of orders payed by PMPRO
	 * @param array $legacy_orders LIst of orders payed by Legacy
	 *
	 * @return array
	 *
	 * @since 1.0.4
	 */
	private function get_transaction_details_for_level( $level, $pmpro_orders, $legacy_orders, $user_id ): array {

		$level_date          = date( 'm/d/Y', $level->startdate );
		$transaction_details = array(
			'payment_type'     => '',
			'transaction_date' => '',
		);

		if ( 0 < count( $pmpro_orders ) ) {
			foreach ( $pmpro_orders as $pmpro_order ) {
				$transaction_date = date( 'm/d/Y', strtotime( $pmpro_order->timestamp ) );
				if ( $transaction_date == $level_date ) {
					$transaction_details['payment_type']     = $pmpro_order->order_details->gateway;
					$transaction_details['transaction_date'] = $transaction_date;

					break;
				}
			}
		}

		if ( empty( $payment_type ) && 0 < count( $legacy_orders ) ) {
			foreach ( $legacy_orders as $legacy_order ) {
				$transaction_date = date( 'm/d/Y', strtotime( $legacy_order->activity_trans_timestamp ) );
				if ( $transaction_date == $level_date ) {
					$transaction_details['payment_type']     = '(Legacy)';
					$transaction_details['transaction_date'] = $transaction_date;

					break;
				}
			}
		}

		return $transaction_details;
	}

}