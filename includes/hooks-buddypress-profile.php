<?php

/**
 * Social Paper Members Profile
 *
 * This file contains code relating to members profiles.
 */

class CACSP_Profile {

	/**
	 * Position to inject main nav and adminbar.
	 *
	 * @var int
	 */
	public $position = 50;

	/**
	 * Slug used for main nav and adminbar.
	 *
	 * @var string
	 */
	public $slug = 'papers';

	/**
	 * Published slug.
	 *
	 * @var string
	 */
	public $published_slug = 'published';

	/**
	 * Drafts slug.
	 *
	 * @var string
	 */
	public $drafts_slug = 'drafts';

	/**
	 * Initialises this object
	 *
	 * @return object
	 */
	function __construct() {

		// register hooks
		$this->register_hooks();

		// --<
		return $this;

	}

	/**
	 * Register hooks for this class
	 */
	public function register_hooks() {

		// add menu items on member profile
		add_action( 'bp_setup_nav', array( $this, 'profile_tab' ), 100 );

		// add adminbar nav
		add_action( 'bp_setup_admin_bar', array( $this, 'adminbar' ), $this->position );
	}

	/**
	 * Create a tab on member profile pages
	 */
	public function profile_tab() {

		// Determine user to use
		if ( bp_displayed_user_domain() ) {
			$user_domain = bp_displayed_user_domain();
		} elseif ( bp_loggedin_user_domain() ) {
			$user_domain = bp_loggedin_user_domain();
		} else {
			return;
		}

		$parent_url = trailingslashit( $user_domain . $this->slug );
		$nav_text = __( 'Papers', 'social-paper' );

		// create primary nav
		bp_core_new_nav_item( array(
			'name'                    => $nav_text,
			'slug'                    => $this->slug,
			'item_css_id'             => 'user-papers',
			'show_for_displayed_user' => true,
			'site_admin_only'         => false,
			'position'                => $this->position,
			'screen_function'         => 'cacsp_profile_screen',
			'default_subnav_slug'     => $this->published_slug,
		) );

		// create subnav items
		bp_core_new_subnav_item( array(
			'name'            => __( 'Published', 'social-paper' ),
			'slug'            => $this->published_slug,
			'parent_url'      => $parent_url,
			'parent_slug'     => $this->slug,
			'screen_function' => 'cacsp_profile_screen_published',
			'position'        => 10,
		) );

		bp_core_new_subnav_item( array(
			'name'            => __( 'Drafts', 'social-paper' ),
			'slug'            => $this->drafts_slug,
			'parent_url'      => $parent_url,
			'parent_slug'     => $this->slug,
			'screen_function' => 'cacsp_profile_screen_draft',
			'position'        => 10,
			'user_has_access' => bp_core_can_edit_settings()
		) );

		bp_core_new_subnav_item( array(
			'name'            => __( 'New Paper', 'social-paper' ),
			'slug'            => 'new-paper',
			'link'            => cacsp_get_the_new_paper_link(),
			'parent_url'      => $parent_url,
			'parent_slug'     => $this->slug,
			'screen_function' => 'cacsp_profile_screen_published',
			'position'        => 99,
			'user_has_access' => current_user_can( 'publish_events' )
		) );

	}

	/**
	 * Create the "Papers" nav menu in the WP adminbar.
	 */
	function adminbar() {
		if ( ! is_user_logged_in() ) {
			return;
		}

		$wp_admin_nav = array();

		// Parent nav
		$wp_admin_nav[] = array(
			'parent' => buddypress()->my_account_menu_id,
			'id'     => 'my-account-' . $this->slug,
			'title'  => __( 'Papers', 'social-paper' ),
			'href'   => trailingslashit( bp_loggedin_user_domain() . $this->slug )
		);

		// Subnav - Published
		$wp_admin_nav[] = array(
			'parent' => 'my-account-' . $this->slug,
			'id'     => 'my-account-' . $this->slug . '-' . $this->published_slug,
			'title'  => __( 'Published', 'social-paper' ),
			'href'   => trailingslashit( bp_loggedin_user_domain() . $this->slug )
		);

		// Subnav - Drafts
		$wp_admin_nav[] = array(
			'parent' => 'my-account-' . $this->slug,
			'id'     => 'my-account-' . $this->slug . '-' . $this->drafts_slug,
			'title'  => __( 'Drafts', 'social-paper' ),
			'href'   => trailingslashit( bp_loggedin_user_domain() . $this->slug . '/' . $this->drafts_slug )
		);

		// Subnav - New Paper
		$wp_admin_nav[] = array(
			'parent' => 'my-account-' . $this->slug,
			'id'     => 'my-account-' . $this->slug . '-new',
			'title'  => __( 'New Paper', 'social-paper' ),
			'href'   => cacsp_get_the_new_paper_link()
		);

		$wp_admin_nav = apply_filters( 'bp_papers_admin_nav', $wp_admin_nav );

		// Add each admin menu
		foreach( $wp_admin_nav as $admin_menu ) {
			$GLOBALS['wp_admin_bar']->add_menu( $admin_menu );
		}
	}

} // end class

