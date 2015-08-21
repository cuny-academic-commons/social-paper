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

	return '.entry-content p:visible';
}
add_filter( 'option_multiselector', 'cacsp_ic_modify_selector' );

/**
 * Disables the cancel 'x' button in IC on Social Paper single pages.
 *
 * @param  string $retval Current setting.
 * @return string
 */
function cacsp_ic_disable_x_button( $retval ) {
	if ( false === cacsp_is_page() ) {
		return $retval;
	}

	return '1';
}
add_filter( 'option_cancel_x', 'cacsp_ic_disable_x_button' );

/**
 * Load the WP Ajaxify Comments (WPAC) module for IC on Social Paper pages.
 *
 * WP Ajaxify Comments has its own settings routine, which bypasses WP's
 * Options API.  As a result, we cannot rely on WP filters.  So in order to
 * load IC's WPAC settings over WPAC's settings, we need to manually merge
 * IC's settings to the $wpac_options global before WPAC writes its inline JS
 * on the 'wp_head' hook.  Sigh...
 *
 * @see wpac_initialize()
 */
function cacsp_ic_load_wpac_options() {
	// if not on a Social Paper single page, bail!
	if ( false === cacsp_is_page() ) {
		return;
	}

	// check if WPAC exists; if not, bail!
	if ( false === function_exists( 'wpac_get_config' ) ) {
		return;
	}

	// check if IC's WPAC module is already present, if not load it!
	if ( false === function_exists( 'filter_options_wpac' ) ) {
		require_once INCOM_PATH . 'frontend/inc/class-wpac.php';
	}

	// magic time!
	$GLOBALS['wpac_options'] = array_merge( $GLOBALS['wpac_options'], get_option( constant( 'WPAC_OPTION_KEY' ) ) );
}
add_action( 'wp_head', 'cacsp_ic_load_wpac_options', 9 );

/** COMMENT OVERRIDES *******************************************************/

/**
 * Set comment type for Inline Comments to 'incom' during comment saving.
 *
 * @param  array $retval Comment data.
 * @return array
 */
function cacsp_ic_change_comment_type( $retval ) {
	// not an Inline Comment
	if ( false === isset( $_POST[ 'data_incom' ] ) ) {
		// see if there is a comment parent
		$comment_parent = isset( $retval['comment_parent'] ) ? absint( $retval['comment_parent'] ) : 0;

		// check if comment parent is an inline comment; if not, bail!
		if ( ! empty( $comment_parent ) ) {
			if ( 'incom' !== get_comment( $comment_parent )->comment_type ) {
				return $retval;
			}

		// no parent, bail!
		} else {
			return $retval;
		}
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
 * Alters the inline comment permalink to add the paragraph number.
 *
 * @param string $retval  Current comment permalink
 * @param object $comment WP Comment object
 */
function cacsp_ic_alter_comment_permalink( $retval, $comment ) {
	if ( 'incom' !== $comment->comment_type ) {
		return $retval;
	}

	// grab the top-parent comment ID
	$parent = empty( $comment->comment_parent ) ? $comment->comment_ID : $comment->comment_parent;
	$comment_parent = get_comment( $comment->comment_parent );

	while ( 0 !== (int) $comment_parent->comment_parent ) {
		$c = get_comment( $comment_parent );
		$parent = $c->comment_parent;
		$comment_parent = get_comment( $c->comment_parent );
	}

	// now re-generate comment permalink
	$para = get_comment_meta( $parent, 'data_incom', true );

	$permalink = get_post_permalink( $comment->comment_post_ID );
	$permalink = add_query_arg( 'para', $para, $permalink );
	$permalink .= "#comment-{$comment->comment_ID}";

	return $permalink;
}
add_filter( 'get_comment_link', 'cacsp_ic_alter_comment_permalink', 10, 2 );

/**
 * Inline JS to display the inline comment permalink at its rightful place.
 *
 * Relies on the "?para" URL parameter and the arrive.js library.  Arrive.js
 * watches for DOM element injections and allows us to hook in when IC adds
 * its comment elements.
 *
 * @see cacsp_ic_alter_comment_permalink()
 * @see https://github.com/uzairfarooq/arrive
 */
function cacsp_ic_comment_permalink_listener() {
	if ( false === cacsp_is_page() ) {
		return;
	}

?>

	<script type="text/javascript" src="//cdn.rawgit.com/uzairfarooq/arrive/master/minified/arrive.min.js"></script>
	<script type="text/javascript">
	jQuery(function(){
		var para = window.location.search.split('=')[1];

		if ( null === para ) {
			return;
		}

		jQuery(document).arrive('[data-incom-bubble=' + para + ']', function() {
			// manually trigger click so IC will display the comment tree
			jQuery(this).click();

			// unbind arrive
			jQuery(document).unbindArrive();
		});
	});
	</script>

<?php
}
add_action( 'wp_footer', 'cacsp_ic_comment_permalink_listener' );

/**
 * Disable Inline Comments on various pages.
 *
 * Currently disabled on:
 * - BuddyPress pages
 * - Social Paper archive page
 */
function cacsp_ic_disable() {
	$disable = false;

	if ( ( function_exists( 'is_buddypress' ) && true === is_buddypress() ) || true === cacsp_is_archive() ) {
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
add_action( 'wp', 'cacsp_ic_disable' );