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
		var draggers, droppers, incom_ref, options, alert_text, div;

		// get all draggable items (top level comments)
		var draggers = $( 'li.incom.depth-1 > .comment-body .incom-permalink' );

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
			accept: '.incom-permalink',
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
							social_paper_incom_comments_dragger_dropped( incom_ref, ui );
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
	 * @param string incom_ref The text signature
	 * @param object ui The UI element
	 * @return void
	 */
	function social_paper_incom_comments_dragger_dropped( incom_ref, ui ) {

		// define vars
		var comment_id, comment_item, comment_to_move, other_comments, comment_list;

		// get comment id
		comment_id = $(ui.draggable).closest('li.incom').prop('id').split('-')[1];

		// let's see what params we've got
		console.log( 'incom_ref: ' + incom_ref );
		console.log( 'comment id: ' + comment_id );

		// post to server
		$.post(
			Social_Paper_Reassign.ajax_url,

			{
				action: 'cacsp_social_paper_reassign_comment', // function in WordPress
				incom_ref: incom_ref,
				comment_id: comment_id
			 },

			// callback
			function( data, textStatus ) {

				//console.log( data.msg );
				//console.log( textStatus );

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

	// slightly hacky way of adding the click listener - done when a bubble is
	// first clicked.
	var social_paper_incom_comments_dragger_inited = false;
	$(document).on( 'click', '.incom-bubble', function( event ) {
		console.log('pre');
		if ( social_paper_incom_comments_dragger_inited === true ) { return; }
		console.log('post');
		social_paper_incom_comments_dragger_init();
		social_paper_incom_comments_dragger_inited = true;
	});

});


