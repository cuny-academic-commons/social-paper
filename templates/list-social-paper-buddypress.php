
				<li>
					<div class="item-avatar">
						<a href="<?php the_permalink(); ?>" rel="bookmark"><?php bp_post_author_avatar(); ?></a>
					</div>

					<div class="item">
						<div class="item-title">
							<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
						</div>

						<div class="item-meta">
							<?php /* translators: "Created [relative time since]" */ ?>
							<span class="activity"><?php printf( __( 'Created %s', 'social-paper' ), bp_core_time_since( get_post_time( 'U', true ) ) ); ?></span>
						</div>

						<?php

						/**
						 * Fires inside the display of a directory papers item.
						 */
						do_action( 'bp_directory_papers_item' ); ?>
					</div>

					<div class="action">
						<span class="item-site-creator" style="font-size:90%;"><?php cacsp_the_loop_author(); ?></span>

						<?php

						/**
						 * Fires inside the 'action' div to display custom markup.
						 */
						do_action( 'bp_directory_papers_actions' ); ?>

					</div>

					<div class="clear"></div>
				</li>