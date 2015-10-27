<?php
/**
 * Header template displayed on social paper archive.
 *
 * @package Social_Paper
 * @subpackage Template
 */
?>
<p><?php _e( 'Here you can view a list of papers written by members of the community.', 'social-paper' ); ?></p>

<?php if ( ! empty( $_GET['cacsp_paper_tag'] ) ) : ?>
	<p><?php printf( __( 'Viewing papers with the tag: %s', 'social-paper' ), '<em>' . esc_html( $_GET['cacsp_paper_tag'] ) . '</em>' ); ?></p>
<?php endif; ?>
