<?php
/**
 * WA Fronted plugin integration into Social Paper.
 *
 * @package Social_Paper
 * @subpackage Hooks
 */

// Not sure why WA Fronted does not support older versions of PHP...
if( phpversion() < 5.43 ){
	add_action( 'plugins_loaded', 'wa_fronted_init', 999 );
}

/**
 * Register our post type with WA Fronted.
 *
 * @param  array $retval Current settings.
 * @return array
 */
function cacsp_wa_fronted_options( $retval = array() ){
	if ( empty( get_queried_object()->ID ) ) {
		return $retval;
	}

	return array_merge( $retval, array(
		'post_types' => array(
			'cacsp_paper' => array(
				'editable_areas' => array(
					array(
						'container'  => '.entry-title',
						'field_type' => 'post_title',
						'toolbar'    => false,
						'permission' => current_user_can( 'edit_paper', get_queried_object()->ID )
					),
					array(
						'container'  => '.entry-content',
						'field_type' => 'post_content',
						'toolbar'    => 'full',
						'permission' => current_user_can( 'edit_paper', get_queried_object()->ID )
					)
				)
			)
		)
	) );
}
add_filter( 'wa_fronted_options', 'cacsp_wa_fronted_options' );

/**
 * Add scripts so we can augment WP FEE's behaviour
 */
function cacsp_wa_fronted_enqueue_scripts() {
	if ( ! cacsp_is_page() ) {
		return;
	}

	if ( is_user_logged_in() ) {

		// enqueue script
		wp_enqueue_script(
			'social-paper-single-fee',
			Social_Paper::$URL . '/assets/js/hooks-wp-fee.js',
			array( 'jquery', 'jquery-ui-droppable', 'jquery-ui-dialog' ), // load droppable and dialog as dependencies
			'0.2'
		);

		// assume user cannot drag-n-drop
		$drag_allowed = '0';

		// enqueue change tracking script
		wp_enqueue_script(
			'social-paper-single-changes',
			Social_Paper::$URL . '/assets/js/changes.js',
			array( 'social-paper-single-fee' ), // make dependent on our main script above
			'0.1'
		);

		if ( current_user_can( 'edit_paper', get_queried_object()->ID ) ) {

			// user can, so override
			$drag_allowed = '1';

			// add style for dialog
			wp_enqueue_style( 'wp-jquery-ui-dialog' );

		}

		// localise
		wp_localize_script( 'social-paper-single-fee', 'Social_Paper_FEE', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'drag_allowed' => $drag_allowed,
			'i18n' => cacsp_wa_frontend_localise(),
		) );

	}

}
add_action( 'wp_enqueue_scripts', 'cacsp_wa_fronted_enqueue_scripts', 999 );

/**
 * Define translation strings for our Javascript
 *
 * @return array $translations The array of translations to pass to the script
 */
function cacsp_wa_frontend_localise() {

	// add translations for comment reassignment
	$translations = array(
		'title' => __( 'Are you sure?', 'social-paper' ),
		'body' => __( 'Are you sure you want to assign the comment and its replies to the paragraph? This action cannot be undone.', 'social-paper' ),
		'submit' => __( 'Submitting...', 'social-paper' ),
		'message' => __( 'Please wait while the comments are reassigned. The page will refresh when this has been done.', 'social-paper' ),
	);

	return $translations;

}

if ( ! function_exists( 'cacsp_filter_ajax_query_attachments' ) ) :
/**
 * Filter attachments when selecting media on the frontend.
 *
 * By default, the "Add Media" modal shows all available attachments across
 * the site.  We do not want to do this due to privacy issues. Instead,
 * this function filters the attachments query to only list attachments
 * uploaded by the logged-in user.
 *
 * @param array $retval Current attachment query arguments
 * @return array
 */
function cacsp_filter_ajax_query_attachments( $retval ) {
	// don't do this in the admin area or if user isn't logged in
	if ( defined( 'WP_NETWORK_ADMIN' ) || false === is_user_logged_in() ) {
		return $retval;
	}

	if ( empty( $_POST['post_id'] ) )  {
		return $retval;
	}

	// check if the post is our event type
	$post = get_post( $_POST['post_id'] );
	if ( 'cacsp_paper' !== $post->post_type ) {
		return $retval;
	}

	// modify the attachments query to filter by the logged-in user
	$retval['author'] = get_current_user_id();
	return $retval;
}
add_filter( 'ajax_query_attachments_args', 'cacsp_filter_ajax_query_attachments' );
endif;