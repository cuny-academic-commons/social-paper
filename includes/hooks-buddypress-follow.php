<?php
/**
 * BP Follow plugin integration into Social Paper.
 *
 * @package Social_Paper
 * @subpackage Hooks
 */

/** FOLLOW API ***********************************************************/

add_action( 'bp_activity_post_type_published', 'cacsp_auto_follow_on_new_paper', 10, 3 );
//add_action( 'cacsp_added_reader_to_paper',     'cacsp_auto_follow_for_new_reader', 10, 2 );
add_action( 'cacsp_removed_reader_from_paper', 'cacsp_unfollow_for_removed_reader', 10, 2 );

/**
 * Automatically provision users to follow their own written paper.
 *
 * Piggyback off of 'new_cacsp_paper' activity item!
 */
function cacsp_auto_follow_on_new_paper( $activity_id, $post, $activity_args ) {
	if ( 'new_cacsp_paper' !== $activity_args['type'] ) {
		return;
	}

	// Save activity ID to the post while we're here!
	update_post_meta( $activity_args['secondary_item_id'], 'cacsp_activity_id', $activity_id );

	/**
	 * This was when I was investigating usage of SP on multiple sites; this data
	 * was to be used for notifications.
	 *
	 * However, since the Paper directory lists only root blog papers, I stopped
	 * implementing this!
	 *
	// Some activity meta; used for notifications
	// Record this in activity streams.
	$post_url = add_query_arg(
		'p',
		$activity_args['secondary_item_id'],
		trailingslashit( get_home_url( $activity_args['item_id'] ) )
	);

	bp_activity_update_meta( $activity_id, 'post_title', $post->post_title );
	bp_activity_update_meta( $activity_id, 'post_url', $post_url );
	*/

	bp_follow_start_following( array(
		'leader_id'   => $activity_id,
		'follower_id' => $activity_args['user_id'],
		'follow_type' => 'cacsp_paper'
	) );
}

/**
 * Automatically provision readers to follow a paper.
 *
 * Disabled for the moment.
 *
 * @param CACSP_Paper $paper
 * @param int         $user_id
 */
function cacsp_auto_follow_for_new_reader( $paper, $user_id ) {
	if ( empty( $user_id ) ) {
		return;
	}

	bp_follow_start_following( array(
		'leader_id'   => cacsp_follow_get_activity_id( $paper->id ),
		'follower_id' => $user_id,
		'follow_type' => 'cacsp_paper'
	) );
}

/**
 * Unfollow a user from a paper when the user is removed from the Readers list.
 *
 * @since 1.0.0
 *
 * @param CACSP_Paper $paper
 * @param int         $user_id
 */
function cacsp_unfollow_for_removed_reader( $paper, $user_id ) {
	if ( empty( $user_id ) ) {
		return;
	}

	bp_follow_stop_following( array(
		'leader_id'   => cacsp_follow_get_activity_id( $paper->id ),
		'follower_id' => $user_id,
		'follow_type' => 'cacsp_paper'
	) );
}

/** NOTIFICATIONS ********************************************************/

add_filter( 'cacsp_custom_notification_format', 'cacsp_follow_format_notifications', 10, 2 );
add_action( 'bp_follow_stop_following_cacsp_paper', 'cacsp_follow_notifications_remove_on_unfollow' );
add_action( 'bp_actions', 'cacsp_follow_notifications_mark_follower_profile_as_read' );

/**
 * Formats follow notifications.
 *
 * @param  bool  $retval False by default.
 * @param  array $args   Notification args.
 * @return array
 */
function cacsp_follow_format_notifications( $retval, $args ) {
	if ( 0 !== strpos( $args['action'], 'follow_paper_' ) ) {
		return $retval;
	}

	$paper = new CACSP_Paper( $args['paper_id'] );

	if ( (int) $args['count'] > 1 ) {
		$text = sprintf( __( '%d members started following your paper "%s"', 'social-paper' ), $args['count'], $paper->post_title );
		$link = add_query_arg( 'spfilter', 'follow_paper', bp_get_notifications_unread_permalink() );
	} else {
		$text = sprintf( __( '%s started following your paper "%s"', 'social-paper' ), bp_core_get_user_displayname( $args['secondary_item_id'] ), $paper->post_title );

		$link = add_query_arg( 'spf_read', $paper->id, bp_core_get_user_domain( $args['secondary_item_id'] ) );

		if ( bp_is_current_action( 'read' ) ) {
			// If we're in the notifications loop, remove query arg
			if ( ! did_action( 'bp_after_member_body' ) ) {
				$link = remove_query_arg( 'spf_read', $link );
			}
		}
	}

	return array(
		'text' => $text,
		'link' => $link
	);
}

