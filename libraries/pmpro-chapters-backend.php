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
                <th><label for="chapter_region">Chapter Region</label></th>
                <td>
                    <select name="chapter_region" id="chapter_region"
                            class="js_field-country select2-hidden-accessible"
                            style="width: 25em;" tabindex="-1" aria-hidden="true">
                        <option value="0">- - -</option>
						<?php
						$chapters   = $this->get_chapters_list();
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

		if ( current_user_can( 'edit_user', $user_id ) && ! empty( $_POST['chapter_user_id'] ) ) {
			update_user_meta( $user_id, 'chapter_region', esc_attr( sanitize_text_field( $_POST['chapter_region'] ) ) );
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

		$chapter_region = get_user_meta( $user_id, 'chapter_region', true );

		if ( ! is_numeric( $chapter_region ) && ! empty( $chapter_region ) ) {
			$chapters = $this->get_chapters_list();
			foreach ( $chapters as $chapter ) {
				if ( strtolower( $chapter_region ) == strtolower( $chapter->post_title ) ) {

					return $chapter->ID;
				}
			}
		}

		return $chapter_region;
	}

	/**
	 * Get list of chapters
	 *
	 * @since 1.0.0
	 *
	 * @return WP_Post[]
	 */
	private function get_chapters_list() {

		$chapters = get_posts(
			array(
				'post_type'      => 'chapters',
				'posts_per_page' => - 1,
			)
		);

		return $chapters;
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
			require PMPRO_CHAPTERS_LIBRARIES_PATH . '/backend/pmpro-chapters-backend-filters.php';
		}

	}

}

function pmpro_chapters_backend_runner() {

	$frontend = new PMPRO_Chapters_Backend;
	$frontend->init();

	return true;
}

pmpro_chapters_backend_runner();