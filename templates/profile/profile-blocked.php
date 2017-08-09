<?php
$PeepSoProfile=PeepSoProfile::get_instance();
?>
<div class="peepso">
	<?php PeepSoTemplate::exec_template('general', 'navbar'); ?>
	<?php PeepSoTemplate::exec_template('profile', 'submenu'); ?>
	<section id="mainbody" class="ps-page ps-submenu-page">
		<section id="component" role="article" class="clearfix">
		<!--<h4><?php _e('Blocked Users', 'peepso-core'); ?></h4>-->

			<div class="ps-profile-blocked cprofile-blocked">
			<?php if ($PeepSoProfile->has_blocked()) { ?>
				<div class="ps-text--center ps-text--muted">
					<?php _e('The following users are blocked and will not be able to see your posts or your Profile.', 'peepso-core'); ?>
				</div>
				<div class="ps-gap"></div>				
				<div class="ps-members">
					<?php
					while ($PeepSoProfile->next_blocked()) {
						$PeepSoProfile->show_blocked();
					} ?>
				</div>
			<?php } ?>
			</div>
<!-- blocked=<?php echo '['.$PeepSoProfile->num_blocked().']'; ?> -->
			<?php if ($PeepSoProfile->num_blocked()) { ?>
			<div class="ps-gap"></div>
			<button id="delete-selected" class="ps-btn ps-btn-danger" onclick="ps_blocks.delete_selected(); return false;"><?php _e('Remove Selected Blocks', 'peepso-core'); ?></button>
			<?php } else { ?>
			<div class="ps-text--center ps-text--muted">
				<?php _e('You have no blocked users.', 'peepso-core'); ?>
			</div>
			<?php } ?>
		</section><!--end compnent-->
	</section><!--end mainbody-->
</div><!--end row-->

<div style="display: none;">
	<div id="peepso-no-block-user-selected"><?php echo _e('Please select at least one user to unblock.', 'peepso-core') ?></div>
</div>
