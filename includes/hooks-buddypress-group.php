<?php

/**
 * Social Paper Group Extension
 *
 * This class extends BP_Group_Extension to create the screens our plugin requires.
 *
 * @see https://codex.buddypress.org/developer/group-extension-api/
 */

// prevent problems during upgrade or when Groups are disabled
if ( ! class_exists( 'BP_Group_Extension' ) ) return;

/**
 * Group Extension object.
 *
 * @since 1.0
 */
class CACSP_Group_Extension extends BP_Group_Extension {

	/**
	 * Initialises this object
	 *
	 * @return void
	 */
	function __construct() {

		// init vars with something sensible
		$name = __( 'Papers', 'social-paper' );
		$slug = 'papers';
		$pos = 31;

		// init setup array
		$args = array(
			'name' => $name,
			'slug' => $slug,
			'nav_item_position' => $pos,
			'enable_create_step' => false,
		);

		// init
		parent::init( $args );

	}

	/**
	 * Display our content when the nav item is selected
	 *
	 * @return void
	 */
	public function display( $group_id = null ) {
		// Perform query for this group.
		$search_query = '';
		if ( cacsp_is_search() ) {
			$search_query = cacsp_get_search_terms();
		}

		$group_query = new WP_Query( array(
			'post_type' => 'cacsp_paper',
			'post_status' => 'publish',
			'bp_group' => $group_id,
			'paged' => get_query_var( 'paged' ) >= 1 ? get_query_var( 'paged' ) : 1, 
			's' => $search_query,
		) );
		?>
		<div class="entry-content">

		<?php if ( $group_query->have_posts() ) :
			$num_papers = $group_query->found_posts;
			
			// Only need total counts, description, new paper button on non-search group paper directories
			if ( !cacsp_is_search() ) {
				$group_paper_comment_count = wp_cache_get( $group_id, 'cacsp_group_paper_comment_counts' );
				if ( false === $group_paper_comment_count ) {
					$group_query_not_paged = new WP_Query( array(
						'post_type' => 'cacsp_paper',
						'post_status' => 'publish',
						'bp_group' => $group_id,
						'posts_per_page' => -1,
					) );
					$group_paper_comment_count = 0;
					foreach( $group_query_not_paged->posts as $paper ) {
						$group_paper_comment_count += $paper->comment_count;
					}
					wp_cache_set( $group_id, $group_paper_comment_count, 'cacsp_group_paper_comment_counts' );			
				}
				$comments_text = sprintf( _n( '%s comment', '%s comments', $group_paper_comment_count, 'social-paper' ), $group_paper_comment_count );
				$papers_text = sprintf( _n( '%s paper', '%s papers', $num_papers, 'social-paper' ), $num_papers );
				$description_text = sprintf( esc_html__( 'This group has %1$s and %2$s.', 'social-paper' ), $papers_text, $comments_text );
				$new_group_paper_url = add_query_arg( 'group_id', $group_id, cacsp_get_the_new_paper_link() );
			} else {
				$results_text = sprintf( _n( '%s result', '%s results', $num_papers, 'social-paper' ), $num_papers );
				$description_text = sprintf( esc_html__( 'Found %1$s for search "%2$s".', 'social-paper' ), $results_text, cacsp_get_search_terms() );
			}
			?>
			<div class="cacsp-group-description">
				<p class="description-text"><?php echo $description_text; ?></p>
				<a href="<?php echo esc_url( $new_group_paper_url ); ?>" class="bp-group-new-social-paper"><?php esc_html_e( 'Create new paper', 'social-paper' ); ?></a>
			</div>

			<div class="cacsp-group-pagination">
				<div class="cacsp-group-pagination-count">
				<?php
					// Set pagination values
					$posts_per_page = $group_query->query_vars['posts_per_page'];
					$curr_page  = $group_query->query_vars['paged'];
					$from_num = intval( ( $curr_page - 1 ) * $posts_per_page ) + 1;
					$to_num    = ( $from_num + $posts_per_page - 1 > $group_query->found_posts ) ? $group_query->found_posts : $from_num + $posts_per_page - 1 ;
					$total = (int) !empty( $group_query->found_posts ) ? $group_query->found_posts : $group_query->post_count;
					$pagination_string = '';
					// Several papers in a group with a single page of papers
					if ( (int)$group_query->max_num_pages == 1 ) {

						$pagination_string = sprintf( _n( 'Viewing %1$s paper', 'Viewing %1$s papers', $total, 'social-paper' ), $total );

					// Several papers in a group with several pages of papers
					} else {
						$pagination_string = sprintf( _n( 'Viewing paper %2$s (of %4$s total)', 'Viewing %1$s papers - %2$s through %3$s (of %4$s total)', $group_query->post_count, 'social-paper' ), $group_query->post_count, $from_num, $to_num, $total );
					}
					esc_html_e( $pagination_string );
				?>
				</div>

				<div class="cacsp-group-pagination-links">
					<?php
						$args = array(
							'total' => (int)ceil( $group_query->max_num_pages ),
							'current' => $curr_page,
							'mid_size' => 1,
							'prev_text' => is_rtl() ? '&rarr;' : '&larr;',
							'next_text' => is_rtl() ? '&larr;' : '&rarr;',
						);
						echo paginate_links( $args );
					?>
				</div>
			</div>

			<?php cacsp_get_template_part( 'search-social-paper', 'buddypress' ); ?>
			
			<ul class="item-list">

			<?php while ( $group_query->have_posts() ) : $group_query->the_post(); ?>
				<?php cacsp_get_template_part( 'list-social-paper', 'buddypress' ); ?>
			<?php endwhile; ?>

			</ul>

		<?php else : ?>

			<div class="cacsp-template-notice info">
				<p><?php esc_html_e( 'There are no papers in this group.', 'social-paper' ); ?></p>
				<a href="<?php cacsp_the_new_paper_link(); ?>" class="create-new-paper"><?php esc_html_e( 'Create new paper', 'social-paper' ); ?></a>
			</div>

			<?php do_action( 'cacsp_no_papers_in_group' ); ?>
		<?php endif; ?>

		</div>

		<?php
	}

} // class ends