/**
 * Removes notification when a user unfollows another user.
 *
 * @param BP_Follow $follow
 */
function cacsp_follow_notifications_remove_on_unfollow( BP_Follow $follow ) {
	$post_id = cacsp_follow_get_paper_ids_from_activity_ids( $follow->leader_id );

	if ( ! empty( $post_id[0] ) ) {
		$post_id = $post_id[0];
	} else {
		return $retval;
	}

	$paper = get_post( $post_id );

	// $user_id, $item_id, $component_name, $component_action, $secondary_item_id = false
	bp_notifications_delete_notifications_by_item_id( $paper->post_author, $paper->ID, 'cacsp', "follow_paper_{$paper->ID}", $follow->follower_id );
}

/**
 * Mark notification as read when a user visits their paper follower's page.
 *
 * Looks for our special 'spf_read' query arg to do this.
 */
function cacsp_follow_notifications_mark_follower_profile_as_read() {
	if ( ! bp_is_user() ) {
		return;
	}

	if ( ! isset( $_GET['spf_read'] ) ) {
		return;
	}

	$paper_id = (int) $_GET['spf_read'];
	if ( empty( $paper_id ) ) {
		return;
	}

	// mark notification as read
	cacsp_mark_notifications_read( bp_loggedin_user_id(), $paper_id, "follow_paper_{$paper_id}", bp_displayed_user_id() );
}

/** NAV ******************************************************************/

add_action( 'bp_setup_nav', 'cacsp_follow_setup_nav', 101 );
add_action( 'bp_activity_admin_nav', 'cacsp_follow_activity_admin_nav', 11 );
add_action( 'bp_papers_admin_nav', 'cacsp_follow_papers_admin_nav' );

/**
 * Setup profile nav.
 */
function cacsp_follow_setup_nav() {
	// Determine user to use
	if ( bp_displayed_user_domain() ) {
		$user_domain = bp_displayed_user_domain();
	} elseif ( bp_loggedin_user_domain() ) {
		$user_domain = bp_loggedin_user_domain();
	} else {
		return;
	}

	// Add papers sub nav item
	bp_core_new_subnav_item( array(
		'name'            => __( 'Followed Papers', 'social-paper' ),
		'slug'            => 'follow',
		'parent_url'      => trailingslashit( $user_domain . 'papers' ),
		'parent_slug'     => 'papers',
		'screen_function' => 'cacsp_followed_papers_screen',
		'position'        => 20,
	) );

	// Add activity sub nav item
	bp_core_new_subnav_item( array(
		'name'            => __( 'Papers', 'social-paper' ),
		'slug'            => 'papers',
		'parent_url'      => trailingslashit( $user_domain . bp_get_activity_slug() ),
		'parent_slug'     => bp_get_activity_slug(),
		'screen_function' => 'bp_activity_screen_my_activity',
		'position'        => 23,
		'item_css_id'     => 'activity-papers'
	) );
}

/**
 * Inject "Papers" nav item to WP adminbar's "Activity" main nav.
 *
 * @param array $retval
 * @return array
 */
function cacsp_follow_activity_admin_nav( $retval ) {
	if ( ! is_user_logged_in() ) {
		return $retval;
	}

	if ( bp_is_active( 'activity' ) ) {
		$new_item = array(
			'parent' => 'my-account-activity',
			'id'     => 'my-account-activity-papers',
			'title'  => __( 'Papers', 'social-paper' ),
			'href'   => bp_loggedin_user_domain() . bp_get_activity_slug() . '/papers/',
		);

		$inject = array();
		$offset = 5;

		$inject[$offset] = $new_item;
		$retval = array_merge(
			array_slice( $retval, 0, $offset, true ),
			$inject,
			array_slice( $retval, $offset, NULL, true )
		);
	}

	return $retval;
}

/**
 * Inject "Followed Papers" nav item to WP adminbar's "Papers" main nav.
 *
 * @param array $retval
 * @return array
 */
