<?php
/**
 * Compatibility with WordPress Front-end Editor plugin
 *
 * @package Social_Paper
 * @subpackage Hooks
 */

/**
 * Utility function to load our custom version of FEE.
 *
 * We extend the regular {@link FEE} class to fix a few issues.
 */
function cacsp_wp_fee_load() {
	require Social_Paper::$PATH . '/includes/class-cacsp-fee.php';
	Social_Paper::$FEE = new CACSP_FEE;

	// re-run init routine.
	// we need to manually call FEE's init() method since we're calling this after
	// the 'init' hook
	Social_Paper::$FEE->init();
}

/**
 * Register FEE support for our custom post type.
 */
function cacsp_add_wp_fee_compatibility() {
	add_post_type_support( 'cacsp_paper', 'front-end-editor' );
}
add_action( 'init', 'cacsp_add_wp_fee_compatibility' );

/**
 * Load our version of FEE on a frontend Social Paper page.
 *
 * We're running this on 'pre_get_posts' since this is when we've determined
 * that we're on a Social Paper page.
 */
function cacsp_wp_fee_frontend_load() {
	if ( false === cacsp_is_page() && false === Social_Paper::$is_new ) {
		return;
	}

	if ( ! class_exists( 'CACSP_FEE' ) ) {
		cacsp_wp_fee_load();

		// do not run FEE's 'wp' routine
		remove_anonymous_object_filter( 'wp', 'FEE', 'wp' );
	}
}
add_action( 'pre_get_posts', 'cacsp_wp_fee_frontend_load', 999 );

/**
 * Load our version of FEE when a FEE AJAX post is being made.
 *
 * This is the only way to override FEE's default AJAX post method.
 *
 * @link https://github.com/iseulde/wp-front-end-editor/pull/228
 */
function cacsp_wp_fee_ajax_load_on_post() {
	// we only want to intercept AJAX requests
	if ( false === defined( 'DOING_AJAX' ) || true !== constant( 'DOING_AJAX' ) ) {
		return;
	}

	// check if we're on the 'fee_post' AJAX hook
	if ( empty( $_REQUEST['action'] ) || 'fee_post' !== $_REQUEST['action'] ) {
		return;
	}

	// let's start the show!
	if ( ! class_exists( 'CACSP_FEE' ) ) {
		// remove FEE's default post hook
		remove_all_actions( 'wp_ajax_fee_post' );

		// load our version of FEE
		cacsp_wp_fee_load();
	}
}
add_action( 'admin_init', 'cacsp_wp_fee_ajax_load_on_post' );

/**
 * Enable FEE on social paper pages.
 *
 * When we're on the 'New' page, we toggle FEE's 'New Mode' with JS.  When
 * we're on a published social paper page, we show an "Enable Editing" link in
 * the top-right corner of the page.
 *
 * @access private
 */
function _cacsp_enable_fee() {
	// see if FEE exists
	if ( false === class_exists( 'FEE' ) ) {
		return;
	}

	// check to see if we're on our social paper page
	if ( false === cacsp_is_page() ) {
		return;
	}

	// automatically toggle FEE into new mode
	if ( 'new' === get_query_var( 'name' ) ) {
		echo '<a id="cacsp-new-paper-link" href="' . admin_url( "/post-new.php?post_type=cacsp_paper" ) . '">&nbsp;</a>';
	?>

		<script type="text/javascript">
		// this is a copy of fee-adminbar.js
		( function( $ ) {
			'use strict';

			function _new( postType ) {
				wp.ajax.post( 'fee_new', {
					post_type: postType,
					nonce: fee.nonce
				} ).done( function( url ) {
					// we change window.location.href to window.location.replace()
					// this is done to avoid the 'new' page being in the browser's history
					url && ( window.location.replace( url ) );
				} );
			}

			$( function() {
				$.each( fee.supportedPostTypes, function( i, value ) {
					$( 'a[href="' + fee.postNew + '?post_type=' + value + '"]' )
					.add( value === 'post' ? 'a[href="' + fee.postNew + '"]' : null )
					.attr( 'href', '#' )
					.on( 'click', function( event ) {
						event.preventDefault();
						_new( value );
					} );
				} );
			} );
		} )( jQuery );

		jQuery( function($) {
			$( '#cacsp-new-paper-link' ).hide().trigger( 'click' );
		});
		</script>

	<?php
	// show edit link if not on a draft page
	} elseif ( get_queried_object() && current_user_can( 'edit_paper', get_queried_object()->ID ) && 'auto-draft' !== get_queried_object()->post_status && ! is_404()  ) {
		echo '<div id="cacsp-edit-paper"><a id="wp-admin-bar-edit" href="#fee-edit-link"><span>Enable Editing</span></a></div>';
	}
}
add_action( 'wp_footer', '_cacsp_enable_fee', 999 );