// register our class
bp_register_group_extension( 'CACSP_Group_Extension' );

/**
 * Invalidate group social paper comment count when new comments are added, edited, or their status changes
 *
 * @param int $comment_id the unique ID of the comment added or changed
 */
function cacsp_invalidate_group_paper_comment_count_cache( $comment_id ) {
	$comment = get_comment( $comment_id );
	if( ! $comment ) {
		return;
	}

	$paper = new CACSP_Paper( $comment->comment_post_ID );
	if ( ! $paper->exists() ) {
		return;
	}

	$group_ids = $paper->get_group_ids();
	if ( ! is_array( $group_ids ) ) {
		return;
	}
	
	foreach( $group_ids as $group_id ) {
		wp_cache_delete( $group_id, 'cacsp_group_paper_comment_counts' );
	}
}
add_action( 'wp_insert_comment', 'cacsp_invalidate_group_paper_comment_count_cache' );
add_action( 'wp_set_comment_status', 'cacsp_invalidate_group_paper_comment_count_cache' );
add_action( 'edit_comment', 'cacsp_invalidate_group_paper_comment_count_cache' );

/**
 * Register group connection taxonomy.
 *
 * Fires at init:15 to ensure we have a chance to register the 'cacsp_paper' post type first.
 */
function cacsp_register_group_connection_taxonomy() {
	register_taxonomy( 'cacsp_paper_group', 'cacsp_paper', array(
		'public' => false,
	) );
}
add_action( 'init', 'cacsp_register_group_connection_taxonomy', 15 );

/**
 * Modify `WP_Query` requests for the 'bp_group' param.
 *
 * @param WP_Query Query object, passed by reference.
 */
function cacsp_filter_query_for_bp_group( $query ) {
	// Only modify 'event' queries.
	$post_types = $query->get( 'post_type' );
	if ( ! in_array( 'cacsp_paper', (array) $post_types ) ) {
		return;
	}

	$bp_group = $query->get( 'bp_group', null );
	if ( null === $bp_group ) {
		return;
	}

	if ( ! is_array( $bp_group ) ) {
		$group_ids = array( $bp_group );
	} else {
		$group_ids = $bp_group;
	}

	// Empty array will always return no results.
	if ( empty( $group_ids ) ) {
		$query->set( 'post__in', array( 0 ) );
		return;
	}

	// Convert group IDs to a tax query.
	$tq = $query->get( 'tax_query' );
	$group_terms = array();
	foreach ( $group_ids as $group_id ) {
		$group_terms[] = 'group_' . $group_id;
	}

	$tq[] = array(
		'taxonomy' => 'cacsp_paper_group',
		'terms' => $group_terms,
		'field' => 'name',
		'operator' => 'IN',
	);

	$query->set( 'tax_query', $tq );
}
add_action( 'pre_get_posts', 'cacsp_filter_query_for_bp_group' );