function cacsp_follow_papers_admin_nav( $retval ) {
	if ( ! is_user_logged_in() ) {
		return $retval;
	}

	$new_item = array(
		'parent' => 'my-account-papers',
		'id'     => 'my-account-papers-follow',
		'title'  => __( 'Followed Papers', 'social-paper' ),
		'href'   => bp_loggedin_user_domain() . '/papers/follow/',
	);

	$inject = array();
	$offset = 3;

	$inject[$offset] = $new_item;
	$retval = array_merge(
		array_slice( $retval, 0, $offset, true ),
		$inject,
		array_slice( $retval, $offset, NULL, true )
	);

	return $retval;
}
/**
 * Screen loader for a user's "Papers > Followed Papers" screen.
 */
function cacsp_followed_papers_screen() {
	add_action( 'bp_template_content', 'cacsp_followed_papers_screen_content' );

	// load template
	bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
}

/**
 * Screen content for a user's "Papers > Followed Papers" screen.
 */
function cacsp_followed_papers_screen_content() {
	$activity_ids = bp_follow_get_following( array(
		'user_id' => bp_displayed_user_id(),
		'follow_type' => 'cacsp_paper'
	) );

	if ( ! empty( $activity_ids ) ) {
		$paper_ids = cacsp_follow_get_paper_ids_from_activity_ids( $activity_ids );
	} else {
		$paper_ids = array( 0 );
	}

	// perform query for this user
	$q = new WP_Query( array(
		'post_type' => 'cacsp_paper',
		'post__in' => $paper_ids,
		'post_status' => bp_is_my_profile() ? array( 'publish', 'private' ) : array( 'publish' )
	) );

	?>
	<div class="entry-content">

	<?php if ( $q->have_posts() ) : ?>

		<ul class="item-list">

		<?php while ( $q->have_posts() ) : $q->the_post(); ?>
			<?php cacsp_get_template_part( 'list-social-paper', 'buddypress' ); ?>
		<?php endwhile; ?>

		</ul>

	<?php else : ?>

		<p><?php _e( 'No followed papers found.', 'social-paper' ); ?></p>

	<?php endif; ?>

	</div>

<?php
}

/** LOOP FILTERING *******************************************************/

add_action( 'bp_before_activity_type_tab_favorites', 'cacsp_follow_add_activity_directory_tab' );
add_filter( 'bp_activity_set_papers_scope_args', 'cacsp_follow_filter_activity_scope', 10, 2 );
add_filter( 'bp_activity_user_can_delete', 'cacsp_activity_user_cannot_delete_new_paper_activity_items', 10, 2 );

add_action( 'bp_papers_directory_tabs',  'cacsp_follow_add_paper_directory_tab' );
add_filter( 'bp_papers_ajax_query_args', 'cacsp_follow_paper_directory_ajax_query_args', 10, 2 );
add_filter( 'cacsp_directory_action_metadata', 'cacsp_follow_add_follower_count_to_action_metadata', 20 );

/**
 * Adds a "My Papers" tab to the activity directory.
 */
function cacsp_follow_add_activity_directory_tab() {
	/*
	$count = bp_follow_get_the_following_count( array(
		'user_id'     => bp_loggedin_user_id(),
		'follow_type' => 'activity',
	) );


	if ( empty( $count ) ) {
		return;
	}
	*/

	// Adding a count is confusing when you can follow comments on papers...
	$count = 0;
	?>
	<li id="activity-papers"><a href="<?php echo esc_url( bp_loggedin_user_domain() . bp_get_activity_slug() . '/papers/' ); ?>"><?php printf( __( 'My Papers', 'social-paper' ), (int) $count ) ?></a></li><?php
}

/**
 * Set up activity arguments for use with the 'papers' scope.
 *
 * For details on the syntax, see {@link BP_Activity_Query}.
 *
 * @param array $retval Empty array by default
 * @param array $filter Current activity arguments
 * @return array
 */
