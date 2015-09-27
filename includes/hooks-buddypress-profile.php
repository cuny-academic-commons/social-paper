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
		$count    = (int) cacsp_get_total_paper_count_for_user();
		$class    = ( 0 === $count ) ? 'no-count' : 'count';
		$nav_text = sprintf( __( 'Papers <span class="%s">%s</span>', 'social-paper' ), esc_attr( $class ), bp_core_number_format( $count )  );

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

	// perform query for this user
	$profile_query = new WP_Query( array(
		'post_type' => 'cacsp_paper',
		'author' => $user_id,
		'post_status' => 'publish',
	) );

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
		'post_status' => 'draft',
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
 * Get the total number of papers for a user
 *
 * @return int $count Total paper count for a user
 */
function cacsp_total_papers_for_user( $user_id = 0 ) {

	// get user ID if none passed
	if ( empty( $user_id ) ) {
		$user_id = ( bp_displayed_user_id() ) ? bp_displayed_user_id() : bp_loggedin_user_id();
	}

	//if ( !$count = wp_cache_get( 'cacsp_total_papers_for_user_' . $user_id, 'cacsp' ) ) {

		// get papers for user
		$papers = get_posts( array(
			'post_type' => 'cacsp_paper',
			'author' => $user_id,
			'post_status' => 'publish',
			'fields' => 'ids',
			'nopaging' => true,
			'orderby' => 'none',
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false
		) );

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
function cacsp_total_paper_count_for_user( $user_id = 0 ) {
	echo cacsp_get_total_paper_count_for_user( $user_id );
}

/**
 * Return the total paper count for a specified user.
 *
 * @param int $user_id ID of user being queried. Default: displayed user ID.
 *
 * @return int The total paper count for the specified user.
 */
function cacsp_get_total_paper_count_for_user( $user_id = 0 ) {
	return apply_filters( 'cacsp_get_total_paper_count_for_user', cacsp_total_papers_for_user( $user_id ) );
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
			$args['post_status'] = array( 'publish', 'private' );
			break;

		default :
			$args = apply_filters( 'bp_papers_ajax_query_args', $args, $scope );
			break;
	}

	// perform query
	$GLOBALS['wp_query'] = new WP_Query( $args );

	if ( have_posts() ) : ?>

		<?php cacsp_pagination(); ?>

		<ul class="item-list">

		<?php while ( have_posts() ) : the_post(); ?>
			<?php cacsp_get_template_part( 'list-social-paper', 'buddypress' ); ?>
		<?php endwhile; ?>

		</ul>

		<?php cacsp_pagination( 'bottom' ); ?>

<?php
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
