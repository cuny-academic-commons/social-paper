
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
				<li>
					<div class="item-avatar">
						<a href="<?php the_permalink(); ?>" rel="bookmark"><?php bp_post_author_avatar(); ?></a>
					</div>

					<div class="item">
						<div class="item-title">
							<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
						</div>

						<div class="item-meta">
							<span class="activity"><?php printf( __( 'Created %s', 'social-paper' ), bp_core_time_since( get_post_time( 'U', true ) ) ); ?></span>
						</div>

						<?php

						/**
						 * Fires inside the display of a directory papers item.
						 */
						do_action( 'bp_directory_papers_item' ); ?>
					</div>

					<div class="action">
						<span class="item-site-creator" style="font-size:90%;"><?php printf( __( 'Written by %s', 'social-paper' ), '<a href="' . bp_core_get_user_domain( $post->post_author ) . '">' . bp_core_get_username( $post->post_author )  . '</a>' ); ?></span>

						<?php

						/**
						 * Fires inside the 'action' div to display custom markup.
						 */
						do_action( 'bp_directory_papers_actions' ); ?>

					</div>

					<div class="clear"></div>
				</li>

			<?php endwhile; ?>

			</ul>

			<?php cacsp_pagination( 'bottom' ); ?>

		<?php else : ?>

			<div id="message" class="info">

				<?php if ( is_user_logged_in() ) : ?>

					<p><?php printf( __( 'No papers have been written yet.  %sBe the first to write a paper!%s', 'social-paper' ), '<a href="' . trailingslashit( get_post_type_archive_link( 'cacsp_paper' ) . 'new' ) . '">', '</a>' ); ?></p>

				<?php else : ?>

					<p><?php _e( 'No papers have been written yet.', 'social-paper' ); ?></p>

				<?php endif; ?>

			</div>

		<?php endif; ?>

	</div>
	</div>
</article>
