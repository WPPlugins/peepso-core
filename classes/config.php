<?php

class PeepSoConfig
{
	private static $instance = NULL;

	public static $slug = 'peepso_config';
	public $config_object = NULL;

	public $sections = NULL;
	public $form = NULL;

	private $tab_count = 0;
	private $curtab = NULL;

	public function __construct()
	{
	}

	// @todo docblock
	public function render()
	{
		wp_enqueue_media();
		wp_enqueue_script('peepso-admin-config');
		PeepSoAdmin::admin_header(__('Configuration', 'peepso-core'));
		$input = new PeepSoInput();
		$tab = $this->curtab = $input->val('tab', 'site');

		$options = PeepSoConfigSettings::get_instance();

		add_action('peepso_admin_config_save', array(&$this, 'config_save'));
		add_filter('peepso_render_form_field_type-radio', array(&$this, 'render_admin_radio_field'), 10, 2);

		// handle tabs within config settings page
		$curtab = $input->val('tab');

		$aTab = $this->get_tab($tab);

		if (!empty($curtab) && !isset($aTab['function'])) {
			switch ($curtab)
			{
			case 'email':
				PeepSoConfigEmails::get_instance();
				break;
			}

			if ('POST' === $_SERVER['REQUEST_METHOD'] &&
				wp_verify_nonce($input->val('peepso-' . $curtab . '-nonce'), 'peepso-' . $curtab . '-nonce'))
				do_action('peepso_admin_config_save-' . $curtab);

			do_action('peepso_admin_config_tab-' . $curtab);

			return;
		}

		$aTab = $this->get_tab($tab);
		$this->config_object = new $aTab['function']();

		if (!($this->config_object instanceOf PeepSoConfigSectionAbstract)) {
			throw new Exception(__('Class must be instance of PeepSoConfigSectionAbstract', 'peepso-core'), 1);
		}

		$filter = 'peepso_admin_register_config_group-' . $aTab['tab'];

		$this->config_object->register_config_groups();
		$this->config_object->config_groups = apply_filters($filter, $this->config_object->config_groups);
		// Call build_form after all config_groups have been defined
		$this->config_object->build_form();

		add_filter('peepso_admin_config_form_open', array(&$this, 'set_form_args'));

		$this->prepare_metaboxes();

		if (isset($_REQUEST['peepso-config-nonce']) &&
			wp_verify_nonce($_REQUEST['peepso-config-nonce'], 'peepso-config-nonce')) {
			do_action('peepso_admin_config_save');
		}


		$this->render_tabs();

		PeepSoTemplate::set_template_dir('admin');
		PeepSoTemplate::exec_template(
			'config',
			'options',
			array(
				'config' => $this
			)
		);
	}

	/*
	 * Display the tabs
	 */
	public function render_tabs()
	{
		$input = new PeepSoInput();
		$curtab = $input->val('tab', 'site');

		$old_cat = 'foundation';

		$c = array(
            'foundation'=>'#ffeeee',
            'core'=>'#eeeeff',
            'extras'=>'#eeffee',
            'integrations'=>'#fefeee',
            'default'       => '#ffffff',
        );

		echo '<ul class="nav nav-tabs">', PHP_EOL;
		$tabs = $this->get_tabs();
		foreach ($tabs as $tab) {
			$config_tab = '';

            $cat = isset($tab['cat']) ? $tab['cat'] : $tab['label'];

            if($cat != $old_cat) {
                echo "<li>&nbsp;</li>";
                $old_cat=$cat;
            }

			if (isset($tab['tab']) && !empty($tab['tab']))
				$config_tab = $tab['tab'];
			$activeclass = '';
			if ($curtab === $config_tab)
				$activeclass = 'active';

			$color = $c['default'];
			if(isset($c[$cat])) {
			    $color = $c[$cat];
            }

			echo '<li  class="', $activeclass, '">', PHP_EOL;
			echo '<a style="background-color:',$color,' !important;" href="', admin_url('admin.php?page='), self::$slug;
			if (!empty($tab['tab']))
				echo '&tab=', $tab['tab'];
			echo '"';
			if (isset($tab['description']) && !empty($tab['description']))
				echo ' title="', esc_attr($tab['description']), '"';
			echo '>';
			echo	$tab['label'];
			echo	'</a>', PHP_EOL;

			echo '</li>';


		}
		echo '</ul>', PHP_EOL;
		echo '<div class="edit-message" id="edit_warning">';
		echo __('Some settings have been changed. Be sure to save your changes.', 'peepso-core');
		echo '</div>';
	}


	/*
	 * Opens config form, applies filters to <form> arguments
	 *
	 * @return string The opening form tag
	 */
	public function form_open()
	{
		$form = apply_filters('peepso_admin_config_form_open', '', 10, array());

		return $this->config_object->get_form()->form_open($form);
	}

