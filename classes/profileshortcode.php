<?php

class PeepSoProfileShortcode
{
    public $save_success = FALSE;

    private static $_instance = NULL;

    private $err_message = NULL;
    private $url;
    private $can_access = TRUE;
    private $user_blocked = FALSE;

    private $view_user_id = NULL;					// user id of profile to view
    private $form_message = NULL;					// error message used in forms

    private $preference_tabs = array(
        'edit',
        'pref',
        'notifications',
        'blocked',
    );

    const NONCE_NAME = 'profile-edit-form';

    public function __construct()
    {
        add_shortcode('peepso_profile', array(&$this, 'do_shortcode'));
        add_action('wp_enqueue_scripts', array(&$this, 'enqueue_scripts'));

        add_action('peepso_save_cover_form', array(&$this, 'save_cover_form'));
        add_action('peepso_save_profile_form', array(&$this, 'save_profile_form'));
        add_action('peepso_save_avatar_form', array(&$this, 'save_avatar_form'));
        add_action('peepso_activity_dialogs', array(&$this, 'upload_dialogs'));

        add_filter('peepso_page_title', array(&$this,'peepso_page_title'));
        add_filter('peepso_user_profile_id', array(&$this, 'user_profile_id'));

        add_action('peepso_profile_segment_about', array(&$this, 'peepso_profile_segment_about'));
        add_filter('peepso_profile_segment_menu_links', array(&$this, 'peepso_profile_segment_menu_links'));

		add_filter('peepso_page_title_check', array(&$this, 'peepso_page_title_check'));
		add_filter('peepso_permissions_post_create', array(&$this, 'filter_permissions_post_create'));
    }


    public function get_view_user_id()
    {
        return intval($this->view_user_id);
    }

    /*
     * return singleton instance of teh plugin
     */
    public static function get_instance()
    {
        PeepSoActivityShortcode::get_instance();					// need the Activity Stream
        if (NULL === self::$_instance)
            self::$_instance = new self();
        return (self::$_instance);
    }


    /*
     * Sets up the page for viewing. The combination of page and exta information
     * specifies which profile to view.
     * @param string $page The 'root' of the page, i.e. 'profile'
     * @param string $extra Optional specifier of extra data, i.e. 'username'
     */
    public function set_page($url)
    {
        if(!$url instanceof PeepSoUrlSegments) {
            return;
        }

        $this->url = $url;

        global $wp_query;

        if ($wp_query->is_404) {
            echo "<h1>404</h1>";
            # $virt = new PeepSoVirtualPage($this->url->get(0), $this->url->get(1));
        }

        if ($this->url->get(1)) {
            $user = get_user_by('slug', $this->url->get(1));

            if (FALSE === $user) {
                $this->view_user_id = get_current_user_id();
            } else {
                $this->view_user_id = $user->ID;
            }
        } else {
            $this->view_user_id = get_current_user_id();
        }

        if (0 === $this->view_user_id) {
            PeepSo::redirect(PeepSo::get_page('activity'));
        }

        $blk = new PeepSoBlockUsers();
        $user = PeepSoUser::get_instance($this->view_user_id);

        $this->user_blocked = $blk->is_user_blocking($this->view_user_id, get_current_user_id(), TRUE);
        $this->can_access = PeepSo::check_permissions($this->view_user_id, PeepSo::PERM_PROFILE_VIEW, get_current_user_id(), TRUE);

        $this->init();
    }

    /*
     * Filter for setting the user id of the page being viewed
     * @param int $id The assumed user id
     * @return int The modified user id, based on the profile page being viewed
     */
    public function user_profile_id($id)
    {
        // this uses the value set in the set_page() method
        if (FALSE !== $this->view_user_id)
            $id = $this->view_user_id;
        return ($id);
    }


    /*
     * shortcode callback for the Registration Page
     * @param array $atts Shortcode attributes
     * @param string $content Contents of the shortcode
     * @return string output of the shortcode
     */

    public function peepso_page_title( $title )
    {
        if( 'peepso_profile' == $title['title']) {
            $user = PeepSoUser::get_instance($this->get_view_user_id());
			$title['newtitle'] = $title['title'] = $user->get_fullname();
        }

        return $title;
    }

