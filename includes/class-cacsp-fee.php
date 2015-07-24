<?php
/**
 * Custom class of FEE used by Social Paper.
 *
 * @package    Social_Paper
 * @subpackage Classes
 */

class_exists( 'FEE' ) || exit;

/**
 * Child class of FEE used by Social Paper.
 *
 * Extends {@link FEE} class to fix various issues.
 *
 * @package    Social_Paper
 * @subpackage Classes
 */
class CACSP_FEE extends FEE {
	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();
	}

	/** OVERRIDES ***********************************************************/

	/**
	 * Pass global $post object.
	 *
	 * @link https://github.com/iseulde/wp-front-end-editor/pull/228
	 */
	function ajax_post() {
		require_once( ABSPATH . '/wp-admin/includes/post.php' );

		if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'update-post_' . $_POST['post_ID'] ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to edit this item.' ) ) );
		}

		$_POST['post_title'] = strip_tags( $_POST['post_title'] );

		$post_id = edit_post();

		if ( isset( $_POST['save'] ) || isset( $_POST['publish'] ) ) {
			$status = get_post_status( $post_id );

			if ( isset( $_POST['publish'] ) ) {
				switch ( $status ) {
					case 'pending':
						$message = 8;
						break;
					case 'future':
						$message = 9;
						break;
					default:
						$message = 6;
				}
			} else {
				$message = 'draft' == $status ? 10 : 1 ;
			}
		} else {
			$message = 4;
		}

		// MOD by CAC
		global $post;
		// end MOD

		$post = get_post( $post_id );

		wp_send_json_success( array(
			'message' => $this->get_message( $post, $message ),
			'post' => $post,
			'processedPostContent' => apply_filters( 'the_content', $post->post_content )
		) );
	}

	/**
	 * Pass the $post->ID to wp_enqueue_media().
	 *
	 * @link https://github.com/cuny-academic-commons/social-paper/commit/b046979143c066f46975491f5fc9bbd662e06d29
	 */
	function wp_enqueue_scripts() {
		global $post;

		// do what FEE does
		parent::wp_enqueue_scripts();

		// here's our mod - requeue media with post ID
		if ( $this->has_fee() ) {
			wp_enqueue_media( array( 'post' => $post->ID ) );
		}
	}

	/**
	 * Groan.  Copy over the parent footer() method to fix a few things.
	 *
	 * - Added 'fee_tax_buttons' hook.
	 * - Fixed notice.
	 *
	 * @link https://github.com/cuny-academic-commons/social-paper/commit/6482497897eacd364bb3ec3842d3171e90846d5b
	 * @link https://github.com/cuny-academic-commons/social-paper/commit/d4b31a249f9104aa8943006aedb44869011f66c3
	 */
	function footer() {
		global $post;

		$post_type = $post->post_type;
		$post_type_object = get_post_type_object( $post_type );
		$can_publish = current_user_can( $post_type_object->cap->publish_posts );

		?>
		<div class="wp-core-ui">
			<div id="fee-notice-area" class="wp-core-ui">
				<div id="lost-connection-notice" class="error hidden">
					<p><span class="spinner"></span> <?php _e( '<strong>Connection lost.</strong> Saving has been disabled until you&#8217;re reconnected.' ); ?>
					<span class="hide-if-no-sessionstorage"><?php _e( 'We&#8217;re backing up this post in your browser, just in case.' ); ?></span>
					</p>
				</div>
			</div>
			<div id="local-storage-notice" class="hidden">
				<p class="local-restore">
					<?php _e( 'The backup of this post in your browser is different from the version below.' ); ?> <a class="restore-backup" href="#"><?php _e( 'Restore the backup.' ); ?></a>
				</p>
				<p class="undo-restore hidden">
					<?php _e( 'Post restored successfully.' ); ?> <a class="undo-restore-backup" href="#"><?php _e( 'Undo.' ); ?></a>
				</p>
				<div class="dashicons dashicons-dismiss"></div>
			</div>
			<input type="hidden" id="post_ID" name="post_ID" value="<?php echo $post->ID; ?>">
			<div class="fee-toolbar">
				<div class="fee-toolbar-right">
					<?php if ( in_array( 'category', get_object_taxonomies( $post ) ) ) { ?>
						<button class="button button-large fee-button-categories"><div class="dashicons dashicons-category"></div></button>
					<?php } ?>

					<?php /** MOD by CAC **/ ?>
					<?php do_action( 'fee_tax_buttons', $post ); ?>
					<?php /** end MOD **/ ?>

					<?php if ( ! in_array( $post->post_status, array( 'publish', 'future', 'pending' ) ) ) { ?>
						<button <?php if ( 'private' == $post->post_status ) { ?>style="display:none"<?php } ?> class="button button-large fee-save"><?php _e( 'Save Draft' ); ?></button>
					<?php } elseif ( 'pending' === $post->post_status && $can_publish ) { ?>
						<button class="button button-large fee-save"><?php _e( 'Save as Pending' ); ?></button>
					<?php } ?>

					<div class="button-group">
						<?php if ( ! in_array( $post->post_status, array( 'publish', 'future', 'private' ) ) || 0 === $post->ID ) { ?>
							<?php if ( $can_publish ) { ?>
								<?php if ( ! empty($post->post_date_gmt ) && time() < strtotime( $post->post_date_gmt . ' +0000' ) ) { ?>
									<button class="button button-primary button-large fee-publish"><?php _e( 'Schedule' ); ?></button>
								<?php } else { ?>
									<button class="button button-primary button-large fee-publish"><?php _e( 'Publish' ); ?></button>
								<?php } ?>
							<?php } else { ?>
								<button class="button button-primary button-large fee-publish"><?php _e( 'Submit for Review' ); ?></button>
							<?php } ?>
						<?php } else { ?>
							<button class="button button-primary button-large fee-save"><?php _e( 'Update' ); ?></button>
						<?php } ?>
						<button class="button button-primary button-large fee-publish-options" style="padding: 0 2px 2px 1px;">
							<div class="dashicons dashicons-arrow-down"></div>
						</button>
					</div>
				</div>
			</div>
			<div class="fee-publish-options-dropdown">
				<label for="fee-post-status">
					<div class="dashicons dashicons-post-status" style="margin-top: 5px;"></div>
					<select id="fee-post-status">
						<?php if ( 'publish' === $post->post_status ) { ?>
							<option<?php selected( $post->post_status, 'publish' ); ?> value="publish"><?php _e( 'Published' ); ?></option>
						<?php } elseif ( 'private' === $post->post_status ) { ?>
							<option<?php selected( $post->post_status, 'private' ); ?> value="publish"><?php _e( 'Privately Published' ); ?></option>
						<?php } elseif ( 'future' === $post->post_status ) { ?>
							<option<?php selected( $post->post_status, 'future' ); ?> value="future"><?php _e( 'Scheduled' ); ?></option>
						<?php } ?>

						<option<?php selected( $post->post_status, 'pending' ); ?> value="pending"><?php _e( 'Pending Review' ); ?></option>

						<?php if ( 'auto-draft' === $post->post_status ) { ?>
							<option<?php selected( $post->post_status, 'auto-draft' ); ?> value="draft"><?php _e( 'Draft' ); ?></option>
						<?php } else { ?>
							<option<?php selected( $post->post_status, 'draft' ); ?> value="draft"><?php _e( 'Draft' ); ?></option>
						<?php } ?>
					</select>
				</label>

				<?php if ( $can_publish ) {
					if ( 'private' === $post->post_status ) {
						$post->post_password = '';
						$visibility = 'private';
					} elseif ( ! empty( $post->post_password ) ) {
						$visibility = 'password';
					} elseif ( $post_type == 'post' && is_sticky( $post->ID ) ) {
						$visibility = 'sticky';
						$visibility_trans = __( 'Public, Sticky' );
					} else {
						$visibility = 'public';
					}
				?>
					<label for="fee-post-visibility">
						<div class="dashicons dashicons-visibility" style="margin-top: 5px;"></div>
						<select id="fee-post-visibility">
							<option<?php selected( $visibility, 'public' ); ?> value="public"><?php _e( 'Public' ); ?></option>
							<option<?php selected( $visibility, 'sticky' ); ?> value="sticky"><?php _e( 'Public, Sticky' ); ?></option>
							<option<?php selected( $visibility, 'password' ); ?> value="password"><?php _e( 'Password protected' ); ?></option>
							<option<?php selected( $visibility, 'private' ); ?> value="private"><?php _e( 'Private' ); ?></option>
						</select>
						<div<?php  echo $visibility === 'password' ? '' : ' style="display: none;"'; ?>>
							<div class="dashicons dashicons-admin-network" style="margin-top: 5px;"></div>
							<input type="text" id="fee-post-password" value="<?php echo esc_attr( $post->post_password ); ?>"  maxlength="20" />
						</div>
					</label>
				<?php } ?>

				<?php if ( post_type_supports( $post_type, 'author' ) && ( is_super_admin() || current_user_can( $post_type_object->cap->edit_others_posts ) ) ) { ?>
					<label for="fee-post-author">
						<div class="dashicons dashicons-admin-users" style="margin-top: 5px;"></div>
						<?php wp_dropdown_users( array(
							'who' => 'authors',
							'name' => 'fee-post-author',
							'selected' => $post->post_author,
							'include_selected' => true
						) ); ?>
					</label>
				<?php } ?>
			</div>
			<div class="fee-alert fee-leave">
				<div class="fee-alert-body">
					<p><?php _e( 'The changes you made will be lost if you navigate away from this page.' ); ?></p>
					<button class="button fee-cancel">Cancel</button>
					<?php if ( in_array( $post->post_status, array( 'auto-draft', 'draft', 'pending' ) ) ) { ?>
						<button class="button fee-save-and-exit"><?php _e( 'Save and leave' ); ?></button>
					<?php } else { ?>
						<button class="button fee-save-and-exit"><?php _e( 'Update and leave' ); ?></button>
					<?php } ?>
					<button class="button button-primary fee-exit">Leave</button>
				</div>
			</div>
			<?php
			require( ABSPATH  . '/wp-admin/includes/meta-boxes.php' );

			$taxonomies = get_object_taxonomies( $post );

			if ( is_array( $taxonomies ) ) {
				foreach ( $taxonomies as $tax_name ) {
					$taxonomy = get_taxonomy( $tax_name );

					if ( ! $taxonomy->show_ui || false === $taxonomy->meta_box_cb ) {
						continue;
					}

					?>
					<div class="modal fee-<?php echo $tax_name; ?>-modal" tabindex="-1" role="dialog" aria-hidden="true">
						<div class="modal-dialog modal-sm">
							<div class="modal-content">
								<div class="modal-header">
									<button data-dismiss="modal" style="float: right;"><span aria-hidden="true">&times;</span><span class="sr-only"><?php _e( 'Close' ); ?></span></button>
									<div class="modal-title" id="myModalLabel"><?php echo $taxonomy->labels->name; ?></div>
								</div>
								<div class="modal-body">
									<?php /** MOD by CAC **/ ?>
									<?php call_user_func( $taxonomy->meta_box_cb, $post, array( 'title' => $taxonomy->labels->name, 'args' => array( 'taxonomy' => $tax_name ) ) ); ?>
									<?php /** end MOD **/ ?>
								</div>
								<div class="modal-footer">
									<button class="button button-primary" data-dismiss="modal"><?php _e( 'Close' ); ?></button>
								</div>
							</div>
						</div>
					</div>
					<?php
				}
			}
			?>
		</div>
		<?php
	}

	/**
	 * Include admin post code if necessary.
	 *
	 * @link https://github.com/iseulde/wp-front-end-editor/pull/230
	 */
	function get_sample_permalink( $post ) {
		$_post = get_post( $post );
		$_post->post_status = 'published';

		// MOD by CAC
		if ( ! function_exists( 'get_sample_permalink' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/post.php' );
		}
		// end MOD

		$sample = get_sample_permalink( $_post );

		return $sample[0];
	}
}