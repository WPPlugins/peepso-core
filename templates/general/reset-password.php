<div class="peepso">
    <section id="mainbody" class="ps-page">
        <section id="component" role="article" class="clearfix">
            <div id="peepso" class="on-socialize ltr cRegister">
            	<?php 
            	if(isset($error) && !in_array($error->get_error_code(), array('bad_form', 'expired_key', 'invalid_key'))) {
				?>
                <h4><?php _e('Pick a New Password', 'peepso-core'); ?></h4>
                <?php } ?>

                <div class="ps-register-recover">

                    <?php
                    if (isset($error) && !empty($error)) {
                        PeepSoGeneral::get_instance()->show_error($error);
                    }

                    if(isset($error) && !in_array($error->get_error_code(), array('bad_form', 'expired_key', 'invalid_key'))) {
                    ?>
                    <form id="recoverpasswordform" name="recoverpasswordform" action="<?php PeepSo::get_page('recover'); ?>?submit" method="post" class="ps-form">
                    	<input type="hidden" id="user_login" name="rp_login" value="<?php echo esc_attr( $attributes['login'] ); ?>" autocomplete="off" />
        				<input type="hidden" name="rp_key" value="<?php echo esc_attr( $attributes['key'] ); ?>" />
                        <input type="hidden" name="task" value="-reset-password" />
                        <input type="hidden" name="-form-id" value="<?php echo wp_create_nonce('peepso-reset-password-form'); ?>" />
                        <div class="ps-form-row">
                            <div class="ps-form-group">
                                <label for="email" class="ps-form-label"><?php _e('New Password:', 'peepso-core'); ?>
                                    <span class="required-sign">&nbsp;*<span></span></span>
                                </label>
                                <div class="ps-form-field">
                                    <input class="ps-input" type="password" name="pass1" placeholder="<?php _e('New Password', 'peepso-core'); ?>" />
                                </div>
                            </div>

                            <div class="ps-form-group">
                                <label for="email" class="ps-form-label"><?php _e('Repeat new password:', 'peepso-core'); ?>
                                    <span class="required-sign">&nbsp;*<span></span></span>
                                </label>
                                <div class="ps-form-field">
                                    <input class="ps-input" type="password" name="pass2" placeholder="<?php _e('Repeat new password', 'peepso-core'); ?>" />
                                </div>
                            
                            </div>

                            <div class="ps-form-group">
                                <label class="ps-form-label"></label>
                                <div class="ps-form-field">
                                    <?php
                                    if (PeepSo::get_option('site_registration_recaptcha_enable', 0))
                                    {
                                        echo '<div class="g-recaptcha" data-sitekey="' . PeepSo::get_option('site_registration_recaptcha_sitekey', 0) . '"></div>';
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="ps-form-group submitel">
                                <input type="submit" name="submit-recover" class="ps-btn ps-btn-primary" value="<?php _e('Submit', 'peepso-core'); ?>" />
                            </div>
                        </div>
                    </form>
                    <div class="ps-gap"></div>
                    <p class="description"><?php echo wp_get_password_hint(); ?></p>
                    <?php
                    }
                    ?>

                    <div class="ps-gap"></div>
                    <a href="<?php echo get_bloginfo('wpurl'); ?>"><?php _e('Back to Home', 'peepso-core'); ?></a>
                </div>
            </div><!--end peepso-->
        </section><!--end component-->
    </section><!--end mainbody-->
</div><!--end row-->
