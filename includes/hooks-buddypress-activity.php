<?php

/**
 * BuddyPress Activity integration.
 */

/**
 * Register activity actions.
 *
 * 'new_cacsp_paper' is handled by BP.
 */
function cacsp_register_activity_actions() {
	bp_activity_set_action(
		'cacsp',
		'new_cacsp_comment',
		__( 'Paper comments created', 'social-paper' ),
		'cacsp_format_activity_action',
		__( 'Paper comments created', 'social-paper' ),
		array( 'activity', 'member', 'group', 'member_groups' )
	);

	bp_activity_set_action(
		'cacsp',
		'new_cacsp_edit',
		__( 'Paper edits', 'social-paper' ),
		'cacsp_format_activity_action',
		__( 'Paper edits', 'social-paper' ),
		array( 'activity', 'member', 'group', 'member_groups' )
	);
}
add_action( 'bp_register_activity_actions', 'cacsp_register_activity_actions' );

/**
 * Format activity actions.
 *
 * @param string $action   Activity action as determined by BuddyPress.
 * @param obj    $activity Activity item.
 * @return string
 */
function cacsp_format_activity_action( $action, $activity ) {
	$paper    = new CACSP_Paper( $activity->secondary_item_id );
	$paper_id = $paper->ID;

	if ( ! $paper_id ) {
		return $action;
	}

	$paper_title = $paper->post_title;
	$paper_link  = get_permalink( $paper->ID );
	$user_link   = bp_core_get_userlink( $activity->user_id );

	switch ( $activity->type ) {
		case 'new_cacsp_paper' :
			$action = sprintf(
				__( '%1$s created a new paper %2$s', 'social-paper' ),
				$user_link,
				sprintf( '<a href="%s">%s</a>', esc_url( $paper_link ), esc_html( $paper_title ) )
			);
			break;

		case 'new_cacsp_comment' :
			$comment = get_comment( $activity->item_id );

			if ( ! $comment ) {
				return $action;
			}

			if ( $comment->user_id ) {
				$commenter_link = bp_core_get_userlink( $comment->user_id );
			} elseif ( $comment->comment_author_url ) {
				$commenter_link = sprintf( '<a href="%s">%s</a>', esc_url( $comment->comment_author_url ), esc_html( $comment->comment_author ) );
			} else {
				$commenter_link = esc_html( $comment->comment_author );
			}

			$action = sprintf(
				__( '%1$s commented on the paper %2$s', 'social-paper' ),
				$commenter_link,
				sprintf( '<a href="%s">%s</a>', esc_url( $paper_link ), esc_html( $paper_title ) )
			);
			break;

		case 'new_cacsp_edit' :
			$action = sprintf(
				__( '%1$s edited the paper %2$s', 'social-paper' ),
				$user_link,
				sprintf( '<a href="%s">%s</a>', esc_url( $paper_link ), esc_html( $paper_title ) )
			);
			break;

		default :
			return $action;
	}

	/**
	 * Filters the formatted action for paper activities.
	 *
	 * Used by hooks-buddypress-groups.php to add group information.
	 *
	 * @param string      $action      Formatted action string.
	 * @param obj         $activity    Activity item.
	 * @param CACSP_Paper $paper       Paper object.
	 * @param string      $paper_title Paper title.
	 * @param string      $paper_link  Paper URL.
	 * @param string      $user_link   User link.
	 */
	return apply_filters( 'cacsp_format_activity_action', $action, $activity, $paper, $paper_title, $paper_link, $user_link );
}

/**
 * Create activity items for paper comments.
 *
 * @param int        $comment_id ID of the comment.
 * @param WP_Comment $comment    Comment object.
 */
function cacsp_create_comment_activity( $comment_id, $comment ) {
	// Approved comments only.
	if ( ! isset( $comment->comment_approved ) || 1 != $comment->comment_approved ) {
		return;
	}

	// cacsp_paper comments only.
	$paper = new CACSP_Paper( $comment->comment_post_ID );
	$cp_post_type = $paper->post_type;
	if ( ! $cp_post_type || 'cacsp_paper' !== $cp_post_type ) {
		return;
	}

	$comment_id = (int) $comment->comment_ID;
	$paper_id = (int) $paper->ID;

	// No dupes.
	$existing = bp_activity_get( array(
		'filter_query' => array(
			array( 'column' => 'component', 'value' => 'cacsp' ),
			array( 'column' => 'type', 'value' => 'new_cacsp_comment' ),
			array( 'column' => 'item_id', 'value' => $comment_id ),
			array( 'column' => 'secondary_item_id', 'value' => $paper_id ),
		),
		'update_meta_cache' => false,
	) );

	if ( ! empty( $existing['activities'] ) ) {
		return;
	}

	$activity_id = bp_activity_add( array(
		'content'           => bp_create_excerpt( $comment->comment_content ),
		'component'         => 'cacsp',
		'type'              => 'new_cacsp_comment',
		'primary_link'      => get_comment_link( $comment ),
		'user_id'           => (int) $comment->user_id,
		'item_id'           => $comment_id,
		'secondary_item_id' => $paper_id,
		'hide_sitewide'     => false, // We'll hide with a filter.

	) );

	return $activity_id;
}
add_action( 'wp_insert_comment', 'cacsp_create_comment_activity', 10, 2 );

