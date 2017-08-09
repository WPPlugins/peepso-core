<?php
/*
 * Performs tasks for Admin page requests
 * @package PeepSo
 * @author PeepSo
 */

class PeepSoAdmin
{
	const NOTICE_KEY = 'peepso_admin_notices_';
	const NOTICE_TTL = 3600;                // set TTL to 1 hour - probably overkill
	const PEEPSO_URL = 'https://www.peepso.com';

	private static $_instance = NULL;

	private $dashboard_tabs = NULL;
	private $dashboard_metaboxes = NULL;
	private $tab_count = 0;

	private function __construct()
	{
		if (get_option('permalink_structure'))
			add_action('admin_menu', array(&$this, 'admin_menu'), 9);

		add_action('admin_enqueue_scripts', array(&$this, 'enqueue_scripts'));

		add_action('deleted_user', array(&$this, 'delete_callback'), 10, 1);
		//allow redirection, even if my theme starts to send output to the browser
		add_action('init', array(&$this, 'do_output_buffer'));
		add_action('init', array(&$this, 'is_peepso_dir_writable_check'));
		add_action('init', array(&$this, 'get_plugin_list'));

		add_action('admin_notices', array(&$this, 'admin_notices'));
		if (PeepSo::get_option('new_pluginnew_plugin', 0) === 1) {
			add_action('admin_notices', array(&$this, 'new_plugin_notice'));
		}
		// check for wp-admin/user.php page and include hooks/classes for user list
		add_filter('views_users', array(&$this, 'filter_user_views'), 100, 1);
		add_filter('manage_users_custom_column', array(&$this, 'filter_custom_user_column'), 10, 3);
		add_filter('user_row_actions', array(&$this, 'filter_user_actions'), 10, 2);
		add_action('manage_users_columns', array(&$this, 'filter_user_list_columns'));
//      add_action('set_user_role', array(&$this, 'set_user_role'), 10, 3);
		add_action('restrict_manage_users', array(&$this, 'peepso_roles'));
		add_action('current_screen', array(&$this, 'update_user_roles'));
		add_action('current_screen', array(&$this, 'update_report'));
		add_action('admin_notices', array(&$this,'register_notice'));
		add_action("wp_ajax_dismiss_new_plugin_notice", array(&$this, "dismiss_new_plugin_notice"));

		$dir = explode('/', plugin_basename(__FILE__));
		$dir = $dir[0];

		add_action('admin_footer', array(&$this, 'show_deactivation_feedback_dialog'));
		add_filter('plugin_action_links_' . $dir . '/peepso.php', array(&$this, 'modify_plugin_action_links'), 10, 2 );
		add_filter('network_admin_plugin_action_links_' . $dir . '/peepso.php', array(&$this, 'modify_plugin_action_links'), 10, 2 );

		add_filter('peepso_admin_profile_field_types', array(&$this,'filter_admin_profile_field_types'));
		add_action('peepso_config_after_save-advanced', array(&$this, 'after_save_advanced'));

		// delete cache
		add_action('delete_user', array(&$this, 'clear_transient'));

	}
	public function filter_admin_profile_field_types( $field_types )
	{
		$field_types[] = 'text';
		$field_types[] = 'textdate';
		$field_types[] = 'texturl';
		$field_types[] = 'selectsingle';
		return $field_types;
	}


	/*
	 * return singleton instance of PeepSoAdmin
	 */
	public static function get_instance()
	{
		if (NULL === self::$_instance)
			self::$_instance = new self();
		return (self::$_instance);
	}


	/*
	 * Callback for displaying admin notices
	 */
	public function admin_notices()
	{
		$screen = get_current_screen();
		if ('users.php' === $screen->parent_file) {
			// check if there are one or more users with a role of 'verified' or 'registered'
//          $result = count_users();
//          if (isset($result['avail_roles']['peepso_register']) || isset($result['avail_roles']['peepso_verified'])) {
			$usradm = PeepSoUser::get_instance();
			$count_roles = $usradm->count_for_roles(array('verified', 'register'));
			if (0 !== $count_roles) {
				$notice = __('You have Registered or Verified users that need to be approved. To approve, change the user\'s role to PeepSo Member or other appropriate role.', 'peepso-core');
				$notice .= sprintf(__(' %1$sClick here%2$s for more information on assigning roles.', 'peepso-core'),
					'<a href="#TB_inline?&inlineId=assign-roles-modal-id" class="thickbox">',
					'</a>');
//              $notice .= ' <a href="#TB_inline?inlineId=assign-roles-modal-id" class="thickbox">' . __('Click here', 'peepso-core') . '</a>' . __(' for more information on assigning roles.', 'peepso-core');
				echo '<div class="update-nag" style="padding:11px 15px; margin:5px 15px 2px 0;">', $notice, '</div>', PHP_EOL;
				echo '<div id="assign-roles-modal-id" style="display:none;">';
				echo '<div>';
				echo '<h3>', __('PeepSo User Roles:', 'peepso-core'), '</h3>';
				echo '<p>', sprintf(__('You can change Roles for PeepSo users by selecting the checkboxes for individual users and then selecting the desired Role from the %s dropdown.', 'peepso-core'),
					'<select><option>' . __('- Select Role -', 'peepso-core') . '</option></select>'), '</p>';
				echo '<p>', sprintf(__('Once the new Role is selected, click on the %s button and those users will be updated.', 'peepso-core'),
					'<input type="button" name="sample" id="sample" class="button" value="' . __('Change Role', 'peepso-core') . '">'), '</p>';
				echo '<p>', __('Meaning of user roles:', 'peepso-core'), '</p>';
				$roles = $this->get_roles();
				$translated_roles = $this->get_translated_roles();
				foreach ($roles as $name => $desc) {
					echo '&nbsp;&nbsp;<b>', $translated_roles[$name], '</b> - ', esc_html($desc), '<br/>';
				}
				echo '</div>';
				echo '</div>'; // #assign-roles-modal-id
				wp_enqueue_script('thickbox');
				wp_enqueue_style('thickbox');
			}
		}

		$key = self::NOTICE_KEY . get_current_user_id();
		$notices = get_transient($key);

		if ($notices) {
			foreach ($notices as $notice)
				echo '<div class="', $notice['class'], '" style="padding:11px 15px; margin:5px 15px 2px 0;">', $notice['message'], '</div>' . PHP_EOL;
		}
		delete_transient($key);
	}


	/*
	 * callback for admin_menu event. set up menus
	 */
	public function admin_menu()
	{
		$admin = PeepSoAdmin::get_instance();
		// $dasboard_hookname = toplevel_page_peepso
		$dashboard_hookname = add_menu_page(__('PeepSo', 'peepso-core'), __('PeepSo', 'peepso-core'),
			'manage_options',
			'peepso',
			array(&$this, 'dashboard'),
			PeepSo::get_asset('images/admin/logo-icon_20x20.png'),
			4);

		add_action('load-' . $dashboard_hookname, array(&$this, $dashboard_hookname . '_loaded'));
		add_action('load-' . $dashboard_hookname, array(&$this, 'config_page_loaded'));

		$aTabs = $admin->get_tabs();

		// add submenu items for each item in tabs list
		foreach ($aTabs as $color => $tabs) {
			foreach ($tabs as $name => $tab) {
				$function = (isset($tab['function'])) ? $tab['function'] : null;

				$count = '';
				if (isset($tab['count']) && ($tab['count'] > 0 || (!is_int($tab['count']) && strlen($tab['count'])))) {
					$count = '<span class="awaiting-mod"><span class="pending-count">' . $tab['count'] . '</span></span>';
				}
				$submenu = '';
				if (isset($tab['submenu']))
					$submenu = $tab['submenu'];

				$submenu_page = add_submenu_page('peepso',
					$tab['menu'], $tab['menu'] . $count . $submenu,
					'manage_options', $tab['slug'], $function);

				if (method_exists($this, $submenu_page . '_loaded'))
					add_action('load-' . $submenu_page, array(&$this, $submenu_page . '_loaded'));


				add_action('load-' . $submenu_page, array(&$this, 'config_page_loaded'));
			}
		}

		$rep = new PeepSoReport();
		$items = $rep->get_num_reported_items();
		$count = '';
		if ($items > 0)
			$count = '<span class="awaiting-mod"><span class="pending-count">' . $items . '</span></span>';

		$report_sub = add_submenu_page(
			'peepso',
			__('Reported Items', 'peepso-core'),
			__('Reported Items', 'peepso-core') . $count,
			'manage_options',
			'peepso-reports',
			array('PeepSoAdminReport', 'dashboard')
		);
		add_action('load-' . $report_sub, array(&$this, 'config_page_loaded'));
	}


