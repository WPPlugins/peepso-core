<?php
$PeepSoForm = PeepSoForm::get_instance();
$PeepSoRegister = PeepSoRegister::get_instance();
?>
<div class="peepso">
	<section id="mainbody" class="ps-page ps-page--register">

		<section id="component" role="article" class="clearfix">
			<div class="ps-page-register cRegister">
				<h4 class="ps-page-title"><?php _e('Register New User', 'peepso-core'); ?></h4>

				<?php do_action('peepso_before_registration_form');?>

				<div class="ps-register-form cprofile-edit">
					<?php if (!empty($error)) { ?>
						<div class="ps-alert ps-alert-danger"><?php _e('Error: ', 'peepso-core'); echo $error; ?></div>
					<?php } ?>
					<?php $PeepSoForm->render($PeepSoRegister->register_form()); ?>
				</div>
			</div><!--end cRegister-->
		</section><!--end component-->

		<?php
		do_action('peepso_after_registration_form');

		if( 1 === PeepSo::get_option('wsl_enable',0)) { ?>
		<div class="ps-widget--wsl">
		<?php
			add_filter( 'wsl_render_auth_widget_alter_provider_icon_markup', 'wsl_use_peepso_icons', 10, 3 );
			do_action( 'wordpress_social_login' );
			remove_filter( 'wsl_render_auth_widget_alter_provider_icon_markup', 'wsl_use_peepso_icons', 10 );
		?>
		</div>
		<?php } // EOF wsl_enable ?>

	</section><!--end mainbody-->
</div><!--end row-->

<script>

// show terms and condition dialog
function show_terms() {
	var inst = pswindow.show('<?php _e('Terms and Conditions', 'peepso-core'); ?>', peepsoregister.terms ),
		elem = inst.$container.find('.ps-dialog');

	elem.addClass('ps-dialog-full');
	ps_observer.add_filter('pswindow_close', function() {
		elem.removeClass('ps-dialog-full');
	}, 10, 1 );
}

</script>
