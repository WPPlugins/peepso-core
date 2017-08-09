<?php
class PeepSoField
{
	const USER_META_FIELD_KEY = 'peepso_user_field_';

	// user
	protected $wp_user;
	protected $user_id;

	// field
	public $id;
	public $desc;
	public $default_desc;
	public $key;
	public $title;
	public $published;
	public $value;

	public $acc;
	public $can_acc;

	public $data_type = NULL;

	// rendering
	public $input_args 			= array();

	public $meta;

	// Admin label (readable "type")
	public static $admin_label='Field';


	// AVAILABLE render methods - these are listed for the Administrator to choose from
	public $render_methods = array('_render'=>'default (text)');
	public $render_form_methods = array();

	// Flag for fields, is render in registration page?
	public static $profile_field_prefix = "profile_field_";
	public $is_registration_page = FALSE;
	public $el_class = 'ps-input';

	// AVAILABLE validation methods - these are listed for the Administrator to choose from
	// All fields have an optional "required" validation rule, and can extend it with more options
	public $validation_methods = array(
		'required',
	);

	// postmeta related to the field objects
	protected $field_meta_keys = array(
		// rendering
		'is_core',					// INT 1,2 (optional)		1 - WP core, 2 - PeepSo core
		'order',					// INT
		'class',					// string
		'method',					// string
		'method_form',				// string
		'validation',				// JSON object (optional)	how to validate
		// user flags
		'user_registration',		// INT 0,1					visible on registration
		'user_disable_edit',		// INT 0,1					readonly
		'user_disable_guest_view',	// INT 0,1					hide on profiles
		'user_disable_acc',			// INT 0,1					disable privacy
		'user_admin_only',			// INT 0,1	only admin users can see it
		// extra flags
		'default_acc',				// INT						default privacy level
	);

	// which postmeta has to be forced INT
	// all INTs default to ZERO
	protected $as_int = array(
		'is_core',
		'order',
		'default_acc',
		'user_registration',
		'user_disable_edit',
		'user_disable_guest_view',
		'user_disable_acc',
		'user_admin_only',
	);

	// which postmeta should be deserialized
	protected $as_array = array(
		'validation',
	);

	#public $profile_fields = array();

	// validation
	public $validation_errors = array();

	/**
	 * Get the wp_posts ID of a given field
	 */
	public static function get_field_by_id( $id, $user_id = NULL)
	{
		if(is_numeric($id)) {
			$post = get_post($id);

		} elseif(strlen($id)) {
			$args = array(
				'post_type' => trim(PeepSoField::USER_META_FIELD_KEY,'_'),
				'name'		=> $id,
			);

			$q = new WP_Query($args);

			if($q->have_posts()) {
				$post = $q->next_post();
			}
		}

		if(!isset($post)) {
			return( FALSE );
		}

		return self::get_field_by_post($post, $user_id);
	}

	/**
	 * Decide which sub-class of PeepSoField* should handle the given field
	 */
	public static function get_field_by_post( $post, $user_id = NULL )
	{
		$class  = 'PeepSoField'.get_post_meta($post->ID, 'class', TRUE);
		if(!class_exists($class)) {
			return NULL;
		}

		return new $class($post, $user_id);
	}

