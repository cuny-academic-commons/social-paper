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

	// declare FEE support for our CPT
	add_post_type_support( 'cacsp_paper', 'front-end-editor' );

	// re-run init routine.
	// we need to manually call FEE's init() method since we're calling this after
	// the 'init' hook
	Social_Paper::$FEE->init();
}

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
		echo '<a id="cascp-new-paper-link" href="' . admin_url( "/post-new.php?post_type=cacsp_paper" ) . '">&nbsp;</a>';
	?>

		<script type="text/javascript">
		jQuery( function($) {
			$( '#cascp-new-paper-link' ).hide().trigger( 'click' );
		});
		</script>

	<?php
	// show edit link if not on a draft page
	} elseif ( current_user_can( 'edit_post', get_queried_object()->ID ) && 'auto-draft' !== get_queried_object()->post_status ) {
		echo '<a id="wp-admin-bar-edit" href="#fee-edit-link"><span>Enable Editing</span></a>';
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
 * Add button to WP FEE's toolbar
 *
 * @param object $post The WordPress post object
 */
function cacsp_wp_fee_tax_buttons( $post ) {
	if ( ! cacsp_is_page() ) {
		return;
	}

	if ( in_array( 'cacsp_paper_tag', get_object_taxonomies( $post ) ) ) { ?>
		<button class="button button-large fee-button-cacsp_paper_tag"><div class="dashicons dashicons-tag dashicons-cacsp_paper_tag"></div></button>
	<?php }
}
add_action( 'fee_tax_buttons', 'cacsp_wp_fee_tax_buttons' );

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

if ( ! function_exists( 'remove_anonymous_object_filter' ) ) :
/**
 * Remove an anonymous object filter.
 *
 * @param  string $tag    Hook name.
 * @param  string $class  Class name
 * @param  string $method Method name
 * @param  bool   $strict Whether to check if the calling class matches exactly with $class.  If
 *                        false, all methods (including parent class) matching $method will be
 *                        removed.  If true, calling class must match $class in order to be removed.
 * @return void
 *
 * @link http://wordpress.stackexchange.com/a/57088 Tweaked by r-a-y for strict class checks.
 */
function remove_anonymous_object_filter( $tag = '', $class = '', $method = '', $strict = true ) {
	$filters = $GLOBALS['wp_filter'][ $tag ];
	if ( empty ( $filters ) ) {
		return;
	}

	foreach ( $filters as $priority => $filter ) {
		foreach ( $filter as $identifier => $function ) {
			if ( is_array( $function )
				and is_a( $function['function'][0], $class )
				and $method === $function['function'][1]
			) {
				// mod by r-a-y - strict class name checks
				if ( true === (bool) $strict && $class !== get_class( $function['function'][0] ) ) {
					continue;
				}

				remove_filter(
					$tag,
					array ( $function['function'][0], $method ),
					$priority
				);
			}
		}
	}
}
endif;
