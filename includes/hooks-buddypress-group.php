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

		// perform query for this group
		// NOTE: query is currently disabled
		$group_query = new WP_Query( array(
			'post_type' => 'cacsp_paper',
			'author' => -1,
			'post_status' => 'publish',
			'bp_group' => $group_id,
		) );

		?>
		<div class="entry-content">

		<?php if ( $group_query->have_posts() ) : ?>

			<ul class="item-list">

			<?php while ( $group_query->have_posts() ) : $group_query->the_post(); ?>
				<?php cacsp_get_template_part( 'list-social-paper', 'buddypress' ); ?>
			<?php endwhile; ?>

			</ul>

		<?php else : ?>

			<p><?php _e( 'No group papers found.', 'social-paper' ); ?></p>

		<?php endif; ?>

		</div>

		<?php
	}

} // class ends

// register our class
bp_register_group_extension( 'CACSP_Group_Extension' );

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
		// @todo Is there a faster (and better cached) way to do this?
		$user_groups = groups_get_groups( array(
			'user_id' => bp_loggedin_user_id(),
			'update_meta_cache' => false,
			'show_hidden' => true,
			'populate_extras' => false,
		) );

		$group_ids = array();
		if ( ! empty( $user_groups['groups'] ) ) {
			$group_ids = array_map( 'intval', wp_list_pluck( $user_groups['groups'], 'id' ) );
		}
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
		'bp_group'       => $group_id,
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
	$args['filter_query'] = $new_filter_query;
	$args['primary_id'] = '';
	$args['object'] = '';
	$args['scope'] = '';

	return $args;
}
add_filter( 'bp_after_has_activities_parse_args', 'cacsp_filter_activity_args_for_groups' );

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