function cacsp_follow_filter_activity_scope( $retval = array(), $filter = array() ) {
	// Determine the user_id
	if ( ! empty( $filter['user_id'] ) ) {
		$user_id = $filter['user_id'];
	} else {
		$user_id = bp_displayed_user_id()
			? bp_displayed_user_id()
			: bp_loggedin_user_id();
	}

	// Get activity IDs that the user is following
	$following_ids = bp_follow_get_following( array(
		'user_id'     => $user_id,
		'follow_type' => 'cacsp_paper',
	) );

	// No papers
	if ( empty( $following_ids ) ) {
		$following_ids = array( 0 );

		$clause = array(
			'column'  => 'id',
			'compare' => 'IN',
			'value'   => $following_ids
		);

	// Fetch post IDs of papers.
	} else {
		$post_ids = cacsp_follow_get_paper_ids_from_activity_ids( $following_ids );

		$clause = array(
			'relation' => 'OR',

			// 'new_cacsp_paper'
			array(
				'column'  => 'id',
				'compare' => 'IN',
				'value'   => $following_ids
			),

			// 'new_cacsp_comment'
			array(
				'relation' => 'AND',
				array(
					'column' => 'type',
					'value'  => 'new_cacsp_comment'
				),
				array(
					'column'  => 'secondary_item_id',
					'compare' => 'IN',
					'value'   => $post_ids
				),
			),

			// 'new_cacsp_edit'
			array(
				'relation' => 'AND',
				array(
					'column' => 'type',
					'value'  => 'new_cacsp_edit'
				),
				array(
					'column'  => 'secondary_item_id',
					'compare' => 'IN',
					'value'   => $post_ids
				),
			),

			// 'cacsp_paper_added_to_group'
			array(
				'relation' => 'AND',
				array(
					'column' => 'type',
					'value'  => 'cacsp_paper_added_to_group'
				),
				array(
					'column'  => 'secondary_item_id',
					'compare' => 'IN',
					'value'   => $post_ids
				),
			),
		);
	}

	// Should we show all items regardless of sitewide visibility?
	$show_hidden = array();
	if ( ! empty( $user_id ) && ( $user_id !== bp_loggedin_user_id() ) ) {
		$show_hidden = array(
			'column' => 'hide_sitewide',
			'value'  => 0
		);
	}

	$retval = array(
		'relation' => 'AND',
		$clause,
		$show_hidden,

		// overrides
		'override' => array(
			'filter'      => array( 'user_id' => 0 ),
			'show_hidden' => true
		),
	);

	return $retval;
}

/**
 * Users should not able to delete 'new_cacsp_paper' activity items.
 *
 * Paper following relies on this activity item, so we enforce this here.
 *
 * @param  bool                 $retval   Current permissions.
 * @param  BP_Activity_Activity $activity Current activity entry.
 * @return bool
 */
function cacsp_activity_user_cannot_delete_new_paper_activity_items( $retval, $activity ) {
	if ( 'new_cacsp_paper' !== $activity->type ) {
		return $retval;
	}

	return false;
}

/**
 * Adds a "Papers I Follow" tab to the paper directory.
 */
function cacsp_follow_add_paper_directory_tab() {
	if ( ! is_user_logged_in() ) {
		return;
	}

	$count = bp_follow_get_the_following_count( array(
		'user_id'     => bp_loggedin_user_id(),
		'follow_type' => 'cacsp_paper',
	) );

	if ( empty( $count ) ) {
		return;
	}
?>

	<li id="papers-follow"><a href="<?php echo bp_loggedin_user_domain() . 'papers/follow/'; ?>"><?php printf( __( 'Papers I Follow <span>%s</span>', 'social-paper' ), $count ); ?></a></li>

<?php
}

/**
 * AJAX query listener for our "Papers I Follow" tab.
 *
 * @param  array  $retval Current WP_Query args.
 * @param  string $scope  Current scope.
 * @return array
 */
function cacsp_follow_paper_directory_ajax_query_args( $retval, $scope ) {
	if ( 'follow' !== $scope ) {
		return $retval;
	}

	// Show public and private papers
	$retval['post_status'] = array( 'publish', 'private' );

	// Get our paper IDs
	$activity_ids = bp_follow_get_following( array(
		'user_id' => bp_loggedin_user_id(),
		'follow_type' => 'cacsp_paper'
	) );

	if ( ! empty( $activity_ids ) ) {
		$paper_ids = cacsp_follow_get_paper_ids_from_activity_ids( $activity_ids );
	} else {
		$paper_ids = array( 0 );
	}

	$retval['post__in'] = $paper_ids;

	return $retval;
}

/**
 * Add 'x followers' to action metadata in paper directories.
 *
 * @since 1.0.0
 *
 * @param array $chunks Action metadata chunks.
 * @return array
 */
