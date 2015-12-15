<?php

/**
 * "Reader" functionality.
 */

/**
 * Get potential reader IDs for a given paper.
 *
 * Gets a list of:
 * - Current readers
 * - Members of groups that logged-in user is a member of
 * - Friends of the logged-in user
 *
 * Heavily cached, because :(
 */
function cacsp_get_potential_reader_ids( $paper_id ) {
	global $wpdb;

	$cache_group = 'cacsp_potential_readers';
	$last_changed = wp_cache_get( 'last_changed', $cache_group );
	if ( ! $last_changed ) {
		$last_changed = microtime();
		wp_cache_set( 'last_changed', $last_changed, $cache_group );
	}

	$cache_key = bp_loggedin_user_id() . ':' . $last_changed;
	$cached = wp_cache_get( $cache_key, $cache_group );
	if ( false === $cached ) {
		$paper = new CACSP_Paper( $paper_id );
		$paper_reader_ids = $paper->get_reader_ids();

		$groups_of_user = cacsp_get_groups_of_user( bp_loggedin_user_id() );
		if ( empty( $groups_of_user ) ) {
			$groups_of_user = array( 0 );
		}

		// So dirty.
		$bp = buddypress();
		$group_member_ids = $wpdb->get_col( $wpdb->prepare( "SELECT user_id FROM {$bp->groups->table_name_members} WHERE group_id IN (" . implode( ',', $groups_of_user ) . ") AND user_id != %d AND is_banned = 0 AND is_confirmed = 1", bp_loggedin_user_id() ) );
		$group_member_ids = array_map( 'intval', $group_member_ids );

		$friend_member_ids = array();
		if ( bp_is_active( 'friends' ) ) {
			$friend_member_ids = friends_get_friend_user_ids( bp_loggedin_user_id() );
		}

		$readers = array_merge( $paper_reader_ids, $group_member_ids, $friend_member_ids );
		wp_cache_set( $cache_key, $readers, $cache_group );
	} else {
		$readers = $cached;
	}

	return array_map( 'intval', $readers );
}

/**
 * Generate the reader selector interface.
 *
 * @since 1.0.0
 */
function cacsp_paper_reader_selector( $paper_id ) {
	$paper = new CACSP_Paper( $paper_id );
	$paper_reader_ids = $paper->get_reader_ids();

	$existing = bp_core_get_users( array(
		'include' => $paper_reader_ids,
		'type' => 'alphabetical',
		'per_page' => 0,
		'populate_extras' => false,
		'count_total' => false,
	) );

	$selected = array();
	if ( ! empty( $users['users'] ) ) {
		foreach ( $users['users'] as $user ) {
			$user_id = (int) $user->ID;

			$user_data[] = array(
				'id'   => $user_id,
				'text' => html_entity_decode( $user->display_name, ENT_QUOTES, 'UTF-8' ),
			);
		}
	}

	// Select2 only needs an <option> printed for the selected options.
	?>
	<select name="cacsp-readers[]" multiple="multiple" style="width:100%;" id="cacsp-reader-selector">
		<?php
		foreach ( $selected as $_selected ) {
			echo '<option value="' . esc_attr( $_selected['id'] ) . '" selected="selected">' . esc_html( $_selected['text'] ) . '</option>';
		}
		?>
	</select>
	<?php

	wp_nonce_field( 'cacsp-reader-selector', 'cacsp-reader-selector-nonce' );
}

/**
 * Save reader selection data sent via AJAX.
 *
 * @param int $post_id ID of the post.
 */
