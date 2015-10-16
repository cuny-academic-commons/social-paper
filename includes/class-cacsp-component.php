<?php

/**
 * Implementation of BP_Component.
 *
 * @since 1.0.0
 */
class CACSP_Component extends BP_Component {
	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::start(
			'cacsp',
			__( 'Social Paper', 'social-paper' )
		);

		// Loaded too late for BuddyPress to do this properly.
		$this->setup_globals();
	}

	/**
	 * Set up globals.
	 *
	 * We only use this for the notification format callback at the moment.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args See BP_Component::setup_globals().
	 */
	public function setup_globals( $args = array() ) {
		// Register ourselves in the active component array.
		buddypress()->active_components['cacsp'] = 1;

		parent::setup_globals( array(
			'notification_callback' => 'cacsp_format_notifications',
		) );
	}
}

function cacsp_load_component() {
	buddypress()->cacsp = new CACSP_Component();
}
add_action( 'bp_init', 'cacsp_load_component' );
