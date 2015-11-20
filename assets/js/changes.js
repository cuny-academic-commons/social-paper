/**
 * Social Paper Change Tracking Javascript
 *
 * @package Social_Paper
 * @subpackage Template
 */



/**
 * Create SocialPaperChange object.
 *
 * This works as a "namespace" of sorts, allowing us to hang properties, methods
 * and "sub-namespaces" from it.
 */
var SocialPaperChange = SocialPaperChange || {};



/**
 * Set up the SocialPaperChange when the page is ready
 */
jQuery(document).ready( function($) {

	/**
	 * Create Reporter object.
	 *
	 * This can be used to hold externally addressable utility functions.
	 */
	SocialPaperChange.reporter = new function() {

		/**
		 * Report the current state of the comments.
		 */
		this.comments = function() {
			SocialPaperChange.tracker.data.forEach( function( item ) {
				$( 'li[data-incom-comment="' + item.modified + '"]' ).each( function( i, el ) {
					console.log( 'comment: ', $(el).prop( 'id' ), $(el).attr( 'data-incom-comment' ) );
				});
			});
		};

	};

	/**
	 * Create Tracker object.
	 */
	SocialPaperChange.tracker = new function() {

		var me = this;

		// tracks the changes to content
		me.data = [];

		/**
		 * Init tracker array
		 */
		this.init = function() {

			me.data = [];

			// get current
			$('.fee-content-original').find( '[data-incom]' ).each( function( i, element ) {

				// construct default data
				var data = {
					is_new: false,
					is_modified: false,
					original: $(element).attr( 'data-incom' ),
					modified: $(element).attr( 'data-incom' ),
				};

				me.data.push( data );

			});

		};

		/**
		 * Rebuild tracker array
		 */
		this.rebuild = function() {

			me.data = [];

			// get current
			$('.fee-content-body').find( '[data-incom]' ).each( function( i, element ) {

				// construct default data
				var data = {
					is_new: false,
					is_modified: false,
					original: $(element).attr( 'data-incom' ),
					modified: $(element).attr( 'data-incom' ),
				};

				me.data.push( data );

			});

		};

		/**
		 * Add an item to the tracker array
		 */
		this.add = function( data ) {
			me.data.push( data );
		};

		/**
		 * Update an item in the tracker array
		 */
		this.update = function( data ) {

			// iterate through items and find relevant one
			me.data.forEach( function( item ) {
				if ( item.original == data.original ) {
					item = data;
					return false;
				}
			});

		};

		/**
		 * Delete an item from the tracker array
		 */
		this.remove = function( data ) {
			me.data.splice( $.inArray( data, me.data ), 1 );
		};

		/**
		 * Get an item from the tracker array
		 *
		 * @param str identifier The identifier to look for
		 * @param str property The property to match the identifier against
		 * @return object|bool found The data object for the identifier, or false if not found
		 */
		this.get_by = function( identifier, property ) {

			// init as not found
			var found = false;

			// find relevant one
			me.data.forEach( function( item ) {
				if (
					property == 'modified' && item.modified == identifier ||
					property == 'original' && item.original == identifier
				) {
					found = item;
					return false;
				}
			});

			if ( found ) {
				return found;
			} else {
				return false;
			}

		};

		/**
		 * Given an array of items, get those in the tracker array which are not
		 * present in the supplied array.
		 *
		 * This is used to find out which paragraphs have been deleted when there
		 * is an uncollapsed selection that we cannot determine the keydown items
		 * for. This applies to the ENTER and DELETE keys, for example.
		 *
		 * @param array items The array of items present in the DOM
		 * @return array missing The array of items missing from the DOM
		 */
		this.get_missing = function( items ) {

			var missing = [];

			// add to missing if the stored paragraph is not in the items
			$.each( me.data, function( index, value ) {
				if ( -1 === $.inArray( value.modified, items ) ) {
					missing.push( value.modified );
				}
			});

			return missing;

		};

	};

	/**
	 * Create Editor Tracking object.
	 */
	SocialPaperChange.editor = new function() {

		var me = this;

		// TinyMCE content editor instance
		me.instance = {};

		// store initial editor content
		me.cache = '';

		// items present when there's a uncollapsed selection on keydown
		me.keydown_items = [];

		// paste flag
		me.paste_flag = false;

		// "para after wp-view has focus" flag
		me.wp_view_after = false;

		// non printing keycodes
		me.non_printing = {
			//'8': 'backspace', '9': 'tab', '13': 'enter',
			'12': 'num lock (mac)', '16': 'shift', '17': 'ctrl', '18': 'alt',
			'19': 'pause/break', '20': 'caps lock', '27': 'escape',
			'33': 'page up', '34': 'page down',
			'35': 'end', '36': 'home',
			'37': 'left arrow', '38': 'up arrow', '39': 'right arrow', '40': 'down arrow',
			//'45': 'insert', '46': 'delete',
			'91': 'left window', '92': 'right window', '93': 'select',
			'112': 'f1', '113': 'f2', '114': 'f3', '115': 'f4', '116': 'f5',
			'117': 'f6', '118': 'f7', '119': 'f8', '120': 'f9', '121': 'f10',
			'122': 'f11', '123': 'f12', '124': 'f13', '125': 'f14', '126': 'f15',
			'127': 'f16', '128': 'f17', '129': 'f18', '130': 'f19',
			'144': 'num lock', '145': 'scroll lock'
		};

		/**
		 * Start tracking changes in the editor.
		 *
		 * NB: when keys are held down so that they repeatedly fire, only keydown
		 * events are triggered. This may have consequences for the method which
		 * handles printing character keys.
		 *
		 * Also, when a modifier is used (for example when cutting or pasting) no
		 * keyup event fires until the modifier key is released, at which point
		 * it is the modifier key's keyCode which is reported. Which means all
		 * cut/paste code can only work on keydown.
		 */
		this.track = function( event ) {

			// bail if already done
			if ( ! $.isEmptyObject( me.instance ) ) {
				return;
			}

			// store editor in our "global"
			me.instance = tinyMCE.get( window.wpActiveEditor );

			// add keydown tracker code
			me.instance.on( 'keydown', function( event ) {

				//console.log( 'keydown event code', event.which );

				// return?
				if ( event.which == tinymce.util.VK.ENTER ) {
					me.handle_ENTER();
					return;
				}

				// delete?
				if ( event.which == tinymce.util.VK.DELETE || event.which == tinymce.util.VK.BACKSPACE ) {
					// Gecko behaves differently, so use keyup instead
					if ( ! tinymce.Env.gecko ) {
						me.handle_DELETE();
					}
					return;
				}

				// any other key that produces a printing character
				if ( ! tinymce.util.VK.metaKeyPressed( event ) && ! ( ( event.which + '' ) in me.non_printing ) ) {
					me.handle_PRINTING_CHAR( 'keydown' );
				}

			});

			// add keypress tracker code
			me.instance.on( 'keypress', function( event ) {
				//console.log( 'keypress event code', event.keyCode );
			});

			// add keyup tracker code
			me.instance.on( 'keyup', function( event ) {

				//console.log( 'keyup event code', event.which );

				// return?
				if ( event.which == tinymce.util.VK.ENTER ) {
					//me.handle_ENTER( 'keyup' );
					return;
				}

				// delete?
				if ( event.which == tinymce.util.VK.DELETE || event.which == tinymce.util.VK.BACKSPACE ) {
					if ( tinymce.Env.gecko ) {
						me.handle_DELETE();
					}
					return;
				}

				// any other key that produces a printing character
				if ( ! tinymce.util.VK.metaKeyPressed( event ) && ! ( ( event.which + '' ) in me.non_printing ) ) {
					me.handle_PRINTING_CHAR( 'keyup' );
				}

			});

			// handle cut
			me.instance.on( 'cut', function( event ) {
				me.handle_CUT( event );
			});

			// handle paste
			me.instance.on( 'PastePreProcess', function( event ) {
				//me.handle_PASTE_PRE( event );
			});
			me.instance.on( 'paste', function( event ) {
				//me.handle_PASTE( event );
			});
			me.instance.on( 'PastePostProcess', function( event ) {
				me.handle_PASTE_POST( event );
			});

			// change tracker
			me.instance.on( 'change', function( event ) {
				//console.log('change event', event);
				//console.log('change event type', event.type);
				me.handle_PASTE_COMPLETE( event );
			});

			// handle undo & redo
			me.instance.on( 'undo redo', function( event ) {
				//console.log('undo redo event', event);
				//console.log('undo redo event type', event.type);
			});

			me.instance.on( 'NodeChange', function( event ) {
				me.handle_NODE_CHANGE( event );
			});

		};

		/**
		 * Handle 'NodeChange' event
		 *
		 * @param object event The TinyMCE event object
		 */
		this.handle_NODE_CHANGE = function( event ) {

			//console.log( '------------------------------------------------------------' );
			//console.log( 'handle_NODE_CHANGE', event );

			var element = event.element,
				className = element.className;

			// bail if not change of focussed element
			if ( 'undefined' === typeof event.selectionChange ) {
				return;
			}

			if ( className === 'wpview-selection-after' ) {
				me.wp_view_after = true;
			} else {
				me.wp_view_after = false;
			}

		};

		/**
		 * Handle 'PastePreProcess' event
		 *
		 * @param object event The TinyMCE event object
		 */
		this.handle_PASTE_PRE = function( event ) {
			//console.log( '------------------------------------------------------------' );
			//console.log( 'handle_PASTE_PRE', event );
		};

		/**
		 * Handle 'paste' event
		 *
		 * @param object event The TinyMCE event object
		 */
		this.handle_PASTE = function( event ) {
			//console.log( '------------------------------------------------------------' );
			//console.log( 'handle_PASTE', event );
		};

		/**
		 * Handle 'PastePostProcess' event
		 *
		 * This is a perhaps unreliable way to do this, but the problem is that
		 * during 'PastePostProcess', the new content has been added to the DOM
		 * but not inserted at the caret. This means that the order cannot be
		 * discovered.
		 *
		 * As a result, a flag is set here and then tested for in the callback
		 * to the 'change' event. See `handle_PASTE_COMPLETE()` below.
		 *
		 * @param object event The TinyMCE event object
		 */
		this.handle_PASTE_POST = function( event ) {

			//console.log( '------------------------------------------------------------' );
			//console.log( 'handle_PASTE_POST', event );
			me.paste_flag = true;

			// When cutting and pasting from the same TinyMCE instance, markup
			// may not be cleaned sufficiently - this can result in the 'style'
			// and 'data-incom' attributes remaining in the paste content. This
			// messes up the logic for finding the last identifiable item below.

			// remove class and data-incom attributes from any content
			items = $('<div>').html( event.node.innerHTML );
			items.find( '[data-incom]' ).each( function( i, element ) {
				element.removeAttribute( 'data-incom' );
				//element.removeAttribute( 'class' );
			});

			// get content stripped of new lines and unnecessary whitespace
			var content = items.html().replace( /(\r\n|\n|\r)/gm, ' ' ).replace( /\s+/g, ' ' );

			// strip ending <p>&nbsp;</p>
			if ( content.length > 13 ) {
				if ( content.slice( -13 ) == '<p>&nbsp;</p>' ) {
					content = content.slice( 0, -13 );
				}
			}

			event.node.innerHTML = content;

		};

		/**
		 * Handle 'paste' event after the DOM has been built.
		 *
		 * This is a callback from the 'change' event and may be unreliable
		 * because for 'change' events to be triggered requires sufficient change
		 * to trigger an undo. Needs thorough testing.
		 *
		 * @param object event The TinyMCE event object
		 */
		this.handle_PASTE_COMPLETE = function( event ) {

			// only allow this to run directly after PastePostProcess
			if ( me.paste_flag !== true ) { return; }
			me.paste_flag = false;

			//console.log( '------------------------------------------------------------' );
			//console.log( 'handle_PASTE_COMPLETE', event );

			var tag, identifier, number, subsequent, wp_view,
				current_items = [], paras = [], filtered = [],
				previous_element = false, stop_now = false;

			// get current identifiers
			$('.fee-content-body').find( '[data-incom]' ).each( function( i, element ) {
				current_items.push( $(element).attr( 'data-incom' ) );
			});

			// get current paras
			paras = me.filter_elements( $('.fee-content-body p') );

			// try to find the para prior to the first unidentified para
			$.each( paras, function( i, element ) {

				// get identifier
				var id = $(element).attr( 'data-incom' );

				// find previous, if present
				if ( 'undefined' === typeof id ) {
					stop_now = true;
				} else {
					if ( stop_now === false ) {
						previous_element = $(element);
					}
				}

			});

			// do we have one?
			if ( previous_element === false ) {

				// this means we have probably pasted the content and the very
				// beginning of the editor

				// sanity check
				if ( paras.length === 0 ) {
					return;
				}

				// set some defaults - but note that this will only work with
				// pargraphs set as the Inline Comments selector
				tag = 'P';
				identifier = 'P0';

				// because it is incremented *before* applying new identifier and
				// we want the sequence to start with 'P0'
				number = -1;

				// filter paras to get subsequent paras
				subsequent = me.filter_elements( paras );

			} else {

				tag = previous_element.prop( 'tagName' ).substr( 0, 5 );
				identifier = previous_element.attr( 'data-incom' );
				number = parseInt( identifier.replace( tag, '' ) );

				// get subsequent paras
				subsequent = me.get_subsequent( number );

			}

			// find any missing items
			missing = SocialPaperChange.tracker.get_missing( current_items );

			// if there are some missing
			if ( missing.length > 0 ) {

				// remove the missing items
				$.each( missing, function( index, value ) {

					var tracker_data;

					// remove from tracker
					tracker_data = SocialPaperChange.tracker.get_by( value, 'modified' );
					SocialPaperChange.tracker.remove( tracker_data );

					// reassign comments to current item
					SocialPaperChange.comments.reassign( value, identifier );

				});

			}

			// are there any subsequent?
			if ( subsequent.length > 0 ) {

				// reparse all p tags greater than this
				$.each( subsequent, function( i, el ) {

					var element, current_identifier, becomes, tracker_data;

					element = $( el );
					current_identifier = element.attr( 'data-incom' );

					// construct and apply new identifier
					number++;
					becomes = tag + number;
					element.attr( 'data-incom', becomes );

					// if we have one
					if ( 'undefined' !== typeof current_identifier ) {

						// get data and update
						tracker_data = SocialPaperChange.tracker.get_by( current_identifier, 'modified' );
						tracker_data.modified = becomes;
						tracker_data.is_modified = true;

						// update tracker array
						SocialPaperChange.tracker.update( tracker_data );

					}

				});

			}

			// update comment refs
			SocialPaperChange.comments.update();

		};

		/**
		 * Handle CUT key combination
		 *
		 * In practice, this seems to need identical handling to the DELETE key,
		 * so route this onwards until there's a need to do otherwise.
		 */
		this.handle_CUT = function( event ) {

			//console.log( '------------------------------------------------------------' );
			//console.log( 'handle_CUT', event );

			/*
			--------------------------------------------------------------------
			It would be nice to be able to detect what has been cut and if it
			contains one or more complete "data-incom" elements. If this can
			be detected, the corresponding comments can also be assigned to a
			"limbo" until such time as that content is pasted back in.

			This should only be a "fallback" system, because there may not be a
			subsequent paste - so comments should be immediately reassigned the
			way they are at present. A record of comments affected by the cut
			can be stored in case a subsequent paste does occur where the paste
			content matches the cut content.

			When entire paragraphs are cut, TinyMCE shifts the existing content
			"upwards" such that it becomes wrapped in the tag of the first
			complete item to have been cut. So, given:

			<p data-incom="P0">Foo</p>
			<p data-incom="P1">Bar</p>
			<p data-incom="P2">Lorem</p>
			<p data-incom="P3">Ipsum</p>

			If we cut P1 and P2, we're left with:

			<p data-incom="P0">Foo</p>
			<p data-incom="P1">Ipsum</p>

			The clipboard data contains the actual elements that have been cut,
			but with the addition of an empty paragraph, representing the
			line-break/return character at the end of the cut content:

			<p data-incom="P1">Bar</p>
			<p data-incom="P2">Lorem</p>
			<p data-incom="P3">&nbsp;</p>

			If we cut partial content spanning two elements, i.e. we cut the
			content below between [ and ]:

			<p data-incom="P0">Foo</p>
			<p data-incom="P1">Ba[r</p>
			<p data-incom="P2">Lo]rem</p>
			<p data-incom="P3">Ipsum</p>

			then we're left with:

			<p data-incom="P0">Foo</p>
			<p data-incom="P1">Barem</p>
			<p data-incom="P3">Ipsum</p>

			the clipboard data is wrapped in the tags from which it originated:

			<p data-incom="P1">r</p>
			<p data-incom="P2">Lo</p>

			Thus, the presence in the clipboard content of the 'data-incom'
			attribute itself is not a reliable way to discover if the cut
			content contains a complete data-incom element.

			Dammit.
			--------------------------------------------------------------------
			*/

			/*
			var clipboard_html, current_items = [], missing = [];

			clipboard_html = event.clipboardData.getData( 'text/html' );
			console.log( 'CUT clipboard HTML', clipboard_html );

			// get current identifiers
			$('.fee-content-body').find( '[data-incom]' ).each( function( i, element ) {
				current_items.push( $(element).attr( 'data-incom' ) );
			});
			console.log( 'CUT current', current_items );

			// find any missing items
			missing = SocialPaperChange.tracker.get_missing( current_items );
			console.log( 'CUT missing', missing );
			*/

			/*

			First we need to detect any instances of a trailing newline, as in:
			<p data-incom="Pn">&nbsp;</p>

			Then:

			If we have cut entire paragraphs, then after stripping the trailing
			newline, missing.length === (complete paragraphs).length

			If we have cut across two adjoining paragraphs, there is
			* no trailing newline,
			* missing.length === 1,
			* there are exactly 2 '<p data-incom="' elements


			If we have missing.length === 1 and we have exactly 1 '<p data-incom="'
			element then it must be a complete paragraph.

			If we have missing.length === 1 and we have more than one '<p data-incom="'
			element then it must be a complete paragraph.

			*/

			this.handle_DELETE();
		};

		/**
		 * Handle ENTER key
		 *
		 * NB: in OSX Chrome, hitting the ENTER key whilst there is an uncollapsed
		 * selection results only in the contents of the selection being deleted.
		 * This is different to how text editors tend to behave - they would also
		 * add the newline. This means that no new node is created, though one or
		 * more may be deleted.
		 *
		 * Unfortunately, there is no way to tell if there was a selection when the
		 * enter key was pressed, since me.instance.selection.isCollapsed()
		 * reports true on both keydown and keyup. We therefore have to rely on the
		 * state of SocialPaperChange.tracker.data to see what's missing.
		 */
		this.handle_ENTER = function() {

			//console.log( '------------------------------------------------------------' );
			//console.log( 'handle_ENTER' );

			var node, item, tag, identifier, number, subsequent,
				current_items = [], missing = [],
				prev_id, original_num,
				new_item, new_number = '';

			// get current identifiers
			$('.fee-content-body').find( '[data-incom]' ).each( function( i, element ) {
				current_items.push( $(element).attr( 'data-incom' ) );
			});

			// get current node and tease out data
			node = me.instance.selection.getNode();
			item = $( node );

			// is this a wp_view_after return?
			if ( me.wp_view_after === true ) {

				// get previous wp-view object's identifier
				prev_id = item.prev().find( '[data-incom]' ).attr( 'data-incom' );

				// apply to current item
				item.attr( 'data-incom', prev_id );

			}

			identifier = item.attr( 'data-incom' );

			// bail if we don't have one
			if ( 'undefined' === typeof identifier ) {
				return;
			}

			// strip tag from identifier to get number
			number = parseInt( identifier.replace( 'P', '' ) );

			// if we've hit return on the "cursor" before the video
			if ( item.hasClass( 'wpview-selection-before' ) ) {

				// store new item
				new_item = item.parent().prev( 'p' );
				new_number = number;

				// replace with previous item
				item = $('.fee-content-body').find( '[data-incom="P' + ( number - 1 ) + '"]' );
				identifier = item.attr( 'data-incom' );
				number = parseInt( identifier.replace( 'P', '' ) );

			}

			// get subsequent
			subsequent = me.get_subsequent( number );

			// find any missing items
			missing = SocialPaperChange.tracker.get_missing( current_items );

			// if there are some missing
			if ( missing.length > 0 ) {

				/**
				 * This is effectively our test for an uncollapsed selection that
				 * spans a number of paragraphs. See the docblock above.
				 */

				// remove the missing items
				$.each( missing, function( index, value ) {

					var tracker_data;

					// remove from tracker
					tracker_data = SocialPaperChange.tracker.get_by( value, 'modified' );
					SocialPaperChange.tracker.remove( tracker_data );

					// reassign comments to current item
					SocialPaperChange.comments.reassign( value, identifier );

				});

			} else {

				/**
				 * There wasn't a selection that altered the paras
				 * i.e. ENTER created a new paragraph
				 */

				number++;

			}

			// are there any?
			if ( subsequent.length > 0 ) {

				// reparse all p tags greater than this
				$.each( subsequent, function( i, element ) {

					var current_identifier, becomes, tracker_data;

					current_identifier = element.attr( 'data-incom' );

					// construct and apply new identifier
					number++;
					becomes = 'P' + number;
					element.attr( 'data-incom', becomes );

					// get data and update
					tracker_data = SocialPaperChange.tracker.get_by( current_identifier, 'modified' );
					tracker_data.modified = becomes;
					tracker_data.is_modified = true;

					// update tracker array
					SocialPaperChange.tracker.update( tracker_data );

				});

			}

			// if collapsed all along
			if ( missing.length === 0 ) {

				// if we've hit return on the "cursor" before the video, then
				// new_number will be an integer
				if ( new_number !== '' ) {

					// substitute data for new item
					original_num = new_number - 1;
					new_item.attr( 'data-incom', 'P' + new_number );

				} else {

					// recalculate and bump this item
					original_num = parseInt( identifier.replace( 'P', '' ) );
					item.attr( 'data-incom', 'P' + ( original_num + 1 ) );

				}

				// add to array
				SocialPaperChange.tracker.add( {
					is_new: true,
					is_modified: false,
					original: 'P' + original_num,
					modified: 'P' + ( original_num + 1 ),
				} );

			}

			// update comment refs
			SocialPaperChange.comments.update();

		};

		/**
		 * Handle DELETE and BACKSPACE keys
		 *
		 * Whether backspace, forward-delete, word-delete or any other is pressed,
		 * all existing paragraphs must be checked and matched against current ones.
		 *
		 * The 'node' var gives us the position of the caret after the delete has
		 * taken place, so that if paragraphs are merged, we can:
		 * (a) decrement the paras that still follow,
		 * (b) merge the comment associations for the merged paras,
		 * (c) assign comments on deleted paras to the current one.
		 *
		 * Unfortunately, there is no way to tell if there was a selection when the
		 * delete key was pressed, since me.instance.selection.isCollapsed()
		 * reports true on both keydown and keyup. We therefore have to rely on the
		 * state of SocialPaperChange.tracker.data to see what's missing.
		 */
		this.handle_DELETE = function() {

			//console.log( '------------------------------------------------------------' );
			//console.log( 'handle_DELETE' );

			var node, item, identifier, number, subsequent,
				current_items = [], missing = [],
				first, first_num,
				is_after = false;

			// get current identifiers
			$('.fee-content-body').find( '[data-incom]' ).each( function( i, element ) {
				current_items.push( $(element).attr( 'data-incom' ) );
			});

			// get current node and tease out data
			node = me.instance.selection.getNode();
			item = $( node );

			// if the node is the TinyMCE wrapper div
			if ( item.hasClass( 'fee-content-body' ) ) {

				// find any missing items
				missing = SocialPaperChange.tracker.get_missing( current_items );

				// if there are some missing
				if ( missing.length > 0 ) {

					// get the first item in the missing array
					first = missing[0];
					first_num = first.replace( 'P', '' );

					// replace container with item prior to first missing one
					item = $('.fee-content-body').find( '[data-incom="P' + ( first_num - 1 ) + '"]' );
					identifier = item.attr( 'data-incom' );
					number = parseInt( identifier.replace( 'P', '' ) );

				} else {

					// no change - bail
					return;

				}


			} else {

				// if we've hit deleted a para directly after an oEmbed
				if ( item.hasClass( 'wpview-selection-after' ) ) {

					// substitute item with wp-view object's data-incom item
					item = item.prevAll( '.wpview-selection-before' );

					// set flag
					is_after = true;

				}

				identifier = item.attr( 'data-incom' );

				// bail if we don't have one
				if ( 'undefined' === typeof identifier ) {
					return;
				}

				// strip tag from identifier to get number
				number = parseInt( identifier.replace( 'P', '' ) );

			}

			// find any missing items
			missing = SocialPaperChange.tracker.get_missing( current_items );
			//console.log( 'missing', missing );

			// bail if there's no difference
			if ( missing.length === 0 ) {
				return;
			}

			// remove the missing items
			$.each( missing, function( index, value ) {

				var tracker_data;

				// remove from tracker
				tracker_data = SocialPaperChange.tracker.get_by( value, 'modified' );
				SocialPaperChange.tracker.remove( tracker_data );

				// reassign comments to current item
				SocialPaperChange.comments.reassign( value, identifier );

			});

			// if we've hit delete with the "cursor" before the video
			if ( item.hasClass( 'wpview-selection-before' ) && is_after === false ) {

				// if there's a missing para, the oEmbed has bumped up at least one para.
				if ( missing.length > 0 ) {

					// replace with previous item
					item = $('.fee-content-body').find( '[data-incom="P' + ( number - 2 ) + '"]' );
					identifier = item.attr( 'data-incom' );
					number = parseInt( identifier.replace( 'P', '' ) );

				}

			}

			// get subsequent nodes
			subsequent = me.get_subsequent( number );

			// are there any?
			if ( subsequent.length > 0 ) {

				// reparse all p tags greater than this
				$.each( subsequent, function( i, element ) {

					var current_identifier, becomes, tracker_data;

					current_identifier = element.attr( 'data-incom' );

					// construct new identifier
					number++;
					becomes = 'P' + ( number );

					// if this is the same, there's no need to apply
					if ( current_identifier == becomes ) {
						return false;
					}

					// apply new identifier
					element.attr( 'data-incom', becomes );

					// get data and update
					tracker_data = SocialPaperChange.tracker.get_by( current_identifier, 'modified' );
					tracker_data.modified = becomes;
					tracker_data.is_modified = true;

					// update tracker array
					SocialPaperChange.tracker.update( tracker_data );

				});

			}

			// update comments
			SocialPaperChange.comments.update();

		};

		/**
		 * Handle any key that produces a printing character.
		 *
		 * We only need to track changes when the selection is *not* collapsed, since
		 * the selection could span two or more nodes.
		 */
		this.handle_PRINTING_CHAR = function( keystate ) {

			//console.log( '------------------------------------------------------------' );
			//console.log( 'handle_PRINTING_CHAR' );

			var node, item, identifier, number, subsequent,
				current_items = [], missing = [];

			// get current identifiers on keydown
			if ( ! me.instance.selection.isCollapsed() && keystate == 'keydown' ) {
				me.keydown_items = [];
				item = $( me.instance.selection.getNode() );
				if ( item.hasClass( 'fee-content-body' ) ) {
					$('.fee-content-body').find( '[data-incom]' ).each( function( i, element ) {
						me.keydown_items.push( $(element).attr( 'data-incom' ) );
					});
				}
				return;
			}

			// bail if there was never a selection
			if (
				me.instance.selection.isCollapsed() &&
				me.keydown_items.length === 0
			) {
				me.keydown_items = [];
				return;
			}

			// get keyup identifiers
			$('.fee-content-body').find( '[data-incom]' ).each( function( i, element ) {
				current_items.push( $(element).attr( 'data-incom' ) );
			});

			// get current node and tease out data
			node = me.instance.selection.getNode();
			item = $( node );
			identifier = item.attr( 'data-incom' );

			// bail if we don't have one
			if ( 'undefined' === typeof identifier ) {
				me.keydown_items = [];
				return;
			}

			// strip tag from identifier to get number
			number = parseInt( identifier.replace( 'P', '' ) );

			// get subsequent
			subsequent = me.get_subsequent( number );

			// are there any?
			if ( subsequent.length > 0 ) {

				// find missing items
				missing = $( me.keydown_items ).not( current_items ).get();

				// assign comments on missing items to current identifier
				$.each( missing, function( index, value ) {

					var tracker_data;

					// remove from tracker
					tracker_data = SocialPaperChange.tracker.get_by( value, 'modified' );
					SocialPaperChange.tracker.remove( tracker_data );

					// reassign comments to new item
					SocialPaperChange.comments.reassign( value, identifier );

				});

				// reparse all p tags greater than this
				$.each( subsequent, function( i, element ) {

					var current_identifier, becomes, tracker_data;

					current_identifier = element.attr( 'data-incom' );

					// construct and apply new identifier
					number++;
					becomes = 'P' + ( number );
					element.attr( 'data-incom', becomes );

					// get data and update
					tracker_data = SocialPaperChange.tracker.get_by( current_identifier, 'modified' );
					tracker_data.modified = becomes;
					tracker_data.is_modified = true;

					// update tracker array
					SocialPaperChange.tracker.update( tracker_data );

				});

			} else {

				// assign comments to current identifier if greater than current
				$.each( me.keydown_items, function( index, value ) {

					var n, tracker_data;

					n = parseInt( value.replace( 'P', '' ) );
					if ( n > number ) {

						// remove from tracker
						tracker_data = SocialPaperChange.tracker.get_by( value, 'modified' );
						SocialPaperChange.tracker.remove( tracker_data );

						// reassign comments
						SocialPaperChange.comments.reassign( value, identifier );

					}

				});

			}

			// update comments
			SocialPaperChange.comments.update();

			// reset keydown items
			me.keydown_items = [];

		};

		/**
		 * Cache the content of the editor.
		 *
		 * By default, WP FEE does not maintain parity between the "Read" mode
		 * content and the "Edit" mode content. If "Leave" or "Cancel" is clicked
		 * in the isDirty dialog, the editor content is not reset to its unedited
		 * state. This method stores the unedited content for retrieval.
		 */
		this.cache_set = function() {

			// store content of editor
			me.cache = me.instance.getContent();

		};

		/**
		 * Get the cached content of the editor.
		 */
		this.cache_get = function() {
			return me.cache;
		};

		/**
		 * Add Inline Comments 'data-incom' attributes to TinyMCE content.
		 *
		 * Due to the way oEmbeds work, there is a discrepancy between the original
		 * content and the editor content, so this needs to be handled to account
		 * for those discrepancies.
		 */
		this.add_atts = function() {

			// replace content of editor with original
			//me.instance.setContent( $('.fee-content-original').html(), {format : 'html'} );

			// add Inline Comments data attributes
			var elements = me.filter_elements( $('.fee-content-body p') );
			$.each( elements, function( i, element ) {
				element.attr( 'data-incom', 'P' + i );
			});

			// clear the undo queue so we can't undo beyond here
			me.instance.undoManager.clear();

		};

		/**
		 * Strip Inline Comments 'data-incom' attributes from TinyMCE content.
		 */
		this.strip_atts = function() {

			// remove class and data-incom attributes from any content
			$('.fee-content-body').find( '[data-incom]' ).each( function( i, element ) {
				element.removeAttribute( 'data-incom' );
				//element.removeAttribute( 'class' );
			});

		};

		/**
		 * Given an element's number, find all subsequent paragraphs.
		 *
		 * This needs to be done "manually" as it were, since $.nextAll only
		 * picks up consecutive siblings and oEmbeds break the sequence since
		 * they are wrapped in <div>s in the editor but <p>s in the content.
		 *
		 * @param int number The selected element's 'data-incom' number
		 * @param array elements The subsequent elements
		 */
		this.get_subsequent = function( number ) {

			var elements = [], paras, current;

			// get all relevant elements in the editor
			paras = me.filter_elements( $('.fee-content-body').find( '[data-incom]' ) );
			$.each( paras, function( i, el ) {
				current = parseInt( el.attr( 'data-incom' ).replace( 'P', '' ) );
				if ( current > number ) {
					elements.push( el );
				}
			});

			return elements;
		};

		/**
		 * Given an array of paragraph tags, filter out those which are used
		 * for UI-related purposes, e.g. inside a .wpview-wrap container which
		 * is used to wrap a "Page Break"
		 *
		 * The exception to the rule is for YouTube embeds, where we allow one
		 * of the internal paras (p.wpview-selection-before) through. This is
		 * because when the oEmbed is rendered in the original content, it is
		 * wrapped in a <p> tag, causing a mis-matched number of paragrapahs in
		 * "read" and "edit" modes.
		 *
		 * @param array elements An array of elements present in the editor
		 * @param array filtered Filtered array of elements
		 */
		this.filter_elements = function( elements ) {

			// init return
			var filtered = [];

			// try to find the para prior to the first unidentified para
			elements.each( function( i, element ) {

				var el = $(element), wp_view;

				// add to filter if not inside .wpview-wrap
				wp_view = el.closest( '.wpview-wrap' );
				if ( 'undefined' === typeof wp_view || wp_view.length === 0 ) {
					filtered.push( el );
				} else {
					// check the view type attribute
					if ( 'embedURL' === wp_view.attr( 'data-wpview-type' ) ) {
						// check if this is a p.wpview-selection-before
						if ( el.hasClass( 'wpview-selection-before' ) ) {
							//console.log( 'this', el );
							filtered.push( el );
						}
					}
				}

			});

			return filtered;

		};

	};

	/**
	 * Create Comments object.
	 */
	SocialPaperChange.comments = new function() {

		var me = this;

		// store original comment refs
		me.data_original = [];

		/**
		 * Save original comments data
		 */
		this.original_save = function() {

			// reassign comments to new item
			$( 'li[data-incom-comment]' ).each( function( i, el ) {

				var target, comment_id;

				// get custom attribute
				target = $(el).attr( 'data-incom-comment' );

				// get comment ID
				comment_id = $(el).prop( 'id' );

				// add to array
				me.data_original[comment_id] = target;

			});

		};

		/**
		 * Get original comments data
		 */
		this.original_get = function() {

			// construct formatted array
			var formatted = [], key;
			for( key in me.data_original ) {
				formatted.push({
					comment_id: key,
					original_value: me.data_original[key],
				});
			}

			return formatted;

		};

		/**
		 * Reset Inline Comments 'data-incom-comment' attributes to their original values
		 */
		this.original_reset = function() {

			// get original data
			data = me.original_get();

			// reset each of the comments
			data.forEach( function( item ) {
				$( 'li#' + item.comment_id ).attr( 'data-incom-comment', item.original_value );
			});

		};

		// tracks the comments that need their refs updated
		me.data = [];

		/**
		 * Assign Inline Comments 'data-incom-comment' attributes from one target to another
		 *
		 * @param str existing The existing identifier
		 * @param str target The target identifier
		 */
		this.reassign = function( existing, target ) {

			// reassign comments to new item
			$( 'li[data-incom-comment="' + existing + '"]' ).each( function( i, el ) {

				//console.log( 'reassigning comments: ', existing, target );

				// update custom attribute
				$(el).attr( 'data-incom-comment', target );

				// update (or add) to data-to-send-on-save
				var comment_id = $(el).prop( 'id' );
				me.data[comment_id] = target;

			});

		};

		/**
		 * Update Inline Comments 'data-incom-comment' attributes for comments
		 */
		this.update = function() {

			// with each of the tracked items
			SocialPaperChange.tracker.data.forEach( function( item ) {

				// if this item is modified and pre-existing
				if ( item.is_modified && ! item.is_new ) {

					// update the comments that reference it
					$( 'li[data-incom-comment="' + item.original + '"]' ).each( function( i, el ) {
						if ( ! $(el).hasClass( 'sp_processed' ) ) {

							//console.log( 'updating comments: ', item.original, item.modified );

							$(el).attr( 'data-incom-comment', item.modified );
							$(el).addClass( 'sp_processed' );

							// update (or add) to data-to-send-on-save
							var comment_id = $(el).prop( 'id' );
							me.data[comment_id] = item.modified;

						}
					});

				}

			});

			// remove processed identifier
			$('li.sp_processed').removeClass( 'sp_processed' );

			// rebuild tracker
			SocialPaperChange.tracker.rebuild();

		};

		/**
		 * Export data for changed comments.
		 *
		 * @return array formatted The array of amended comment data
		 */
		this.get_formatted = function() {

			// construct formatted array
			var formatted = [], key;
			for( key in me.data ) {
				formatted.push({
					comment_id: key,
					new_value: me.data[key],
				});
			}

			return formatted;

		};

	};

});