	// @todo docblock
	public function set_form_args()
	{
		return array();
	}

	/*
	 * Creates a meta box for each config group item
	 */
	public function prepare_metaboxes()
	{
		foreach ($this->config_object->config_groups as $id => $group) {
			add_meta_box(
		        'peepso_config-' . $id, //Meta box ID
		        __($group['title'], 'peepso-core'), //Meta box Title
		        array(&$this, 'render_field_group'), //Callback defining the plugin's innards
		        'peepso_page_peepso-config', // Screen to which to add the meta box
		        isset($group['context']) ? $group['context'] : 'full', // Context
		        'default',
				array('group' => $group)
		    );
		}
	}

	/**
	 * Metabox callback - renders the field group
	 * @param  object $post An object containing the current post.
	 * @param  array $metabox Is an array with metabox id, title, callback, and args elements.
	 * @return void Echoes the field group.
	 */
	public function render_field_group($post, $metabox)
	{
		$group = $metabox['args']['group'];

		if (isset($group['description']))
			echo '<p style="color:gray">', $group['description'], '</p>', PHP_EOL;

		foreach ($group['fields'] as $field) {
			$field = $this->config_object->form->fields[$field['name']];
			echo '<div id="field_' . $field['name'] . '" class="form-group"';

			if(isset($field['children'])) {
				echo ' data-children="'. implode(',',$field['children']).'" ';
			}
			echo '>';
			echo $this->config_object->form->render_field($field);
			echo '</div>';
			echo '<div class="clearfix"></div>';
		}

		if (isset($group['summary']))
			echo '<p style="color:gray">', $group['summary'], '</p>', PHP_EOL;
	}

	// Calls get_instance() to start
	public static function init()
	{
		$config = self::get_instance();
		$config->render();
	}

	// Return an instance of PeepSoConfig
	public static function get_instance()
	{
		if (NULL === self::$instance)
			self::$instance = new self();
		return self::$instance;
	}


	/*
	 * Build a list of tabs to display at the top of config pages
	 * @return array List of tabs to display on config pages
	 */
	public function get_tabs()
	{
		$default_tabs = array(
			'site' => array(
				'label' => __('General', 'peepso-core'),
				'tab' => 'site',
				'description' => __('General configuration settings for PeepSo', 'peepso-core'),
				'function' => 'PeepSoConfigSections',
                'cat' => 'foundation',
			),

			'appearance' => array(
				'label' => __('Appearance', 'peepso-core'),
				'tab' => 'appearance',
				'description' => __('Look and feel settings', 'peepso-core'),
				'function' => 'PeepSoConfigSectionAppearance',
                'cat' => 'foundation',
			),
			'email' => array(
				'label' => __('Edit Emails', 'peepso-core'),
				'tab' => 'email',
				'description' => __('Edit content of emails sent by PeepSo to users and Admins', 'peepso-core'),
                'cat' => 'foundation',
			),
			'advanced' => array(
				'label' => __('Advanced', 'peepso-core'),
				'tab' => 'advanced',
				'description' => __('Avanced System options', 'peepso-core'),
				'function' => 'PeepSoConfigSectionAdvanced',
                'cat' => 'foundation',
			),
			# todo handling when no additional configuration was set, so hide the tab
			/*'additional' => array(
				'label' => __('Additional Options', 'peepso-core'),
				'tab' => 'additional',
				'description' => __('Additional options', 'peepso-core'),
				'function' => 'PeepSoConfigSectionAdditional'
			),*/
		);

		$tabs = apply_filters('peepso_admin_config_tabs', array());

		$tabs_by_cat=array();
		foreach($tabs as $key=>$tab) {
            $cat = isset($tab['cat']) ? $tab['cat'] : 'thirdparty';

            $tab['key'] = $key;
            $tabs_by_cat[$cat][$tab['label']] = $tab;
            ksort($tabs_by_cat[$cat]);
        }

        $tabs = array();

        if(isset($tabs_by_cat['core'])) {
            foreach($tabs_by_cat['core'] as $key=>$tab) {
                $tabs[$tab['key']] = $tab;
            }
        }

        if(isset($tabs_by_cat['extras'])) {
            foreach($tabs_by_cat['extras'] as $key=>$tab) {
                $tabs[$tab['key']] = $tab;
            }
        }

        if(isset($tabs_by_cat['integrations'])) {
            foreach($tabs_by_cat['integrations'] as $key=>$tab) {
                $tabs[$tab['key']] = $tab;
            }
        }

        if(isset($tabs_by_cat['thirdparty'])) {
            foreach($tabs_by_cat['thirdparty'] as $key=>$tab) {
                $tabs[$tab['key']] = $tab;
            }
        }

        $tabs = array_merge($default_tabs, $tabs);

		return ($tabs);
	}

	// @todo docblock
	public static function test()
	{
		$instance = self::get_instance();
	}

