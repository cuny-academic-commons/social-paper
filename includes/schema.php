<?php

/**
 * CAC Social Paper schema.
 *
 * Defines post types, taxonomies, and other data types.
 *
 * @since 1.0
 */

add_action( 'init', 'cacsp_register_post_types' );
add_action( 'init', 'cacsp_register_taxonomies', 15 );

/**
 * Register post types.
 *
 * @since 1.0
 */
function cacsp_register_post_types() {
	$labels = array(
		'name'               => _x( 'Papers', 'papers general name', 'cac-social-paper' ),
		'singular_name'      => _x( 'Paper', 'papers singular name', 'cac-social-paper' ),
		'add_new'            => _x( 'Add New', 'paper', 'cac-social-paper' ),
		'add_new'            => __( 'Add New Paper', 'cac-social-paper' ),
		'edit_item'          => __( 'Edit Paper', 'cac-social-paper' ),
		'new_item'           => __( 'New Paper', 'cac-social-paper' ),
		'view_item'          => __( 'View Paper', 'cac-social-paper' ),
		'search_items'       => __( 'Search Papers', 'cac-social-paper' ),
		'not_found'          => __( 'No papers found.', 'cac-social-paper' ),
		'not_found_in_trash' => __( 'No papers found in Trash.', 'cac-social-paper' ),
		'all_items'          => __( 'All Papers', 'cac-social-paper' ),
	);

	register_post_type( 'cacsp_paper', array(
		'labels' => $labels,
		'public' => true,
		'rewrite' => array(
			'slug' => 'papers',
			'with_front' => false,
		),
		'supports' => array( 'title', 'editor', 'comments' ),
		'has_archive' => true
	) );
}

/**
 * Register taxonomies.
 *
 * @since 1.0
 */
function cacsp_register_taxonomies() {
	// Paper tags.
	register_taxonomy( 'cacsp_paper_tag', 'cacsp_paper', array(
		'hierarchical' => false,
		'rewrite' => array(
			'with_front' => false,
			'slug' => 'tag',
		),
	) );
}
