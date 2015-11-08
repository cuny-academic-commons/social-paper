<?php

/**
 * "Reader" functionality.
 */

/**
 * Generate the reader selector interface.
 */
function cacsp_paper_reader_selector( $paper_id ) {
	global $wpdb;

	// Get a list of readers, friends, and co-group-members to prime selectbox.
	// @todo Add AJAX support.
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

	$users = bp_core_get_users( array(
		'include' => array_merge( $paper_reader_ids, $group_member_ids, $friend_member_ids ),
		'type' => 'alphabetical',
		'per_page' => 0,
	) );

	$user_data = array();
	$selected = array();
	if ( ! empty( $users['users'] ) ) {
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

	$script = 'var CACSP_Potential_Readers = ' . wp_json_encode( $user_data ) . ';';
	echo "\n" . '<script type="text/javascript">' . $script . '</script>' . "\n";

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
