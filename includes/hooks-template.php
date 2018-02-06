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

	// Check for 'title-tag' support.
	$title = get_theme_support( 'title-tag' );

	// If 'title-tag' support doesn't exist, only add it on single paper pages.
	if ( false === $title ) {
		// Since 'after_setup_theme' runs early, do checks on the current URL to
		// determine if we're on a single paper page.
		$curr_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . untrailingslashit( $_SERVER['REQUEST_URI'] );
		$base = substr( $curr_url, 0, strrpos( $curr_url, '/' ) );

		if ( $base === home_url( '/papers' ) || ( ! empty( $_GET['post_type'] ) && 'cacsp_paper' === $_GET['post_type'] ) ) {
			add_theme_support( 'title-tag' );
		}
	}
}
add_action( 'after_setup_theme', 'cacsp_after_setup_theme', 11 );

/**
 * Add title for paper drafts.
 *
 * @param  array $retval Current title parts.
 * @return array
 */
function cacsp_filter_document_title_parts( $retval ) {
	if ( empty( $_GET['post_type'] ) || 'cacsp_paper' !== $_GET['post_type'] ) {
		return $retval;
	}

	// WP title
	if ( current_filter() === 'wp_title_parts' ) {
		$retval[0] = __( 'Social Paper Draft', 'social-paper' ) . ' | ';

		// Twenty Twelve has a custom title filter, which duplicates the site name.
		// Let's just remove all filters on 'the_title' here.
		remove_all_filters( 'wp_title' );

	// Document title
	} else {
		$retval['title'] = __( 'Social Paper Draft', 'social-paper' );
	}

	return $retval;
}
add_filter( 'wp_title_parts',       'cacsp_filter_document_title_parts' );
add_filter( 'document_title_parts', 'cacsp_filter_document_title_parts' );

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
function cacsp_asset_single_enqueue_handler() {
	if ( ! cacsp_is_page() || is_404() ) {
		return;
	}

	// Fix embed widths
	$GLOBALS['content_width'] = 754;

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
			'wp-core-ui',
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

	$select2_css_url = set_url_scheme( 'http://cdnjs.cloudflare.com/ajax/libs/select2/4.0.0/css/select2.min.css' );
	wp_enqueue_style( 'social-paper-select2', $select2_css_url );

	// Register scripts.
	$sp_js_deps = array( 'jquery' );

	// Select2 (Readers and Groups).
	$select2_js_url = set_url_scheme( 'http://cdnjs.cloudflare.com/ajax/libs/select2/4.0.0/js/select2.js' );
	wp_register_script( 'select2', $select2_js_url, array( 'jquery' ) );
	$sp_js_deps[] = 'select2';

	$autogrow_js_url = set_url_scheme( Social_Paper::$URL . '/lib/js/autogrow.min.js' );
	wp_register_script( 'autogrow', $autogrow_js_url, array( 'jquery' ) );
	$sp_js_deps[] = 'autogrow';

	wp_enqueue_script( 'social-paper-single', Social_Paper::$URL . '/assets/js/single.js', $sp_js_deps );

	$unapproved_comment_count = cacsp_get_unapproved_comment_count( get_queried_object_id() );
	if ( 0 == $unapproved_comment_count ) {
		$unapproved_comment_alt = __( 'No unapproved comments', 'social-paper' );
	} elseif ( 1 == $unapproved_comment_count ) {
		$unapproved_comment_alt = __( 'One unapproved comment', 'social-paper' );
	} else {
		$unapproved_comment_alt = sprintf( _n( '%s unapproved comment', '%s unapproved comments', $unapproved_comment_count, 'social-paper' ), number_format_i18n( $unapproved_comment_count ) );
	}

	$group_id = '';
	if ( isset( $_GET['group_id'] ) ) {
		$group_id = $_GET['group_id'];
		if ( ! $group_id || ! bp_group_is_member( $group_id ) ) {
			$group_id = '';
		}
	}

	wp_localize_script( 'social-paper-single', 'SocialPaperI18n', array(
		'group_placeholder' => __( 'Enter a group name', 'social-paper' ),
		'reader_placeholder' => __( 'Enter a user name', 'social-paper' ),
		'description_max_length' => cacsp_get_description_max_length(),
		'unapproved_comment_count' => $unapproved_comment_count,
		'unapproved_comment_alt' => $unapproved_comment_alt,
		'spammed' => __( 'You have successfuly marked the comment as spam.', 'social-paper' ),
		'trashed' => __( 'You have successfuly trashed the comment.', 'social-paper' ),
		'paper_id' => get_queried_object_id(),
		'rest_url' => trailingslashit( rest_url( 'social-paper/' . Social_Paper::$rest_api_version ) ),
		'group_id' => $group_id,
	) );
}
add_action( 'wp_enqueue_scripts', 'cacsp_asset_single_enqueue_handler', 999 );

