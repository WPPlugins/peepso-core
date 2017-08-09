<?php

class PeepSoGeneral
{
	protected static $_instance = NULL;

	public $template_tags = array(
		'access_types',				// options for post/content access types
		'navbar',					// output the navigation bar
		'post_types',				// options for post types
		'show_error',				// outputs a WP_Error object
		'navbar_mobile',
		'navbar_sidebar_mobile',
	);

	private function __construct()
	{
	}

	/*
	 * return singleton instance
	 */
	public static function get_instance()
	{
		if (self::$_instance === NULL)
			self::$_instance = new self();
		return (self::$_instance);
	}

	/* return propeties for the profile page
	 * @param string $prop The name of the property to return
	 * @return mixed The value of the property
	 */
	public function get_prop($prop)
	{
	}

	//// implementation of template tags

	public function access_types()
	{
		$access = array(
			'public' => array(
				'icon' => 'globe',
				'label' => __('Public', 'peepso-core'),
				'descript' => __('Can be seen by everyone, even if they\'re not members', 'peepso-core'),
			),
			'site_members' => array(
				'icon' => 'users',
				'label' => __('Site Members', 'peepso-core'),
				'descript' => __('Can be seen by registered members', 'peepso-core'),
			),
			'friends' => array(
				'icon' => 'user',
				'label' => __('Friends', 'peeps'),
				'descript' => __('Can be seen by your friends', 'peepso-core'),
			),
			'me' => array(
				'icon' => 'lock',
				'label' => __('Only Me', 'peepso-core'),
				'descript' => __('Can only be seen by you', 'peepso-core'),
			)
		);

		foreach ($access as $name => $data) {
			echo '<li data-priv="', $name, '">', PHP_EOL;

			echo '<p class="reset-gap">';
			echo '<i class="ps-icon-', $data['icon'], '"></i>', PHP_EOL;
			echo $data['label'], "</p>\r\n";
			echo '<span>', $data['descript'], "</span></li>", PHP_EOL;
		}
	}

	// Displays the frontend navbar	for mobile
	public function navbar_mobile()
	{
		// Put the filter at the last of the queue so we get them all.
		add_filter('peepso_navbar_menu', array(&$this, 'parse_mobile_menus'), 100);
		$this->navbar();
		remove_filter('peepso_navbar_menu', array(&$this, 'parse_mobile_menus'), 100);
	}

	// Displays the frontend navbar	for mobile
	public function navbar_sidebar_mobile()
	{
		// Put the filter at the last of the queue so we get them all.
		add_filter('peepso_navbar_menu', array(&$this, 'parse_mobile_sidebar'), 100);
		$this->navbar();
		remove_filter('peepso_navbar_menu', array(&$this, 'parse_mobile_sidebar'), 100);
	}

	/**
	 * Tries to determine which menus are available on the navigation header.
	 * Returns menus with icons.
	 * @param  array $menus
	 * @return array
	 */
	public function parse_mobile_menus($menus)
	{
		foreach ($menus as $index => &$menu) {
			if (!isset($menu['icon'])) {
				unset($menus[$index]);
				continue;
			}

			if (isset($menu['label']))
				unset($menu['label']);

			$menu['class'] = str_replace('', '', isset($menu['class']) ? $menu['class'] : '');
			$menu['class'] = str_replace('ps-right', '', $menu['class']);
		}

		$menus = apply_filters('peepso_parse_mobile_menus', $menus);

		return $menus;
	}

	/**
	 * Tries to determine which menus are available on the navigation sidebar
	 * Returns menus with icons.
	 * @param  array $menus
	 * @return array
	 */
	public function parse_mobile_sidebar($menus)
	{
		// Profile + Segments are separate on desktop, squish them together for mobile
		$menus['profile']['label'] = $menus['profile-home']['label'];
		unset($menus['profile-home']);

		foreach ($menus as $index => &$menu) {
			if (isset($menu['icon'])) {
				unset($menus[$index]);
				continue;
			}

			if (!isset($menu['label'])) {
				$menu['label'] = 'TODO';
			}

			if( array_key_exists('menu', $menu) && count($menu['menu']) ) {

				foreach($menu['menu'] as &$submenu) {
					if (isset($submenu['icon'])) {
						unset($submenu['icon']);
					}
				}
			}

			$menu['class'] = str_replace('', '', isset($menu['class']) ? $menu['class'] : '');
			$menu['class'] = str_replace('ps-right', '', $menu['class']);
		}

		return $menus;
	}

