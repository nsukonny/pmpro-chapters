<?php

/**
 * Class My_List_Table
 * Customize WP List Table for chapter users
 *
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class PMPRO_Chapters_Users_List_Table extends WP_List_Table {

	/**
	 * Count of all chapter users
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	private $total_users = 0;

	/**
	 * Users per page
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	private $per_page = 15;

	/**
	 * Chapter ID
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	public $chapter_id;

	/**
	 * PMPRO_Chapters_Users_List_Table constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		parent::__construct( array(
			'singular' => __( 'Chapter user', 'pmpro-chapters' ),
			'plural'   => __( 'Chapter users', 'pmpro-chapters' ),
			'ajax'     => true,
		) );

		add_action( 'admin_head', array( &$this, 'admin_header' ) );

	}

	/**
	 * Styles for table
	 *
	 * @since 1.0.0
	 */
	public function admin_header() {

		$page = ( isset( $_GET['page'] ) ) ? esc_attr( $_GET['page'] ) : false;
		if ( 'chapter_detail' != $page ) {
			return;
		}
		echo '<style type="text/css">';
		echo '.wp-list-table .column-member_id { width: 5%; }';
		echo '.wp-list-table .column-user_id { width: 5%; }';
		echo '.wp-list-table .column-user_login { width: 30%; }';
		echo '.wp-list-table .column-first_name { width: 30%; }';
		echo '.wp-list-table .column-last_name { width: 30%; }';
		echo '</style>';

	}

	/**
	 * Message about empty result
	 *
	 * @since 1.0.0
	 */
	public function no_items() {

		_e( 'No users found in this chapter.', 'pmpro-chapters' );

	}

	/**
	 * Set columns
	 *
	 * @since 1.0.0
	 *
	 * @param object $item
	 * @param string $column_name
	 *
	 * @return string
	 */
	public function column_default( $item, $column_name ) {

		switch ( $column_name ) {
			case 'member_id':
				$member_legacy_ID = get_user_meta( $item->ID, 'member_legacy_ID', true );

				return $member_legacy_ID;
			case 'user_login':
				return $item->user_login;
			case 'first_name':
				return $item->first_name;
			case 'last_name':
				return $item->last_name;
			case 'user_id':
				return $item->ID;
			default:
				return print_r( $item, true );

		}

		return '';
	}

	/**
	 * Add sortable for columns
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_sortable_columns() {

		$sortable_columns = array(
			'member_id'  => array( 'member_id', false ),
			'user_id'    => array( 'user_id', false ),
			'user_login' => array( 'user_login', false ),
			'first_name' => array( 'first_name', false ),
			'last_name'  => array( 'last_name', false ),
		);

		return $sortable_columns;
	}

	/**
	 * Set columns
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_columns() {

		$columns = array(
			'cb'         => '<input type="checkbox" />',
			'member_id'  => __( 'Member ID', 'pmpro-chapters' ),
			'user_id'    => __( 'User ID', 'pmpro-chapters' ),
			'user_login' => __( 'User Name', 'pmpro-chapters' ),
			'first_name' => __( 'First Name', 'pmpro-chapters' ),
			'last_name'  => __( 'Last Name', 'pmpro-chapters' ),
		);

		return $columns;
	}

	/**
	 * Order for sort columns
	 *
	 * @since 1.0.0
	 *
	 * @param $a
	 * @param $b
	 *
	 * @return int|lt
	 */
	public function usort_reorder( $a, $b ) {

		$orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'user_id';
		$order   = ( ! empty( $_GET['order'] ) ) ? $_GET['order'] : 'asc';
		$result  = strcmp( $a[ $orderby ], $b[ $orderby ] );

		return ( $order === 'asc' ) ? $result : - $result;
	}

	/**
	 * Modification for user_login
	 *
	 * @since 1.0.0
	 *
	 * @param $item
	 *
	 * @return string
	 */
	public function column_user_login( $item ) {

		$edit_link   = admin_url( 'user-edit.php?user_id=' . $item->ID );
		$delete_link = wp_nonce_url( "users.php?action=delete&user={$item->ID}", 'bulk-users' );
		$actions     = array(
			'edit'   => sprintf( '<a href="' . $edit_link . '">Edit</a>', $_REQUEST['page'], 'edit', $item->ID ),
			'delete' => sprintf( '<a href="' . $delete_link . '">Delete</a>', $_REQUEST['page'], 'delete', $item->ID ),
		);

		return sprintf( '%1$s %2$s', $item->user_login, $this->row_actions( $actions ) );
	}

	/**
	 * Bulk delete action
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_bulk_actions() {

		$actions = array(
			'delete' => 'Delete'
		);

		return $actions;
	}

	/**
	 * Render column checkbox
	 *
	 * @since 1.0.0
	 *
	 * @param object $item
	 *
	 * @return string|void
	 */
	public function column_cb( $item ) {

		return sprintf(
			'<input type="checkbox" name="user_id[]" value="%s" />', $item->ID
		);
	}

	/**
	 * Prepare all items
	 *
	 * @since 1.0.0
	 */
	public function prepare_items() {

		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$current_page = $this->get_pagenum();
		$this->items  = self::get_users( $this->chapter_id, $current_page, $this->per_page );

		$this->set_pagination_args( array(
			'total_items' => $this->total_users,
			'per_page'    => $this->per_page,
		) );

	}

	/**
	 * Get chapter users
	 *
	 * @since 1.0.0
	 *
	 * @param $chapter_id
	 * @param int $page_number
	 *
	 * @param int $per_page
	 *
	 * @return array|object|null
	 */
	private function get_users( $chapter_id, $page_number = 1, $per_page = 15 ) {

		$args = array(
			'number'     => $per_page,
			'offset'     => ( $page_number - 1 ) * $per_page,
			'meta_key'   => 'chapter_id',
			'meta_value' => sanitize_text_field( $chapter_id ),
		);

		$users_query       = new WP_User_Query( $args );
		$users             = $users_query ? $users_query->get_results() : array();
		$this->total_users = $users_query ? $users_query->get_total() : 0;

		return $users;
	}

	/**
	 * Add buttons to table nav block
	 *
	 * @since 1.0.0
	 *
	 * @param string $which
	 */
	public function extra_tablenav( $which ) {

		$export_link = admin_url( 'admin.php?page=chapter_detail&chapter_id=' . $this->chapter_id . '&action=export' );
		$import_link = admin_url( 'admin.php?page=chapter_detail&chapter_id=' . $this->chapter_id . '&action=import' );
		?>
        <a href="<?php echo esc_url( $export_link ); ?>"
           class="button action"><?php _e( 'Export', 'pmpro-chapters' ); ?>
            (<?php esc_attr_e( $this->total_users ); ?> <?php _e( 'users', 'pmpro-chapters' ); ?>)</a>
        <a href="#" class="button action" id="pmpro-chapters-import"><?php _e( 'Import', 'pmpro-chapters' ); ?></a>
        <form action="<?php echo esc_url( $import_link ); ?>" method="post" id="pmpro-cahpters-import-form"
              enctype="multipart/form-data" style="display: none;">
			<?php wp_nonce_field( 'import_excel_file', 'fileup_nonce' ); ?>
            <input type="file" name="import_excel_file"
                   accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"/>
            <input type="submit" value="upload file"/>
        </form>
		<?php

	}

}