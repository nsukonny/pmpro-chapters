<?php
/**
 * Class PMPRO_Chapters_Backend
 * Init all methods for work chapters in admin side
 *
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class PMPRO_Chapters_Backend {

	/**
	 * Couple WP_Post
	 *
	 * @since 1.0.0
	 *
	 * @var null
	 */
	private $chapter = null;

	/**
	 * PMPRO_Chapters_Backend initialization class.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		$this->includes();

		add_action( 'show_user_profile', array( $this, 'show_chapter_fields' ), 8, 1 );
		add_action( 'edit_user_profile', array( $this, 'show_chapter_fields' ), 8, 1 );

		add_action( 'personal_options_update', array( $this, 'save_chapter_fields' ), 10, 1 );
		add_action( 'edit_user_profile_update', array( $this, 'save_chapter_fields' ), 10, 1 );

		add_action( 'admin_enqueue_scripts', array( $this, 'add_scripts' ), 10 );

	}

	/**
	 * Add scripts and styles for frontend
	 *
	 * @since 1.0.1
	 */
	public function add_scripts() {

		wp_enqueue_style( 'pmpro-chapters-styles', PMPRO_CHAPTERS_PLUGIN_URL . '/assets/backend-style.css' );

		$this->load_iconpicker_scripts();

		wp_enqueue_script( 'pmpro-chapters-scripts',
			PMPRO_CHAPTERS_PLUGIN_URL . '/assets/backend-scripts.js', array(
				'jquery',
			), time(), true );

		wp_localize_script(
			'pmpro-chapters-scripts',
			'ajax_chapters',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
			)
		);

	}

	/**
	 * Display chapter for that user
	 *
	 * @since 1.0.0
	 *
	 * @param $user
	 */
	public function show_chapter_fields( $user ) {

		?>
        <h3>NCGR Chapter</h3>
        <table class="form-table">
            <tbody>
            <tr>
                <th><label for="chapter_id">Chapter (сonsists of)</label></th>
                <td>
                    <select name="chapter_id" id="chapter_id"
                            class="js_field-country select2-hidden-accessible"
                            style="width: 25em;" tabindex="-1" aria-hidden="true">
                        <option value="0">- - -</option>
						<?php
						$chapters   = PMPRO_Chapters_Supports::get_chapters();
						$chapter_id = $this->get_chapter_id( $user->ID );
						if ( $chapters ) {
							foreach ( $chapters as $chapter ) {
								?>
                                <option value="<?php esc_attr_e( $chapter->ID ); ?>"
									<?php selected( $chapter_id, $chapter->ID, true ); ?> >
									<?php esc_attr_e( $chapter->post_title ); ?>
                                </option>
								<?php
							}
						}
						?>
                    </select>
                </td>
            </tr>
            </tbody>
        </table>
		<?php
	}

	/**
	 * Save changes with chapter user
	 *
	 * @since 1.0.0
	 *
	 * @param $user_id
	 */
	public function save_chapter_fields( $user_id ) {

		if ( current_user_can( 'edit_user', $user_id ) && ! empty( $_POST['chapter_id'] ) ) {
			update_user_meta( $user_id, 'chapter_id', esc_attr( sanitize_text_field( $_POST['chapter_id'] ) ) );
		}

	}

	/**
	 * Get chapter by user ID
	 *
	 * @since 1.0.0
	 *
	 * @param $user_id
	 *
	 * @return WP_Post
	 */
	private function get_chapter( $user_id ) {

		if ( ! $this->chapter ) {
			$chapters = get_posts(
				array(
					'post_type'  => 'chapters',
					'meta_query' => array(
						'relation' => 'OR',
						array(
							'key'     => 'chapter_user_1',
							'value'   => $user_id,
							'compare' => '='
						),
						array(
							'key'     => 'chapter_user_2',
							'value'   => $user_id,
							'compare' => '='
						),
					),
				)
			);

			if ( $chapters ) {
				foreach ( $chapters as $chapter ) {
					$this->chapter = $chapter;
				}
			}
		}

		return $this->chapter;
	}

	/**
	 * Get user chapter ID
	 *
	 * @since 1.0.0
	 *
	 * @param $user_id
	 *
	 * @return mixed
	 */
	private function get_chapter_id( $user_id ) {

		$chapter_id = get_user_meta( $user_id, 'chapter_id', true );

		if ( ! is_numeric( $chapter_id ) && ! empty( $chapter_id ) ) {
			$chapters = PMPRO_Chapters_Supports::get_chapters();
			foreach ( $chapters as $chapter ) {
				if ( strtolower( $chapter_id ) == strtolower( $chapter->post_title ) ) {

					return $chapter->ID;
				}
			}
		}

		return $chapter_id;
	}

	/**
	 * Includes all necessary PHP files
	 *
	 * This function is responsible for including all necessary PHP files.
	 *
	 * @since 1.0.0
	 */
	private function includes() {

		if ( defined( 'PMPRO_CHAPTERS_LIBRARIES_PATH' ) ) {
			require PMPRO_CHAPTERS_LIBRARIES_PATH . '/pmpro-chapters-users-table.php';
			require PMPRO_CHAPTERS_LIBRARIES_PATH . '/backend/metaboxes.php';
			require PMPRO_CHAPTERS_LIBRARIES_PATH . '/backend/filters.php';
			require PMPRO_CHAPTERS_LIBRARIES_PATH . '/backend/detail-page.php';
			require PMPRO_CHAPTERS_LIBRARIES_PATH . '/backend/total-import.php';
		}

	}

	/**
	 * Load scripts for work icon picker
	 *
	 * @since 1.0.1
	 */
	private function load_iconpicker_scripts() {
		global $pagenow,
		       $post;

		$is_post_page = 'post.php' === $pagenow || 'post-new.php' === $pagenow;
		$is_chapters  = ( isset( $_GET['post_type'] ) && 'chapters' === $_GET['post_type'] )
		                || ( ! empty( $post ) && 'chapters' === $post->post_type );

		if ( $is_post_page && $is_chapters ) {
			wp_enqueue_style( 'pmpro-chapters-bootstrap-styles',
				'//netdna.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css' );

			wp_enqueue_style( 'pmpro-chapters-bootstrap',
				'https://use.fontawesome.com/releases/v5.5.0/css/all.css' );

			wp_enqueue_style( 'pmpro-chapters-iconpicker-styles',
				PMPRO_CHAPTERS_PLUGIN_URL . '/vendor/itsjavi/fontawesome-iconpicker/src/less/iconpicker.css' );

			wp_enqueue_script( 'pmpro-chapters-bootstrap-js',
				'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js', array( 'jquery' ), '3.3.7', true );

			wp_enqueue_script( 'pmpro-chapters-iconpicker',
				PMPRO_CHAPTERS_PLUGIN_URL . '/vendor/itsjavi/fontawesome-iconpicker/dist/js/fontawesome-iconpicker.js', array(
					'jquery',
					'pmpro-chapters-bootstrap-js',
				), false, true );

			wp_enqueue_style( 'select2-styles',
				WC()->plugin_url() . '/assets/css/select2.css' );

			wp_register_script( 'select2', WC()->plugin_url() . '/assets/js/select2/select2.full.min.js', array( 'jquery' ) );
			wp_enqueue_script( 'select2' );

			wp_register_script( 'selectWoo', WC()->plugin_url() . '/assets/js/selectWoo/selectWoo.full.min.js', array( 'jquery' ) );
			wp_enqueue_script( 'selectWoo' );

			wp_register_script( 'wc-enhanced-select', WC()->plugin_url() . '/assets/js/admin/wc-enhanced-select.min.js', array(
				'jquery',
				'selectWoo'
			) );
			wp_enqueue_script( 'wc-enhanced-select' );

		}

	}

}

function pmpro_chapters_backend_runner() {

	$backend = new PMPRO_Chapters_Backend;
	$backend->init();

	return true;
}

pmpro_chapters_backend_runner();