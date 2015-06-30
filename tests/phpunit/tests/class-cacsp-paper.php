<?php

/**
 * @group paper
 */
class CACSP_Tests_ClassCacspPaper extends CACSP_UnitTestCase {
	public function test_constructor_should_accept_proper_post_object() {
		$p = $this->factory->paper->create();
		$p_obj = get_post( $p );

		$paper = new CACSP_Paper( $p_obj );

		$this->assertSame( $p, $paper->id );
	}

	public function test_constructor_should_accept_post_object_of_wrong_post_type() {
		$p = $this->factory->post->create();
		$p_obj = get_post( $p );

		$paper = new CACSP_Paper( $p_obj );

		$this->assertSame( 0, $paper->id );
	}

	public function test_constructor_should_accept_post_id_of_proper_paper_object() {
		$p = $this->factory->paper->create();

		$paper = new CACSP_Paper( $p );

		$this->assertSame( $p, $paper->id );
	}

	public function test_constructor_should_reject_post_id_of_post_of_wrong_post_type() {
		$p = $this->factory->post->create();

		$paper = new CACSP_Paper( $p );

		$this->assertSame( 0, $paper->id );
	}
}
