<?php if(TRUE === apply_filters('peepso_permissions_post_create', TRUE)) {
$PeepSoGeneral = PeepSoGeneral::get_instance();
$PeepSoPostbox = PeepSoPostbox::get_instance();

?><div class="ps-postbox clearfix">
	<div class="ps-postbox-content">
		<div class="ps-postbox-status">
			<div style="position:relative">
				<div class="ps-postbox-input ps-inputbox">
					<?php // echo (isset($prefix)) ? $prefix : ''; ?>
					<textarea class="ps-textarea ps-postbox-textarea" placeholder="<?php _e(apply_filters('peepso_postbox_message', 'Say what is on your mind...'), 'peepso-core'); ?>"></textarea>
					<?php // echo (isset($suffix)) ? $suffix : ''; ?>
				</div>
				<div class="ps-postbox-addons"></div>
			</div>
			<div class="post-charcount charcount ps-postbox-charcount"></div>
		</div>
	</div>
	<div class="ps-postbox-tab ps-postbox-tab-root clearfix" style="display:none">
		<div class="ps-postbox__menu">
			<?php // $PeepSoGeneral->post_types(array('is_current_user' => isset($is_current_user) ? $is_current_user : NULL)); ?>
		</div>
	</div>
	<nav class="ps-postbox-tab selected">
		<div class="ps-postbox__menu">
			<?php $PeepSoPostbox->post_interactions(); ?>
		</div>
		<div class="ps-postbox__action ps-postbox-action">
			<button type="button" class="ps-btn ps-btn--postbox ps-button-cancel"><?php _e('Cancel', 'peepso-core'); ?></button>
			<button type="button" class="ps-btn ps-btn--postbox ps-button-action postbox-submit"><?php _e('Post', 'peepso-core'); ?></button>
		</div>
		<div class="ps-postbox-loading" style="display:none">
			<img src="<?php echo PeepSo::get_asset('images/ajax-loader.gif'); ?>">
			<div></div>
		</div>
	</nav>
</div>
<?php } // peepso_permissions_post_create ?>