/**
 * Filter activity query args to include group-connected papers.
 */
function cacsp_filter_activity_args_for_groups( $args ) {
	if ( 'groups' !== $args['object'] && 'groups' !== $args['scope'] ) {
		return $args;
	}

	// Distinguish single group streams from "my groups".
	if ( ! empty( $args['primary_id'] ) ) {
		$group_ids = array( (int) $args['primary_id'] );
	} else {
		$group_ids = cacsp_get_groups_of_user( bp_loggedin_user_id() );
	}

	$group_filter = array(
		'relation' => 'AND',
		array(
			'column' => 'component',
			'value'  => 'groups',
		),
	);

	// Only bother doing a group_id clause if this is a single group, or my-groups of a logged-in user.
	if ( ! empty( $group_ids ) ) {
		$group_filter[] = array(
			'column'  => 'item_id',
			'value'   => $group_ids,
			'compare' => 'IN',
		);
	}

	$papers_of_group = get_posts( array(
		'post_type'      => 'cacsp_paper',
		'post_status'    => 'publish',
		'bp_group'       => $group_ids,
		'posts_per_page' => -1,
		'fields'         => 'ids',
	) );

	if ( empty( $papers_of_group ) ) {
		$papers_of_group = array( 0 );
	}

	$paper_filter = array(
		'relation' => 'AND',
		array(
			'column' => 'component',
			'value'  => 'activity',
		),
		array(
			'column'  => 'type',
			'value'   => array( 'new_cacsp_paper' ),
			'compare' => 'IN',
		),
		array(
			'column'  => 'secondary_item_id',
			'value'   => $papers_of_group,
			'compare' => 'IN',
		),
	);

	$new_filter_query = array(
		'relation' => 'OR',
		$group_filter,
		$paper_filter,
	);

	// Merge with existing filters.
	if ( ! empty( $args['filter_query'] ) ) {
		$new_filter_query = array(
			'relation' => 'AND',
			$new_filter_query,
			$args['filter_query'],
		);
	}

	// Replace in the query args, and remove original group filters.
	if ( ! is_array( $args ) ) {
		$args = array();
	}

	$args['filter_query'] = $new_filter_query;
	$args['primary_id'] = '';
	$args['object'] = '';
	$args['scope'] = '';

	return $args;
}
add_filter( 'bp_after_has_activities_parse_args', 'cacsp_filter_activity_args_for_groups' );

/**
 * Create an activity item when a paper is added to a group.
 *
 * @param CACSP_Paper $paper    Paper object.
 * @param int         $group_id ID of the group.
 */
function cacsp_create_added_to_group_activity( CACSP_Paper $paper, $group_id ) {
	if ( ! $paper->ID ) {
		return;
	}

	// Only published paper should have activity created.
	$post_status = $paper->post_status;
	if ( 'publish' !== $post_status ) {
		return;
	}

	// The author of the edit is the one who wrote the last revision.
	if ( $revisions = wp_get_post_revisions( $paper->ID ) ) {
		// Grab the last revision, but not an autosave.
		foreach ( $revisions as $revision ) {
			if ( false !== strpos( $revision->post_name, "{$revision->post_parent}-revision" ) ) {
				$last_revision = $revision;
				break;
			}
		}
	}

	// Either revisions are disabled, or something else has gone wrong. Just use the post author.
	if ( ! isset( $last_revision ) ) {
		$rev_author = $paper->post_author;
	} else {
		$rev_author = $last_revision->post_author;
	}

	$group = groups_get_group( array( 'group_id' => $group_id ) );

	$activity_id = bp_activity_add( array(
		'component'         => 'groups',
		'type'              => 'cacsp_paper_added_to_group',
		'primary_link'      => get_permalink( $paper->ID ),
		'user_id'           => $rev_author,
		'item_id'           => $group_id,
		'secondary_item_id' => $paper->ID,
		'hide_sitewide'     => 'public' !== $group->status,
	) );

	return $activity_id;
}
add_action( 'cacsp_connected_paper_to_group', 'cacsp_create_added_to_group_activity', 10, 2 );

/**
 * When a paper is disconnected from a group, remove the "added to group" activity item.
 *
 * @param CACSP_Paper $paper    Paper object.
 * @param int         $group_id ID of the group.
 */
