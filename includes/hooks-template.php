<?php
/**
 * Template hook integration into WordPress
 *
 * @package Social_Paper
 * @subpackage Hooks
 */

/**
 * Single template loader.
 *
 * Overrides the single post template in themes to use our bundled template.
 *
 * @param  string $retval Absolute path to found template or empty string.
 * @return string
 */
function sp_single_template_loader( $retval = '' ) {
	if ( ! sp_is_page() ) {
		return $retval;
	}

	$post = get_queried_object();

	// locate template
	$new_template = sp_locate_template( array(
		// maybe open the doors to post types having different templates?
		//"single-social-paper-{$post->post_type}.php"
		'single-social-paper.php'
	), false );

	// if it exists, use it!
	if ( ! empty( $new_template ) ) {
		/**
		 * Filters the located template for the single Social Paper page.
		 *
		 * @param type string
		 */
		return apply_filters( 'sp_single_template', $new_template );
	}

	return $retval;
}
add_filter( 'single_template', 'sp_single_template_loader' );

/**
 * Comments template loader.
 *
 * Overrides the theme's comments template with our bundled one only on single
 * Social Paper pages.
 *
 * @param  string $retval Absolute path to found template or empty string.
 * @return string
 */
function sp_comments_template_loader( $retval = '' ) {
	if ( ! sp_is_page() ) {
		return $retval;
	}

	$post = get_queried_object();

	// locate template
	$new_template = sp_locate_template( array(
		// maybe open the doors to post types having different templates?
		//"comments-social-paper-{$post->post_type}.php"
		'comments-social-paper.php'
	), false );

	// if it exists, use it!
	if ( ! empty( $new_template ) ) {
		/**
		 * Filters the located comments template for the single Social Paper page.
		 *
		 * @param type string
		 */
		return apply_filters( 'sp_comments_template', $new_template );
	}

	return $retval;
}
add_filter( 'comments_template', 'sp_comments_template_loader' );

/**
 * Asset enqueue handler on single social paper pages.
 *
 * Removes all styles except the most pertinent ones.
 *
 * @todo maybe do the same for scripts?
 */
function sp_asset_enqueue_handler() {
	if ( ! sp_is_page() ) {
		return;
	}

	$styles = wp_styles();

	// wipe out all styles and only enqueue the ones we need
	$styles->queue = array(
		'social-paper-single',

		// wp-side-comments
		'side-comments-style',
		'side-comments-theme'
	);

	// enqueue our styles
	wp_enqueue_style( 'social-paper-single', Social_Paper::$URL . '/assets/css/single.css' );
}
add_action( 'wp_enqueue_scripts', 'sp_asset_enqueue_handler', 999 );

/**
 * Set our page marker to determine if we're on a Social Paper page.
 *
 * @access private
 */
function _sp_set_page_marker() {
	// bail if not on a single page
	if ( ! is_single() ) {
		return;
	}

	$post = get_queried_object();

	// check to see if we support this post type
	if ( false === in_array( $post->post_type, (array) sp_get_supported_post_types(), true ) ) {
		return;
	}

	// set our marker
	Social_Paper::$is_page = true;
}
add_action( 'wp', '_sp_set_page_marker', 0 );

/**
 * Disables the admin bar on single Social Paper pages.
 *
 * Might bring it back later...
 *
 * @access private
 */
function _sp_disable_admin_bar_on_social_paper_pages() {
	if ( ! sp_is_page() ) {
		return;
	}

	show_admin_bar( false );
}
add_action( 'admin_bar_init', '_sp_disable_admin_bar_on_social_paper_pages', 1 );