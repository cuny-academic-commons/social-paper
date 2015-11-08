<?php

/**
 * Integration with the BuddyPress Notifications component.
 *
 * @since 1.0.0
 */

/**
 * Format notifications.
 *
 * @since 1.0.0
 *
 */
function cacsp_format_notifications( $action, $paper_id, $secondary_item_id, $count, $format = 'string' ) {
	$paper = new CACSP_Paper( $paper_id );

	switch ( $action ) {
		case 'added_reader' :
			if ( (int) $count > 1 ) {
				$text = sprintf( _n( 'You have been added as a reader on %s paper', 'You have been added as a reader to %s papers', $count, 'social-paper' ), $count );

				// @todo Some directory.
				$link = '';
			} else {
				$text = sprintf( __( '%s has added you as a reader on the paper "%s"', 'social-paper' ), bp_core_get_user_displayname( $secondary_item_id ), $paper->post_title );
				$link = get_permalink( $paper_id );
			}

			break;

		case 'mypaper_comment' :
			if ( (int) $count > 1 ) {
				$text = sprintf( _n( 'You have %s comment on your papers', 'You have %s comments on your papers', $count, 'social-paper' ), $count );

				// @todo Notifications directory? Maybe only if corresponding to more than one paper?
				$link = get_comment_link( $secondary_item_id );
			} else {
				$paper = new CACSP_Paper( $paper_id );
				$text = sprintf( __( 'You have a new comment on your paper "%s"', 'social-paper' ), $paper->post_title );
				$link = get_comment_link( $secondary_item_id );
			}

			break;

		case 'followedpaper_comment' :
			if ( (int) $count > 1 ) {
				$text = __( 'There is new activity on papers you follow', 'social-paper' );

				$link = bp_loggedin_user_domain() . bp_get_notifications_slug();
			} else {
				$paper = new CACSP_Paper( $paper_id );
				$text = sprintf( __( 'There is new activity on the paper "%s"', 'social-paper' ), $paper->post_title );
				$link = get_comment_link( $secondary_item_id );
			}

			break;

		case 'comment_mention' :
			if ( (int) $count > 1 ) {
				$text = __( 'You have been mentioned in papers', 'social-paper' );

				// @todo Notifications directory? Maybe only if corresponding to more than one paper?
				$link = get_comment_link( $secondary_item_id );
			} else {
				$paper = new CACSP_Paper( $paper_id );
				$text = sprintf( __( 'You&#8217;ve been mentioned in a comment on the paper "%s"', 'social-paper' ), $paper->post_title );
				$link = get_comment_link( $secondary_item_id );
			}

			break;

		default :
			/**
			 * Filter for custom paper screen notifications.
			 *
			 * Must return an array with 'text' and 'link' keys.
			 *
			 * @param bool  $retval Defaults to false
			 * @param array $args   Current notification parameters.
			 */
			$notification = apply_filters( 'cacsp_custom_notification_format', false, compact(
				'action',
				'paper_id',
				'secondary_item_id',
				'count'
			) );

			$text = '';
			$link = '';

			if ( ! empty( $notification['link'] ) && ! empty( $notification['text'] ) ) {
				$link = esc_url( $notification['link'] );
				$text = esc_attr( $notification['text'] );
			}
			break;
	}

	if ( 'array' === $format ) {
		return array(
			'link' => $link,
			'text' => $text,
		);
	} else {
		return sprintf( '<a href="%s">%s</a>', $link, $text );
	}
}

/**
 * Send an email related to a notification.
 *
 * @since 1.0.0
 *
 * @todo This would be a good place to block multiple emails. Maybe create a hash of user IDs + notification IDs,
 * and don't let multiple emails for a given notification ID to go out to a single user. Doesn't need to be persistent.
 *
 * @param array $args {
 *     @type int    $recipient_user_id ID of the user receiving the message.
 *     @type int    $sender_user_id    ID of the user responsible for triggering the message.
 *     @type int    $paper_id          ID of the paper.
 *     @type string $subject           Email subject base. Will be prepended with site name.
 *     @type string $content           Email content. Will be embedded within content template.
 *     @type string $type              Type of notification. Used for fine-grained email settings.
 * }
 * @return bool|WP_Error Returns true on success, WP_Error on failure.
 */
