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
	add_theme_support( 'post-thumbnails', array( 'cacsp_paper' ) );

	// define a custom image size, cropped to fit
	add_image_size(
		'cacsp-feature',
		apply_filters( 'cacsp_feature_image_width', 1200 ),
		apply_filters( 'cacsp_feature_image_height', 600 ),
		true // crop
	);

}
add_action( 'after_setup_theme', 'cacsp_after_setup_theme' );

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

	// wipe out all styles and only enqueue the ones we need
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
	);

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
 * Disables the admin bar on single Social Paper pages.
 *
 * Might bring it back later...
 *
 * @access private
 */
function _cacsp_disable_admin_bar_on_social_paper_pages() {
	if ( ! cacsp_is_page() ) {
		return;
	}

	add_filter( 'show_admin_bar', '__return_false' );
}
add_action( 'wp', '_cacsp_disable_admin_bar_on_social_paper_pages' );

/**
 * Add a generic title in the loop if no title is added for a paper.
 *
 * @param  string $retval
 * @return string
 */
function cacsp_loop_add_placeholder_title( $retval = '' ) {
	if ( 'cacsp_paper' !== get_query_var( 'post_type' ) ) {
		return $retval;
	}

	if ( is_singular( 'cacsp_paper' ) ) {
		return $retval;
	}

	if ( ! empty( $retval ) ) {
		return $retval;
	}

	return __( '(Untitled)', 'social-paper' );
}
add_filter( 'the_title', 'cacsp_loop_add_placeholder_title' );

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
