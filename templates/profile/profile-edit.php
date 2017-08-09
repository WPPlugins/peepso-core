<?php
$PeepSoProfile = PeepSoProfile::get_instance();
?>
<div class="peepso">
	<?php PeepSoTemplate::exec_template('general', 'navbar'); ?>
	<?php PeepSoTemplate::exec_template('profile', 'submenu'); ?>
	<section id="mainbody" class="ps-page ps-submenu-page">
		<section id="component" role="article" class="clearfix">
		<!--<h4 class="ps-page-title"><?php _e('Edit Account', 'peepso-core'); ?></h4>-->

			<div class="ps-form-container">
				<?php if (strlen($PeepSoProfile->edit_form_message())) { ?>
				<div class="ps-alert ps-alert-success">
					<?php echo $PeepSoProfile->edit_form_message(); ?>
				</div>
				<?php } ?>
				<div class="ps-form-legend">
					<?php _e('Basic Information', 'peepso-core'); ?>
				</div>
				<?php $PeepSoProfile->edit_form(); ?>
				<div class="ps-form-group">
					<label for=""></label>
					<span class="ps-form-helper"><?php _e('Fields marked with an asterisk (<span class="required-sign">*</span>) are required.', 'peepso-core'); ?></span>
				</div>
			</div> <!-- .clayout -->
		</section><!--end compnent-->
	</section><!--end mainbody-->
</div><!--end row-->

<?php $PeepSoProfile->after_edit_form(); ?>