	public function __construct( $post , $user_id = NULL )
	{
		$this->user_id = (NULL != $user_id) ? $user_id : get_current_user_id();
		$this->_get_wp_user();

		$id = $post->ID;

		// the core fields are identified by name eg USER_META_FIELD_KEY_gender
		// custom added ones are referred by id eg USER_META_FIELD_KEY_2137
		// custom added fields post_name is same as ID
		$key = $post->post_name;

		$this->id = $id;
		$this->default_desc = __('Enter data', 'peepso-core');

		$this->input_args['name'] = PeepSoField::$profile_field_prefix . $id;
		$this->input_args['id'] = PeepSoField::$profile_field_prefix . $id;
		$this->input_args['data_id'] = $id;

		$this->key = PeepSoField::user_meta_key_add($key);
		$this->title = html_entity_decode($post->post_title);
		$this->desc = $post->post_content;
		$this->published = ('publish' == $post->post_status) ? 1 : 0;

		// load up all field meta
		$this->meta = (object)array();
		$this->field_meta_keys  = apply_filters('peepso_user_field_meta_keys', 			$this->field_meta_keys);
		$this->as_int			= apply_filters('peepso_user_field_meta_keys_as_int', 	$this->as_int);
		$this->as_array			= apply_filters('peepso_user_field_meta_keys_as_array', $this->as_array);

		foreach ($this->field_meta_keys as $meta) {

			// default - don't deserialize, string
			$is_int = FALSE; // if TRUE - don't deserialize, force int
			$is_array = FALSE; // if TRUE - deserialize

			if (in_array($meta, $this->as_int)) {
				$is_int = TRUE;
			}

			if (in_array($meta, $this->as_array)) {
				$is_array = TRUE;
			}

			$this->meta->$meta = get_post_meta($id, $meta, TRUE);

			// intval('') might return 1, need to do this manually
			if (TRUE == $is_int) {
				if (strlen($this->meta->$meta)) {
					$this->meta->$meta = intval($this->meta->$meta);
				} else {
					$this->meta->$meta = 0;
				}

				continue;
			}

			if (TRUE == $is_array) {
				$this->meta->$meta = (object)$this->meta->$meta;
			}
		}

		// values used in front-end rendering
		$this->value = $this->get_value(FALSE);
		$this->acc = $this->get_acc();
		$this->can_acc = PeepSoUser::is_accessible_static($this->prop('acc'), $this->prop('user_id'));

		if(!current_user_can('edit_users') && 1 == $this->prop('meta', 'user_admin_only')) {
			$this->can_acc = 0;
		}

		// Additional accessibility conditions for non-owners and non-admins
		if ($this->user_id != get_current_user_id() && !current_user_can('edit_users')) {

			// Guest view disabled (eg first name, last name)
			if (1 == $this->prop('meta', 'user_disable_guest_view')) {
				$this->can_acc = 0;
			}

			// The field is empty
			if (!$this->value) {
				$this->can_acc = 0;
			}
		}
	}



	/** GET & SET **/

	/**
	 * Return any usermeta, with privacy check
	 * @since 1.6
	 * @reason Custom Profile Fields
	 *
	 * @param string 	$key 		the meta key
	 * @param bool|TRUE $check_acc	whether to perform privacy check
	 * @return bool|mixed
	 */
	public function get_value($check_acc = TRUE)
	{
		$key = $this->key;
		$this->_get_wp_user();

		if(TRUE === $check_acc && !PeepSoUser::is_accessible_static($this->get_acc(), $this->user_id)) {
			return FALSE;
		}

		// if called externally, the value might already be initialized
		if(FALSE != $this->value) {
			return $this->value;
		}

		// we are usually handling internal PeepSo meta here
		$key = PeepSoField::user_meta_key_add($key);

		if (isset($this->wp_user->$key)) {
			return ($this->wp_user->$key);
		}

		// fallback 1: to  WordPress defaults
		$key = PeepSoField::user_meta_key_trim($key);

		if (isset($this->wp_user->$key)) {
			return ($this->wp_user->$key);
		}

		// fallback 2: to legacy value
		$ret_legacy = $this->get_legacy_field_value($check_acc);
		if(add_user_meta($this->user_id, PeepSoField::user_meta_key_add($key), $ret_legacy, true)) {
			return $ret_legacy;
		}

		return FALSE;
	}

	public function save($value, $validate_only = FALSE)
	{
		if( 'array' == $this->data_type && !is_array($value) ) {

			if( $value_array = json_decode(stripslashes(html_entity_decode($value)), TRUE)) {
				$value = $value_array;
			} else {
				$value = array();
			}
		}

		$this->old_value = $this->value;
		$this->value = $value;

		if ($this->validate()) {
			if ($validate_only) {
				return (TRUE);
			} else {
				if($this->set_user_field($this->key, $this->value)) {
					do_action('peepso_action_profile_field_save', $this);
					return(TRUE);
				}
			}
		}

		return (FALSE);
	}

