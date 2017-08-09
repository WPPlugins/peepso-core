<?php

class PeepSoConfigSectionAppearance extends PeepSoConfigSectionAbstract
{
	public static $css_overrides = array(
		'appearance-avatars-square',
	);

	// Builds the groups array
	public function register_config_groups()
	{
		$this->context='left';
		$this->_group_members();
		$this->_group_profiles();

		$this->context='right';
		$this->_group_general();
		$this->_group_registration();
	}

	private function _group_profiles()
	{
		// Display Name Style
		$options = array(
			'real_name' => __('Real Names', 'peepso-core'),
			'username' => __('Usernames', 'peepso-core'),
		);

		$this->args('options', $options);

		$this->set_field(
			'system_display_name_style',
			__('Display Name Style', 'peepso-core'),
			'select'
		);

		// Allow User To Override Name Setting
		$this->set_field(
				'system_override_name',
				__('Users Can Override Display Name Style', 'peepso-core'),
				'yesno_switch'
		);


		// Allow User To Change Username
		$this->args('default', 1);
		$this->args('descript', __('Users can modify their usernames'));
		$this->set_field(
				'allow_username_change',
				__('Username Changes', 'peepso-core'),
				'yesno_switch'
		);

		// Allow Profile Deletion
		$this->args('descript', __('Users can permanently delete their profiles'));
		$this->set_field(
			'site_registration_allowdelete',
			__('Profile Deletion', 'peepso-core'),
			'yesno_switch'
		);


		// Profile Deletion
		$this->args('descript',__('User profiles are shareable to social networks', 'peepso-core'));
		$this->set_field(
			'profile_sharing',
			__('Profile Sharing', 'peepso-core'),
			'yesno_switch'
		);

		// Profile Likes
		$this->args('descript',__('Users can "like" each other\'s  profiles', 'peepso-core'));
		$this->set_field(
			'site_likes_profile',
			__('Profile Likes', 'peepso-core'),
			'yesno_switch'
		);

		// Always link to PeepSo Profile
		$this->set_field(
			'always_link_to_peepso_profile',
			__('Always link to PeepSo Profile', 'peepso-core'),
			'yesno_switch'
		);

		/** AVATARS **/
		// # Separator Avatars
		$this->set_field(
			'separator_avatars',
			__('Avatars', 'peepso-core'),
			'separator'
		);

		// Use Square Avatars
		$this->set_field(
			'appearance-avatars-square',
			__('Use square avatars', 'peepso-core'),
			'yesno_switch'
		);

		// Use Peepso Avatars
		$this->set_field(
			'avatars_wordpress_only',
			__('Use WordPress Avatars', 'peepso-core'),
			'yesno_switch'
		);

		// Use Peepso Avatars
		$this->set_field(
			'avatars_wordpress_only_desc',
			__('The users will be unable to change their avatars in their PeepSo profiles. PeepSo will inherit the avatars from your WordPress site', 'peepso-core'),
			'message'
		);

		// Use Peepso Avatars
		$this->set_field(
			'avatars_peepso_only',
			__('Use PeepSo avatars everywhere', 'peepso-core'),
			'yesno_switch'
		);

		// Use Gravatar Avatars
		$this->set_field(
			'avatars_gravatar_enable',
			__('Allow Gravatar avatars', 'peepso-core'),
			'yesno_switch'
		);


		// Build Group
		$this->set_group(
			'profiles',
			__('User Profiles', 'peepso-core')
		);
	}


	private function _group_registration()
	{
		/** CUSTOM TEXT **/

		// # Separator Callout
		$this->set_field(
			'separator_callout',
			__('Customize text', 'peepso-core'),
			'separator'
		);

		// # Callout Header
		$this->set_field(
			'site_registration_header',
			__('Callout Header', 'peepso-core'),
			'text'
		);

		// # Callout Text
		$this->set_field(
			'site_registration_callout',
			__('Callout Text', 'peepso-core'),
			'text'
		);

		// # Button Text
		$this->set_field(
			'site_registration_buttontext',
			__('Button Text', 'peepso-core'),
			'text'
		);

		/** LANDING PAGE IMAGE **/
		// # Separator Landing Page
		$this->set_field(
			'separator_landing_page',
			__('Landing Page Image', 'peepso-core'),
			'separator'
		);

		// # Message Logging Description
		$this->set_field(
			'suggested_message_landing_page',
			// todo: filter for landing page image size
			__('Suggested Landing Page image size is: 1140px x 469px.', 'peepso-core'),
			'message'
		);

		// Landing Page Image
		$default = PeepSo::get_option('landing_page_image', PeepSo::get_asset('images/landing/register-bg.jpg'));
		$landing_page = !empty($default) ? $default : PeepSo::get_asset('images/landing/register-bg.jpg');
		$this->args('value', $landing_page);
		$this->set_field(
			'landing_page_image',
			__('Selected Image', 'peepso-core'),
			'text'
		);

		$default = PeepSo::get_option('landing_page_image_default', PeepSo::get_asset('images/landing/register-bg.jpg'));
		$this->args('value', $default);
		$this->set_field(
			'landing_page_image_default',
			'',
			'text'
		);
		// Build Group
		$this->set_group(
			'registration',
			__('Registration', 'peepso-core')
		);
	}

	private function _group_general()
	{
		// Primary CSS Template
		$options = array(
			'' => __('Light', 'peepso-core'),
		);

		$dir =  plugin_dir_path(__FILE__).'/../templates/css';

		$dir = scandir($dir);
		$from_key	= array( 'template-', '.css' );
		$to_key		= array( '' );

		$from_name	= array( '_', '-' );
		$to_name 	= array( ' ',' ' );

		foreach($dir as $file){
			if('template-' == substr($file, 0, 9)) {

				$key=str_replace($from_key, $to_key, $file);
				$name=str_replace($from_name, $to_name, $key);
				$options[$key]=ucwords($name);
			}
		}

		$this->args('options', $options);

		$this->set_field(
			'site_css_template',
			__('Primary CSS Template', 'peepso-core'),
			'select'
		);


		// Show "Powered By Peepso" Link
		$this->set_field(
			'system_show_peepso_link',
			__('Show "Powered by PeepSo" link', 'peepso-core'),
			'yesno_switch'
		);

		// Show notification icons on WP Toolbar
		$this->set_field(
			'site_show_notification_on_navigation_bar',
			__('Show notification icons on WP Toolbar', 'peepso-core'),
			'yesno_switch'
		);

		// Build Group
		$this->set_group(
			'appearance_general',
			__('General', 'peepso-core')
		);
	}

	private function _group_members()
	{
		// Default Sorting
		$options = array(
			'' => __('Alphabetical', 'peepso-core'),
			'peepso_last_activity' => __('Recently online', 'peepso-core'),
			'registered' => __('Latest members', 'peepso-core'),
		);

		$this->args('options', $options);

		$this->set_field(
			'site_memberspage_default_sorting',
			__('Default Sorting', 'peepso-core'),
			'select'
		);

		// Allow users to hide themselves from all user listings
		$this->args('descript', __('Users can hide from Members Page, Widgets etc', 'peepso-core'));
		$this->set_field(
			'allow_hide_user_from_user_listing',
			__('Users can hide from user listings', 'peepso-core'),
			'yesno_switch'
		);

		// allow guest access to Members listing
		$this->args('default', 0);
		$this->set_field(
			'allow_guest_access_to_members_listing',
			__('Allow guest access to members listing', 'peepso-core'),
			'yesno_switch'
		);

		// Build Group
		$this->set_group(
			'appearance_members',
			__('Member listings', 'peepso-core')
		);
	}
}