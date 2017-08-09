<div class="ps-postbox clearfix cstream-edit">
	<div class="ps-postbox-content">
		<div class="ps-postbox-status">
			<div style="position:relative">
				<div class="ps-postbox-input ps-inputbox">
					<textarea class="ps-textarea ps-postbox-textarea"><?php echo esc_textarea($cont); ?></textarea>
				</div>
			</div>
		</div>
	</div>
	<nav class="ps-postbox-tab selected">
		<div class="ps-postbox__menu">
			<div class="ps-postbox__menu-item"><a>&nbsp;</a></div>
		</div>
		<div class="ps-postbox__action ps-postbox-action">
			<button class="ps-btn ps-btn--postbox ps-button-cancel" onclick="return activity.option_cancel_edit_description(<?php echo $act_id; ?>);"><?php _e('Cancel', 'peepso-core'); ?></button>
			<button class="ps-btn ps-btn--postbox ps-button-action" onclick="return activity.option_save_description(<?php echo $act_id; ?>, '<?php echo $type; ?>', <?php echo $act_external_id; ?>);"><?php _e('Save', 'peepso-core'); ?></button>
		</div>
		<div class="ps-edit-loading" style="display: none;">
			<img src="<?php echo PeepSo::get_asset('images/ajax-loader.gif'); ?>">
			<div> </div>
		</div>
	</nav>
</div>