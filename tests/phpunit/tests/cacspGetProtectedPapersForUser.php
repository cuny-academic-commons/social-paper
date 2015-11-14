<?php

/**
 * @group caps
 */
class CACSP_Tests_CacspGetProtectedPapersForUser extends CACSP_UnitTestCase {
	public function test_should_include_private_papers_from_group_where_user_has_no_group_memberships() {
		$p = $this->factory->paper->create();
		$paper = new CACSP_Paper( $p );
		$paper->set_status( 'protected' );

		$u = $this->factory->user->create();
		$g = $this->factory->group->create();
		$paper->connect_to_group( $g );

		$this->assertContains( $p, cacsp_get_protected_papers_for_user( $u ) );
	}

	public function test_should_include_private_papers_from_group_where_user_is_member_of_another_group() {
		$p = $this->factory->paper->create();
		$paper = new CACSP_Paper( $p );
		$paper->set_status( 'protected' );

		$u = $this->factory->user->create();
		$groups = $this->factory->group->create_many( 2 );
		$paper->connect_to_group( $groups[1] );
		$this->add_user_to_group( $u, $groups[0] );

		$this->assertContains( $p, cacsp_get_protected_papers_for_user( $u ) );
	}

	public function test_should_not_include_private_papers_from_groups_where_user_is_member() {
		$p = $this->factory->paper->create();
		$paper = new CACSP_Paper( $p );
		$paper->set_status( 'protected' );

		$u = $this->factory->user->create();
		$g = $this->factory->group->create();
		$paper->connect_to_group( $g );
		$this->add_user_to_group( $u, $g );

		$this->assertNotContains( $p, cacsp_get_protected_papers_for_user( $u ) );
	}

	public function test_should_not_include_private_papers_when_user_is_only_reader() {
		$p = $this->factory->paper->create();
		$paper = new CACSP_Paper( $p );
		$paper->set_status( 'protected' );

		$u = $this->factory->user->create();
		$paper->add_reader( $u );

		$this->assertNotContains( $p, cacsp_get_protected_papers_for_user( $u ) );
	}

	public function test_should_not_include_private_papers_when_user_is_one_of_multiple_readers() {
		$p = $this->factory->paper->create();
		$paper = new CACSP_Paper( $p );
		$paper->set_status( 'protected' );

		$users = $this->factory->user->create_many( 2 );
		$paper->add_reader( $users[0] );
		$paper->add_reader( $users[1] );

		$this->assertNotContains( $p, cacsp_get_protected_papers_for_user( $users[0] ) );
		$this->assertNotContains( $p, cacsp_get_protected_papers_for_user( $users[1] ) );
	}

	public function test_should_include_private_papers_when_paper_has_no_readers() {
		$p = $this->factory->paper->create();
		$paper = new CACSP_Paper( $p );
		$paper->set_status( 'protected' );

		$u = $this->factory->user->create();

		$this->assertContains( $p, cacsp_get_protected_papers_for_user( $u ) );
	}

	public function test_should_include_private_papers_when_paper_has_readers_but_user_is_not_among_them() {
		$p = $this->factory->paper->create();
		$paper = new CACSP_Paper( $p );
		$paper->set_status( 'protected' );

		$users = $this->factory->user->create_many( 2 );
		$paper->add_reader( $users[0] );

		$this->assertContains( $p, cacsp_get_protected_papers_for_user( $users[1] ) );
	}

	public function test_should_not_include_private_papers_written_by_user() {
		$u = $this->factory->user->create();

		$p = $this->factory->paper->create( array(
			'post_author' => $u,
		) );
		$paper = new CACSP_Paper( $p );
		$paper->set_status( 'protected' );

		$this->assertNotContains( $p, cacsp_get_protected_papers_for_user( $u ) );
	}
}
