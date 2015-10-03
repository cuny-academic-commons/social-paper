<div class="sidebar-section sidebar-section-settings" id="sidebar-section-settings">
	<h2><?php esc_html_e( 'Settings', 'social-paper' ); ?></h2>

	<?php /* @todo separate out */ ?>
	<?php if ( function_exists( 'bp_is_active' ) && bp_is_active( 'groups' ) ) : ?>
		<h3><?php esc_html_e( 'Groups', 'social-paper' ); ?></h3>
		<p><?php esc_html_e( 'Your paper will appear in the group directory and activity stream of all associated groups.', 'social-paper' ) ?>
		<?php cacsp_paper_group_selector( get_queried_object_id() ); ?>
	<?php endif; ?>

</div>

<?php /*
<div class="sidebar-section sidebar-section-stats" id="sidebar-section-stats">
	<h2><?php esc_html_e( 'Stats', 'social-paper' ); ?></h2>

</div>
*/ ?>
