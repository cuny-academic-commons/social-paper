<?php

/**
 * BuddyPress Activity integration.
 */

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

	// @todo Other activity types.
	$action = sprintf(
		__( '%1$s created a new paper %2$s', 'social-paper' ),
		$user_link,
		sprintf( '<a href="%s">%s</a>', esc_url( $paper_link ), esc_html( $paper_title ) )
	);

	return $action;
}
