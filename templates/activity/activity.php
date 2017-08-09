<?php
$PeepSoActivityShortcode = PeepSoActivityShortcode::get_instance();
?>
<div class="peepso ps-page--activity-post">
	<section id="mainbody" class="ps-wrapper clearfix">
		<section id="component" role="article" class="clearfix">
			<?php PeepSoTemplate::exec_template('general', 'navbar'); ?>
<?php /*	<h2 class="ps-page-title"><?php echo PeepSo::get_option('site_frontpage_title', __('Recent Activities', 'peepso-core')); ?></h2><?php */ ?>
			<?php PeepSoTemplate::exec_template('general', 'register-panel'); ?>

			<?php /*override header*/ do_action('peepso_activity_single_override_header'); ?>

			<div class="ps-body">
			<!--<div class="ps-sidebar"></div>-->
				<div class="ps-main ps-main-full">
					<?php PeepSoTemplate::exec_template('general', 'postbox-legacy'); ?>

					<?php

					$user_id = get_current_user_id();

					$current_option = 1;

					if($user_id && FALSE === $PeepSoActivityShortcode->is_permalink_page()) {

						$stream_options = apply_filters('peepso_default_stream_options', array());

						if (count($stream_options) > 1) {

							$current_option = PeepSoActivity::get_stream_filter_users(get_current_user_id());

							if (!array_key_exists($current_option, $stream_options)) {
								$keys = array_keys($stream_options);
								$current_option = $keys[0];
							}

							update_user_meta($user_id, 'peepso_default_stream', $current_option);
							?>

							<div class="ps-tabs__wrapper ps-tabs--align">
								<div class="ps-tabs">

									<?php foreach ($stream_options as $option => $label) : ?>

										<div class="ps-tabs__item <?php echo ($option == $current_option) ? 'current':'';?>">
											<a href="<?php echo PeepSo::get_page('activity');?>?switch_default_stream=<?php echo $option;?>"><?php echo $label;?></a>
										</div>

									<?php endforeach;?>

								</div>
							</div>
						<?php
						}
					}
					?>

					<input type="hidden" id="peepso_stream_id" value="<?php echo $current_option; ?>" />

					<!-- stream activity -->
					<div class="ps-stream-wrapper">

						<!-- recent posts -->
						<div id="ps-activitystream-recent" class="ps-stream-container" style="display:none"></div>

						<!-- pinned posts -->
						<?php
						$activity = new PeepSoActivity();

						if ( FALSE === $PeepSoActivityShortcode->is_permalink_page() && $activity->has_posts($current_option, TRUE) ) { ?>
						<div id="ps-activitystream-pinned" class="ps-stream-container" data-filter="all" data-filterid="0" data-groupid data-eventid data-profileid>

						<?php
						while( $activity->next_post() ) {
							$activity->show_post(); // display post and any comments
						}
						?>

						</div>

						<?php }?>

						<!-- remaining posts -->
						<?php
						$activity = new PeepSoActivity();
						$showNoMorePostNotice = FALSE;

						if( $activity->has_posts($current_option, FALSE, PeepSoActivity::ACTIVITY_LIMIT_PAGE_LOAD) ) { ?>
							<div id="ps-activitystream" class="ps-stream-container" data-filter="all" data-filterid="0" data-groupid data-eventid data-profileid>
							<?php

								$showNoMorePostNotice = TRUE;
								$showNoMoreCounter = 0;

								// display all posts
								while( $activity->next_post() ) {
									$activity->show_post(); // display post and any comments
									if ( ++$showNoMoreCounter >= PeepSoActivity::ACTIVITY_LIMIT_PAGE_LOAD ) {
										$showNoMorePostNotice = FALSE;
									}
								}

								$activity->show_more_posts_link();
							?>
							</div>
						<?php } else if (0 === PeepSo::get_option('site_activity_hide_stream_from_guest', 0)) { ?>
							<div id="ps-no-posts" class="ps-alert"><?php _e('No posts found. Be the first one to share something amazing!', 'peepso-core'); ?></div>
							<div id="ps-activitystream" class="ps-stream-container" style="display:none" data-filter="all" data-filterid="0" data-groupid data-eventid data-profileid>
							</div>
						<?php } ?>

						<div id="ps-no-more-posts" class="ps-alert" <?php echo $showNoMorePostNotice ? '' : 'style="display:none"'; ?>><?php _e('Nothing more to show.', 'peepso-core'); ?></div>

						<?php PeepSoTemplate::exec_template('activity', 'dialogs'); ?>
					</div>
				</div>
			</div>
		</section><!--end component-->
	</section><!--end mainbody-->
</div><!--end row-->