	public static function admin_header($title)
	{
		?><h1 style="font-variant:small-caps;color: #666666;"><img width="110" style="margin-top:10px;" src="<?php echo PeepSo::get_asset('images/admin/logo_red.png');?>" /> <?php echo strtolower($title);?></h1><?php
	}
	/*
	 * callback to display the PeepSo Dashboard
	 */
	public function dashboard()
	{
		$aTabs = apply_filters('peepso_admin_dashboard_tabs', $this->dashboard_tabs);

		$peepso_config = PeepSoConfig::get_instance();
		$admin = PeepSoAdmin::get_instance();
		$admin->define_dashboard_metaboxes();
		$this->dashboard_metaboxes = apply_filters('peepso_admin_dashboard_metaboxes', $this->dashboard_metaboxes);
		$admin->prepare_metaboxes();

		PeepSoAdmin::admin_header(__('Dashboard', 'peepso-core'));
		echo '<div id="peepso" class="wrap">';

		echo '<div class="row-fluid">';

		echo '<div class="dashtab">';
		foreach ($aTabs as $color => $tabs)
			$this->output_tabs($color, $tabs);
		echo '</div>';

		echo '<div class="dashgraphs">';
		echo '<div class="row">
				<div class="col-xs-12">
				<div class="row">
					<!-- Left column -->
					<div class="col-xs-12 col-sm-6">';
		$peepso_config->do_meta_boxes('toplevel_page_peepso', 'left', null);
		echo '
					</div>
					<!-- Right column -->
					<div class="col-xs-12 col-sm-6">';
		$peepso_config->do_meta_boxes('toplevel_page_peepso', 'right', null);
		echo '
					</div>
				</div>
				<div class="clearfix"></div>
			</div>
		</div>
		<div class="clearfix"></div>';

		echo '</div>';
		echo '</div>';
		echo '</div>';  // .wrap
	}

	/**
	 * Output the admin dashboard tabs
	 * @param  string $color   The infobox color used as css class
	 * @param  array $tablist The tabs to be displayed
	 * @return void          Echoes the tab HTML.
	 */
	private function output_tabs($color, $tablist)
	{
		$size = number_format((100 / $this->tab_count) - 1, 2);
		if ($size > 15)
			$size = 15;
		foreach ($tablist as $tab => $data) {
			echo    '<div class="infobox infobox-', $color, ' infobox-dark" style="width:', $size, '%">', PHP_EOL;
			if ('/' === substr($data['slug'], 0, 1))
				echo    '<a href="', get_admin_url(NULL, $data['slug']), '">', PHP_EOL;
			else
				echo    '<a href="admin.php?page=', $data['slug'], '">', PHP_EOL;
			echo            '<div class="infobox-icon dashicons dashicons-', $data['icon'], '"></div>' , PHP_EOL;
			if (isset($data['count'])) {
				echo            '<div class="infobox-data">', PHP_EOL;
				echo                '<div class="infobox-content">', $data['count'], '</div>', PHP_EOL;
				echo            '</div>', PHP_EOL;
			}
			echo            '<div class="infobox-caption">', $data['menu'], '</div>', PHP_EOL;
			echo            '</a>', PHP_EOL;
			echo    '</div>', PHP_EOL;
		}
	}


	/*
	 * Enqueue scripts and styles for PeepSo admin
	 */
	public function enqueue_scripts()
	{
		global $wp_styles;

		wp_register_style('ace-admin-boostrap-min', PeepSo::get_asset('aceadmin/css/bootstrap.min.css'), NULL, PeepSo::PLUGIN_VERSION, 'all');
		wp_register_style('ace-admin-boostrap-responsive', PeepSo::get_asset('aceadmin/bootstrap-responsive.min.css'), NULL, PeepSo::PLUGIN_VERSION, 'all');
		wp_register_style('ace-admin-boostrap-timepicker', PeepSo::get_asset('aceadmin/bootstrap-timepicker.css'), NULL, PeepSo::PLUGIN_VERSION, 'all');

		wp_register_style('ace-admin-fonts', PeepSo::get_asset('aceadmin/css/ace-fonts.css'), NULL, PeepSo::PLUGIN_VERSION, 'all');
		wp_register_style('ace-admin-fontawesome', PeepSo::get_asset('aceadmin/css/font-awesome.min.css'), NULL, PeepSo::PLUGIN_VERSION, 'all');
		wp_register_style('ace-admin', PeepSo::get_asset('aceadmin/css/ace.min.css'), NULL, PeepSo::PLUGIN_VERSION, 'all');
		wp_register_style('ace-admin-responsive', PeepSo::get_asset('aceadmin/css/ace-responsive.min.css'), NULL, PeepSo::PLUGIN_VERSION, 'all');
		wp_register_style('ace-admin-skins', PeepSo::get_asset('aceadmin/css/ace-skins.min.css'), NULL, PeepSo::PLUGIN_VERSION, 'all');
		wp_register_style('ace-admin-ie', PeepSo::get_asset('aceadmin/css/ace-ie.min.css'), NULL, PeepSo::PLUGIN_VERSION, 'all');
		$wp_styles->add_data('ace-admin-ie', 'conditional', 'IE 7');

		if ( is_rtl() ) {
			wp_register_style('peepso-admin', PeepSo::get_asset('css/admin-rtl.css'), NULL, PeepSo::PLUGIN_VERSION, 'all');
		} else {
			wp_register_style('peepso-admin', PeepSo::get_asset('css/admin.css'), NULL, PeepSo::PLUGIN_VERSION, 'all');
		}

		// core peepso libraries
		wp_register_script('peepso-core', PeepSo::get_asset('js/peepso-core.min.js'), array('jquery', 'underscore'), PeepSo::PLUGIN_VERSION, TRUE);
		wp_register_script('peepso-observer', FALSE, array('peepso-core'), PeepSo::PLUGIN_VERSION, TRUE);
		wp_register_script('peepso-npm', FALSE, array('peepso-core'), PeepSo::PLUGIN_VERSION, TRUE);
		wp_register_script('peepso-util', FALSE, array('peepso-core'), PeepSo::PLUGIN_VERSION, TRUE);
		wp_register_script('peepso', PeepSo::get_asset('js/peepso.js'), array('peepso-core'), PeepSo::PLUGIN_VERSION, TRUE);

		wp_register_script('peepso-admin-config', PeepSo::get_asset('js/peepso-admin-config.js'),
			array('jquery'), PeepSo::PLUGIN_VERSION, TRUE);

		$aData = array(
			'ajaxurl' => admin_url('admin-ajax.php'),
			'ajaxurl_legacy' => get_bloginfo('wpurl') . '/peepsoajax/',
			'version' => PeepSo::PLUGIN_VERSION,
			'currentuserid' => get_current_user_id(),
			'userid' => apply_filters('peepso_user_profile_id', 0),     // user id of the user being viewed (from PeepSoProfileShortcode)
			'objectid' => apply_filters('peepso_object_id', 0),         // user id of the object being viewed
			'objecttype' => apply_filters('peepso_object_type', ''),    // type of object being viewed (profile, group, etc.)
			'loading_gif' => PeepSo::get_asset('images/ajax-loader.gif'),
			'view_all_text' => __('View All', 'peepso-core'),
			'notifications_title' => __('Notifications', 'peepso-core'),
			'mark_all_as_read_text' => __('Mark All as Read', 'peepso-core'),
		);
		wp_localize_script('peepso', 'peepsodata', $aData);
		wp_enqueue_script('peepso');

		wp_enqueue_script('peepso-admin-config');

		if ( is_rtl() ) {
			wp_enqueue_style('peepso', PeepSo::get_template_asset(NULL, 'css/admin/peepso-rtl.css'), NULL, PeepSo::PLUGIN_VERSION, 'all');
		} else {
			wp_enqueue_style('peepso', PeepSo::get_template_asset(NULL, 'css/admin/peepso.css'), NULL, PeepSo::PLUGIN_VERSION, 'all');
		}

		wp_enqueue_style('peepso-icons', PeepSo::get_template_asset(NULL, 'css/admin/icons.css'), NULL, PeepSo::PLUGIN_VERSION, 'all');
		wp_register_script('peepso-window', PeepSo::get_asset('js/pswindow.min.js'), array('jquery'), PeepSo::PLUGIN_VERSION, TRUE);

		wp_localize_script('peepso-window', 'peepsowindowdata', array(
			'label_confirm' => __('Confirm', 'peepso-core'),
			'label_confirm_delete' => __('Confirm Delete', 'peepso-core'),
			'label_confirm_delete_content' => __('Are you sure you want to delete this?', 'peepso-core'),
			'label_yes' => __('Yes', 'peepso-core'),
			'label_no' => __('No', 'peepso-core'),
			'label_delete' => __('Delete', 'peepso-core'),
			'label_cancel' => __('Cancel', 'peepso-core'),
			'label_okay' => __('Okay', 'peepso-core'),
		));

		// if version < 3.9 include dashicons
		global $wp_version;
		if (version_compare($wp_version, '3.9', 'lt')) {
			wp_register_style('peepso-dashicons', PeepSo::get_asset('css/dashicons.css'),
				array(), PeepSo::PLUGIN_VERSION, 'all');
			wp_enqueue_style('peepso-dashicons');
		}

		wp_enqueue_style('thickbox');
		wp_enqueue_script('thickbox');

		wp_enqueue_script('peepso-notification', PeepSo::get_asset('js/notifications.min.js'), array('jquery', 'jquery-ui-position', 'underscore', 'peepso', 'peepso-observer'), PeepSo::PLUGIN_VERSION, TRUE);
	}