/**
 * Asset enqueue handler on the social paper archive page.
 */
function cacsp_asset_archive_enqueue_handler() {
	$enqueue = is_post_type_archive( 'cacsp_paper' );

	if ( ! $enqueue && function_exists( 'buddypress' ) ) {
		$enqueue = (
			bp_is_user() && bp_is_current_component( 'papers' ) ||
			bp_is_group() && bp_is_current_action( 'papers' )
		);
	}

	if ( $enqueue ) {
		wp_enqueue_style( 'social-paper-archive', Social_Paper::$URL . '/assets/css/archive.css' );
	}
}
add_action( 'wp_enqueue_scripts', 'cacsp_asset_archive_enqueue_handler' );

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

		// Sort by last modified
		$q->set( 'orderby', 'modified' );

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

	// Rewind posts since we have to redo the loop in our template.
	$q->rewind_posts();

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

	return cacsp_get_untitled_placeholder();
}

/**
 * Get the 'untitled' placeholder string.
 *
 * @since 1.0.0
 *
 * @retrun string
 */
function cacsp_get_untitled_placeholder() {
	return __( '(Untitled)', 'social-paper' );
}

/**
 * Display tag data in paper meta area.
 *
 * @since 1.0.0
 */
function cacsp_show_paper_tags_in_paper_meta() {
	$post_id = get_queried_object_id() ? get_queried_object_id() : $GLOBALS['post']->ID;

	$links = cacsp_get_paper_tags_links( $post_id );

	if ( ! $links ) {
		return;
	}

	echo '<span class="paper-tags"><br />';
	printf( __( 'Tags: <span class="paper-tags-list">%s</span>', 'social-paper' ), implode( ', ', $links ) );
	echo '</span>';
}
add_action( 'cacsp_after_paper_meta', 'cacsp_show_paper_tags_in_paper_meta', 100 );
add_action( 'bp_directory_papers_item_meta', 'cacsp_show_paper_tags_in_paper_meta' );

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
 * Display paper metadata in directory 'action' sections.
 *
 * Includes:
 *   - comment count
 *   - follower count
 *   - paper status
 *
 * @since 1.0.0
 */
function cacsp_directory_action_metadata() {
	$chunks = array();

	// Status.
	if ( cacsp_paper_is_protected( get_the_ID() ) ) {
		$chunks['paper_status'] = __( 'Private Paper', 'social-paper' );
	} else {
		$chunks['paper_status'] = __( 'Public Paper', 'social-paper' );
	}

	// Comment count.
	$comment_count = (int) get_post()->comment_count;
	if ( 1 === $comment_count ) {
		$chunks['comment_count'] = __( '1 comment', 'social-paper' );
	} elseif ( 1 < $comment_count ) {
		$chunks['comment_Count'] = sprintf( _n( '%s comment', '%s comments', $comment_count, 'social-paper' ), number_format_i18n( $comment_count ) );
	}


	/**
	 * Filter the directory action metadata chunks before they're imploded for display.
	 *
	 * @since 1.0.0
	 *
	 * @param array $chunks
	 */
	$chunks = apply_filters( 'cacsp_directory_action_metadata', $chunks );

	echo '<div class="meta">' . implode ( ' / ', array_map( 'esc_html', $chunks ) ) . '</div>';
}
add_action( 'bp_directory_papers_actions', 'cacsp_directory_action_metadata', 150 );

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
 * Filter the subject of WP's comment moderation emails.
 *
 * @since 1.0.0
 *
 * @param string $subject    Subject line from WP.
 * @param int    $comment_id ID of the comment.
 */
function cacsp_filter_comment_moderation_subject( $subject, $comment_id ) {
	$comment = get_comment( $comment_id );
	if ( ! $comment ) {
		return $subject;
	}

	$paper = new CACSP_Paper( $comment->comment_post_ID );
	if ( ! $paper->exists() ) {
		return $subject;
	}

	$subject = sprintf(
		__( 'A comment is awaiting your approval on the paper "%1$s" [%2$s]', 'social-paper' ),
		$paper->post_title,
		wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES )
	);

	return $subject;
}
add_filter( 'comment_moderation_subject', 'cacsp_filter_comment_moderation_subject', 10, 2 );

