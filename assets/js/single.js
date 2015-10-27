( function( $ ){
	$( document ).ready( function(){
		if ( 'undefined' !== typeof CACSP_Potential_Readers ) {
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

		window.SocialPaperTagBox && window.SocialPaperTagBox.init();
	} );

}( jQuery ) );