function cacsp_follow_add_follower_count_to_action_metadata( $chunks ) {
	$activity_id = cacsp_follow_get_activity_id( get_post()->ID );
	$count = bp_follow_get_the_followers_count( array(
		'object_id'   => $activity_id,
		'follow_type' => 'cacsp_paper'
	) );

	if ( 1 == $count ) {
		$chunks['followers'] = __( '1 follower', 'social-paper' );
	} elseif ( 1 < $count ) {
		$chunks['followers'] = sprintf( _n( '%s follower', '%s followers', $count, 'social-paper' ), number_format_i18n( $count ) );
	}

	return $chunks;
}

/** RSS ******************************************************************/

add_action( 'bp_actions', 'cacsp_follow_rss_handler' );
add_filter( 'bp_get_sitewide_activity_feed_link', 'cacsp_follow_activity_feed_url_filter' );
add_filter( 'bp_dtheme_activity_feed_url',        'cacsp_follow_activity_feed_url_filter' );
add_filter( 'bp_legacy_theme_activity_feed_url',  'cacsp_follow_activity_feed_url_filter' );
add_filter( 'bp_get_activities_member_rss_link',  'cacsp_follow_activity_feed_url_filter' );

/**
 * RSS handler for a user's followed papers.
 */
function cacsp_follow_rss_handler() {
	// only available in BP 1.8+
	if ( ! class_exists( 'BP_Activity_Feed' ) ) {
		return;
	}

	if ( ! bp_is_user_activity() || ! bp_is_current_action( 'papers' ) || ! bp_is_action_variable( 'feed', 0 ) ) {
		return;
	}

	$args = array(
		'user_id' => bp_displayed_user_id(),
		'scope'   => 'papers'
	);

	// setup the feed
	buddypress()->activity->feed = new BP_Activity_Feed( array(
		'id'            => 'papers',

		/* translators: User's following activity RSS title - "[Site Name] | [User Display Name] | Followed Activity" */
		'title'         => sprintf( __( '%1$s | %2$s | Followed Papers', 'bp-follow' ), bp_get_site_name(), bp_get_displayed_user_fullname() ),

		'link'          => trailingslashit( bp_displayed_user_domain() . bp_get_activity_slug() . '/papers' ),
		'description'   => sprintf( __( "Activity feed for papers that %s is following.", 'buddypress' ), bp_get_displayed_user_fullname() ),
		'activity_args' => $args,
	) );
}

/**
 * Sets the "RSS" feed URL for the tab on the Sitewide Activity page.
 *
 * This occurs when the "Followed Papers" tab is clicked on the Sitewide
 * Activity page or when the activity scope is already set to "followblogs".
 *
 * Only do this for BuddyPress 1.8+.
 *
 * @param string $retval The feed URL.
 * @return string The feed URL.
 */
function cacsp_follow_activity_feed_url_filter( $retval ) {
	// only available in BP 1.8+
	if ( ! class_exists( 'BP_Activity_Feed' ) ) {
		return $retval;
	}

	// This filters the RSS link when on a user's "Activity > Papers" page
	if ( 'bp_get_activities_member_rss_link' === current_filter() && '' == $retval && bp_is_current_action( 'papers' ) ) {
		return bp_displayed_user_domain() . bp_get_activity_slug() . '/papers/feed/';
	}

	// this is done b/c we're filtering 'bp_get_sitewide_activity_feed_link' and
	// we only want to alter the feed link for the "RSS" tab
	if ( ! defined( 'DOING_AJAX' ) && ! did_action( 'bp_before_directory_activity' ) ) {
		return $retval;
	}

	// get the activity scope
	$scope = ! empty( $_COOKIE['bp-activity-scope'] ) ? $_COOKIE['bp-activity-scope'] : false;

	if ( $scope == 'papers' && bp_loggedin_user_id() ) {
		$retval = bp_loggedin_user_domain() . bp_get_activity_slug() . '/papers/feed/';
	}

	return $retval;
}

/** BUTTONS **************************************************************/

add_action( 'bp_directory_papers_actions', 'cacsp_follow_add_follow_button_to_paper_loop', 50 );
add_filter( 'bp_follow_activity_message_cacsp_paper', 'cacsp_follow_filter_feedback_message', 10, 4 );
add_action( 'cacsp_paper_actions', 'cacsp_follow_add_follow_button_to_single_paper' );
add_action( 'template_redirect', 'cacsp_catch_follow_requests' );

