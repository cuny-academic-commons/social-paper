
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
				cacsp_get_template_part( 'list-social-paper' );

			// End the loop.
			endwhile;
			?>

			</ul>

		<?php else : ?>

			<?php if ( is_user_logged_in() ) : ?>

				<p><?php printf( __( 'No papers have been written yet.  %sBe the first to write a paper!%s', 'social-paper' ), '<a href="' . trailingslashit( get_post_type_archive_link( 'cacsp_paper' ) . 'new' ) . '">', '</a>' ); ?></p>

			<?php else : ?>

				<p><?php _e( 'No papers have been written yet.', 'social-paper' ); ?></p>

			<?php endif; ?>

		<?php endif; ?>

	</div>
</article>