	// toolbar menu
	public function toolbar_menu()
	{
		// Put the filter at the last of the queue so we get them all.
		add_filter('peepso_navbar_menu', array(&$this, 'parse_toolbar_menu'), 100);
		$this->navbar();
		remove_filter('peepso_navbar_menu', array(&$this, 'parse_toolbar_menu'), 100);
	}

	/**
	 * Tries to determine which menus are available on the toolbar menu
	 * Returns menus with icons.
	 * @param  array $menus
	 * @return array
	 */
	public function parse_toolbar_menu($menus)
	{
		unset($menus['profile-home']);
		unset($menus['profile']);

		foreach ($menus as $index => &$menu) {
			if (isset($menu['icon']) && $index != 'home') {
				unset($menus[$index]);
				continue;
			}
		}
		return $menus;
	}

	// toolbar menu
	public function toolbar_notifications()
	{
		// Put the filter at the last of the queue so we get them all.
		add_filter('peepso_navbar_menu', array(&$this, 'parse_toolbar_notifications'), 100);
		$this->navbar();
		remove_filter('peepso_navbar_menu', array(&$this, 'parse_toolbar_notifications'), 100);
	}

	/**
	 * Tries to determine which menus are available on the toolbar menu
	 * Returns menus with icons.
	 * @param  array $menus
	 * @return array
	 */
	public function parse_toolbar_notifications($menus)
	{
		foreach ($menus as $index => &$menu) {
			if ((!isset($menu['icon']) || $index == 'home') && (!in_array($index, array('profile', 'profile-home')))) {
				unset($menus[$index]);
				continue;
			}
		}
		return $menus;
	}

	// Displays the frontend navbar
	public function navbar()
	{
		$note = PeepSoNotifications::get_instance();
		$unread_notes = $note->get_unread_count_for_user();
		$user = PeepSoUser::get_instance(get_current_user_id());

		$navbar = array(
			'home' => array(
				'href' 	=> PeepSo::get_page('activity'),
				'icon' 	=> 'home',
				'order' => 0,
				'title' => __('Activity Stream', 'peepso-core'),
			),
			'members-page' => array(
				'href' 	=> PeepSo::get_page('members'),
				'label' => __('Members', 'peepso-core'),
				'order' => 10,
			),
			'notifications' => array(
				'count' => $unread_notes,
				'class' => 'dropdown-notification ps-js-notifications',
				'href' => PeepSo::get_page('notifications'),
				'icon' => 'globe',
				'order' => 140,
				'title' => __('Pending Notifications', 'peepso-core'),
			),

			// Profile - avatar and name
			'profile-home' => array(
				'class' => '',
				'href' => $user->get_profileurl(),
				'label' =>'<div class="ps-avatar ps-avatar--toolbar ps-avatar--xs"><img src="' . $user->get_avatar() . '"></div> ' . $user->get_firstname(),
				'order'	=> 100,
			),

			// Profile segments
			'profile' => array(
				'class' => 'ps-dropdown-toggle',
				'menuclass' => 'ps-dropdown-menu ps-dropdown__menu',
				'wrapclass' => 'ps-dropdown-right',

				'label' => '<span class="dropdown-caret ps-icon-caret-down"></span>',
				'order' => 110,

				'menu' => array(
					'stream' => array(
						'href' => $user->get_profileurl(),
						'icon' => 'home',
						'label' => __('Stream', 'peepso-core'),
						'order' => 0,
					),
					'about' => array(
						'href' => $user->get_profileurl().'about',
						'icon' => 'user2',
						'label' => __('About', 'peepso-core'),
						'order' => 10,
					),
					'edit' => array(
						'href' => PeepSo::get_page('profile') . '?edit',
						'icon' => 'edit',
						'label' => __('Edit Account', 'peepso-core'),
						'order' => 100,
					),
					'logout' => array(
						'href' => PeepSo::get_page('logout'),
						'icon' => 'off',
						'label' => __('Log Out', 'peepso-core'),
						'order' => 110
					)
				),
			),
		);

		$navbar = apply_filters('peepso_navbar_menu', $navbar);

		$sort_col = array();

		foreach ($navbar as $nav)
			$sort_col[] = (isset($nav['order']) ? $nav['order'] : 10);

		array_multisort($sort_col, SORT_ASC, $navbar);

		if (isset($navbar['profile'])) {
			$sort_profile_segments = array();

			foreach ($navbar['profile']['menu'] as $nav) {
				$sort_profile_segments[] = (isset($nav['order']) ? $nav['order'] : 10);
			}

			array_multisort($sort_profile_segments, SORT_ASC, $navbar['profile']['menu']);
		}

		foreach ($navbar as $item => $data) {
			if (isset($data['menu'])) {
				echo '<span class="ps-dropdown ',$data['wrapclass'],'">', PHP_EOL;
				echo '<a onclick="return false;" ';
				if (isset($data['href']))
					echo ' href="', $data['href'], '" ';
				if (isset($data['class']))
					echo ' class="', $data['class'], '">';
				echo $data['label'], '</a>', PHP_EOL;

				echo '<div ';
				if (isset($data['menuclass']))
					echo ' class="', $data['menuclass'], '" ';
				echo '>', PHP_EOL;

				foreach ($data['menu'] as $name => $submenu) {
					echo '<a ';
					if (isset($submenu['class']))
						echo ' class="', $submenu['class'], '" ';
					echo 'href="', $submenu['href'], '">';
					if (isset($submenu['icon']))
						echo '<i class="ps-icon-', $submenu['icon'], '"></i>';
					echo $submenu['label'], '</a>', PHP_EOL;
					echo '</a>', PHP_EOL;
				}
				echo '</div>', PHP_EOL;
				echo '</span>', PHP_EOL;
			} else {
				// visible-desktop
				echo '<span class=" ', (isset($data['class']) ? $data['class'] : ''), '"><a href="', $data['href'], '" ';

				if (isset($data['title']))
					echo ' title="', esc_attr($data['title']), '" ';
				echo '>';

				if (isset($data['count'])) {
					echo '<div class="ps-bubble__wrapper">';
				}

				if (isset($data['icon']))
					echo '<i class="ps-icon-', $data['icon'], '"></i>';
				if (isset($data['label']))
					echo $data['label'];
				if (isset($data['count'])) {
					echo '<span class="js-counter ps-bubble ps-bubble--toolbar ps-js-counter"' , ($data['count'] > 0 ? '' : ' style="display:none"'),'>', ($data['count'] > 0 ? $data['count'] : ''), '</span></div>';
				}
				echo '</a></span>', PHP_EOL;
			}
		}
	}