	public function save_acc($acc)
	{
		$this->acc=$acc;
		return $this->set_user_field($this->key.'_acc', $acc);
	}

	/**
	 * Save any valid user meta
	 *
	 * @param 	string	$key
	 * @param	string	$value
	 * @return	bool|int
	 */
	public function set_user_field($key, $value)
	{
		$wp_key = PeepSoField::user_meta_key_trim($key);
		$key = PeepSoField::user_meta_key_add($key);

		$is_core = isset($this->meta->is_core) ? $this->meta->is_core : 0;

		// use ints for the _acc fields
		if (substr($key, -4) == '_acc') {
			$value = intval($value);
			$is_core = 0;
		}

		// some values are stored in WP itself
		if(1 == $is_core) {
			return wp_update_user(array('ID' => $this->user_id, $wp_key => $value));
		}

		// otherwise put in PeepSo keys
		if( $value == get_user_meta($this->user_id, $key, true) ) {
			return TRUE;
		}

		return update_user_meta($this->user_id, $key, $value);
	}

	/**
	 * Provides fallback if PeepSo has been freshly upgraded
	 * Will pull data from legacy peepso_users columns and copy them over to the new meta keys
	 *
	 * @param 	string	$key
	 * @param 	bool|TRUE $check_acc
	 * @return 	bool|mixed
	 */
	protected function get_legacy_field_value($check_acc = TRUE)
	{
		$this->_get_wp_user();

		$col_name = 'usr_' . $this->key;
		$acc_name = $col_name . '_acc';			// name of access column in peepso_users table

		if ($check_acc) {
			// if there's an access column, check it
			// $this->user->peepso_user is NOT a typo
			if (isset($this->user->peepso_user[$acc_name])) {
				if (!$this->user->is_accessible($this->key))
					return (FALSE);
			}
		}

		if (isset($this->wp_user->{$this->key})) {
			return ($this->wp_user->{$this->key});
		}

		return (FALSE);
	}

	/** UTILS **/

	/**
	 * Make sure $this->wp_user is properly set-up
	 */
	protected function _get_wp_user()
	{
		if( FALSE == $this->wp_user) {
			$this->wp_user = get_user_by('id', $this->user_id);
		}
	}

	/** VALIDATION & ACCESS **/

	public function validate()
	{
		if( count($this->meta->validation) ) {

			if( 1 == $this->prop('meta','validation','required') ) {

				$test = new PeepSoFieldTestRequired($this->value);
				$test->test();

				if (NULL !== $test->error) {
					$this->validation_errors['required'] = $test->error;
					return FALSE;
				}
			}

			if(isset($this->meta->validation->required) && !$this->is_registration_page) {
				unset($this->meta->validation->required);
			}

			if(is_object($this->meta->validation) && count($this->meta->validation)) {
				foreach ($this->meta->validation as $rule => $args) {

					if (0 == $args) {
						// this validation option is disabled
						continue;
					}

					$classname = 'PeepSoFieldTest' . ucfirst($rule);

					// the "value" keys will automatically be skipped because the class doesnt exist
					if (class_exists($classname)) {

						$value_key = $rule . '_value';

						if ($this->prop('meta', 'validation', $value_key)) {
							$args = $this->prop('meta', 'validation', $value_key);
						}

						$test = new  $classname($this->value, $args);
						$test->test();

						if (NULL !== $test->error) {
							$this->validation_errors[$rule] = $test->error;
						}
					}
				}
			}

			if(count($this->validation_errors)) {
				return FALSE;
			}
		}

		return TRUE;
	}

