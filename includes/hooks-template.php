<?php
/**
 * Template hook integration into WordPress
 *
 * @package Social_Paper
 * @subpackage Hooks
 */

/**
 * Set theme capabilities
 */
function cacsp_after_setup_theme() {

	// enable Featured Images for papers
	// http://wordpress.stackexchange.com/a/23943
	$support = get_theme_support( 'post-thumbnails' );

	// No support for 'post-thumbnails', so add it.
	if( false === $support ) {
		add_theme_support( 'post-thumbnails', array( 'cacsp_paper' ) );

	// Only certain post types have 'post-thumbnails' support.
	// Add our post type to the list.
	} elseif ( is_array( $support ) ) {
		$support[0][] = 'cacsp_paper';
		add_theme_support( 'post-thumbnails', $support[0] );
	}

	// define a custom image size, cropped to fit
	add_image_size(
		'cacsp-feature',
		apply_filters( 'cacsp_feature_image_width', 1200 ),
		apply_filters( 'cacsp_feature_image_height', 600 ),
		true // crop
	);

}
add_action( 'after_setup_theme', 'cacsp_after_setup_theme', 11 );

/**
 * Utility to test for feature image, because has_post_thumbnail() fails sometimes
 * @see http://codex.wordpress.org/Function_Reference/has_post_thumbnail
 *
 * @return bool True if post has thumbnail, false otherwise
 */
function cacsp_has_feature_image() {
	if ( '' != get_the_post_thumbnail() ) {
		return true;
	}

	return false;
}

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
 * Directory template loader.
 *
 * Overrides the archive post template in themes to use the page template.
 * The page template is more generic-looking than the archive template and
 * is what BuddyPress uses as well.
 *
 * @param  string $retval Absolute path to found template or empty string.
 * @return string
 */
function cacsp_archive_template_loader( $retval = '' ) {
	if ( ! cacsp_is_archive() ) {
		return $retval;
	}

	return get_query_template( 'page', array(
		'archive-social-paper.php',
		'page.php'
	) );
}
add_filter( 'archive_template', 'cacsp_archive_template_loader' );
add_filter( 'search_template',  'cacsp_archive_template_loader' );

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

/**
 * Asset enqueue handler on single social paper pages.
 *
 * Removes all styles except the most pertinent ones.
 *
 * @todo maybe do the same for scripts?
 */
