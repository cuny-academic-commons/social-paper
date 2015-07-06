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
function cacsp_single_template_loader( $retval = '' ) {
	if ( ! cacsp_is_page() ) {
		return $retval;
	}

	$post = get_queried_object();

	// locate template
	$new_template = cacsp_locate_template( array(
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
		return apply_filters( 'cacsp_single_template', $new_template );
	}

	return $retval;
}
add_filter( 'single_template', 'cacsp_single_template_loader' );
add_filter( 'page_template',   'cacsp_single_template_loader' );

/**
 * Comments template loader.
 *
 * Overrides the theme's comments template with our bundled one only on single
 * Social Paper pages.
 *
 * @param  string $retval Absolute path to found template or empty string.
 * @return string
 */
function cacsp_comments_template_loader( $retval = '' ) {
	if ( ! cacsp_is_page() ) {
		return $retval;
	}

	$post = get_queried_object();

	// locate template
	$new_template = cacsp_locate_template( array(
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
		return apply_filters( 'cacsp_comments_template', $new_template );
	}

	return $retval;
}
add_filter( 'comments_template', 'cacsp_comments_template_loader' );

function cacsp_create_template_loader( $retval = '' ) {
	$requested_uri = $_SERVER['REQUEST_URI'];
	$requested_uri_clean = trim( $requested_uri, '/' );
	$requested_uri_parts = explode( '/', $requested_uri_clean );

	if ( $requested_uri_parts[1] != 'new' ) {
		return;
	}

	$post_types = (array) cacsp_get_supported_post_types();
	$create_post_type = '';

	foreach( $post_types as $post_type ) {
		$post_type_object = get_post_type_object( $post_type );
		$post_type_slug = $post_type_object->rewrite['slug'];
		if ( $requested_uri_parts[0] === $post_type_slug ) {
			$new_post_type = $post_type;
		}
	}

	if ( ! empty( $new_post_type ) ) {
		$new_template = cacsp_locate_template( array( 'new-social-paper.php' ), false );

		if ( ! empty( $new_template ) ) {
			/**
		 	* Filters the located new paper template for the create Social Paper page.
		 	*
		 	* @param type string
		 	*/
			return apply_filters( 'cacsp_create_template', $new_template );
		}
	}
}
add_filter( 'template_include', 'cacsp_create_template_loader' );

/**
 * Asset enqueue handler on single social paper pages.
 *
 * Removes all styles except the most pertinent ones.
 *
 * @todo maybe do the same for scripts?
 */
function cacsp_asset_enqueue_handler() {
	if ( ! cacsp_is_page() ) {
		return;
	}

	$styles = wp_styles();

	// wipe out all styles and only enqueue the ones we need
	$styles->queue = array(
		'social-paper-single',

		// wp-side-comments
		'side-comments-style',
		'side-comments-theme',

		// inline-comments
		'incom-style'
	);

	// enqueue our styles
	wp_enqueue_style( 'social-paper-single', Social_Paper::$URL . '/assets/css/single.css' );
}
add_action( 'wp_enqueue_scripts', 'cacsp_asset_enqueue_handler', 999 );

/**
 * Set our page markers to determine if we're on a Social Paper page.
 *
 * @access private
 *
 * @param WP_Query $q
 */
function _cacsp_set_markers( $q ) {
	// bail if this is not the main query
	if ( false === $q->is_main_query() ) {
		return;
	}

	$post_type = isset( $q->query['post_type'] ) ? $q->query['post_type'] : '';

	if ( empty( $post_type ) ) {
		if ( $q->is_page ) {
			$post_type = 'page';

		// kind of a catch-all... we don't support the 'attachment' post type
		} else {
			$post_type = 'post';
		}
	}

	// check to see if we support this post type
	if ( false === in_array( $post_type, (array) cacsp_get_supported_post_types(), true ) ) {
		return;
	}

	// set our markers
	if ( $q->is_archive && 'cacsp_paper' === $post_type ) {
		Social_Paper::$is_archive = true;

	} elseif ( $q->is_singular ) {
		Social_Paper::$is_page = true;
	}
}
add_action( 'pre_get_posts', '_cacsp_set_markers' );

/**
 * Disables the admin bar on single Social Paper pages.
 *
 * Might bring it back later...
 *
 * @access private
 */
function _cacsp_disable_admin_bar_on_social_paper_pages() {
	if ( ! cacsp_is_page() ) {
		return;
	}

	show_admin_bar( false );
}
add_action( 'admin_bar_init', '_cacsp_disable_admin_bar_on_social_paper_pages', 1 );

/**
 * Start the buffer for content replacement on the Social Paper archive page.
 *
 * @access private
 *
 * @param WP_Query $q
 */
function _cacsp_archive_ob_start( $q ) {
	if ( false === cacsp_is_archive() ) {
		return;
	}

	if ( true === Social_Paper::$is_buffer ) {
		return;
	}

	Social_Paper::$is_buffer = true;

	remove_action( 'loop_start', '_cacsp_archive_ob_start', -999 );

	ob_start();
}
add_action( 'loop_start', '_cacsp_archive_ob_start', -999 );

/**
 * Clears the buffer for content replacement on the Social Paper archive page.
 *
 * This is also where we call our custom directory template.
 *
 * @access private
 *
 * @param WP_Query $q
 */
function _cacsp_archive_ob_end( $q ) {
	if ( false === Social_Paper::$is_buffer ) {
		return;
	}

	if ( false === cacsp_is_archive() ) {
		return;
	}

	ob_end_clean();

	remove_action( 'loop_end', '_cacsp_archive_ob_end', 999 );

	$q->rewind_posts();

	cacsp_locate_template( 'content-directory-social-paper.php', true );
}
add_action( 'loop_end', '_cacsp_archive_ob_end', 999 );