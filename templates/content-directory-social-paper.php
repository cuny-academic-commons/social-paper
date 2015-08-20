
<article <?php post_class(); ?>>
	<header class="entry-header">
		<h1 class="entry-title"><?php _e( 'Papers', 'social-paper' ); ?></h1>
	</header>

	<div class="entry-content">

		<?php if ( have_posts() ) : ?>

			<?php cacsp_get_template_part( 'archive-header', 'social-paper' ); ?>

			<ul>

			<?php
			// Start the Loop.
			while ( have_posts() ) : the_post();
				cacsp_get_template_part( 'archive-item', 'social-paper' );

			// End the loop.
			endwhile;
			?>

			</ul>

		<?php else : ?>

			<p><?php _e( 'No papers have been written yet.  Be the first to write a paper!', 'social-paper' ); ?></p>

		<?php endif; ?>

	</div>
</article>
