<?php
/**
 * Class PMPRO_Chapters_Supports
 * Static methods for global using
 *
 * @since 1.0.1
 */

defined( 'ABSPATH' ) || exit;

class PMPRO_Chapters_Supports {

	/**
	 * Get states list from WooCommerce
	 *
	 * @since 1.0.1
	 *
	 * @param string $country_code
	 *
	 * @return mixed
	 */
	public static function get_states( $country_code = 'US' ) {

		if (0 === strlen($country_code)) {
			$country_code = 'US';
		}

		$countries_obj = new WC_Countries();
		$states        = $countries_obj->__get( 'states' );

		return isset( $states[ $country_code ] ) ? $states[ $country_code ] : array();
	}

	/**
	 * Get list of regions
	 *
	 * @since 1.0.1
	 *
	 * @return WP_Post[]
	 */
	public static function get_regions() {

		$args = array(
			'post_type'   => 'chapter_regions',
			'numberposts' => - 1,
		);

		return get_posts( $args );
	}

	/**
	 * Get list of chapters
	 *
	 * @since 1.0.1
	 *
	 * @return int[]|WP_Post[]
	 */
	public static function get_chapters() {

		$chapters = get_posts( array(
			'post_type'   => 'chapters',
			'numberposts' => - 1,
			'post_status' => array( 'publish', 'private' ),
			'orderby'=> 'title',
			'order' => 'ASC',
		) );

		return $chapters;
	}

	/**
	 * Get countri list from WooCommerce or upme country plugin
	 *
	 * @since 1.0.1
	 *
	 * @return array|mixed
	 */
	public static function get_countries() {

		$countries = array();

		if ( class_exists( 'WC_Countries' ) ) {
			$wc_countries = new WC_Countries();
			$countries    = $wc_countries->__get( 'countries' );
		} elseif ( function_exists( 'upme_country_value_list' ) ) {
			$countries = upme_country_value_list();
		}

		return array_merge( array( __( 'All', 'pmpro-chapters' ) ), $countries );
	}

}