	/*
	 * return list of tab items for PeepSo Dashboard display
	 */
	public function get_tabs()
	{
        if (NULL === $this->dashboard_tabs) {
            global $wpdb;



            $msg_count = PeepSoMailQueue::get_pending_item_count();

            $tabs = array(
				'blue' => array(
					'members' => array(
						'slug' => '/users.php',
						'menu' => __('Members', 'peepso-core'),
						'icon' => 'id-alt', // 'group',                 // dashicons-id-alt
						#'count' => intval($user_query->get_total()),
					),
					'profilefields' => array(
						'slug' => 'peepso-profiles', // peepso-messages',
						'menu' => __('Profiles', 'peepso-core'),
						'icon' => 'id-alt',
						#'count' => 'NEW!',
						'function' => array('PeepSoAdminProfiles', 'administration'),
					),
					'messages' => array(
						'slug' => 'peepso-mailqueue', // peepso-messages',
						'menu' => __('Mail Queue', 'peepso-core'),
						'icon' => 'email', // 'envelope',               // dashicons-email
						'count' => intval($msg_count),
						'function' => array('PeepSoAdminMailQueue', 'administration'),
					),
					'extensions' => array(
						'slug' => 'peepso-extensions',
						'menu' => __('Addons', 'peepso-core'),
						'icon' => 'id-alt',
						'function' => array('PeepSoAdmin', 'extensions'),
						'count' => __('NEW!', 'peepso-core'),
					),
				),
				'red' => array(
				),
				'green' => array(
				),
				'orange' => array(
				),
				'gray' => array(
					'config' => array(
						'slug' => PeepSoConfig::$slug,
						'menu' => __('Configuration', 'peepso-core'),
						'icon' => 'admin-generic',
						'function' => array('PeepSoConfig', 'init')
					),
				),
			);

			if (isset($_GET['page']) && 'peepso_config' === $_GET['page']) {
				$cfg = PeepSoConfig::get_instance();
				$cfg_tabs = $cfg->get_tabs();
				$list = '';
				foreach ($cfg_tabs as $cfg_tab => $cfg_data) {
					$list .= '<li><a href="' . admin_url('admin.php?page=peepso_config&tab=' . $cfg_data['tab']) . '">';
					$list .= '&raquo;&nbsp;' . $cfg_data['label'] . '</a></li>';
				}
				$tabs['gray']['config']['submenu'] = '</a>' .
					'<ul class="wp-submenu wp-submenu-wrap" style="margin: 0 0 0 10px">' .
					$list .
					'</ul>';
			}

			$tabs = apply_filters('peepso_admin_dashboard_tabs', $tabs);
			$this->dashboard_tabs = &$tabs;

			$this->tab_count = 0;
			foreach ($tabs as $color => $tabitems)
				$this->tab_count += count($tabitems);
		}

		return ($this->dashboard_tabs);
	}


	/*
	 * called from wp_delete_user() to signal a user has been deleted
	 * @param int $id The id of the user that is to be deleted
	 */
	public function delete_callback($id)
	{
		$user = PeepSoUser::get_instance($id);
		$user->delete_data($id);
	}


	/**
	 * Add notice with type and message
	 * @param string $notice The message to display in an Admin Notice
	 * @param string $type The type of notice. One of: 'error', 'warning', 'info', 'note', 'none'
	 */
	public function add_notice($notice, $type = 'error')
	{
		$types = array(
			'error' => 'error',
			'warning' => 'update-nag',
			'info' => 'check-column',
			'note' => 'updated',
			'none' => '',
		);
		if (!array_key_exists($type, $types))
			$type = 'none';

		$notice_data = array('class' => $types[$type], 'message' => $notice);

		$key = self::NOTICE_KEY . get_current_user_id();
		$notices = get_transient($key);

		if (FALSE === $notices)
			$notices = array($notice_data);

		// only add the message if it's not already there
		$found = FALSE;
		foreach ($notices as $notice) {
			if ($notice_data['message'] === $notice['message'])
				$found = TRUE;
		}
		if (!$found)
			$notices[] = $notice_data;

		set_transient($key, $notices, self::NOTICE_TTL);
	}

	// TODO: let's try to remove this and do away with output buffering
	public function do_output_buffer()
	{
		ob_start();
	}


	/*
	 * Update the columns displayed for the WP user list
	 * @param array $columns The current columns to display in the user list
	 * @return array The modified column list
	 */
	public function filter_user_list_columns($columns)
	{
		$ret = array();
		foreach ($columns as $key => $value) {
			// remove the 'Posts' column
			if ('posts' === $key)
				continue;
			$ret[$key] = $value;
			// add the PeepSo Role column after the WP Role column
			if ('role' === $key)
				$ret['peepso_role'] = __('PeepSo Role', 'peepso-core');
		}
		return ($ret);
	}

	/**
	 * Filters the list of view links, adding some for PeepSo roles
	 * @param array $views List of views
	 * @return array The modified list of views
	 */
	public function filter_user_views($views)
	{
		$usradm = PeepSoUser::get_instance();
		$res = $usradm->get_counts_by_role();
		if (is_array($res)) {
			foreach ($res as $row) {
				$translated_roles = $this->get_translated_roles();

				$link = '<a href="users.php?psrole=' . $row['role'] . '">' . $translated_roles[$row['role']] . ' <span class="count">(' . $row['count'] . ')</span></a>';
				$views[$row['role']] = $link;
			}
		}
		return ($views);
	}

	/**
	 * Filters the custom column, displaying the PeepSo Role value for the indicated user
	 * @param string $value Filter value
	 * @param string $column The name of the column
	 * @param int $id The user id for the row being displayed
	 * @return string Appropriate column value for the user being displayed
	 */
	public function filter_custom_user_column($value, $column, $id)
	{
		switch ($column)
		{
			case 'peepso_role':
				$roles = $this->get_roles();
				$translated_roles = $this->get_translated_roles();

				$user = PeepSoUser::get_instance($id);
				$role = $user->get_user_role();

				// Fallback for removed legacy user roles
				if(!array_key_exists($role, $roles)) {
					$role = 'member';
					$user->set_user_role($role);
				}

				$value = '<span title="' . esc_attr($roles[$role]) . '">' .
					$translated_roles[$role] . '</span>';
				break;
		}
		return ($value);
	}

	/**
	 * Filters the WP_User_Query, adding the WHERE clause to look for PeepSo roles
	 * @param WP_User_query $query The query object to filter
	 * @return WP_User_Query The modified query object
	 */
	public function filter_user_query($query)
	{
		global $wpdb;
		$input = new PeepSoInput();

		$query->query_from .= " LEFT JOIN `{$wpdb->prefix}" . PeepSoUser::TABLE . "` ON `{$wpdb->users}`.ID = `usr_id` ";
		$query->query_where .= " AND `usr_role`='" . esc_sql($input->val('psrole', 'member')) . '\' ';
		return ($query);
	}

	/**
	 * Performs updates on the user selected via the Bulk Action checkboxes
	 * @param object $screen The current screen object
	 * @return type
	 */
	public function update_user_roles($screen)
	{
		switch ($screen->base)
		{
			case 'toplevel_page_peepso' :
				$input = new PeepSoInput();

				$action = $input->val('action');
				$set = $input->val('set');
				$id = $input->val('id');
				$_wpnonce = $input->val('_wpnonce');

				if (
					$action === 'update-user-role' &&
					($set === 'member' || $set == 'ban') &&
					wp_verify_nonce($_wpnonce, 'update-role-nonce_' . $id)
				)
				{
					$user = PeepSoUser::get_instance($id);

					switch ($set)
					{
						case 'member' :
							$adm = PeepSoUser::get_instance($id);
							$adm->approve_user();

							// add action, use for created album when admin set roles to member
							do_action('peepso_register_approved', $user);

							// update the user with their new role
							$user->set_user_role('member');

							$this->add_notice(__(trim(strip_tags($user->get_fullname()))  . ' approved', 'peepso-core'), 'note');
							break;
						case 'ban' :
							$user->set_user_role('ban');

							$this->add_notice(__(trim(strip_tags($user->get_fullname())) . ' banned', 'peepso-core'), 'note');
							break;
					}
				} else if (isset($action) && $action === 'update-user-role')
				{
					$this->add_notice(__('Invalid action', 'peepso-core'), 'error');
				}
				break;
			case 'users' :
				// if there is a PeepSo Role filter requestsed, add the WP_Users_query filter
				if (isset($_GET['psrole']))
					add_filter('pre_user_query', array(&$this, 'filter_user_query'));
				if ('GET' === $_SERVER['REQUEST_METHOD']) {
					$input = new PeepSoInput();
					$role0  = strtolower($input->val('peepso-role-select', '0'));
					$role2  = strtolower($input->val('peepso-role-select2', '0'));
					$role   = $role2 != '0' ? $role2 : ( $role0 != '0' ? $role0 : '0' );
					if ('0' !== $role) {
						// verify that the form is valid
						if (!current_user_can('edit_users')) {
							$this->add_notice(__('You do not have permission to do that.', 'peepso-core'), 'error');
							return;
						}
						if (!wp_verify_nonce($input->val('ps-role-nonce'), 'psrole-nonce')) {
							$this->add_notice(__('Form is invalid.', 'peepso-core'), 'error');
							return;
						}
						$users = (isset($_GET['users']) ? $_GET['users'] : array()); // $input->val('users', array());
						$roles = $this->get_roles();
						if (in_array($role, array_keys($roles)) && 0 < count($users)) {
							foreach ($users as $user_id) {
								$user = PeepSoUser::get_instance($user_id);
								$old_role = $user->get_user_role();

								// perform approval; sends welcome email
								if ('member' === $role) {
									if ('member' === $role && 'verified' === $old_role) {
										$adm = PeepSoUser::get_instance($user_id);
										$adm->approve_user();
									}

									// add action, use for created album when admin set roles to member
									do_action('peepso_register_approved', $user);
								}
								// update the user with their new role
								//                      $data = array('usr_role' => $role);
								//                      $user->update_peepso_user($data);
								$user->set_user_role($role);
							}
						}
					} else {
						if (isset($_GET['change-peepso-role']))
							$this->add_notice(__('Please select a PeepSo Role before clicking on "Change Role".', 'peepso-core'), 'warning');
					}
				}
				break;
		}
	}

