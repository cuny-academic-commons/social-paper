<?php

/**
 * CACSP Factory.
 *
 * @since 1.0
 */
class CACSP_UnitTest_Factory extends CACSP_UnitTest_Factory_Base {
	public $paper;

	public function __construct() {
		parent::__construct();

		$this->paper = new CACSP_UnitTest_Factory_For_Paper( $this );
	}
}

class CACSP_UnitTest_Factory_For_Paper extends WP_UnitTest_Factory_For_Post {
	public function create_object( $args ) {
		$args['post_type'] = 'cacsp_paper';
		$post_id = parent::create_object( $args );
		return $post_id;
	}

	public function update_object( $paper_id, $fields ) {
		unset( $fields['post_type'] );
		return parent::update_object( $paper_id, $fields );
	}

	public function get_object_by_id( $paper_id ) {
		return parent::get_object_by_id( $paper_id );
	}
}
