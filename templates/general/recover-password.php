<div class="peepso">
    <section id="mainbody" class="ps-page">
        <section id="component" role="article" class="clearfix">
            <div id="peepso" class="on-socialize ltr cRegister">
                <h4><?php _e('Recover Password', 'peepso-core'); ?></h4>

                <div class="ps-register-recover">
                    <p>
                        <?php _e('Please enter the email address for your account. A verification code will be sent to you. Once you have received the verification code, you will be able to choose a new password for your account.', 'peepso-core'); ?>
                    </p>
                    <div class="ps-gap"></div>
                    <?php
                    if (isset($error)) {
                        PeepSoGeneral::get_instance()->show_error($error);
                    }
                    ?>
                    <form id="recoverpasswordform" name="recoverpasswordform" action="<?php PeepSo::get_page('recover'); ?>?submit" method="post" class="ps-form">
                        <input type="hidden" name="task" value="-recover-password" />
                        <input type="hidden" name="-form-id" value="<?php echo wp_create_nonce('peepso-recover-password-form'); ?>" />
                        <div class="ps-form-row">
                            <div class="ps-form-group">
                                <label for="email" class="ps-form-label"><?php _e('Email Address:', 'peepso-core'); ?>
                                    <span class="required-sign">&nbsp;*<span></span></span>
                                </label>
                                <div class="ps-form-field">
                                    <input class="ps-input" type="text" name="email" placeholder="<?php _e('Email address', 'peepso-core'); ?>" />
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
                    <a href="<?php echo get_bloginfo('wpurl'); ?>"><?php _e('Back to Home', 'peepso-core'); ?></a>
                </div>
            </div><!--end peepso-->
        </section><!--end component-->
    </section><!--end mainbody-->
</div><!--end row-->
