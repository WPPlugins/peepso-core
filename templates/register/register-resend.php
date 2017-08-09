<div class="peepso">
	<section id="mainbody" class="ps-page">
		<section id="component" role="article" class="clearfix">
			<div id="peepso" class="ps-register-resend">
				<h4><?php _e('Resend Activation Code', 'peepso-core'); ?></h4>

				<div class="ps-register-success">
					<p>
						<?php _e('Please enter your registered e-mail address here so that we can resend you the activation link.', 'peepso-core'); ?>
					</p>
					<div class="ps-gap"></div>
					<?php

					if (isset($error)) {
						PeepSoGeneral::get_instance()->show_error($error);
					}
					?>
					<form class="ps-form" name="resend-activation" action="<?php PeepSo::get_page('register'); ?>?resend" method="post">
						<input type="hidden" name="task" value="-resend-activation" />
						<input type="hidden" name="-form-id" value="<?php echo wp_create_nonce('resent-activation-form'); ?>" />
						<div class="ps-form-row">
							<div class="ps-form-group">
								<label for="email" class="form-label"><?php _e('Email Address', 'peepso-core'); ?>
									<span class="required-sign">&nbsp;*<span></span></span>
								</label>
								<div class="ps-form-field">
									<input class="ps-input" type="text" name="email" id="email" placeholder="<?php _e('Email address', 'peepso-core'); ?>" />
								</div>
							</div>
							<div class="ps-form-group submitel">
								<div class="ps-form-field">
									<input type="submit" name="submit-resend" class="ps-btn ps-btn-primary" value="<?php _e('Submit', 'peepso-core'); ?>" />
								</div>
							</div>
						</div>
					</form>
					<div class="ps-gap"></div>
					<a href="<?php echo get_bloginfo('wpurl'); ?>"><?php _e('Back to Home', 'peepso-core'); ?></a>
				</div>
			</div><!--end peepso-->
		</section><!--end component-->
	</section><!--end mainbody-->
</div><!--end row-->
