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
add_filter( 'default_option_incom_reply', 'cacsp_ic_enable_inline_replies' );

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

	return apply_filters( 'cacsp_ic_modify_selector', 'article:not(.post-password-required) .entry-content p:visible' );
}
add_filter( 'option_multiselector', 'cacsp_ic_modify_selector' );
add_filter( 'default_option_multiselector', 'cacsp_ic_modify_selector' );

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
add_filter( 'default_option_cancel_x', 'cacsp_ic_disable_x_button' );

/**
 * Enable permalink icons in IC on Social Paper single pages.
 *
 * @param  string $retval Current setting.
 * @return string
 */
function cacsp_ic_enable_permalink_icons( $retval ) {
	if ( false === cacsp_is_page() ) {
		return $retval;
	}


	return '1';
}
add_filter( 'option_incom_comment_permalink', 'cacsp_ic_enable_permalink_icons' );
add_filter( 'default_option_incom_comment_permalink', 'cacsp_ic_enable_permalink_icons' );

/**
 * Disable references in IC on Social Paper pages.
 *
 * @param  string $retval Current setting.
 * @return string
 */
function cacsp_ic_disable_references( $retval ) {
	if ( false === cacsp_is_page() ) {
		return $retval;
	}

	return 'nowhere';
}
add_filter( 'option_incom_references', 'cacsp_ic_disable_references' );
add_filter( 'default_option_incom_references', 'cacsp_ic_disable_references' );

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

	// Some special mods to WP Ajaxify Comments for IC
	add_filter( 'option_wpac', 'cacsp_ic_wpac_extras', 20 );

	// magic time!
	$GLOBALS['wpac_options'] = array_merge( $GLOBALS['wpac_options'], get_option( constant( 'WPAC_OPTION_KEY' ) ) );
}
add_action( 'wp_head', 'cacsp_ic_load_wpac_options', 9 );

/**
 * Mods to WP Ajaxify Comments for better UX.
 *
 * Mods include:
 * - Update comment timestamp position.  Comment timestamp is a custom addition
 *   that needs special placement after AJAX is made. See cacsp_comment_text().
 */
function cacsp_ic_wpac_extras( $wpacOptions ) {
	$wpacOptions = is_array( $wpacOptions ) ? $wpacOptions : array();

	if ( isset( $wpacOptions['callbackOnAfterUpdateComments'] ) ) {
		$wpacOptions['callbackOnAfterUpdateComments'] .= '
			// Update comment timestamp position
			jQuery(".comment-time").each(function() {
				jQuery(this).closest(".comment-body").find(".comment-author cite").append(this);
			});
		';
	}

	return $wpacOptions;
}

/** COMMENT OVERRIDES *******************************************************/

/**
 * Registers our custom comment type with WP's avatar comment types.
 *
 * This allows avatars to display for our inline comments.
 *
 * @param  array $retval Registered comment types
 * @return array
 */
function cacsp_ic_register_avatar_comment_type( $retval = array() ) {
	$retval[] = 'incom';
	return $retval;
}
add_filter( 'get_avatar_comment_types', 'cacsp_ic_register_avatar_comment_type' );

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
 * Also tell Inline Comments to fetch all comments for a paper, regardless of pagination settings.
 *
 * @param  array $retval Current comment list args.
 * @return array
 */
