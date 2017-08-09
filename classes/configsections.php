<?php

class PeepSoConfigSections extends PeepSoConfigSectionAbstract
{
	const SITE_ALERTS_SECTION = 'site_alerts_';

	public function register_config_groups()
	{
		$this->set_context('left');
		$this->_group_activity();
		$this->_group_registration();
		$this->_group_reporting();

		$this->set_context('right');
        $this->_group_license();
        $this->_group_integrations();

    }


	private function _group_reporting()
	{
		// # Enable Reporting
		$this->args('children',array('site_reporting_types'));
		$this->set_field(
			'site_reporting_enable',
			__('Enable Reporting', 'peepso-core'),
			'yesno_switch'
		);

		// # Predefined  Text
		$this->args('raw', TRUE);
		$this->args('multiple', TRUE);

		$this->set_field(
			'site_reporting_types',
			__('Predefined Text (Separated by a New Line)', 'peepso-core'),
			'textarea'
		);

		// # Build  Group
		$this->set_group(
			'report',
			__('Reporting', 'peepso-core'),
			__('These settings are used to control users\' ability to report inappropriate content.', 'peepso-core')
		);
	}


	private function _group_registration()
	{
		/** GENERAL **/
		// Enable Account Verification
		$this->set_field(
			'site_registration_enableverification',
			__('Admin Account Verification', 'peepso-core'),
			'yesno_switch'
		);

		//$summary = __('Setting "Enable Account Verification" to YES will send verification emails to new users when they Register. An Administrator will then need to approve the user before they can use the site. On approval, users will receive another email letting them know they can use the site.<br />Setting "Enable Account Verification" to NO, users will be automatically validated upon registration and can use the site immediately.', 'peepso-core');
		$summary_1 		= __('Users always must confirm their email address.', 'peepso-core');
		$summary_no 	= __('With the "Admin Account Verification" set to: NO, users register, confirm their email address and can immediately participate in your community.', 'peepso-core');
		$summary_yes 	= __('With the "Admin Account Verification" set to: YES, users register, confirm their email address and must be accepted by an Admin to be able to participate in your community. Users are notified by email when they\'re approved.', 'peepso-core');
		$summary = "$summary_1<br><br>$summary_no<br><br>$summary_yes";
		$this->set_field(
				'site_registration_enableverification_description',
				$summary,
				'message'
		);

		// # Force profile completion
		$this->set_field(
			'force_required_profile_fields',
			__('Force Profile Completion', 'peepso-core'),
			'yesno_switch'
		);

		$summary = __('Switching this setting on will force user profile completion. This means, that user will have to fill in all required fields before being able to participate in the community.', 'peepso-core');
		$this->set_field(
			'force_required_profile_fields_description',
			$summary,
			'message'
		);

        // # Redirect Successful Logins

        $args = array(
            'sort_order' => 'asc',
            'sort_column' => 'post_title',
            'hierarchical' => 1,
            'exclude' => '',
            'include' => '',
            'meta_key' => '',
            'meta_value' => '',
            'authors' => '',
            'child_of' => 0,
            'parent' => -1,
            'exclude_tree' => '',
            'number' => '',
            'offset' => 0,
            'post_type' => 'page',
            'post_status' => 'publish'
        );
        $pages = get_pages($args);
        $options = array(0 => __('(no redirect)', 'peepso-core'));

        $pageredirect = PeepSo::get_option('site_frontpage_redirectlogin');
        $settings = PeepSoConfigSettings::get_instance();
        foreach ($pages as $page) {
            // handling selected old value (activity/profile)
            if($page->post_name == $pageredirect) {
                //$this->args('default', $page->ID);
                // update option to selected ID
                $settings->set_option('site_frontpage_redirectlogin', $page->ID);
            }

            $options[$page->ID] = ($page->post_parent > 0 ? '&nbsp;&nbsp;' : '') . $page->post_title;
        }

        $this->args('options', $options);

        $this->set_field(
            'site_frontpage_redirectlogin',
            __('Log-in redirect', 'peepso-core'),
            'select'
        );

        // # Redirect Logout

        $args = array(
            'sort_order' => 'asc',
            'sort_column' => 'post_title',
            'hierarchical' => 1,
            'exclude' => '',
            'include' => '',
            'meta_key' => '',
            'meta_value' => '',
            'authors' => '',
            'child_of' => 0,
            'parent' => -1,
            'exclude_tree' => '',
            'number' => '',
            'offset' => 0,
            'post_type' => 'page',
            'post_status' => 'publish'
        );
        $pages = get_pages($args);
        $options = array(0 => __('(no redirect)', 'peepso-core'));

        $pageredirect = PeepSo::get_option('logout_redirect');
        $settings = PeepSoConfigSettings::get_instance();
        foreach ($pages as $page) {
            // handling selected old value (activity/profile)
            if($page->post_name == $pageredirect) {
                //$this->args('default', $page->ID);
                // update option to selected ID
                $settings->set_option('logout_redirect', $page->ID);
            }

            $options[$page->ID] = ($page->post_parent > 0 ? '&nbsp;&nbsp;' : '') . $page->post_title;
        }

        $this->args('options', $options);

        $this->set_field(
            'logout_redirect',
            __('Log-out redirect', 'peepso-core'),
            'select'
        );



		// Enable Secure Mode For Registration
		$this->set_field(
				'site_registration_enable_ssl',
				__('Force SSL on Registration Page', 'peepso-core'),
				'yesno_switch'
		);

		$this->set_field(
				'site_registration_enable_ssl_description',
				__('Requires a valid SSL certificate. Enabling this option without a valid certificate might break your site.', 'peepso-core'),
				'message'
		);


		/** RECAPTCHA **/
		// # Separator Recaptcha
		$this->set_field(
			'separator_recaptcha',
			__('ReCaptcha', 'peepso-core'),
			'separator'
		);

		// # Enable ReCaptcha
		$this->set_field(
			'site_registration_recaptcha_enable',
			__('Enable ReCaptcha', 'peepso-core'),
			'yesno_switch'
		);

		// # ReCaptcha Site Key
		$this->set_field(
			'site_registration_recaptcha_sitekey',
			__('Site Key', 'peepso-core'),
			'text'
		);

		// # ReCaptcha Secret Key
		$this->set_field(
			'site_registration_recaptcha_secretkey',
			__('Secret Key', 'peepso-core'),
			'text'
		);

		// # Message ReCaptcha Description
		$this->set_field(
			'site_registration_recaptcha_description',
			__('Google ReCaptcha is a great way to keep spamming bots away from your website.<br><strong>Get ReCaptcha keys <a href="https://www.google.com/recaptcha/" target="_blank">here</a></strong>.', 'peepso-core'),
			'message'
		);

		/** T&C **/

		// # Separator Terms & Conditions
		$this->set_field(
			'separator_terms',
			__('Terms & Conditions', 'peepso-core'),
			'separator'
		);

		// # Enable Terms & Conditions
		$this->set_field(
			'site_registration_enableterms',
			__('Enable Terms &amp; Conditions', 'peepso-core'),
			'yesno_switch'
		);

		// # Terms & Conditions Text
		$this->args('raw', TRUE);

		$this->set_field(
			'site_registration_terms',
			__('Terms &amp; Conditions', 'peepso-core'),
			'textarea'
		);

		// Build Group

		#$this->args('summary', $summary);

		$this->set_group(
			'registration',
			__('Registration', 'peepso-core'),
			__('These settings allow you to customize the Registration process.', 'peepso-core')
		);
	}

