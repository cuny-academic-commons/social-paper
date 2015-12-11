<?php

/**
 * @group paper
 * @group reader
 */
class CACSP_Tests_ClassCacspPaperReader extends CACSP_UnitTestCase {
	public function test_add_reader_should_error_for_nonexistent_user() {
		$p = $this->factory->paper->create();
		$paper = new CACSP_Paper( $p );

		$actual = $paper->add_reader( 123 );
		$this->assertWPError( $actual );
	}

	public function test_add_reader_successful_connection() {
		$p = $this->factory->paper->create();
		$u = $this->factory->user->create();
		$paper = new CACSP_Paper( $p );

		$this->assertTrue( $paper->add_reader( $u ) );
		$this->assertEqualSets( array( $u ), $paper->get_reader_ids() );
	}

	public function test_remove_reader_should_error_for_nonexistent_user() {
		$p = $this->factory->paper->create();
		$paper = new CACSP_Paper( $p );

		$actual = $paper->remove_reader( 123 );
		$this->assertWPError( $actual );
	}

	public function test_remove_reader_should_error_for_user_who_is_not_a_reader() {
		$p = $this->factory->paper->create();
		$u = $this->factory->user->create();
		$paper = new CACSP_Paper( $p );

		$actual = $paper->remove_reader( $u );
		$this->assertWPError( $actual );
	}

	public function test_remove_reader_successful_removal() {
		$p = $this->factory->paper->create();
		$u = $this->factory->user->create();
		$paper = new CACSP_Paper( $p );
		$paper->add_reader( $u );

		$this->assertTrue( $paper->remove_reader( $u ) );
	}

	public function test_pre_get_posts_filter() {
		$papers = $this->factory->paper->create_many( 3 );
		$groups = $this->factory->group->create_many( 3 );

		$p0 = new CACSP_Paper( $papers[0] );
		$p1 = new CACSP_Paper( $papers[1] );
		$p2 = new CACSP_Paper( $papers[2] );

		$p0->connect_to_group( $groups[0] );
		$p1->connect_to_group( $groups[1] );
		$p2->connect_to_group( $groups[2] );

		$q = new WP_Query( array(
			'post_type' => 'cacsp_paper',
			'bp_group' => array( $groups[0], $groups[2] ),
			'fields' => 'ids',
		) );

		$this->assertEqualSets( array( $papers[0], $papers[2] ), $q->posts );
	}

	public function test_cacsp_get_potential_reader_ids_should_cache_results() {
		$u = $this->factory->user->create();
		$p = $this->factory->paper->create( array(
			'post_author' => $u,
		) );

		$users = $this->factory->user->create_many( 4 );

		$paper = new CACSP_Paper( $p );
		$paper->add_reader( $users[0] );

		$group = $this->factory->group->create( array(
			'creator_id' => $u,
		) );
		$this->add_user_to_group( $users[1], $group );

		friends_add_friend( $u, $users[2], true );

		if ( method_exists( $this, 'set_current_user' ) ) {
			$this->set_current_user( $u );
		} else {
			wp_set_current_user( $u );
		}

		$found = cacsp_get_potential_reader_ids( $p );
		$this->assertEqualSets( array( $users[0], $users[1], $users[2] ), $found );

		global $wpdb;
		$num_queries = $wpdb->num_queries;

		$found = cacsp_get_potential_reader_ids( $p );
		$this->assertEqualSets( array( $users[0], $users[1], $users[2] ), $found );
		$this->assertSame( $num_queries, $wpdb->num_queries );

		// Invalidation. Ever hear of "unit" tests? Me neither.

		// Remove reader.
		$paper->remove_reader( $users[0] );
		$found = cacsp_get_potential_reader_ids( $p );
		$this->assertEqualSets( array( $users[1], $users[2] ), $found );

		// Add reader.
		$paper->add_reader( $users[0] );
		$found = cacsp_get_potential_reader_ids( $p );
		$this->assertEqualSets( array( $users[0], $users[1], $users[2] ), $found );

		// Remove group member.
		$member = new BP_Groups_Member( $users[1], $group );
		$member->remove();
		$found = cacsp_get_potential_reader_ids( $p );
		$this->assertEqualSets( array( $users[0], $users[2] ), $found );

		// Add group member.
		$this->add_user_to_group( $users[1], $group );
		$found = cacsp_get_potential_reader_ids( $p );
		$this->assertEqualSets( array( $users[0], $users[1], $users[2] ), $found );

		// Remove friend.
		friends_remove_friend( $u, $users[2] );
		$found = cacsp_get_potential_reader_ids( $p );
		$this->assertEqualSets( array( $users[0], $users[1] ), $found );

		// Add friend.
		friends_add_friend( $u, $users[2], true );
		$found = cacsp_get_potential_reader_ids( $p );
		$this->assertEqualSets( array( $users[0], $users[1], $users[2] ), $found );
	}
}