// init
new CACSP_Profile;

/**
 * Show "published" screen
 */
function cacsp_profile_screen_published() {

	// temporary callback
	add_action( 'bp_template_content', 'cacsp_profile_screen_published_content' );

	// load template
	bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );

}

/**
 * Show "published" screen content
 */
function cacsp_profile_screen_published_content() {

	// determine user to query
	if ( bp_displayed_user_domain() ) {
		$user_id = bp_displayed_user_id();
	} elseif ( bp_loggedin_user_domain() ) {
		$user_id = bp_loggedin_user_id();
	} else {
		die('WTF?');
	}

	$args = array(
		'post_type' => 'cacsp_paper',
		'author' => $user_id,
		'post_status' => 'publish',
	);

	if ( $user_id === bp_loggedin_user_id() ) {
		$args['post_status'] = array( 'publish', 'private' );
	}

	// perform query for this user
	$profile_query = new WP_Query( $args );

	?>
	<div class="entry-content">

	<?php if ( $profile_query->have_posts() ) : ?>

		<ul class="item-list"">

		<?php while ( $profile_query->have_posts() ) : $profile_query->the_post(); ?>
			<?php cacsp_get_template_part( 'list-social-paper', 'buddypress' ); ?>
		<?php endwhile; ?>

		</ul>

	<?php else : ?>

		<p><?php _e( 'No published papers found.', 'social-paper' ); ?></p>

	<?php endif; ?>

	</div>

	<?php
}

/**
 * Show "draft" screen
 */
function cacsp_profile_screen_draft() {

	// temporary callback
	add_action( 'bp_template_content', 'cacsp_profile_screen_draft_content' );

	// load template
	bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );

}

/**
 * Show "draft" screen content
 */
function cacsp_profile_screen_draft_content() {

	// determine user to query
	if ( bp_displayed_user_domain() ) {
		$user_id = bp_displayed_user_id();
	} elseif ( bp_loggedin_user_domain() ) {
		$user_id = bp_loggedin_user_id();
	} else {
		die('WTF?');
	}

	// perform query for this user
	$profile_query = new WP_Query( array(
		'post_type' => 'cacsp_paper',
		'author' => $user_id,
		'post_status' => array( 'draft', 'future' )
	) );

	?>
	<div class="entry-content">

	<?php if ( $profile_query->have_posts() ) : ?>

		<ul class="item-list">

		<?php while ( $profile_query->have_posts() ) : $profile_query->the_post(); ?>
			<?php cacsp_get_template_part( 'list-social-paper', 'buddypress' ); ?>
		<?php endwhile; ?>

		</ul>

	<?php else : ?>

		<p><?php _e( 'No draft papers found.', 'social-paper' ); ?></p>

	<?php endif; ?>

	</div>

	<?php
}

/**
 * Get the total number of papers for a user.
 *
 * @param  int  $user_id The user ID
 * @param  bool $include_drafts Should count include drafts?
 * @return int
 */
function cacsp_total_papers_for_user( $user_id = 0, $include_drafts = false ) {

	// get user ID if none passed
	if ( empty( $user_id ) ) {
		$user_id = ( bp_displayed_user_id() ) ? bp_displayed_user_id() : bp_loggedin_user_id();
	}

	//if ( !$count = wp_cache_get( 'cacsp_total_papers_for_user_' . $user_id, 'cacsp' ) ) {

		$args = array(
			'post_type' => 'cacsp_paper',
			'author' => $user_id,
			'post_status' => 'publish',
			'fields' => 'ids',
			'nopaging' => true,
			'orderby' => 'none',
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false
		);

		if ( (int) $user_id === bp_loggedin_user_id() ) {
			$args['post_status'] = array( 'publish', 'private' );
		}

		if ( true === (bool) $include_drafts ) {
			$args['post_status'] = array( 'publish', 'private', 'draft', 'future' );
		}

		// get papers for user
		$papers = get_posts( $args );

		// get count
		$count = bp_core_number_format( count( $papers ) );

		// stash it
		//wp_cache_set( 'cacsp_total_papers_for_user_' . $user_id, $count, 'cacsp' );

	//}

	return $count;

}

