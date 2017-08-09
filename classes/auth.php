<?php

class PeepSoAuth extends PeepSoAjaxCallback
{
	/**
	 * Called from PeepSoAjaxHandler
	 * Declare methods that don't need auth to run
	 * @return array
	 */
	public function ajax_auth_exceptions()
	{
		return array(
			'login',
		);
	}

	/**
	 * Handles AJAX login requests.
	 * @param  PeepSoAjaxResponse $resp
	 */
	public function login(PeepSoAjaxResponse $resp)
	{
		check_ajax_referer('ajax-login-nonce', 'security');

	    $info = array();
	    $info['user_login'] = $this->_input->val('username');
	    $info['user_password'] = $this->_input->raw('password');
	    $info['remember'] = $this->_input->int('remember', 0) ? TRUE : FALSE;

		$secure_cookie = NULL;

		// If the user wants ssl but the session is not ssl, force a secure cookie.
		if ( ! force_ssl_admin() ) {
			$user = is_email( $info['user_login'] ) ? get_user_by( 'email', $info['user_login'] ) : get_user_by( 'login', sanitize_user( $info['user_login'] ) );

			if ( $user && get_user_option( 'use_ssl', $user->ID ) ) {
				$secure_cookie = TRUE;
				force_ssl_admin( TRUE );
			}
		}

		if ( force_ssl_admin() ) {
			$secure_cookie = TRUE;
		}

		if ( is_null( $secure_cookie ) && force_ssl_admin() ) {
			$secure_cookie = TRUE;
		}

	    $user_signon = wp_signon($info, $secure_cookie);
	    if (is_wp_error($user_signon)){
	    	$resp->success(FALSE);
	    	$resp->set('dialog_title', __('Login Error', 'peepso-core'));

	    	if (empty($info['user_login']) && empty($info['user_password']))
	    		$resp->error(__('Username and password required.', 'peepso-core'));
			else {
				$msg = $user_signon->get_error_message();
				$pattern = "/(?<=href=(\"|'))[^\"']+(?=(\"|'))/";
				$msg = preg_replace($pattern, PeepSo::get_page('recover'), $msg);
				$resp->error($msg);
				return (FALSE);
			}
	    } else {
	        $resp->success(TRUE);
	    }
	}
}

// EOF
