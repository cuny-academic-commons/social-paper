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

	/**
	 * Create Drag-n-drop object.
	 */
	SocialPaper.dragdrop = new function() {

		// prevent reference collisions
		var me = this;

		/**
		 * Enable reassignment of comments
		 */
		this.init = function() {

			// define vars
			var draggers, droppers, dropped,
				incom_ref, incom_attr,
				comment_ref, tmp, comment_id = 0,
				options, div;

			// get all draggable items (bubbles AND top level comments)
			draggers = $( '.incom-bubble, li.incom.depth-1 > .comment-body .incom-permalink' );

			// make comment reassign button draggable
			draggers.draggable({
				helper: 'clone',
				cursor: 'move'
			});

			// get all droppable items
			droppers = $('.fee-content-original').find( '[data-incom]' );

			// make textblocks droppable
			droppers.droppable({

				// configure droppers
				accept: '.incom-bubble, .incom-permalink',
				hoverClass: 'selected-dropzone',
				addClasses: false,

				activate: function( event, ui ) {

					var incom_attr;

					$( '.fee-content-original [data-incom]' ).removeClass( 'suppress-highlight' );

					// get existing attribute from either bubble or comment
					incom_attr = ui.draggable.data( 'incomBubble' );
					if ( 'undefined' === typeof incom_attr ) {
						incom_attr = $(ui.draggable).closest('li.incom').attr( 'data-incom-comment' );
					}

					$( '.fee-content-original [data-incom="' + incom_attr + '"]' ).addClass( 'suppress-highlight' );

				},

				deactivate: function( event, ui ) {
					$( '.fee-content-original [data-incom]' ).removeClass( 'suppress-highlight' );
				},

				// when the button is dropped
				drop: function( event, ui ) {

					// get identifier of dropped-on item
					incom_ref = $(this).attr('data-incom');

					// determine what was dropped
					incom_attr = ui.draggable.data( 'incomBubble' );
					if ( 'undefined' === typeof incom_attr ) {
						dropped = 'comment';
						incom_attr = $(ui.draggable).closest('li.incom').attr( 'data-incom-comment' );
						comment_ref = $(ui.draggable).closest('li.incom').prop('id');
						if ( comment_ref.match( 'comment-' ) ) {
							tmp = comment_ref.split('comment-');
							if ( tmp.length === 2 ) {
								comment_id = parseInt( tmp[1] );
							}
						}
					} else {
						dropped = 'bubble';
					}

					// bail if the target is the same
					if ( incom_ref == incom_attr ) {
						return;
					}

					// create options for modal dialog
					options = {
						resizable: false,
						width: 400,
						height: 200,
						zIndex: 3999,
						modal: true,
						dialogClass: 'wp-dialog',
						buttons: {
							"Yes": function() {
								$(this).dialog( "option", "disabled", true );
								$('.ui-dialog-buttonset').hide();
								$('.ui-dialog-title').html( Social_Paper_FEE.i18n.submit );
								$('.social_paper_alert_text').html( Social_Paper_FEE.i18n.message );
								if ( dropped == 'bubble' ) {
									me.bubble_dropped( $( '#comment_post_ID' ).val(), incom_ref, incom_attr );
								} else {
									me.comment_dropped( incom_ref, comment_id );
								}
							},
							"Cancel": function() {
								$(this).dialog( 'close' );
								$(this).dialog( 'destroy' );
								$(this).remove();
							}
						}
					};

					// create modal dialog
					div = $('<div><p class="social_paper_alert_text">' + Social_Paper_FEE.i18n.body + '</p></div>');
					div.prop( 'title', Social_Paper_FEE.i18n.title )
					   .appendTo( 'body' )
					   .dialog( options );

				}

			});

		};

		/**
		 * Destroy reassignment of comments functionality
		 */
		this.destroy = function() {

			var drag, drop;

			// destroy draggable if present
			drag = $( '.incom-bubble, li.incom.depth-1 > .comment-body .incom-permalink' ).draggable( 'instance' );
			if ( 'undefined' !== typeof drag ) {
				$( '.incom-bubble, li.incom.depth-1 > .comment-body .incom-permalink' ).draggable( 'destroy' );
			}

			// destroy droppable if present
			drop = $('.fee-content-original').find( '[data-incom]' ).droppable( 'instance' );
			if ( 'undefined' !== typeof drop ) {
				$('.fee-content-original').find( '[data-incom]' ).droppable( 'destroy' );
			}

		};

		/**
		 * Reassign all comments for a paragraph when bubble is dropped.
		 *
		 * @param int    postId      Post ID for the comments.
		 * @param string targetPara  Paragraph reference of the target.
		 * @param string draggedPara Paragraph reference for the comments needing to be updated.
		 * @return void
		 */
		this.bubble_dropped = function( postId, targetPara, draggedPara ) {

			// configure and send
			me.post_to_server({
				action: 'cacsp_social_paper_reassign_comments', // function in WordPress
				post_id: postId,
				incom_ref: targetPara,
				curr_ref: draggedPara,
			});

		};

		/**
		 * Reassign a comment and its children when a comment permalink is dropped.
		 *
		 * @param string incom_ref  The paragraph reference
		 * @param object comment_id The comment ID
		 */
		this.comment_dropped = function( incom_ref, comment_id ) {

			// configure and send
			me.post_to_server({
				action: 'cacsp_social_paper_reassign_comment', // function in WordPress
				incom_ref: incom_ref,
				comment_id: comment_id
			});

		};

		/**
		 * Send data to server.
		 *
		 * @param object data The data to send
		 */
		this.post_to_server = function( data ) {

			// post to server
			$.post(
				Social_Paper_FEE.ajax_url,
				data,
				// callback
				function( data, textStatus ) {
					// if success, refresh from server
					if ( textStatus == 'success' ) {
						document.location.reload( true );
					} else {
						console.log( textStatus );
					}
				},
				'json' // expected format
			);

		};

	};

	/**
	 * Hook into window load
	 */
	$(window).on( "load", function() {

		// drag 'n' drop time! (if allowed)
		if ( Social_Paper_FEE.drag_allowed == '1' ) {
			SocialPaper.dragdrop.init();
		}

	});

	/**
	 * Hook into WP FEE initialisation.
	 */
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

		// if Inline Comments present
		if ( window.incom ) {

			// destroy drag-n-drop
			if ( Social_Paper_FEE.drag_allowed == '1' ) {
				SocialPaper.dragdrop.destroy();
			}

		}

		// fade out bubbles if Inline Comments present
		if ( window.incom ) {
			$('#incom_wrapper').hide();
		}

		// always fade out comments and comment form
		$('#comments, #respond').hide();

		// switch editing toggle button text
		$('#wp-admin-bar-edit span').text( Social_Paper_FEE.i18n.button_disable );

		// Toggle Settings sidebar.
		$sidebar.addClass( 'toggle-on' );
		$settings_toggle.addClass( 'active' );

	});

	/**
	 * Hook into WP FEE deactivation
	 */
	$(document).on( 'fee-off', function( event ) {

		//console.log( 'fee-off' );

		// if Inline Comments present
		if ( window.incom ) {

			if ( window.incom.rebuild ) {

				// rebuild Inline Comments UI
				window.incom.rebuild();

				// rebuild drag-n-drop
				if ( Social_Paper_FEE.drag_allowed == '1' ) {
					SocialPaper.dragdrop.init();
				}

			}

		}

		// fade in bubbles if Inline Comments present
		if ( window.incom ) {
			$('#incom_wrapper').show();
		}

		// always fade in comments and comment form
		$('#comments, #respond').show();

		// switch editing toggle button text
		$('#wp-admin-bar-edit span').text( Social_Paper_FEE.i18n.button_enable );

		// Toggle Settings sidebar.
		$sidebar.removeClass( 'toggle-on' );
		$settings_toggle.removeClass( 'active' );

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

		// Paper status nonce.
		wp.fee.post.social_paper_status_nonce = function() {
			return $( '#cacsp-paper-status-nonce' ).val();
		};

		// Paper status.
		wp.fee.post.social_paper_status = function() {
			return $( 'input[name="cacsp-paper-status"]:checked' ).val();
		};

		// Group association nonce.
		wp.fee.post.social_paper_groups_nonce = function() {
			return $( '#cacsp-group-selector-nonce' ).val();
		};

		// Group associations.
		wp.fee.post.social_paper_groups = function() {
			return $( '#cacsp-group-selector' ).val();
		};

		// Reader selection nonce.
		wp.fee.post.social_paper_readers_nonce = function() {
			return $( '#cacsp-reader-selector-nonce' ).val();
		};

		// Reader selection.
		wp.fee.post.social_paper_readers = function() {
			return $( '#cacsp-reader-selector' ).val();
		};
	});

	/**
	 * Hook into WP FEE after save
	 */
	$(document).on( 'fee-after-save', function( event ) {

		//console.log( 'fee-after-save' );

	});

});


