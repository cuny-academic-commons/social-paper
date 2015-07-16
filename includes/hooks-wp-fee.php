<?php
/**
 * Compatibility with WordPress Front-end Editor plugin
 *
 * @package Social_Paper
 * @subpackage Hooks
 */

// declare support
add_post_type_support( 'cacsp_paper', 'front-end-editor' );

/**
 * Add WP FEE support for our CPT
 *
 * @param bool $supports_fee Whether or not a CPT is supported by WP FEE
 * @param object $post The WordPress post object
 * @return bool
 */
function cacsp_wp_fee_content_type( $supports_fee, $post ) {
	if ( $post->post_type == 'cacsp_paper' ) {
		$supports_fee = true;
	}
	return $supports_fee;
}
add_filter( 'supports_fee', 'cacsp_wp_fee_content_type', 20, 2 );

/**
 * Add scripts so we can augment WP FEE's behaviour
 */
function cacsp_wp_fee_enqueue_scripts() {
	if ( ! cacsp_is_page() ) {
		return;
	}

	if ( is_user_logged_in() ) {

		// enqueue script
		wp_enqueue_script( 'social-paper-single-fee', Social_Paper::$URL . '/assets/js/hooks-wp-fee.js', array('jquery'), '0.1' );

		// localise
		wp_localize_script( 'social-paper-single-fee', 'Social_Paper_FEE_i18n', array(
			'button_enable' => __( 'Enable Editing', 'social-paper' ),
			'button_disable' => __( 'Disable Editing', 'social-paper' ),
		) );

	}

}
add_action( 'wp_enqueue_scripts', 'cacsp_wp_fee_enqueue_scripts', 999 );

/**
 * Prevent WP FEE from loading
 *
 * Utility that can be called before 'init' to suppress WP FEE from loading.
 * Will only work with a fork of WP FEE where PRs from @christianwach have been
 * merged.
 *
 * @see https://github.com/iseulde/wp-front-end-editor/pull/227
 * @see https://github.com/iseulde/wp-front-end-editor/pull/228
 * @see https://github.com/iseulde/wp-front-end-editor/pull/229
 * @see https://github.com/iseulde/wp-front-end-editor/pull/230
 */
function cacsp_wp_fee_suppress() {

	global $wordpress_front_end_editor;
	remove_action( 'init', array( $wordpress_front_end_editor, 'init' ) );

}
