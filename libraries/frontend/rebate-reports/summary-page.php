<?php
/**
 * Class Rebate_Summary
 *
 * Main page in XLS Rebate report
 *
 * @since 1.0.3
 */

class Rebate_Summary extends Rebate_Report {

	/**
	 * Prepare sheet
	 *
	 * @since 1.0.3
	 */
	public function make_sheet() {

		$sheet = $this->spreadsheet->getActiveSheet();
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

		$row = 1;
		$sheet->setCellValue( 'A' . $row, __( 'Date of Report: ' . date( 'm/d/y' ), 'pmpro-chapters' ) );
		$sheet->setCellValue( 'B' . $row, __( 'Chapter Name', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'C' . $row, __( 'Member Counter', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'D' . $row, __( 'Amount Paid Full', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'E' . $row, __( 'Amount Chapter Rebate', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'F' . $row, __( 'Amount NCGR National Revenue', 'pmpro-chapters' ) );

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

		$row = 2;

		if ( 0 < count( $this->chapters ) ) {

			//Remove members if his expiration date not at range
			foreach ( $this->members as $chapter_id => $chapter_members ) {
				foreach ( $chapter_members as $member_key => $member ) {
					$last_orders     = $this->get_last_orders_info( $member->ID );
					$last_two_orders = $this->get_last_two_orders( $last_orders );

					if ( 0 == count( $last_two_orders ) ) {
						$member_expiration_time = ! empty( $member->membership_level->enddate ) ? $member->membership_level->enddate : null;

						if ( $member_expiration_time
						     && ( $member_expiration_time < strtotime( $_REQUEST['from'] . ' 00:00:00' )
						          || $member_expiration_time > strtotime( $_REQUEST['to'] . ' 23:59:59' ) ) ) {
							unset( $this->members[ $chapter_id ][ $member_key ] );
						}
					}
				}
			}

			foreach ( $this->chapters as $chapter ) {
				$sheet->setCellValue( 'B' . $row, $chapter->post_title );

				$count_members = isset( $this->members[ $chapter->ID ] ) ? count( $this->members[ $chapter->ID ] ) : 0;
				$sheet->setCellValue( 'C' . $row, $count_members );

				$amount = $this->get_amount_paid( $this->members[ $chapter->ID ] );
				$sheet->setCellValue( 'D' . $row, $amount['paid'] );
				$sheet->setCellValue( 'E' . $row, $amount['rebate'] );
				$sheet->setCellValue( 'F' . $row, $amount['revenue'] );

				$row ++;
			}

		}

		$last_data_row = $row - 1;
		$sheet->setCellValue( 'A' . ++ $row, '="' . __( 'Total Members: ', 'pmpro-chapters' ) . '"&SUM(C2:C' . $last_data_row . ')' );
		$sheet->setCellValue( 'A' . ++ $row, '="' . __( 'Total Paid: $', 'pmpro-chapters' ) . '"&SUM(D2:D' . $last_data_row . ')' );
		$sheet->setCellValue( 'A' . ++ $row, '="' . __( 'Total Rebate: $', 'pmpro-chapters' ) . '"&SUM(E2:E' . $last_data_row . ')' );
		$sheet->setCellValue( 'A' . ++ $row, '="' . __( 'Total NCGR: $', 'pmpro-chapters' ) . '"&SUM(F2:F' . $last_data_row . ')' );

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

		$sheet->setTitle( __( 'Summary Page', 'pmpro-chapters' ) );

		$sheet->getColumnDimension( 'A' )->setAutoSize( true );
		$sheet->getColumnDimension( 'B' )->setAutoSize( true );
		$sheet->getColumnDimension( 'C' )->setAutoSize( true );
		$sheet->getColumnDimension( 'D' )->setAutoSize( true );
		$sheet->getColumnDimension( 'E' )->setAutoSize( true );
		$sheet->getColumnDimension( 'F' )->setAutoSize( true );

		return $sheet;
	}

}