/**
 * Adds a follow button to the paper loop.
 */
function cacsp_follow_add_follow_button_to_paper_loop() {
	// Don't show this for drafts.
	if ( 'draft' === get_post()->post_status ) {
		return;
	}

	// Authors shouldn't see a follow button for their own papers.
	if ( bp_loggedin_user_id() === (int) get_post()->post_author ) {
		return;
	}

	// Button time!
	bp_follow_activity_button( array(
		'leader_id' => cacsp_follow_get_activity_id( get_post()->ID ),
		'wrapper_class' => 'paper-button',
		'link_class' => '',
		'wrapper' => 'div'
	) );
}

/**
 * Adds a follow button to single papers.
 */
function cacsp_follow_add_follow_button_to_single_paper() {
	// Don't show this for drafts.
	if ( 'draft' === get_post()->post_status ) {
		return;
	}

	// Only logged-in users can follow papers.
	if ( ! is_user_logged_in() ) {
		return;
	}

	// Authors shouldn't see a follow button for their own papers.
	if ( bp_loggedin_user_id() === (int) get_post()->post_author ) {
		return;
	}

	// Set up the button arguments. (Should maybe break out button into separate function.)
	$activity_id = (int) cacsp_follow_get_activity_id( get_post()->ID );
	$is_following = bp_follow_is_following( array(
		'leader_id'   => $activity_id,
		'follower_id' => bp_loggedin_user_id(),
		'follow_type' => 'cacsp_paper',
	) );

	if ( $is_following ) {
		$class = 'following';
		$action = 'unfollow';
		$link_text = _x( 'Unfollow', 'Follow paper button', 'social-paper' );
	} else {
		$class = 'not-following';
		$action = 'follow';
		$link_text = _x( 'Follow', 'Follow paper button', 'social-paper' );
	}

	// Not using bp_button() because we don't have BP styles to fall back on anyway.
	$button = sprintf(
		'<form method="post" action="%s">
		<button class="%s">%s</button>
		<input type="hidden" name="follow-action" value="%s" />
		<input type="hidden" name="follow-activity-id" value="%s" />
		%s
		</form>',
		get_permalink( get_post() ),
		esc_attr( $class ),
		esc_html( $link_text ),
		esc_html( $action ),
		esc_attr( $activity_id ),
		wp_nonce_field( 'cacsp_follow_' . $action . '_' . $activity_id, 'cacsp_follow_nonce', false, false ),
		esc_html( $action )
	);

	echo $button;
}

/**
 * Filters the feedback message when a follow button is clicked.
 *
 * @param string $retval       Current message.
 * @param string $action       Current follow action. Either 'follow' or 'unfollow'.
 * @param int    $activity_id  Activity ID relating to the item being followed/unfollowed.
 * @param string $message_type Either 'success' or 'error'.
 */
function cacsp_follow_filter_feedback_message( $retval = '', $action = '', $activity_id = 0, $message_type = '' ) {
	$activity = new BP_Activity_Activity( $activity_id );

	// Sanity check!
	if ( 'new_cacsp_paper' !== $activity->type ) {
		return $retval;
	}

	$post = get_post( $activity->secondary_item_id );

	if ( 'success' === $message_type ) {
		if ( 'follow' === $action ) {
			$retval = sprintf( __( 'You are now following the paper "%s"', 'bp-follow' ), $post->post_title );
		} else {
			$retval = sprintf( __( 'You are no longer following the paper "%s"', 'bp-follow' ), $post->post_title );
		}

	} else {
		if ( 'follow' === $action ) {
			$retval = sprintf( __( 'You are already following the paper "%s"', 'bp-follow' ), $post->post_title );
		} else {
			$retval = sprintf( __( 'You were not following the paper "%s"', 'bp-follow' ), $post->post_title );
		}
	}

	return $retval;
}

/**
 * Catch and process follow/unfollow requests on single papers.
 *
 * @since 1.0.0
 */
