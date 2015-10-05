<div class="sidebar-section sidebar-section-settings" id="sidebar-section-settings">
	<h2><?php esc_html_e( 'Settings', 'social-paper' ); ?></h2>

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

</div>

<?php /*
<div class="sidebar-section sidebar-section-stats" id="sidebar-section-stats">
	<h2><?php esc_html_e( 'Stats', 'social-paper' ); ?></h2>

</div>
*/ ?>