function cacsp_ic_alter_comments_list_args( $retval ) {
	if ( false === cacsp_is_page() ) {
		return $retval;
	}

	$retval['type'] = 'incom';
	$retval['page'] = 1;
	$retval['per_page'] = -1;
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
	if ( $comment_parent instanceof WP_Comment ) {
		$comment_parent = new stdClass;
		$comment_parent->comment_parent = 0;
	}

	while ( $comment_parent && 0 !== (int) $comment_parent->comment_parent ) {
		$c = get_comment( $comment_parent );
		$parent = $c->comment_parent;
		$comment_parent = get_comment( $c->comment_parent );
		if ( $comment_parent instanceof WP_Comment ) {
			$comment_parent = new stdClass;
			$comment_parent->comment_parent = 0;
		}
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
 * Inline JS outputted in the footer.
 *
 * JS does the following:
 *
 * - Repositions inline comment timestamps underneath the author name.
 * - Displays inline comment for inline comment permalink URLs. Relies on the
 *   the "?para" URL parameter and the arrive.js library.  Arrive.js watches
 *   for DOM element injections and allows us to hook in when IC adds its
 *   comment elements.
 *
 * @see cacsp_ic_alter_comment_permalink()
 * @see https://github.com/uzairfarooq/arrive
 */
function cacsp_ic_inline_js() {
	if ( false === cacsp_is_page() ) {
		return;
	}

	if( true === Social_Paper::$is_new ) {
		return;
	}

	if ( get_queried_object() && true === in_array( get_queried_object()->post_status, array( 'draft', 'auto-draft' ), true ) ) {
		return;
	}
?>

	<script type="text/javascript" src="//cdn.rawgit.com/uzairfarooq/arrive/master/minified/arrive.min.js"></script>
	<script type="text/javascript">
	jQuery(function(){
		// reposition inline comment timestamps
		jQuery('.comment-time').each(function() {
			jQuery(this).closest('.comment-body').find('.comment-author cite').append(this);
		});

		// Hide main comment bubble form when attempting to reply to an inline comment
		jQuery( '.incom-reply a.comment-reply-link' ).on( 'click', function() {
			if ( jQuery( 'li #comment' ).length ) {
				jQuery( '#comments-and-form > #respond' ).hide();
				jQuery( '#comments-and-form > .incom-cancel' ).hide();
			}

			jQuery( 'li #comment' ).show();
			jQuery( 'li .form-submit' ).show();
		});

		/**
		 * Hook into window load
		 */
		jQuery(window).on( 'load', function() {
			// Show main comment bubble form when clicking on a comment bubble
			jQuery( '.incom-bubble' ).on( 'click', function() {
				// Hide inline reply comment form
				if ( jQuery( 'li #comment:visible' ).length ) {
					jQuery( 'li #comment' ).hide();
					jQuery( 'li .form-submit' ).hide();
				}

				jQuery( '#comments-and-form > #respond' ).show();
				jQuery( '#comments-and-form > .incom-cancel' ).show();
			});

		});

		var pmatch = window.location.search.match(/para\=([^&]+)/);

		if ( null === pmatch ) {
			return;
		}

		jQuery(document).arrive('[data-incom-bubble=' + pmatch[1] + ']', function() {
			// manually trigger click so IC will display the comment tree
			jQuery(this).click();

			// unbind arrive
			jQuery(document).unbindArrive();
		});
	});
	</script>

<?php
}
add_action( 'wp_footer', 'cacsp_ic_inline_js' );

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

	if ( is_singular( 'cacsp_paper' ) && 'auto-draft' === get_post_status() ) {
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

/**
 * Update all comment references based on data sent via AJAX.
 *
 * This function is called when a paragraph bubble is dropped on to a different
 * paragraph. This allows all comments for a paragraph to be reassigned to a new
 * one with a single action.
 */
function cacsp_social_paper_reassign_comments() {
	if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
		exit();
	}

	// get incoming data
	$target_reference = isset( $_POST[ 'incom_ref' ] ) ? $_POST[ 'incom_ref' ] : '';
	$curr_reference   = isset( $_POST[ 'curr_ref' ] ) ? $_POST[ 'curr_ref' ] : '';

	// init with error message
	if ( $target_reference === $curr_reference ) {
		$msg = __( 'Comments match current paragraph! No updating needed.', 'social-paper' );
	} else {
		$msg = __( 'Comments were not moved.', 'social-paper' );
	}

	// sanity check
	if ( ! empty( $target_reference ) && ! empty( $curr_reference ) && $target_reference !== $curr_reference ) {
		// fetch all comments matching our post ID and comments needing updating
		$comments = get_comments( array(
			'post_id'    => $_POST['post_id'],
			'status'     => 'any',
			'fields'     => 'ids',
			'orderby'    => false,
			'meta_key'   => 'data_incom',
			'meta_value' => $curr_reference
		) );

		foreach ( $comments as $comment_id ) {
			update_comment_meta( $comment_id, 'data_incom', sanitize_text_field( $target_reference ) );
		}

		// overwrite message
		$msg = __( 'Comments successfully moved!', 'social-paper' );

	}

	$msg .= "\n";

	// set reasonable headers
	header('Content-type: text/plain');
	header("Cache-Control: no-cache");
	header("Expires: -1");

	// echo & die
	echo json_encode( $msg );
	exit();

}

add_action( 'wp_ajax_cacsp_social_paper_reassign_comments', 'cacsp_social_paper_reassign_comments' );

/**
 * Update a comment's reference based on data sent via AJAX.
 *
 * This function is called when a comment permalink is dropped on to a paragraph.
 * This enables a single comment (and its replies) to be reassigned to a new
 * paragraph. The roadmap for this is to allow authors to reassign "orphaned"
 * comments to a relevant paragraph.
 */
function cacsp_social_paper_reassign_comment() {
	if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
		exit();
	}

	// get incoming data
	$comment_reference = isset( $_POST[ 'incom_ref' ] ) ? $_POST[ 'incom_ref' ] : '';
	$comment_id = isset( $_POST[ 'comment_id' ] ) ? (int) $_POST[ 'comment_id' ] : '';

	// init with error message
	$msg = sprintf( __( 'Comment with ID %d was not updated', 'social-paper' ), $comment_id );

	// sanity check
	if ( ! empty( $comment_reference ) AND ! empty( $comment_id ) ) {

		// update meta
		update_comment_meta( $comment_id, 'data_incom', sanitize_text_field( $comment_reference ) );

		// overwrite message
		$msg = sprintf( __( 'Comment with ID %d has been successfully updated', 'social-paper' ), $comment_id );

	}

	$msg .= "\n";

	// set reasonable headers
	header('Content-type: text/plain');
	header("Cache-Control: no-cache");
	header("Expires: -1");

	// echo & die
	echo json_encode( $msg );
	exit();

}

add_action( 'wp_ajax_cacsp_social_paper_reassign_comment', 'cacsp_social_paper_reassign_comment' );

/**
 * Update comment references based on data sent via AJAX.
 *
 * @param int $post_id ID of the post.
 */
function cacsp_update_comment_references( $post_id ) {
	if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
		return;
	}

	// bail unless we get a populated array
	if (
		! isset( $_POST['social_paper_comments'] ) ||
		! is_array( $_POST['social_paper_comments'] ) ||
		count( $_POST['social_paper_comments'] ) == 0	) {
		return;
	}

	/*
	if ( ! wp_verify_nonce( $_POST['social_paper_comments_nonce'], 'cacsp-social-paper-comments' ) ) {
		return;
	}
	*/

	// update comment meta for each changed comment
	foreach( $_POST['social_paper_comments'] AS $comment_data ) {

		// get comment ID from exploded array
		$comment_id_array = explode( '-', $comment_data['comment_id'] );
		$comment_id = isset( $comment_id_array[1] ) ? (int) $comment_id_array[1] : 0;

		// sanity check
		if ( $comment_id === 0 ) continue;

		// update comment meta
		update_comment_meta( $comment_id, 'data_incom', sanitize_text_field( $comment_data['new_value'] ) );

	}

}
add_action( 'save_post', 'cacsp_update_comment_references' );