	/**
	 * Outputs UI controls for setting the User roles
	 */
	public function peepso_roles()
	{
		static $counter = 0;
		$role_extra =  $counter != 0 ?  2 : '';

		echo '<div id="peepso-role-wrap" style="vertical-align: baseline">';
		echo '<span>';
		echo __('Set PeepSo Role:', 'peepso-core'), '&nbsp;&nbsp;';
		echo '<select id="peepso-role-select" name="peepso-role-select'.$role_extra.'">';
		echo '<option value="0">', __(' - Select Role -', 'peepso-core'), '</option>';
		$roles = $this->get_roles();
		$translated_roles = $this->get_translated_roles();
		foreach ($roles as $name => $desc) {
			echo '<option value="', $name, '">', $translated_roles[$name], '</option>';
		}
		echo '</select>';
		echo '<input type="hidden" name="ps-role-nonce" value="', wp_create_nonce('psrole-nonce'), '" />';
		echo '<input type="submit" name="change-peepso-role" id="change-peepso-role" class="button" value="', __('Change Role', 'peepso-core'), '">';
		echo '</span>';
		echo '</div>';
		echo '<style>';
		echo '#peepso-role-wrap { display: inline-block; margin-left: 1em; padding: 3px 5px; }';
		echo '#peepso-role-wrap span { bottom; padding-top: 2em }';
		echo '#peepso-role-wrap #peepso-role-select { float:none; }';
		echo '</style>';

		$counter++;
	}

	/**
	 * Get a list of the Roles recognized by PeepSo
	 * @return Array The list of Roles
	 */
	public function get_roles()
	{
		$ret = array(
			'member' => __('Full member, can write posts and participate', 'peepso-core'),
			#'moderator' => __('Full member, can moderate posts', 'peepso-core'),
			'admin' => __('PeepSo Administrator, can Moderate, edit users, etc.', 'peepso-core'),
			'ban' => __('Banned, cannot login or participate', 'peepso-core'),
			'register' => __('Registered, awaiting email verification', 'peepso-core'),
			'verified' => __('Verified email, awaiting Adminstrator approval', 'peepso-core'),
			#'user' => __('Standard user account', 'peepso-core'),
		);

		// TODO: before we can allow filtering/adding to this list we need to change the `peepso_users`.`usr_role` column
		return ($ret);
	}

	public function get_translated_roles()
	{
		$ret = array(
			'member'    => __('Community Member',   'peepso'),
			'moderator' => __('Community Moderator', 'peepso-core'),
			'admin'     => __('Community Administrator',    'peepso'),
			'ban'       => __('Banned',         'peepso'),
			'register'  => __('Pending user email verification',    'peepso'),
			'verified'  => __('Pending admin approval',     'peepso'),
			#'user'         => __('role_user',      'peepso'),

		);

		foreach($ret as $k=>$v) {
			if(stristr($v, 'role_')) {
				$ret[$k] = ucwords($k);
			}
		}

		return $ret;
	}


	/*
	 * Filter the avatar so that the PeepSo avatar is displayed
	 * @param string $avatar The avatar HTML content
	 * @param midxed $id_or_email The user id or email address of the user
	 * @param int $size The size of the avatar to create
	 * @param mixed $default
	 * @param string $alt Alternate text
	 */


	/*
	 * Add a link to the user's profile page to the actions
	 * @param array $actions The current list of actions
	 * @param WP_User $user The WP_User instance
	 * @return array List of actions, with a profile link added
	 */
	public function filter_user_actions($actions, $user = NULL)
	{
		// add the 'Profile Link' action to the list of actions
		$user = PeepSoUser::get_instance($user->ID);
		$actions['profile'] = '<a class="submitdelete" href="' . $user->get_profileurl(FALSE) . '" target="_blank">' . __('Profile Link', 'peepso-core') . '</a>';
		return ($actions);
	}

	/**
	 * Enqueues scripts after the config page has been loaded
	 */
	public function config_page_loaded()
	{
		add_action('admin_enqueue_scripts', array(&$this, 'enqueue_ace_admin_scripts'));
	}

	/**
	 * Enqueues the admin dashboard assets
	 */
	public function enqueue_ace_admin_scripts()
	{
		wp_enqueue_style('ace-admin-boostrap-min');
		wp_enqueue_style('ace-admin');
		wp_enqueue_style('ace-admin-fontawesome');
		wp_enqueue_style('peepso-admin');
	}

	/**
	 * Enqueues scripts when the peepso backend is accessed
	 */
	public function toplevel_page_peepso_loaded()
	{
		wp_register_script('bootstrap', PeepSo::get_asset('aceadmin/js/bootstrap.min.js'),
			array('jquery'), PeepSo::PLUGIN_VERSION, TRUE);
		wp_register_script('flot', PeepSo::get_asset('aceadmin/js/flot/jquery.flot.min.js'),
			array('jquery'), PeepSo::PLUGIN_VERSION, TRUE);
		wp_register_script('flot-pie', PeepSo::get_asset('aceadmin/js/flot/jquery.flot.pie.min.js'),
			array('flot'), PeepSo::PLUGIN_VERSION, TRUE);
		wp_register_script('flot-time', PeepSo::get_asset('aceadmin/js/flot/jquery.flot.time.js'),
			array('flot'), PeepSo::PLUGIN_VERSION, TRUE);
		wp_register_script('peepso-admin-dashboard', PeepSo::get_asset('js/admin-dashboard.js'),
			array('flot'), PeepSo::PLUGIN_VERSION, TRUE);

		wp_localize_script('peepso-admin-dashboard', 'peepsoadmindashboarddata', array(
			'user_id' => get_current_user_id()
		));

		wp_enqueue_script('bootstrap');
		wp_enqueue_script('flot');
		wp_enqueue_script('flot-time');
		wp_enqueue_script('flot-pie');
		wp_enqueue_script('peepso-admin-dashboard');
	}

	/**
	 * Calls add_meta_box for every metabox defined in define_dashboard_metaboxes()
	 */
	public function prepare_metaboxes()
	{
		foreach ($this->dashboard_metaboxes as $metabox) {
			add_meta_box(
				'peepso_dashboard_' . $metabox['name'], // meta box ID
				$metabox['title'],                      // meta box Title
				$metabox['callback'],                   // callback defining the plugin's innards
				'toplevel_page_peepso',                 // screen to which to add the meta box
				isset($metabox['context']) ? $metabox['context'] : 'left', // context
				'default');
		}
	}

	/*
	 * Defines the default metaboxes for the dashboard
	 */
	public function define_dashboard_metaboxes()
	{
		$translated_roles = $this->get_translated_roles();

		$dashboard_metaboxes = array();

		if (count(get_user_meta(get_current_user_id() , 'peepso_admin_newsletter_subscribe')) === 0) {
			$dashboard_metaboxes[] = array(
				'name' => 'mailchimp',
				'title' => __('Get Free eBook Now! ($9.99 Value)', 'peepso-core'),
				'callback' => array(&$this, 'mailchimp_metabox'),
				'context' => 'left'
			);
		}

		$dashboard_metaboxes[] = array(
			'name' => 'user_engagement',
			'title' => __('User Engagement', 'peepso-core'),
			'callback' => array(&$this, 'engagement_metabox'),
			'context' => 'left'
		);

		$dashboard_metaboxes[] = array(
			'name' => 'pending_members',
			'title' => __('Users', 'peepso-core') . ' - ' . $translated_roles['verified'],
			'callback' => array(&$this, 'pending_members_metabox'),
			'context' => 'left'
		);
		$dashboard_metaboxes[] = array(
			'name' => 'reported_items',
			'title' => __('Reported Items', 'peepso-core'),
			'callback' => array(&$this, 'reported_items_metabox'),
			'context' => 'left'
		);
		$dashboard_metaboxes[] = array(
			'name' => 'most_recent',
			'title' => __('Most Recent Content', 'peepso-core'),
			'callback' => array(&$this, 'recent_metabox'),
			'context' => 'right'
		);
		$dashboard_metaboxes[] = array(
			'name' => 'demographic',
			'title' => __('User Demographics', 'peepso-core'),
			'callback' => array(&$this, 'demographic_metabox'),
			'context' => 'right'
		);

		$this->dashboard_metaboxes = $dashboard_metaboxes;

	}

