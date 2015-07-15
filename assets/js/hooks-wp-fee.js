/**
 * Social Paper FEE Javascript
 *
 * Implements additional processing via callbacks from WP FEE events
 *
 * @package Social_Paper
 * @subpackage Template
 */

/**
 * When the page is ready
 */
jQuery(document).ready( function($) {

	/**
	 * Hook into WP FEE activation
	 */
	$(document).on( 'fee-on', function( event ) {

		//console.log( 'fee-on' );

		// fade out bubbles if Inline Comments present
		if ( window.incom ) {
			$('#incom_wrapper').fadeOut();
		}

		// always fade out comments and comment form
		$('#comments, #respond').fadeOut();

	});

	/**
	 * Hook into WP FEE deactivation
	 */
	$(document).on( 'fee-off', function( event ) {

		//console.log( 'fee-off' );

		// fade in bubbles if Inline Comments present
		if ( window.incom ) {
			$('#incom_wrapper').fadeIn();
		}

		// always fade in comments and comment form
		$('#comments, #respond').fadeIn();

	});

	/**
	 * Hook into WP FEE and add items to be saved along with the post data.
	 */
	$(document).on( 'fee-before-save', function( event ) {

		//console.log( 'fee-before-save' );

		/*
		// example additions

		// add nonce
		wp.fee.post.social_paper_nonce = function() {
			return $('#social_paper_nonce').val();
		};

		// add a value
		wp.fee.post.social_paper_value = function() {
			return $('#social_paper_value').val();
		};
		*/

	});

	/**
	 * Hook into WP FEE after save
	 */
	$(document).on( 'fee-after-save', function( event ) {

		//console.log( 'fee-after-save' );

	});

});


