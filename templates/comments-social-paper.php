<?php
/**
 * Custom comments template displayed on single social paper pages.
 *
 * Right now, it's a copy-n-paste of TwentyFifteen's template with a few minor
 * tweaks.
 *
 * @package Social_Paper
 * @subpackage Template
 */

/*
 * If the current post is protected by a password and
 * the visitor has not yet entered the password we will
 * return early without loading the comments.
 */
if ( post_password_required() ) {
	return;
}
?>

<div id="comments" class="comments-area">

	<?php if ( have_comments() ) : ?>
		<h2 class="comments-title">
			<?php _e( 'Responses', 'social-paper' ); ?>
		</h2>

		<ul class="comment-list">
			<?php
				wp_list_comments( array(
					'short_ping'  => true,
					'avatar_size' => 36,
				) );
			?>
		</ul><!-- .comment-list -->

	<?php endif; // have_comments() ?>

	<?php
		// If comments are closed and there are comments, let's leave a little note, shall we?
		if ( ! comments_open() && get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) :
	?>
		<p class="no-comments"><?php _e( 'Comments are closed.', 'twentyfifteen' ); ?></p>
	<?php endif; ?>

	<?php comment_form( array(
		'comment_field' => '<textarea id="comment" name="comment" cols="65" rows="4" placeholder="' . __( 'Leave a reply', 'social-paper' ). '" required="required"></textarea>',
		'title_reply'         => '',
		'comment_notes_after' => ''
	) ); ?>

</div><!-- .comments-area -->
