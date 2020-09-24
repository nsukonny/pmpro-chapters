<?php
/**
 * Class Rebate_All_Members
 *
 * Main page in XLS Rebate report
 *
 * @since 1.0.3
 */

class Rebate_All_Members extends Rebate_Report {

	/**
	 * Prepare sheet
	 *
	 * @since 1.0.3
	 */
	public function make_sheet() {

		$sheet = $this->spreadsheet->createSheet();
		$sheet = $this->set_titles( $sheet );
		$sheet = $this->set_rows( $sheet );
		$sheet = $this->set_modifications( $sheet );

		return $this->spreadsheet;
	}

	/**
	 * Set titles for sheet
	 *
	 * @since 1.0.3
	 *
	 * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
	 *
	 * @return \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
	 */
	private function set_titles( \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet ) {

		$row   = 1;
		$title = __( 'Chapter Name', 'pmpro-chapters' );
		if ( $this->chapter_id ) {
			foreach ( $this->chapters as $chapter ) {
				if ( $chapter->ID == $this->chapter_id ) {
					$title = $chapter->post_title;
				}
			}
		}

		$sheet->setCellValue( 'A' . $row, $title );
		$sheet->setCellValue( 'B' . $row, __( 'Last Name', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'C' . $row, __( 'First Name', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'D' . $row, __( 'Membership Level', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'E' . $row, __( 'Activity Type', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'F' . $row, __( 'Recent Payment Type', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'G' . $row, __( 'Recent Transaction Date', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'H' . $row, __( 'Current Start Date', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'I' . $row, __( 'Current Amount Paid in Full', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'J' . $row, __( 'Previous Activity Date', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'K' . $row, __( 'Previous Membership  Level', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'L' . $row, __( 'Amount Chapter Rebate', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'M' . $row, __( 'Amount NCGR National Revenue', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'N' . $row, __( 'Current Expiration Date', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'O' . $row, __( 'Address Line 1', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'P' . $row, __( 'City', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'Q' . $row, __( 'State', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'R' . $row, __( 'Country', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'S' . $row, __( 'Zip Code', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'T' . $row, __( 'Phone Number 1', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'U' . $row, __( 'Combined Name', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'V' . $row, __( 'Email', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'W' . $row, __( 'Active Since Date', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'X' . $row, __( 'Deceased Members', 'pmpro-chapters' ) );

		return $sheet;
	}

	/**
	 * Add rows
	 *
	 * @since 1.0.3
	 *
	 * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
	 *
	 * @return \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
	 */
	private function set_rows( \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet ) {

		$row    = 2;
		$totals = array(
			'current_amount' => 0,
			'rebate'         => 0,
			'revenue'        => 0,
		);

		if ( 0 < count( $this->chapters ) ) {
			foreach ( $this->chapters as $chapter ) {
				if ( ( ! isset( $this->members[ $chapter->ID ] ) || 0 === count( $this->members[ $chapter->ID ] ) )
				     || ( $this->chapter_id && $chapter->ID != $this->chapter_id ) ) {
					continue;
				}

				foreach ( $this->members[ $chapter->ID ] as $member ) {
					$user_meta = array_map( function ( $a ) {
						return $a[0];
					}, get_user_meta( $member->ID ) );

					$last_orders = $this->get_last_orders_info( $member->ID );

					$start_date          = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel( strtotime( $last_orders[0]['start_date'] ) );
					$previous_start_date = isset( $last_orders[1] ) ? \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel( strtotime( $last_orders[1]['start_date'] ) ) : '';

					$end_date = PMPRO_Chapters_Reports::get_end_date( $member->data->membership_level, $member->ID );
					$end_date = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel( strtotime( $end_date ) );

					$last_name  = isset( $user_meta['last_name'] ) ? $user_meta['last_name'] : '';
					$first_name = isset( $user_meta['first_name'] ) ? $user_meta['first_name'] : '';

					$deceased = 'deceased' == strtolower( get_user_meta( $member->ID, 'member_status', true ) );
					$rebate   = 0 < $last_orders[0]['amount'] ? round( $last_orders[0]['amount'] / 3 ) : 0;

					if ( ! $this->chapter_id ) {
						$sheet->setCellValue( 'A' . $row, $chapter->post_title );
					}

					$phone = trim( $user_meta['pmpro_bphone'] );
					$phone = empty( $phone ) ? trim( $user_meta['billing_phone'] ) : $phone;
					$phone = empty( $phone ) ? trim( $user_meta['member_phone_1'] ) : $phone;
					$phone = empty( $phone ) ? $user_meta['member_phone_2'] : $phone;

					$sheet->setCellValue( 'B' . $row, $last_name );
					$sheet->setCellValue( 'C' . $row, $first_name );
					$sheet->setCellValue( 'D' . $row, $member->data->membership_level_name );
					$sheet->setCellValue( 'E' . $row, $last_orders[0]['description'] );
					$sheet->setCellValue( 'F' . $row, $last_orders[0]['payment_type'] );
					$sheet->setCellValue( 'G' . $row, $start_date );
					$sheet->setCellValue( 'H' . $row, $start_date );
					$sheet->setCellValue( 'I' . $row, '$' . $last_orders[0]['amount'] );
					$sheet->setCellValue( 'J' . $row, $previous_start_date );
					$sheet->setCellValue( 'K' . $row, isset( $last_orders[1] ) ? $last_orders[1]['description'] : '' );
					$sheet->setCellValue( 'L' . $row, '$' . $rebate );
					$sheet->setCellValue( 'M' . $row, '$' . ( $last_orders[0]['amount'] - $rebate ) );
					$sheet->setCellValue( 'N' . $row, $end_date );
					$sheet->setCellValue( 'O' . $row, isset( $user_meta['member_addr_street_1'] ) ? $user_meta['member_addr_street_1'] : '' );
					$sheet->setCellValue( 'P' . $row, isset( $user_meta['member_addr_city'] ) ? $user_meta['member_addr_city'] : '' );
					$sheet->setCellValue( 'Q' . $row, isset( $user_meta['member_addr_state'] ) ? $user_meta['member_addr_state'] : '' );
					$sheet->setCellValue( 'R' . $row, isset( $user_meta['member_addr_country'] ) ? $user_meta['member_addr_country'] : $user_meta['pmpro_bcountry'] );
					$sheet->setCellValue( 'S' . $row, isset( $user_meta['member_addr_zip'] ) ? $user_meta['member_addr_zip'] : '' );
					$sheet->setCellValue( 'T' . $row, $phone );
					$sheet->setCellValue( 'U' . $row, $first_name . ' ' . $last_name );
					$sheet->setCellValue( 'V' . $row, isset( $user_meta['pmpro_bemail'] ) ? $user_meta['pmpro_bemail'] : '' );

					$member_since_date = PMPRO_Chapters_Reports::get_member_since_date( $member->ID );
					if ( ! empty( $member_since_date ) ) {
						$since_date = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(
							strtotime( $member_since_date ) );
						$sheet->setCellValue( 'W' . $row, $since_date );
					}

					$sheet->setCellValue( 'X' . $row, $deceased ? 'Deceased' : '' );

					$totals['current_amount'] += $last_orders[0]['amount'];
					$totals['rebate']         += $rebate;
					$totals['revenue']        += $last_orders[0]['amount'] - $rebate;
					$row ++;
				}
			}
		}

		$row += 2;

		if ( $this->chapter_id ) {
			$sheet->setCellValue( 'A' . $row, __( 'Total Members: ', 'pmpro-chapters' ) . count( $this->members[ $this->chapter_id ] ) );
			$row ++;
			$sheet->setCellValue( 'A' . $row, __( 'Total Paid: ' . '$' . $totals['current_amount'], 'pmpro-chapters' ) );
		} else {
			$sheet->setCellValue( 'A' . $row, __( 'TOTALS', 'pmpro-chapters' ) );
			$sheet->setCellValue( 'I' . $row, '$' . $totals['current_amount'] );
			$sheet->setCellValue( 'L' . $row, '$' . $totals['rebate'] );
			$sheet->setCellValue( 'M' . $row, '$' . $totals['revenue'] );
		}

		return $sheet;
	}

	/**
	 * Apply column modifications
	 *
	 * @since 1.0.3
	 *
	 * @param $sheet
	 *
	 * @return mixed
	 */
	private function set_modifications( $sheet ) {

		$title = __( 'All Members', 'pmpro-chapters' );

		if ( $this->chapter_id ) {
			foreach ( $this->chapters as $chapter ) {
				if ( $chapter->ID == $this->chapter_id ) {
					$title = $chapter->post_title;
				}
			}
		}

		$sheet->setTitle( substr( $title, 0, 30 ) );

		$sheet->getColumnDimension( 'A' )->setWidth( 30 );
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
		$sheet->getColumnDimension( 'S' )->setAutoSize( true );
		$sheet->getColumnDimension( 'T' )->setAutoSize( true );
		$sheet->getColumnDimension( 'U' )->setAutoSize( true );
		$sheet->getColumnDimension( 'V' )->setAutoSize( true );
		$sheet->getColumnDimension( 'W' )->setAutoSize( true );
		$sheet->getColumnDimension( 'X' )->setAutoSize( true );

		$sheet->getStyle( 'G2:G' . $sheet->getHighestRow() )
		      ->getNumberFormat()
		      ->setFormatCode( 'mm/dd/yyyy' );
		$sheet->getStyle( 'H2:H' . $sheet->getHighestRow() )
		      ->getNumberFormat()
		      ->setFormatCode( 'mm/dd/yyyy' );
		$sheet->getStyle( 'J2:J' . $sheet->getHighestRow() )
		      ->getNumberFormat()
		      ->setFormatCode( 'mm/dd/yyyy' );
		$sheet->getStyle( 'U2:U' . $sheet->getHighestRow() )
		      ->getNumberFormat()
		      ->setFormatCode( 'mm/dd/yyyy' );

		if ( ! $this->chapter_id ) {
			//$sheet->getStyle( 'A2:A' . $sheet->getHighestRow() )->getFill()->setFillType( \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID )->getStartColor()->setARGB( 'f68fb0' );
		}

		return $sheet;
	}

}