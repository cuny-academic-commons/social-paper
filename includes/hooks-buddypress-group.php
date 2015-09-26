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
		) );

		?>
		<div class="entry-content">

		<?php if ( $group_query->have_posts() ) : ?>

			<?php cacsp_get_template_part( 'group-header', 'social-paper' ); ?>

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