/**
 * Disable WP FEE on BuddyPress pages.
 *
 * BuddyPress uses virtual pages with the "page" post type, which essentially
 * confuses FEE.  Here, we bail out of FEE support when on a BP page.
 *
 * @see https://github.com/iseulde/wp-front-end-editor/pull/227
 */
function cacsp_wp_fee_disable_support_on_bp_pages( $retval ) {
	if ( function_exists( 'is_buddypress' ) && is_buddypress() ) {
		return false;
	}

	return $retval;
}
add_filter( 'supports_fee', 'cacsp_wp_fee_disable_support_on_bp_pages' );

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
		wp_enqueue_script(
			'social-paper-single-fee',
			Social_Paper::$URL . '/assets/js/hooks-wp-fee.js',
			array( 'jquery', 'jquery-ui-droppable', 'jquery-ui-dialog' ), // load droppable and dialog as dependencies
			'0.2'
		);

		// assume user cannot drag-n-drop
		$drag_allowed = '0';

		// enqueue change tracking script
		wp_enqueue_script(
			'social-paper-single-changes',
			Social_Paper::$URL . '/assets/js/changes.js',
			array( 'social-paper-single-fee' ), // make dependent on our main script above
			'0.1'
		);

		global $post;
		if ( current_user_can( 'edit_post', $post->ID ) ) {

			// user can, so override
			$drag_allowed = '1';

			// add style for dialog
			wp_enqueue_style( 'wp-jquery-ui-dialog' );

			wp_enqueue_script( 'social-paper-tags-box', Social_Paper::$URL . '/assets/js/tags-box.js', array( 'jquery', 'suggest' ), false, true );
			wp_localize_script( 'social-paper-tags-box', 'tagsBoxL10n', array(
				'tagDelimiter' => _x( ',', 'tag delimiter', 'social-paper' ),
			) );
		}

		// localise
		wp_localize_script( 'social-paper-single-fee', 'Social_Paper_FEE', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'drag_allowed' => $drag_allowed,
			'i18n' => cacsp_wp_fee_localise(),
		) );

	}

}
add_action( 'wp_enqueue_scripts', 'cacsp_wp_fee_enqueue_scripts', 999 );

/**
 * Define translation strings for our Javascript
 *
 * @return array $translations The array of translations to pass to the script
 */
function cacsp_wp_fee_localise() {
	$time_string = cacsp_get_paper_time_string();

	$translations = array(
		'body' => __( 'Are you sure you want to assign the comment and its replies to the paragraph? This action cannot be undone.', 'social-paper' ),
		'button_disable' => __( 'Disable Editing', 'social-paper' ),
		'button_enable' => __( 'Enable Editing', 'social-paper' ),
		'button_update' => __( 'Update', 'social-paper' ),
		'created_on' => sprintf( _x( 'Created on %s', 'Used before publish date', 'social-paper' ), $time_string ),
		'paper_notice_public' => esc_html__( 'Public Paper', 'social-paper' ),
		'paper_notice_private' => esc_html__( 'Private Paper', 'social-paper' ),
		'published_on' => sprintf( _x( 'Published on %s', 'Used before publish date', 'social-paper' ), $time_string ),
		'message' => __( 'Please wait while the comments are reassigned. The page will refresh when this has been done.', 'social-paper' ),
		'submit' => __( 'Submitting...', 'social-paper' ),
		'title' => __( 'Are you sure?', 'social-paper' ),
	);

	return $translations;

}

/**
 * Strip paragraph tags during autosave.
 *
 * FEE adds wpautop to the paper content, which interferes with revisions during
 * autosave.
 *
 * @param  array $retval Post data.
 * @param  array $post   Post attributes.
 * @return array
 */
