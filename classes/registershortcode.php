<?php

class PeepSoRegisterShortcode
{
	private static $_instance = NULL;

	private $_err_message = NULL;

	private $_form = NULL;

	public function __construct()
	{
		// if user already logged in, show "already logged in" message
		if (is_user_logged_in()) {
			if (is_user_logged_in()) {
				PeepSo::redirect(PeepSo::get_page('activity'));
			}
		}

		if (PeepSo::get_option('site_registration_enable_ssl'))
			redirect_https();

		add_action('wp_enqueue_scripts', array(&$this, 'enqueue_scripts'));
		add_filter('peepso_register_error', array(&$this, 'error_message'), 10, 1);

		if ('POST' === $_SERVER['REQUEST_METHOD']) {
			if (isset($_POST['submit-activate'])) {
				// submitted the activation code
				$this->activate_account();
			}
			else if (isset($_POST['submit-resend'])) {
				// submitted resend activation link
				$this->resend_activation();
			} else {
				if (FALSE !== $this->register_user()) {
					wp_redirect(PeepSo::get_page('register') . '?success');
				}
			}
		}
	}

	/*
	 * return singleton instance of teh plugin
	 */
	public static function get_instance()
	{
		if (self::$_instance === NULL)
			self::$_instance = new self();
		return (self::$_instance);
	}

	/*
	 * shortcode callback for the Registration Page
	 * @param array $atts Shortcode attributes
	 * @param string $content Contents of the shortcode
	 * @return string output of the shortcode
	 */
	public function do_shortcode($atts, $content)
	{
		PeepSo::set_current_shortcode('peepso_register');

		$data = array('error' => $this->_err_message);

		if(!isset($this->url) || !($this->url instanceof PeepSoUrlSegments)) {
            $this->url = PeepSoUrlSegments::get_instance();
        }

		$ret = PeepSoTemplate::get_before_markup();
		if (isset($_GET['peepso_activate'])) {
			$error = ('POST' === $_SERVER['REQUEST_METHOD']) ? array('error' => new WP_Error('error', $data['error'])) : array();
			$result = $this->activate_account();
			if ($result === FALSE)
				$ret .= PeepSoTemplate::exec_template('register', 'register-activate', $error, TRUE);
		} else if (isset($_GET['success']))
			$ret .= PeepSoTemplate::exec_template('register', 'register-complete', NULL, TRUE);
		else if (isset($_GET['verified']))
			$ret .= PeepSoTemplate::exec_template('register', 'register-verified', NULL, TRUE);
		else if (isset($_GET['resend'])) {
			if ('POST' === $_SERVER['REQUEST_METHOD']) {
				// check for any errors from call to resend_activation() in __construct()
				if (NULL === $this->_err_message)
					$ret .= PeepSoTemplate::exec_template('register', 'register-resent', NULL, TRUE);
				else
					$ret .= PeepSoTemplate::exec_template('register', 'register-resend', array('error' => new WP_Error('error', $this->_err_message)), TRUE);
			} else {
				$ret .= PeepSoTemplate::exec_template('register', 'register-resend', NULL, TRUE);
			}
		} else if ($this->url->get(1)) {
            ob_start();
            do_action('peepso_register_segment_' . $this->url->get(1), $this->url);
            $ret .= ob_get_clean();
        } else
			$ret .= PeepSoTemplate::exec_template('register', 'register', $data, TRUE);
		$ret .= PeepSoTemplate::get_after_markup();

		wp_reset_query();

		// disable WP comments from displaying on page
//		global $wp_query;
//		$wp_query->is_single = FALSE;
//		$wp_query->is_page = FALSE;

		return ($ret);
	}

	/*
	 * Performs registration operation
	 */
	private function register_user()
	{
		$input = new PeepSoInput();
		$sNonce = $input->val('-form-id'); // isset($_POST['-form-id']) ? $_POST['-form-id'] : '';
		if (wp_verify_nonce($sNonce, 'register-form')) {
			$u = PeepSoUser::get_instance(0);

			$uname = $input->val('username', '');
			$email = $input->val('email', '');
			$passw = $input->raw('password', '');
			$pass2 = $input->raw('password2', '');

			$task = $input->val('task');

			$register = PeepSoRegister::get_instance();
			$register_form = $register->register_form();
			$form = PeepSoForm::get_instance();
			$form->add_fields($register_form['fields']);
			$form->map_request();

			if (FALSE === $form->validate()) {
				$this->_err_message = __('Form contents are invalid.', 'peepso-core');
				return (FALSE);
			}

			// verify form contents
			if ('-register-save' != $task) {
				$this->_err_message = __('Form contents are invalid.', 'peepso-core');
				return (FALSE);
			}

			if (empty($uname) || empty($email) || empty($passw)) {
				$this->_err_message = __('Required form fields are missing.', 'peepso-core');
				return (FALSE);
			}

			if (!is_email($email)) {
				$this->_err_message = __('Please enter a valid email address.', 'peepso-core');
				return (FALSE);
			}

			$id = get_user_by('email', $email);
			if (FALSE !== $id) {
				$this->_err_message = __('That email address is already in use.', 'peepso-core');
				return (FALSE);
			}

			$id = get_user_by('login', $uname);
			if (FALSE !== $id) {
				$this->_err_message = __('That user name is already in use.', 'peepso-core');
				return (FALSE);
			}

			if ($passw != $pass2) {
				$this->_err_message = __('The passwords you submitted do not match.', 'peepso-core');
				return (FALSE);
			}

			// checking additional fields is include in registration page?.
			if(isset($register_form['fields']['extended_profile_fields'])) {
				$valid_ext_fields = apply_filters('peepso_register_valid_extended_fields', TRUE, $input);
				if( FALSE === $valid_ext_fields) {
					$this->_err_message = __('Additional fields are invalid.', 'peepso-core');
					return (FALSE);
				}
			}

			$wpuser = $u->create_user('', '', $uname, $email, $passw, '');
			do_action('peepso_register_new_user', $wpuser);
		} else {
			$this->_err_message = __('Incomplete form contents.', 'peepso-core');
			return (FALSE);
		}

		return (TRUE);
	}