	private function _group_integrations()
    {
        /** WORDPRESS SOCIAL LOGIN**/

        // # Separator WSL
        $this->set_field(
            'separator_wsl',
            __('WordPress Social Login', 'peepso-core'),
            'separator'
        );

        $wsl =' <a href="plugin-install.php?tab=plugin-information&plugin=wordpress-social-login&TB_iframe=true&width=750&height=500" class="thickbox">Wordpress Social Login</a> ';

        // # message WSL
        $this->set_field(
            'message_wsl',
            sprintf(__('Requires %s to be installed and properly configured.', 'peepso-core'), $wsl),
            'message'
        );

        if( defined('WORDPRESS_SOCIAL_LOGIN_ABS_PATH') ) {
            // # Enable WSL
            $this->set_field(
                'wsl_enable',
                __('Enable WordPress Social Login', 'peepso-core'),
                'yesno_switch'
            );
        } else {
            $this->set_field(
                'message_wsl_missing',
                sprintf(__('%s not found! Please install the plugin to see the configuration setting.', 'peepso-core'), $wsl),
                'message'
            );
        }

        /** WORDPRESS SOCIAL INVITES */

        // # Separator WSI
        $this->set_field(
            'separator_wsi',
            __('WordPress Social Invites', 'peepso-core'),
            'separator'
        );

        $wsi =' <a href="http://peep.so/wsi" target="_blank">Wordpress Social Invites</a> ';

        // # message WSL
        $this->set_field(
            'message_wsi',
            sprintf(__('Requires %s to be installed and properly configured.', 'peepso-core'), $wsi),
            'message'
        );

        if( class_exists('Wsi_Public') ) {
            // # Enable WSI
            $this->set_field(
                'wsi_enable_members',
                __('Show WSI on Members Page', 'peepso-core'),
                'yesno_switch'
            );
        } else {
            $this->set_field(
                'message_wsi_missing',
                sprintf(__('%s not found! Please install the plugin to see the configuration setting.', 'peepso-core'), $wsi),
                'message'
            );
        }

        $this->set_group(
            'integrations',
            __('Core Integrations', 'peepso-core'),
            __('Settings for built-in integrations between PeepSo Core and third party plugins.', 'peepso-core')
        );
    }