/**
 * Output the total paper count for a specified user.
 *
 * @param int $user_id The numeric ID of the user
 */
function cacsp_total_paper_count_for_user( $user_id = 0, $include_drafts = false ) {
	echo cacsp_get_total_paper_count_for_user( $user_id, $include_drafts );
}

/**
 * Return the total paper count for a specified user.
 *
 * @param int $user_id ID of user being queried. Default: displayed user ID.
 *
 * @return int The total paper count for the specified user.
 */
function cacsp_get_total_paper_count_for_user( $user_id = 0, $include_drafts = false ) {
	return apply_filters( 'cacsp_get_total_paper_count_for_user', cacsp_total_papers_for_user( $user_id, $include_drafts ) );
}

/**
 * AJAX callback on the paper directory page to dynamically load papers.
 */
function cacsp_ajax_directory_template_callback() {
	// Bail if not a POST action
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) ) {
		return;
	}

	// Bail if no object passed
	if ( empty( $_POST['object'] ) || 'papers' !== $_POST['object'] ) {
		return;
	}

	$scope = ! empty( $_POST['scope'] ) ? $_POST['scope'] : 'all';

	$args = array(
		'post_type' => 'cacsp_paper',
		'post_status' => 'publish',
	);

	if ( ! empty( $_POST['search_terms'] ) ) {
		$args['s'] = $_POST['search_terms'];
	}

	switch ( $scope ) {
		case 'personal' :
			$args['author'] = bp_loggedin_user_id();
			$args['post_status'] = array( 'publish', 'private', 'draft', 'future' );
			break;

		default :
			$args = apply_filters( 'bp_papers_ajax_query_args', $args, $scope );
			break;
	}

	// perform query
	$GLOBALS['wp_query'] = new WP_Query( $args );

	if ( have_posts() ) :
		cacsp_get_template_part( 'loop-social-paper', 'buddypress' );

	// no papers
	else :
?>

		<div id="message" class="info">
			<p><?php _e( 'Sorry, no papers were found.', 'social-paper' ); ?></p>
		</div>

<?php
	endif;

	exit;
}
add_action( 'wp_ajax_papers_filter', 'cacsp_ajax_directory_template_callback' );
add_action( 'wp_ajax_nopriv_papers_filter', 'cacsp_ajax_directory_template_callback' );

/**
 * Action handler.
 */
function cacsp_profile_action_handler() {
	if ( false == bp_is_current_component( 'papers' ) ) {
		return;
	}

	// if on a subnav slug, bail!
	if ( bp_is_current_action( 'published' ) || bp_is_current_action( 'drafts' ) ) {
		return;
	}

	if ( false !== strpos( wp_get_referer(), get_post_type_archive_link( 'cacsp_paper' ) ) ) {
		$redirect = get_post_type_archive_link( 'cacsp_paper' );
	} else {
		$redirect = bp_displayed_user_domain() . 'papers/';
	}

	switch ( bp_current_action() ) {
		// delete paper
		case 'delete' :
			// action variable must be set
			// if not, redirect to user's papers
			if ( ! bp_action_variable( 0 ) || ! bp_loggedin_user_id() ) {
				bp_core_redirect( $redirect );
				die();
			}

			// nonce check
			check_admin_referer( 'bp_social_paper_' . bp_current_action(), 'bpsp-' . bp_current_action() );

			// sanity check!
			if ( false === current_user_can( 'delete_post', bp_action_variable( 0 ) ) ) {
				bp_core_add_message( __( 'You do not have permission to delete that paper.', 'social-paper' ), 'error' );
				bp_core_redirect( $redirect );
				die();
			}

			$delete = wp_delete_post( bp_action_variable( 0 ), true );

			if ( $delete ) {
				bp_core_add_message( __( 'Paper successfully deleted.', 'social-paper' ) );

			} else {
				bp_core_add_message( __( 'There was an error deleting that paper.', 'social-paper' ), 'error' );
			}

			bp_core_redirect( $redirect );
			die();

			break;

		// publish paper
		case 'publish' :
			// action variable must be set
			// if not, redirect to user's papers
			if ( ! bp_action_variable( 0 ) || ! bp_loggedin_user_id() ) {
				bp_core_redirect( $redirect );
				die();
			}

			// nonce check
			check_admin_referer( 'bp_social_paper_' . bp_current_action(), 'bpsp-' . bp_current_action() );

			// sanity check!
			if ( false === current_user_can( 'edit_post', bp_action_variable( 0 ) ) ) {
				bp_core_add_message( __( 'You do not have permission to publish that paper.', 'social-paper' ), 'error' );
				bp_core_redirect( $redirect );
				die();
			}

			// wp_publish_post() doesn't have a return value
			wp_publish_post( bp_action_variable( 0 ) );

			bp_core_add_message( __( 'Paper successfully published.', 'social-paper' ) );
			bp_core_redirect( $redirect );
			die();

			break;
	}
}
add_action( 'bp_actions', 'cacsp_profile_action_handler' );