function cacsp_remove_added_to_group_activity( CACSP_Paper $paper, $group_id ) {
	$activity = bp_activity_get( array(
		'filter_query' => array(
			array( 'column' => 'component', 'value' => 'groups' ),
			array( 'column' => 'type', 'value' => 'cacsp_paper_added_to_group' ),
			array( 'column' => 'item_id', 'value' => $group_id ),
			array( 'column' => 'secondary_item_id', 'value' => $paper->ID ),
		),
		'update_meta_cache' => false,
	) );

	foreach ( $activity['activities'] as $a ) {
		bp_activity_delete( array( 'id' => $a->id ) );
	}
}
add_action( 'cacsp_disconnected_paper_from_group', 'cacsp_remove_added_to_group_activity', 10, 2 );

/**
 * Send 'new_cacsp_edit' activity items to BuddyPress Group Email Subscription.
 *
 * Edit activity is not associated with the 'groups' component, so BPGES will not recognize it
 * by default. This function manually triggers the necessary notifications.
 *
 * Requires BPGES 3.6.0.
 *
 * @param int $activity_id ID of the 'new_cacsp_edit' activity.
 * @param int $paper_id    ID of the paper post object.
 */
function cacsp_send_edit_activity_to_bpges( $activity_id, $paper_id ) {
	// Requires the `ass_generate_notification()` function, introduced in BPGES 3.6.0.
	if ( ! function_exists( 'ass_generate_notification' ) ) {
		return;
	}

	$paper = new CACSP_Paper( $paper_id );

	$paper_groups = $paper->get_group_ids();
	if ( empty( $paper_groups ) ) {
		return;
	}

	$activity = new BP_Activity_Activity( $activity_id );

	$args = array(
		'sender_id'   => $activity->user_id,
		'activity_id' => $activity_id,
		'action'      => $activity->action,
		'content'     => $activity->content,
		'link'        => get_permalink( $paper_id ),
	);

	foreach ( $paper_groups as $group_id ) {
		$_args = $args;
		$_args['group_id'] = $group_id;
		ass_generate_notification( $_args );
	}
}
add_action( 'cacsp_created_edit_activity', 'cacsp_send_edit_activity_to_bpges', 10, 2 );

/**
 * Format activity actions for papers connected to groups.
 *
 * Disabled for the time being. Not sure if it's valuable information.
 *
 * @param string      $action      Formatted action string.
 * @param obj         $activity    Activity item.
 * @param CACSP_Paper $paper       Paper object.
 * @param string      $paper_title Paper title.
 * @param string      $paper_link  Paper URL.
 * @param string      $user_link   User link.
 * @return string
 */
function cacsp_format_activity_action_for_group( $action, $activity, CACSP_Paper $paper, $paper_title, $paper_link, $user_link ) {
	// Don't bother doing this on a group page.
	if ( bp_is_group() ) {
		return $action;
	}

	$paper_group_ids = $paper->get_group_ids();
	if ( empty( $paper_group_ids ) ) {
		return $action;
	}

	// @todo roll our own cache support here too? Le sigh.
	$_paper_groups = groups_get_groups( array(
		'populate_extras' => false,
		'update_meta_cache' => false,
		'show_hidden' => true,
		'include' => $paper_group_ids,

	) );
	$paper_groups = $_paper_groups['groups'];

	// Only include groups that a user has access to. Groups a user is a member of come first.
	$groups_to_include = array(
		'is_member'     => array(),
		'is_not_member' => array(),
	);

	$user_groups = array();
	if ( is_user_logged_in() ) {
		$user_groups = cacsp_get_groups_of_user( bp_loggedin_user_id() );
	}

	foreach ( $paper_groups as $pg ) {
		$pg_id = (int) $pg->id;

		$k = null;
		if ( in_array( $pg_id, $user_groups, true ) ) {
			$k = 'is_member';
		} elseif ( 'public' === $pg->status ) {
			$k = 'is_not_member';
		}

		if ( $k ) {
			$groups_to_include[ $k ][ $pg->name ] = bp_get_group_permalink( $pg ) . 'papers/';
		}
	}

	$links = array();
	foreach ( $groups_to_include as $gg ) {
		ksort( $gg );
		foreach ( $gg as $group_name => $group_link ) {
			$links[] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( $group_link),
				esc_html( $group_name )
			);
		}
	}

	// @todo Other activity types.
	// 1, 2, 3 groups: show all. 4+ groups: show first two + "and x more groups".
	if ( 'new_cacsp_paper' === $activity->type ) {
		if ( count( $links ) === 1 ) {
			$action = sprintf(
				__( '%1$s created a new paper %2$s in the group %3$s', 'social-paper' ),
				$user_link,
				sprintf( '<a href="%s">%s</a>', esc_url( $paper_link ), esc_html( $paper_title ) ),
				implode( '', $links )
			);

		} elseif ( count( $links ) <= 3 ) {
			$action = sprintf(
				_n( '%1$s created a new paper %2$s in the group %3$s', '%1$s created a new paper in the groups %3$s', count( $links ), 'social-paper' ),
				$user_link,
				sprintf( '<a href="%s">%s</a>', esc_url( $paper_link ), esc_html( $paper_title ) ),
				implode( ', ', $links )
			);

		} else {
			$remainder = count( $links ) - 2;
			$action = sprintf(
				_n( '%1$s created a new paper %2$s in %3$s and %4$s more group', '%1$s created a new paper in %3$s and %4$s more groups', $remainder, 'social-paper' ),
				$user_link,
				sprintf( '<a href="%s">%s</a>', esc_url( $paper_link ), esc_html( $paper_title ) ),
				implode( ', ', array_slice( $links, 0, 2 ) ),
				number_format_i18n( $remainder )
			);
		}
	}

	return $action;
}
//add_filter( 'cacsp_format_activity_action', 'cacsp_format_activity_action_for_group', 10, 6 );