    public function do_shortcode($atts, $content)
    {
        echo "<!--shortcode";
        global $didshortcode;
        echo ++$didshortcode;
        echo "-->";

        PeepSo::set_current_shortcode('peepso_profile');
        $allow = apply_filters('peepso_access_content', TRUE, 'peepso_profile', PeepSo::MODULE_ID);
        if (!$allow) {
            echo apply_filters('peepso_access_message', NULL);
            return;
        }

        if(!isset($this->url) || !($this->url instanceof PeepSoUrlSegments)) {
            $this->url = PeepSoUrlSegments::get_instance();
        }

        $PeepSoProfile = PeepSoProfile::get_instance();
        $PeepSoProfile->init($this->view_user_id);

        // use get variables to determine exactly which profile template to run
        $ret = PeepSoTemplate::get_before_markup();

        if ($this->user_blocked)
            $ret .= '<div class="ps-alert ps-alert-danger">' . __('Sorry, you don\'t have permission to access the content of this page.', 'peepso-core') . '</div>';
        else if (FALSE === $this->can_access)
            $ret .= PeepSoTemplate::exec_template('profile', 'no-access', NULL, TRUE);
        else if (isset($_GET['blocked']))
            $ret .= PeepSoTemplate::exec_template('profile', 'profile-blocked', NULL, TRUE);
        else if (isset($_GET['edit'])) {
            $ret .= PeepSoTemplate::exec_template('profile', 'profile-edit', array('shortcode' => self::get_instance()), TRUE);
        }
        else if (isset($_GET['notifications'])) {
            $ret .= PeepSoTemplate::exec_template('profile', 'profile-notifications', NULL, TRUE);
        }
        else if (isset($_GET['pref'])) {
            $ret .= PeepSoTemplate::exec_template('profile', 'profile-preferences', NULL, TRUE);
        }
        else if ($this->url->get(2)) {
            ob_start();
            do_action('peepso_profile_segment_' . $this->url->get(2), $this->url);
            $ret .= ob_get_clean();

            wp_enqueue_script('peepso-focus-area', PeepSo::get_asset('js/focus-area.js'),
                array('peepso', 'peepso-avatar-dialog'), PeepSo::PLUGIN_VERSION, TRUE);

        } else {
            $ret .= PeepSoTemplate::exec_template('profile', 'profile', NULL, TRUE);

            wp_enqueue_script('peepso-focus-area', PeepSo::get_asset('js/focus-area.js'),
                array('peepso', 'peepso-avatar-dialog'), PeepSo::PLUGIN_VERSION, TRUE);

            if ($this->view_user_id !== get_current_user_id()) {
                $usr = PeepSoUser::get_instance(0);
                $usr->add_view_count($this->view_user_id);
            }
        }
        $ret .= PeepSoTemplate::get_after_markup();

        if ($PeepSoProfile->can_edit()) {
            wp_enqueue_style('peepso-datepicker');
            wp_enqueue_style('peepso-fileupload');

            wp_enqueue_script('peepso-fileupload');
        }

        PeepSo::reset_query();

        return ($ret);
    }

    /*
     * Init callback. Checks for post operations
     */
    public function init()
    {
        if ('POST' === $_SERVER['REQUEST_METHOD']) {
            if (isset($_GET['cover']))
                do_action('peepso_save_cover_form', $this->view_user_id);
            else if (isset($_GET['edit']))
                do_action('peepso_save_profile_form', $this->view_user_id);
            else if (isset($_GET['avatar']))
                do_action('peepso_save_avatar_form', $this->view_user_id);
        }
    }

    /*
     * Function called when saving cover photo
     */
    public function save_cover_form($id)
    {
        if (FALSE === PeepSo::check_permissions($this->view_user_id, PeepSo::PERM_PROFILE_EDIT, get_current_user_id())) {
            $this->err_message = __('You do not have enough permissions.', 'peepso-core');
            return (FALSE);
        } else {
            $input = new PeepSoInput();

            // verify a valid user id
            $user_id = $input->int('user_id');
            if (0 === $user_id)
                return (FALSE);

            if (isset($_FILES['filedata'])) {
                $allowed_mime_types = apply_filters(
                    'peepso_profiles_cover_mime_types',
                    array(
                        'image/jpeg',
                        'image/png'
                    )
                );

                if (!in_array($_FILES['filedata']['type'], $allowed_mime_types)) {
                    $this->err_message = __('The file type you uploaded is not allowed.', 'peepso-core');
                    return (FALSE);
                }

                if (empty($_FILES['filedata']['tmp_name'])) {
                    $this->err_message = __('The file you uploaded is either missing or too large.', 'peepso-core');
                    return (FALSE);
                }

                $user = PeepSoUser::get_instance($user_id);
                $user->move_cover_file($_FILES['filedata']['tmp_name']);
                return (TRUE);
            } else {
                $this->err_message = __('No file uploaded.', 'peepso-core');
                return (FALSE);
            }

        }
    }

