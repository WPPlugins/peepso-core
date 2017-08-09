<?php
$PeepSoProfile=PeepSoProfile::get_instance();
$PeepSoUser = $PeepSoProfile->user;
?>
<div id="dialog-upload-cover">
	<div id="dialog-upload-cover-title" class="hidden"><?php _e('Change Cover Photo', 'peepso-core'); ?></div>
	<div id="dialog-upload-cover-content">
		<div class="ps-loading-image" style="display: none;">
			<img src="<?php echo PeepSo::get_asset('images/ajax-loader.gif'); ?>">
			<div> </div>
		</div>

		<div class="ps-alert ps-alert-danger errors error-container ps-js-error"></div>

		<ul class="ps-list <?php if ($PeepSoUser->has_cover()) { echo 'ps-list-half'; } ?> upload-cover">
			<li class="ps-list-item">
				<span class="ps-btn ps-full fileinput-button">
					<?php _e('Upload Photo', 'peepso-core'); ?>
					<input class="fileupload" type="file" name="filedata" />
				</span>
			</li>

			<?php if ($PeepSoUser->has_cover()) { ?>
			<li class="ps-list-item">
				<a href="javascript:void(0);" onclick="profile.remove_cover_photo(<?php echo $PeepSoUser->get_id(); ?>);" class="ps-btn ps-btn-danger ps-full"><?php _e('Remove Cover Photo', 'peepso-core'); ?></a>
			</li>
			<?php } ?>

			<?php wp_nonce_field('cover-photo', '_covernonce'); ?>
		</ul>
	</div>
</div>
<div style="display: none;">
	<div id="profile-cover-error-filetype"><?php _e('The file type you uploaded is not allowed. Only JPEG/PNG allowed.', 'peepso-core'); ?></div>
	<div id="profile-cover-error-filesize"><?php printf(__('The file size you uploaded is too big. The maximum file size is %s.', 'peepso-core'), '<strong>' . PeepSoGeneral::get_instance()->upload_size() . '</strong>'); ?></div>
</div>
