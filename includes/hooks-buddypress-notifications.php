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
function cacsp_format_notifications( $action, $paper_id, $user_id, $count, $format = 'string' ) {
	$paper = new CACSP_Paper( $paper_id );

	switch ( $action ) {
		case 'added_reader' :
			if ( (int) $count > 1 ) {
				$text = sprintf( _n( 'You have been added as a reader on %s paper', 'You have been added as a reader to %s papers', $count, 'social-paper' ), $count );

				// @todo Some directory.
				$link = '';
			} else {
				$text = sprintf( __( '%s has added you as a reader on the paper %s', 'social-paper' ), bp_core_get_user_displayname( $user_id ), $paper->post_title );
				$link = get_permalink( $paper_id );
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

	$text = sprintf( __( '%s has added you as a reader on the paper "%s".', 'social-paper' ), bp_core_get_user_displayname( bp_loggedin_user_id() ), $paper->post_title );
	$link = wp_login_url( get_permalink( $paper->ID ) );
	$content = sprintf( __(
'%1$s

Visit the paper: %2$s', 'social-paper' ), $text, $link );

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
 * Send an email related to a notification.
 *
 * @since 1.0.0
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