/**
 * Create activity for approved comments.
 *
 * @param string $new_status
 * @param string $old_status
 * @param object $comment
 */
function cacsp_handle_comment_activity_on_transition_comment_status( $new_status, $old_status, $comment ) {
	if ( 'approved' === $old_status || 'approved' !== $new_status ) {
		return;
	}

	cacsp_create_comment_activity( $comment->comment_ID, $comment );
}
add_action( 'transition_comment_status', 'cacsp_handle_comment_activity_on_transition_comment_status', 5, 3 );

/**
 * Create activity for paper edits.
 *
 * Edit activity is throttled: no more than one activity item per 60 minutes.
 *
 * @param int     $post_id     ID of the post.
 * @param WP_Post $post_after  New post.
 * @param WP_Post $post_before Old post.
 */
function cacsp_create_edit_activity( $post_id, WP_Post $post_after, WP_Post $post_before ) {
	if ( 'cacsp_paper' !== $post_after->post_type ) {
		return;
	}

	// We only want to record edits of published posts. Drafts don't get activity, and BP handles publishing.
	if ( 'publish' !== $post_before->post_status || 'publish' !== $post_after->post_status ) {
		return;
	}

	// The author of the edit is the one who wrote the last revision.
	if ( $revisions = wp_get_post_revisions( $post_id ) ) {
		// Grab the last revision, but not an autosave.
		foreach ( $revisions as $revision ) {
			if ( false !== strpos( $revision->post_name, "{$revision->post_parent}-revision" ) ) {
				$last_revision = $revision;
				break;
			}
		}
	}

	// Either revisions are disabled, or something else has gone wrong. Just use the post author.
	if ( ! isset( $last_revision ) ) {
		$rev_author = $post_after->post_author;
	} else {
		$rev_author = $last_revision->post_author;
	}

	// Throttle.
	$existing = bp_activity_get( array(
		'filter_query' => array(
			array( 'column' => 'component', 'value' => 'cacsp' ),
			array( 'column' => 'type', 'value' => 'new_cacsp_edit' ),
			array( 'column' => 'secondary_item_id', 'value' => $post_id ),
			array( 'column' => 'user_id', 'value' => $rev_author ),
		),
		'update_meta_cache' => false,
		'per_page' => 1,
	) );

	if ( ! empty( $existing['activities'] ) ) {
		/**
		 * Filters the number of seconds in the edit throttle.
		 *
		 * This prevents activity stream flooding by multiple edits of the same paper.
		 *
		 * @param int $throttle_period Defaults to 6 hours.
		 */
		$throttle_period = apply_filters( 'bpeo_event_edit_throttle_period', 6 * HOUR_IN_SECONDS );
		if ( ( time() - strtotime( $existing['activities'][0]->date_recorded ) ) < $throttle_period ) {
			return;
		}
	}

	// Poor man's diff. https://coderwall.com/p/3j2hxq/find-and-format-difference-between-two-strings-in-php
	$old = $post_before->post_content;
	$new = $post_after->post_content;

	$from_start = strspn( $old ^ $new, "\0" );
	$from_end = strspn( strrev( $old ) ^ strrev( $new ), "\0" );

	$old_end = strlen( $old ) - $from_end;
	$new_end = strlen( $new ) - $from_end;

	$start = substr( $new, 0, $from_start );
	$end = substr( $new, $new_end );
	$new_diff = substr( $new, $from_start, $new_end - $from_start );

	// Take a few words before the diff.
	$_start = explode( ' ', $start );
	$_start = implode( ' ', array_slice( $_start, -5 ) );

	$content = bp_create_excerpt( '&hellip;' . $_start . $new_diff . $end );

	$activity_id = bp_activity_add( array(
		'content'           => $content,
		'component'         => 'cacsp',
		'type'              => 'new_cacsp_edit',
		'primary_link'      => get_permalink( $post_id ),
		'user_id'           => $rev_author,
		'item_id'           => get_current_blog_id(), // for compat with 'new_cacsp_paper'
		'secondary_item_id' => $post_id,
		'hide_sitewide'     => false, // We'll hide with a filter.

	) );
}
add_action( 'post_updated', 'cacsp_create_edit_activity', 10, 3 );

/**
 * Access protection in the activity feed.
 *
 * Users should not see activity related to papers to which they do not have access.
 */
function cacsp_access_protection_for_activity_feed( $where_conditions ) {
	$protected_paper_ids = cacsp_get_protected_papers_for_user( bp_loggedin_user_id() );
	if ( ! $protected_paper_ids ) {
		return $where_conditions;
	}

	// DeMorgan says: A & B == ( ! A || ! B )
	$activity_query = new BP_Activity_Query( array(
		'relation' => 'OR',
		array(
			'column' => 'type',
			'value' => array( 'new_cacsp_post', 'new_cacsp_comment' ),
			'compare' => 'NOT IN',
		),
		array(
			'column' => 'secondary_item_id',
			'value' => $protected_paper_ids,
			'compare' => 'NOT IN',
		),
	) );
	$aq_sql = $activity_query->get_sql();

	if ( $aq_sql ) {
		$where_conditions[] = $aq_sql;
	}

	return $where_conditions;
}
add_filter( 'bp_activity_get_where_conditions', 'cacsp_access_protection_for_activity_feed' );
