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
		$sheet->setCellValue( 'A' . $row, __( 'Chapter Name', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'B' . $row, __( 'Member Counter', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'C' . $row, __( 'Amount Paid Full', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'D' . $row, __( 'Amount Chapter Rebate', 'pmpro-chapters' ) );
		$sheet->setCellValue( 'E' . $row, __( 'Amount NCGR National Revenue', 'pmpro-chapters' ) );

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
			'members' => 0,
			'paid'    => 0,
			'rebate'  => 0,
			'ncgr'    => 0,
		);

		if ( 0 < count( $this->chapters ) ) {
			foreach ( $this->chapters as $chapter ) {
				$sheet->setCellValue( 'A' . $row, $chapter->post_title );

				$count_members = isset( $this->members[ $chapter->ID ] ) ? count( $this->members[ $chapter->ID ] ) : 0;
				$sheet->setCellValue( 'B' . $row, $count_members );

				$amount_paid = $this->get_amount_paid( $this->members[ $chapter->ID ] );
				$sheet->setCellValue( 'C' . $row, '$' . $amount_paid );

				$rebate = round( $amount_paid / 3 );
				$ncgr   = $amount_paid - $rebate;
				$sheet->setCellValue( 'D' . $row, '$' . $rebate );
				$sheet->setCellValue( 'E' . $row, '$' . $ncgr );

				$totals['members'] += $count_members;
				$totals['rebate']  += $rebate;
				$totals['ncgr']    += $ncgr;
				$totals['paid']    += $amount_paid;
				$row ++;
			}
		}

		$row ++;
		$sheet->setCellValue( 'B' . $row, $totals['members'] );
		$sheet->setCellValue( 'C' . $row, '$' . $totals['paid'] );
		$sheet->setCellValue( 'D' . $row, '$' . $totals['rebate'] );
		$sheet->setCellValue( 'E' . $row, '$' . $totals['ncgr'] );

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

		return $sheet;
	}

}