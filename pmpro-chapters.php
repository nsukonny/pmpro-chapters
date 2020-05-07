<?php
/**
 * Plugin Name: Paid Memberships Pro - Chapters Add On
 * Plugin URI: https://geocosmic.org
 * Description: Add chapters for Paid Memberships Pro members or WP users.
 * Version: 1.0.0
 * Author: Dmwds.com
 * Author URI: https://www.dmwds.com/
 * Text Domain: pmpro-chapters
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'PMPRO_Chapters' ) ) {

	include_once dirname( __FILE__ ) . '/libraries/pmpro-chapters.php';

}

/**
 * The main function for returning PMPRO_Chapters instance
 *
 * @since 1.0.0
 *
 * @return object The one and only true PMPRO_Chapters instance.
 */
function pmpro_chapters_runner() {

	return PMPRO_Chapters::instance();
}

pmpro_chapters_runner();