<?php
/**
 * Class PMPRO_Chapters_Taxonomies
 * Add new post types
 *
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class PMPRO_Chapters_Taxonomies {

	/**
	 * PMPRO_Chapters_Taxonomies initialization class.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		add_action( 'init', array( $this, 'register_chapters_post_type' ), 10, 1 );
		add_filter( 'post_row_actions', array( $this, 'remove_row_actions' ), 10, 2 );

	}

	/**
	 *
	 * Define Workshop Post Type Taxonomies
	 *
	 */
	public function add_taxonomy(){

		if ( ! is_blog_installed() ) {
			return;
		}

		if ( taxonomy_exists( 'chapter_region' ) ) {
			return;
		}

		$chapter_taxonomies = array();

		$chapter_taxonomies['chapter_region'] = array(
			'hierarchical' => false,
			'query_var' => 'chapter_region',
			'has_archive' => true,
			'show_in_rest' => true,
			'rest_base' => 'chapter_region',
			'rest_controller_class' => 'WP_REST_Terms_Controller',
			'rewrite' => array(
				'slug' => 'chapter-region',
				'with_front' => true,
				'pages'      => true,
				'feeds'      => true
			),
			'labels' => array(
				'name' => 'Chapter Region',
				'singular_name' => 'Chapter Region',
				'add_new' => 'Add Chapter Region',
				'add_new_item' => 'Add New Chapter Region',
				'edit_item' => 'Edit Chapter Region',
				'new_item' => 'Add New Chapter Region',
				'view_item' => 'View Chapter Region',
				'search_item' => 'Search Chapter Region',
				'not_found' => 'No Chapter Region Found',
				'not_found_in_trash' => 'No Chapter Region Found in Trash'
			)
		);


		$this->register_chapter_taxonomies($chapter_taxonomies);
	}

}

function pmpro_chapters_taxonomies_runner() {

	$frontend = new PMPRO_Chapters_Taxonomies;
	$frontend->init();

	return true;
}

pmpro_chapters_taxonomies_runner();