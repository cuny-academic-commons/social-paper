<?php
/**
 * Utility functions used by Social Paper
 *
 * @package Social_Paper
 * @subpackage Functions
 */

/**
 * Get post types that Social Paper should support.
 *
 * @return array
 */
function cacsp_get_supported_post_types() {
	/**
	 * Filters the post types that Social Paper should support.
	 *
	 * @param type array
	 */
	return apply_filters( 'cacsp_get_supported_post_types', array( 'cacsp_paper' ) );
}

/**
 * Locate templates used by Social Paper.
 *
 * Essentially a wrapper function for {@link locate_template()} but supports
 * our custom template directory.
 *
 * @see locate_template() for parameter documentation
 */
function cacsp_locate_template( $template_names, $load = false, $require_once = true ) {
	// check WP first
	$located = locate_template( $template_names, false );

	// fallback to bundled template on failure
	if ( empty( $located ) ) {
		$located = '';
		foreach ( (array) $template_names as $template_name ) {
			if ( ! $template_name ) {
				continue;
			}

			if ( file_exists( Social_Paper::$TEMPLATEPATH . '/' . $template_name ) ) {
				$located = Social_Paper::$TEMPLATEPATH . '/' . $template_name;
				break;
			}
		}
	}

	if ( true === (bool) $load && '' !== $located ) {
		load_template( $located, $require_once );
	} else {
		return $located;
	}
}

/**
 * Load a Social Paper template part into a template.
 *
 * Essentially a duplicate of {@link get_template_part()} but supports
 * our custom template directory.
 *
 * @see get_template_part() for parameter documentation
 */
function cacsp_get_template_part( $slug, $name = null ) {
	/** This action is documented in wp-includes/general-template.php */
	do_action( "get_template_part_{$slug}", $slug, $name );

	$templates = array();
	$name = (string) $name;
	if ( '' !== $name ) {
		$templates[] = "{$slug}-{$name}.php";
	}

	$templates[] = "{$slug}.php";

	cacsp_locate_template( $templates, true, false );
}

/**
 * Determine whether we're on a Social Paper page
 *
 * @return bool
 */
function cacsp_is_page() {
	return (bool) Social_Paper::$is_page;
}

/**
 * Determine whether we're on the Social Paper archive page
 *
 * @return bool
 */
function cacsp_is_archive() {
	return (bool) Social_Paper::$is_archive;
}

/**
 * Template tag to output pagination on archive page.
 *
 * Pagination resembles the markup from BuddyPress.
 */