/**
 * Filter the content of WP's comment moderation emails.
 *
 * Regular SP users don't have the ability to moderate comments in the dashboard, so we send them to Edit Mode instead.
 *
 * @since 1.0.0
 *
 * @param string $message    Message content generated by WP.
 * @param int    $comment_id ID of the comment.
 * @return string
 */
function cacsp_filter_comment_moderation_text( $message, $comment_id ) {
	global $wpdb;

	$comment = get_comment( $comment_id );
	if ( ! $comment ) {
		return $message;
	}

	$paper = new CACSP_Paper( $comment->comment_post_ID );
	if ( ! $paper->exists() ) {
		return $message;
	}

	$message = sprintf(
__( 'A new comment on the paper %1$s is waiting for your approval.
%2$s

Author: %3$s
Email: %4$s
URL: %5$s
Comment: %6$s', 'social-paper' ),
		$paper->post_title,
		get_permalink( $paper->ID ),
		$comment->comment_author,
		$comment->comment_author_email,
		$comment->comment_author_url,
		$comment->comment_content
	);

	$comments_waiting = $wpdb->get_var( "SELECT count(comment_ID) FROM $wpdb->comments WHERE comment_approved = '0'" );

	$notify_message = sprintf( _n( 'Currently %s comment is waiting for approval. Please visit the moderation panel:', 'Currently %s comments are waiting for approval. Please visit the moderation panel:', $comments_waiting, 'social-paper' ), number_format_i18n( $comments_waiting ) ) . "\r\n";
	$notify_message .= add_query_arg( 'mod_comments', '1', get_permalink( $paper->ID ) );

	$message .= "\r\n\r\n" . $notify_message;

	return $message;
}
add_filter( 'comment_moderation_text', 'cacsp_filter_comment_moderation_text', 20, 2 );

/**
 * Prevent comment-moderation emails from going to site admin for Paper comments.
 *
 * @since 1.1.0
 */
function cacsp_prevent_moderator_emails( $emails, $comment_id ) {
	$comment = get_comment( $comment_id );
	if ( ! $comment ) {
		return $emails;
	}

	$post = get_post( $comment->comment_post_ID );
	if ( ! $post ) {
		return $emails;
	}

	if ( 'cacsp_paper' === $post->post_type ) {
		$admin_email = get_option( 'admin_email' );
		$admin_user = get_user_by( 'email', $admin_email );
		if ( $admin_user && $post->post_author != $admin_user->ID ) {
			$emails = array_diff( $emails, array( $admin_email ) );
		}
	}

	return $emails;
}
add_filter( 'comment_moderation_recipients', 'cacsp_prevent_moderator_emails', 10, 2 );

/**
 * Generate the Paper Status notices for display on single papers.
 *
 * @since 1.0.0
 */
function cacsp_paper_status_notices() {
	$notices = array();

	// Public/Private notice.
	if ( cacsp_paper_is_protected( get_queried_object_id() ) ) {
		$label = __( 'Private Paper', 'social-paper' );
		$class = 'protected';
	} else {
		$label = __( 'Public Paper', 'social-paper' );
		$class = '';
	}

	$notices[] = sprintf(
		'<div class="paper-notice paper-status %s">%s</div>',
		esc_attr( $class ),
		esc_html( $label )
	);

	$notices[] = sprintf(
		'<div class="paper-notice paper-draft %s">%s</div>',
		'publish' === get_queried_object()->post_status ? 'hidden' : '',
		esc_html__( 'Draft', 'social-paper' )
	);

	echo implode( '', $notices );
}

/**
 * Get a time string for display in the 'Published on' section of single papers.
 *
 * @since 1.0.0
 *
 * @return string
 */
function cacsp_get_paper_time_string() {
	$time_string = '<time class="entry-date published updated" datetime="%1$s">%2$s</time>';

	$time_string = sprintf( $time_string,
		esc_attr( get_the_date( 'c' ) ),
		get_the_date()
	);

	$time_string = sprintf( '<a href="%s" rel="bookmark">%s</a>',
		esc_url( get_permalink() ),
		$time_string
	);

	return $time_string;
}

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
