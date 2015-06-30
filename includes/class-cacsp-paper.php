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