	public function mailchimp_metabox()
	{
		$user = wp_get_current_user();
		?>

		<div class="row">
			<div class="col-xs-12 col-sm-5">
				<img class="img-responsive" src="<?php echo PeepSo::get_asset('images/peepso_ebook.jpg'); ?>">
			</div>
			<div class="col-xs-12 col-sm-7">

				<p><?php _e('This eBook will teach you everything you need to know to start, grow and monetize your online community.', 'peepso-core'); ?></p>
				<p><?php _e('Based on research of hundreds successful and unsuccessful communities.', 'peepso-core'); ?></p>
				<p><?php _e('Subscribe to our mailing list and get the ebook!', 'peepso-core'); ?></p>

				<form action="http://newsletter.peepso.com/index.php/lists/yq637obydc3fd/subscribe" method="post" accept-charset="utf-8" target="_blank">
					<div class="form-group">
						<label><?php _e('First name', 'peepso-core'); ?></label>
						<input type="text" class="form-control" name="FNAME" placeholder="" value="<?php echo $user->user_firstname;?>"/>
					</div>

					<div class="form-group">
						<label><?php _e('Last name', 'peepso-core'); ?></label>
						<input type="text" class="form-control" name="LNAME" placeholder="" value="<?php echo $user->user_lastname;?>"/>
					</div>

					<div class="form-group clearfix">
						<label><?php _e('Email', 'peepso-core'); ?> <span class="required">*</span></label>
						<input type="text" class="form-control" name="EMAIL" placeholder="" value="<?php echo $user->user_email;?>" required />
					</div>

					    <button type="submit" class="btn btn-primary btn-submit btn-subscribe btn-block"><?php _e('SEND ME THIS EBOOK NOW', 'peepso-core'); ?></button>
						<a href="javascript:" class="btn-cancel"><?php echo __('No, thanks', 'peepso-core');?></a>
				</form>

			</div>
		</div>

		<?php
	}

	/**
	 * Renders the demographic metabox on the dashboard
	 */
	public function demographic_metabox()
	{
		$peepso_user_model = PeepSoUser::get_instance();
		// Should this be 'm'?
		$males = $peepso_user_model->get_count_by_gender('m');
		$females = $peepso_user_model->get_count_by_gender('f');
		$unknown = $peepso_user_model->get_count_by_gender('u') + $peepso_user_model->get_count_by_gender('');

		$data = array();
		if (0 < $males)
			$data[] = array(
				'label' => __('Male', 'peepso-core'),
				'value' => $males,
				'icon' => PeepSo::get_asset('images/avatar/user-male-thumb.png'),
				'color' => 'rgb(237,194,64)'
			);
		if (0 < $females)
			$data[] = array(
				'label' => __('Female', 'peepso-core'),
				'value' => $females,
				'icon' => PeepSo::get_asset('images/avatar/user-female-thumb.png'),
				'color' => 'rgb(175,216,248)'
			);
		if (0 < $unknown)
			$data[] = array(
				'label' => __('Unknown', 'peepso-core'),
				'value' => $unknown,
				'icon' => PeepSo::get_asset('images/avatar/user-neutral-thumb.png'),
				'color' => 'rgb(180,180,180)'
			);

		$options = array(
			'series' => array(
				'pie' => array(
					'show' => true,
					'radius' => 100,
					'highlight' => array(
						'opacity' => 0.25
					),
					'label' => array(
						'show' => true
					)
				)
			),
			'legend' => array(
				'show' => true,
				'position' => "ne",
			),
			'grid' => array(
				'hoverable' => true,
				'clickable' => true
			)
		);

		$data = apply_filters('peepso_admin_dashboard_demographic_data', $data);
		$options = apply_filters('peepso_admin_dashboard_demographic_options', $options);

		echo '<script>', PHP_EOL;
		echo 'var demographic_data = ', json_encode($data), ';', PHP_EOL;
		echo 'var demographic_options = ', json_encode($options), ';', PHP_EOL;
		echo '</script>', PHP_EOL;
		echo '<div id="demographic-pie"></div>', PHP_EOL;
		echo '<div class="hr hr-double"></div>', PHP_EOL;
		echo '<div class="clearfix">', PHP_EOL;

		$demographic_ctr = 0;

		foreach ($data as $demographic) {
			echo '<div class="col-md-4 text-center">', PHP_EOL;

			if (isset($demographic['icon']))
				echo '      <img src="', $demographic['icon'], '" class= "inline avg-avatar img-circle"/>', PHP_EOL;

			echo    '<h3 class="inline">', $demographic['value'], '</h3>', PHP_EOL;
			echo    '<span class="block">', $demographic['label'], '</span>', PHP_EOL;
			echo '</div>';

			if (count($data) === ++$demographic_ctr) {
				echo '</div>';
				echo '<div class="hr hr-double"></div>';
				echo '<div class="clearfix">';
				$demographic_ctr = 0;
			}
		}

		echo '</div>', PHP_EOL; // </clearfix>
	}

	/*
	 * Display the content of the Most Recent metabox and gathers additional tabs from other plugins
	 */
	public function recent_metabox()
	{
		// This metabox's default tabs
		$tabs = array(
			array(
				'id' => 'recent-posts',
				'title' => __('Posts', 'peepso-core'),
				'callback' => array(&$this, 'recent_posts_tab')
			),
			array(
				'id' => 'recent-comments',
				'title' => __('Comments', 'peepso-core'),
				'callback' => array(&$this, 'recent_comments_tab')
			),
			array(
				'id' => 'recent-members',
				'title' => __('Members', 'peepso-core'),
				'callback' => array(&$this, 'recent_members_tab')
			)
		);

		$tabs = apply_filters('peepso_admin_dashboard_recent_metabox_tabs', $tabs);

		echo '<ul class="nav nav-tabs">', PHP_EOL;

		$first = TRUE;
		foreach ($tabs as $tab) {
			echo '<li class="', ($first ? 'active' : ''), '">
					<a href="#', $tab['id'], '" data-toggle="tab">', $tab['title'], '</a>
				</li>', PHP_EOL;

			$first = FALSE;
		}

		echo '</ul>', PHP_EOL;

		$first = TRUE;
		echo '<div class="tab-content">', PHP_EOL;

		foreach ($tabs as $tab) {
			echo '<div class="tab-pane ', ($first ? 'active' : ''), '" id="', $tab['id'], '">', PHP_EOL;
			echo call_user_func($tab['callback']);
			echo '</div>', PHP_EOL;

			$first = FALSE;
		}

		echo '</div>', PHP_EOL;
	}

	/*
	 * Display the content of the Posts tab under the Most Recent metabox
	 */
	public function recent_posts_tab()
	{
		$activities = PeepSoActivity::get_instance();

		$posts = $activities->get_all_activities(
			'post_date_gmt',
			'desc',
			5,
			0,
			array(
				'post_type' => PeepSoActivityStream::CPT_POST
			)
		);

		if (0 === $posts->post_count) {
			echo __('No recent posts.', 'peepso-core');
		} else {
			add_filter('filter_remove_location_shortcode', array(&$this, 'filter_remove_location_shortcode'));

			echo '<div class="dialogs">', PHP_EOL;

			foreach ($posts->posts as $post) {
				$type = get_post_type_object($post->post_type);
				$user = PeepSoUser::get_instance($post->post_author);

				echo '<div class="itemdiv dialogdiv">' , PHP_EOL;
				echo '  <div class="user">' , PHP_EOL;
				echo '      <img title="', $user->get_username(), '" alt="', esc_attr($user->get_username()), '" src="', $user->get_avatar(), '" />', PHP_EOL;
				echo '  </div>', PHP_EOL;
				echo '  <div class="body">', PHP_EOL;
				echo '      <div class="time">', PHP_EOL;
				echo '          <i class="ace-icon fa fa-clock-o"></i>', PHP_EOL;
				echo '          <span class="green">', PeepSoTemplate::time_elapsed(strtotime($post->post_date_gmt), current_time('timestamp')), ' </span>', PHP_EOL;
				echo '      </div>', PHP_EOL;
				echo '      <div class="name">', PHP_EOL;
				echo '          <a href="', $user->get_profileurl(), '" title="', esc_attr(__('View profile', 'peepso-core')), '" target="_blank">', $user->get_fullname(), '</a>', PHP_EOL;
				echo '      </div>', PHP_EOL;
				echo '      <div class="text">', ucfirst($type->labels->activity_action), ': "', substr(strip_tags(apply_filters('filter_remove_location_shortcode', $post->post_content)), 0, 30), '"', PHP_EOL;
				echo '      </div>', PHP_EOL;
				echo '      <div class="tools">', PHP_EOL;
				echo '          <a href="', PeepSo::get_page('activity'), '?status/', $post->post_title, '/" title="', esc_attr(__('View post', 'peepso-core')), '" target="_blank" class="btn btn-minier btn-info">', PHP_EOL;
				echo '              <i class="icon-only ace-icon fa fa-share"></i>', PHP_EOL;
				echo '          </a>', PHP_EOL;
				echo '      </div>', PHP_EOL;
				echo '  </div>', PHP_EOL;
				echo '</div>', PHP_EOL;
			}

			echo '</div>', PHP_EOL;

			echo '<div class="center cta-full">
					<a href="', admin_url('admin.php?page=peepso-activities'), '">',
			__('See all Activities', 'peepso-core'), ' &nbsp;
						<i class="fa fa-arrow-right"></i>
					</a>
				</div>', PHP_EOL;
		}
	}

