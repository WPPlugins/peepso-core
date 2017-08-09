<div class="ps-dialog-wrapper ps-js-dialog-avatar">
	<div class="ps-dialog-container">
		<div class="ps-dialog ps-dialog-wide">
			<div class="ps-dialog-header">
				<span><?php echo __('Change Avatar', 'peepso-core'); ?></span>
				<a href="#" class="ps-dialog-close ps-js-btn-close"><span class="ps-icon-remove"></span></a>
			</div>
			<div class="ps-dialog-body ps-js-body" style="position:relative">
				<div class="ps-alert ps-alert-danger ps-js-error"></div>
				<div class="ps-page-split">
					<div class="ps-page-half">
						<a href="#" class="fileinput-button ps-btn ps-btn-small ps-full-mobile ps-js-btn-upload">
							<?php echo __('Upload Photo', 'peepso-core'); ?>
							<img src="<?php echo PeepSo::get_asset('images/ajax-loader.gif'); ?>" alt="loading" style="padding-left:5px; display:none" />
						</a>
						<a href="#" class="ps-btn ps-btn-danger ps-btn-small ps-full-mobile ps-js-btn-remove" style="overflow:hidden; {{= data.imgOriginal ? '' : 'display:none' }}">
							<?php echo __('Remove Photo', 'peepso-core'); ?>
							<img src="<?php echo PeepSo::get_asset('images/ajax-loader.gif'); ?>" alt="loading" style="padding-left:5px; display:none" />
						</a>
						<?php if ( PeepSo::get_option('avatars_gravatar_enable') == 1 ) { ?>
							<a href="#" class="ps-btn ps-btn-small ps-full-mobile ps-js-btn-gravatar" style="overflow:hidden">
								<?php echo __('Use Gravatar', 'peepso-core'); ?>
								<img src="<?php echo PeepSo::get_asset('images/ajax-loader.gif'); ?>" alt="loading" style="padding-left:5px; display:none" />
							</a>
						<?php } ?>
						<div class="ps-gap"></div>
						<div class="ps-js-has-avatar" style="{{= data.imgOriginal ? '' : 'display:none' }}">
							<h5 class="ps-page-title"><?php echo __('Uploaded Photo', 'peepso-core'); ?></h5>
							<div class="ps-js-preview" style="position:relative; user-select:none">
								<img src="{{= data.imgOriginal || '' }}" alt="<?php echo __('Automatically Generated. (Maximum width: 160px)', 'peepso-core'); ?>"
									class="ps-image-preview ps-name-tips">
							</div>
							<div class="ps-page-footer">
								<a href="#" class="ps-btn ps-btn-small ps-full-mobile ps-avatar-crop ps-js-btn-crop"><?php echo __('Crop Image', 'peepso-core'); ?></a>
								<a href="#" class="ps-btn ps-btn-small ps-full-mobile ps-avatar-crop ps-js-btn-crop-cancel" style="display:none"><?php echo __('Cancel Cropping', 'peepso-core'); ?></a>
								<a href="#" class="ps-btn ps-btn-small ps-btn-primary ps-full-mobile ps-js-btn-crop-save" style="display:none">
									<?php echo __('Save Thumbnail', 'peepso-core'); ?>
									<img src="<?php echo PeepSo::get_asset('images/ajax-loader.gif'); ?>" alt="loading" style="padding-left:5px; display:none" />
								</a>
							</div>
						</div>
						<div class="ps-js-no-avatar" style="{{= data.imgOriginal ? 'display:none' : '' }}">
							<div class="ps-alert"><?php echo __('No avatar uploaded. Use the button above to select and upload one.', 'peepso-core'); ?></div>
						</div>
						<div class="ps-gap"></div>
					</div>
					<div class="ps-page-half ps-text--center show-avatar show-thumbnail">
						<h5 class="ps-page-title"><?php echo __('Avatar Preview', 'peepso-core'); ?></h5>
						<div class="ps-avatar ps-js-avatar">
							<img src="{{= data.imgAvatar || '' }}" alt="<?php echo __('Avatar Preview', 'peepso-core'); ?>">
						</div>
						<div class="ps-gap"></div>
						<p class="reset-gap ps-text--muted">{{
							var textPreview = <?php echo json_encode( __('This is how <strong>%s</strong> Avatar will appear throughout the entire community.', 'peepso-core') ); ?>;
							if ( +data.id === +data.myId ) {
								textPreview = <?php echo json_encode( __('This is how your Avatar will appear throughout the entire community.', 'peepso-core') ); ?>;
							} else {
								textPreview = textPreview.replace('%s', data.name );
							}
						}}{{= textPreview }}
						</p>
					</div>
				</div>
				<!-- Avatar uploader element -->
				<div style="position:relative; width:1px; height:1px; overflow:hidden">
					<input type="file" name="filedata" accept="image/*" />
				</div>
				<!-- Form disabler and loading -->
				<div class="ps-dialog-disabler ps-js-disabler">
					<div class="ps-dialog-spinner">
						<span class="ps-icon-spinner"></span>
					</div>
				</div>
			</div>
			<div class="ps-dialog-footer">
				<button class="ps-btn ps-btn-primary ps-js-btn-finalize" disabled="disabled">
					<?php _e('Done', 'peepso-core'); ?>
					<img src="<?php echo PeepSo::get_asset('images/ajax-loader.gif'); ?>" alt="loading" style="padding-left:5px; display:none" />
				</button>
			</div>
		</div>
	</div>
</div>
