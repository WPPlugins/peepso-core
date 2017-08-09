<?php
$PeepSoProfile = PeepSoProfile::get_instance();
?>
<div class="peepso">
	<?php PeepSoTemplate::exec_template('general', 'navbar'); ?>
	<?php PeepSoTemplate::exec_template('profile', 'submenu'); ?>
	<section id="mainbody" class="ps-page ps-submenu-page ps-page-unstyled">
		<section id="component" role="article" class="clearfix">
		<!--<h4><?php _e('Notifications', 'peepso-core'); ?></h4>-->

			<div class="ps-profile-notifications">
				<?php if ($PeepSoProfile->has_notifications()) { ?>
					<div class="ps-text--center ps-text--muted ps-padding">
						<?php _e('Your notifications are stored for only 20 day(s). Old notifications will be deleted.', 'peepso-core'); ?>
					</div>
					<div class="ps-notifications">
						<?php
						while ($PeepSoProfile->next_notification()) {
							$PeepSoProfile->show_notification();
						} ?>
					</div>
				<?php } else { ?>
					<div class="ps-text--center ps-text--muted ps-padding">
						<?php _e('You currently have no notifications', 'peepso-core'); ?>
					</div>
				<?php } ?>
			</div>
			<?php if ($PeepSoProfile->has_notifications()) { ?>
			<div class="ps-padding">
				<button id="notifications-select-all" class="ps-btn ps-button-cancel" onclick="ps_profile_notification.select_all(); return false;"><?php _e('Select All', 'peepso-core'); ?></button>
				<button id="notifications-unselect-all" class="ps-btn ps-button-cancel" onclick="ps_profile_notification.unselect_all(); return false;" style="display: none;"><?php _e('Unselect All', 'peepso-core'); ?></button>
				<button id="delete-selected" class="ps-btn ps-btn-danger" onclick="ps_profile_notification.delete_selected(); return false;"><?php _e('Delete Selected', 'peepso-core'); ?></button>
			</div>
			<?php } ?>
		</section><!--end compnent-->
	</section><!--end mainbody-->
</div><!--end row-->