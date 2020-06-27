<?php
/**
 * Class PMPRO_Chapters_Frontend
 * Init all methods for work chapters
 *
 * @since 1.0.1
 */

defined( 'ABSPATH' ) || exit;

class PMPRO_Chapters_Frontend {

	/**
	 * PMPRO_Chapters_Frontend initialization class.
	 *
	 * @since 1.0.1
	 */
	public function init() {

		add_action( 'wp_enqueue_scripts', array( $this, 'add_scripts' ), 10 );
		$this->includes();

	}

	/**
	 * Add scripts and styles for frontend
	 *
	 * @since 1.0.1
	 */
	public function add_scripts() {

		wp_enqueue_style( 'pmpro-chapters-styles', PMPRO_CHAPTERS_PLUGIN_URL . '/assets/style.css' );

		wp_enqueue_script( 'pmpro-chapters-scripts', PMPRO_CHAPTERS_PLUGIN_URL . '/assets/scripts.js', array(
			'jquery',
		), time(), true );

		wp_enqueue_script( 'pmpro-chapters-fontawesome', 'https://kit.fontawesome.com/2dee5ce468.js', array(
			'jquery',
		), false, true );

		$hide_renewal_button = $this->check_need_hide_renewal();
		$hide_become_button  = $this->check_need_hide_become();

		wp_localize_script(
			'pmpro-chapters-scripts',
			'ajax_chapters',
			array(
				'ajax_url'            => admin_url( 'admin-ajax.php' ),
				'hide_renewal_button' => $hide_renewal_button,
				'hide_become_button'  => $hide_become_button,
			)
		);

	}

	/**
	 * Includes all necessary PHP files
	 *
	 * @since 1.0.1
	 */
	private function includes() {

		if ( defined( 'PMPRO_CHAPTERS_LIBRARIES_PATH' ) ) {
			require PMPRO_CHAPTERS_LIBRARIES_PATH . '/frontend/chapters-list.php';
			require PMPRO_CHAPTERS_LIBRARIES_PATH . '/frontend/chapters-reports.php';
		}

	}

	/**
	 * Check if we need to hide renewal button
	 *
	 * @since 1.0.1
	 *
	 * @return bool
	 */
	private function check_need_hide_renewal() {

		$need_hide = false;

		$user = wp_get_current_user();
		if ( $user && is_user_logged_in() ) {
			$couple_prime     = get_user_meta( $user->ID, 'couple_prime', true );
			$membership_level = pmpro_getMembershipLevelForUser( $user->ID );

			if ( empty( $membership_level->enddate ) && empty( $membership_level->cycle_period ) ) {
				$expiration_date = '+ 1 year';
			} else {
				$expiration_month = date_i18n( "m", $membership_level->enddate );
				$expiration_day   = date_i18n( "j", $membership_level->enddate );
				$expiration_year  = date_i18n( "Y", $membership_level->enddate );
				$expiration_date  = date( 'd M Y', strtotime( $expiration_day . '-' . $expiration_month . '-' . $expiration_year ) );
			}

			$need_hide = ( ! empty( $couple_prime ) && 'second' == strtolower( $couple_prime ) )
			             || ( strtotime( $expiration_date ) > strtotime( '+ 30 days' ) );
		}

		return $need_hide;
	}

	/**
	 * Check if we need to hide become a member button
	 *
	 * @since 1.0.1
	 *
	 * @return bool
	 */
	private function check_need_hide_become() {

		$need_hide = false;

		$user = wp_get_current_user();
		if ( $user ) {
			$couple_prime           = get_user_meta( $user->ID, 'couple_prime', true );
			$member_expiration_date = get_user_meta( $user->ID, 'member_expiration_date', true );
			$need_hide              = ( ! empty( $couple_prime ) && 'second' == strtolower( $couple_prime ) )
			                          || ( ! empty( $member_expiration_date ) );
		}

		return $need_hide;
	}

}

function pmpro_chapters_frontend_runner() {

	$frontend = new PMPRO_Chapters_Frontend;
	$frontend->init();

	return true;
}

pmpro_chapters_frontend_runner();