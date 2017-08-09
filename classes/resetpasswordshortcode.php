<?php

class PeepSoResetPasswordShortcode {

    private static $_instance = NULL;

    public function __construct()
    {
        if (is_user_logged_in())
        {
            PeepSo::redirect(PeepSo::get_page('activity'));
        }
    }

    public static function get_instance()
    {
        if (NULL === self::$_instance)
            self::$_instance = new self();
        return (self::$_instance);
    }

    /*
     * Callback function for the Recover Password shortcode
     * @param array $atts Attributes array
     * @param string $content The content within the shortcode
     */

    public function do_shortcode($atts, $content = '')
    {
        PeepSo::set_current_shortcode('peepso_reset');
        $ret = PeepSoTemplate::get_before_markup();

        $attributes = array();
        $err = new WP_Error; 
        if ( isset( $_REQUEST['login'] ) && isset( $_REQUEST['key'] ) && 'POST' !== $_SERVER['REQUEST_METHOD']) {
            $attributes['login'] = $_REQUEST['login'];
            $attributes['key'] = $_REQUEST['key'];

            $user = check_password_reset_key( $attributes['key'], $attributes['login'] );
 
	        if ( ! $user || is_wp_error( $user ) ) {
	            if ( $user && $user->get_error_code() === 'expired_key' ) {
	                $err->add('expired_key', __('<strong>ERROR</strong>: The password reset link you used is not valid anymore.', 'peepso-core'));
	            } else {
	                $err->add('invalid_key', __('<strong>ERROR</strong>: The password reset link you used is not valid anymore.', 'peepso-core'));
	            }
	        }

       	} else {
       		$err = new WP_Error('bad_form', __('<strong>ERROR</strong>: Invalid password reset link.', 'peepso-core'));
       	}

        if ('POST' === $_SERVER['REQUEST_METHOD'] && isset($_POST['pass1']))
        {
            $res = wp_verify_nonce($_POST['-form-id'], 'peepso-reset-password-form');
            if (1 !== $res)
                $err = new WP_Error('bad_form', __('<strong>ERROR</strong>: Invalid form contents, please resubmit', 'peepso-core'));
            else
                $err = $this->reset_password();

            if (PeepSo::get_option('site_registration_recaptcha_enable', 0))
            {
                $input = new PeepSoInput();

                $postdata = http_build_query(
                        array(
                            'secret' => PeepSo::get_option('site_registration_recaptcha_secretkey', 0),
                            'response' => $input->val('g-recaptcha-response')
                        )
                );

                $opts = array('http' =>
                    array(
                        'method' => 'POST',
                        'header' => 'Content-type: application/x-www-form-urlencoded',
                        'content' => $postdata
                    )
                );

                $context = stream_context_create($opts);

                $result = json_decode(file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context));

                if ($result->success === FALSE)
                {
                    $err = new WP_Error('bad_form', __('Invalid captcha, please try again', 'peepso-core'));
                }
            }

            if (is_wp_error($err) && 'user_login_blocked' !== $err->get_error_code()) {
	            $ret .= PeepSoTemplate::exec_template('general', 'reset-password', array('attributes' => $attributes, 'error' => $err), TRUE);
	        } else {
	            $ret .= PeepSoTemplate::exec_template('general', 'reset-password-success', NULL, TRUE);
	        }
        } else {
        	$ret .= PeepSoTemplate::exec_template('general', 'reset-password', array('attributes' => $attributes, 'error' => $err), TRUE);
        }
        
        $ret .= PeepSoTemplate::get_after_markup();

        wp_reset_query();

        // disable WP comments from displaying on page
//        global $wp_query;
//        $wp_query->is_single = FALSE;
//        $wp_query->is_page = FALSE;

        return ($ret);
    }

    public function reset_password()
    {
    	$input = new PeepSoInput();
        $errors = new WP_Error();

        $rp_key = $input->val('rp_key');
        $rp_login = $input->val('rp_login');
 
        $user = check_password_reset_key( $rp_key, $rp_login );
 
        if ( ! $user || is_wp_error( $user ) ) {
            if ( $user && $user->get_error_code() === 'expired_key' ) {
                $errors->add('expired_key', __('<strong>ERROR</strong>: The password reset link you used is not valid anymore.', 'peepso-core'));
            } else {
                $errors->add('invalid_key', __('<strong>ERROR</strong>: The password reset link you used is not valid anymore.', 'peepso-core'));
            }
        }
 
 		$pass1 = $input->val('pass1', FALSE);
 		$pass2 = $input->val('pass2', FALSE);
        if ( $pass1 === FALSE ) {
        	$errors->add('bad_form', __('<strong>ERROR</strong>: Invalid request.', 'peepso-core'));
        }

    	if ( $pass1 != $pass2 ) {
            // Passwords don't match
            $errors->add('password_reset_missmatch', __('<strong>ERROR</strong>: The two passwords you entered don\'t match.', 'peepso-core'));
        }
 
 		if ($errors->get_error_code()) {
        	return ($errors);
 		}

        // Parameter checks OK, reset password
        reset_password( $user, $_POST['pass1'] );

    	return TRUE;
	    
    }

}

// EOF
