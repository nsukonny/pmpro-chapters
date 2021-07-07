<?php
/**
 * Class PMPRO_Chapters_Backend_Filters
 * Add new post metaboxes
 *
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class PMPRO_Chapters_Backend_Filters {

	/**
	 * PMPRO_Chapters_Backend_Filters initialization class.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		add_action( 'restrict_manage_users', array( $this, 'add_filter_by_chapter' ), 10, 1 );
		add_filter( 'pre_get_users', array( $this, 'filter_by_chapter' ), 10, 1 );

	}

	/**
	 * Add filter in users page
	 *
	 * @since 1.0.0
	 *
	 * @param $which
	 */
	public function add_filter_by_chapter( $which ) {

		$args     = array(
			'post_type'   => 'chapters',
			'numberposts' => - 1,
			'orderby'=> 'title',
			'order' => 'ASC',
		);
		$chapters = get_posts( $args );

		$filter_chapter_id = $this->get_filter_chapter_id();
		if ( 0 < count( $chapters ) ) { ?>
            <select name="filter_chapter_id_<?php esc_attr_e( $which ); ?>" id="filter_chapter_id"
                    style="float:none;margin-left:10px;">
                <option value=""><?php _e( 'Chapter' ); ?></option>
				<?php foreach ( $chapters as $chapter ) { ?>
					<?php
					$closed = get_post_meta( $chapter->ID, 'chapter_closed', true );
					if ( 'yes' == $closed ) {
					    continue;
					}
					?>
                    <option value="<?php esc_attr_e( $chapter->ID ); ?>"
						<?php selected( $chapter->ID, $filter_chapter_id, true ); ?>>
						<?php esc_attr_e( $chapter->post_title ); ?>
                    </option>
				<?php } ?>
            </select>
			<?php
			submit_button( __( 'Filter' ), null, $which, false );
		}

	}

	/**
	 * Apply filter by chapter
	 *
	 * @since 1.0.0
	 *
	 * @param $query
	 */
	public function filter_by_chapter( $query ) {
		global $pagenow;

		if ( is_admin() && 'users.php' == $pagenow ) {
			$filter_chapter_id = $this->get_filter_chapter_id();

			if ( null !== $filter_chapter_id ) {

				$meta_query = array(
					array(
						'key'     => 'chapter_id',
						'value'   => $filter_chapter_id,
						'compare' => '=',
					)
				);

				$query->set( 'meta_query', $meta_query );

			}
		}
	}

	/**
	 * Get filtered chapter if exist
	 *
	 * @since 1.0.0
	 * @since 1.0.1 Renamed from get_filtered_chapter to get_filter_chapter_id
	 *
	 * @return string|null
	 */
	private function get_filter_chapter_id() {

		$has_top    = isset( $_GET['filter_chapter_id_top'] ) && 0 < strlen( trim( $_GET['filter_chapter_id_top'] ) );
		$has_bottom = isset( $_GET['filter_chapter_id_bottom'] ) && 0 < strlen( trim( $_GET['filter_chapter_id_bottom'] ) );

		$filter_chapter_id = $has_top ? sanitize_text_field( $_GET['filter_chapter_id_top'] ) : null;
		if ( null === $filter_chapter_id ) {
			$filter_chapter_id = null == $filter_chapter_id && $has_bottom
				? sanitize_text_field( $_GET['filter_chapter_id_bottom'] ) : null;
		}

		return $filter_chapter_id;
	}

}

function pmpro_chapters_backend_filters_runner() {

	$filters = new PMPRO_Chapters_Backend_Filters;
	$filters->init();

	return true;
}

pmpro_chapters_backend_filters_runner();