	/*
	 * Display the content of the Comments tab under the Most Recent metabox
	 */
	public function recent_comments_tab()
	{
		$activities = PeepSoActivity::get_instance();

		$comments = $activities->get_all_activities(
			'post_date_gmt',
			'desc',
			5,
			0,
			array(
				'post_type' => PeepSoActivityStream::CPT_COMMENT
			)
		);

		if (0 === $comments->post_count) {
			echo __('No recent posts.', 'peepso-core');
		} else {
			echo '<div class="dialogs">', PHP_EOL;

			foreach ($comments->posts as $post) {
				$type = get_post_type_object($post->post_type);
				$user = PeepSoUser::get_instance($post->post_author);

				echo '<div class="itemdiv dialogdiv">', PHP_EOL;
				echo '  <div class="user">', PHP_EOL;
				echo '      <img title="', esc_attr($user->get_username()), '" alt="', esc_attr($user->get_username()), '" src="', $user->get_avatar(), '" />', PHP_EOL;
				echo '  </div>', PHP_EOL;
				echo '  <div class="body">', PHP_EOL;
				echo '      <div class="time">', PHP_EOL;
				echo '          <i class="ace-icon fa fa-clock-o"></i>', PHP_EOL;
				echo '          <span class="green">', PeepSoTemplate::time_elapsed(strtotime($post->post_date_gmt), current_time('timestamp')), '</span>', PHP_EOL;
				echo '      </div>', PHP_EOL;
				echo '      <div class="name">', PHP_EOL;
				echo '          <a href="', $user->get_profileurl(), '" title="', esc_attr(__('View profile', 'peepso-core')), '" target="_blank">', $user->get_fullname(), '</a>', PHP_EOL;
				echo '      </div>', PHP_EOL;
				echo '      <div class="text">', PHP_EOL;
				echo '          <i class="fa fa-quote-left"></i>', PHP_EOL;
				echo            substr(strip_tags($post->post_content), 0, 30);
				echo '      </div>', PHP_EOL;
				echo '      <div class="tools">', PHP_EOL;
				echo '          <a href="', PeepSo::get_page('activity'), '?status/', $post->post_title, '/" title="', esc_attr(__('View comment', 'peepso-core')), '" target="_blank" class="btn btn-minier btn-info">', PHP_EOL;
				echo '              <i class="icon-only ace-icon fa fa-share"></i>', PHP_EOL;
				echo '          </a>', PHP_EOL;
				echo '      </div>', PHP_EOL;
				echo '  </div>', PHP_EOL;
				echo '</div>', PHP_EOL;
			}

			echo '</div>', PHP_EOL;
		}
	}

	/*
	 * Display the content of the Members tab under the Most Recent metabox
	 */
	public function recent_members_tab()
	{
		global $wp_version, $wpdb;

		$args = array(
			'number' => 10,
			'orderby' => 'user_registered',
			'order' => 'DESC',
			'meta_key' => $wpdb->prefix . 'capabilities',
			'meta_value' => 'subscriber',
			'meta_compare' => 'LIKE'
		);

		$user_query = new WP_User_Query($args);

		if (0 === $user_query->total_users) {
			echo __('No users found', 'peepso-core');
		} else {
			$legacy_edit_link = (version_compare($wp_version, '3.5') < 0);

			foreach ($user_query->results as $user) {
				$user = PeepSoUser::get_instance($user->ID);

				if ($legacy_edit_link)
					$edit_link = admin_url('user-edit.php?user_id=' . $user->get_id());
				else
					$edit_link = get_edit_user_link($user->get_id());

				echo '<div class="itemdiv memberdiv clearfix">', PHP_EOL;
				echo '  <div class="user">', PHP_EOL;
				echo '      <a href="', $user->get_profileurl(), '" title="', esc_attr(__('View profile', 'peepso-core')), '" target="_blank">', PHP_EOL;
				echo '          <img alt="', esc_attr($user->get_firstname()), '" src="', $user->get_avatar(), '">', PHP_EOL;
				echo '      </a>', PHP_EOL;
				echo '  </div>', PHP_EOL;
				echo '  <div class="body">', PHP_EOL;
				echo '      <div class="name">', PHP_EOL;
				echo '          <a href="', $user->get_profileurl(), '" title="', esc_attr(__('View profile', 'peepso-core')), '" target="_blank">', $user->get_fullname(), '</a>', PHP_EOL;
				echo '      </div>', PHP_EOL;
				echo '      <div class="time">', PHP_EOL;
				echo '          <i class="ace-icon fa fa-clock-o"></i>', PHP_EOL;
				echo '          <span class="green">', PeepSoTemplate::time_elapsed(strtotime($user->get_date_registered()), current_time('timestamp')), '</span>', PHP_EOL;
				echo '      </div>', PHP_EOL;
				echo '      <div>', PHP_EOL;
				echo '          <span class="label label-success arrowed-in">', implode(', ', $user->get_role()), '</span>', PHP_EOL;
				echo '          <a href="', $edit_link, '" title="', esc_attr(__('Edit this user', 'peepso-core')), '"><i class="ace-icon fa fa-edit"></i></a>', PHP_EOL;
				echo '      </div>', PHP_EOL;
				echo '  </div>', PHP_EOL;
				echo '</div>', PHP_EOL;
			}

			echo '<div class="clearfix"></div>', PHP_EOL;
		}

		echo '<div class="center cta-full">
			<a href="', admin_url('users.php'), '">',
		__('See all Members', 'peepso-core'), ' &nbsp;
				<i class="fa fa-arrow-right"></i>
			</a>
		</div>', PHP_EOL;
	}



	private static function plugin_exists($filename, $class)
	{
		if(class_exists($class)) {
			return true;
		}
	}

	/*
	 * Displays the User Engagement metabox and gathers additional tabs from other plugins
	 */
	public function engagement_metabox()
	{
		add_filter(
			'peepso_admin_dashboard_engagement-' . PeepSoActivity::MODULE_ID . '_stat_types',
			array(&$this, 'stream_stat_types'));
		// This metabox's default tabs
		$tabs = array(
			array(
				'id' => 'engagment-stream',
				'title' => __('Stream', 'peepso-core'),
				'callback' => array(&$this, 'engagement_tab'),
				'module_id' => PeepSoActivity::MODULE_ID
			)
		);

		$tabs = apply_filters('peepso_admin_dashboard_engagement_metabox_tabs', $tabs);

		echo '<ul class="nav nav-tabs">';

		$first = TRUE;
		foreach ($tabs as $tab) {
			echo '<li class="', ($first ? 'active' : ''), '" data-module-id=', $tab['module_id'], '>
					<a href="#', $tab['id'], '" data-toggle="tab">', $tab['title'], '</a>
				</li>';

			$first = FALSE;
		}

		echo '</ul>';

		$first = TRUE;
		echo '<div class="tab-content">';

		foreach ($tabs as $tab) {
			echo '<div class="tab-pane ', ($first ? 'active' : ''), '" id="', $tab['id'], '">';
			echo call_user_func_array($tab['callback'], array($tab['module_id']));
			echo '</div>';

			$first = FALSE;
		}

		echo '</div>';
	}

	/*
	 * Renders the contents of the tab under the User Engagement metabox
	 * @param string $module_id MODULE_ID of the plugin from which the data will be referencing
	 */
	public function engagement_tab($module_id)
	{
		$date_range_filters = apply_filters('peepso_admin_dashboard_' . $module_id . '_date_range',
			array(
				'this_week' => __('This week', 'peepso-core'),
				'last_week' => __('Last week', 'peepso-core'),
				'this_month' => __('This month', 'peepso-core'),
				'last_month' => __('Last month', 'peepso-core'),
			)
		);

		$stat_types = apply_filters('peepso_admin_dashboard_engagement-' . $module_id . '_stat_types', array());

		// Content is called via ajax PeepSoActivity::get_graph_data()
		echo '<div class="container-fluid">
				<div class="row">
					<div class="col-xs-12">
						<select name="engagement_', $module_id, '_date_range" class="engagement_date_range">', PHP_EOL;

		foreach ($date_range_filters as $val => $date_range)
			echo '<option value="', $val, '">', $date_range, '</option>', PHP_EOL;

		echo '          </select>
					</div>
				</div>
				<div class="row">
					<div class="col-xs-12 graph-container"></div>
					<div class="col-xs-12 series-container">', PHP_EOL;

		foreach ($stat_types as $stat) {
			echo '<label>
					<input value="', $stat['stat_type'], '" type="checkbox" name="stats[]" checked="checked" id="id', $stat['label'], '" style="margin:0">
					<span class="lbl" for="id', $stat['label'], '">', ucwords($stat['label']), '</span> &nbsp; &nbsp;
				</label>', PHP_EOL;
		}

		echo '      </div>
				</div>
			</div>', PHP_EOL;
	}

