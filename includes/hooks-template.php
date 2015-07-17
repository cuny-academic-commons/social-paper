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
 * Remove the action which writes inline styles for the admin bar
 */
function cacsp_prevent_inline_admin_bar_styles() {
	if ( ! cacsp_is_page() ) {
		return $retval;
	}

	add_theme_support( 'admin-bar', array( 'callback' => '__return_false' ) );
}
add_action( 'wp', 'cacsp_prevent_inline_admin_bar_styles' );

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
	if ( ! cacsp_is_page() ) {
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
		'admin-bar',
		'fee-adminbar',
	);

	// enqueue our styles
	wp_enqueue_style( 'social-paper-single', Social_Paper::$URL . '/assets/css/single.css' );
	wp_enqueue_style( 'social-paper-single-print', Social_Paper::$URL . '/assets/css/print.css', array('social-paper-single'), '0.1', 'print' );
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

	return $p;
}
add_filter( 'the_posts', '_cacsp_set_virtual_page' );

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

	show_admin_bar( false );
}
add_action( 'admin_bar_init', '_cacsp_disable_admin_bar_on_social_paper_pages', 1 );

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

	$q->rewind_posts();

	cacsp_locate_template( 'content-directory-social-paper.php', true );
}
add_action( 'loop_end', '_cacsp_archive_ob_end', 999 );