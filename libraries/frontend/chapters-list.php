<?php
/**
 * Class PMPRO_Chapters_Frontend_Chapters_List
 * Add shortcode for display list
 *
 * @since 1.0.1
 */

defined( 'ABSPATH' ) || exit;

class PMPRO_Chapters_Frontend_Chapters_List {

	/**
	 * PMPRO_Chapters_Frontend_Chapters_List initialization class.
	 *
	 * @since 1.0.1
	 */
	public function init() {

		add_shortcode( 'pmpro_chapters_list', array( $this, 'chapters_list' ) );

	}

	/**
	 * Display chapters list by shortcode
	 *
	 * @since 1.0.1
	 *
	 * @param $atts
	 *
	 * @return string
	 */
	public function chapters_list( $atts ) {

		if ( isset( $atts['states'] ) && $atts['states'] ) {
			$tabs = $this->by_states();
		} elseif ( isset( $atts['sig'] ) && $atts['sig'] ) {
			$tabs = $this->by_sig();
		} else {
			$tabs = $this->by_countries();
		}

		$output = $this->render_tabs( $tabs );

		return $output;
	}

	/**
	 * Show chapters list by countries for international
	 *
	 * @since 1.0.1
	 *
	 * @return array
	 */
	private function by_countries() {

		$countries = PMPRO_Chapters_Supports::get_countries();
		$chapters  = PMPRO_Chapters_Supports::get_chapters();
		$tabs      = array();

		if ( $countries && $chapters ) {
			foreach ( $countries as $country_code => $country ) {
				if ( empty( $country_code ) || 'US' == $country_code ) {
					continue;
				}

				$tab = array(
					'tab_code' => $country_code,
					'tab_name' => $country,
					'chapters' => array(),
				);

				foreach ( $chapters as $chapter ) {

					$chapter_country = get_post_meta( $chapter->ID, 'chapter_country', true );
					$closed          = get_post_meta( $chapter->ID, 'chapter_closed', true );
					if ( $chapter_country == $country_code && 'yes' != $closed ) {
						$tab['chapters'][] = $chapter;
					}

				}

				if ( 0 < count( $tab['chapters'] ) ) {
					$tabs[] = $tab;
				}
			}
		}

		return $tabs;
	}

	/**
	 * Show chapters list by "Special Interests Group" region
	 *
	 * @since 1.0.1
	 *
	 * @return array
	 */
	private function by_sig() {

		$regions  = PMPRO_Chapters_Supports::get_regions();
		$chapters = PMPRO_Chapters_Supports::get_chapters();
		$tabs     = array();

		if ( $regions && $chapters ) {
			foreach ( $regions as $region ) {
				if ( strtolower( $region->post_title ) != 'Special Interest Groups'
				     && 4812 != $region->ID ) {
					continue;
				}

				$tab = array(
					'tab_code' => 'all_countries',
					'tab_name' => __( 'All', 'pmpro-chapters' ),
					'chapters' => array(),
				);

				foreach ( $chapters as $chapter ) {

					$chapter_region = get_post_meta( $chapter->ID, 'chapter_region', true );
					$closed         = get_post_meta( $chapter->ID, 'chapter_closed', true );
					if ( $chapter_region == $region->ID && 'yes' != $closed ) {
						$tab['chapters'][] = $chapter;
					}

				}

				if ( 0 < count( $tab['chapters'] ) ) {
					$tabs[] = $tab;
				}
			}
		}

		return $tabs;
	}

	/**
	 * Show chapters list by regions
	 *
	 * @since 1.0.1
	 *
	 * @return array
	 */
	private function by_states() {

		$states   = PMPRO_Chapters_Supports::get_states();
		$chapters = PMPRO_Chapters_Supports::get_chapters();
		$tabs     = array();

		if ( $states && $chapters ) {
			foreach ( $states as $code => $state ) {
				$tab = array(
					'tab_code' => $code,
					'tab_name' => $state,
					'chapters' => array(),
				);

				foreach ( $chapters as $chapter ) {

					$chapter_state_code = get_post_meta( $chapter->ID, 'chapter_state', true );
					$closed             = get_post_meta( $chapter->ID, 'chapter_closed', true );
					if ( $chapter_state_code == $code && 'yes' != $closed ) {
						$tab['chapters'][] = $chapter;
					}

				}

				if ( 0 < count( $tab['chapters'] ) ) {
					$tabs[] = $tab;
				}
			}
		}

		return $tabs;
	}

	/**
	 * Display tabs
	 *
	 * @since 1.0.1
	 *
	 * @param $tabs
	 *
	 * @return string
	 */
	private function render_tabs( $tabs ) {

		$output = '';

		if ( 0 < count( $tabs ) ) {
			$output = '<div class="pmpro-chapters-list">
                        <div class="list-wrapper">
                        <div class="list-wrapper-content">
                            <ul class="chapter-tabs">';

			$i = 0;
			foreach ( $tabs as $tab ) {
				$output .= '<li class="chapter-tabs__tab' . ( 0 == $i ? ' chapter-tabs__tab_active' : '' ) . '">
                                <a href="#" data-pane="chapter_tab_' . esc_attr( $tab['tab_code'] ) . '" 
                                class="chapter-tabs__title">'
				           . esc_attr( $tab['tab_name'] ) . '</a>
                            </li>';
				$i ++;
			}
			$output .= '</ul>';

			$output .= '<div class="chapter-tab-panes">';
			$i      = 0;
			foreach ( $tabs as $tab ) {
				$output .= '<div id="chapter_tab_' . esc_attr( $tab['tab_code'] ) . '" 
                                class="chapter-pane' . ( 0 == $i ? ' chapter-pane_active' : '' ) . '">';

				foreach ( $tab['chapters'] as $chapter ) {

					$president            = get_post_meta( $chapter->ID, 'chapter_president', true );
					$chapter_social_links = get_post_meta( $chapter->ID, 'chapter_social', true );

					$output .= '<div class="chapter-pane-elem">
                                        <div class="chapter-pane-elem__title">' . esc_attr( $chapter->post_title ) . '</div>
                                        <div class="chapter-pane-elem__subtitle">' . esc_attr( $president ) . '</div>
                                        <div class="chapter-pane-elem__description">' . $chapter->post_content . '</div>
                                    <div class="chapter-pane-elem__social">';

					if ( ! empty( $chapter_social_links ) && 0 < count( $chapter_social_links ) ) {
						foreach ( $chapter_social_links as $chapter_social_link ) {
							$output .= '<a href="' . esc_url( $chapter_social_link['href'] ) . '" class="chapter-pane-elem__social-link" ><i class="' . esc_attr( $chapter_social_link['icon'] ) . '"></i></a>';
						}
					}

					$output .= '</div></div >';
				}

				$output .= '</div>';
				$i ++;
			}
			$output .= '</div>';

			$output .= '</div></div></div>';
		}

		return $output;
	}

}

function pmpro_chapters_frontend_chapters_list_runner() {

	$chapters_list = new PMPRO_Chapters_Frontend_Chapters_List;
	$chapters_list->init();

	return true;
}

pmpro_chapters_frontend_chapters_list_runner();