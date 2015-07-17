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
 * Add WP FEE message support for our CPT
 *
 * @param array $messages The existing message array
 * @param array $messages The modified message array
 */
function cacsp_wp_fee_messages( $messages ) {

	/**
	 * We have to declare access to the post global because the WP FEE filter
	 * does not pass $post or $revision_id along with the message array.
	 *
	 * @see FEE::post_updated_messages()
	 */
	global $post;

	// sanity check
	if ( ! isset( $post ) ) {
		return $messages;
	}

	$messages['cacsp_paper'] = array(
		 0 => '', // Unused. Messages start at index 1.
		 1 => __( 'Paper updated.', 'social-paper' ),
		 2 => __( 'Custom field updated.', 'social-paper' ),
		 3 => __( 'Custom field deleted.', 'social-paper' ),
		 4 => __( 'Paper updated.', 'social-paper' ),
		 5 => isset( $revision_id ) ? sprintf( __( 'Paper restored to revision from %s', 'social-paper' ), wp_post_revision_title( (int) $revision_id, false ) ) : false,
		 6 => __( 'Paper published.', 'social-paper' ),
		 7 => __( 'Paper saved.', 'social-paper' ),
		 8 => __( 'Paper submitted.', 'social-paper' ),
		 9 => sprintf( __( 'Paper scheduled for: <strong>%1$s</strong>.', 'social-paper' ), date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ) ),
		10 => __( 'Paper draft updated.', 'social-paper' )
	);

	return $messages;
}
add_filter( 'post_updated_messages', 'cacsp_wp_fee_messages' );

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