function cacsp_catch_follow_requests() {
	if ( ! cacsp_is_page() ) {
		return;
	}

	if ( ! isset( $_POST['cacsp_follow_nonce'] ) || ! isset( $_POST['follow-action'] ) || ! isset( $_POST['follow-activity-id'] ) ) {
		return;
	}

	$activity_id = intval( $_POST['follow-activity-id'] );
	$action = stripslashes( $_POST['follow-action'] );
	if ( ! wp_verify_nonce( $_POST['cacsp_follow_nonce'], 'cacsp_follow_' . $action . '_' . $activity_id ) ) {
		return;
	}

	if ( 'follow' === $action ) {
		$result = bp_follow_start_following( array(
			'leader_id'   => $activity_id,
			'follower_id' => bp_loggedin_user_id(),
			'follow_type' => 'cacsp_paper',
		) );
		$redirect = add_query_arg( 'followed', intval( $result ), get_permalink( get_post() ) );
	} elseif ( 'unfollow' === $action ) {
		$result = bp_follow_stop_following( array(
			'leader_id'   => $activity_id,
			'follower_id' => bp_loggedin_user_id(),
			'follow_type' => 'cacsp_paper',
		) );
		$redirect = add_query_arg( 'unfollowed', intval( $result ), get_permalink( get_post() ) );
	}

	wp_redirect( $redirect );
	die();
}

/** CACHE ****************************************************************/

add_action( 'bp_follow_setup_globals',               'cacsp_follow_setup_global_cachegroups' );
add_action( 'bp_follow_start_following_cacsp_paper', 'cacsp_follow_clear_cache_on_follow', 99 );
add_action( 'bp_follow_stop_following_cacsp_paper',  'cacsp_follow_clear_cache_on_follow', 99 );
add_action( 'bp_follow_before_remove_data',          'cacsp_follow_clear_cache_on_user_delete' );
add_action( 'bp_activity_after_delete',              'cacsp_follow_clear_cache_on_activity_delete' );

/**
 * Register global cachegroups.
 */
function cacsp_follow_setup_global_cachegroups() {
	// Counts
	buddypress()->follow->global_cachegroups[] = 'bp_follow_user_cacsp_paper_following_count';
	buddypress()->follow->global_cachegroups[] = 'bp_follow_cacsp_paper_followers_count';

	// Query
	buddypress()->follow->global_cachegroups[] = 'bp_follow_user_cacsp_paper_following_query';
	buddypress()->follow->global_cachegroups[] = 'bp_follow_cacsp_paper_followers_query';
}

/**
 * Clear cache when a paper is followed/unfollowed.
 *
 * @param BP_Follow $follow
 */
function cacsp_follow_clear_cache_on_follow( BP_Follow $follow ) {
	// clear followers count for activity
	wp_cache_delete( $follow->leader_id,   'bp_follow_cacsp_paper_followers_count' );

	// clear following activity count for user
	wp_cache_delete( $follow->follower_id, 'bp_follow_user_cacsp_paper_following_count' );

	// clear queried followers / following
	wp_cache_delete( $follow->leader_id,   'bp_follow_cacsp_paper_followers_query' );
	wp_cache_delete( $follow->follower_id, 'bp_follow_user_cacsp_paper_following_query' );
}

/**
 * Clear paper cache when a user is deleted.
 *
 * @param int $user_id The user ID being deleted
 */
function cacsp_follow_clear_cache_on_user_delete( $user_id = 0 ) {
	// delete user's paper follow count
	wp_cache_delete( $user_id, 'bp_follow_user_cacsp_paper_following_count' );

	// delete queried papers that user was following
	wp_cache_delete( $user_id, 'bp_follow_user_cacsp_paper_following_query' );

	// delete each paper's followers count that the user was following
	$aids = BP_Follow::get_following( $user_id, 'cacsp_paper' );
	if ( ! empty( $aids ) ) {
		foreach ( $aids as $aid ) {
			wp_cache_delete( $aid, 'bp_follow_cacsp_paper_followers_count' );
		}
	}
}

/**
 * Clear cache when activity item is deleted.
 */
