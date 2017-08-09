<?php
$PeepSoActivity = PeepSoActivity::get_instance();
$PeepSoUser		= PeepSoUser::get_instance($post_author);
$PeepSoPrivacy	= PeepSoPrivacy::get_instance();
?>
<div class="ps-js-modal-attachment--<?php echo $act_id; ?>">
	<div class="ps-stream-header">
		<!-- post author avatar -->
		<div class="ps-avatar-stream">
			<a href="<?php echo $PeepSoUser->get_profileurl(); ?>">
				<img data-author="<?php echo $post_author; ?>" src="<?php echo PeepSoUser::get_instance($post_author)->get_avatar(); ?>" alt="">
			</a>
		</div>
		<!-- post meta -->
		<div class="ps-stream-meta">
			<div class="reset-gap">
				<?php $PeepSoActivity->post_action_title(); ?>
				<?php
				$post_extras = apply_filters('peepso_post_extras', array());
				echo implode(' ', $post_extras);
				?>
			</div>
			<small class="ps-stream-time" data-timestamp="<?php $PeepSoActivity->post_timestamp(); ?>">
				<a href="<?php $PeepSoActivity->post_link(); ?>">
					<span><?php $PeepSoActivity->post_age(); ?></span>
				</a>
			</small>
			<?php if ($post_author == get_current_user_id()) { ?>
			<span class="ps-dropdown ps-dropdown-privacy ps-stream-privacy ps-js-privacy--<?php echo $act_id; ?>">
				<?php if (TRUE == $disable_privacy) { ?>
				<span style="opacity:.5">
					<span class="dropdown-value"><?php $PeepSoActivity->post_access(); ?></span>
				</span>
				<?php } else { ?>
				<a href="javascript:" data-toggle="dropdown" data-value="" class="ps-dropdown-toggle">
					<span class="dropdown-value"><?php $PeepSoActivity->post_access(); ?></span>
				</a>
				<?php wp_nonce_field('change-post-privacy-' . $act_id, '_privacy_wpnonce_' . $act_id); ?>
				<?php echo $PeepSoPrivacy->render_dropdown('activity.change_post_privacy(this, ' . $act_id . ')'); ?>
				<?php } ?>
			</span>
			<?php } ?>
		</div>
	</div>
	<div class="ps-stream-body">
		<?php if (isset($post_attachments)) { ?>
		<div>
			<p><?php echo $post_attachments; ?></p>
		</div>
		<?php } ?>
		<div class="ps-stream-attachment cstream-attachment">
			<?php echo $act_description; ?>
		</div>
	</div>
	<div class="ps-stream-actions stream-actions" data-type="stream-action">
		<input type="hidden" name="module-id" value="<?php echo $act_module_id;?>" />
		<?php wp_nonce_field('activity-delete', '_delete_nonce'); ?>
		<nav class="ps-stream-status-action ps-stream-status-action pstd-contrast">
			<?php $PeepSoActivity->post_actions(); ?>
		</nav>
	</div>
	<?php do_action('peepso_modal_before_comments'); ?>

	<?php if($likes = $PeepSoActivity->has_likes($act_id)){ ?>
	<div class="ps-stream-status cstream-likes ps-js-act-like--<?php echo $act_id; ?>" id="act-like-<?php echo $act_id; ?>" data-count="<?php echo $likes ?>">
		<?php $PeepSoActivity->show_like_count($likes); ?>
	</div>
	<?php } else { ?>
	<div class="ps-stream-status cstream-likes ps-js-act-like--<?php echo $act_id; ?>" id="act-like-<?php echo $act_id; ?>" data-count="0" style="display:none">
	</div>
	<?php } ?>

	<div class="clearfix">
		<div class="ps-comment cstream-respond wall-cocs" id="wall-cmt-<?php echo $act_id; ?>">
			<div class="ps-comment-container comment-container ps-js-comment-container ps-js-comment-container--<?php echo $act_id; ?>" data-act-id="<?php echo $act_id; ?>">
				<?php $PeepSoActivity->show_recent_comments(); ?>
			</div>
			<?php $show_commentsbox = apply_filters('peepso_commentsbox_display', apply_filters('peepso_permissions_comment_create', TRUE), $ID); ?>
			<?php if (is_user_logged_in() && $show_commentsbox ) { ?>
			<div id="act-new-comment-<?php echo $act_id; ?>" data-type="stream-newcomment" class="ps-comment-reply cstream-form stream-form wallform ps-js-comment-new" data-formblock="true">
				<div class="ps-textarea-wrapper cstream-form-input">
					<textarea
						data-act-id="<?php echo $act_id;?>"
						class="ps-textarea cstream-form-text"
						name="comment"
						oninput="return activity.on_commentbox_change(this);"
						style="min-height: 20px; resize: none; overflow: hidden; word-wrap: break-word;"
						placeholder="<?php _e('Write a comment...', 'peepso-core');?>"></textarea>

					<?php
					// call function to add button addons for comments
					$PeepSoActivity->show_commentsbox_addons();
					?>

				</div>
				<div class="ps-comment-send cstream-form-submit">
					<div class="ps-comment-loading" style="display:none;">
						<img src="<?php echo PeepSo::get_asset('images/ajax-loader.gif'); ?>" alt="" />
						<div> </div>
					</div>
					<div class="ps-comment-actions" style="display:none;">
						<button onclick="return activity.comment_cancel(<?php echo $act_id; ?>);" class="ps-btn ps-button-cancel"><?php _e('Clear', 'peepso-core'); ?></button>
						<button onclick="return activity.comment_save(<?php echo $act_id; ?>, this);" class="ps-btn ps-btn-primary ps-button-action" disabled><?php _e('Post', 'peepso-core'); ?></button>
					</div>
				</div>
			</div>
			<?php } // is_user_loggged_in ?>
		</div>
	</div>
</div>
