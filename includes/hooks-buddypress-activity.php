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
 * Access protection in the activity feed.
 *
 * Users should not see activity related to papers to which they do not have access.
 */
function cacsp_access_protection_for_activity_feed( $where_conditions ) {
	$protected_paper_ids = cacsp_get_protected_papers_for_user( bp_loggedin_user_id() );
	if ( ! $protected_paper_ids ) {
		return $where_conditions;
	}

	// DeMorgan says: A & B == ! ( ! A || ! B )
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
