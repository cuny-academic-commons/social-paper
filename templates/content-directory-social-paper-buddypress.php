
<article <?php post_class(); ?>>
	<header class="entry-header">
		<h1 class="entry-title"><?php _e( 'Papers', 'social-paper' ); ?></h1>
	</header>

	<div class="entry-content">
	<div id="buddypress">

		<div id="papers-dir-search" class="dir-search" role="search">
			<form action="" method="get" id="search-papers-form">
				<label for="papers_search"><input type="text" name="<?php the_search_query(); ?>" id="papers_search" placeholder="<?php the_search_query(); ?>" /></label>
				<input type="submit" id="papers_search_submit" name="papers_search_submit" value="<?php _e( 'Search', 'social-paper' ); ?>" />
			</form>
		</div><!-- #papers-dir-search -->

		<?php cacsp_get_template_part( 'archive-header', 'social-paper' ); ?>

		<div class="item-list-tabs" role="navigation">
			<ul>
				<li class="selected" id="papers-all"><a href="<?php echo esc_url( get_post_type_archive_link( 'cacsp_paper' ) ); ?>"><?php printf( __( 'All Papers <span>%s</span>', 'social-paper' ), $GLOBALS['wp_query']->found_posts ); ?></a></li>

				<?php if ( is_user_logged_in() ) : ?>
					<li id="papers-personal"><a href="<?php echo bp_loggedin_user_domain() . 'papers/'; ?>"><?php printf( __( 'My Papers <span>%s</span>', 'social-paper' ), cacsp_get_total_paper_count_for_user() ); ?></a></li>
				<?php endif; ?>

				<?php

				/**
				 * Fires inside the papers directory tab.
				 */
				do_action( 'bp_papers_directory_tabs' ); ?>

				<?php if ( is_user_logged_in() ) : ?>
					<li id="papers-create"><a class="no-ajax" href="<?php cacsp_the_new_paper_link(); ?>"><?php _e( 'Create a Paper', 'social-paper' ); ?></a></li>
				<?php endif; ?>

			</ul>
		</div><!-- .item-list-tabs -->

		<?php if ( have_posts() ) : ?>

			<div class="papers">
				<?php cacsp_get_template_part( 'loop-social-paper', 'buddypress' ); ?>
			</div>

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
