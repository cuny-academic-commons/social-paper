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

			<p><?php _e( 'Group papers.', 'social-paper' ); ?></p>

			<ul>

			<?php while ( $group_query->have_posts() ) : $group_query->the_post(); ?>
				<li><a href="<?php the_permalink(); ?>" rel="bookmark"><?php the_title(); ?></a></li>
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

