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

	public function test_exists_should_be_false_for_nonexistent_post() {
		$paper = new CACSP_Paper( 12345 );
		$this->assertFalse( $paper->exists() );
	}

	public function test_exists_should_be_false_for_post_of_wrong_type() {
		$p = $this->factory->post->create( array( 'post_type' => 'post' ) );
		$paper = new CACSP_Paper( $p );
		$this->assertFalse( $paper->exists() );
	}

	public function test_exists_should_be_true_for_paper() {
		$p = $this->factory->paper->create();
		$paper = new CACSP_Paper( $p );
		$this->assertTrue( $paper->exists() );
	}

	public function test_set_status_should_return_false_for_invalid_status() {
		$p = $this->factory->paper->create();
		$paper = new CACSP_Paper( $p );
		$this->assertFalse( $paper->set_status( 'foo' ) );
	}

	public function test_set_status_from_public_to_protected() {
		$p = $this->factory->paper->create();
		$this->assertFalse( cacsp_paper_is_protected( $p ) );

		$paper = new CACSP_Paper( $p );
		$this->assertTrue( $paper->set_status( 'protected' ) );
		$this->assertTrue( cacsp_paper_is_protected( $p ) );
	}

	public function test_set_status_from_public_to_private() {
		$p = $this->factory->paper->create();
		$this->assertFalse( cacsp_paper_is_protected( $p ) );

		$paper = new CACSP_Paper( $p );
		$this->assertTrue( $paper->set_status( 'private' ) );
		$this->assertTrue( cacsp_paper_is_protected( $p ) );
	}

	public function test_set_status_from_protected_to_public() {
		$p = $this->factory->paper->create();
		$paper = new CACSP_Paper( $p );
		$paper->set_status( 'protected' );
		$this->assertTrue( cacsp_paper_is_protected( $p ) );

		$this->assertTrue( $paper->set_status( 'public' ) );
		$this->assertFalse( cacsp_paper_is_protected( $p ) );
	}

	public function test_set_status_from_public_to_public() {
		$p = $this->factory->paper->create();
		$this->assertFalse( cacsp_paper_is_protected( $p ) );

		$paper = new CACSP_Paper( $p );
		$this->assertFalse( $paper->set_status( 'public' ) );
		$this->assertFalse( cacsp_paper_is_protected( $p ) );
	}

	public function test_set_status_from_protected_to_protected() {
		$p = $this->factory->paper->create();
		$paper = new CACSP_Paper( $p );
		$paper->set_status( 'protected' );
		$this->assertTrue( cacsp_paper_is_protected( $p ) );

		$this->assertFalse( $paper->set_status( 'protected' ) );
		$this->assertTrue( cacsp_paper_is_protected( $p ) );
	}
}