function cacsp_wp_fee_strip_paragraph_element_on_autosave( $retval, $post ) {
	// auto-save
	if ( 'revision' === $post['post_type'] ) {
		$parent = get_post( $post['post_parent'] );
		if ( 'cacsp_paper' !== $parent->post_type ) {
			return $retval;
		}

		// remove data-incom attribute added by Inline Comments
		$retval['post_content'] = preg_replace('/(<[^>]+) data-incom=".*?"/i', '$1', wp_unslash( $retval['post_content'] ) );
		$retval['post_content'] = wp_slash( $retval['post_content'] );

		// reverse autop
		$retval['post_content'] = str_replace( '<p>', '', $retval['post_content'] );
		$retval['post_content'] = str_replace( '</p>', "\n\n", $retval['post_content'] );
	}

	return $retval;
}
add_filter( 'wp_insert_post_data', 'cacsp_wp_fee_strip_paragraph_element_on_autosave', 10, 2 );

/**
 * Add button to WP FEE's toolbar
 *
 * @param object $post The WordPress post object
 */
function cacsp_wp_fee_tax_buttons( $post ) {
	if ( ! cacsp_is_page() ) {
		return;
	}

	if ( in_array( 'cacsp_paper_tag', get_object_taxonomies( $post ) ) ) { ?>
		<button data-toggle="modal" data-target=".fee-cacsp_paper_tag-modal" class="button button-large fee-button-cacsp_paper_tag"><div class="dashicons dashicons-tag dashicons-cacsp_paper_tag"></div></button>
	<?php }
}
add_action( 'fee_tax_buttons', 'cacsp_wp_fee_tax_buttons' );

/**
 * Filter attachments when selecting media on the frontend.
 *
 * By default, the "Add Media" modal shows all available attachments across
 * the site.  We do not want to do this due to privacy issues. Instead,
 * this function filters the attachments query to only list attachments
 * uploaded by the logged-in user.
 *
 * @param array $retval Current attachment query arguments
 * @return array
 */
function cacsp_filter_ajax_query_attachments( $retval ) {
	// don't do this in the admin area or if user isn't logged in
	if ( defined( 'WP_NETWORK_ADMIN' ) || false === is_user_logged_in() ) {
		return $retval;
	}

	if ( empty( $_POST['post_id'] ) )  {
		return $retval;
	}

	// check if the post is our event type
	$post = get_post( $_POST['post_id'] );
	if ( 'cacsp_paper' !== $post->post_type ) {
		return $retval;
	}

	// modify the attachments query to filter by the logged-in user
	$retval['author'] = get_current_user_id();
	return $retval;
}
add_filter( 'ajax_query_attachments_args', 'cacsp_filter_ajax_query_attachments' );

/**
 * AJAX callback for sample permalink generation on entry-title blur.
 *
 * @since 1.0.0
 */
function cacsp_sample_permalink_cb() {
	$post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
	if ( ! $post_id || ! current_user_can( 'edit_paper', $_POST['post_id'] ) ) {
		wp_send_json_error( -1 );
	}

	// @todo nonce check
	$title = isset( $_POST['new_title'] ) ? stripslashes( $_POST['new_title'] ) : '';

	$permalink = get_sample_permalink( $post_id, $title, $title );
	wp_send_json_success( $permalink );
}
add_action( 'wp_ajax_cacsp_sample_permalink', 'cacsp_sample_permalink_cb' );

/**
 * Save tags sent via AJAX.
 *
 * @param int $post_id ID of the post.
 */
function cacsp_save_tags( $post_id ) {
	if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
		return;
	}

	if ( ! isset( $_POST['cacsp_paper_tags'] ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_paper', $post_id ) ) {
		return;
	}

	$tags = explode( ',', $_POST['cacsp_paper_tags'] );
	wp_set_object_terms( $post_id, $tags, 'cacsp_paper_tag' );
}
add_action( 'save_post', 'cacsp_save_tags' );

/**
 * Get tag data.
 *
 * @since 1.0.0
 */
function cacsp_get_tag_data_cb() {
	$post_id = (int) $_POST['post_id'];
	$links = cacsp_get_paper_tags_links( $post_id );

	$data = '';
	if ( $links ) {
		$data = implode( ', ', $links );
	}

	wp_send_json_success( $data );
}
add_action( 'wp_ajax_cacsp_get_tag_data', 'cacsp_get_tag_data_cb' );