function cacsp_asset_enqueue_handler() {
	if ( ! cacsp_is_page() || is_404() ) {
		return;
	}

	$styles = wp_styles();

	$theme_handle = '';
	foreach ( $styles->registered as $queue => $arg ) {
		if ( false !== strpos( $arg->src, get_stylesheet_uri() ) ) {
			$theme_handle = $arg->handle;
			break;
		}
	}

	// Found the theme's stylesheet!  Remove it!
	if ( ! empty( $theme_handle ) ) {
		wp_deregister_style( $theme_handle );

	// Brute-force: wipe out all styles and only enqueue the ones we need.
	// Do this just to remove the theme's stylesheet! :(
	} else {
		$styles->queue = array(
			'social-paper-single',

			// wp-side-comments
			'side-comments-style',
			'side-comments-theme',

			// inline-comments
			'incom-style',

			// wp-front-end-editor
			#'wp-core-ui',
			'media-views',
			'wp-core-ui-colors',
			'buttons',
			'wp-auth-check',
			'fee-modal',
			'fee-link-modal',
			'tinymce-core',
			'tinymce-view',
			'fee',
			'dashicons',

			// adminbar
			'admin-bar',
			'fee-adminbar',

			// buddypress
			'bp-mentions-css'

		);

	}

	// enqueue our styles
	wp_enqueue_style( 'social-paper-single', Social_Paper::$URL . '/assets/css/single.css' );
	wp_enqueue_style( 'social-paper-single-print', Social_Paper::$URL . '/assets/css/print.css', array('social-paper-single'), '0.1', 'print' );

	if ( function_exists( 'bp_is_active' ) && bp_is_active( 'groups' ) ) {
		$select2_css_url = set_url_scheme( 'http://cdnjs.cloudflare.com/ajax/libs/select2/4.0.0/css/select2.min.css' );
		wp_enqueue_style( 'social-paper-select2', $select2_css_url );
	}

	// Register scripts.
	$sp_js_deps = array( 'jquery' );
	if ( function_exists( 'bp_is_active' ) && bp_is_active( 'groups' ) ) {
		$select2_js_url = set_url_scheme( 'http://cdnjs.cloudflare.com/ajax/libs/select2/4.0.0/js/select2.js' );
		wp_register_script( 'social-paper-select2', $select2_js_url, array( 'jquery' ) );
		$sp_js_deps[] = 'social-paper-select2';
	}

	wp_enqueue_script( 'social-paper-single', Social_Paper::$URL . '/assets/js/single.js', $sp_js_deps );

	wp_localize_script( 'social-paper-single', 'SocialPaperL18n', array(
		'group_placeholder' => __( 'Enter a group name', 'social-paper' ),
		'reader_placeholder' => __( 'Enter a user name', 'social-paper' ),
	) );
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

		// always reset scope to all
		@setcookie( 'bp-papers-scope', 'all', 0, '/' );

		if ( is_user_logged_in() ) {
			$user_filter = false;

			if ( ! empty( $_GET['user'] ) && get_current_user_id() === (int) $_GET['user'] ) {
				$user_filter = true;
			}

			if ( $user_filter ) {
				$q->set( 'author', (int) get_current_user_id() );
				$q->set( 'publish_status', array( 'publish', 'private' ) );
			}
		}

	} elseif ( $q->is_singular ) {
		Social_Paper::$is_page = true;
	}

	// add marker if we're on the 'new' page slug
	if ( ! empty( $q->query['cacsp_paper'] ) && 'new' === $q->query['cacsp_paper'] ) {
		Social_Paper::$is_new = true;
	}
}
add_action( 'pre_get_posts', '_cacsp_set_markers' );

/**
 * Set up virtual pages for use with Social Paper.
 *
 * Some of our pages do not exist in WordPress.  In order to render content,
 * we have to do some tomfoolery to get WP to see our page as a "real" page.
 *
 * Currently supports the 'new' page slug.
 *
 * @see _cacsp_set_markers()
 *
 * @param  array $p Current queried posts.
 * @return array
 */
function _cacsp_set_virtual_page( $p ) {
	// 'new' page slug
	if ( true === Social_Paper::$is_new ) {
		// redirect non-authenticated users back to paper directory
		if ( false === is_user_logged_in() ) {
			wp_redirect( home_url( '/papers/' ) );
			die();
		}

		// dummy time!
		$p = array();
		$p[] = new WP_Post( (object) array(
			'ID'              => 0,
			'post_content'    => __( 'Loading new paper.  Please wait...', 'social-paper' ),
			'post_title'      => '',
			'post_name'       => 'new',
			'filter'          => 'raw',
			'post_type'	  => 'cacsp_paper',
		) );
	}

	// empty directory
	// we need to pass the have_posts() check so we can override the content
	if ( cacsp_is_archive() && empty( $p ) ) {
		Social_Paper::$is_empty_archive = true;

		// dummy time!
		$p = array();
		$p[] = new WP_Post( (object) array(
			'ID'              => 0,
			'post_content'    => '',
			'post_title'      => '',
			'post_name'       => '',
			'filter'          => 'raw',
			'post_type'	  => 'cacsp_paper',
		) );
	}

	return $p;
}
add_filter( 'the_posts', '_cacsp_set_virtual_page' );

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

	// be careful with other WP_Query loops on the page
	if ( empty( $q->query['post_type'] ) || 'cacsp_paper' !== $q->query['post_type'] ) {
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

	// rewind posts if papers exist to display them in our template
	if ( false === Social_Paper::$is_empty_archive ) {
		$q->rewind_posts();
	}

	$templates = array();
	if ( function_exists( 'buddypress' ) ) {
		$templates[] = 'content-directory-social-paper-buddypress.php';
	}
	$templates[] = 'content-directory-social-paper.php';

	cacsp_locate_template( $templates, true );
}
add_action( 'loop_end', '_cacsp_archive_ob_end', 999 );

