
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
							<span class="item-paper-creator"><?php cacsp_the_loop_author(); ?></span>
							<span class="item-paper-date"><?php cacsp_the_loop_date(); ?></span>

							<?php

							/**
							 * Fires inside the display of the directory paper's meta section.
							 */
							do_action( 'bp_directory_papers_item_meta' ); ?>
						</div>

						<div class="item-desc">
							<?php the_excerpt(); ?>
						</div>

						<?php

						/**
						 * Fires inside the display of a directory papers item.
						 */
						do_action( 'bp_directory_papers_item' ); ?>
					</div>

					<div class="action">
						<?php

						/**
						 * Fires inside the 'action' div to display custom markup.
						 */
						do_action( 'bp_directory_papers_actions' ); ?>

					</div>

					<div class="clear"></div>
				</li>
