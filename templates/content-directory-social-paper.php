
<div class="entry-content">

<?php if ( have_posts() ) : ?>

	<p>Here you can view a list of papers written by members of the community.</p>

	<ul>

	<?php
	// Start the Loop.
	while ( have_posts() ) : the_post(); ?>

		<li><a href="<?php the_permalink(); ?>" rel="bookmark"><?php the_title(); ?></a></li>

	<?php
	// End the loop.
	endwhile;
	?>

	</ul>

<?php else : ?>

	<p>No papers have been written yet.  Be the first to write a paper!</p>

<?php endif; ?>

</div>