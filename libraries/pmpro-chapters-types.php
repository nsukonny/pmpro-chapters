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
		add_action( 'init', array( $this, 'register_chapter_region_post_type' ), 10, 1 );
		add_filter( 'post_row_actions', array( $this, 'remove_row_actions' ), 10, 2 );

		add_filter( 'manage_chapters_posts_columns', array( $this, 'add_columns' ) );
		add_action( 'manage_chapters_posts_custom_column', array( $this, 'add_columns_values' ), 10, 2 );

		add_filter( 'manage_chapter_regions_posts_columns', array( $this, 'add_chapter_regions_columns' ) );
		add_action( 'manage_chapter_regions_posts_custom_column', array(
			$this,
			'add_chapter_regions_columns_values'
		), 10, 2 );

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
				'editor',
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
		);

		register_post_type( 'chapters', $args );

	}

	/**
	 * Register post type chapter region
	 *
	 * @since 1.0.0
	 */
	public function register_chapter_region_post_type() {

		if ( ! is_blog_installed() || post_type_exists( 'chapter_regions' ) ) {
			return;
		}

		$args = array(
			'labels'                => array(
				'name'               => 'Chapter Regions',
				'singular_name'      => 'Chapter Region',
				'add_new'            => 'Add New Chapter Region',
				'add_new_item'       => 'Add New Chapter Region',
				'edit_item'          => 'Edit Chapter Region',
				'new_item'           => 'Add New Chapter Region',
				'view_item'          => 'View Chapter Region',
				'search_item'        => 'Search Chapter Regions',
				'not_found'          => 'No Chapter Regions Found',
				'not_found_in_trash' => 'No Chapter Regions Found in Trash'
			),
			'query_var'             => 'chapter_regions',
			'rewrite'               => false,
			'public'                => true,
			'menu_position'         => 20,
			'menu_icon'             => 'dashicons-book',
			'supports'              => array(
				'title',
			),
			'show_in_rest'          => true,
			'rest_base'             => 'chapter_regions',
			'rest_controller_class' => 'WP_REST_Posts_Controller',
			'has_archive'           => true,
			'taxonomies'            => array(),
			'publicly_queryable'    => true,
			'exclude_from_search'   => false,
			'hierarchical'          => false,
			'show_ui'               => true,
			'show_in_menu'          => 'edit.php?post_type=chapters',
		);

		register_post_type( 'chapter_regions', $args );

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

		if ( $current_screen->post_type != 'chapters'
		     && $current_screen->post_type != 'chapter_regions' ) {
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
		$columns['chapter_users']   = __( 'Users' );
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
				$regions        = PMPRO_Chapters_Supports::get_regions();
				$chapter_region = get_post_meta( $post_id, 'chapter_region', true );

				if ( $regions ) {
					foreach ( $regions as $region ) {
						if ( $region->ID == $chapter_region ) {
							echo $region->post_title;
							break;
						}
					}
				}
				break;
			case 'chapter_country' :
				$countries = upme_country_value_list();
				$country   = get_post_meta( $post_id, 'chapter_country', true );

				echo isset( $countries[ $country ] ) ? $countries[ $country ] : '';
				break;
			case 'chapter_state' :
				$states = PMPRO_Chapters_Supports::get_states();
				$state  = get_post_meta( $post_id, 'chapter_state', true );

				echo isset( $states[ $state ] ) ? $states[ $state ] : '';
				break;
			case 'chapter_closed' :
				$closed = get_post_meta( $post_id, 'chapter_closed', true );

				echo 'yes' == $closed ? 'Yes' : 'No';
				break;
			case 'chapter_users' :
				$args = array(
					'meta_query'  => array(
						array(
							'key'     => 'chapter_id',
							'value'   => $post_id,
							'compare' => '=',
						)
					),
					'count_total' => true
				);

				$users = new WP_User_Query( $args );

				$link = admin_url( 'users.php?filter_chapter_id_top=' . $post_id, 'https' );
				echo '<a href="' . esc_url( $link ) . '" >' . esc_attr( $users->get_total() ) . '</a>';
				break;
			case 'chapter_id' :
				$link = admin_url( 'admin.php?page=chapter_detail&chapter_id=' . $post_id, 'https' );
				echo '<a href="' . esc_url( $link ) . '" >' . esc_attr( $post_id ) . '</a>';
				break;
			default:
		}

	}

	/**
	 * Add columns in chapters regions table
	 *
	 * @since 1.0.1
	 *
	 * @param $columns
	 *
	 * @return mixed
	 */
	public function add_chapter_regions_columns( $columns ) {

		unset( $columns['title'] );
		unset( $columns['date'] );

		$columns['chapter_region_id'] = __( 'Chapter region ID' );
		$columns['title']             = __( 'Chapter Name' );
		$columns['date']              = __( 'Date' );

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
	public function add_chapter_regions_columns_values( $column, $post_id ) {

		switch ( $column ) {
			case 'chapter_region_id' :
				$link = admin_url( 'post-new.php?post_type=chapter_regions', 'https' );
				echo '<a href="' . esc_url( $link ) . '" >' . esc_attr( $post_id ) . '</a>';
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