/**
 * Things we do at the beginning of a paper loop.
 *
 * Currently, we add a placeholder title if no title was added for a paper.
 *
 * @param WP_Query $q
 */
function cacsp_loop_start( $q ) {
	if ( empty( $q->query['post_type'] ) || 'cacsp_paper' !== $q->query['post_type'] ) {
		return;
	}

	add_filter( 'the_title', 'cacsp_loop_add_placeholder_title' );
}
add_action( 'loop_start', 'cacsp_loop_start' );

/**
 * Filters the post title to add a placeholder if it is omitted from a paper.
 *
 * @param  string $retval
 * @return string
 */
function cacsp_loop_add_placeholder_title( $retval = '' ) {
	if ( is_singular( 'cacsp_paper' ) ) {
		return $retval;
	}

	if ( ! empty( $retval ) ) {
		return $retval;
	}

	return __( '(Untitled)', 'social-paper' );
}

/**
 * Display tag data in paper meta area.
 *
 * @since 1.0.0
 */
function cacsp_show_paper_tags_in_paper_meta() {
	$links = cacsp_get_paper_tags_links( get_queried_object_id() );

	echo '<span class="paper-tags"><br />';
	printf( __( 'Tags: <span class="paper-tags-list">%s</span>', 'social-paper' ), implode( ', ', $links ) );
	echo '</span>';
}
add_action( 'cacsp_after_paper_meta', 'cacsp_show_paper_tags_in_paper_meta', 100 );

/**
 * Get an array of tag archive links for a post.
 *
 * @since 1.0.0
 *
 * @param int $post_id ID of the post.
 * @return array
 */
function cacsp_get_paper_tags_links( $post_id ) {
	$tags = wp_get_object_terms( $post_id, 'cacsp_paper_tag' );

	if ( empty( $tags ) ) {
		return;
	}

	$links = array();
	foreach ( $tags as $tag ) {
		$tag_archive = add_query_arg( 'cacsp_paper_tag', $tag->slug, get_post_type_archive_link( 'cacsp_paper' ) );
		$links[] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( $tag_archive ),
			esc_html( $tag->name )
		);
	}

	return $links;
}

/**
 * Wrap comment content in an identifer div
 *
 * @param str $comment_content The comment content
 * @param object $comment The comment object
 * @param array $args The comment arguments
 * @return void
 */
function cacsp_comment_text( $comment_content, $comment, $args ) {
	if ( ! cacsp_is_page() ) {
		return $comment_content;
	}

	$comment_content = '<div class="comment_content">' . $comment_content;

	// add inline comment permalink to end of content
	// this will be later moved by JS - see cacsp_ic_inline_js()
	if ( 'incom' === $comment->comment_type ) {
		/* translators: 1: date, 2: time */
		$comment_time = '<div class="comment-time"><a href="' . get_comment_link() . '">' . sprintf( __( '%1$s at %2$s', 'social-paper' ), get_comment_date(),  get_comment_time() ) . '</a></div>';

		$comment_content .= $comment_time;
	}

	$comment_content .= '</div>';
	return $comment_content;
}
add_filter( 'get_comment_text', 'cacsp_comment_text', 1000, 3 );

/**
 * Generate unapproved comment action links.
 *
 * @since 1.0.0
 *
 * @param object $comment Comment object.
 */
