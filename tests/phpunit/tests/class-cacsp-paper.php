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

	public function test_save_should_update_wp_post_properties() {
		$p = $this->factory->paper->create();
		$paper = new CACSP_Paper( $p );

		$paper->post_title = 'Foo';
		$paper->post_content = 'Bar';

		$saved = $paper->save();

		$this->assertNotEmpty( $saved );
		$this->assertSame( 'Foo', $paper->post_title );
		$this->assertSame( 'Bar', $paper->post_content );
	}
}
