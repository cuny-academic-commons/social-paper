( function( $ ){
	$( document ).ready( function(){
		$( '#cacsp-group-selector' ).select2( {
			placeholder: SocialPaperL18n.group_placeholder
		} );

		$( '#cacsp-reader-selector' ).select2( {
			placeholder: SocialPaperL18n.reader_placeholder,
			data: CACSP_Potential_Readers
		} );
	} );
}( jQuery ) );