    /*
     * Function called when saving avatar image
     *
     */
    public function save_avatar_form()
    {
        if (FALSE === PeepSo::check_permissions($this->view_user_id, PeepSo::PERM_PROFILE_EDIT, get_current_user_id())) {
            $this->err_message = __('You do not have enough permissions.', 'peepso-core');
            return (FALSE);
        } else {
            $input = new PeepSoInput();

            // verify a valid user id
            $user_id = $input->int('user_id');
            if (0 === $user_id) {
                $this->err_message = __('No user defined.', 'peepso-core');
                return (FALSE);
            }

            if (isset($_FILES['filedata'])) {
                $allowed_mime_types = apply_filters(
                    'peepso_profiles_avatar_mime_types',
                    array(
                        'image/jpeg',
                        'image/png'
                    )
                );

                if (empty($_FILES['filedata']['tmp_name'])) {
                    $this->err_message = __('The file you uploaded is either missing or too large.', 'peepso-core');
                    return (FALSE);
                }

                if (!in_array($_FILES['filedata']['type'], $allowed_mime_types)) {
                    $this->err_message = __('The file type you uploaded is not allowed.', 'peepso-core');
                    return (FALSE);
                }

                $user = PeepSoUser::get_instance($user_id);
                $user->move_avatar_file($_FILES['filedata']['tmp_name']);

                return (TRUE);
            } else {
                $this->err_message = __('No file uploaded.', 'peepso-core');
                return (FALSE);
            }
        }
    }

    /*
     * Function called when saving an Edit Profile form
     * @param int $id The user id for the form save operation
     */
    public function save_profile_form($id)
    {
        // check permissions
        if (PeepSo::check_permissions($this->view_user_id, PeepSo::PERM_PROFILE_EDIT, get_current_user_id())) {
            $input = new PeepSoInput();

            $nonce = $input->val('-form-id');
            if (FALSE === wp_verify_nonce($nonce, self::NONCE_NAME))
                return;

            // verify that authkey field is empty
            $authkey = $input->val('authkey');
            if (!empty($authkey))
                return;

            // verify a valid user id
            $user_id = $input->int('user_id');
            if (0 === $user_id) {
				return;
			}

            $PeepSoProfile = PeepSoProfile::get_instance();
            $PeepSoProfile->init($user_id);

            $edit_form = apply_filters('peepso_profile_edit_form_fields', array(), $user_id);

            add_filter('peepso_form_validate_after', array(&$PeepSoProfile, 'change_password_validate_after'), 10, 2);

            $form = PeepSoForm::get_instance();
            $form->add_fields($edit_form);
            $form->map_request();

            if (!$form->validate())
                return (FALSE);

            // create a user instance for this user
            $user = PeepSoUser::get_instance($user_id);

            // update password
            $change_password = $input->raw('change_password');

            if (!empty($change_password)) {
				wp_set_password($change_password, $user_id);
			}

            // update the WordPress user information
            global $wpdb;
            $new_username = $input->val('user_nicename');
            $old_username =  $user->get_username();

            $username_changed = FALSE;
            if( $new_username != $old_username ) {
                $username_changed = TRUE;
            }

            // TODO: need to check if $new_username is already used and not allow the change - `user_login` does not have a UNIQUE index!
            // TODO: the check for and updating of the `user_login` need to be within a transaction to ensure no duplicate names
            $data_user = array('user_login' => $new_username);
            $ret_update_user = $wpdb->update($wpdb->users, $data_user, array('ID' => $user_id));
            // TODO: don't return, allow the rest of the form contents to be updated and return a form validation error message
            // TODO: $wpdb->update() returns the number of rows updated (1 in this case) or FALSE on error -- not a WP_Error instance

            $data = array('ID' => $user_id);
            $props = array('first_name', 'last_name', 'user_url', 'description');
            foreach ($props as $prop) {
                if (isset($_POST[$prop]))
                    $data[$prop] = $input->raw($prop);
            }
            $data['display_name'] = $input->val('first_name') . ' ' . $input->val('last_name');
            $data['nickname']     = $new_username;
            $data['user_nicename']= $new_username;
			$data['user_email']	  = $input->val('user_email');
            $ret = wp_update_user($data);

            if (!is_wp_error($ret)) {

                do_action('peepso_profile_after_save', $user_id);

                if( TRUE === $username_changed ) {
                    PeepSo::redirect(PeepSo::get_page('activity'));
                    die();
                }

                $PeepSoProfile->edit_form_message(__('Changes successfully saved.', 'peepso-core'));
            }

            remove_filter('peepso_form_validate_after', array(&$PeepSoProfile, 'change_password_validate_after'), 10);
        }
    }

