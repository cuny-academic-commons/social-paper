<?php

/**
 * @group caps
 */
class CACSP_Tests_Caps extends CACSP_UnitTestCase {
	protected $current_user;
	protected $user;

	public function setUp() {
		parent::setUp();
		$this->current_user = get_current_user_id();
	}

	public function tearDown() {
		$this->set_current_user( $this->current_user );
	}

	public function test_subscriber_can_publish_paper() {
		$u = $this->factory->user->create();
		$this->set_current_user( $u );
		$this->assertTrue( current_user_can( 'publish_papers' ) );
	}

	public function test_subscriber_cannot_edit_other_papers() {
		$p = $this->factory->paper->create();
		$u = $this->factory->user->create();

		$this->set_current_user( $u );

		$this->assertFalse( current_user_can( 'edit_paper', $p ) );
	}

	public function test_members_of_associated_group_should_be_able_to_read_protected_paper() {
		$p = $this->factory->paper->create();
		$u = $this->factory->user->create();
		$g = $this->factory->group->create();
		$this->add_user_to_group( $u, $g );

		$paper = new CACSP_Paper( $p );
		$paper->set_status( 'protected' );

		$this->set_current_user( $u );
		$this->assertFalse( current_user_can( 'read_paper', $p ) );

		$paper->connect_to_group( $g );
		$this->assertTrue( current_user_can( 'read_paper', $p ) );
	}
}