	/*
	 * Get a tab based on the associative key
	 *
	 * @param string $tab The tab's associative key
	 * @return array
	 */
	public function get_tab($tab)
	{
		$tabs = $this->get_tabs();

    	if (empty($tabs[$tab])) {
			PeepSo::redirect('wp-admin/404');
    	}

		return $tabs[$tab];
	}

	/**
	 * 'peepso_admin_config_save' action callback. Maps the $_POST data to the form fields,
	 * calls validation and saves data once validation is passed.
	 *
	 * @return void Sets error or success messages to the 'peepso_config_notice' option.
	 */
	public function config_save()
	{
        do_action('peepso_config_before_save-' . $this->curtab);

		$this->config_object->get_form()->map_request();

		if ($this->config_object->get_form()->validate()) {
			$this->_save();
			$type = 'note';
			$message = __('Options updated', 'peepso-core');
		} else {
			$type = 'error';
			$message = __('Please correct the errors below', 'peepso-core');
		}

		$peepso_admin = PeepSoAdmin::get_instance();
		$peepso_admin->add_notice($message, $type);
	}

	/**
	 * Rendering function for radio buttons. Called from the filter - 'peepso_render_form_field_type-radio'.
	 * @param  string $sField The field's HTML.
	 * @param  object $field  The field object.
	 * @return string The radio button HTML.
	 */
	public function render_admin_radio_field($sField, $field)
	{
		$sField = '';

		foreach ($field->options as $val => $text) {
			$sField .= '<label>';
			$sField .= '<input type="radio" name="' . $field->name . '" value="' . $val . '" ';
			if ($val === $field->value)
				$sField .= ' checked ';
			$sField .= ' />';
			$sField .= '<span class="lbl"> ' . $text . '</span>';
			$sField .= '</label>';
		}

		return $sField;
	}

	/**
	 * Loops through the form object and saves the values as options via PeepSoConfigSettings.
	 * @return void
	 */
	private function _save()
	{
		foreach ($this->config_object->get_form()->fields as $field) {
			PeepSoConfigSettings::get_instance()->set_option(
				$field['name'],
				$field['value']
			);
		}

		do_action('peepso_config_after_save-' . $this->curtab);
	}

	/**
	 * @TODO this is a temporary hack to remove dependency on the WP method,
	 * do_meta_boxes implementation tends to change from version to version
	 * should be at some point rewritten to something more PeepSo-ish
	 * */
	function do_meta_boxes( $screen, $context, $object ) {
		global $wp_meta_boxes;
		static $already_sorted = false;

		if ( empty( $screen ) )
			$screen = get_current_screen();
		elseif ( is_string( $screen ) )
			$screen = convert_to_screen( $screen );

		$page = $screen->id;

		$hidden = get_hidden_meta_boxes( $screen );

		printf('<div id="%s-sortables" class="meta-box-sortables">', htmlspecialchars($context));

		// Grab the ones the user has manually sorted. Pull them out of their previous context/priority and into the one the user chose
		if ( ! $already_sorted && $sorted = get_user_option( "meta-box-order_$page" ) ) {
			foreach ( $sorted as $box_context => $ids ) {
				foreach ( explode( ',', $ids ) as $id ) {
					if ( $id && 'dashboard_browser_nag' !== $id ) {
						add_meta_box( $id, null, null, $screen, $box_context, 'sorted' );
					}
				}
			}
		}

		$already_sorted = true;

		$i = 0;

		if ( isset( $wp_meta_boxes[ $page ][ $context ] ) ) {
			foreach ( array( 'high', 'sorted', 'core', 'default', 'low' ) as $priority ) {
				if ( isset( $wp_meta_boxes[ $page ][ $context ][ $priority ]) ) {
					foreach ( (array) $wp_meta_boxes[ $page ][ $context ][ $priority ] as $box ) {
						if ( false == $box || ! $box['title'] )
							continue;
						$i++;
						$hidden_class = in_array($box['id'], $hidden) ? ' hide-if-js' : '';
						echo '<div id="' . $box['id'] . '" class="postbox ' . postbox_classes($box['id'], $page) . $hidden_class . '" ' . '>' . "\n";
						if ( 'dashboard_browser_nag' != $box['id'] ) {
							echo '<button type="button" class="handlediv button-link" aria-expanded="true">';
							echo '<span class="screen-reader-text">' . sprintf( __( 'Toggle panel: %s' ), $box['title'] ) . '</span>';
							echo '<span class="toggle-indicator" aria-hidden="true"></span>';
							echo '</button>';
						}
						echo "<h3 class='hndle'><span>{$box['title']}</span></h3>\n";
						echo '<div class="inside">' . "\n";
						call_user_func($box['callback'], $object, $box);
						echo "</div>\n";
						echo "</div>\n";
					}
				}
			}
		}

		echo "</div>";

		return $i;

	}
}
