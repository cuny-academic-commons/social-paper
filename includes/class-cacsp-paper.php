<?php

/**
 * Paper object.
 *
 * @since 1.0
 */
class CACSP_Paper {
	protected $id = 0;
	protected $post_obj;

	public function __construct( $paper = false ) {
		if ( $paper ) {
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
			}
		}
	}

	public function __get( $key ) {
		$value = null;

		switch ( $key ) {
			case 'id' :
				$value = $this->{$key};
			break;
		}

		return $value;
	}
}
