<?php

$wp_tests_dir = false;

$wp_develop_dir = getenv( 'WP_DEVELOP_DIR' );
if ( ! $wp_develop_dir ) {
	die( 'To run these tests, you must have a checkout of the develop.svn.wordpress.org repository, and an environment variable WP_DEVELOP_DIR that points to the checkout path.' . "\n" );
}

require_once $wp_develop_dir . '/tests/phpunit/includes/functions.php';

function _bootstrap_cacsp() {
	require dirname( __FILE__ ) . '/../../../social-paper.php';
}
tests_add_filter( 'muplugins_loaded', '_bootstrap_cacsp', 20 );

if ( ! defined( 'BP_TESTS_DIR' ) ) {
	define( 'BP_TESTS_DIR', dirname( __FILE__ ) . '/../../../../buddypress/tests/phpunit' );
}

$do_buddypress = file_exists( BP_TESTS_DIR . '/bootstrap.php' );
define( 'CACSP_TESTS_DO_BUDDYPRESS', $do_buddypress );

function _bootstrap_test_requirements() {
	// Make sure BP is installed and loaded first.
	if ( CACSP_TESTS_DO_BUDDYPRESS ) {
		require BP_TESTS_DIR . '/includes/loader.php';
	}

	// We need a compatible commenting plugin. Prefer inline-comments.
	require dirname( __FILE__ ) . '/../../../../inline-comments/inline-comments.php';

	// Social Paper does an explicit check for the FEE class
	// If it doesn't exist, just add a dummy FEE class to pass the check.
	if ( ! class_exists( 'FEE' ) ) {
		class FEE {}
	}

	// Init buddypress-followers.
	require dirname( __FILE__ ) . '/../../../../buddypress-followers/loader.php';
}
tests_add_filter( 'muplugins_loaded', '_bootstrap_test_requirements' );

/**
 * Load and install buddypress-followers.
 *
 * BP_Follow, you do not make this easy.
 */
function cacsptests_install_bpfollow() {
	remove_action( 'bp_loaded', 'bp_follow_setup_component' );
	bp_follow_setup_component();
	require_once( buddypress()->follow->path . '/bp-follow-updater.php' );
	require dirname( __FILE__ ) . '/bp-follow-updater.php';

	$updater = new CACSP_BP_Follow_Updater();
	$updater->run_install();
}
tests_add_filter( 'bp_loaded', 'cacsptests_install_bpfollow', 20 );

// Bootstrap WordPress.
require_once $wp_develop_dir . '/tests/phpunit/includes/bootstrap.php';

// We use a different testcase for BuddyPress vs non-BuddyPress.
if ( $do_buddypress ) {
	require BP_TESTS_DIR . '/includes/testcase.php';
	require dirname( __FILE__ ) . '/testcase-base-bp.php';
	require dirname( __FILE__ ) . '/factory-base-bp.php';
} else {
	require dirname( __FILE__ ) . '/factory-base.php';
	require dirname( __FILE__ ) . '/testcase-base.php';
}

require dirname( __FILE__ ) . '/factory.php';
require dirname( __FILE__ ) . '/testcase.php';
