<?php

/**
 * "Reader" functionality.
 */

/**
 * Generate the reader selector interface.
 */
function cacsp_paper_reader_selector( $paper_id ) {
	$paper = new CACSP_Paper( $paper_id );
	$paper_reader_ids = $paper->get_reader_ids();

	// Get a list of readers + friends, to pre-fill selectbox.
	$users = bp_core_get_users( array(
		'user_id' => bp_loggedin_user_id(),
		'include' => $paper_reader_ids,
		'type' => 'alphabetical',
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

	$paper = new CACSP_Paper( $post_id );
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
