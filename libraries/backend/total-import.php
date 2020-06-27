<?php
/**
 * Class PMPRO_Chapters_Backend_Total_Import
 * Import users from big members xlsx
 *
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class PMPRO_Chapters_Backend_Total_Import {

	/**
	 * List for integrate old chapters with new
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	private $chapter_bridge;

	/**
	 * List of all users with meta
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	private $users;

	/**
	 * PMPRO_Chapters_Backend_Filters initialization class.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		add_action( 'restrict_manage_posts', array( $this, 'add_import_chapters' ), 10, 1 );
		add_action( 'in_admin_footer', array( $this, 'add_upload_form' ), 10, 1 );
		add_action( 'admin_notices', array( $this, 'add_notice' ) );

		add_filter( 'parse_query', array( $this, 'import_by_chapters' ), 10, 1 );

	}

	/**
	 * Add chapters import button
	 *
	 * @since 1.0.0
	 *
	 * @param $post_type
	 */
	public function add_import_chapters( $post_type ) {

		if ( $post_type !== 'chapters' ) {
			return;
		}
		?>
        <a href="#" class="button action" id="pmpro-chapters-total-import">
			<?php _e( 'Import', 'pmpro-chapters' ); ?>
        </a>
		<?php

	}

	/**
	 * Add form for hidden upload
	 *
	 * @since 1.0.0
	 */
	public function add_upload_form( $data ) {

		$chapters_link = admin_url( 'edit.php?post_type=chapters' );
		?>
        <form action="<?php echo esc_url( $chapters_link ); ?>" method="post" id="pmpro-cahpters-total-import-form"
              enctype="multipart/form-data" style="display: none;">
			<?php wp_nonce_field( 'import_excel_file', 'fileup_nonce' ); ?>
            <input type="file" name="import_excel_file" accept=".xls"/>
            <input type="submit" value="upload file"/>
        </form>
		<?php

	}

	/**
	 * Get users from loaded file
	 *
	 * @since 1.0.0
	 *
	 * @param $query
	 *
	 * @return bool
	 * @throws \PhpOffice\PhpSpreadsheet\Exception
	 */
	public function import_by_chapters( $query ) {
		global $pagenow;

		$current_page    = isset( $_GET['post_type'] ) ? $_GET['post_type'] : '';
		$is_chapter_edit = 'chapters' == $current_page && 'edit.php' == $pagenow;
		$is_have_xls     = isset( $_FILES['import_excel_file'] );
		$is_right_nonce  = isset( $_POST['fileup_nonce'] ) && wp_verify_nonce( $_POST['fileup_nonce'], 'import_excel_file' );
		if ( ! $is_have_xls || ! $is_right_nonce ) {
			return false;
		}

		if ( is_admin() && $is_chapter_edit && $is_have_xls && $is_right_nonce ) {
			if ( ! function_exists( 'wp_handle_upload' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/file.php' );
			}

			$file          = &$_FILES['import_excel_file'];
			$overrides     = [ 'test_form' => false ];
			$uploaded_file = wp_handle_upload( $file, $overrides );

			if ( $uploaded_file && empty( $uploaded_file['error'] ) ) {
				$this->read_users_from_file( $uploaded_file['file'] );
			}
		}
	}

	/**
	 * Get users from file and insert to database
	 *
	 * @since 1.0.0
	 *
	 * @param $filename
	 *
	 * @throws \PhpOffice\PhpSpreadsheet\Exception
	 */
	private function read_users_from_file( $filename ) {

		$reader      = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
		$spreadsheet = $reader->load( $filename );
		$worksheet   = $spreadsheet->getActiveSheet();
		$highestRow  = $worksheet->getHighestRow();

		session_start();
		$_SESSION['pmpro_import_notice'] = '';

		$count_users = 0;
		for ( $row = 2; $row <= $highestRow; ++ $row ) {
			$user_data                      = array();
			$user_data['member_id']         = sanitize_text_field( $worksheet->getCell( 'A' . $row )->getValue() );
			$user_data['member_first_name'] = sanitize_text_field( $worksheet->getCell( 'B' . $row )->getValue() );
			$user_data['member_last_name']  = sanitize_text_field( $worksheet->getCell( 'C' . $row )->getValue() );
			$user_data['chapter_name']      = sanitize_text_field( $worksheet->getCell( 'D' . $row )->getValue() );

			if ( $this->update_user( $user_data ) ) {
				$count_users ++;
			}

		}

		$_SESSION['pmpro_import_notice'] .= '<br>Updated: ' . $count_users . ' users';

	}

	/**
	 * Add success notice for show imported count
	 *
	 * @since 1.0.0
	 */
	public function add_notice() {

		if ( ! isset( $_SESSION['pmpro_import_notice'] ) || empty( $_SESSION['pmpro_import_notice'] ) ) {
			return;
		}
		?>
        <div class="notice notice-success is-dismissible">
			<?php echo $_SESSION['pmpro_import_notice']; ?>
        </div>
		<?php
		unset( $_SESSION['pmpro_import_notice'] );

	}

	/**
	 * Update user in database
	 *
	 * @since 1.0.0
	 *
	 * @param array $user_data
	 *
	 * @return bool
	 */
	private function update_user( array $user_data ) {

		$chapters_bridge = $this->get_chapters_bridge();
		$chapter_id      = 0;

		foreach ( $chapters_bridge as $id => $chapter ) {
			if ( strtolower( $user_data['chapter_name'] ) === strtolower( $chapter['old_name'] ) ) {
				$chapter_id = $id;
				break;
			}
		}

		if ( 0 === $chapter_id ) {
			return false;
		}

		$users = $this->get_users();
		foreach ( $users as $user_id => $user ) {
			$is_by_name      = strtolower( $user_data['member_first_name'] ) == strtolower( $user['first_name'] )
			                   && strtolower( $user_data['member_last_name'] ) == strtolower( $user['last_name'] );
			$is_by_member_id = $user_data['member_id'] == $user['member_id'];
			if ( $is_by_member_id || $is_by_name ) {
				$_SESSION['pmpro_import_notice'] .= 'User <strong>' . $user_data['member_first_name'] . ' '
				                                    . $user_data['member_last_name'] . '</strong> moved to chapter #' . $chapter_id
				                                    . ' <strong>' . $chapters_bridge[ $chapter_id ]['title'] . '</strong><br>';
				update_user_meta( $user_id, 'chapter_id', $chapter_id );
				update_user_meta( $user_id, 'member_legacy_ID', sanitize_text_field( $user_data['member_id'] ) );

				return true;
			}
		}

		return false;
	}

	/**
	 * Get bridge for old chapters
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	private function get_chapters_bridge() {

		if ( ! $this->chapter_bridge || empty( $this->chapter_bridge ) ) {
			$this->chapter_bridge = array();

			$args     = array(
				'post_type'   => 'chapters',
				'numberposts' => - 1,
				'orderby'     => 'title',
				'order'       => 'ASC',
			);
			$chapters = get_posts( $args );
			foreach ( $chapters as $chapter ) {
				$chapter_old_name = get_post_meta( $chapter->ID, 'pmpro_chapters_old_name', true );
				if ( ! empty( $chapter_old_name ) ) {
					$this->chapter_bridge[ $chapter->ID ] = array(
						'old_name' => $chapter_old_name,
						'title'    => $chapter->post_title,
					);
				}
			}
		}

		return $this->chapter_bridge;
	}

	/**
	 * Get all users
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	private function get_users() {

		if ( ! $this->users || empty( $this->users ) ) {
			$users = get_users( array(
				'fields' => array(
					'ID',
				)
			) );

			$this->users = array();
			foreach ( $users as $user ) {
				$user_data                = get_userdata( $user->ID );
				$this->users[ $user->ID ] = array(
					'first_name' => $user_data->first_name,
					'last_name'  => $user_data->last_name,
					'member_id'  => get_user_meta( $user->ID, 'member_legacy_ID', true ),
				);
			}
		}

		return $this->users;
	}

}

function pmpro_chapters_backend_total_import_runner() {

	$filters = new PMPRO_Chapters_Backend_Total_Import();
	$filters->init();

	return true;
}

pmpro_chapters_backend_total_import_runner();