	/**
	 * Displays the post types available on the post box. Plugins can add to these via the `peepso_post_types` filter.
	 */
	public function post_types($params = array())
	{
		$opts = array(
			'status' => array(
				'icon' => 'pencil',
				'name' => __('Status', 'peepso-core'),
				'class' => 'ps-postbox__menu-item active',
			),
			// plugins will add these
/*			'photo' => array(
				'icon' => 'camera',
				'name' => __('Photo', 'peepso-core'),
			),
			'video' => array(
				'icon' => 'videocam',
				'name' => __('Video', 'peepso-core'),
			),
			'event' => array(
				'icon' => 'calendar',
				'name' => __('Event', 'peepso-core'),
			)*/
		);

		$opts = apply_filters('peepso_post_types', $opts, $params);

		foreach ($opts as $type => $data) {
			echo '<div data-tab="', $type, '" ';
			if (isset($data['class']) && !empty($data['class']))
				echo 'class="', $data['class'], '" ';
			echo '>', PHP_EOL;
			echo '<a href="javascript:void(0)">';

			echo '<i class="ps-icon-', $data['icon'], '"></i>';
			echo '<span>', $data['name'], '</span>', PHP_EOL;

			echo '</a></div>', PHP_EOL;
		}
	}


	/*
	 * Displays error messages contained within an error object
	 * @param WP_Error $error The instance of WP_Error to display messages from.
	 */
	public function show_error($error)
	{
		if (!is_wp_error($error))
			return;

		$codes = $error->get_error_codes();
		foreach ($codes as $code) {
			echo '<div class="ps-alert">', PHP_EOL;
			$msg = $error->get_error_message($code);
			echo $msg;
			echo '</div>';
		}
	}

	/**
	 * Returns the max upload size from php.ini and wp.
	 * @return string The max upload size bytes in human readable format.
	 */
	public function upload_size()
	{
		$upload_max_filesize = convert_php_size_to_bytes(ini_get('upload_max_filesize'));
		$post_max_size = convert_php_size_to_bytes(ini_get('post_max_size'));

		return (size_format(min($upload_max_filesize, $post_max_size, wp_max_upload_size())));
	}

}

// EOF
