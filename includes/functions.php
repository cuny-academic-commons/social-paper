<?php
/**
 * Utility functions used by Social Paper
 *
 * @package Social_Paper
 * @subpackage Functions
 */

/**
 * Get post types that Social Paper should support.
 *
 * @return array
 */
function sp_get_supported_post_types() {
	/**
	 * Filters the post types that Social Paper should support.
	 *
	 * @param type array
	 */
	return apply_filters( 'sp_get_supported_post_types', array( 'cacsp_paper' ) );
}

/**
 * Locate templates used by Social Paper.
 *
 * Essentially a wrapper function for {@link locate_template()} but supports
 * our custom template directory.
 *
 * @see locate_template() for parameter documentation
 */
function sp_locate_template( $template_names, $load = false, $require_once = true ) {
	// check WP first
	$located = locate_template( $template_names, false );

	// fallback to bundled template on failure
	if ( empty( $located ) ) {
		$located = '';
		foreach ( (array) $template_names as $template_name ) {
			if ( ! $template_name ) {
				continue;
			}

			if ( file_exists( Social_Paper::$TEMPLATEPATH . '/' . $template_name ) ) {
				$located = Social_Paper::$TEMPLATEPATH . '/' . $template_name;
				break;
			}
		}
	}

	if ( true === (bool) $load && '' !== $located ) {
		load_template( $located, $require_once );
	} else {
		return $located;
	}
}

/**
 * Determine whether we're on a Social Paper page
 *
 * @return bool
 */
function sp_is_page() {
	return (bool) Social_Paper::$is_page;
}

if ( ! function_exists( 'wp_styles' ) ) :
/**
 * Abstraction of {@link wp_styles()} function
 *
 * Initialize $wp_styles if it has not been set.
 *
 * @global WP_Styles $wp_styles
 *
 * @since 4.2.0
 *
 * @return WP_Styles WP_Styles instance.
 */
function wp_styles() {
	global $wp_styles;
	if ( ! ( $wp_styles instanceof WP_Styles ) ) {
		$wp_styles = new WP_Styles();
	}
	return $wp_styles;
}
endif;