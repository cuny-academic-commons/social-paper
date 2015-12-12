<?php

if ( ! function_exists( 'bp_is_active' ) || ! bp_is_active( 'groups' ) ) return;

/**
 * @group paper
 * @group group
 */
class CACSP_Tests_ClassCacspPaperGroupIntegration extends CACSP_UnitTestCase {
	public function test_connect_to_group_should_error_for_nonexistent_group() {
		$p = $this->factory->paper->create();
		$paper = new CACSP_Paper( $p );

		$actual = $paper->connect_to_group( 123 );
		$this->assertWPError( $actual );
	}

	public function test_connect_to_group_successful_connection() {
		$p = $this->factory->paper->create();
		$g = $this->factory->group->create();
		$paper = new CACSP_Paper( $p );

		$this->assertTrue( $paper->connect_to_group( $g ) );
		$this->assertEqualSets( array( $g ), $paper->get_group_ids() );
	}

	public function test_disconnect_from_group_should_error_for_nonexistent_group() {
		$p = $this->factory->paper->create();
		$paper = new CACSP_Paper( $p );

		$actual = $paper->disconnect_from_group( 123 );
		$this->assertWPError( $actual );
	}

	public function test_disconnect_from_group_should_error_for_group_not_connected_to_paper() {
		$p = $this->factory->paper->create();
		$g = $this->factory->group->create();
		$paper = new CACSP_Paper( $p );

		$actual = $paper->disconnect_from_group( $g );
		$this->assertWPError( $actual );
	}

	public function test_disconnect_from_group_successful_removal() {
		$p = $this->factory->paper->create();
		$g = $this->factory->group->create();
		$paper = new CACSP_Paper( $p );
		$paper->connect_to_group( $g );

		$this->assertTrue( $paper->disconnect_from_group( $g ) );
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

	public function test_get_papers_of_group() {
		$papers = $this->factory->paper->create_many( 2 );
		$groups = $this->factory->group->create_many( 2 );

		$p0 = new CACSP_Paper( $papers[0] );
		$p1 = new CACSP_Paper( $papers[1] );

		$p0->connect_to_group( $groups[0] );
		$p1->connect_to_group( $groups[1] );

		$found0 = cacsp_get_papers_of_group( $groups[0] );
		$this->assertEqualSets( array( $papers[0] ), wp_list_pluck( $found0, 'ID' ) );

		$found1 = cacsp_get_papers_of_group( $groups[1] );
		$this->assertEqualSets( array( $papers[1] ), wp_list_pluck( $found1, 'ID' ) );
	}

	public function test_get_papers_of_group_cache() {
		global $wpdb;

		$papers = $this->factory->paper->create_many( 2 );
		$group = $this->factory->group->create();

		$p0 = new CACSP_Paper( $papers[0] );
		$p0->connect_to_group( $group );

		$found = cacsp_get_papers_of_group( $group );
		$this->assertEqualSets( array( $papers[0] ), wp_list_pluck( $found, 'ID' ) );

		$num_queries = $wpdb->num_queries;

		$found = cacsp_get_papers_of_group( $group );
		$this->assertEqualSets( array( $papers[0] ), wp_list_pluck( $found, 'ID' ) );
		$this->assertSame( $num_queries, $wpdb->num_queries );

		$p0->disconnect_from_group( $group );
		$found = cacsp_get_papers_of_group( $group );
		$this->assertEqualSets( array(), wp_list_pluck( $found, 'ID' ) );

		$p0->connect_to_group( $group );
		$found = cacsp_get_papers_of_group( $group );
		$this->assertEqualSets( array( $papers[0] ), wp_list_pluck( $found, 'ID' ) );
	}
}
