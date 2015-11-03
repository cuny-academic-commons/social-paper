/**
 * Social Paper FEE Javascript
 *
 * Implements additional processing via callbacks from WP FEE events
 *
 * @package Social_Paper
 * @subpackage Template
 */

/*global
	SocialPaper, Social_Paper_FEE, jQuery, document, tinyMCE, window, wp,
	$sidebar, $settings_toggle, $readers_subsection
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
	var $paper_status,
		$settings_toggle,
		$sidebar,
		$status_toggle;

	/**
	 * Create Drag-n-drop object.
	 */
	SocialPaper.dragdrop = new function() {


		var me = this, // prevent reference collisions
			active = false;

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
								comment_id = parseInt( tmp[1], 10 );
							}
						}
					} else {
						dropped = 'bubble';
					}

					// bail if the target is the same
					if ( incom_ref === incom_attr ) {
						return;
					}

					// create options for modal dialog
					options = {
						resizable: false,
						width: 400,
						height: 240,
						zIndex: 3999,
						modal: true,
						dialogClass: 'wp-dialog',
						buttons: {
							"Yes": function() {
								$(this).dialog( "option", "disabled", true );
								$('.ui-dialog-buttonset').hide();
								$('.ui-dialog-title').html( Social_Paper_FEE.i18n.submit );
								$('.social_paper_alert_text').html( Social_Paper_FEE.i18n.message );
								if ( dropped === 'bubble' ) {
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

			// set active flag
			active = true;

		};

		/**
		 * Destroy reassignment of comments functionality
		 */
		this.destroy = function() {

			var drag, drop;

			// bail if inactive
			if ( ! active ) {
				return;
			}

			// destroy draggable if present
			drag = $( '.incom-bubble, li.incom.depth-1 > .comment-body .incom-permalink' ).draggable( 'instance' );
			if ( 'undefined' !== typeof drag && 'function' === typeof drag.destroy ) {
				drag.destroy();
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
					if ( textStatus === 'success' ) {
						document.location.reload( true );
					} else {
						console.log( textStatus );
					}
				},
				'json' // expected format
			);

		};

	};

	var toggle_sidebar = function( setting ) {
		$sidebar.toggleClass( 'toggle-on' );
		$( 'body' ).toggleClass( 'sidebar-on' );
		$settings_toggle.toggleClass( 'active' );
	}

	/**
	 * Hook into window load
	 */
	$(window).on( "load", function() {

		// drag 'n' drop time! (if allowed)
		if ( Social_Paper_FEE.drag_allowed === '1' ) {
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
			toggle_sidebar();
		} );

		// If the current post has unapproved comments, show a count.
		if ( SocialPaperI18n.unapproved_comment_count > 0 ) {
			var unapproved_span = '<span class="unapproved-comment-count" title="' + SocialPaperI18n.unapproved_comment_alt + '">' + SocialPaperI18n.unapproved_comment_count * 1 + '</span>';
			$settings_toggle.append( unapproved_span );
			$( '.sidebar-section-subsection-unapproved-comments h3' ).append( unapproved_span );
		}

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

		$paper_status = $( '.paper-status' );
		$status_toggle = $( 'input.cacsp-paper-status' );
		$status_toggle.on( 'change', function( e ) {
			var protected = 'protected' === this.value;
			if ( 'protected' === this.value ) {
				$paper_status.html( SocialPaperI18n.protected_paper ).addClass( 'protected' );
			} else {
				$paper_status.html( SocialPaperI18n.public_paper ).removeClass( 'protected' );
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
			if ( Social_Paper_FEE.drag_allowed === '1' ) {
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

		/*
		 * When entering Edit mode, slide out the Settings sidebar if:
		 * a. The paper is not yet published OR the paper has unapproved comments;
		 * b. AND there's enough room to show the sidebar.
		 */
		if ( 'publish' != wp.fee.post.post_status() || SocialPaperI18n.unapproved_comment_count > 0 ) {
			var slug_offset = $( '.fee-url' ).offset();
			if ( slug_offset.left > 275 ) {
				toggle_sidebar();
			}
		}

		var $entry_title = $( '.entry-title' );
		var $entry_slug = $( '.fee-slug' );
		var slug_editor, current_title, current_slug;
		$.each( window.tinymce.editors, function( i, ed ) {
			if ( ed.id == $entry_slug.attr( 'id' ) ) {
				slug_editor = ed;
				current_slug = slug_editor.getContent();
			}

			if ( ed.id == $entry_title.attr( 'id' ) ) {
				current_title = ed.getContent();

				ed.on( 'blur', function() {
					var new_title = this.getContent();

					// No change? Nothing to do here.
					if ( new_title == current_title ) {
						return;
					} else {
						current_title = new_title;
					}

					// We only auto-update the slug for non-published posts.
					if ( 'publish' == window.wp.fee.postOnServer.post_status ) {
						return;
					}

					$( slug_editor.getElement() ).addClass( 'slug-loading' );

					$.post( ajaxurl, {
						action: 'cacsp_sample_permalink',
						new_title: new_title,
						post_id: window.wp.fee.post.post_ID
					},
					function( response ) {
						if ( response.success && slug_editor ) {
							slug_editor.setContent( response.data[1] );
							slug_editor.setProgressState( false );
							$( slug_editor.getElement() ).removeClass( 'slug-loading' );
						}
					} );
				} );
			}
		} );
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
				if ( Social_Paper_FEE.drag_allowed === '1' ) {
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

		// Tags input.
		wp.fee.post.cacsp_paper_tags = function() {
			SocialPaperTagBox.flushTags( $( '#cacsp_paper_tag' ), false );
			return $( '#cacsp_paper_tag' ).find( '.the-tags' ).val();
		}

		// Paper description - let FEE handle it as post_excerpt.
		wp.fee.post.post_excerpt = function() {
			return $( '#cacsp-paper-description' ).val();
		}
	});

	/**
	 * Hook into WP FEE after save
	 */
	$(document).on( 'fee-after-save', function( event ) {
		// Dynamically do some stuff after a paper is first published
		if ( -1 !== event.currentTarget.URL.indexOf( '#edit=true' ) ) {
			// Change the current URL to the full paper URL using HTML5 history
			if ( typeof ( history.replaceState ) != "undefined" ) {
				history.replaceState( {}, wp.fee.post.post_title(), event.currentTarget.URL.substr( 0, event.currentTarget.URL.indexOf( '?' ) ) + 'papers/' + wp.fee.post.post_name() + '/' );
			}

			// Hide various UI items
			$( '#fee-post-status' ).hide();
			$( 'button.fee-save' ).remove();

			// Change 'Publish' button text to 'Update'
			$( 'button.fee-publish' ).html( Social_Paper_FEE.i18n.button_update ).removeClass( 'fee-publish' ).addClass( 'fee-save' );
		}


		// FEE tells us the term IDs, but nothing else, so back to the server we go.
		$.post(
			Social_Paper_FEE.ajax_url, {
				action: 'cacsp_get_tag_data',
				post_id: window.wp.fee.post.post_ID
			},
			function( response ) {
				if ( response.success ) {
					$tag_list = $( '.paper-tags-list' ).html( response.data );
				}
			}
		);

	});

});