function cacsp_pagination( $type = 'top' ) {
	// no pagination? bail!
	if ( '' === get_the_posts_pagination() ) {
		return;
	}

	$pag_args = array(
		'prev_text' => _x( '&larr;', 'Pagination previous text', 'social-paper' ),
		'next_text' => _x( '&rarr;', 'Pagination next text', 'social-paper' ),
		'before_page_number' => '<span class="meta-nav screen-reader-text">' . __( 'Page', 'social-paper' ) . ' </span>',
	);

	if ( defined( 'DOING_AJAX' ) && true === DOING_AJAX ) {
		add_filter( 'get_pagenum_link', create_function( '', "
			return trailingslashit( get_post_type_archive_link( 'cacsp_paper' ) );
		" ) );
		$pag_args['format'] = '';
		$pag_args['base'] = trailingslashit( get_post_type_archive_link( 'cacsp_paper' ) ) . 'page/%#%/';

		if ( ! empty( $_POST['search_terms'] ) ) {
			$pag_args['base'] .= '?s=' . esc_attr( $_POST['search_terms'] );
		}

		if ( ! empty( $_POST['scope'] ) && 'personal' === $_POST['scope'] && is_user_logged_in() ) {
			if ( ! empty( $_POST['search_terms'] ) ) {
				$pag_args['base'] .= '&';
			} else {
				$pag_args['base'] .= '?';
			}

			$pag_args['base'] .= 'user=' . bp_loggedin_user_id();
		}
	}
?>

	<div id="pag-<?php esc_attr_e( $type ); ?>" class="pagination no-ajax">
		<div class="pag-count">
			<?php
				if ( 1 === (int) $GLOBALS['wp_query']->found_posts ) {
					_e( 'Viewing 1 paper', 'social-paper' );

				} else {
					$curr_page = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;

					$start_num = intval( ( $curr_page - 1 ) * get_query_var( 'posts_per_page' ) ) + 1;
					$to_num    = ( $start_num + ( get_query_var( 'posts_per_page' ) - 1 ) > $GLOBALS['wp_query']->found_posts ) ? $GLOBALS['wp_query']->found_posts : $start_num + ( get_query_var( 'posts_per_page' ) - 1 );

					printf( _n( 'Viewing %1$s - %2$s of %3$s paper', 'Viewing %1$s - %2$s of %3$s papers', (int) $GLOBALS['wp_query']->found_posts, 'social-paper' ), number_format_i18n( $start_num ), number_format_i18n( $to_num ), number_format_i18n( $GLOBALS['wp_query']->found_posts ) );
				}
			?>
		</div>

		<div class="pagination-links">
			<?php echo paginate_links( $pag_args ); ?>
		</div>
	</div>

<?php
}

/**
 * Template tag to output the author of a social paper in a loop.
 */
function cacsp_the_loop_author() {
	if ( empty( $GLOBALS['post'] ) ) {
		return;
	}

	if ( (int) $GLOBALS['post']->post_author === bp_loggedin_user_id() ) {
		if ( is_archive() ) {
			_e( 'Written by you.', 'social-paper' );
		}

	} else {
		printf( __( 'Written by %s', 'social-paper' ), '<a href="' . bp_core_get_user_domain( $GLOBALS['post']->post_author ) . '">' . bp_core_get_username( $GLOBALS['post']->post_author )  . '</a>' );
	}
}

/**
 * Template tag to output the relative date of a social paper in a loop.
 *
 * If published date doesn't exist, falls back to last modified date.
 */
function cacsp_the_loop_date() {
	$date = get_post_time( 'U', true );

	if ( (int) $date > 0 ) {
		/* translators: "Created [relative time since]" */
		printf( __( 'Created %s', 'social-paper' ), bp_core_time_since( $date ) );
	} else {
		/* translators: "Updated [relative time since]" */
		printf( __( 'Updated %s', 'social-paper' ), bp_core_time_since( get_post_modified_time( 'U', true ) ) );
	}
}

/**
 * Template tag to output the link to create a new paper.
 *
 * No capability check here.
 */
function cacsp_the_new_paper_link() {
	echo cacsp_get_the_new_paper_link();
}

	/**
	 * Returns the link to create a new paper.
	 *
	 * No capability check here.
	 */
	function cacsp_get_the_new_paper_link() {
		return trailingslashit( get_post_type_archive_link( 'cacsp_paper' ) . 'new' );
	}

if ( ! function_exists( 'remove_anonymous_object_filter' ) ) :
/**
 * Remove an anonymous object filter.
 *
 * @param  string $tag    Hook name.
 * @param  string $class  Class name
 * @param  string $method Method name
 * @param  bool   $strict Whether to check if the calling class matches exactly with $class.  If
 *                        false, all methods (including parent class) matching $method will be
 *                        removed.  If true, calling class must match $class in order to be removed.
 * @return void
 *
 * @link http://wordpress.stackexchange.com/a/57088 Tweaked by r-a-y for strict class checks.
 */
function remove_anonymous_object_filter( $tag = '', $class = '', $method = '', $strict = true ) {
	$filters = $GLOBALS['wp_filter'][ $tag ];
	if ( empty ( $filters ) ) {
		return;
	}

	foreach ( $filters as $priority => $filter ) {
		foreach ( $filter as $identifier => $function ) {
			if ( ! is_array( $function ) ) {
				continue;
			}

			// mod by r-a-y - bail from closures; prevents fatal error
			if ( $function['function'] instanceOf Closure ) {
				continue;
			}

			if ( is_a( $function['function'][0], $class )
				and $method === $function['function'][1]
			) {
				// mod by r-a-y - strict class name checks
				if ( true === (bool) $strict && $class !== get_class( $function['function'][0] ) ) {
					continue;
				}

				remove_filter(
					$tag,
					array ( $function['function'][0], $method ),
					$priority
				);
			}
		}
	}
}
endif;

if ( ! function_exists( 'wp_styles' ) ) :
/**
 * Abstraction of {@link wp_styles()} function
 *
 * Initialize $wp_styles if it has not been set.
 *
 * @global WP_Styles $wp_styles
 *
 * @since 4.2.0
 *
 * @return WP_Styles WP_Styles instance.
 */
function wp_styles() {
	global $wp_styles;
	if ( ! ( $wp_styles instanceof WP_Styles ) ) {
		$wp_styles = new WP_Styles();
	}
	return $wp_styles;
}
endif;