	private function _group_activity()
	{
		// # Separator Callout
		$this->set_field(
				'separator_general',
				__('General', 'peepso-core'),
				'separator'
		);

		$options = apply_filters('peepso_default_stream_options', array());

		#$options[666]  = 'Groups feed';

		if(count($options) > 1 ) {

			$this->args('options', $options);
			$this->set_field(
				'default_stream',
				__('Default stream content', 'peepso-core'),
				'select'
			);
		}

		// # Maximum size of Post
		$this->args('validation', array('required', 'numeric'));
		$this->args('data', array('min'=>100,'max'=>300));
		$this->args('int', TRUE);

		$this->set_field(
			'site_status_limit',
			__('Maximum size of Post', 'peepso-core'),
			'text'
		);

		// # Open Links In New Tab
		$this->set_field(
				'site_activity_open_links_in_new_tab',
				__('Open links in new tab', 'peepso-core'),
				'yesno_switch'
		);

		// # Hide Activity Stream From Guests
		$this->set_field(
				'site_activity_hide_stream_from_guest',
				__('Hide Activity Stream from Non-logged in Users', 'peepso-core'),
				'yesno_switch'
		);

		// # Enable Repost
		$this->set_field(
			'site_repost_enable',
			__('Enable Repost', 'peepso-core'),
			'yesno_switch'
		);

		$stream_config = apply_filters('peepso_activity_stream_config', array());

		if(count($stream_config) > 0 ) {

			foreach ($stream_config as $option) {
				if(isset($option['descript'])) {
					$this->args('descript', $option['descript']);
				}
				if(isset($option['int'])) {
					$this->args('int', $option['int']);
				}
				if(isset($option['default'])) {
					$this->args('default', $option['default']);
				}

				$this->set_field($option['name'], $option['label'], $option['type']);
			}
		}

		// # Separator Comments
		$this->set_field(
				'separator_comments',
				__('Comments', 'peepso-core'),
				'separator'
		);

		// # Number Of Comments To Display
		$this->args('validation', array('required', 'numeric'));

		$this->set_field(
			'site_activity_comments',
			__('Number of Comments to display', 'peepso-core'),
			'text'
		);

		// # Limit Number Of Comments Per Post
		$this->args('descript', __('Select "No" for unlimited comments', 'peepso-core'));

		$this->set_field(
			'site_activity_limit_comments',
			__('Limit Number of Comments per Post', 'peepso-core'),
			'yesno_switch'
		);

		// # Maximum Number Of Comments Allowed Per Post
		$this->args('validation', array('required', 'numeric'));
		$this->args('int', TRUE);
		$this->set_field(
			'site_activity_comments_allowed',
			__('Maximum number of Comments allowed per post', 'peepso-core'),
			'text'
		);

		// # 1055 Show comments in batches
		$this->args('validation', array('required', 'numeric'));
		$this->args('int', TRUE);
		$this->set_field(
			'activity_comments_batch',
			__('Show X more comments', 'peepso-core'),
			'text'
		);

		/* READMORE */

		// # Separator Readmore
		$this->set_field(
				'separator_readmore',
				__('Read more', 'peepso-core'),
				'separator'
		);

		// # Show Read More After N Characters
		$this->args('default', 1000);
		$this->args('validation', array('required', 'numeric'));

		$this->set_field(
			'site_activity_readmore',
			__("Show 'read more' after: [n] characters", 'peepso-core'),
			'text'
		);


		// # Redirect To Single Post View
		$this->args('default', 2000);
		$this->args('validation', array('required', 'numeric'));

		$this->set_field(
			'site_activity_readmore_single',
			__('Redirect to single post view when post is longer than: [n] characters', 'peepso-core'),
			'text'
		);

		// # Separator Profile
		$this->set_field(
				'separator_profile',
				__('Profile Posts', 'peepso-core'),
				'separator'
		);

		// # Who can post on "my profile" page
		$privacy = PeepSoPrivacy::get_instance();
		$privacy_settings = apply_filters('peepso_privacy_access_levels', $privacy->get_access_settings());

		$options = array();

		foreach($privacy_settings as $key => $value) {
			$options[$key] = $value['label'];
		}

		// Remove site guests & rename "only me"
		unset($options[PeepSo::ACCESS_PUBLIC]);
		$options[PeepSo::ACCESS_PRIVATE] .= __(' (profile owner)', 'peepso-core');

		$this->args('options', $options);

		$this->set_field(
				'site_profile_posts',
				__('Who can post on "my profile" page', 'peepso-core'),
				'select'
		);

		$this->args('default', 1);
		$this->set_field(
				'site_profile_posts_override',
				__('Let users override this setting', 'peepso-core'),
				'yesno_switch'
		);


		// Build Group
		$this->set_group(
			'activity',
			__('Activity', 'peepso-core'),
			__('These settings control how many posts and comments will be displayed in the Activity Stream, as well as "read more" settings and permissions to post on another user\'s profiles.', 'peepso-core')
		);
	}

