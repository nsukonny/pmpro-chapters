<?php
/**
 * Class PMPRO_Chapters_Types
 * Add new post types
 *
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class PMPRO_Chapters_Types {

	/**
	 * PMPRO_Chapters_Types initialization class.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		add_action( 'init', array( $this, 'register_chapters_post_type' ), 10, 1 );
		add_filter( 'post_row_actions', array( $this, 'remove_row_actions' ), 10, 2 );

		add_filter( 'manage_chapters_posts_columns', array( $this, 'add_columns' ) );
		add_action( 'manage_chapters_posts_custom_column', array( $this, 'add_columns_values' ), 10, 2 );

	}

	/**
	 * Register new post type chapters
	 *
	 * @since 1.0.0
	 */
	public function register_chapters_post_type() {

		if ( ! is_blog_installed() || post_type_exists( 'chapters' ) ) {
			return;
		}

		$args = array(
			'labels'                => array(
				'name'               => 'Chapters',
				'singular_name'      => 'Chapter',
				'add_new'            => 'Add New Chapter',
				'add_new_item'       => 'Add New Chapter',
				'edit_item'          => 'Edit Chapter',
				'new_item'           => 'Add New Chapter',
				'view_item'          => 'View Chapter',
				'search_item'        => 'Search Chapters',
				'not_found'          => 'No Chapters Found',
				'not_found_in_trash' => 'No Chapters Found in Trash'
			),
			'query_var'             => 'chapters',
			'rewrite'               => false,
			'public'                => false,
			'menu_position'         => 20,
			'menu_icon'             => 'dashicons-book',
			'supports'              => array(
				'title',
			),
			'show_in_rest'          => true,
			'rest_base'             => 'chapters',
			'rest_controller_class' => 'WP_REST_Posts_Controller',
			'has_archive'           => true,
			'taxonomies'            => array(),
			'publicly_queryable'    => true,
			'exclude_from_search'   => false,
			'hierarchical'          => false,
			'show_ui'               => true,
			'show_in_menu'          => 'pmpro-dashboard',
		);

		register_post_type( 'chapters', $args );

	}

	/**
	 * Remove no need actions
	 *
	 * @since 1.0.0
	 *
	 * @param $actions
	 * @param $post
	 *
	 * @return mixed
	 */
	public function remove_row_actions( $actions, $post ) {
		global $current_screen;

		if ( $current_screen->post_type != 'chapters' ) {
			return $actions;
		}

		unset( $actions['view'] );
		unset( $actions['inline hide-if-no-js'] );
		unset( $actions['duplicate'] );

		return $actions;
	}

	/**
	 * Add columns in chapters table
	 *
	 * @since 1.0.0
	 *
	 * @param $columns
	 *
	 * @return mixed
	 */
	public function add_columns( $columns ) {

		unset( $columns['title'] );
		unset( $columns['date'] );

		$columns['chapter_id']      = __( 'Chapter ID' );
		$columns['chapter_region']  = __( 'Region' );
		$columns['chapter_country'] = __( 'Country' );
		$columns['chapter_state']   = __( 'State' );
		$columns['title']           = __( 'Chapter Name' );
		$columns['chapter_closed']  = __( 'Closed' );

		return $columns;
	}

	/**
	 * Add value for new columns
	 *
	 * @since 1.0.0
	 *
	 * @param $column
	 * @param $post_id
	 *
	 * @return mixed
	 */
	public function add_columns_values( $column, $post_id ) {

		switch ( $column ) {
			case 'chapter_region' :
				$regions = PMPRO_Chapters_Metaboxes::get_regions();
				$region  = get_post_meta( $post_id, 'chapter_region', true );

				echo isset( $regions[ $region ] ) ? $regions[ $region ] : '';
				break;
			case 'chapter_country' :
				$countries = upme_country_value_list();
				$country   = get_post_meta( $post_id, 'chapter_country', true );

				echo isset( $countries[ $country ] ) ? $countries[ $country ] : '';
				break;
			case 'chapter_state' :
				$states = PMPRO_Chapters_Metaboxes::get_states();
				$state  = get_post_meta( $post_id, 'chapter_state', true );

				echo isset( $states[ $state ] ) ? $states[ $state ] : '';
				break;
			case 'chapter_closed' :
				$closed = get_post_meta( $post_id, 'chapter_closed', true );

				echo 'yes' == $closed ? 'Yes' : 'No';
				break;
			case 'chapter_id' :
				echo $post_id;
				break;
			default:
		}

	}

}

function pmpro_chapters_types_runner() {

	$frontend = new PMPRO_Chapters_Types;
	$frontend->init();

	return true;
}

pmpro_chapters_types_runner();