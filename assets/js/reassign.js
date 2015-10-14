/**
 * Social Paper Comment Reassignment Javascript
 *
 * @package Social_Paper
 * @subpackage Template
 */



/**
 * When the page is ready
 */
jQuery(document).ready( function($) {

	/**
	 * Enable reassignment of comments
	 */
	function social_paper_incom_comments_dragger_init() {

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
							$('.ui-dialog-title').html( Social_Paper_Reassign.i18n.submit );
							$('.social_paper_alert_text').html( Social_Paper_Reassign.i18n.message );
							if ( dropped == 'bubble' ) {
								social_paper_incom_comments_bubble_dropped( $( '#comment_post_ID' ).val(), incom_ref, incom_attr );
							} else {
								social_paper_incom_comments_comment_dropped( incom_ref, comment_id );
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
				div = $('<div><p class="social_paper_alert_text">' + Social_Paper_Reassign.i18n.body + '</p></div>');
				div.prop( 'title', Social_Paper_Reassign.i18n.title )
				   .appendTo( 'body' )
				   .dialog( options );

			}

		});

	};

	/**
	 * Reassign all comments for a paragraph when bubble is dropped.
	 *
	 * @param int    postId      Post ID for the comments.
	 * @param string targetPara  Paragraph reference of the target.
	 * @param string draggedPara Paragraph reference for the comments needing to be updated.
	 * @return void
	 */
	function social_paper_incom_comments_bubble_dropped( postId, targetPara, draggedPara ) {

		// configure and send
		social_paper_incom_comments_post_to_server({
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
	function social_paper_incom_comments_comment_dropped( incom_ref, comment_id ) {

		// configure and send
		social_paper_incom_comments_post_to_server({
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
	function social_paper_incom_comments_post_to_server( data ) {

		// post to server
		$.post(
			Social_Paper_Reassign.ajax_url,
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

	// drag 'n' drop time!
	$(window).on( "load", function() {
		social_paper_incom_comments_dragger_init();
	});

});
