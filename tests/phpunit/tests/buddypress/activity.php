<?php

/**
 * @group buddypress
 * @group activity
 */
class CACSP_Tests_BuddyPress_Activity extends CACSP_UnitTestCase {
	public function test_new_cacsp_paper_activity_action_for_untitled_paper() {
		$u = $this->factory->user->create( array(
			'display_name' => 'Foo',
		) );
		$p = $this->factory->paper->create( array(
			'post_title' => '',
			'post_author' => $u,
		) );

		$a = bp_activity_get( array(
			'filter' => array(
				'action' => 'new_cacsp_paper',
				'secondary_id' => $p,
			),
		) );

		$paper_link = get_permalink( $p );
		$user_link = bp_core_get_userlink( $u );
		$expected = sprintf(
			__( '%1$s created <a href="%s">an untitled paper</a>', 'social-paper' ),
			$user_link,
			$paper_link
		);

		$this->assertSame( $expected, $a['activities'][0]->action );
	}

	public function test_new_cacsp_paper_activity_action_for_paper_with_title() {
		$u = $this->factory->user->create( array(
			'display_name' => 'Foo',
		) );
		$p = $this->factory->paper->create( array(
			'post_title' => 'Bar',
			'post_author' => $u,
		) );

		$a = bp_activity_get( array(
			'filter' => array(
				'action' => 'new_cacsp_paper',
				'secondary_id' => $p,
			),
		) );

		$paper_link = get_permalink( $p );
		$user_link = bp_core_get_userlink( $u );
		$expected = sprintf(
			__( '%1$s created a new paper %2$s', 'social-paper' ),
			$user_link,
			sprintf( '<a href="%s">%s</a>', esc_url( $paper_link ), 'Bar' )
		);

		$this->assertSame( $expected, $a['activities'][0]->action );
	}

	public function test_new_cacsp_comment_activity_action_for_untitled_paper() {
		$u = $this->factory->user->create( array(
			'display_name' => 'Foo',
		) );
		$p = $this->factory->paper->create( array(
			'post_title' => '',
			'post_author' => $u,
		) );
		$c = $this->factory->comment->create( array(
			'user_id' => $u,
			'comment_post_ID' => $p,
		) );

		$a = bp_activity_get( array(
			'filter' => array(
				'action' => 'new_cacsp_comment',
				'secondary_id' => $p,
			),
		) );

		$paper_link = get_permalink( $p );
		$user_link = bp_core_get_userlink( $u );
		$expected = sprintf(
			__( '%1$s commented on <a href="%2$s">an untitled paper</a>', 'social-paper' ),
			$user_link,
			$paper_link
		);

		$this->assertSame( $expected, $a['activities'][0]->action );
	}

	public function test_activity_action_for_paper_with_title() {
		$u = $this->factory->user->create( array(
			'display_name' => 'Foo',
		) );
		$p = $this->factory->paper->create( array(
			'post_title' => 'Bar',
			'post_author' => $u,
		) );
		$c = $this->factory->comment->create( array(
			'user_id' => $u,
			'comment_post_ID' => $p,
		) );

		$a = bp_activity_get( array(
			'filter' => array(
				'action' => 'new_cacsp_comment',
				'secondary_id' => $p,
			),
		) );

		$paper_link = get_permalink( $p );
		$user_link = bp_core_get_userlink( $u );
		$expected = sprintf(
			__( '%1$s commented on the paper %2$s', 'social-paper' ),
			$user_link,
			sprintf( '<a href="%s">%s</a>', esc_url( $paper_link ), 'Bar' )
		);

		$this->assertSame( $expected, $a['activities'][0]->action );
	}