	/**
	 * Define which stats to track on the dashboard for the 'activity' module
	 * @param array $types
	 * @return array Stat types
	 */
	public function stream_stat_types($types)
	{
		return array(
			array(
				'label' => __('posts', 'peepso-core'),
				'stat_type' => PeepSoActivityStream::CPT_POST
			),
			array(
				'label' => __('comments', 'peepso-core'),
				'stat_type' => PeepSoActivityStream::CPT_COMMENT
			),
			array(
				'label' => __('likes', 'peepso-core'),
				'stat_type' => 'likes'
			)
		);
	}

	/**
	 * Display pending members who require Admin Activation
	 */
	public function pending_members_metabox()
	{
		global $wp_version, $wpdb;

		$args = array(
			'number' => 6,
			'orderby' => 'user_registered',
			'order' => 'DESC',
			'peepso_roles' => 'verified'
		);

		add_action('pre_user_query', array(PeepSo::get_instance(), 'filter_user_roles'));

		$user_query = new WP_User_Query($args);

		if (!$user_query->total_users) {
			echo __('The list is empty', 'peepso-core') . ' ';
		} else {
			$legacy_edit_link = (version_compare($wp_version, '3.5') < 0);

			foreach ($user_query->get_results() as $user_item) {
				$user = PeepSoUser::get_instance($user_item->ID);
				$nonce = wp_create_nonce('update-role-nonce_' . $user_item->ID);

				echo '<div class="itemdiv memberdiv clearfix">', PHP_EOL;
				echo '  <div class="user">', PHP_EOL;
				echo '      <a href="', $user->get_profileurl(), '" title="', esc_attr(__('View profile', 'peepso-core')), '" target="_blank">', PHP_EOL;
				echo '          <img alt="', esc_attr($user->get_firstname()), '" src="', $user->get_avatar(), '">', PHP_EOL;
				echo '      </a>', PHP_EOL;
				echo '  </div>', PHP_EOL;
				echo '  <div class="body">', PHP_EOL;
				echo '      <div class="name">', PHP_EOL;
				echo '          <a href="', $user->get_profileurl(), '" title="', esc_attr(__('View profile', 'peepso-core')), '" target="_blank">', $user->get_fullname() , '</a>', PHP_EOL;
				echo '      </div>', PHP_EOL;
				echo '      <div class="email">', PHP_EOL;
				echo '          <span>', $user->get_email(), '</span>', PHP_EOL;
				echo '      </div>', PHP_EOL;
				echo '      <div class="approve-links">', PHP_EOL;
				echo '          <a class="btn btn-xs btn-success" href="', admin_url('admin.php?page=peepso&action=update-user-role&set=member&id=' . $user_item->ID . '&_wpnonce=' . $nonce), '" title="', esc_attr(__('Approve', 'peepso-core')), '">', esc_attr(__('Approve', 'peepso-core')), '</a>', PHP_EOL;
				echo '          <a class="btn btn-xs" href="', admin_url('admin.php?page=peepso&action=update-user-role&set=ban&id=' . $user_item->ID . '&_wpnonce=' . $nonce), '" title="', esc_attr(__('Dismiss and Ban', 'peepso-core')), '">', esc_attr(__('Dismiss and Ban', 'peepso-core')), '</a>', PHP_EOL;
				echo '      </div>', PHP_EOL;
				echo '  </div>', PHP_EOL;
				echo '</div>', PHP_EOL;
			}

			echo '<div class="clearfix"></div>', PHP_EOL;
		}

		echo '<div class="center cta-full">
			<a href="', admin_url('users.php?psrole=verified'), '">',
		__('See all Pending Members', 'peepso-core'), ' (' , $user_query->total_users . ') &nbsp;
				<i class="fa fa-arrow-right"></i>
			</a>
		</div>', PHP_EOL;
	}

	function filter_remove_location_shortcode($content)
	{
		$content = str_replace('[/peepso_geo]', '', $content);
		return preg_replace('/\[peepso_geo(?:.*?)\]/', '', $content);
	}

	function reported_items_metabox()
	{
		$rep = new PeepSoReport();

		$report_items = $rep->get_reports('', 'DESC', 0, 5);

		if ($report_items)
		{
			add_filter('filter_remove_location_shortcode', array(&$this, 'filter_remove_location_shortcode'));
			echo '<div class="psa-list--reported">';
			foreach ($report_items as $item)
			{
				$nonce = wp_create_nonce('update-report-nonce_' . $item['rep_id']);

				echo '<div class="psa-list__item">';
				echo '<div class="psa-list--reported__amount" title="', __('Amount of reports', 'peepso-core'), '">' . $item['rep_user_count'] . '</div>';
				echo '<div class="psa-list--reported__reason"><span>' . $item['rep_reason'] . '</span></div>';
				echo '<div class="psa-list--reported__content">';

				switch ($item['rep_module_id'])
				{
					case 0 :
						$user = PeepSoUser::get_instance($item['rep_external_id']);
						echo __('Profile', 'peepso-core'), ' : ' . $user->get_fullname();
						break;
					default :
						echo __('Content', 'peepso-core'), ' : ' . apply_filters('filter_remove_location_shortcode', $item['post_content']);
						break;
				}
				echo '</div>';

				echo '<div class="psa-list--reported__action">';
				switch ($item['rep_module_id'])
				{
					case 0 :
						echo '<a class="psa-list--reported__link" href="' . $user->get_profileurl() . '" target="_blank">' . $user->get_fullname() . ' <i class="fa fa-external-link"></i></a>';
						echo '<a class="btn btn-xs" href="' . admin_url('admin.php?page=peepso&action=update-report&set=dismiss&id=' . $item['rep_id'] . '&_wpnonce=' . $nonce) . '">Dismiss</a>';
						echo '<a class="btn btn-xs btn-danger" href="' . admin_url('admin.php?page=peepso&action=update-report&set=ban&id=' . $item['rep_id'] . '&_wpnonce=' . $nonce) . '">Ban Profile</a>';
						break;
					default :
						echo '<a class="psa-list--reported__link" href="' . PeepSo::get_page('activity') . '?status/' . $item['post_title'] . '/" target="_blank">' . $item['post_title'] . ' <i class="fa fa-external-link"></i></a>';
						echo '<a class="btn btn-xs" href="' . admin_url('admin.php?page=peepso&action=update-report&set=dismiss&id=' . $item['rep_id'] . '&_wpnonce=' . $nonce) . '">Dismiss</a>';
						echo '<a class="btn btn-xs btn-danger" href="' . admin_url('admin.php?page=peepso&action=update-report&set=unpublish&id=' . $item['rep_id'] . '&_wpnonce=' . $nonce) . '">Unpublish</a>';
						break;
				}
				echo '</div>';
				echo '</div>';
			}
			echo '</div>';
		} else
		{
			echo __('The list is empty', 'peepso-core') . ' ';
		}

		echo '<div class="center cta-full">
					<a href="', admin_url('admin.php?page=peepso-reports'), '">',
						__('See all Reported Items', 'peepso-core'),
						' (' , $rep->get_num_reported_items() . ') &nbsp;
						<i class="fa fa-arrow-right"></i>
					</a>
			</div>', PHP_EOL;
	}

	function update_report($screen)
	{
		if ($screen->base == 'toplevel_page_peepso')
		{
			$input = new PeepSoInput();

			$action = $input->val('action');
			$set = $input->val('set');
			$id = $input->val('id');
			$_wpnonce = $input->val('_wpnonce');

			if (
				$action === 'update-report' &&
				($set === 'dismiss' || $set == 'ban' || $set == 'unpublish') &&
				wp_verify_nonce($_wpnonce, 'update-report-nonce_' . $id)
			)
			{
				$rep = new PeepSoReport();
				switch ($set)
				{
					case 'dismiss' :
						if ($rep->dismiss_report($id))
							$this->add_notice(__('Report dismissed.', 'peepso-core'), 'note');
						break;
					case 'ban' :
						if ($rep->ban_user($id))
							$this->add_notice(__('User banned.', 'peepso-core'), 'note');
						break;
					case 'unpublish' :
						if ($rep->unpublish_report($id))
							$this->add_notice(__('Post unpublished.', 'peepso-core'), 'note');
						break;
				}


			} else if (isset($action) && $action === 'update-report')
			{
				$this->add_notice(__('Invalid action', 'peepso-core'), 'error');
			}
		}
	}