	private function _group_license()
	{
		// Get all licensed PeepSo products
		$products = apply_filters('peepso_license_config', array());

		if (0 === count($products)) {
			return (NULL);
		}

		$new_products = array();
        foreach ($products as $prod) {

            $key = $prod['plugin_name'];

		    if(strstr($prod['plugin_name'],':')) {
                $name = explode(':', $prod['plugin_name']);
                $prod['cat'] = $name[0];
                $prod['plugin_name'] = $name[1];
            }

            $new_products[$key] = $prod;
        }

        ksort($new_products);

		// Loop through the list and build fields
        $prev_cat = NULL;
		foreach ($new_products as $prod) {

		    if(isset($prod['cat']) && $prev_cat != $prod['cat']) {
                $this->set_field(
                    'cat_'.$prod['cat'],
                    $prod['cat'],
                    'separator'
                );

                $prev_cat = $prod['cat'];
            }
			// label contains some extra HTML for  license checking AJAX to hook into
            $label = $prod['plugin_name'];
			$label .= ' <span class="license_status_check" id="' . $prod['plugin_slug'] . '" data-plugin-name="'.$prod['plugin_edd'].'"><img src="images/loading.gif"></span>';
            $label .='<br><small style=color:#cccccc>';
            $label .= $prod['plugin_version'].'</small>';

			$this->set_field(
				'site_license_'.$prod['plugin_slug'],
				$label,
				'text'
			);
		}

		// Build Group
		$this->set_group(
			'license',
			__('License Key Configuration', 'peepso-core'),
			'<a name="licensing"></a>' . __('This is where you configure the license keys for each PeepSo add-on. You can find your license numbers on <a target="_blank" href="http://peepso.com/my-account/">My Orders</a> page. Please copy them here and click SAVE at the bottom of this page.', 'peepso-core')
		);
	}
}

// EOF
