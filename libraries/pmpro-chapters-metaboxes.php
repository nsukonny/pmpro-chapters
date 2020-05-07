<?php
/**
 * Class PMPRO_Chapters_Metaboxes
 * Add new post metaboxes
 *
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class PMPRO_Chapters_Metaboxes {

	/**
	 * PMPRO_Chapters_Metaboxes initialization class.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		add_action( 'add_meta_boxes', array( $this, 'add_metaboxes' ) );
		add_action( 'save_post', array( $this, 'save_metaboxes' ) );

	}

	/**
	 * Add new metabox for chapter parameters
	 *
	 * @since 1.0.0
	 */
	public function add_metaboxes() {

		add_meta_box( 'chapter_parameters', __( 'Chapter parameters' ), array(
			$this,
			'chapter_coutry_metabox'
		), 'chapters', 'normal', 'high' );

	}

	/**
	 * Body of metabox with chapter parameters
	 *
	 * @since 1.0.0
	 *
	 * @param $post
	 * @param $meta
	 */
	public function chapter_coutry_metabox( $post, $meta ) {

		$this->select_region( $post );
		$this->select_country( $post );
		$this->select_state( $post );
		$this->closed( $post );

	}

	/**
	 * Select region for that chapter
	 *
	 * @since 1.0.0
	 */
	private function select_region( $post ) {
		?>
        <p>
            <label for="chapter_country">Region: </label>
            <select name="chapter_region" id="chapter_region"
                    class="js_field-country select2-hidden-accessible" style="width: 25em;" tabindex="-1"
                    aria-hidden="true">
				<?php
				if ( function_exists( 'upme_country_value_list' ) ) {
					$regions     = self::get_regions();
					$region_code = get_post_meta( $post->ID, 'chapter_region', true );
					if ( $regions ) {
						foreach ( $regions as $code => $region ) {
							?>
                            <option value="<?php esc_attr_e( $code ); ?>"
								<?php selected( $region_code, $code, true ); ?> >
								<?php esc_attr_e( $region ); ?>
                            </option>
							<?php
						}
					}
				}
				?>
            </select>
        </p>
		<?php
	}

	/**
	 * Select country for that chapter
	 *
	 * @since 1.0.0
	 */
	private function select_country( $post ) {
		?>
        <p>
            <label for="chapter_country">Country: </label>
            <select name="chapter_country" id="chapter_country"
                    class="js_field-country select2-hidden-accessible" style="width: 25em;" tabindex="-1"
                    aria-hidden="true">
				<?php
				if ( function_exists( 'upme_country_value_list' ) ) {
					$countries    = upme_country_value_list();
					$country_code = get_post_meta( $post->ID, 'chapter_country', true );
					if ( $countries ) {
						foreach ( $countries as $code => $country ) {
							?>
                            <option value="<?php esc_attr_e( $code ); ?>"
								<?php selected( $country_code, $code, true ); ?>
                            ><?php esc_attr_e( $country ); ?></option>
							<?php
						}
					}
				}
				?>
            </select>
        </p>
		<?php
	}

	/**
	 * Select state for that chapter
	 *
	 * @since 1.0.0
	 */
	private function select_state( $post ) {

		?>
        <p>
            <label for="chapter_state">State: </label>
            <select name="chapter_state" id="chapter_state"
                    class="js_field-country select2-hidden-accessible" style="width: 25em;" tabindex="-1"
                    aria-hidden="true">
                <option value="0"></option>
				<?php
				$states     = self::get_states();
				$state_code = get_post_meta( $post->ID, 'chapter_state', true );
				if ( $states ) {
					foreach ( $states as $code => $state ) {
						?>
                        <option value="<?php esc_attr_e( $code ); ?>"
							<?php selected( $state_code, $code, true ); ?> ><?php esc_attr_e( $state ); ?></option>
						<?php
					}
				}
				?>
            </select>
        </p>
		<?php
	}

	/**
	 * Show checkbox for closing chapter
	 *
	 * @since 1.0.0
	 */
	private function closed( $post ) {

		$closed = get_post_meta( $post->ID, 'chapter_closed', true );
		?>
        <p>
            <label for="chapter_closed">Closed? : </label>
            <input type="checkbox" name="chapter_closed" id="chapter_closed" value="yes"
				<?php checked( $closed, 'yes', true ); ?> >
        </p>
		<?php
	}

	/**
	 * Get states list from WooCommerce
	 *
	 * @since 1.0.0
	 *
	 * @return mixed
	 */
	public static function get_states() {

		$countries_obj = new WC_Countries();
		$states        = $countries_obj->__get( 'states' );

		return $states['US'];
	}

	/**
	 * Get list of regions
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public static function get_regions() {

		$regions = array(
			'01' => 'USA',
			'02' => 'INTL',
		);

		return $regions;
	}


	/**
	 * Save all metaboxes data
	 *
	 * @since 1.0.0
	 *
	 * @param $post_id
	 */
	public function save_metaboxes( $post_id ) {

		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		     || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		update_post_meta( $post_id, 'chapter_region', sanitize_text_field( $_POST['chapter_region'] ) );
		update_post_meta( $post_id, 'chapter_country', sanitize_text_field( $_POST['chapter_country'] ) );
		update_post_meta( $post_id, 'chapter_state', sanitize_text_field( $_POST['chapter_state'] ) );
		update_post_meta( $post_id, 'chapter_closed', 'yes' == $_POST['chapter_closed'] ? 'yes' : 'no' );
	}

}

function pmpro_chapters_metaboxes_runner() {

	$frontend = new PMPRO_Chapters_Metaboxes;
	$frontend->init();

	return true;
}

pmpro_chapters_metaboxes_runner();