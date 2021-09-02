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
	 * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
	 *
	 * @return \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
	 * @since 1.0.3
	 *
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

		$sheet->setCellValue( 'B' . $row, $title );
		$sheet->setCellValue( 'C' . $row, __( 'Last Name', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'D' . $row, __( 'First Name', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'E' . $row, __( 'Current Membership Level', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'F' . $row, __( 'Activity Type', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'G' . $row, __( 'Recent Payment Type', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'H' . $row, __( 'Recent Transaction Date', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'I' . $row, __( 'Current Start Date', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'J' . $row, __( 'Current Amount Paid in Full', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'K' . $row, __( 'Amount Chapter Rebate', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'L' . $row, __( 'Amount NCGR National Revenue', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'M' . $row, __( 'Current Expiration Date', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'N' . $row, __( 'Previous Activity Date', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'O' . $row, __( 'Previous Activity Type', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'P' . $row, __( 'Address Line 1', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'Q' . $row, __( 'City', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'R' . $row, __( 'State', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'S' . $row, __( 'Country', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'T' . $row, __( 'Zip Code', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'U' . $row, __( 'Phone Number 1', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'V' . $row, __( 'Combined Name', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'W' . $row, __( 'Email', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'X' . $row, __( 'Active Since Date', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'Y' . $row, __( 'Deceased Members', 'pmpro-chapters' ) );

		return $sheet;
	}

	/**
	 * Add rows
	 *
	 * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
	 *
	 * @return \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
	 * @since 1.0.3
	 *
	 */
	private function set_rows( \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet ) {

		$row = 2;

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

					$last_orders     = $this->get_last_orders_info( $member->ID );
					$last_two_orders = $this->get_last_two_orders( $last_orders );

					$start_date          = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel( strtotime( $last_two_orders['last']['start_date'] ) );
					$previous_start_date = isset( $last_two_orders['prev'] ) ? \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel( strtotime( $last_two_orders['prev']['start_date'] ) ) : '';

					$end_date = '';
					/* By logic we need expiration date from current transaction, but ladies want see member expiration date.
					if ( isset( $last_two_orders['last']['end_date'] ) && ! empty( $last_two_orders['last']['end_date'] ) ) {
						$end_date = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel( $last_two_orders['last']['end_date'] );
					}*/

					if ( $member->member_expiration_time ) {
						$end_date = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel( $member->member_expiration_time );
					}

					$last_name  = isset( $user_meta['last_name'] ) ? $user_meta['last_name'] : '';
					$first_name = isset( $user_meta['first_name'] ) ? $user_meta['first_name'] : '';

					$deceased = 'deceased' == strtolower( get_user_meta( $member->ID, 'member_status', true ) );

					if ( ! $this->chapter_id ) {
						$sheet->setCellValue( 'B' . $row, $chapter->post_title );
					}

					$phone = trim( $user_meta['pmpro_bphone'] );
					$phone = empty( $phone ) ? trim( $user_meta['billing_phone'] ) : $phone;
					$phone = empty( $phone ) ? trim( $user_meta['member_phone_1'] ) : $phone;
					$phone = empty( $phone ) ? $user_meta['member_phone_2'] : $phone;

					//$activity_type         = '';
					$membership_level_name = 'None';

					foreach ( $this->membership_levels as $level ) {
						if ( $level->id == $member->membership_level->id ) {
							$membership_level_name = $level->name . ' $' . round( $level->billing_amount );

							break;
						}
					}

					/*
					if ( isset( $this->legacy_orders[ $member->ID ] ) ) {
						$activity_type = PMPRO_Chapters_Reports::check_description( $member->membership_level->name, $this->pmpro_orders[ $member->ID ], $this->legacy_orders[ $member->ID ] );
					} else {
						$activity_type = PMPRO_Chapters_Reports::check_description( $member->membership_level->name, $this->pmpro_orders[ $member->ID ], $this->legacy_orders );
					}*/

					if ( isset( $_GET['debug'] ) ) {
						$sheet->setCellValue( 'A' . $row, $member->ID );
					}

					$sheet->setCellValue( 'C' . $row, $last_name );
					$sheet->setCellValue( 'D' . $row, $first_name );
					$sheet->setCellValue( 'E' . $row, $membership_level_name );
					$sheet->setCellValue( 'F' . $row, $last_two_orders['last']['description'] );
					$sheet->setCellValue( 'G' . $row, ucfirst( $last_two_orders['last']['payment_type'] ) );
					$sheet->setCellValue( 'H' . $row, $start_date );
					$sheet->setCellValue( 'I' . $row, $start_date );
					$sheet->setCellValue( 'J' . $row, $last_two_orders['last']['amount'] );
					$sheet->setCellValue( 'K' . $row, $last_two_orders['last']['rebate'] );
					$sheet->setCellValue( 'L' . $row, $last_two_orders['last']['revenue'] );
					$sheet->setCellValue( 'M' . $row, $end_date );
					$sheet->setCellValue( 'N' . $row, $previous_start_date );
					$sheet->setCellValue( 'O' . $row, isset( $last_two_orders['prev'] ) ? $last_two_orders['prev']['description'] : '' );
					$sheet->setCellValue( 'P' . $row, isset( $user_meta['member_addr_street_1'] ) ? $user_meta['member_addr_street_1'] : '' );
					$sheet->setCellValue( 'Q' . $row, isset( $user_meta['member_addr_city'] ) ? $user_meta['member_addr_city'] : '' );
					$sheet->setCellValue( 'R' . $row, isset( $user_meta['member_addr_state'] ) ? $user_meta['member_addr_state'] : '' );
					$sheet->setCellValue( 'S' . $row, isset( $user_meta['member_addr_country'] ) ? mb_strtoupper( $user_meta['member_addr_country'] ) : '' );
					$sheet->setCellValue( 'T' . $row, isset( $user_meta['member_addr_zip'] ) ? $user_meta['member_addr_zip'] : '' );
					$sheet->setCellValue( 'U' . $row, $phone );
					$sheet->setCellValue( 'V' . $row, $first_name . ' ' . $last_name );
					$sheet->setCellValue( 'W' . $row, isset( $user_meta['pmpro_bemail'] ) ? $user_meta['pmpro_bemail'] : '' );

					$member_since_date = PMPRO_Chapters_Reports::get_member_since_date( $member->ID );
					if ( ! empty( $member_since_date ) ) {
						$since_date = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(
							strtotime( $member_since_date ) );
						$sheet->setCellValue( 'X' . $row, $since_date );
					}

					$sheet->setCellValue( 'Y' . $row, $deceased ? 'Deceased' : '' );
					$row ++;
				}
			}
		}

		$last_data_row = $row - 1;
		$row           += 2;
		$total_col     = $this->chapter_id ? 'B' : 'A';
		$sheet->setCellValue( $total_col . $row, '="' . __( 'Total Members: ', 'pmpro-chapters' ) . '"&COUNTIF(C2:C' . $last_data_row . ', "<>")' );
		$sheet->setCellValue( $total_col . ++ $row, '="' . __( 'Total Paid: $', 'pmpro-chapters' ) . '"&SUM(J2:J' . $last_data_row . ')' );
		$sheet->setCellValue( $total_col . ++ $row, '="' . __( 'Total Rebate: $', 'pmpro-chapters' ) . '"&SUM(K2:K' . $last_data_row . ')' );
		$sheet->setCellValue( $total_col . ++ $row, '="' . __( 'Total Revenue: $', 'pmpro-chapters' ) . '"&SUM(L2:L' . $last_data_row . ')' );

		return $sheet;
	}

	/**
	 * Apply column modifications
	 *
	 * @param $sheet
	 *
	 * @return mixed
	 * @since 1.0.3
	 *
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
		$sheet->getColumnDimension( 'B' )->setWidth( 30 );
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
		$sheet->getColumnDimension( 'Y' )->setAutoSize( true );

		$sheet->getStyle( 'H2:H' . $sheet->getHighestRow() )
		      ->getNumberFormat()
		      ->setFormatCode( 'mm/dd/yyyy' );
		$sheet->getStyle( 'I2:I' . $sheet->getHighestRow() )
		      ->getNumberFormat()
		      ->setFormatCode( 'mm/dd/yyyy' );
		$sheet->getStyle( 'M2:M' . $sheet->getHighestRow() )
		      ->getNumberFormat()
		      ->setFormatCode( 'mm/dd/yyyy' );
		$sheet->getStyle( 'N2:N' . $sheet->getHighestRow() )
		      ->getNumberFormat()
		      ->setFormatCode( 'mm/dd/yyyy' );
		$sheet->getStyle( 'X2:X' . $sheet->getHighestRow() )
		      ->getNumberFormat()
		      ->setFormatCode( 'mm/dd/yyyy' );

		$align_left = \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT;
		$sheet->getStyle( 'T2:T' . $sheet->getHighestRow() )
		      ->getAlignment()
		      ->setHorizontal( $align_left );
		$sheet->getStyle( 'U2:U' . $sheet->getHighestRow() )
		      ->getAlignment()
		      ->setHorizontal( $align_left );

		if ( $this->chapter_id ) {
			$sheet->removeColumnByIndex( 1 );
		}

		return $sheet;
	}

}