<div class="ps-postbox clearfix cstream-edit">
	<div class="ps-postbox-content">
		<div class="ps-postbox-status">
			<div style="position:relative">
				<div class="ps-postbox-input ps-inputbox">
					<?php echo (isset($prefix)) ? $prefix : ''; ?>
					<textarea class="ps-textarea ps-postbox-textarea" placeholder="<?php _e(apply_filters('peepso_postbox_message', 'Say what is on your mind...'), 'peepso-core'); ?>"><?php echo $cont; ?></textarea>
					<?php echo (isset($suffix)) ? $suffix : ''; ?>
				</div>
			</div>
			<div class="post-charcount charcount ps-postbox-charcount"><?php echo PeepSo::get_option('site_status_limit', 4000) ?></div>
		</div>
	</div>
	<nav class="ps-postbox-tab selected">
		<div class="ps-postbox__menu">
			<div class="ps-postbox__menu-item"><a>&nbsp;</a></div>
		</div>
		<div class="ps-postbox__action ps-postbox-action">
			<button type="button" class="ps-btn ps-btn--postbox ps-button-cancel" onclick="return activity.option_canceledit(<?php echo $act_id; ?>);"><?php _e('Cancel', 'peepso-core'); ?></button>
			<button type="button" class="ps-btn ps-btn--postbox ps-button-action postbox-submit" onclick="return activity.option_savepost(<?php echo $act_id; ?>);"><?php _e('Post', 'peepso-core'); ?></button>
		</div>
		<div class="ps-edit-loading" style="display: none;">
			<img src="<?php echo PeepSo::get_asset('images/ajax-loader.gif'); ?>">
			<div> </div>
		</div>
	</nav>
</div>