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
 * Is the paper Protected?
 *
 * @param int $paper_id ID of the paper.
 * @return bool
 */
function cacsp_paper_is_protected( $paper_id ) {
	$status = wp_get_object_terms( $paper_id, 'cacsp_paper_status', array(
		'update_term_meta_cache' => false,
	) );

	$protected = false;
	if ( ! empty( $status ) ) {
		foreach ( $status as $_status ) {
			if ( 'protected' === $_status->name ) {
				$protected = true;
				break;
			}
		}
	}

	return $protected;
}

/**
 * Get IDs of papers that are protected to the user, ie those they don't have access to.
 *
 * As a hack, we cache using the 'last_changed' incrementor from the 'posts' group.
 *
 * @param int $user_id ID of the user.
 * @return array Array of post IDs that are off-limits to the user.
 */
function cacsp_get_protected_papers_for_user( $user_id ) {
	$last_changed = wp_cache_get( 'last_changed', 'posts' );
	if ( ! $last_changed ) {
		$last_changed = microtime();
		wp_cache_set( 'last_changed', $last_changed, 'posts' );
	}

	$cache_key = 'cacsp_protected_papers:' . $user_id . ':' . $last_changed;
	$protected_paper_ids = wp_cache_get( $cache_key, 'posts' );

	if ( false === $protected_paper_ids ) {
		$base_args = array(
			'post_type' => 'cacsp_paper',
			'post_status' => 'any',
			'fields' => 'ids',
			'nopaging' => true,
			'orderby' => false,
		);

		remove_action( 'pre_get_posts', 'cacsp_filter_query_for_access_protection' );

		/*
		 * Three queries
		 * 1. Papers where I'm a reader
		 * 2. Papers where I'm a member of the associated group
		 * 3. Papers that are protected and authored by someone else but post__not_in the previous two
		 */
		$reader_args = array_merge( array(
			'tax_query' => array(
				array(
					'taxonomy' => 'cacsp_paper_reader',
					'terms' => array( 'reader_' . $user_id ),
					'field' => 'name',
				)
			),
		), $base_args );
		$reader_query = new WP_Query( $reader_args );
		$reader_papers = $reader_query->posts;

		$group_papers = array();
		if ( bp_is_active( 'groups' ) ) {
			$group_terms = array();
			$group_ids = cacsp_get_groups_of_user( $user_id );
			foreach ( $group_ids as $group_id ) {
				$group_terms[] = 'group_' . $group_id;
			}

			if ( empty( $group_terms ) ) {
				$group_papers = array();
			} else {
				$group_args = array_merge( array(
					'tax_query' => array(
						array(
							'taxonomy' => 'cacsp_paper_group',
							'terms' => $group_terms,
							'field' => 'name',
							'operator' => 'IN',
						),
					),
				), $base_args );
				$group_query = new WP_Query( $group_args );
				$group_papers = $group_query->posts;
			}
		}

		$protected_args = array_merge( array(
			'tax_query' => array(
				array(
					'taxonomy' => 'cacsp_paper_status',
					'terms' => array( 'protected' ),
					'field' => 'name',
				),
			),
			'post__not_in' => array_merge( $reader_papers, $group_papers ),
			'author__not_in' => array( $user_id ),
		), $base_args );
		$protected_query = new WP_Query( $protected_args );
		$protected_paper_ids = $protected_query->posts;

		add_action( 'pre_get_posts', 'cacsp_filter_query_for_access_protection' );

		wp_cache_set( $cache_key, $protected_paper_ids, 'posts' );
	}

	return array_map( 'intval', $protected_paper_ids );
}

/**
 * Ensure that comments are open on new papers.
 *
 * @since 1.0.0
 *
 * @param string $status Default 'comment_status'.
 * @param string $post_type Post type name.
 * @param string $comment_type Comment type name.
 * @return string
 */
