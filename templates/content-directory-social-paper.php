
<div class="entry-content">

<?php if ( have_posts() ) : ?>

	<?php cacsp_get_template_part( 'archive-header', 'social-paper' ); ?>

	<ul>

	<?php
	// Start the Loop.
	while ( have_posts() ) : the_post(); ?>

		<?php cacsp_get_template_part( 'archive-item', 'social-paper' ); ?>

	<?php
	// End the loop.
	endwhile;
	?>

	</ul>

<?php else : ?>

	<p><?php _e( 'No papers have been written yet.  Be the first to write a paper!', 'social-paper' ); ?></p>

<?php endif; ?>

</div>
