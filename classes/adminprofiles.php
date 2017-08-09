<?php

class PeepSoAdminProfiles
{
	public static function administration()
	{
		self::enqueue_scripts();
		$PeepSoUser = PeepSoUser::get_instance(0);
		$profile_fields = new PeepSoProfileFields($PeepSoUser);
		$fields = $profile_fields->load_fields();

		$add_new_message = __('To be able to add new Profile Fields. Please install PeepSo Extended Profile Fields plugin. You can find it <a href="https://peepso.com/pricing" target="_blank">here</a>.', 'peepso');
		$plugin_url = 'https://peepso.com/downloads/profileso';

		wp_localize_script('peepso-admin-profiles', 'peepsoadminprofilesdata', array(
			'popup_template' => PeepSoTemplate::exec_template('admin', 'profiles_no_plugin', array('message' => $add_new_message), TRUE),
			'plugin_url' => $plugin_url,
			'number_invalid' => __('Value should be greater than or equal to 0.', 'peepso-core'),
			'max_invalid' => __('Maximum value should be greater than or equal to %d (minimum value).', 'peepso-core'),
			'min_invalid' => __('Minimum value should be less than or equal to %d (maximum value).', 'peepso-core'),
		));

		do_action('peepso_admin_profiles_list_before');
		PeepSoTemplate::exec_template('admin','profiles_field_list', $fields);
	}

	public static function enqueue_scripts()
	{
		wp_register_script('bootstrap', PeepSo::get_asset('aceadmin/js/bootstrap.min.js'),
			array('jquery'), PeepSo::PLUGIN_VERSION, TRUE);
		wp_register_script('peepso-admin-profiles', PeepSo::get_asset('js/admin-profiles.min.js'),
			array('jquery', 'jquery-ui-sortable', 'underscore', 'peepso'), PeepSo::PLUGIN_VERSION, TRUE);

		wp_enqueue_script('bootstrap');
		wp_enqueue_script('peepso-admin-profiles');
	}
}

if(isset($_GET['peepso_reset_profile_fields']) && PeePso::is_admin()) {
	PeepSoProfileFields::reset();
	PeepSoProfileFields::install(TRUE);
	echo '<hr>Reset complete';die();
}

// EOF