    /*
     * Enqueues needed css and javascript files for the Profile page
     */
    public function enqueue_scripts()
    {
        $input = new PeepSoInput();

        // profile management
        wp_enqueue_script('peepso-profile', PeepSo::get_asset('js/profile.js'), array('peepso'), PeepSo::PLUGIN_VERSION, TRUE);
        add_filter('peepso_data', function( $data ) {
            $id = $this->get_view_user_id();
            $PeepSoUser = PeepSoUser::get_instance($id);

            $data['profile'] = array(
                'id' => $id,
                'username'    =>    $PeepSoUser->get_username(),
                'name'        =>    $PeepSoUser->get_fullname(),
                'hasAvatar'   =>    $PeepSoUser->has_avatar(),
                'imgAvatar'   =>    $PeepSoUser->get_avatar('full'),
                'imgOriginal' =>    $PeepSoUser->get_avatar('orig'),
            );
            return $data;
        }, 10, 1);

        $load = array(
            'peepso-window' => 'js/window-1.0.js~jquery',
            'peepso-form' => 'js/form.min.js~jquery,peepso-profile,peepso-datepicker',
            'peepso-dropdown' => 'js/dropdown.js~jquery,peepso-profile',
        );

        if (PeepSo::is_admin() || get_current_user_id() == $this->view_user_id) {
            $load['peepso-crop'] = 'js/crop.js~jquery,peepso-hammer';
            $load['peepso-profileavatar'] = 'js/profile-edit.js~jquery,peepso-crop';
        }

        if ($input->exists('notifications'))
            $load['peepso-profile-notification'] = 'js/profile-notification.js~jquery,peepso';
        if ($input->exists('blocked'))
            $load['peepso-blocks'] = 'js/profile-blocks.js~jquery,peepso';
        if ($input->exists('alerts'))
            $load['peepso-profile-alerts'] = 'js/profile-alerts.js~jquery,peepso';

        wp_enqueue_script('jquery-ui-widget');
        wp_enqueue_script('jquery-ui-draggable');
        foreach ($load as $handle => $data) {
            $parts = explode('~', $data, 2);
            $deps = explode(',', $parts[1]);

            wp_register_script($handle, PeepSo::get_asset($parts[0]), $deps, PeepSo::PLUGIN_VERSION, TRUE);
            wp_enqueue_script($handle);
        }

        wp_register_script('peepso-resize', PeepSo::get_asset('js/jquery.autosize.min.js'),
            array('jquery'), PeepSoActivityStream::PLUGIN_VERSION, TRUE);

        wp_enqueue_script('peepso-posttabs');

        wp_register_style('peepso-datepicker', PeepSo::get_asset('css/datepicker.css'), NULL, PeepSo::PLUGIN_VERSION, 'all');
    }


    /**
     * Returns TRUE of FALSE whether an err_message has been set.
     * @return boolean
     */
    public function has_error()
    {
        return (NULL !== $this->err_message);
    }


    /**
     * Returns the error message as a string.
     * @return string The error message.
     */
    public function get_error_message()
    {
        return ($this->err_message);
    }

    /**
     * callback - peepso_activity_dialogs
     * Renders the dialog boxes for uploading profile and cover photo.
     */
    public function upload_dialogs()
    {
        wp_enqueue_style('peepso-fileupload');
        PeepSoTemplate::exec_template('profile', 'dialog-profile-avatar');
        PeepSoTemplate::exec_template('profile', 'dialog-profile-cover');
    }

    public function peepso_profile_segment_about()
    {
        echo PeepSoTemplate::exec_template('profile', 'profile-about');
    }

    public function peepso_profile_segment_menu_links($links)
    {
        $links[2][] = array(
            'href' => 'about',
            'title'=> __('About', 'peepso-core'),
            'id' => 'about',
            'icon' => 'pencil'
        );

        ksort($links);
        return $links;
    }

	public function peepso_page_title_check($post) {
		if (isset($post->post_content) && strpos($post->post_content, '[peepso_profile]') !== FALSE) {
			return TRUE;
		}
		return $post;
	}
	
	/**
	 * todo:docblock
	 */
	public function filter_permissions_post_create($acc) {
		$PeepSoProfile = PeepSoProfile::get_instance();

		if (!$PeepSoProfile->is_current_user()) {
			if (PeepSoUser::is_accessible_static($PeepSoProfile->user->get_profile_post_accessibility(), $this->get_view_user_id())) {
				$acc = TRUE;
			} else {
				$acc = FALSE;
			}
		}
		
		return $acc;
	}
}

// EOF
