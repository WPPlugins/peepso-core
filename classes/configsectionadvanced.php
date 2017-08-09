<?php

class PeepSoConfigSectionAdvanced extends PeepSoConfigSectionAbstract
{
	public static $css_overrides = array(
		'appearance-avatars-square',
	);

	// Builds the groups array
	public function register_config_groups()
	{
		$this->context='full';
		$this->_group_filesystem();

		if( !isset($_GET['filesystem']) ) {
            $this->_group_uninstall();
			$this->context = 'left';
			$this->_group_opengraph();
            $this->_group_debug();

			$this->context = 'right';
			$this->_group_listings();
			$this->_group_emails();
		}

		# @todo #257 $this->config_groups[] = $this->_group_opengraph();
	}

	private function _group_filesystem()
	{

		// # Message Filesystem
		$this->set_field(
			'system_filesystem_warning',
			__('This setting is to be changed upon very first PeepSo activation or in case of site migration. If changed in any other case it will result in missing content including user avatars, covers, photos etc. (error 404).', 'peepso-core'),
			'warning'
		);

		// # Message Filesystem
		$this->set_field(
			'system_filesystem_description',
			__('PeepSo allows users to upload images that are stored on your server. Enter a location where these files are to be stored.<br/>This must be a directory that is writable by your web server and and is accessible via the web. If the directory specified does not exist, it will be created.', 'peepso-core'),
			'message'
		);

		$this->args('class','col-xs-12');
		$this->args('field_wrapper_class','controls col-sm-10');
		$this->args('field_label_class', 'control-label col-sm-2');
		$this->args('default', WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'peepso');

		$this->args('validation', array('required', 'custom'));
		$this->args('validation_options',
			array(
			'error_message' => __('Can not write to directory', 'peepso-core'),
			'function' => array($this, 'check_wp_filesystem')
			)
		);
		// # Uploads
		$this->set_field(
			'site_peepso_dir',
			__('Uploads Directory', 'peepso-core'),
			'text'
		);


		$this->set_group(
			'filesystem',
			__('File System', 'peepso-core')
		);
	}

	private function _group_debug()
	{
		// # Enable Logging
		$this->set_field(
			'system_enable_logging',
			__('Enable Logging', 'peepso-core'),
			'yesno_switch'
		);

		// # Message Logging Description
		$this->set_field(
			'system_enable_logging_description',
			__('When enabled, various debug information is logged in the peepso_errors database table. This can impact website speed and should ONLY be enabled when someone is debugging PeepSo.', 'peepso-core'),
			'message'
		);

        // # Enable Logging
        $this->set_field(
            'override_fstvl',
            __('Override Strict Version Lock', 'peepso-core'),
            'yesno_switch'
        );

        // # Message Logging Description
        $this->set_field(
            'override_fstvl_desc',
            __('Strict Version Lock makes sure that it\'s impossible to upgrade PeepSo Core before all of the child plugins have been updated. Please DO NOT enable this unless you are having issues with updating PeepSo Core.', 'peepso-core'),
            'message'
        );


		$this->set_group(
			'advanced_debug',
			__('Maintenance & debugging', 'peepso-core')
		);
	}

	/**
	 * #1922 add config option for listings
	 */
	private function _group_listings()
	{
		$this->args('descript', __('Disables infinite loading of activities, members lists etc until the users clicks the "load more" button.', 'peepso-core'));
		$this->set_field(
			'loadmore_enable',
			__('Enable "load more:" button', 'peepso-core'),
			'yesno_switch'
		);

		// Build Group
		$this->set_group(
			'listings',
			__('Listings', 'peepso-core')
		);
	}