	public function get_acc()
	{
		$this->_get_wp_user();

		$key_acc = PeepSoField::user_meta_key_add($this->key).'_acc';

		$default = $this->prop('meta','default_acc');

		if(0 == $default) {
			$default = PeepSo::ACCESS_MEMBERS;
		}

		if(1 == $this->prop('meta','user_disable_acc')) {
			return $default;
		}

		if (isset($this->wp_user->$key_acc) && 0 < intval($this->wp_user->$key_acc)) {
			return ($this->wp_user->$key_acc);
		} else {
			add_user_meta($this->user_id, $key_acc, $default, TRUE);
			return $default;
		}


		return FALSE;
	}

	/**
	 * Attach self::meta_field_key to the key
	 *
	 * @param 	string	$key
	 * @return 	string
	 */
	public static function user_meta_key_add( $key )
	{
		return self::USER_META_FIELD_KEY . self::user_meta_key_trim( $key );
	}

	/**
	 * Trim self::meta_field_key from the key
	 *
	 * @param	string	$key
	 * @return 	string
	 */
	public static function user_meta_key_trim( $key )
	{
		return str_ireplace(self::USER_META_FIELD_KEY, '', $key);
	}

	/**
	 * Renderers are protected
	 * They have to be interfaced via render() and render_input()
	 * They also have to be accessible from child classes
	 */

	/** RENDER the front-end (user profile/edit/registration) **/

	/**
	 * @param string $context - view|register|edit
	 */
	public function should_render( $context = 'view' )
	{
		if( !$this->can_acc ) {
			return FALSE;
		}

		$key = 'user_'.$context;

		if(isset($this->meta->$key)) {

			// In profile view, even empty fields render (if they are accessible) so they can be modified
			if('view' == $context) {
				return (1 == $this->meta->$key) ? TRUE : FALSE;
			}

			// Fields in different context (eg on_cover) don't render if they are empty
			return ( $this->value ) ? TRUE : FALSE;
		}

		return TRUE;
	}

	public function render_input($echo = true)
	{
		// check if loaded in registration or not
		$method_form = $this->prop('meta','method_form');
		if(TRUE === $this->is_registration_page) {
			$method_form = $method_form . '_register';
		}

		$ret = call_user_func(array($this, $method_form));

		if($echo) {
			echo $ret;
		}

		return $ret;
	}

	public function render($echo = TRUE)
	{
		$ret = call_user_func(array($this, $this->prop('meta','method')));

		if($echo) {
			echo $ret;
		}

		return $ret;
	}

	public function render_validation( $echo = TRUE )
	{
		ob_start();
		$has_validation_options = 0;
		$validation_config = (array)$this->prop('meta','validation');
		foreach($this->validation_methods as $method) {
			if(1 == $this->prop('meta','validation',$method)){
				$validation_class = 'PeepSoFieldTest'.$method;

				if('required' == $method || !class_exists($validation_class)) {
					continue;
				}

				$test = new $validation_class(0,0);
				$has_validation_options++;

				/**
				 * @todo this HTML block must be identified by $method
				 *
				 * if the AJAX response has $method key in errors:
				 *   	highlight this HTML block RED
				 *		and DO NOT display the error again on the bottom
				 *
				 * however if AJAX response has a $method that was NOT printed here
				 *		DO display the error on the bottom, and do nothing her
				 */

				echo '<div class="ps-alert ps-alert--sm ps-js-validation ps-js-validation-' . $method . '">';
				echo sprintf($test->message, $this->prop('meta','validation',$method.'_value'));
				echo '</div>';
			}
		}

		$ret = ob_get_clean();

		if($echo) {
			echo $ret;
		}

		return $ret;
	}
	protected function _render()
	{
		if(empty($this->value) || ($this->is_registration_page)) {
			return $this->_render_empty_fallback();
		}

		return $this->value;
	}

	protected function _render_empty_fallback()
	{
		ob_start();
		if(!$this->is_registration_page){
		?>
		<span class="ps-field-placeholder <?php echo ( 1 == $this->prop('meta','validation','required' ) ) ? 'ps-text--danger' : '';?>">
		<?php echo $this->prop('desc'); ?>

		</span>
		<?php
		} else {
			echo $this->prop('desc');
		}

		return ob_get_clean();
	}