function cacsp_default_comment_status( $status, $post_type, $comment_type ) {
	if ( 'cacsp_paper' === $post_type ) {
		$status = 'open';
	}

	return $status;
}
add_filter( 'get_default_comment_status', 'cacsp_default_comment_status', 10, 3 );

/**
 * Force-approve comments from logged-in users.
 *
 * @since 1.0.0
 *
 * @param bool|string $approved    The approval status.
 * @param array       $commentdata Comment data.
 */
function cacsp_approve_loggedin_comments( $approved, $commentdata ) {
	$post = get_post( $commentdata['comment_post_ID'] );
	if ( 'cacsp_paper' !== $post->post_type ) {
		return $approved;
	}

	if ( ! is_user_logged_in() ) {
		return $approved;
	}

	// Sanity-check.
	$user = get_userdata( get_current_user_id() );
	if ( ! $user->exists() || $user->user_email !== $commentdata['comment_author_email'] ) {
		return $approved;
	}

	return 1;
}
add_filter( 'pre_comment_approved', 'cacsp_approve_loggedin_comments', 10, 2 );

/**
 * Get unapproved comments for a paper.
 *
 * @since 1.0.0
 *
 * @param int $paper_id ID of the paper.
 * @return array
 */
function cacsp_get_unapproved_comments( $paper_id ) {
	return get_comments( array(
		'post_id' => $paper_id,
		'status' => 0,
	) );
}

/**
 * Get unapproved comment count for a paper.
 *
 * Calls cacsp_get_unapproved_comments(). Shouldn't matter for performance, since `get_comments()` will cache.
 *
 * @since 1.0.0
 *
 * @param int $post_id ID of the post.
 * @return int
 */
function cacsp_get_unapproved_comment_count( $paper_id ) {
	$unapproved_comments = cacsp_get_unapproved_comments( $paper_id );
	return count( $unapproved_comments );
}

/**
 * Get the max length (in characters) of the Description field.
 *
 * @since 1.0.0
 *
 * @return int
 */
function cacsp_get_description_max_length() {
	return (int) apply_filters( 'cacsp_get_description_max_length', 250 );
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

	$group_links = array();
	if ( function_exists( 'buddypress' ) && bp_is_active( 'groups' ) ) {
		$group_links = cacsp_get_group_links_for_paper( $GLOBALS['post']->ID );
	}


	if ( (int) $GLOBALS['post']->post_author === bp_loggedin_user_id() ) {
		if ( is_archive() ) {
			if ( ! empty( $group_links ) ) {
				printf( __( 'Written by you in %s', 'social-paper' ), implode( ', ', $group_links ) );
			} else {
				_e( 'Written by you.', 'social-paper' );
			}
		}

	} else {
		if ( ! empty( $group_links ) ) {
			printf( __( 'Written by %s in %s', 'social-paper' ), '<a href="' . bp_core_get_user_domain( $GLOBALS['post']->post_author ) . '">' . bp_core_get_user_displayname( $GLOBALS['post']->post_author )  . '</a>', implode( ', ', $group_links ) );
		} else {
			printf( __( 'Written by %s', 'social-paper' ), '<a href="' . bp_core_get_user_domain( $GLOBALS['post']->post_author ) . '">' . bp_core_get_user_displayname( $GLOBALS['post']->post_author )  . '</a>' );
		}
	}
}

/**
 * Template tag to output the relative date of a social paper in a loop.
 *
 * If published date doesn't exist, falls back to last modified date.
 */
function cacsp_the_loop_date() {
	$date_modified = get_post_modified_time( 'U', true );
	$date_created  = get_post_time( 'U', true );

	if ( $date_modified === $date_created ) {
		/* translators: "Created [relative time since]" */
		printf( __( 'Created %s', 'social-paper' ), bp_core_time_since( $date_created ) );
	} else {
		/* translators: "Updated [relative time since]" */
		printf( __( 'Updated %s', 'social-paper' ), bp_core_time_since( $date_modified ) );
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
