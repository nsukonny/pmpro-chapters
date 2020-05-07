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

		$args             = array(
			'post_type'   => 'chapters',
			'numberposts' => - 1
		);
		$chapters         = get_posts( $args );
		$filtered_chapter = isset( $_GET['filter_chapter_top'] ) ? $_GET['filter_chapter_top'] :
			isset( $_GET['filter_chapter_bottom'] ) ? $_GET['filter_chapter_bottom'] : 0;
		if ( 0 < count( $chapters ) ) { ?>
            <select name="filter_chapter_<?php esc_attr_e( $which ); ?>" id="filter_chapter"
                    style="float:none;margin-left:10px;">
                <option value=""><?php _e( 'Chapter' ); ?></option>
				<?php foreach ( $chapters as $chapter ) { ?>
                    <option value="<?php esc_attr_e( $chapter->ID ); ?>"
						<?php selected( $chapter->ID, $filtered_chapter, true ); ?>>
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
			$filtered_chapter = isset( $_GET['filter_chapter_top'] ) ? $_GET['filter_chapter_top'] :
				isset( $_GET['filter_chapter_bottom'] ) ? $_GET['filter_chapter_bottom'] : null;

			if ( null != $filtered_chapter ) {

				$meta_query = array(
					array(
						'key'     => 'chapter_region',
						'value'   => $filtered_chapter,
						'compare' => '='
					)
				);
				$query->set( 'meta_query', $meta_query );

			}
		}
	}

}

function pmpro_chapters_backend_filters_runner() {

	$filters = new PMPRO_Chapters_Backend_Filters;
	$filters->init();

	return true;
}

pmpro_chapters_backend_filters_runner();