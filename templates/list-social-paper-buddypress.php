
				<li>
					<div class="item-avatar">
						<a href="<?php the_permalink(); ?>" rel="bookmark"><?php bp_post_author_avatar(); ?></a>
					</div>

					<div class="item">
						<div class="item-title">
							<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>

							<?php
								if ( 'publish' !== get_post_status() ) {
									echo '<span ';
									post_class();
									echo '>' . get_post_status_object( get_post_status() )->label . '</span>';
								}
							?>
						</div>

						<div class="item-meta">
							<span class="activity"><?php cacsp_the_loop_date(); ?></span>
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