	public function test_new_cacsp_edit_activity_action_for_untitled_paper() {
		$u = $this->factory->user->create( array(
			'display_name' => 'Foo',
		) );
		$p = $this->factory->paper->create( array(
			'post_title' => '',
			'post_author' => $u,
		) );

		wp_set_current_user( $u );
		wp_update_post( array( 'ID' => $p, 'post_content' => 'New content' ) );

		$a = bp_activity_get( array(
			'filter' => array(
				'action' => 'new_cacsp_edit',
				'secondary_id' => $p,
			),
		) );

		$paper_link = get_permalink( $p );
		$user_link = bp_core_get_userlink( $u );
		$expected = sprintf(
			__( '%1$s edited <a href="%2$s">an untitled paper</a>', 'social-paper' ),
			$user_link,
			$paper_link
		);

		$this->assertSame( $expected, $a['activities'][0]->action );
	}

	public function test_new_cacsp_edit_activity_action_for_paper_with_title() {
		$u = $this->factory->user->create( array(
			'display_name' => 'Foo',
		) );
		$p = $this->factory->paper->create( array(
			'post_title' => 'Bar',
			'post_author' => $u,
		) );

		wp_set_current_user( $u );
		wp_update_post( array( 'ID' => $p, 'post_content' => 'New content' ) );

		$a = bp_activity_get( array(
			'filter' => array(
				'action' => 'new_cacsp_edit',
				'secondary_id' => $p,
			),
		) );

		$paper_link = get_permalink( $p );
		$user_link = bp_core_get_userlink( $u );
		$expected = sprintf(
			__( '%1$s edited the paper %2$s', 'social-paper' ),
			$user_link,
			sprintf( '<a href="%s">%s</a>', esc_url( $paper_link ), 'Bar' )
		);

		$this->assertSame( $expected, $a['activities'][0]->action );
	}

	public function test_cacsp_paper_added_to_group_activity_action_for_untitled_paper() {
		$u = $this->factory->user->create( array(
			'display_name' => 'Foo',
		) );
		$p = $this->factory->paper->create( array(
			'post_title' => '',
			'post_author' => $u,
		) );

		$g = $this->factory->group->create();

		$paper = new CACSP_Paper( $p );
		$paper->connect_to_group( $g );

		$a = bp_activity_get( array(
			'filter' => array(
				'action' => 'cacsp_paper_added_to_group',
				'secondary_id' => $p,
			),
		) );

		$paper_link = get_permalink( $p );
		$user_link = bp_core_get_userlink( $u );
		$group = groups_get_group( array( 'group_id' => $g ) );
		$expected = sprintf(
			__( '%1$s added <a href="%2$s">an untitled paper</a> to the group %3$s', 'social-paper' ),
			$user_link,
			$paper_link,
			sprintf( '<a href="%s">%s</a>', esc_url( bp_get_group_permalink( $group ) ), esc_html( stripslashes( $group->name ) ) )
		);

		$this->assertSame( $expected, $a['activities'][0]->action );
	}

	public function test_cacsp_paper_added_to_group_activity_action_for_paper_with_title() {
		$u = $this->factory->user->create( array(
			'display_name' => 'Foo',
		) );
		$p = $this->factory->paper->create( array(
			'post_title' => 'Bar',
			'post_author' => $u,
		) );
		$g = $this->factory->group->create();

		$paper = new CACSP_Paper( $p );
		$paper->connect_to_group( $g );

		$a = bp_activity_get( array(
			'filter' => array(
				'action' => 'cacsp_paper_added_to_group',
				'secondary_id' => $p,
			),
		) );

		$paper_link = get_permalink( $p );
		$user_link = bp_core_get_userlink( $u );
		$group = groups_get_group( array( 'group_id' => $g ) );
		$expected = sprintf(
			__( '%1$s added the paper %2$s to the group %3$s', 'social-paper' ),
			$user_link,
			sprintf( '<a href="%s">%s</a>', esc_url( $paper_link ), 'Bar' ),
			sprintf( '<a href="%s">%s</a>', esc_url( bp_get_group_permalink( $group ) ), esc_html( stripslashes( $group->name ) ) )
		);

		$this->assertSame( $expected, $a['activities'][0]->action );
	}
}
