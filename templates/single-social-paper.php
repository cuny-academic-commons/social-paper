<?php
/**
 * Template for displaying single social papers.
 *
 * @package Social_Paper
 * @subpackage Template
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width">
	<link rel="profile" href="http://gmpg.org/xfn/11">
	<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">
	<?php if ( ! current_theme_supports( 'title-tag' ) ) : ?><title><?php wp_title( '|', true, 'right' ); ?></title><?php endif; ?>
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>

<div id="page">
<?php
// Start the loop.
while ( have_posts() ) : the_post();
?>

	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

		<header class="entry-header">
			<?php if ( cacsp_has_feature_image() && post_type_supports( 'cacsp_paper', 'thumbnail' ) ) : ?>
				<div class="cacsp_feature_image">
					<?php echo get_the_post_thumbnail( get_the_ID(), 'cacsp-feature' ); ?>
				</div>
			<?php endif; ?>
			<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
		</header><!-- .entry-header -->

		<div class="entry-content">
			<?php
				/* translators: %s: Name of current post */
				the_content( sprintf(
					__( 'Continue reading %s', 'twentyfifteen' ),
					the_title( '<span class="screen-reader-text">', '</span>', false )
				) );
			?>

			<?php
				wp_link_pages( array(
					'before'      => '<div class="page-links"><span class="page-links-title">' . __( 'Pages:', 'social-paper' ) . '</span>',
					'after'       => '</div>',
					'link_before' => '<span>',
					'link_after'  => '</span>',
					'pagelink'    => '<span class="screen-reader-text">' . __( 'Page', 'social-paper' ) . ' </span>%',
					'separator'   => '<span class="screen-reader-text">, </span>',
				) );
			?>
		</div><!-- .entry-content -->

		<?php if ( current_user_can( 'edit_post', get_queried_object()->ID ) ) : ?>
		<div class="entry-sidebar">
			<?php cacsp_locate_template( 'sidebar-single-social-paper.php', true ); ?>
		</div>
		<?php endif; ?>

		<?php if ( 'new' !== get_query_var( 'name' ) && 'auto-draft' !== get_queried_object()->post_status ) : ?>
		<footer class="entry-footer">
			<div class="entry-author">
				<a href="<?php the_author_meta( 'url' ); ?>"><?php echo get_avatar( $post->post_author, 50, 'mm', '', array(
					'class' => 'avatar'
				) ); ?>
				</a>

				<h3><?php the_author_link(); ?></h3>
				<?php
				if ( $bio = get_the_author_meta( 'description' ) ) {
					echo "<p>{$bio}</p>";
				}
				?>

				<?php
					$time_string = '<time class="entry-date published updated" datetime="%1$s">%2$s</time>';

					$time_string = sprintf( $time_string,
						esc_attr( get_the_date( 'c' ) ),
						get_the_date()
					);

					printf( '<span class="posted-on">%1$s <a href="%2$s" rel="bookmark">%3$s</a></span>',
						_x( 'Published on', 'Used before publish date.', 'social-paper' ),
						esc_url( get_permalink() ),
						$time_string
					);
				?>
			</div>

			<?php //edit_post_link( __( 'Edit', 'social-paper' ), '<span class="edit-link">', '</span>' ); ?>
		</footer><!-- .entry-footer -->

		<?php
			// If comments are open or we have at least one comment, load up the comment template.
			if ( comments_open() || get_comments_number() ) :
				comments_template();
			endif;

		endif;
		?>

	</article><!-- #post-## -->

<?php
// End the loop.
endwhile;
?>
</div>

<?php wp_footer(); ?>
</body>
</html>
