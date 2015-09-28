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
}