/**
 * Template tag to add a button.
 *
 * @param string $type The type of button. Either 'delete' or 'publish'.
 */
function cacsp_add_button( $type = 'delete' ) {
	$r = array(
		'id'                => "bpsp-{$type}",
		'component'         => 'members',
		'must_be_logged_in' => true,
		'block_self'        => false,
		'link_text'         => 'delete' == $type ? __( 'Delete', 'social-paper' ) : __( 'Publish', 'social-paper' ),
		'wrapper_class'     => 'paper-button',
		'link_class'        => 'paper-button',
	);

	// add confirm class just so user can confirm the choice
	if ( 'delete' === $type ) {
		$r['link_class'] .= ' confirm';
	}

	$r['link_href'] = wp_nonce_url(
		trailingslashit( bp_loggedin_user_domain() . 'papers/' . esc_attr( $type ) . '/' . get_post()->ID ),
		"bp_social_paper_{$type}",
		"bpsp-{$type}"
	);

	// Output button
	bp_button( apply_filters( 'bp_social_paper_button_args', $r ) );
}

/** Directory ************************************************************/

/**
 * Adds a 'delete' button to the paper loop.
 */
function cacsp_loop_delete_button() {
	if ( current_user_can( 'delete_paper', get_post()->ID ) ) {
		cacsp_add_button( 'delete' );
	}
}
add_action( 'bp_directory_papers_actions', 'cacsp_loop_delete_button' );

/**
 * Adds a 'publish' button to the paper loop.
 */
function cacsp_loop_publish_button() {
	if ( 'draft' === get_post_status() && current_user_can( 'edit_paper', get_post()->ID ) ) {
		cacsp_add_button( 'publish' );
	}
}
add_action( 'bp_directory_papers_actions', 'cacsp_loop_publish_button' );

/**
 * Add the 'buddypress' CSS class when on the Paper archive page.
 *
 * Fixes display issues for BuddyPress companion stylesheets.
 *
 * @param  array $retval Current CSS classes
 * @return array
 */
function cacsp_directory_add_buddypress_body_class( $retval ) {
	if ( false === is_post_type_archive( 'cacsp_paper' ) ) {
		return $retval;
	}

	$retval[] = 'buddypress';
	return $retval;
}
add_filter( 'body_class', 'cacsp_directory_add_buddypress_body_class' );

/**
 * Alters the CSS post class when on the Paper archive page.
 *
 * Fixes display issues for BuddyPress companion stylesheets.  Most notably,
 * twentyfourteen, twentyfifteen and twentysixteen.
 *
 * @param  array $retval Current CSS classes
 * @return array
 */
function cacsp_directory_post_class_compatibility( $retval ) {
	if ( false === is_post_type_archive( 'cacsp_paper' ) ) {
		return $retval;
	}

	$retval = array_unique( $retval );

	// twentysixteen needs this
	$retval[] = 'type-page';

	// twentyfourteen + twentyfifteen - remove 'has-post-thumbnail' class.
	// This is done because we're tricking the archive template to use the post
	// template and using the post_class() function.  The 'has-post-thumbnail'
	// class styles things differently in twentyfourteen + twentyfifteen.
	$key = array_search( 'has-post-thumbnail', $retval );
	if ( false !== $key ) {
		unset( $retval[ $key ] );
	}

	return $retval;
}
add_filter( 'post_class', 'cacsp_directory_post_class_compatibility', 999 );