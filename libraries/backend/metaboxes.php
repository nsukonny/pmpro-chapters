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

		add_meta_box( 'chapter_parameters', __( 'Chapter settings' ), array(
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

		$this->input_president( $post );
		$this->select_region( $post );
		$this->select_country( $post );
		$this->select_state( $post );
		$this->closed( $post );
		$this->social_links( $post );

	}

	/**
	 * Select region for that chapter
	 *
	 * @since 1.0.0
	 */
	private function select_region( $post ) {

		?>
        <div class="pmpro-chapters-row">
            <label for="chapter_region" class="pmpro-chapters-row__label">Region:</label>
            <select name="chapter_region" id="chapter_region"
                    class="js_field-country select2-hidden-accessible pmpro-chapters-row__input" style="width: 25em;"
                    tabindex="-1"
                    aria-hidden="true">
				<?php
				if ( function_exists( 'upme_country_value_list' ) ) {
					$regions     = PMPRO_Chapters_Supports::get_regions();
					$region_code = get_post_meta( $post->ID, 'chapter_region', true );
					if ( $regions ) {
						foreach ( $regions as $region ) {
							?>
                            <option value="<?php esc_attr_e( $region->ID ); ?>"
								<?php selected( $region_code, $region->ID, true ); ?> >
								<?php esc_attr_e( $region->post_title ); ?>
                            </option>
							<?php
						}
					}
				}
				?>
            </select>
        </div>
		<?php

	}

	/**
	 * Field for set president of chapter
	 *
	 * @since 1.0.1
	 */
	private function input_president( $post ) {

		?>
        <div class="pmpro-chapters-row">
            <label for="chapter_president" class="pmpro-chapters-row__label">President:</label>
            <input name="chapter_president" id="chapter_president" class="pmpro-chapters-row__input"
                   value="<?php esc_attr_e( get_post_meta( $post->ID, 'chapter_president', true ) ); ?>">
        </div>
		<?php

	}

	/**
	 * Select country for that chapter
	 *
	 * @since 1.0.0
	 */
	private function select_country( $post ) {

		?>
        <div class="pmpro-chapters-row">
            <label for="chapter_country" class="pmpro-chapters-row__label">Country:</label>
            <select name="chapter_country" id="chapter_country"
                    class="js_field-country select2-hidden-accessible pmpro-chapters-row__input" style="width: 25em;"
                    tabindex="-1"
                    aria-hidden="true">
				<?php
                $countries = PMPRO_Chapters_Supports::get_countries();
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
				?>
            </select>
        </div>
		<?php

	}

	/**
	 * Select state for that chapter
	 *
	 * @since 1.0.0
	 */
	private function select_state( $post ) {

		?>
        <div class="pmpro-chapters-row">
            <label for="chapter_state" class="pmpro-chapters-row__label">State:</label>
            <select name="chapter_state" id="chapter_state"
                    class="js_field-country select2-hidden-accessible pmpro-chapters-row__input" style="width: 25em;"
                    tabindex="-1"
                    aria-hidden="true">
                <option value="0"></option>
				<?php
				$states     = PMPRO_Chapters_Supports::get_states();
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
        </div>
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
        <div class="pmpro-chapters-row">
            <label for="chapter_closed" class="pmpro-chapters-row__label">Closed?:</label>
            <input type="checkbox" name="chapter_closed" id="chapter_closed" value="yes"
                   class="pmpro-chapters-row__checkbox"
				<?php checked( $closed, 'yes', true ); ?> >
        </div>
		<?php

	}

	/**
	 * Website for that chapter
	 *
	 * @since 1.0.1
	 */
	private function social_links( $post ) {

		$chapter_social_links = get_post_meta( $post->ID, 'chapter_social', true );
		if ( $chapter_social_links && 0 < count( $chapter_social_links ) ) {
			$i = 1;
			foreach ( $chapter_social_links as $chapter_social_link ) {
				?>
                <div class="pmpro-chapters-row">
                    <label for="chapter_social_<?php esc_attr_e( $i ); ?>"
                           class="pmpro-chapters-row__label"><?php _e( 'Link', 'pmpro-chapters' ); ?> :
                    </label>
                    <div class="btn-group">
                        <button type="button" class="btn btn-primary iconpicker-component">
                            <i class="<?php esc_attr_e( ! empty( $chapter_social_link['icon'] )
								? $chapter_social_link['icon'] : 'fas fa-link' ); ?>"></i>
                        </button>
                        <button type="button" class="icp icp-dd btn btn-primary dropdown-toggle"
                                data-selected="fa-car" data-toggle="dropdown">
                            <span class="caret"></span>
                            <span class="sr-only"><?php _e( 'Toggle Dropdown', 'pmpro-chapters' ); ?></span>
                        </button>
                        <div class="dropdown-menu"></div>
                    </div>
                    <input type="hidden" name="chapter_social[<?php esc_attr_e( $i ); ?>][icon]"
                           class="pmpro-chapters-row__social-icon"
                           value="<?php esc_attr_e( $chapter_social_link['icon'] ); ?>">
                    <input type="text" class="pmpro-chapters-row__input" id="chapter_social_<?php esc_attr_e( $i ); ?>"
                           name="chapter_social[<?php esc_attr_e( $i ); ?>][href]"
                           value="<?php esc_attr_e( $chapter_social_link['href'] ); ?>">
                    <a href="#" class="pmpro-chapters-row__delete"><i class="fas fa-backspace"></i></a>
                </div>
				<?php
				$i ++;
			}
		}

		?>
        <div class="pmpro-chapters-row">
            <a href="#" class="pmpro-chapters-row__add btn btn-primary">
                <i class="fas fa-plus-square"></i> <?php _e( 'Add new link', 'pmpro-chapters' ); ?>
            </a>
        </div>
		<?php

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

		if ( isset( $_POST['chapter_president'] ) ) {
			update_post_meta( $post_id, 'chapter_president', sanitize_text_field( $_POST['chapter_president'] ) );
		}

		if ( isset( $_POST['chapter_region'] ) ) {
			update_post_meta( $post_id, 'chapter_region', sanitize_text_field( $_POST['chapter_region'] ) );
		}

		if ( isset( $_POST['chapter_country'] ) ) {
			update_post_meta( $post_id, 'chapter_country', sanitize_text_field( $_POST['chapter_country'] ) );
		}

		if ( isset( $_POST['chapter_state'] ) ) {
			update_post_meta( $post_id, 'chapter_state', sanitize_text_field( $_POST['chapter_state'] ) );
		}

		if ( isset( $_POST['chapter_closed'] ) ) {
			update_post_meta( $post_id, 'chapter_closed', 'yes' == $_POST['chapter_closed'] ? 'yes' : 'no' );
		}

		if ( isset( $_POST['chapter_social'] ) && 0 < count( $_POST['chapter_social'] ) ) {
			$chapter_social = array();
			foreach ( $_POST['chapter_social'] as $social_data ) {
				$chapter_social[] = array(
					'icon' => sanitize_text_field( $social_data['icon'] ),
					'href' => sanitize_text_field( $social_data['href'] ),
				);
			}

			update_post_meta( $post_id, 'chapter_social', $chapter_social );
		} else {
			delete_post_meta( $post_id, 'chapter_social' );
		}

	}

}

function pmpro_chapters_metaboxes_runner() {

	$frontend = new PMPRO_Chapters_Metaboxes;
	$frontend->init();

	return true;
}

pmpro_chapters_metaboxes_runner();