	protected function _render_input_args()
	{
		ob_start();

		echo ' name="'.$this->input_args['name'].'"',
			' id="'.$this->input_args['id'].'"',
			' data-id="'.$this->id.'"',
		' oninput="profile.field_changed(this,event);"';

		return ob_get_clean();
	}

	protected function _render_input_register_args()
	{
		ob_start();

		echo ' name="'.$this->input_args['name'].'"',
			' id="'.$this->input_args['id'].'"',
			' data-id="'.$this->id.'"';

		if (!empty($this->el_class )) {
			echo ' class="'.$this->el_class.'"';
		}

		return ob_get_clean();
	}

	protected function _render_input_args_acc()
	{
		ob_start();
		echo ' name="'.$this->input_args['name'].'_acc"',
			' id="'.$this->input_args['id'].'_acc"',
			' data-id="'.$this->id.'"';

		return ob_get_clean();
	}

	protected function _render_form_input( )
	{
		$ret = '<input type="text" value="' . $this->value . '"' . $this->_render_input_args().' onkeydown="return profile.field_keydown(this,event);">';

		return $ret;
	}

	protected function _render_form_input_register( )
	{
		// since key event not used in registration page
		$ret = '<input type="text" value="' . $this->value . '"' . $this->_render_input_register_args().'>';

		return $ret;
	}

	public function render_access()
	{
		$acc = $this->acc;

		if(FALSE == $acc) {
			return FALSE;
		}

		$acc = intval($acc);

		$privacy = PeepSoPrivacy::get_instance();

		$access_settings = $privacy->get_access_settings();

		if (!isset($access_settings[$acc])) {
			// access value not found in keys, assume value from first access entry
			$keys = array_keys($access_settings);
			$acc = $keys[0];
		}


		if( 1 == $this->meta->user_disable_acc) {
			?>
			<span class="ps-profile-privacy--disabled ps-tooltip" title="<?php _e('Some fields privacy is defined by the administrators', 'peepso-core'); ?>">
				<i class="ps-icon-<?php echo $access_settings[$acc]['icon'];?>"></i><span class="ps-privacy-title"><?php echo $access_settings[$acc]['label'];?></span>
			</span>
			<?php
			return;
		}


		?>
		<div class="ps-profile-privacy">
			<div class="ps-privacy-dropdown ps-js-dropdown">
				<input type="hidden" <?php echo $this->_render_input_args_acc();?> value="<?php echo $acc;?>" />
				<button id="acc-<?php echo $this->input_args['name'];?>" type="button" class="ps-btn ps-dropdown-toggle" data-toggle="dropdown">
					<span class="dropdown-value"><i class="ps-icon-<?php echo $access_settings[$acc]['icon'];?>"></i></span>
					<span class="ps-privacy-title"><?php echo $access_settings[$acc]['label'];?></span>
				</button>


				<ul class="ps-dropdown-menu" style="display:none">

					<?php foreach ($access_settings as $acc_key => $acc_value) {
						echo '<li><a id="', $this->id, '-acc-', $acc_key, '" href="javascript:" data-option-value="', $acc_key, '" onclick="profile.change_privacy(this);">';
						echo '<i class="ps-icon-', $acc_value['icon'], '"></i>';
						echo '<span>', $acc_value['label'], '</span></a></li>', PHP_EOL;
					}

					?>
				</ul>
			</div>
		</div>
		<?php
	}

	public function prop($key, $key1=NULL, $key2=NULL)
	{
		// chain validate each key

		if(!isset($this->$key)) {
			return FALSE;
		}

		if('meta' == $key && !isset($this->$key->$key1)) {
			echo "$key1 not set";
		}

		if($key1 && !isset($this->$key->$key1)) {
			return FALSE;
		}

		if($key2 && !isset($this->$key->$key1->$key2)) {
			return FALSE;
		}

		// reverse return

		if($key2) {
			return $this->$key->$key1->$key2;
		}

		if($key1) {
			return $this->$key->$key1;
		}

		return $this->$key;
	}
}

// EOF
