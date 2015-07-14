<?php

/**
 * Social Paper Members Profile
 *
 * This file contains code relating to members profiles.
 */

class CACSP_Profile {

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

		$slug = 'papers';
		$parent_url = trailingslashit( $user_domain . $slug );
		$count    = (int) cacsp_get_total_paper_count_for_user();
		$class    = ( 0 === $count ) ? 'no-count' : 'count';
		$nav_text = sprintf( __( 'Papers <span class="%s">%s</span>', 'social-paper' ), esc_attr( $class ), bp_core_number_format( $count )  );

		// create primary nav
		bp_core_new_nav_item( array(
			'name'                    => $nav_text,
			'slug'                    => $slug,
			'item_css_id'             => 'user-papers',
			'show_for_displayed_user' => true,
			'site_admin_only'         => false,
			'position'                => 50,
			'screen_function'         => 'cacsp_profile_screen',
			'default_subnav_slug'     => 'published',
		) );

		// create subnav items
		bp_core_new_subnav_item( array(
			'name'            => __( 'Published', 'social-paper' ),
			'slug'            => 'published',
			'parent_url'      => $parent_url,
			'parent_slug'     => $slug,
			'screen_function' => 'cacsp_profile_screen_published',
			'position'        => 10,
		) );

		bp_core_new_subnav_item( array(
			'name'            => __( 'Drafts', 'social-paper' ),
			'slug'            => 'drafts',
			'parent_url'      => $parent_url,
			'parent_slug'     => $slug,
			'screen_function' => 'cacsp_profile_screen_draft',
			'position'        => 10,
		) );

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

		<p><?php _e( 'Your published papers.', 'social-paper' ); ?></p>

		<ul>

		<?php while ( $profile_query->have_posts() ) : $profile_query->the_post(); ?>
			<li><a href="<?php the_permalink(); ?>" rel="bookmark"><?php the_title(); ?></a></li>
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

		<p><?php _e( 'Your draft papers.', 'social-paper' ); ?></p>

		<ul>

		<?php while ( $profile_query->have_posts() ) : $profile_query->the_post(); ?>
			<li><a href="<?php the_permalink(); ?>" rel="bookmark"><?php the_title(); ?></a></li>
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