function cacsp_save_reader_connection( $post_id ) {
	if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
		return;
	}

	if ( ! isset( $_POST['social_paper_readers_nonce'] ) || ! isset( $_POST['social_paper_readers'] ) ) {
		return;
	}

	if ( ! wp_verify_nonce( $_POST['social_paper_readers_nonce'], 'cacsp-reader-selector' ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_paper', $post_id ) ) {
		return;
	}

	$paper = new CACSP_Paper( $post_id );
	$_paper_id = $paper->ID;
	if ( ! $_paper_id ) {
		return;
	}

	$results = array();

	$new_reader_ids      = array_map( 'intval', (array) $_POST['social_paper_readers'] );
	$existing_reader_ids = $paper->get_reader_ids();

	// Remove readers no longer listed.
	$readers_to_remove = array_diff( $existing_reader_ids, $new_reader_ids );
	if ( $readers_to_remove ) {
		foreach ( $readers_to_remove as $user_id ) {
			$results['removed'][ $user_id ] = $paper->remove_reader( $user_id );
		}
	}

	// Add new readers.
	$readers_to_add = array_diff( $new_reader_ids, $existing_reader_ids );
	if ( $readers_to_add ) {
		foreach ( $readers_to_add as $user_id ) {
			$results['connected'][ $user_id ] = $paper->add_reader( $user_id );
		}
	}

	// Can't do much with results :(
}
add_action( 'save_post', 'cacsp_save_reader_connection' );

/**
 * Save paper status setting data sent via AJAX.
 *
 * @param int $post_id ID of the post.
 */
function cacsp_save_paper_status( $post_id ) {
	if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
		return;
	}

	if ( ! isset( $_POST['social_paper_status_nonce'] ) || ! isset( $_POST['social_paper_status'] ) ) {
		return;
	}

	if ( ! wp_verify_nonce( $_POST['social_paper_status_nonce'], 'cacsp-paper-status' ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_paper', $post_id ) ) {
		return;
	}

	$paper = new CACSP_Paper( $post_id );
	if ( ! $paper->exists() ) {
		return;
	}

	$status = 'protected' === $_POST['social_paper_status'] ? 'protected' : 'public';
	$paper->set_status( $status );
}
add_action( 'save_post', 'cacsp_save_paper_status' );

/**
 * Fetch a JSON list of potential readers, to populate the Select2 interface.
 *
 * @since 1.1.0
 */
function cacsp_potential_readers_cb() {
	// Get a list of readers, friends, and co-group-members to prime selectbox.
	$paper_id = isset( $_POST['paper_id'] ) ? intval( $_POST['paper_id'] ) : 0;
	$potential_reader_ids = cacsp_get_potential_reader_ids( $paper_id );

	$users = bp_core_get_users( array(
		'include' => $potential_reader_ids,
		'type' => 'alphabetical',
		'per_page' => 0,
		'populate_extras' => false,
		'count_total' => false,
	) );

	$user_data = array();
	$selected = array();
	if ( ! empty( $users['users'] ) ) {
		$paper = new CACSP_Paper( $paper_id );
		$paper_reader_ids = $paper->get_reader_ids();

		foreach ( $users['users'] as $user ) {
			$user_id = (int) $user->ID;

			$user_data[] = array(
				'id'   => $user_id,
				'text' => html_entity_decode( $user->display_name, ENT_QUOTES, 'UTF-8' ),
			);

			// Collect data about the existing readers.
			if ( in_array( $user_id, $paper_reader_ids, true ) ) {
				$selected[] = array(
					'id' => $user_id,
					'text' => stripslashes( $user->display_name ),
				);
			}
		}
	}

	$retval = array(
		'existing'  => $selected,
		'potential' => $user_data,
	);

	wp_send_json_success( $retval );
}
add_action( 'wp_ajax_cacsp_potential_readers', 'cacsp_potential_readers_cb' );

/** Cache ********************************************************************/

/**
 * Invalidate cache incrementors on reader add/remove.
 *
 * We use a single incrementor for all papers because programming is hard.
 *
 * @since 1.0.0
 */
function cacsp_invalidate_potential_reader_cache_incrementor() {
	wp_cache_delete( 'last_changed', 'cacsp_potential_readers' );
}
add_action( 'cacsp_added_reader_to_paper', 'cacsp_invalidate_potential_reader_cache_incrementor' );
add_action( 'cacsp_removed_reader_from_paper', 'cacsp_invalidate_potential_reader_cache_incrementor' );
add_action( 'groups_member_after_remove', 'cacsp_invalidate_potential_reader_cache_incrementor' );
add_action( 'groups_member_after_save', 'cacsp_invalidate_potential_reader_cache_incrementor' );
add_action( 'friends_friendship_deleted', 'cacsp_invalidate_potential_reader_cache_incrementor' );
add_action( 'friends_friendship_accepted', 'cacsp_invalidate_potential_reader_cache_incrementor' );
