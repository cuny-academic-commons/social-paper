
		<?php if ( is_search() ) : ?>
			<h2 class="currently-searching"><?php printf( __( 'Found the following results for: %s', 'social-paper' ), '<span class="search-terms">' . get_search_query() . '</span>' ); ?></h2>
		<?php endif; ?>

		<?php cacsp_pagination(); ?>

		<ul class="item-list">

		<?php while ( have_posts() ) : the_post(); ?>
			<?php cacsp_get_template_part( 'list-social-paper', 'buddypress' ); ?>
		<?php endwhile; ?>

		</ul>

		<?php cacsp_pagination( 'bottom' ); ?>