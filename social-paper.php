<?php
/*
Plugin Name: Social Paper
Description: Create a paper allowing colleagues to comment on each paragraph. Inspired by Medium.com.
Author: CUNY Academic Commons Team
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

add_action( 'plugins_loaded', array( 'Social_Paper', 'init' ), 20 );

/**
 * Social Paper core class.
 *
 * @package Social_Paper
 * @subpackage Core
 */
class Social_Paper {

	/**
	 * Absolute path to our this directory.
	 *
	 * @var string
	 */
	public static $PATH = '';

	/**
	 * URL to this directory.
	 *
	 * @var string
	 */
	public static $URL = '';

	/**
	 * Absolute path to our bundled template directory.
	 *
	 * @var string
	 */
	public static $TEMPLATEPATH = '';

	/**
	 * Our custom Frontend Editor instance.
	 *
	 * @var CACSP_FEE|null
	 */
	public static $FEE = null;

	/**
	 * Marker to determine if we're on a social paper page.
	 *
	 * @var bool
	 */
	public static $is_page = false;

	/**
	 * Marker to determine if we're on the social paper archive page.
	 *
	 * Otherwise known as the directory page.
	 *
	 * @var bool
	 */
	public static $is_archive = false;

	/**
	 * Marker to determine if we have an empty social paper archive.
	 *
	 * Otherwise known as the directory page.
	 *
	 * @var bool
	 */
	public static $is_empty_archive = false;

	/**
	 * Marker to determine if we're on the social paper create page.
	 *
	 * @var bool
	 */
	public static $is_new = false;

	/**
	 * Marker to determine if object buffering is on.
	 *
	 * Done during template injection overrides.
	 *
	 * @var bool
	 */
	public static $is_buffer = false;

	/**
	 * Static initializer.
	 */
	public static function init() {
		return new self();
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Bail if the "Inline Comments" plugin is not enabled
		if ( ! function_exists( 'incom_frontend_init' ) ) {
			// Show admin notice
			if ( current_user_can( 'install_plugins' ) ) {
				$notice = sprintf(
					__( 'Social Paper requires the %s plugin to be enabled.  Please download it %shere%s.', 'social-paper' ),
					'<strong>' . __( 'Inline Comments', 'social-paper' ). '</strong>',
					'<a target="_blank" href="https://wordpress.org/plugins/inline-comments/">',
					'</a>'
				);

				add_action( 'admin_notices', create_function( '', "
					echo '<div class=\"error\"><p>" . $notice . "</p></div>';
				" ) );
			}
			return;
		}

		// Bail if the "Front-end Editor" plugin is not enabled
		if ( ! class_exists( 'FEE' ) ) {
			// Show admin notice
			if ( current_user_can( 'install_plugins' ) ) {
				$notice = sprintf(
					__( 'Social Paper requires the %s plugin to be enabled.  Please download it %shere%s.', 'social-paper' ),
					'<strong>' . __( 'Inline Comments', 'social-paper' ). '</strong>',
					'<a target="_blank" href="https://wordpress.org/plugins/inline-comments/">',
					'</a>'
				);

				add_action( 'admin_notices', create_function( '', "
					echo '<div class=\"error\"><p>" . $notice . "</p></div>';
				" ) );
			}
			return;
		}

		$this->properties();
		$this->includes();
	}

	/**
	 * Properties.
	 */
	protected function properties() {
		self::$PATH         = dirname( __FILE__ );
		self::$URL          = plugins_url( basename( self::$PATH ) );
		self::$TEMPLATEPATH = self::$PATH . '/templates';
	}

	/**
	 * Includes.
	 */
	protected function includes() {
		require dirname( __FILE__ ) . '/includes/functions.php';
		require dirname( __FILE__ ) . '/includes/class-cacsp-paper.php';
		require dirname( __FILE__ ) . '/includes/hooks-template.php';
		require dirname( __FILE__ ) . '/includes/readers.php';

		// WP FEE integration
		if ( class_exists( 'FEE' ) ) {
			require dirname( __FILE__ ) . '/includes/hooks-wp-fee.php';
		}

		/**
		 * Should we register our custom post type?
		 *
		 * Handy to disable if developers already have their own post types in mind.
		 *
		 * @param type bool
		 */
		$register_cpt = (bool) apply_filters( 'cacsp_register_cpt', true );
		if ( true === $register_cpt ) {
			require dirname( __FILE__ ) . '/includes/schema.php';
		}

		// Inline Comments integration
		if ( function_exists( 'incom_frontend_init' ) ) {
			require dirname( __FILE__ ) . '/includes/hooks-inline-comments.php';
		}

		// BuddyPress integration
		if ( function_exists( 'buddypress' ) ) {
			require dirname( __FILE__ ) . '/includes/class-cacsp-component.php';
			require dirname( __FILE__ ) . '/includes/hooks-buddypress-profile.php';

			if ( bp_is_active( 'groups' ) ) {
				require dirname( __FILE__ ) . '/includes/hooks-buddypress-group.php';
			}

			if ( bp_is_active( 'activity' ) ) {
				require dirname( __FILE__ ) . '/includes/hooks-buddypress-activity.php';
			}

			if ( bp_is_active( 'notifications' ) ) {
				require dirname( __FILE__ ) . '/includes/hooks-buddypress-notifications.php';
			}

			//require dirname( __FILE__ ) . '/includes/hooks-buddypress-directory.php';
		}
	}

}