function cacsp_follow_clear_cache_on_activity_delete( $activities ) {
	// Pluck the activity IDs out of the $activities array.
	$activity_ids = wp_parse_id_list( wp_list_pluck( $activities, 'id' ) );

	// See if any of the deleted activity IDs were being followed
	$sql  = 'SELECT leader_id, follower_id FROM ' . buddypress()->follow->table_name . ' ';
	$sql .= 'WHERE leader_id IN (' . implode( ',', wp_parse_id_list( $activity_ids ) ) . ') ';
	$sql .= "AND follow_type = 'cacsp_paper'";

	$followed_ids = $GLOBALS['wpdb']->get_results( $sql );
	if ( empty( $followed_ids ) ) {
		return;
	}

	foreach ( $followed_ids as $activity ) {
		// clear followers count for paper
		wp_cache_delete( $activity->leader_id, 'bp_follow_cacsp_paper_followers_count' );

		// clear queried followers for paper
		wp_cache_delete( $activity->leader_id, 'bp_follow_cacsp_paper_followers_query' );

		// delete user's paper follow count
		wp_cache_delete( $activity->follower_id, 'bp_follow_user_cacsp_paper_following_count' );

		// delete queried papers that user was following
		wp_cache_delete( $activity->follower_id, 'bp_follow_user_cacsp_paper_following_query' );

		// Delete the follow entry
		// @todo Need a mass bulk-delete method
		bp_follow_stop_following( array(
			'leader_id'   => $activity->leader_id,
			'follower_id' => $activity->follower_id,
			'follow_type' => 'cacsp_paper'
		) );
	}
}

/** UTILITY **************************************************************/

/**
 * Grabs the post IDs from 'new_cacsp_paper' activity items.
 *
 * @param  array $activity_ids Activity IDs to grab
 * @return array
 */
function cacsp_follow_get_paper_ids_from_activity_ids( $activity_ids = array() ) {
	$post_ids = array();

	$activity_ids = (array) $activity_ids;

	// This block is similar to BP_Activity_Activity:get_activity_data().
	$uncached_ids = bp_get_non_cached_ids( $activity_ids, 'bp_activity' );
	$cached_ids   = array_diff( $activity_ids, $uncached_ids );

	// Fetch cached activity items to grab the saved post ID.
	foreach ( $cached_ids as $cid ) {
		$a = wp_cache_get( $cid, 'bp_activity' );

		if ( 'new_cacsp_paper' === $a->type ) {
			$post_ids[] = $a->secondary_item_id;
		}
	}

	// Fetch noncached activity items.  Whee!
	if ( ! empty( $uncached_ids ) ) {
		global $bp, $wpdb;

		// Format the activity ID's for use in the query below.
		$uncached_ids_sql = implode( ',', wp_parse_id_list( $uncached_ids ) );

		// Fetch data from activity table, preserving order.
		$queried_adata = $wpdb->get_results( "SELECT * FROM {$bp->activity->table_name} WHERE id IN ({$uncached_ids_sql})");

		foreach ( (array) $queried_adata as $adata ) {
			// Grab the post ID
			if ( 'new_cacsp_paper' === $adata->type ) {
				$post_ids[] = $adata->secondary_item_id;
			}

			// Since we're here, might as well cache! :)
			wp_cache_set( $adata->id, $adata, 'bp_activity' );
		}
	}

	// Sanity check!
	return array_unique( $post_ids );
}

/**
 * Helper function to grab the activity ID from a paper post ID.
 *
 * If activity item doesn't exist, we create it for the paper.
 *
 * @param  int $post_id The paper post ID
 * @return int
 */
function cacsp_follow_get_activity_id( $post_id ) {
	$activity_id = get_post_meta( $post_id, 'cacsp_activity_id', true );

	// Save activity ID into post meta; this is mostly to backfill older papers
	// during development testing.
	// @todo remove this block entirely before release
	if ( empty( $activity_id ) ) {
		$post = get_post( $post_id );

		$activity_id = bp_activity_get_activity_id( array(
			'type'              => 'new_cacsp_paper',
			'item_id'           => get_current_blog_id(),
			'secondary_item_id' => $post_id,
		) );

		// Still empty? Create activity entry for the paper.
		if ( empty( $activity_id ) ) {
			$activity_id = bp_activity_post_type_publish( $post_id, $post );
		}

		// Update post meta
		update_post_meta( $post_id, 'cacsp_activity_id', $activity_id );

		/**
		 * See cacsp_auto_follow_on_new_paper() for this part.
		 *
		// Activity meta
		$post_url = add_query_arg(
			'p',
			$activity_args['secondary_item_id'],
			trailingslashit( get_home_url() )
		);

		bp_activity_update_meta( $activity_id, 'post_title', $post->post_title );
		bp_activity_update_meta( $activity_id, 'post_url', $post_url );
		*/
	}

	return $activity_id;
}
