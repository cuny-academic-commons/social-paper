<?php
/**
 * Inline Comments plugin integration into Social Paper.
 *
 * @package Social_Paper
 * @subpackage Hooks
 */

/** SETTINGS OVERRIDES ***************************************************/

/**
 * Enable inline replies in IC on Social Paper single pages.
 *
 * @param  string $retval Current setting.
 * @return string
 */
function cacsp_ic_enable_inline_replies( $retval ) {
	if ( false === cacsp_is_page() ) {
		return $retval;
	}

	return 1;
}
add_filter( 'option_incom_reply', 'cacsp_ic_enable_inline_replies' );

/**
 * Modify selector for Inline Comments when on Social Paper single pages.
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

/** COMMENT OVERRIDES *******************************************************/

/**
 * Set comment type for Inline Comments to 'incom' during comment saving.
 *
 * @param  array $retval Comment data.
 * @return array
 */
function cacsp_ic_change_comment_type( $retval ) {
	// if not an Inline Comment, stop now!
	if ( false === isset( $_POST[ 'data_incom' ] ) ) {
		return $retval;
	}

	$retval['comment_type'] = 'incom';
	return $retval;
}
add_filter( 'preprocess_comment', 'cacsp_ic_change_comment_type', 999 );

/**
 * Fetch Inline Comments with the 'incom' comment type.
 *
 * Since we've altered IC to change the comment type, we now need to tell IC
 * to fetch comments with this new comment type.
 *
 * @param  array $retval Current comment list args.
 * @return array
 */
function cacsp_ic_alter_comments_list_args( $retval ) {
	if ( false === cacsp_is_page() ) {
		return $retval;
	}

	$retval['type'] = 'incom';
	return $retval;
}
add_filter( 'incom_comments_list_args', 'cacsp_ic_alter_comments_list_args' );

/**
 * Remove inline comments from the main comment loop on a Social Paper page.
 *
 * By default, all comment types are shown in the main comment loop on a
 * Social Paper page.  We do not want inline comments shown here, so we
 * restrict the main comment loop to show only main comments.
 *
 * At the moment, this also means that trackbacks and pingbacks will not
 * display... might need to create a new block solely for these types.
 *
 * @param  array $retval Current comment list args.
 * @return array
 */
function cacsp_ic_remove_inline_comments_from_main_comment_loop( $retval ) {
	if ( false === cacsp_is_page() ) {
		return $retval;
	}

	if ( ! empty( $retval['type'] ) && 'all' !== $retval['type'] ) {
		return $retval;
	}

	$retval['type'] = 'comment';
	return $retval;
}
add_filter( 'wp_list_comments_args', 'cacsp_ic_remove_inline_comments_from_main_comment_loop' );

/**
 * Add back 'comment' CSS class to Inline Comments.
 *
 * Since we've altered IC to change the comment type, the CSS class changed
 * from 'comment' to 'incom'.  IC's JS relies on the comment having the
 * 'comment' CSS class for toggling to work.
 *
 * @param  array $retval Current comment classes.
 * @return array
 */
function cacsp_ic_add_comment_class( $retval ) {
	if ( false === cacsp_is_page() ) {
		return $retval;
	}

	// add back the 'comment' CSS class
	$retval[] = 'comment';
	return $retval;
}
add_filter( 'comment_class', 'cacsp_ic_add_comment_class' );

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