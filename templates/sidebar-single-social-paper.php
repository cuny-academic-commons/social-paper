<div class="sidebar-section sidebar-section-settings" id="sidebar-section-settings">
	<h2><?php esc_html_e( 'Settings', 'social-paper' ); ?></h2>

	<div class="sidebar-section-subsection">
		<?php $excerpt = get_the_excerpt(); ?>
		<h3><?php esc_html_e( 'Description', 'social-paper' ); ?></h3>
		<p><label for="cacsp-paper-description"><?php esc_html_e( 'A description of your paper, in 300 characters or less, to be displayed in directories.', 'social-paper' ); ?></label> <span class="cacsp-description-char-ratio">(<span><?php echo strlen( $excerpt ); ?></span>/<?php echo cacsp_get_description_max_length(); ?>)</span></p>
		<textarea name="cacsp-paper-description" class="cacsp-paper-description" id="cacsp-paper-description" /><?php echo esc_textarea( $excerpt ); ?></textarea>
		<p class="description"><?php esc_html_e( 'If blank, an excerpt will be used.', 'social-paper' ); ?></p>
		<?php wp_nonce_field( 'cacsp-paper-description-' . get_queried_object_id(), 'cacsp-paper-description-nonce', false, true ); ?>
	</div>

	<div class="sidebar-section-subsection">
		<h3><?php esc_html_e( 'Access', 'social-paper' ); ?></h3>
		<?php $protected = cacsp_paper_is_protected( get_queried_object_id() ); ?>
		<p>
		<input type="radio" name="cacsp-paper-status" class="cacsp-paper-status" id="cacsp-paper-status-public" value="public" <?php checked( ! $protected ) ?> /> <label for="cacsp-paper-status-public"><strong><?php esc_html_e( 'Public', 'social-paper' ); ?></strong> &middot; <?php esc_html_e( 'Anyone can read and comment on my paper.', 'social-paper' ); ?></label><br />
		<input type="radio" name="cacsp-paper-status" class="cacsp-paper-status" id="cacsp-paper-status-protected" value="protected" <?php checked( $protected ) ?> /> <label for="cacsp-paper-status-protected"><strong><?php esc_html_e( 'Protected', 'social-paper' ); ?></strong> &middot; <?php esc_html_e( 'Only the readers and group members specified below can read and comment on my paper.', 'social-paper' ); ?></label>
		</p>

		<?php wp_nonce_field( 'cacsp-paper-status', 'cacsp-paper-status-nonce' ); ?>
	</div>

	<?php /* @todo separate out */ ?>
	<?php if ( function_exists( 'bp_is_active' ) && bp_is_active( 'groups' ) ) : ?>
		<div class="sidebar-section-subsection">
			<h3><?php esc_html_e( 'Groups', 'social-paper' ); ?></h3>
			<p><?php esc_html_e( 'Your paper will appear in the group directory and activity stream of all associated groups.', 'social-paper' ) ?>
			<?php cacsp_paper_group_selector( get_queried_object_id() ); ?>
		</div>
	<?php endif; ?>

	<div class="sidebar-section-subsection sidebar-section-subsection-readers <?php if ( ! cacsp_paper_is_protected( get_queried_object_id() ) ) : ?>hidden<?php endif; ?>">
		<h3><?php esc_html_e( 'Readers', 'social-paper' ); ?></h3>
		<p><?php esc_html_e( 'Readers are allowed to read and comment on your paper.', 'social-paper' ) ?>
		<?php cacsp_paper_reader_selector( get_queried_object_id() ); ?>
	</div>

	<?php if ( $unapproved = cacsp_get_unapproved_comments( get_queried_object_id() ) ) : ?>
	<div class="sidebar-section-subsection sidebar-section-subsection-unapproved-comments">
		<h3><?php esc_html_e( 'Unapproved Comments', 'social-paper' ); ?></h3>

		<ol>
		<?php foreach ( $unapproved as $u_comment ) : ?>
			<li>
			<?php printf( __( 'Author:&nbsp;%s', 'social-paper' ), esc_html( $u_comment->comment_author ) ); ?><br />
			<?php printf( __( 'Email:&nbsp; %s', 'social-paper' ), esc_html( $u_comment->comment_author_email ) ); ?><br />
			<?php if ( $u_comment->comment_author_url ) : ?>
				<?php printf( __( 'URL:&nbsp; %s', 'social-paper' ), esc_url( $u_comment->comment_author_url ) ); ?><br />
			<?php endif; ?>

			<blockquote><?php echo esc_html( $u_comment->comment_content ); ?></blockquote>

			<?php
			$in_response_to = false;
			if ( $u_comment->comment_parent ) {
				$in_response_to = sprintf( '<a target="_blank" href="%s">#</a>', esc_url( get_comment_link( $u_comment->comment_parent ) ) );
			} elseif ( '' !== $pnum = get_comment_meta( $u_comment->comment_ID, 'data_incom', true ) ) {
				$in_response_to = sprintf( '<a target="_blank" href="%s">#</a>', esc_url( add_query_arg( 'para', $pnum, get_permalink( get_queried_object() ) ) ) );
			}

			if ( $in_response_to ) {
				printf( esc_html__( 'In response to:&nbsp;%s', 'social-paper' ) . '<br />', $in_response_to );
			}
			?>

			<span class="unapproved-comment-timestamp"><?php echo date( 'M j, Y H:i:s', strtotime( $u_comment->comment_date ) ) ?></span><br />

			<span class="comment-actions">
				<?php cacsp_unapproved_comment_links( $u_comment ); ?>
			</span>
			</li>

		<?php endforeach; ?>
		</ol>
	</div>
	<?php endif; ?>
</div>

<?php /*
<div class="sidebar-section sidebar-section-stats" id="sidebar-section-stats">
	<h2><?php esc_html_e( 'Stats', 'social-paper' ); ?></h2>

</div>
*/ ?>
