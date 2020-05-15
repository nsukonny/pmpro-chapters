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

	}

	/**
	 * Includes all necessary PHP files
	 *
	 * @since 1.0.1
	 */
	private function includes() {

		if ( defined( 'PMPRO_CHAPTERS_LIBRARIES_PATH' ) ) {
			require PMPRO_CHAPTERS_LIBRARIES_PATH . '/frontend/chapters-list.php';
		}

	}

}

function pmpro_chapters_frontend_runner() {

	$frontend = new PMPRO_Chapters_Frontend;
	$frontend->init();

	return true;
}

pmpro_chapters_frontend_runner();