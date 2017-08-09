<?php $PeepSoActivity = PeepSoActivity::get_instance(); ?>
<div class="cstream-edit ps-comment-edit ps-js-comment-edit">
	<div class="ps-textarea-wrapper cstream-form-input">
		<textarea class="ps-textarea cstream-form-text" placeholder="<?php _e('Write a comment...', 'peepso-core');?>"><?php echo $data['cont'];?></textarea>
		<?php $PeepSoActivity->show_commentsbox_addons($data['post_id']); ?>
	</div>
	<div style="text-align:right">
		<button class="ps-btn ps-btn-small ps-button-cancel" onclick="return activity.option_canceleditcomment(<?php echo $data['post_id'];?>, this);"><?php _e('Cancel', 'peepso-core'); ?></button>
		<button class="ps-btn ps-btn-small ps-button-action" onclick="return activity.option_savecomment(<?php echo $data['post_id']; ?>, this);"><?php _e('Save', 'peepso-core'); ?></button>
	</div>
	<div class="ps-edit-loading" style="display:none;">
		<img src="<?php echo PeepSo::get_asset('images/ajax-loader.gif'); ?>" alt="" />
		<div> </div>
	</div>
</div>
