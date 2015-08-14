<?php
/**
 * Inline Comments plugin integration into Social Paper.
 *
 * @package Social_Paper
 * @subpackage Hooks
 */

/**
 * Modify selector for Inline Comments when on our Social Paper single page.
 *
 * @param  string $retval Current CSS selector.
 * @return string
 */
function cacsp_ic_modify_selector( $retval ) {
	if ( false === cacsp_is_page() ) {
		return $retval;
	}

	return '.entry-content p';
}
add_filter( 'option_multiselector', 'cacsp_ic_modify_selector' );

/**
 * Disable Inline Comments on various pages.
 *
 * Currently disabled on:
 * - BuddyPress pages
 * - Social Paper archive page
 */
function cacsp_ic_disable() {
	$disable = false;

	if ( true === is_buddypress() || true === cacsp_is_archive() ) {
		$disable = true;
	}

	if ( false === $disable ) {
		return;
	}

	/**
	 * Since IC runs their code on 'init', we have to remove each hook
	 * individually on 'wp' ('bp_ready') using our utility function,
	 * {@link remove_anonymous_object_filter()} to do so.
	 *
	 * @see initialize_incom_wp()
	 * @see initialize_incom_comments()
	 */
	remove_anonymous_object_filter( 'wp_enqueue_scripts', 'INCOM_WordPress', 'incom_enqueue_scripts' );
	remove_anonymous_object_filter( 'wp_footer',          'INCOM_WordPress', 'load_incom' );
	remove_anonymous_object_filter( 'wp_enqueue_scripts', 'INCOM_WordPress', 'load_incom_style' );
	remove_anonymous_object_filter( 'wp_head',            'INCOM_WordPress', 'load_custom_css' );

	remove_anonymous_object_filter( 'wp_footer',          'INCOM_Comments',  'generateCommentsAndForm' );
}
add_action( 'bp_ready', 'cacsp_ic_disable' );