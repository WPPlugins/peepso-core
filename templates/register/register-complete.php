<div class="peepso">
	<section id="mainbody" class="ps-page">
		<section id="component" role="article" class="clearfix">
			<div id="peepso" class="ps-register-complete">
				<h4><?php _e('User Registered', 'peepso-core'); ?></h4>

				<div class="ps-register-success">
					<p>
						<?php 
							if (PeepSo::get_option('site_registration_enableverification', '0'))
								_e('Please check your email account and confirm your registration. Once that\'s done, Administrator will be notified that your account has been created and is awaiting approval. Until the site administrator approves your account, you will not be able to login. Once your account has been approved, you will receive a notification email.', 'peepso-core'); 
							else
								_e('Your account has been created. An activation link has been sent to the email address you provided, click on the link to logon to your account.', 'peepso-core'); 
						?>
					</p>
					<a href="<?php echo get_bloginfo('wpurl');?>" class="ps-btn ps-btn-primary"><?php _e('Back to Home', 'peepso-core'); ?></a>
				</div>
			</div><!--end peepso-->
		</section><!--end component-->
	</section><!--end mainbody-->
</div><!--end row-->
