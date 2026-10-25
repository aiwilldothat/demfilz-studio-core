<?php
/**
 * Portfolio and Service post types, portfolio categories and form entries.
 *
 * @package Demfilz_Studio_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the post types and taxonomy.
 *
 * Names and meta keys are shared with the Demfilz Studio theme.
 *
 * @return void
 */
function dstudio_register_post_types() {
	register_post_type(
		'dstudio_portfolio',
		array(
			'labels'        => array(
				'name'               => __( 'Portfolio', 'demfilz-studio-core' ),
				'singular_name'      => __( 'Project', 'demfilz-studio-core' ),
				'add_new'            => __( 'Add Project', 'demfilz-studio-core' ),
				'add_new_item'       => __( 'Add New Project', 'demfilz-studio-core' ),
				'edit_item'          => __( 'Edit Project', 'demfilz-studio-core' ),
				'new_item'           => __( 'New Project', 'demfilz-studio-core' ),
				'view_item'          => __( 'View Project', 'demfilz-studio-core' ),
				'search_items'       => __( 'Search Projects', 'demfilz-studio-core' ),
				'not_found'          => __( 'No projects found.', 'demfilz-studio-core' ),
				'not_found_in_trash' => __( 'No projects found in Trash.', 'demfilz-studio-core' ),
				'all_items'          => __( 'All Projects', 'demfilz-studio-core' ),
			),
			'public'        => true,
			'has_archive'   => true,
			'menu_icon'     => 'dashicons-portfolio',
			'menu_position' => 20,
			'rewrite'       => array( 'slug' => 'portfolio' ),
			'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes' ),
			'show_in_rest'  => true,
		)
	);

	register_taxonomy(
		'dstudio_portfolio_cat',
		'dstudio_portfolio',
		array(
			'labels'            => array(
				'name'          => __( 'Project Categories', 'demfilz-studio-core' ),
				'singular_name' => __( 'Project Category', 'demfilz-studio-core' ),
				'search_items'  => __( 'Search Categories', 'demfilz-studio-core' ),
				'all_items'     => __( 'All Categories', 'demfilz-studio-core' ),
				'edit_item'     => __( 'Edit Category', 'demfilz-studio-core' ),
				'update_item'   => __( 'Update Category', 'demfilz-studio-core' ),
				'add_new_item'  => __( 'Add New Category', 'demfilz-studio-core' ),
				'new_item_name' => __( 'New Category Name', 'demfilz-studio-core' ),
			),
			'hierarchical'      => true,
			'public'            => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => 'portfolio-category' ),
		)
	);

	register_post_type(
		'dstudio_service',
		array(
			'labels'        => array(
				'name'               => __( 'Services', 'demfilz-studio-core' ),
				'singular_name'      => __( 'Service', 'demfilz-studio-core' ),
				'add_new'            => __( 'Add Service', 'demfilz-studio-core' ),
				'add_new_item'       => __( 'Add New Service', 'demfilz-studio-core' ),
				'edit_item'          => __( 'Edit Service', 'demfilz-studio-core' ),
				'new_item'           => __( 'New Service', 'demfilz-studio-core' ),
				'view_item'          => __( 'View Service', 'demfilz-studio-core' ),
				'search_items'       => __( 'Search Services', 'demfilz-studio-core' ),
				'not_found'          => __( 'No services found.', 'demfilz-studio-core' ),
				'not_found_in_trash' => __( 'No services found in Trash.', 'demfilz-studio-core' ),
				'all_items'          => __( 'All Services', 'demfilz-studio-core' ),
			),
			'public'        => true,
			'has_archive'   => true,
			'menu_icon'     => 'dashicons-lightbulb',
			'menu_position' => 21,
			'rewrite'       => array( 'slug' => 'services' ),
			'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes' ),
			'show_in_rest'  => true,
		)
	);

	register_post_type(
		'dstudio_entry',
		array(
			'labels'          => array(
				'name'          => __( 'Form Entries', 'demfilz-studio-core' ),
				'singular_name' => __( 'Form Entry', 'demfilz-studio-core' ),
				'edit_item'     => __( 'View Entry', 'demfilz-studio-core' ),
				'search_items'  => __( 'Search Entries', 'demfilz-studio-core' ),
				'not_found'     => __( 'No entries yet. Contact form submissions are stored here.', 'demfilz-studio-core' ),
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => true,
			'menu_icon'       => 'dashicons-email-alt2',
			'menu_position'   => 22,
			'supports'        => array( 'title' ),
			'capability_type' => 'post',
			'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
			'map_meta_cap'    => true,
		)
	);
}
add_action( 'init', 'dstudio_register_post_types' );

/**
 * Admin list columns for form entries.
 *
 * @param array $columns Columns.
 * @return array
 */
function dstudio_entry_columns( $columns ) {
	$date = isset( $columns['date'] ) ? $columns['date'] : __( 'Date', 'demfilz-studio-core' );

	return array(
		'cb'            => isset( $columns['cb'] ) ? $columns['cb'] : '',
		'title'         => __( 'From', 'demfilz-studio-core' ),
		'dstudio_email' => __( 'Email', 'demfilz-studio-core' ),
		'dstudio_phone' => __( 'Phone', 'demfilz-studio-core' ),
		'date'          => $date,
	);
}
add_filter( 'manage_dstudio_entry_posts_columns', 'dstudio_entry_columns' );

/**
 * Render the custom entry columns.
 *
 * @param string $column  Column key.
 * @param int    $post_id Post ID.
 * @return void
 */
function dstudio_entry_column_content( $column, $post_id ) {
	if ( 'dstudio_email' === $column ) {
		echo esc_html( (string) get_post_meta( $post_id, '_dstudio_email', true ) );
	} elseif ( 'dstudio_phone' === $column ) {
		echo esc_html( (string) get_post_meta( $post_id, '_dstudio_phone', true ) );
	}
}
add_action( 'manage_dstudio_entry_posts_custom_column', 'dstudio_entry_column_content', 10, 2 );
