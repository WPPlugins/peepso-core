<?php
$user = PeepSoUser::get_instance(PeepSoProfileShortcode::get_instance()->get_view_user_id());

$can_edit = FALSE;
if($user->get_id() == get_current_user_id() || current_user_can('edit_users')) {
	$can_edit = TRUE;
}

?>
<?php
$PeepSoProfile = PeepSoProfile::get_instance();
?>
<div class="peepso">
	<?php PeepSoTemplate::exec_template('general', 'navbar'); ?>
	<?php PeepSoTemplate::exec_template('profile', 'submenu'); ?>
	<section id="mainbody" class="ps-page ps-submenu-page ps-page--preferences">
		<section id="component" role="article" class="clearfix">
		<!--<h4 class="ps-page-title"><?php _e('Preferences', 'peepso-core'); ?></h4>-->
		
		<div class="cfield-list creset-list">
			<?php if ($PeepSoProfile->num_preferences_fields()) { ?>
				<?php $PeepSoProfile->preferences_form_fields(); ?>
			<?php } else { ?>
				<?php _e('You have no configurable preferences settings.', 'peepso-core'); ?>
			<?php } ?>
		</div>
		</section><!--end compnent-->
	</section><!--end mainbody-->
</div><!--end row-->

