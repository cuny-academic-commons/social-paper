<?php

/**
 * Paper object.
 *
 * @since 1.0
 */
class CACSP_Paper {
	protected $id = 0;
	protected $post_obj;
	protected $post_obj_pristine;
	protected $group_ids;
	protected $reader_ids;

	/**
	 * Constructor.
	 *
	 * @param int|WP_Post $paper Optional. Pass the ID or object of a cacsp_paper post to populate the object.
	 * @return CACSP_Paper
	 */
	public function __construct( $paper = false ) {
		if ( $paper ) {
			$this->populate( $paper );
		}
	}

	/**
	 * Populate object for a specific paper.
	 *
	 * @since 1.0
	 *
	 * @param int|WP_Post $paper Optional. Pass the ID or object of a cacsp_paper post to populate the object.
	 */
	protected function populate( $paper ) {
		if ( $paper instanceof WP_Post ) {
			$post_id = $paper->ID;
			$post_obj = $paper;
		} elseif ( is_numeric( $paper ) ) {
			$post_id = $paper;
			$post_obj = get_post( $post_id );
		}

		if ( 'cacsp_paper' === $post_obj->post_type ) {
			$this->id = $post_id;
			$this->post_obj = $post_obj;
			$this->post_obj_pristine = clone( $post_obj );
		}
	}

	/**
	 * Save changes to the database.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function save() {
		$update_post_args = array();
		foreach ( $this->post_obj as $key => $value ) {
			if ( $value !== $this->post_obj_pristine->{$key} ) {
				$update_post_args[ $key ] = $value;
			}
		}

		$update_post_args['ID'] = $this->id;
		$saved = wp_update_post( $update_post_args );

		if ( $saved ) {
			$this->populate( $this->id );
		}

		return (bool) $saved;
	}

	/**
	 * Connect the paper to a group.
	 *
	 * @param int $group_id Group ID.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function connect_to_group( $group_id ) {
		if ( ! function_exists( 'bp_is_active') || ! bp_is_active( 'groups' ) ) {
			return false;
		}

		$group = groups_get_group( array( 'group_id' => $group_id ) );
		if ( ! $group->id ) {
			return new WP_Error( 'group_not_found', __( 'No group found by that ID.', 'social-paper' ) );
		}

		$set = wp_set_object_terms( $this->id, array( 'group_' . $group_id ), 'cacsp_paper_group', true );

		$this->group_ids = null;

		if ( is_wp_error( $set ) || empty( $set ) ) {
			return $set;
		} else {
			/**
			 * Fires when a paper has been connected to a group.
			 *
			 * @param CACSP_Paper $paper    Paper object.
			 * @param int         $group_id ID of the group.
			 */
			do_action( 'cacsp_connected_paper_to_group', $this, $group_id );

			return true;
		}
	}

	/**
	 * Disconnect the paper from a group.
	 *
	 * @param int $group_id ID of the group.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function disconnect_from_group( $group_id ) {
		if ( ! function_exists( 'bp_is_active') || ! bp_is_active( 'groups' ) ) {
			return false;
		}

		$group = groups_get_group( array( 'group_id' => $group_id ) );
		if ( ! $group->id ) {
			return new WP_Error( 'group_not_found', __( 'No group found by that ID.', 'social-paper' ) );
		}

		$paper_groups = $this->get_group_ids();
		if ( ! in_array( $group_id, $paper_groups ) ) {
			return new WP_Error( 'paper_not_connected_to_group', __( 'This paper is not connected to that group.', 'social-paper' ), array( 'paper_id' => $this->id, 'group_id' => $group_id ) );
		}

		$removed = wp_remove_object_terms( $this->id, 'group_' . $group_id , 'cacsp_paper_group' );

		$this->group_ids = null;

		if ( $removed && ! is_wp_error( $removed ) ) {
			/**
			 * Fires when a paper has been disconnected from a group.
			 *
			 * @param CACSP_Paper $paper    Paper object.
			 * @param int         $group_id ID of the group.
			 */
			do_action( 'cacsp_disconnected_paper_from_group', $this, $group_id );
		}

		return $removed;
	}

	public function get_group_ids() {
		if ( ! function_exists( 'bp_is_active') || ! bp_is_active( 'groups' ) ) {
			return false;
		}

		if ( ! is_null( $this->group_ids ) ) {
			return $this->group_ids;
		}

		$group_terms = wp_get_object_terms( $this->id, 'cacsp_paper_group', array(
			'update_term_meta_cache' => false,
		) );
		$group_term_names = wp_list_pluck( $group_terms, 'name' );

		$this->group_ids = array();
		foreach ( $group_term_names as $group_term_name ) {
			// Trim leading 'group_'.
			$this->group_ids[] = intval( substr( $group_term_name, 6 ) );
		}

		return $this->group_ids;
	}

	/**
	 * Get the IDs of readers of this paper.
	 *
	 * @return array
	 */
	public function get_reader_ids() {
		if ( ! is_null( $this->reader_ids ) ) {
			return $this->reader_ids;
		}

		$reader_terms = wp_get_object_terms( $this->id, 'cacsp_paper_reader', array(
			'update_term_meta_cache' => false,
		) );
		$reader_term_names = wp_list_pluck( $reader_terms, 'name' );

		$this->reader_ids = array();
		foreach ( $reader_term_names as $reader_term_name ) {
			// Trim leading 'reader_'.
			$this->reader_ids[] = intval( substr( $reader_term_name, 7 ) );
		}

		return $this->reader_ids;
	}

	/**
	 * Add a reader to the paper.
	 *
	 * @param int $user_id ID of the user being added to the paper as a reader.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function add_reader( $user_id ) {
		$user = new WP_User( $user_id );
		if ( ! $user->ID ) {
			return new WP_Error( 'user_not_found', __( 'No user found by that ID.', 'social-paper' ) );
		}

		$set = wp_set_object_terms( $this->id, array( 'reader_' . $user_id ), 'cacsp_paper_reader', true );

		$this->reader_ids = null;

		if ( is_wp_error( $set ) || empty( $set ) ) {
			return $set;
		} else {
			return true;
		}
	}

	/**
	 * Remove a reader from a paper.
	 *
	 * @param int $user_id ID of the user.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function remove_reader( $user_id ) {
		if ( ! is_numeric( $user_id ) ) {
			return;
		}

		$user_id = (int) $user_id;

		$user = new WP_User( $user_id );
		if ( ! $user->ID ) {
			return new WP_Error( 'user_not_found', __( 'No user found by that ID.', 'social-paper' ) );
		}

		$paper_readers = $this->get_reader_ids();
		if ( ! in_array( $user_id, $paper_readers, true ) ) {
			return new WP_Error( 'reader_not_found', __( 'That user is not a reader of this paper.', 'social-paper' ), array( 'paper_id' => $this->id, 'group_id' => $user_id ) );
		}

		$removed = wp_remove_object_terms( $this->id, 'reader_' . $user_id , 'cacsp_paper_reader' );

		$this->reader_ids = null;

		return $removed;
	}

	public function __get( $key ) {
		$value = null;

		if ( isset( $this->post_obj->{$key} ) ) {
			$value = $this->post_obj->{$key};
		} else {
			switch ( $key ) {
				case 'id' :
					$value = $this->{$key};
				break;
			}
		}

		return $value;
	}

	public function __set( $key, $value ) {
		if ( isset( $this->post_obj->{$key} ) ) {
			$this->post_obj->{$key} = $value;
		} else {
			switch ( $key ) {
				case 'id' :
					$this->{$key} = intval( $value );
				break;
			}
		}
	}
}
