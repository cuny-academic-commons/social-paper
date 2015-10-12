/**
 * Social Paper FEE Javascript
 *
 * Implements additional processing via callbacks from WP FEE events
 *
 * @package Social_Paper
 * @subpackage Template
 */

/**
 * Create SocialPaper instance
 */
var SocialPaper = SocialPaper || {};

// TinyMCE content editor instance
SocialPaper.editor = {};

/**
 * When the page is ready
 */
jQuery(document).ready( function($) {

	$(document).on( 'fee-editor-init', function( event ) {

		// store editor in our "global" if not already done
		if ( $.isEmptyObject( SocialPaper.editor ) ) {
			SocialPaper.editor = tinyMCE.get( window.wpActiveEditor );
		}

		// Add the Settings button to fee-toolbar, if necessary.
		$( '.fee-toolbar' ).prepend( '<div class="fee-toolbar-left"><button class="button button-large fee-button-settings"><div class="dashicons dashicons-admin-generic"></div></button></div>' );

		// Set up Settings toggle.
		$sidebar = $( '.entry-sidebar' );
		$settings_toggle = $( '.fee-button-settings' );
		$settings_toggle.on( 'click', function( e ) {
			$sidebar.toggleClass( 'toggle-on' );
			$( e.target ).toggleClass( 'active' );
		} );

		// Set up Readers hide/show.
		$readers_subsection = $( '.sidebar-section-subsection-readers' );
		$( 'input[name="cacsp-paper-status"]' ).on( 'change', function() {
			var self = $(this);
			if ( 'public' === self.val() ) {
				$readers_subsection.addClass( 'hidden' );
			} else {
				$readers_subsection.removeClass( 'hidden' );
			}
		} );
	} );

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

		// if Inline Comments present
		if ( window.incom ) {

			// rebuild - requires this commit on my fork of Inline Comments
			// https://github.com/christianwach/inline-comments/commit/351f24e1cf5a224024a965ea21bede302b20c07f
			if ( window.incom.rebuild ) {
				window.incom.rebuild();
			}

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
			items = $('<div>').html( SocialPaper.editor.getContent() );

			// strip Inline Comments data attribute
			items.find( '[data-incom]' ).each( function( i, element ) {
				element.removeAttribute( 'data-incom' );
			});

			// overwrite current content
			SocialPaper.editor.setContent( items.html(), {format : 'html'} );

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

	});

});


