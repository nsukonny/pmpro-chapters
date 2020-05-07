<?php
/**
 * Class PMPRO_Chapters_Frontend
 * Init all methods for work chapters
 *
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class PMPRO_Chapters_Frontend {

	/**
	 * PMPRO_Chapters_Frontend initialization class.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		add_action( 'gform_after_submission', array( $this, 'catch_chapter_members' ), 10, 3 );

	}

	/**
	 * Get data from gravity form and check to chapter users
	 *
	 * @since 1.0.0
	 *
	 * @param $lead
	 * @param $form
	 */
	public function catch_chapter_members( $lead, $form ) {

		/*
		echo '<pre>';
		print_r( $lead );

		print_r( $form );
		echo '</pre>';
		wp_die();
		*/

	}

}

function pmpro_chapters_frontend_runner() {

	$frontend = new PMPRO_Chapters_Frontend;
	$frontend->init();

	return true;
}

pmpro_chapters_frontend_runner();