	function register_notice() {
		global $pagenow;

		if ($pagenow == 'admin.php' && $_GET['page'] == 'peepso') {
			$optionName = 'peepso_register';

			$registrationHide	 = filter_input(INPUT_GET, 'peepso_registration_hide' );
			if ( $registrationHide ) {
				update_option($optionName, 1);
			}

			$post = filter_input_array(INPUT_POST);
			$domain = 'http://www.peepso.com';

			if (!empty($post) && !empty($post['register_nonce'])) {

				$nonceCheck = wp_verify_nonce($post['register_nonce'], 'peepso_register');
				if ($nonceCheck) {

					unset($post['register_nonce']);
					$jsonData = wp_json_encode(array($post));

					$args = array(
						'body' => array(
							'jsonData' => $jsonData
						)
					);

					$href		 = str_replace('http', 'https', $domain) . '/wp-admin/admin-ajax.php?action=add_user&cminds_json_api=add_user';
					$response	 = wp_remote_post($href, $args);

					if (!is_wp_error($response))
					{
						$result = json_decode(wp_remote_retrieve_body($response), true);
						if ($result && 1 === $result['result'])
						{
							update_option($optionName, 1);
						}
					} else {
						$args['sslverify'] = false;
						$href				 = $domain . '/wp-admin/admin-ajax.php?action=add_user&cminds_json_api=add_user';
						$response			 = wp_remote_post($href, $args);

						if (!is_wp_error($response))
						{
							$result = json_decode(wp_remote_retrieve_body($response), true);
							if ($result && 1 === $result['result'])
							{
								update_option($optionName, 1);
							}
						} else {
							$message = 'Registered fields: <br/><table>';
							foreach ($post as $key => $value) {
								if (!in_array($key, array('product_name', 'email', 'hostname'))) {
									continue;
								}
								$message .= '<tr><td>' . $key . '</td><td>' . $value . '</td></tr>';
							}
							$message .= '</table>';

							add_filter('wp_mail_content_type', array(&$this, 'set_mail_content_type'));
							wp_mail('info@peepso.com', 'PeepSo Product Registration', $message);
							remove_filter('wp_mail_content_type', array(&$this, 'set_mail_content_type'));
						}
					}
				}
			}


			$fields = array(
				'product_name'	 => 'peepso',
				'remote_url'	 => get_bloginfo('wpurl'),
				'remote_ip'		 => $_SERVER['SERVER_ADDR'],
				'remote_country' => '',
				'remote_city'	 => '',
				'email'			 => get_bloginfo('admin_email'),
				'hostname'		 => get_bloginfo('wpurl'),
				'username'		 => '',
			);

			$output = '';
			foreach ($fields as $key => $value) {
				$output .= sprintf( '<input type="hidden" name="%s" value="%s" />', $key, $value );
			}

			$registrationHidden = get_option($optionName);
			if (!$registrationHidden)
			{
				$dashboard_main = __('Once registered, you will receive updates and special offers from PeepSo. We will only send once, your administrator\'s e-mail and site URL to PeepSo server.','peepso');
				$dashboard_info = __('No additional information will be ever collected or sent.', 'peepso');
				?>
				<div class="cminds_registration_wrapper">
					<div class="cminds_registration">
						<div class="cminds_registration_action">
							<form method="post" action="">
								<?php
								wp_nonce_field('peepso_register', 'register_nonce');
								echo $output;
								?>
								<input class="button button-primary" type="submit" value="Register Your Copy" />
								<div class="no-registration">
									<a class="cminds-registration-hide-button" href="<?php echo add_query_arg( array( 'peepso_registration_hide' => 1 ), remove_query_arg( 'peepso_registration_hide' ) ); ?>"><?php echo __("I don't want to register", 'peepso-core'); ?></a>
								</div>
							</form>
						</div>
						<div class="cminds_registration_text">
								<span>
									<?php echo $dashboard_main; ?>
								</span>
							<span>
									<p><i class="infobox-icon dashicons dashicons-info"></i><?php echo $dashboard_info; ?></p>
								</span>
						</div>
					</div>
				</div>
				<?php
			}
		}
	}

	function modify_plugin_action_links($links, $file) {
		/*
		 * This HTML element is used to identify the correct plugin when attaching an event to its Deactivate link.
		 */
		if ( isset( $links[ 'deactivate' ] ) ) {
			$links[ 'deactivate' ] .= '<i class="peepso-slug" data-slug="cma"></i>';
		}

		return $links;
	}

	function show_deactivation_feedback_dialog()
	{
		global $pagenow;
		if ('plugins.php' === $pagenow)
		{
			PeepSoTemplate::exec_template('admin', 'deactivation_feedback_modal');
		}
	}

	function submit_uninstall_reason()
	{
		if (empty($_POST['plugin_slug']) || empty($_POST['deactivation_reason'])) {
			exit;
		}

		$reason = isset($_REQUEST['deactivation_reason']) ? trim(stripslashes($_REQUEST['deactivation_reason'])) : '';

		$fields = array(
			'product_name'	 => 'peepso',
			'remote_url'	 => get_bloginfo('wpurl'),
			'email'			 => get_bloginfo('admin_email'),
		);

		$registered	 = get_option('peepso_register') ? ' (registered)' : '';
		$message	 = '<p>The ' . $fields['product_name'] . ' has been deactivated on ' . $fields['remote_url'] . ' by ' . $fields['email'] . $registered . '.</p> <p>The reason was:</p> <p><strong>' . $reason . '</strong></p>';

		add_filter('wp_mail_content_type', array(&$this, 'set_mail_content_type'));
		$result = wp_mail('feedback@peepso.com', 'PeepSo Deactivation Feedback', $message);
		remove_filter('wp_mail_content_type', array(&$this, 'set_mail_content_type'));

		// Print '1' for successful operation.
		echo 1;
		exit;
	}

	function set_mail_content_type() {
		return "text/html";
	}

	public static function extensions() {
		wp_enqueue_media();
		wp_enqueue_script('peepso-admin-config');
		PeepSoAdmin::admin_header(__('Addons', 'peepso-core'));

		wp_register_script('peepso-admin-extensions', PeepSo::get_asset('js/admin-extensions.min.js'),
			array('jquery', 'peepso'), PeepSo::PLUGIN_VERSION, TRUE);

		wp_localize_script('peepso-admin-extensions', 'peepsoadminextdata', array(
			'label_hide_installed' => __('Hide Installed Plugins', 'peepso-core'),
			'label_show_installed' => __('Show Installed Plugins', 'peepso-core')
		));

		wp_enqueue_script('peepso-admin-extensions');

		$plugins = get_transient('peepso_plugins');

		PeepSoTemplate::exec_template('admin', 'extensions', array(
				'plugins' => $plugins,
				'plugin_url' => self::PEEPSO_URL . '/addons'
			)
		);
	}

	/*
	 * check if peepso directory is writable
	 */
	public function is_peepso_dir_writable_check()
	{
		if (isset($_GET['filesystem'])) {
			set_transient('peepso_dir_writable', 2, 15);
			return;
		}
		$check = intval(get_transient('peepso_dir_writable'));
		// 0 : empty value
		// 1 : not writable
		// 2 : writable

		if (!$check) {
			$dir = PeepSo::get_peepso_dir();
			$is_writable = wp_is_writable($dir);
			set_transient('peepso_dir_writable', ($is_writable ? 2 : 1), 15 );
			$check = intval(get_transient('peepso_dir_writable'));
		}

		if ($check === 1) {
			add_action('admin_notices', array(&$this, 'peepso_dir_not_writable_notice'));
		}
	}

	/*
	 * show notice when peepso directory is not writable
	 */
	public function peepso_dir_not_writable_notice()
	{
		?>
		<div class="error">
			<strong>
				<?php echo sprintf(__('The <a href="%s">file system directory</a> seems to be unwritable. Please adjust it. If you don\'t feel comfortable doing it yourself, please contact PeepSo support ', 'peepso-core'), admin_url() . 'admin.php?page=peepso_config&tab=advanced'); ?>
				<a href="<?php echo self::PEEPSO_URL . '/my-account';?>" target="_blank">
					<?php _e('here', 'msgso');?>
				</a>
			</strong>
		</div>
		<?php
	}

	/*
	 * delete transient for peepso directory is writable
	 * after config on advanced tab saved
	 */
	public function after_save_advanced()
	{
		delete_transient('peepso_dir_writable');
	}

	/*
	 * clear peepso transient
	 */
	public function clear_transient() {
		global $wpdb;
		$wpdb->query("DELETE FROM `{$wpdb->prefix}options` WHERE `option_name` LIKE ('%_transient%peepso_cache_%');");
	}

	public function get_plugin_list() {

        $check_plugins = get_transient('peepso_plugins');

		if (empty($check_plugins)) {
			$response = wp_remote_get(self::PEEPSO_URL . '/edd-api/products/?number=-1');
			if (is_array($response)) {
                $plugins = json_decode($response['body']);
                set_transient('peepso_plugins', $plugins, HOUR_IN_SECONDS);
            }
        }
	}

	public function new_plugin_notice() {
		?>
		<div class="notice updated is-dismissible peepso-new-plugin" >
			<p><?php echo sprintf(__('New Plugin for PeepSo is available! See it in the <a href="%s">Addons</a> page.', 'peepso-core'), admin_url('admin.php?page=peepso-extensions')); ?></p>
		</div>
		<?php
	}

	public function dismiss_new_plugin_notice() {
		PeepSoConfigSettings::get_instance()->set_option(
			'new_plugin', 0
		);
	}

	public function count_plugin($plugins) {
		return count(array_filter($plugins->products, function($plugin) {
			return strpos($plugin->info->slug, 'translation') === FALSE && strpos($plugin->info->slug, 'bundle') === FALSE;
		}));
	}

	/**
	 * Fires after the user's role has changed.
	 * @param int    $user_id   The user ID.
	 * @param string $role      The new role.
	 * @param array  $old_roles An array of the user's previous roles.
	 */
//  public function set_user_role($user_id, $role, $old_roles)
//  {
//      if ('peepso_member' === $role && in_array('peepso_verified', $old_roles)) {
//          $user = PeepSoUser::get_instance();
//          $user->approve_user();
//      }
//  }
}

// EOF
