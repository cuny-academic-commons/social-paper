( function( $ ){
	var $description,
		$description_char_ratio,
		$description_char_count;

	$( document ).ready( function(){
		$( 'body' ).removeClass( 'no-js' ).addClass( 'js' );

		if ( 'undefined' !== typeof CACSP_Potential_Readers && $.isFunction( $.fn.select2 ) ) {
			$( '#cacsp-group-selector' ).select2( {
				placeholder: SocialPaperL18n.group_placeholder
			} );

			$( '#cacsp-reader-selector' ).select2( {
				placeholder: SocialPaperL18n.reader_placeholder,
				data: CACSP_Potential_Readers
			} );
		}

		if ( 'undefined' !== typeof bp && 'undefined' !== typeof bp.mentions.users ) {
			var $incom_text = $( '#incom-commentform textarea' );
			$incom_text.bp_mentions( bp.mentions.users );

			// Don't let Inline Comments collapse comments on @-mentions selection.
			$( '#atwho-container' ).click( function( e ) {
				e.stopPropagation();
			});
		}

		// Initialize the tags interface.
		window.SocialPaperTagBox && window.SocialPaperTagBox.init();

		// Character count for the Description field.
		$description = $( '#cacsp-paper-description' );
		$description_char_ratio = $( '.cacsp-description-char-ratio' );
		$description_char_count = $description_char_ratio.find( 'span' );
		$description.on( 'input', function() {
			update_description_char_count();
		} );

		// Run right away, to color the counts.
		update_description_char_count();

		// Autogrow the description field.
		$description.autogrow( {
			onInitialize: true,
			fixMinHeight: false,
			animate: false
		} );
	} );

	update_description_char_count = function() {
                var count = 0;
                if ( $description.val() ) {
                        count = $description.val().length;
                }

		var class_to_add;

		$description_char_count.html( count );

		if ( count > SocialPaperL18n.description_max_length ) {
			class_to_add = 'red';
		} else if ( count > ( SocialPaperL18n.description_max_length * .75 ) ) {
			class_to_add = 'orange';
		}

		$description_char_ratio.removeClass( 'red orange' );
		$description_char_ratio.addClass( class_to_add );
	}

}( jQuery ) );
