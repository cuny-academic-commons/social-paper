<form role="search" method="get" class="cacsp-search-form" id="cacsp-search-form" action="<?php cacsp_group_search_url(); ?>">
	<div>
		<label class="screen-reader-text hidden" for="cacsp_search"><?php _e( 'Search for:', 'social-paper' ); ?></label>
		<input type="text" value="<?php echo esc_attr( cacsp_get_search_terms() ); ?>" placeholder="<?php echo esc_attr__( 'Search group papers...', 'social-paper' ); ?>" name="cacsp_search" class="cacsp-search-input" id="cacsp_search" />
		<input class="button" type="submit" id="cacsp_search_submit" value="<?php esc_attr_e( 'Search', 'social-paper' ); ?>" />
	</div>
</form>