/**
 * Handle WP FEE hooks when the page is ready
 */
jQuery(document).ready( function($) {

	/**
	 * Hook into WP FEE initialisation.
	 */
	$(document).on( 'fee-editor-init', function( event ) {

		//console.log( 'fee-editor-init' );

		// if Inline Comments present
		if ( window.incom ) {

			// start tracking the editor
			SocialPaperChange.editor.track( event );

		}

	});

	/**
	 * Hook into WP FEE activation.
	 */
	$(document).on( 'fee-on', function( event ) {

		//console.log( 'fee-on' );

		// if Inline Comments present
		if ( window.incom ) {

			// build tracker array
			SocialPaperChange.tracker.init();

			// build original comments array
			SocialPaperChange.comments.original_save();

			// cache TinyMCE content
			SocialPaperChange.editor.cache_set();

			// set up attributes in TinyMCE content
			SocialPaperChange.editor.add_atts();

		}

	});

	/**
	 * Hook into WP FEE deactivation.
	 */
	$(document).on( 'fee-off', function( event ) {

		//console.log( 'fee-off' );

	});

	/**
	 * Hook into WP FEE before save.
	 */
	$(document).on( 'fee-before-save', function( event ) {

		//console.log( 'fee-before-save' );

		// if Inline Comments present
		if ( window.incom ) {

			// send an array of changed comment data along with the post data
			wp.fee.post.social_paper_comments = function() {
				var to_send = SocialPaperChange.comments.get_formatted();
				SocialPaperChange.comments.data = [];
				return to_send;
			};

		}

	});

	/**
	 * Hook into WP FEE after save.
	 */
	$(document).on( 'fee-after-save', function( event ) {

		//console.log( 'fee-after-save' );

		// if Inline Comments present
		if ( window.incom ) {

			// cache TinyMCE content
			SocialPaperChange.editor.cache_set();

			// set up attributes in TinyMCE content
			SocialPaperChange.editor.add_atts();

			// force not dirty state
			SocialPaperChange.editor.isNotDirty = 1;

			// save comments as unmodified
			SocialPaperChange.comments.original_save();

			// build tracker array
			//SocialPaperChange.tracker.init();

			// set attributes in TinyMCE content
			//SocialPaperChange.tracker.rebuild();

		}

	});

	/**
	 * Hook into clicks on the "Disable Editing" button
	 *
	 * Hooking in here means we can manipulate the content of the editor before
	 * FEE checks it for changes.
	 */
	$('a[href="#fee-edit-link"]').on( 'click', function( event ) {

		// bail if not disabling editing
		if ( ! $(this).hasClass( 'active' ) ) {
			return;
		}

		// is the editor *really* dirty? - let FEE determine this by stripping
		// just the 'data-incom' attributes from the editor before it does so
		SocialPaperChange.editor.strip_atts();

	});

	/**
	 * Hook into clicks on email moderation links.
	 *
	 * We want to make sure that FEE's isDirty() check is accurate.
	 */
	$( '.comment-actions a' ).on( 'click', function() {
		SocialPaperChange.editor.strip_atts();
	} );

	// add keydown tracker code
	$(document).on( 'keydown', function( event ) {

		// check for ESC key
		if ( event.keyCode === 27 ) {
			SocialPaperChange.editor.strip_atts();
		}

	});

	/**
	 * Hook into clicks on the "Cancel" button in the FEE "Leave" dialog
	 *
	 * Doing this means we can restore the Inline Comments attributes which have
	 * been stripped in order to let FEE decide if the editor is dirty or not.
	 */
	$('.fee-leave').find( '.fee-cancel' ).on( 'click.fee', function() {

		// rebuild attributes in TinyMCE content
		SocialPaperChange.editor.add_atts();

	} );

	/**
	 * Hook into clicks on the "Leave" button in the FEE "Leave" dialog
	 *
	 * Doing this means we can reset the Inline Comments comment attributes
	 * which have been modified during the unsaved edit.
	 */
	$('.fee-leave').find( '.fee-exit' ).on( 'click.fee', function() {

		var cached;

		// get cached TinyMCE content
		cached = SocialPaperChange.editor.cache_get();

		// apply to editor
		SocialPaperChange.editor.instance.setContent( cached );

		// reset comments to original state
		SocialPaperChange.comments.original_reset();

	} );

});
