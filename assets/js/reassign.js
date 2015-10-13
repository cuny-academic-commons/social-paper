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
	 *
	 * @return void
	 */
	function social_paper_incom_comments_dragger_init() {

		// define vars
		var draggers, droppers, incom_ref, options, div;

		// get all draggable items (top level comments)
		draggers = $( '.incom-bubble' );

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
			accept: '.incom-bubble',
			hoverClass: 'selected-dropzone',

			// when the button is dropped
			drop: function( event, ui ) {
				// get id of dropped-on item
				incom_ref = $(this).attr('data-incom');

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
							social_paper_incom_comments_dragger_dropped( $( '#comment_post_ID' ).val(), incom_ref, ui.draggable.data( 'incomBubble' ) );
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
	 * Reassign a comment when dropped.
	 *
	 * @param int    postId      Post ID for the comments.
	 * @param string targetPara  Paragraph reference of the target.
	 * @param string draggedPara Paragraph reference for the comments needing to be updated.
	 * @return void
	 */
	function social_paper_incom_comments_dragger_dropped( postId, targetPara, draggedPara ) {

		// post to server
		$.post(
			Social_Paper_Reassign.ajax_url,

			{
				action: 'cacsp_social_paper_reassign_comment', // function in WordPress
				post_id: postId,
				incom_ref: targetPara,
				curr_ref: draggedPara,
			 },

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
	$(document).on( 'fee-off', function( event ) {
		social_paper_incom_comments_dragger_init();
	});

});


