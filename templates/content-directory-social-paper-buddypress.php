
<article <?php post_class(); ?>>
	<header class="entry-header">
		<h1 class="entry-title"><?php _e( 'Papers', 'social-paper' ); ?></h1>
	</header>

	<div class="entry-content">
	<div id="buddypress">

		<?php if ( have_posts() ) : ?>

			<?php cacsp_get_template_part( 'archive-header', 'social-paper' ); ?>

			<?php cacsp_pagination(); ?>

			<ul class="item-list">

			<?php while ( have_posts() ) : the_post(); ?>
				<?php cacsp_get_template_part( 'list-social-paper', 'buddypress' ); ?>
			<?php endwhile; ?>

			</ul>

			<?php cacsp_pagination( 'bottom' ); ?>

		<?php else : ?>

			<div id="message" class="info">

				<?php if ( is_user_logged_in() ) : ?>

					<p><?php printf( __( 'No papers have been written yet.  %sBe the first to write a paper!%s', 'social-paper' ), '<a href="' . cacsp_get_the_new_paper_link() . '">', '</a>' ); ?></p>

				<?php else : ?>

					<p><?php _e( 'No papers have been written yet.', 'social-paper' ); ?></p>

				<?php endif; ?>

			</div>

		<?php endif; ?>

	</div>
	</div>
</article>
