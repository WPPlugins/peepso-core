<div class="peepso">
	<?php PeepSoTemplate::exec_template('general', 'navbar'); ?>
	<?php PeepSoTemplate::exec_template('general', 'register-panel'); ?>
	<?php if(get_current_user_id() > 0 || (get_current_user_id() == 0 && $allow_guest_access)) { ?>
	<section id="mainbody" class="ps-page-unstyled">
		<section id="component" role="article" class="clearfix">
			<h4 class="ps-page-title"><?php _e('Members', 'peepso-core'); ?></h4>

            <?php PeepSoTemplate::exec_template('general','wsi'); ?>

			<form class="ps-form ps-form-search" role="form" name="form-peepso-search" onsubmit="return false;">
				<div class="ps-form-row">
					<input placeholder="<?php _e('Start typing to search...', 'peepso-core');?>" type="text" class="ps-input full ps-js-members-query" name="query" value="" />
				</div>
				<a href="javascript:" class="ps-form-search-opt">
					<span class="ps-icon-cog"></span>
				</a>
			</form>
			<div class="ps-js-page-filters" style="display:none;">
				<div class="ps-page-filters">
					<div class="ps-filters-row">
						<label><?php _e('Gender', 'peepso-core'); ?></label>
						<select class="ps-select ps-js-members-gender" style="margin-bottom:5px">
							<option value=""><?php _e('Any', 'peepso-core'); ?></option>
							<?php
							if (!empty($genders) && is_array($genders)) {
								foreach ($genders as $key => $value) {
									?>
									<option value="<?php echo $key; ?>"><?php echo $value; ?></option>
									<?php
								}
							}
							?>
						</select>
					</div>

					<?php $default_sorting = PeepSo::get_option('site_memberspage_default_sorting',''); ?>
					<div class="ps-filters-row">
						<label><?php _e('Sort', 'peepso-core'); ?></label>
						<select class="ps-select ps-js-members-sortby" style="margin-bottom:5px">
							<option value=""><?php _e('Alphabetical', 'peepso-core'); ?></option>
							<option <?php echo ('peepso_last_activity' == $default_sorting) ? ' selected="selected" ' : '';?> value="peepso_last_activity|asc"><?php _e('Recently online', 'peepso-core'); ?></option>
							<option <?php echo ('registered' == $default_sorting) ? ' selected="selected" ' : '';?>value="registered|desc"><?php _e('Latest members', 'peepso-core'); ?></option>
						</select>
					</div>

					<div class="ps-filters-row">
						<label><?php _e('Avatars', 'peepso-core');?></label>
						<div class="ps-checkbox">
							<input type="checkbox" id="only-avatars" name="avatar" value="1" class="ps-js-members-avatar" />
							<label for="only-avatars" style="font-weight:normal"><?php _e('Only users with avatars', 'peepso-core'); ?></label>
						</div>
					</div>
				</div>
			</div>

			<div class="clearfix mb-20"></div>
			<div class="ps-members clearfix ps-js-members"></div>
			<div class="ps-scroll clearfix ps-js-members-triggerscroll">
				<img class="post-ajax-loader ps-js-members-loading" src="<?php echo PeepSo::get_asset('images/ajax-loader.gif'); ?>" alt="" style="display:none" />
			</div>
		</section>
	</section>
	<?php } ?>
</div><!--end row-->

<?php

PeepSoTemplate::exec_template('activity', 'dialogs');
