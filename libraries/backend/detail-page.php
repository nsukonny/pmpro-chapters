<?php
/**
 * Class PMPRO_Chapters_Detail_Page
 * Display detail info about chapter
 *
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class PMPRO_Chapters_Detail_Page {

	/**
	 * PMPRO_Chapters_Detail_Page initialization class.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		add_action( 'admin_menu', array( $this, 'add_detail_page' ) );

	}

	/**
	 * Register detail page
	 *
	 * @since 1.0.0
	 */
	public function add_detail_page() {

		add_submenu_page(
			null,
			'My Custom Submenu Page',
			'My Custom Submenu Page',
			'manage_options',
			'chapter_detail',
			array( $this, 'render_detail_page' )
		);
	}

	/**
	 * Display detail page
	 *
	 * @since 1.0.0
	 */
	public function render_detail_page() {

		$chapter_id = isset( $_REQUEST['chapter_id'] ) ? (int) $_REQUEST['chapter_id'] : null;

		if ( $chapter_id ) {
			$this->export_users( $chapter_id );
			$this->import_users( $chapter_id );

			$chapter             = get_post( $chapter_id );
			$chapter_users_table = new PMPRO_Chapters_Users_List_Table();

			$chapter_users_table->chapter_id = $chapter_id;
			$chapter_users_table->prepare_items();
			$president = get_post_meta( $chapter_id, 'chapter_president', true );
			?>
            <h1><?php esc_attr_e( $chapter->post_title ); ?></h1>
            <div>
                <b>President:</b> <span><?php esc_attr_e( $president ); ?></span>
            </div>
			<?php
			$chapter_users_table->display();
		} else {
			echo 'You select wrong chapter.';
		}

	}

	/**
	 * Export users list to file
	 *
	 * @since 1.0.0
	 *
	 * @param $chapter_id
	 *
	 * @return bool
	 * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
	 */
	private function export_users( $chapter_id ) {

		if ( ! isset( $_REQUEST['action'] ) || 'export' !== $_REQUEST['action'] ) {
			return false;
		}

		$args        = array(
			'meta_key'   => 'chapter_id',
			'meta_value' => sanitize_text_field( $chapter_id ),
		);
		$users_query = new WP_User_Query( $args );
		if ( 0 < $users_query->get_total() ) {
			$spreadsheet  = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
			$sheet        = $spreadsheet->getActiveSheet();
			$row          = 2;
			$chapter      = get_post( $chapter_id );
			$chapter_slug = $chapter->post_name;

			$sheet->setCellValue( 'A1', __( 'Member ID', 'pmpro-chapters' ) );
			$sheet->setCellValue( 'B1', __( 'User ID', 'pmpro-chapters' ) );
			$sheet->setCellValue( 'C1', __( 'User Name', 'pmpro-chapters' ) );
			$sheet->setCellValue( 'D1', __( 'First Name', 'pmpro-chapters' ) );
			$sheet->setCellValue( 'E1', __( 'Last Name', 'pmpro-chapters' ) );

			foreach ( $users_query->get_results() as $user ) {
				$member_legacy_ID = get_user_meta( $user->ID, 'member_legacy_ID', true );

				$sheet->setCellValue( 'A' . $row, $member_legacy_ID );
				$sheet->setCellValue( 'B' . $row, $user->ID );
				$sheet->setCellValue( 'C' . $row, $user->user_login );
				$sheet->setCellValue( 'D' . $row, $user->first_name );
				$sheet->setCellValue( 'E' . $row, $user->last_name );
				$row ++;
			}

			$writer = new PhpOffice\PhpSpreadsheet\Writer\Xlsx( $spreadsheet );
			$writer->save( PMPRO_CHAPTERS_PLUGIN_PATH . 'temp/chapter_' . $chapter_slug . '.xlsx' );

			wp_safe_redirect( PMPRO_CHAPTERS_PLUGIN_URL . 'temp/chapter_' . $chapter_slug . '.xlsx' );
			exit;
		}
	}

	/**
	 * Import users from uploaded xlsx file
	 *
	 * @since 1.0.0
	 *
	 * @param $chapter_id
	 *
	 * @return bool
	 * @throws \PhpOffice\PhpSpreadsheet\Exception
	 */
	private function import_users( $chapter_id ) {

		if ( ! isset( $_REQUEST['action'] ) || 'import' !== $_REQUEST['action'] ) {
			return false;
		}

		if ( isset( $_POST['fileup_nonce'] ) && wp_verify_nonce( $_POST['fileup_nonce'], 'import_excel_file' ) ) {

			if ( ! function_exists( 'wp_handle_upload' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/file.php' );
			}

			$file          = &$_FILES['import_excel_file'];
			$overrides     = [ 'test_form' => false ];
			$uploaded_file = wp_handle_upload( $file, $overrides );

			if ( $uploaded_file && empty( $uploaded_file['error'] ) ) {
				$this->read_users_from_file( $uploaded_file['file'], $chapter_id );
			}
		}

		return true;
	}

	/**
	 * Get users from file and insert to database
	 *
	 * @since 1.0.0
	 *
	 * @param $filename
	 *
	 * @param $chapter_id
	 *
	 * @throws \PhpOffice\PhpSpreadsheet\Exception
	 */
	private function read_users_from_file( $filename, $chapter_id ) {

		$reader      = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
		$spreadsheet = $reader->load( $filename );
		$worksheet   = $spreadsheet->getActiveSheet();
		$highestRow  = $worksheet->getHighestRow();

		for ( $row = 1; $row <= $highestRow; ++ $row ) {
			$user_data               = array();
			$user_data['member_id']  = sanitize_text_field( $worksheet->getCell( 'A' . $row )->getValue() );
			$user_data['user_id']    = sanitize_text_field( $worksheet->getCell( 'B' . $row )->getValue() );
			$user_data['user_login'] = sanitize_text_field( $worksheet->getCell( 'C' . $row )->getValue() );
			$user_data['first_name'] = sanitize_text_field( $worksheet->getCell( 'D' . $row )->getValue() );
			$user_data['last_name']  = sanitize_text_field( $worksheet->getCell( 'E' . $row )->getValue() );

			$this->update_user( $user_data, $chapter_id );
		}

	}

	/**
	 * Update user in database
	 *
	 * @since 1.0.0
	 *
	 * @param array $user_data
	 * @param $chapter_id
	 */
	private function update_user( array $user_data, $chapter_id ) {

		$user_id = username_exists( $user_data['user_login'] );
		if ( ! $user_id && ! empty( $user_data['user_id'] ) && is_numeric( $user_data['user_id'] ) ) {
			$user_id = $user_data['user_id'];
		}

		if ( $user_id ) {
			update_user_meta( $user_id, 'chapter_id', $chapter_id );
			update_user_meta( $user_id, 'member_legacy_ID', sanitize_text_field( $user_data['member_id'] ) );

			wp_update_user( array(
				'ID'         => $user_id,
				'first_name' => $user_data['first_name'],
				'last_name'  => $user_data['last_name'],
			) );

		}

	}

}

function pmpro_chapters_detail_page_runner() {

	$page = new PMPRO_Chapters_Detail_Page;
	$page->init();

	return true;
}

pmpro_chapters_detail_page_runner();