	private function _group_emails()
	{
		// # Email Sender
		$this->args('validation', array('required','validate'));
		$this->args('data', array(
			'rule-min-length' => 1,
			'rule-max-length' => 64,
			'rule-message'    => __('Should be between 1 and 64 characters long.', 'peepso-core')
		));


		$this->set_field(
			'site_emails_sender',
			__('Email sender', 'peepso-core'),
			'text'
		);

		// # Admin Email
		$this->args('validation', array('required','validate'));
		$this->args('data', array(
			'rule-type'    => 'email',
			'rule-message' => __('Email format is invalid.', 'peepso-core')
		));
		$this->set_field(
			'site_emails_admin_email',
			__('Admin Email', 'peepso-core'),
			'text'
		);

		// # Copyright Text
		$this->args('raw', TRUE);

		$this->set_field(
			'site_emails_copyright',
			__('Copyright Text', 'peepso-core'),
			'textarea'
		);

		// # Number of mails to process per run
		$this->args('validation', array('required','validate'));

		// new javascript validation
		$this->args('data', array(
			'rule-type'    => 'int',
			'rule-min'     => 1,
			'rule-max'     => 1000,
			'rule-message' => __('Insert number between 1 and 1000.', 'peepso-core')
		));

		$this->args('int', TRUE);

		$this->set_field(
			'site_emails_process_count',
			__('Number of mails to process per run', 'peepso-core'),
			'text'
		);

		// # Disable MailQueue
		$this->set_field(
			'disable_mailqueue',
			__('Disable PeepSo Default Mailqueue', 'peepso-core'),
			'yesno_switch'
		);

		// # Message Logging Description
		$this->set_field(
			'disable_mailqueue_description',
			__('Only set this to "yes" if you are experiencing issues with the default PeepSo mailqueue (some PeepSo emails not sent). ', 'peepso-core')
			.__('You will have to set up a replacement cronjob, please refer to the documentation under the keyword "mailqueue"', 'peepso-core')
			,
			'message'
		);
		
		// Build Group
		$this->set_group(
			'emails',
			__('Emails', 'peepso-core'),
			__('These settings control the appearance of emails sent by PeepSo.', 'peepso-core')
		);
	}

	private function _group_uninstall()
	{
		// # Delete Posts and Comments
		$this->args('field_wrapper_class', 'controls col-sm-8 danger');

		$this->set_field(
			'delete_post_data',
			__('Delete Post and Comment data', 'peepso-core'),
			'yesno_switch'
		);

		// # Delete All Data And Settings
		$this->args('field_wrapper_class', 'controls col-sm-8 danger');

		$this->set_field(
			'delete_on_deactivate',
			__('Delete all data and settings', 'peepso-core'),
			'yesno_switch'
		);

		// Build Group
		$summary= __('When set to "YES", all <em>PeepSo</em> data will be deleted upon plugin Uninstall (but not Deactivation).<br/>Once deleted, <u>all data is lost</u> and cannot be recovered.', 'peepso-core');
		$this->args('summary', $summary);

		$this->set_group(
			'peepso_uninstall',
			__('PeepSo Uninstall', 'peepso-core'),
			__('Control behavior of PeepSo when uninstalling / deactivating', 'peepso-core')
		);
	}

	private function _group_opengraph()
	{
		$this->set_field(
			'opengraph_enable',
			__('Enable Open Graph', 'peepso-core'),
			'yesno_switch'
		);

		// Open Graph Title
		$this->set_field(
			'opengraph_title',
			__('Title (og:title)', 'peepso-core'),
			'text'
		);
		
		// Open Graph Title
		$this->set_field(
			'opengraph_description',
			__('Description (og:description)', 'peepso-core'),
			'textarea'
		);
		
		// Open Graph Image
		$this->set_field(
			'opengraph_image',
			__('Image (og:image)', 'peepso-core'),
			'text'
		);

		$this->set_group(
			'opengraph',
			__('Open Graph', 'peepso-core'),
			__("The Open Graph protocol enables sites shared for example to Facebook carry information that render shared URLs in a great way. Having a photo, title and description. You can learn more about it in our documentation. Just search for 'Open Graph'.", 'peepso-core')
		);
	}


	/**
	 * Checks if the directory has been created, if not use WP_Filesystem to create the directories.
	 * @param  string $value The peepso upload directory
	 * @return boolean
	 */
	public function check_wp_filesystem($value)
	{
		$form_fields = array('site_peepso_dir');
		$url = wp_nonce_url('admin.php?page=peepso_config&tab=advanced', 'peepso-config-nonce', 'peepso-config-nonce');

		if (FALSE === ($creds = request_filesystem_credentials($url, '', false, false, $form_fields))) {
			return FALSE;
		}

		// now we have some credentials, try to get the wp_filesystem running
		if (!WP_Filesystem($creds)) {
			// our credentials were no good, ask the user for them again
			request_filesystem_credentials($url, '', true, false, $form_fields);
			return FALSE;
		}

		global $wp_filesystem;

		if (!$wp_filesystem->is_dir($value) || !$wp_filesystem->is_dir($value . DIRECTORY_SEPARATOR . 'users')) {
			$wp_filesystem->mkdir($value);
			$wp_filesystem->mkdir($value . DIRECTORY_SEPARATOR . 'users');
			return TRUE;
		}

		return $wp_filesystem->is_writable($value);
	}

}