function cacsp_unapproved_comment_links( $comment ) {
	$post_permalink = get_permalink( $comment->comment_post_ID );

	$links = array();

	// Approve
	$approve_link = add_query_arg(
		'cacsp_approve',
		intval( $comment->comment_ID ),
		$post_permalink
	);

	$links[] = sprintf(
		'<a class="approve" href="%s">' . __( 'Approve', 'social-paper' ) . '</a>',
		wp_nonce_url( $approve_link, 'cacsp_approve_comment-' . $comment->comment_ID, '_cacsp_approve_nonce' )
	);

	// Spam
	$spam_link = add_query_arg(
		'cacsp_spam',
		intval( $comment->comment_ID ),
		$post_permalink
	);

	$links[] = sprintf(
		'<a class="spam confirm" href="%s">' . __( 'Spam', 'social-paper' ) . '</a>',
		wp_nonce_url( $spam_link, 'cacsp_spam_comment-' . $comment->comment_ID, '_cacsp_spam_nonce' )
	);

	// Trash
	$trash_link = add_query_arg(
		'cacsp_trash',
		intval( $comment->comment_ID ),
		$post_permalink
	);

	$links[] = sprintf(
		'<a class="trash confirm" href="%s">' . __( 'Trash', 'social-paper' ) . '</a>',
		wp_nonce_url( $trash_link, 'cacsp_trash_comment-' . $comment->comment_ID, '_cacsp_trash_nonce' )
	);


	echo implode( ' | ', $links );
}

/**
 * Catch comment moderation actions.
 *
 * @since 1.0.0
 */
function cacsp_catch_comment_moderation() {
	$actions = array( 'cacsp_approve', 'cacsp_spam', 'cacsp_trash' );

	foreach ( $actions as $_action ) {
		if ( isset( $_GET[ $_action ] ) ) {
			$action = $_action;
			$comment_id = intval( $_GET[ $action ] );
			break;
		}
	}

	if ( ! isset( $action ) ) {
		return;
	}

	$nonce_key = '_' . $action . '_nonce';
	$nonce = isset( $_GET[ $nonce_key ] ) ? urldecode( $_GET[ $nonce_key ] ) : '';
	$nonce_action = $action . '_comment-' . $comment_id;

	if ( ! wp_verify_nonce( $nonce, $nonce_action ) ) {
		return;
	}

	// @todo Better permission check.
	if ( ! current_user_can( 'edit_paper', get_queried_object_id() ) ) {
		return;
	}

	switch ( $action ) {
		case 'cacsp_approve' :
			wp_set_comment_status( $comment_id, 'approve' );
			$redirect = add_query_arg( 'approved', 1, get_comment_link( $comment_id ) );
			break;

		case 'cacsp_spam' :
			wp_spam_comment( $comment_id );
			$redirect = add_query_arg( 'spammed', 1, get_permalink( get_queried_object_id() ) );
			break;

		case 'cacsp_trash' :
			wp_trash_comment( $comment_id );
			$redirect = add_query_arg( 'spammed', 1, get_permalink( get_queried_object_id() ) );
			break;
	}

	wp_redirect( $redirect );
	die();
}
add_action( 'template_redirect', 'cacsp_catch_comment_moderation', 100 );

/**
 * bp-default theme comment overrides.
 *
 * Disables the avatar from showing atop the comment form.
 *
 * @access private
 */
function _cacsp_bp_dtheme_overrides() {
	if ( ! cacsp_is_page() ) {
		return;
	}

	remove_action( 'comment_form_top', 'bp_dtheme_before_comment_form' );
	remove_action( 'comment_form', 'bp_dtheme_after_comment_form' );
}
add_action( 'wp', '_cacsp_bp_dtheme_overrides' );

/**
 * Access protection for single papers.
 *
 */
function cacsp_access_protection() {
	$post = get_queried_object();

	if ( ! ( $post instanceof WP_Post ) || 'cacsp_paper' !== $post->post_type ) {
		return;
	}

	if ( ! current_user_can( 'read_paper', $post->ID ) ) {
		if ( function_exists( 'bp_core_add_message' ) ) {
			bp_core_add_message( __( 'You do not have access to read that paper.', 'social-paper' ), 'error' );
		}

		wp_redirect( get_post_type_archive_link( 'cacsp_paper' ) );
		die();
	}
}
add_action( 'template_redirect', 'cacsp_access_protection' );