/**
 * Generate the group selector interface.
 */
function cacsp_paper_group_selector( $paper_id ) {
	$paper = new CACSP_Paper( $paper_id );
	$paper_group_ids = $paper->get_group_ids();

	$user_groups = groups_get_groups( array(
		'user_id' => bp_loggedin_user_id(),
		'type' => 'alphabetical',
	) );
	$user_group_ids = array_map( 'intval', wp_list_pluck( $user_groups['groups'], 'id' ) );

	?>
	<select name="cacsp-groups[]" multiple="multiple" style="width:100%;" id="cacsp-group-selector">
		<?php
			foreach ( $user_groups['groups'] as $group ) {
				$private = 'public' !== $group->status ? 'title="Private"' : '';
				$selected = in_array( intval( $group->id ), $paper_group_ids, true ) ? 'selected="selected"' : '';
				echo '<option value="' . esc_attr( $group->id ) . '" ' . $selected . ' ' . $private . '>' . esc_html( stripslashes( $group->name ) ) . '</option>';
				$foo = 1;
			}
		?>
	</select>
	<?php

	wp_nonce_field( 'cacsp-group-selector', 'cacsp-group-selector-nonce' );
}

/**
 * Save group selection data sent via AJAX.
 *
 * @param int $post_id ID of the post.
 */
