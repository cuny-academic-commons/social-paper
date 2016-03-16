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
	 * Plugin slug of required plugin that current site does not have activated.
	 *
	 * @var string
	 */
	protected $required_plugin = '';

	/**
	 * Admin notice; shown if SP requirements are not met.
	 *
	 * @var string
	 */
	protected $admin_notice = '';

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
			$this->setup_admin_notice( 'inline-comments' );
			return;
		}

		// Bail if the "Front-end Editor" plugin is not enabled
		if ( ! class_exists( 'FEE' ) ) {
			$this->setup_admin_notice( 'wp-front-end-editor' );
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

		// WP FEE integration
		if ( class_exists( 'FEE' ) ) {
			require dirname( __FILE__ ) . '/includes/hooks-wp-fee.php';
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

			// BP Follow - requires BP Follow v1.3-bleeding
			if ( bp_is_active( 'follow' ) && class_exists( 'BP_Follow_Activity_Core' ) ) {
				require dirname( __FILE__ ) . '/includes/hooks-buddypress-follow.php';
			}
		}
	}

	/**
	 * Set up admin notice to be loaded.
	 *
	 * Notice needs to be rendered on the 'admin_notices' hook to avoid notices.
	 *
	 * @string $plugin Plugin slug.
	 */
	protected function setup_admin_notice( $plugin = '' ) {
		// Bail if not in admin area.
		if ( false === defined( 'WP_NETWORK_ADMIN' ) ) {
			return;
		}

		$this->required_plugin = $plugin;

		add_action( 'admin_notices', array( $this, 'load_admin_notice' ) );
	}

	/**
	 * Load admin notice for our unmet plugin requirement.
	 */
	public function load_admin_notice() {
		if ( false === current_user_can( 'install_plugins' ) ) {
			return;
		}

		// Inline Comments.
		if ( 'inline-comments' === $this->required_plugin ) {
			$is_installed = (bool) get_plugins( '/inline-comments' );

			if ( $is_installed ) {
				$notice = sprintf(
					__( 'Social Paper requires the %s plugin to be activated.  Please activate it %shere%s.', 'social-paper' ),
					'<strong>' . __( 'Inline Comments', 'social-paper' ). '</strong>',
					'<a href="' . wp_nonce_url( 'plugins.php?action=activate&amp;plugin=inline-comments%2finline-comments.php&amp;plugin_status=all&amp;paged=1', 'activate-plugin_inline-comments/inline-comments.php' ) . '">',
					'</a>'
				);

			} else {
				$notice = sprintf(
					__( 'Social Paper requires the %s plugin to be downloaded.  Please download it %shere%s.', 'social-paper' ),
					'<strong>' . __( 'Inline Comments', 'social-paper' ). '</strong>',
					'<a target="_blank" href="https://wordpress.org/plugins/inline-comments/">',
					'</a>'
				);

			}

		// Front-end Editor (our special fork).
		} elseif ( 'wp-front-end-editor' === $this->required_plugin ) {
			$is_installed = (bool) get_plugins( '/wp-front-end-editor' );
			$is_fork = false;

			if ( $is_installed ) {
				// Check if our fork is installed
				$json = json_decode( @file_get_contents( WP_PLUGIN_DIR . '/wp-front-end-editor/package.json' ) );
				if ( '1.1.0' === $json->devDependencies->{'grunt-sass'} ) {
					$is_fork = true;
				}

				if ( $is_fork ) {
					$notice = sprintf(
						__( 'Social Paper requires the %s plugin to be activated.  Please activate it %shere%s.', 'social-paper' ),
						'<strong>' . __( 'Front-end Editor', 'social-paper' ). '</strong>',
						'<a href="' . wp_nonce_url( 'plugins.php?action=activate&amp;plugin=wp-front-end-editor%2fplugin.php&amp;plugin_status=all&amp;paged=1', 'activate-plugin_wp-front-end-editor/plugin.php' ) . '">',
						'</a>'
					);
				}

			}

			if ( false === $is_fork ) {
				$notice = sprintf(
					__( 'Social Paper requires our special fork of %s plugin to be downloaded.  Please download it %shere%s.', 'social-paper' ),
					'<strong>' . __( 'Front-end Editor', 'social-paper' ). '</strong>',
					'<a target="_blank" href="https://github.com/cuny-academic-commons/wp-front-end-editor/releases">',
					'</a>'
				);

			}
		}

		echo "<div class='error'><p>{$notice}</p></div>";
	}
}