function cacsp_send_notification_email( $args = array() ) {
	$r = wp_parse_args( $args, array(
		'recipient_user_id' => null,
		'sender_user_id'    => bp_loggedin_user_id(),
		'paper_id'          => null,
		'subject'           => '',
		'content'           => '',
		'type'              => '',
	) );

	$recipient = new WP_User( $r['recipient_user_id'] );
	if ( ! $recipient->exists() ) {
		return new WP_Error( 'user_not_found', __( 'User does not exist.', 'social-paper' ), $r['recipient_user_id'] );
	}

	// @todo Allow filtering based on 'type', 'paper_id', or other user settings.

	$subject = bp_get_email_subject( array( 'text' => $r['subject'] ) );

	return wp_mail( $recipient->user_email, $subject, $r['content'] );
}

/** Specific notifications ***************************************************/

/**
 * Notify a user when added as a reader to a paper.
 *
 * @since 1.0.0
 *
 * @param CACSP_Paper $paper   Paper object.
 * @param int         $user_id ID of the user added as a reader.
 */
function cacsp_notification_added_reader( CACSP_Paper $paper, $user_id ) {
	$type = 'added_reader';

	$added = bp_notifications_add_notification( array(
		'user_id' => $user_id,
		'item_id' => $paper->ID,
		'secondary_item_id' => bp_loggedin_user_id(),
		'component_name' => 'cacsp',
		'component_action' => $type,
	) );

	$adder_name = bp_core_get_user_displayname( bp_loggedin_user_id() );

	$text = sprintf( __( '%s has added you as a reader on the paper "%s".', 'social-paper' ), $adder_name, $paper->post_title );

	$link = wp_login_url( get_permalink( $paper->ID ) );
	$content = sprintf( __(
'%1$s

Visit the paper: %2$s', 'social-paper' ), $text, $link );

	if ( bp_is_active( 'follow' ) ) {
		$content .= "\n\n";

		$content .= sprintf( __(
'%1$s is a private paper, but %2$s has granted you permission to read and leave comments. To receive notifications when the paper is edited or receives comments, add yourself as a follower.
', 'social-paper' ), $paper->post_title, $adder_name );

		$content .= "\n\n";
	}

	cacsp_send_notification_email( array(
		'recipient_user_id' => $user_id,
		'paper_id' => $paper->ID,
		'subject' => $text,
		'content' => $content,
		'type' => $type,
	) );
}
add_action( 'cacsp_added_reader_to_paper', 'cacsp_notification_added_reader', 10, 2 );

/**
 * Prevent WP from sending its native postauthor email notifications for comments.
 *
 * We send our own. This is an ugly hack.
 *
 * @since 1.0.0
 *
 * @param array $emails     Array of email addresses to notify. We'll wipe it out to noop wp_notify_postauthor().
 * @param int   $comment_id ID of the comment.
 */
function cacsp_prevent_wp_notify_postauthor( $emails, $comment_id ) {
	$comment = get_comment( $comment_id );
	if ( ! $comment ) {
		return $emails;
	}

	$paper = new CACSP_Paper( $comment->comment_post_ID );
	$paper_id = $paper->ID;
	if ( ! $paper_id ) {
		return $emails;
	}

	return array();
}
add_filter( 'comment_notification_recipients', 'cacsp_prevent_wp_notify_postauthor', 10, 2 );

/**
 * Notify a user when a comment is left on her paper.
 *
 * @since 1.0.0
 *
 * @param int $comment_id ID of the comment.
 */
function cacsp_notification_mypaper_comment( $comment_id ) {
	$comment = get_comment( $comment_id );
	if ( ! $comment ) {
		return;
	}

	// Don't send notifications of unapproved comments.
	if ( '1' != $comment->comment_approved ) {
		return;
	}

	$paper = new CACSP_Paper( $comment->comment_post_ID );
	$paper_id = $paper->ID;
	if ( ! $paper_id ) {
		return;
	}

	$type = 'mypaper_comment';

	// Don't notify authors of their own comments.
	if ( $comment->user_id == $paper->post_author ) {
		return;
	}

	$added = bp_notifications_add_notification( array(
		'user_id' => $paper->post_author,
		'item_id' => $paper->ID,
		'secondary_item_id' => $comment_id,
		'component_name' => 'cacsp',
		'component_action' => $type,
	) );

	// Comment authors automatically follow the paper that they replied to
	if ( bp_is_active( 'follow' ) && ! empty( $comment->user_id ) ) {
		bp_follow_start_following( array(
			'leader_id'   => cacsp_follow_get_activity_id( $paper->ID ),
			'follower_id' => (int) $comment->user_id,
			'follow_type' => 'cacsp_paper'
		) );
	}

	$subject = sprintf( __( 'New comment on your paper "%s"', 'social-paper' ), $paper->post_title );

	$content  = sprintf( __( 'New comment on your paper "%s"', 'social-paper' ), $paper->post_title ) . "\r\n";
	$content .= sprintf( __( 'Author: %s', 'social-paper' ), $comment->comment_author ) . "\r\n";
	$content .= sprintf( __( 'Comment: %s', 'social-paper' ), "\r\n" . $comment->comment_content ) . "\r\n\r\n";
	$content .= sprintf( __( 'View the commment: %s', 'social-paper' ), get_comment_link( $comment ) ) . "\r\n";
	$content .= sprintf( __( 'Visit the paper: %s', 'social-paper' ), get_permalink( $paper->ID ) ) . "\r\n";

	cacsp_send_notification_email( array(
		'recipient_user_id' => $paper->post_author,
		'paper_id' => $paper->ID,
		'subject' => $subject,
		'content' => $content,
		'type' => $type,
	) );
}
add_action( 'wp_insert_comment', 'cacsp_notification_mypaper_comment' );

/**
 * Notify a user when a comment is left a paper she follows.
 *
 * @since 1.0.0
 *
 * @param int $comment_id ID of the comment.
 */
function cacsp_notification_followedpaper_comment( $comment_id ) {
	$comment = get_comment( $comment_id );
	if ( ! $comment ) {
		return;
	}

	// Don't send notifications of unapproved comments.
	if ( '1' != $comment->comment_approved ) {
		return;
	}

	$paper = new CACSP_Paper( $comment->comment_post_ID );
	$paper_id = $paper->ID;
	if ( ! $paper_id ) {
		return;
	}

	$type = 'followedpaper_comment';

	$leader_id = cacsp_follow_get_activity_id( $paper->ID );
	$followers = bp_follow_get_followers( array(
		'object_id' => $leader_id,
		'follow_type' => 'cacsp_paper',
	) );

	if ( empty( $followers ) ) {
		return;
	}

	// @todo I think this is a WP bug - 'user_id' is not always filled in.
	$comment_author_id = 0;
	if ( ! empty( $comment->user_id ) ) {
		$comment_author_id = (int) $comment->user_id;
	} else {
		$comment_author = get_user_by( 'email', $comment->comment_author_email );
		if ( $comment_author ) {
			$comment_author_id = $comment_author->ID;
		}
	}

	foreach ( $followers as $follower_id ) {
		// Don't notify authors of their own comments.
		if ( $comment_author_id == $follower_id ) {
			continue;
		}

		// Post author is handled elsewhere.
		if ( $paper->post_author == $follower_id ) {
			continue;
		}

		// Sanity check.
		$follower = new WP_User( $follower_id );
		if ( ! $follower->exists() ) {
			continue;
		}

		$added = bp_notifications_add_notification( array(
			'user_id' => $follower_id,
			'item_id' => $paper->ID,
			'secondary_item_id' => $comment_id,
			'component_name' => 'cacsp',
			'component_action' => $type,
		) );

		$subject = sprintf( __( 'New comment on the paper "%s"', 'social-paper' ), $paper->post_title );

		$content = sprintf(
'New comment on the paper "%1$s"

Author: %2$s
Comment: %3$s
View the comment: %4$s
Visit the paper: %5$s

You receieved this notification because you are following the paper "%6$s". To unfollow, visit %7$s and click the Unfollow button.',
			$paper->post_title, // 1
			$comment->comment_author, // 2
			$comment->comment_content, // 3
			get_comment_link( $comment ), // 4
			get_permalink( $paper_id ), // 5
			$paper->post_title, // 6
			get_permalink( $paper_id ) // 7
		);

		cacsp_send_notification_email( array(
			'recipient_user_id' => $follower_id,
			'paper_id' => $paper->ID,
			'subject' => $subject,
			'content' => $content,
			'type' => $type,
		) );
	}
}
add_action( 'wp_insert_comment', 'cacsp_notification_followedpaper_comment' );

/**
 * Notify a user when a followed paper is edited.
 *
 * Piggybacks on 'new_cacsp_edit', so that we take advantage of throttling there.
 *
 * @since 1.0.0
 *
 * @param int $activity_id ID of the 'new_cacsp_edit' activity item .
 * @param int $post_id     ID of the paper.
 */
function cacsp_notification_followedpaper_edit( $activity_id, $paper_id ) {
	$paper = new CACSP_Paper( $paper_id );
	$paper_id = $paper->ID;
	if ( ! $paper_id ) {
		return;
	}

	$edit_activity = new BP_Activity_Activity( $activity_id );

	$type = 'followedpaper_edit';

	$leader_id = cacsp_follow_get_activity_id( $paper->ID );
	$followers = bp_follow_get_followers( array(
		'object_id' => $leader_id,
		'follow_type' => 'cacsp_paper',
	) );

	if ( empty( $followers ) ) {
		return;
	}

	$editor_name = bp_core_get_user_displayname( $edit_activity->user_id );

	foreach ( $followers as $follower_id ) {
		// Don't notify authors of their own edits.
		if ( $edit_activity->user_id == $follower_id ) {
			continue;
		}

		// Sanity check.
		$follower = new WP_User( $follower_id );
		if ( ! $follower->exists() ) {
			continue;
		}

		$added = bp_notifications_add_notification( array(
			'user_id' => $follower_id,
			'item_id' => $paper->ID,
			'secondary_item_id' => $comment_id,
			'component_name' => 'cacsp',
			'component_action' => $type,
		) );

		$subject = sprintf( __( 'The paper "%s" has been updated', 'social-paper' ), $paper->post_title );

		$content = sprintf(
'The paper "%1$s" has been updated

Editor: %2$s
Visit the paper: %3$s

You receieved this notification because you are following the paper "%4$s". To unfollow, visit %5$s and click the Unfollow button.',
			$paper->post_title, // 1
			$editor_name, // 2
			get_permalink( $paper_id ), // 3
			$paper->post_title, // 4
			get_permalink( $paper_id ) // 4
		);

		cacsp_send_notification_email( array(
			'recipient_user_id' => $follower_id,
			'paper_id' => $paper->ID,
			'subject' => $subject,
			'content' => $content,
			'type' => $type,
		) );
	}
}
add_action( 'cacsp_created_edit_activity', 'cacsp_notification_followedpaper_edit', 10, 2 );


/**
 * Notify a user when a comment is left with an @-mention of a user.
 *
 * Fired from cacsp_at_mention_send_notifications().
 *
 * @todo This will produce duplicate comment notifications in some cases. Figure out the precedence, and how to
 *       keep track and deduplicate.
 *
 * @since 1.0.0
 *
 * @param int   $activity_id ID of the cacsp_new_comment activity item triggering the notification.
 * @param array $user_ids    IDs of the users the notification is being sent to.
 */
function cacsp_notification_comment_mention( $activity_id, $user_ids ) {
	if ( empty( $user_ids ) ) {
		return;
	}

	$activity = new BP_Activity_Activity( $activity_id );
	if ( empty( $activity->secondary_item_id ) ) {
		return;
	}

	$comment_id = $activity->item_id;
	$_paper_id  = $activity->secondary_item_id;

	$comment = get_comment( $comment_id );
	if ( ! $comment ) {
		return;
	}

	$paper = new CACSP_Paper( $comment->comment_post_ID );
	$paper_id = $paper->ID;
	if ( ! $paper_id || $paper_id != $_paper_id ) {
		return;
	}

	// Pull up info on the commenter.
	if ( ! empty( $comment->user_id ) ) {
		$comment_user_id = (int) $comment->user_id;
		$comment_user = new WP_User( $comment_user_id );
	} else {
		$_comment_user = get_user_by( 'email', $comment->comment_author_email );
		if ( $_comment_user->exists() ) {
			$comment_user = $_comment_user;
			$comment_user_id = (int) $comment_user->ID;
		}
	}

	$type = 'comment_mention';

	// Fetch emails of users.
	$users = get_users( array(
		'include' => $user_ids,
		'fields' => 'all',
	) );

	$emails = array();
	foreach ( $users as $u ) {
		$emails[ $u->ID ] = $u->user_email;
	}

	if ( empty( $emails ) ) {
		return;
	}

	$email_subject = sprintf( __( 'You&#8217;ve been mentioned in a comment on the paper "%s"', 'social-paper' ), $paper->post_title );
	$email_content  = sprintf( __( '%1$s mentioned you in a comment on the paper "%2$s"', 'social-paper' ), bp_core_get_user_displayname( $comment_user_id ), $paper->post_title ) . "\r\n\r\n";
	$email_content .= sprintf( _x( '"%s"', 'Mention email notification comment content', 'social-paper' ), $comment->comment_content ) . "\r\n\r\n";
	$email_content .= sprintf( __( 'View the commment: %s', 'social-paper' ), get_comment_link( $comment ) ) . "\r\n";
	$email_content .= sprintf( __( 'Visit the paper: %s', 'social-paper' ), get_permalink( $paper->ID ) ) . "\r\n\r\n";

	foreach ( $emails as $user_id => $email ) {
		// We handle the post author elsewhere.
		if ( $user_id == $paper->post_author ) {
			continue;
		}

		$added = bp_notifications_add_notification( array(
			'user_id' => $user_id,
			'item_id' => $paper->ID,
			'secondary_item_id' => $comment_id,
			'component_name' => 'cacsp',
			'component_action' => $type,
		) );

		cacsp_send_notification_email( array(
			'recipient_user_id' => $user_id,
			'paper_id' => $paper->ID,
			'subject' => $email_subject,
			'content' => $email_content,
			'type' => $type,
		) );
	}
}
add_action( 'wp_insert_comment', 'cacsp_notification_mythread_comment' );

/** User notifications page **************************************************/

/**
 * Filter notifications by component action.
 *
 * Only applicable in BuddyPress 2.1+.
 *
 * @param array $retval Current notification parameters.
 * @return array
 */
function cacsp_filter_unread_notifications( $retval ) {
	// Make sure we're on a user's notification page
	if ( ! bp_is_user_notifications() ) {
		return $retval;
	}

	// Only do this on the 'unread' page
	if ( ! bp_is_current_action( 'unread' ) ) {
		return $retval;
	}

	// Make sure we're doing this for the main notifications loop
	if ( ! did_action( 'bp_before_member_body' ) ) {
		return $retval;
	}

	// Filter notifications by action
	if ( ! empty( $_GET['spfilter'] ) ) {
		$retval['component_action'] = sanitize_title( $_GET['spfilter'] );

		// Wildcard filter to show paper followers for all papers
		if ( 0 === strpos( $retval['component_action'], 'follow_paper' ) ) {
			$retval['search_terms'] = $retval['component_action'] . '_';
			$retval['component_action'] = false;
		}

		// Remove this filter to prevent any other notification loop getting filtered
		remove_filter( 'bp_after_has_notifications_parse_args', 'cacsp_filter_unread_notifications' );
	}

	return $retval;
}
add_filter( 'bp_after_has_notifications_parse_args', 'cacsp_filter_unread_notifications' );

/** Marking read / delete ****************************************************/

/**
 * Delete paper notifications when a paper is deleted.
 *
 * @param int $post_id Post ID
 */
function cacsp_delete_notifications_on_paper_delete( $post_id ) {
	// Check if a paper is being deleted
	$post = new CACSP_Paper( $post_id );
	if ( empty( $post->id ) ) {
		return;
	}

	// $item_id, $component_name, $component_action = false, $secondary_item_id = false
	bp_notifications_delete_all_notifications_by_type( $post->id, 'cacsp' );
}
add_action( 'delete_post', 'cacsp_delete_notifications_on_paper_delete' );

/**
 * Mark all notifications read for a user + paper combination.
 *
 * Optionally, you can also pass a 'type' which will limit the marking-read to notifications matching
 * that 'component_action'.
 *
 * @since 1.0.0
 *
 * @param int    $user_id           ID of the user.
 * @param int    $paper_id          ID of the paper.
 * @param string $type              Optional. 'component_action' to limit to.
 * @param int    $secondary_item_id Optional. 'secondary_item_id' to limit to.
 */
function cacsp_mark_notifications_read( $user_id, $paper_id, $type = '', $secondary_item_id = '' ) {
	$where = array(
		'user_id'        => $user_id,
		'item_id'        => $paper_id,
		'component_name' => 'cacsp',
	);

	if ( $type ) {
		$where['component_action'] = $type;
	}

	if ( $secondary_item_id ) {
		$where['secondary_item_id'] = $secondary_item_id;
	}

	return BP_Notifications_Notification::update(
		array( 'is_new' => 0 ),
		$where
	);
}

/**
 * Mark notifications read when a user views a paper.
 *
 * @since 1.0.0
 */
function cacsp_mark_notifications_read_on_paper_view() {
	if ( ! is_user_logged_in() ) {
		return;
	}

	$queried_object = get_queried_object();
	if ( ! ( $queried_object instanceof WP_Post ) || 'cacsp_paper' !== $queried_object->post_type ) {
		return;
	}

	cacsp_mark_notifications_read( bp_loggedin_user_id(), $queried_object->ID );
}
add_action( 'bp_actions', 'cacsp_mark_notifications_read_on_paper_view' );

/** Follow ******************************************************************/

add_action( 'bp_follow_start_following_cacsp_paper',  'cacsp_follow_send_email_notification_to_author' );
add_action( 'bp_follow_screen_notification_settings', 'cacsp_follow_notification_screen_settings' );

/**
 * Send a notification to the paper author when a user follows their paper.
 *
 * We only send a notification when a paper is followed manually.  Not via
 * auto-follows from comment replying or reader additions.
 *
 * @param BP_Follow $follow
 */
function cacsp_follow_send_email_notification_to_author( BP_Follow $follow ) {
	// Bail from comment auto-follows
	if ( did_action( 'bp_notification_after_save' ) ) {
		return;
	}

	// Bail from reader auto-follows
	if ( did_action( 'cacsp_added_reader_to_paper' ) ) {
		return;
	}

	$activity = new BP_Activity_Activity( $follow->leader_id );

	// Bail if author is the same as the follower
	if ( $follow->follower_id == $activity->user_id ) {
		return;
	}

	// Bail if author doesn't want to be notified via email
	$notify = bp_get_user_meta( $activity->user_id, 'notification_starts_following_paper', true );
	if ( 'no' === $notify ) {
		return;
	}

	$paper = get_post( $activity->secondary_item_id );

	$follower_name = wp_specialchars_decode( bp_core_get_user_displayname( $follow->follower_id ), ENT_QUOTES );

	$subject = sprintf( __( '%1$s is now following your paper "%2$s"', 'social-paper' ), $follower_name, $paper->post_title );
	$message = sprintf( __(
'%1$s is now following your paper "%2$s".

View %1$s\'s profile: %3$s
View your paper: %4$s', 'social-paper' ), $follower_name, $paper->post_title, bp_core_get_user_domain( $follow->follower_id ), get_permalink( $paper->ID ) );

	// Add notifications link if settings component is enabled
	if ( bp_is_active( 'settings' ) ) {
		$settings_link = bp_core_get_user_domain( $follow->follower_id ) . bp_get_settings_slug() . '/notifications/';
		$message .= "\n\n";
		$message .= sprintf( __(
'---------------------
To disable these notifications please log in and go to:
%s', 'social-paper' ), $settings_link );
	}

	$added = bp_notifications_add_notification( array(
		'user_id' => $paper->post_author,
		'item_id' => $paper->ID,
		'secondary_item_id' => $follow->follower_id,
		'component_name' => 'cacsp',
		'component_action' => "follow_paper_{$paper->ID}",
	) );

	// Email time!
	cacsp_send_notification_email( array(
		'recipient_user_id' => $paper->post_author,
		'paper_id' => $paper->ID,
		'subject' => $subject,
		'content' => $message,
		'type' => 'follow_paper'
	) );
}


/**
 * Show paper email options under a user's "Settings > Email" page.
 */
function cacsp_follow_notification_screen_settings() {
	if ( ! $notify = bp_get_user_meta( bp_displayed_user_id(), 'notification_starts_following_paper', true ) ) {
		$notify = 'yes';
	}
?>

	<tr>
		<td></td>
		<td><?php _e( 'A member manually follows a paper that you authored', 'social-paper' ) ?></td>
		<td class="yes"><input type="radio" name="notifications[notification_starts_following_paper]" value="yes" <?php checked( $notify, 'yes', true ) ?>/></td>
		<td class="no"><input type="radio" name="notifications[notification_starts_following_paper]" value="no" <?php checked( $notify, 'no', true ) ?>/></td>
	</tr>

<?php
}