	/*
	 * Resends the email activation link to new users
	 */
	private function resend_activation()
	{
		$input = new PeepSoInput();

		$err = NULL;
		$nonce = $input->val('-form-id');
		if (!wp_verify_nonce($nonce, 'resent-activation-form')) {
			$this->_err_message = __('Invalid form contents.', 'peepso-core');
			return (FALSE);
		}

		$email = sanitize_email($input->val('email'));
		if (!is_email($email)) {
			$this->_err_message = __('Please enter a valid email address', 'peepso-core');
			return (FALSE);
		}

		// verify form contents
		$task = $input->val('task');
		if ('-resend-activation' !== $task) {
			$this->_err_message = __('Invalid form contents.', 'peepso-core');
			return (FALSE);
		}

		// form is valid; look up user by email address
		$user = get_user_by('email', $email);
		if (FALSE !== $user) {
			// if it's a valid user - resend the email
			$u = PeepSoUser::get_instance($user->ID);
			$u->send_activation($email);
		}
		// if it's not a valid user, we don't want to act like there was a problem
	}

	/**
	 * Returns the error message
	 * @param  string $msg The error message, assigned to $this->_err_message
	 * @return string      The error message
	 */
	public function error_message($msg)
	{
		if (NULL !== $this->_err_message)
			$msg = $this->_err_message;
		return ($msg);
	}

	public function enqueue_scripts()
	{
		$data = array();

		if (1 === PeepSo::get_option('site_registration_enableterms', 0)) {
			$data['terms'] = nl2br(PeepSoSecurity::strip_content(PeepSo::get_option('site_registration_terms', '')));
		}

		wp_enqueue_style('peepso-datepicker');

		wp_register_script('peepso-register', PeepSo::get_asset('js/register.js'), array('jquery', 'underscore', 'peepso-form'), PeepSo::PLUGIN_VERSION, TRUE);
		wp_enqueue_script('peepso-register');

		//wp_register_script('validate', PeepSo::get_asset('js/validate-1.5.js'), array('jquery'), PeepSo::PLUGIN_VERSION, TRUE);
		//wp_enqueue_script('validate');
		wp_localize_script('peepso', 'peepsoregister', $data);
	}

	/**
	 * Changes the user's role to peepso_verified.
	 */
	public function activate_account()
	{
		global $wpdb;

		$input = new PeepSoInput();
		if (isset($_GET['peepso_activation_code']))
		{
			$key = $input->val('peepso_activation_code', NULL);
		} else {
			$key = $input->val('activate', NULL);
		}

		// Get user by meta
		if (NULL !== $key && !empty($key)) {
			$args = array(
				'fields' => 'ID',
				'meta_key' => 'peepso_activation_key',
				'meta_value' => $key,
				'number' => 1 // limit to 1 user
			);
			$user = new WP_User_Query($args);

			if (count($user->results) > 0) {
				$user = get_user_by('id', $user->results[0]);
				$wpuser = PeepSoUser::get_instance($user->ID);
				do_action('peepso_register_verified', $wpuser);
				if (PeepSo::get_option('site_registration_enableverification', '0')) {
					$wpuser->set_user_role('verified');
//					$user->set_role('peepso_verified');

					// send admin an email
					$args = array(
						'role' => 'administrator',
					);

					$user_query = new WP_User_Query($args);
					$users = $user_query->get_results();

					$adm_email = PeepSo::get_notification_emails();

					$data = array(
						'userlogin' => $wpuser->get_username(),
						'userfullname' => trim(strip_tags($wpuser->get_fullname())),
						'userfirstname' => $wpuser->get_firstname(),
						'permalink' => admin_url('users.php?s=' . $wpuser->get_email()),
					);

					$is_admin_email = FALSE;
					if (count($users) > 0) {
						foreach ($users as $user) {
							$email = $user->data->user_email;
							if ($email == $adm_email) {
								$is_admin_email = TRUE;
							}
							$data['useremail'] = $email;
							$data['currentuserfullname'] = PeepSoUser::get_instance(get_user_by('email', $email)->ID)->get_fullname();
							PeepSoMailQueue::add_message($user->ID, $data, __('{sitename} - New User Registration', 'peepso-core'), 'new_user_registration', 'new_user_registration',0,1);
						}
					}

					if (!$is_admin_email) {
						$data['useremail'] = $adm_email;
						$data['currentuserfullname'] = PeepSoUser::get_instance(get_user_by('email', $email)->ID)->get_fullname();
						PeepSoMailQueue::add_message(PeepSo::get_notification_user(), $data, __('{sitename} - New User Registration', 'peepso-core'), 'new_user_registration', 'new_user_registration',0,1);
					}

					wp_safe_redirect(PeepSo::get_page('register') . '?verified');
					exit();
				} else {
					$wpuser->set_user_role('member');
//					$user->set_role('peepso_member');

					wp_clear_auth_cookie();
				    wp_set_current_user($user->ID);
				    wp_set_auth_cookie($user->ID);
				}

				$redirect = PeepSo::get_page('redirectlogin');
				if (empty($redirect)) {
					$redirect = PeepSo::get_page('profile');
				}
				
				PeepSo::redirect($redirect);
			}
		}
		$this->_err_message = __('Please enter a valid activation code', 'peepso-core');
		return (FALSE);
	}
}

// EOF
