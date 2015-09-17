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

		// test for our localisation object
		if ( 'undefined' !== typeof Social_Paper_FEE_i18n ) {

			// switch editing toggle button text
			$('#wp-admin-bar-edit span').text( Social_Paper_FEE_i18n.button_disable );

		}

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

		// test for our localisation object
		if ( 'undefined' !== typeof Social_Paper_FEE_i18n ) {

			// switch editing toggle button text
			$('#wp-admin-bar-edit span').text( Social_Paper_FEE_i18n.button_enable );

		}

	});

	/**
	 * Hook into WP FEE before save
	 *
	 * Currently used to strip 'data-incom' attribute from post content before
	 * it is sent to the server. This prevents the attributes being saved in the
	 * post content.
	 *
	 * Can also be used to add items to be saved along with the post data. See
	 * example code at foot of function.
	 */
	$(document).on( 'fee-before-save', function( event ) {

		//console.log( 'fee-before-save' );

		var items;

		// if Inline Comments present
		if ( window.incom ) {

			// get raw post content and wrap in temporary div
			items = $('<div>').html( tinymce.activeEditor.getContent() );

			// strip Inline Comments data attribute
			items.find( '[data-incom]' ).each( function( i, element ) {
				element.removeAttribute( 'data-incom' );
			});

			// overwrite current content
			tinymce.activeEditor.setContent( items.html(), {format : 'html'} );

		}

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

		// if Inline Comments present
		if ( window.incom ) {

			// window.incom has no destroy() method, so cannot be re-inited

			// this call requires changing `function load_incom()` in class-wp.php:
			// wrap incom.init() with window.incom_init = function() {} and call
			// window.incom_init() immediately. Unfortunately there's a ring-fenced
			// global variable in inline-comments.js that increments per item parsed
			// by '.entry-content p:visible', so this doesn't actually work.

			//window.incom_init();

		}

	});

});


