<div class="peepso">
	<section id="mainbody" class="ps-page">
		<section id="component" role="article" class="clearfix">
			<div id="peepso" class="ps-page-register cRegister">
				<h4 class="ps-page-title"><?php _e('Account Activation', 'peepso-core'); ?></h4>

				<div class="ps-register-success cRegister-Success">
					<p>
						<?php _e('Please enter your activation code below to enable your account.', 'peepso-core'); ?>
					</p>
					<?php
					if (isset($error)) {
						PeepSoGeneral::get_instance()->show_error($error);
					}
					?>
					<form class="ps-form" name="resend-activation" action="<?php PeepSo::get_page('register'); ?>?activate" method="post">
						<div class="ps-form-row">
							<div class="ps-form-group">
								<label for="activation" class="form-label"><?php _e('Activation Code:', 'peepso-core'); ?>
									<span class="required-sign">&nbsp;*<span></span></span>
								</label>
								<div class="ps-form-field">
									<?php
										$input = new PeepSoInput();
										$value = $input->val('peepso_activation_code', '');
									?>
									<input type="text" name="activate" class="ps-input" value="<?php echo $value; ?>" placeholder="<?php _e('Activation code', 'peepso-core'); ?>" />
								</div>
							</div>
							<div class="ps-form-group submitel">
								<div class="ps-form-field">
									<input type="submit" name="submit-activate" class="ps-btn ps-btn-primary" value="<?php _e('Submit', 'peepso-core'); ?>" />
								</div>
							</div>
						</ul>
					</form>
					<div class="ps-gap"></div>
					<a href="<?php echo get_bloginfo('wpurl'); ?>"><?php _e('Back to Home', 'peepso-core'); ?></a>
				</div>
			</div><!--end peepso-->
		</section><!--end component-->
	</section><!--end mainbody-->
</div><!--end row-->
