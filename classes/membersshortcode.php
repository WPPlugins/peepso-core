<?php

class PeepSoMembersShortcode
{
	public $template_tags = array(
		'show_member'
	);

	public function __construct()
	{
		add_action('wp_enqueue_scripts', array(&$this, 'enqueue_scripts'));
	}

	/**
	 * Enqueues the scripts used in this shortcode only.
	 */
	public function enqueue_scripts()
	{

	}

	/**
	 * Displays the member search page.
	 */
	public function shortcode_search()
	{
		$allow_guest_access = PeepSo::get_option('allow_guest_access_to_members_listing', 0);
		if(get_current_user_id() > 0 || !$allow_guest_access) {
			do_action('peepso_profile_completeness_redirect');
		}

		PeepSo::set_current_shortcode('peepso_members');
		$allow = apply_filters('peepso_access_content', TRUE, 'peepso_members', PeepSo::MODULE_ID);
		if (!$allow) {
			echo apply_filters('peepso_access_message', NULL);
			return;
		}
		
		// get gender field
		$PeepSoUser = PeepSoUser::get_instance(0);
		$profile_fields = new PeepSoProfileFields($PeepSoUser);
		$fields = $profile_fields->load_fields();
		
		wp_enqueue_script('peepso-page-members', PeepSo::get_asset('js/page-members.min.js'), array('peepso', 'peepso-page-autoload'), PeepSo::PLUGIN_VERSION, TRUE);

		$ret = PeepSoTemplate::get_before_markup() .
				PeepSoTemplate::exec_template('members', 'search', array('allow_guest_access' => $allow_guest_access, 'genders' => $fields['peepso_user_field_gender']->meta->select_options), TRUE) .
				PeepSoTemplate::get_after_markup();

		wp_reset_query();

		// disable WP comments from displaying on page
//		global $wp_query;
//		$wp_query->is_single = FALSE;
//		$wp_query->is_page = FALSE;

		return ($ret);
	}
}

// EOF