function cacsp_save_group_connection( $post_id ) {
	if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
		return;
	}

	if ( ! isset( $_POST['social_paper_groups_nonce'] ) || ! isset( $_POST['social_paper_groups'] ) ) {
		return;
	}

	if ( ! wp_verify_nonce( $_POST['social_paper_groups_nonce'], 'cacsp-group-selector' ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$paper = new CACSP_Paper( $post_id );
	$results = array();

	$new_group_ids      = array_map( 'intval', (array) $_POST['social_paper_groups'] );
	$existing_group_ids = $paper->get_group_ids();

	// Disconnect from groups no longer listed.
	$disconnected_groups = array_diff( $existing_group_ids, $new_group_ids );
	if ( $disconnected_groups ) {
		foreach ( $disconnected_groups as $group_id ) {
			$results['disconnected'][ $group_id ] = $paper->disconnect_from_group( $group_id );
		}
	}

	// Connect to new groups.
	$connected_groups = array_diff( $new_group_ids, $existing_group_ids );
	if ( $connected_groups ) {
		foreach ( $connected_groups as $group_id ) {
			$results['connected'][ $group_id ] = $paper->connect_to_group( $group_id );
		}
	}

	// Can't do much with results :(
}
add_action( 'save_post', 'cacsp_save_group_connection' );

/**
 * Get a list of group links corresponding to a paper.
 *
 * Will only return groups that the current user has access to.
 *
 * @since 1.0.0
 *
 * @param int $paper_id ID of the paper.
 * @return array
 */
function cacsp_get_group_links_for_paper( $paper_id ) {
	$paper = new CACSP_Paper( $paper_id );
	$group_ids = $paper->get_group_ids();

	if ( empty( $group_ids ) ) {
		return array();
	}

	$groups = groups_get_groups( array(
		'type' => 'alphabetical',
		'include' => $group_ids,
		'show_hidden' => true,
	) );

	$show_groups = array();
	foreach ( $groups['groups'] as $group ) {
		if ( 'public' === $group->status || $group->is_member ) {
			$show_groups[] = sprintf( '<a href="%s">%s</a>', esc_url( bp_get_group_permalink( $group ) ) . 'papers/', esc_html( stripslashes( $group->name ) ) );
		}
	}

	return $show_groups;
}

/**
 * Add group information to the paper meta area.
 *
 * @since 1.0.0
 */
function cacsp_show_group_info_in_paper_meta() {
	$show_groups = cacsp_get_group_links_for_paper( get_queried_object_id() );

	if ( empty( $show_groups ) ) {
		return;
	}

	echo '<span class="posted-in-groups"><br />';
	printf( _x( 'In %s', 'Paper group list', 'social-paper' ), implode( ', ', $show_groups ) );
	echo '</span>';
}
add_action( 'cacsp_after_paper_meta', 'cacsp_show_group_info_in_paper_meta' );

/**
 * Cache-friendly way to get papers belonging to a group.
 *
 * @since 1.0.0
 *
 * @param int $group_id ID of the group.
 * @return array Array of WP_Post objects.
 */
function cacsp_get_papers_of_group( $group_id ) {
	$last_changed = wp_cache_get( 'last_changed', 'posts' );
	$cache_key = $group_id . ':' . $last_changed;
	$papers_of_group = wp_cache_get( $cache_key, 'cacsp_papers_of_group' );
	if ( false === $papers_of_group ) {
		$papers_of_group = get_posts( array(
			'post_type'      => 'cacsp_paper',
			'post_status'    => 'publish',
			'bp_group'       => $group_id,
			'posts_per_page' => -1,
		) );
		wp_cache_set( $cache_key, $papers_of_group, 'cacsp_papers_of_group' );
	}

	return $papers_of_group;
}

/** Cache (ugh) **************************************************************/

/**
 * Add our non-persistent cache group.
 *
 * BuddyPress does not have decent (any) cache support for groups-of-member queries. Adding a
 * non-persistent group here so that we don't have to worry about invalidation. At least this
 * will help with single pages.
 */
function cacsp_add_non_persistent_cache_group() {
	wp_cache_add_non_persistent_groups( array( 'cacsp_groups_of_user' ) );
}
add_action( 'init', 'cacsp_add_non_persistent_cache_group' );

/**
 * Cached wrapper for fetching IDs of groups that a user is a member of.
 *
 * @param int $user_id
 * @return array
 */
function cacsp_get_groups_of_user( $user_id ) {
	if ( ! $user_id ) {
		return array();
	}

	$group_ids = wp_cache_get( $user_id, 'cacsp_groups_of_user' );

	if ( false === $group_ids ) {
		$user_groups = groups_get_groups( array(
			'user_id' => $user_id,
			'update_meta_cache' => false,
			'show_hidden' => true,
			'populate_extras' => false,
			'orderby' => 'name',
			'per_page' => null, // Show all
			'page' => null,
		) );

		$group_ids = array();
		if ( ! empty( $user_groups['groups'] ) ) {
			$group_ids = array_map( 'intval', wp_list_pluck( $user_groups['groups'], 'id' ) );
		}

		wp_cache_add( $user_id, $group_ids, 'cacsp_groups_of_user' );
	}

	return array_map( 'intval', $group_ids );
}

/**
 * Invalidate group paper cache on connect/disconnect.
 *
 * @since 1.0.0
 *
 * @param CACSP_Paper $paper    Paper object.
 * @param int         $group_id ID of the group.
 */
function cacsp_invalidate_group_paper_cache( CACSP_Paper $paper, $group_id ) {
	$last_changed = wp_cache_get( 'last_changed', 'posts' );
	$cache_key = $group_id . ':' . $last_changed;
	wp_cache_delete( $cache_key, 'cacsp_papers_of_group' );
}
add_action( 'cacsp_connected_paper_to_group', 'cacsp_invalidate_group_paper_cache', 10, 2 );
add_action( 'cacsp_disconnected_paper_from_group', 'cacsp_invalidate_group_paper_cache', 10, 2 );
