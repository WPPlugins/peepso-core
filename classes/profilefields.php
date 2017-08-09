<?php

/**
 * Class PeepSoProfileFields
 *
 *
 * This class aims to handle all fields considered "PeepSo profile fields":
 *
 * - getting values
 * - setting values
 * - running accessibility checks against privacy settings
 *
 *
 * The fields considered "profile fields" in core PeepSo are:
 *
 * [[ Wordpress defaults ]]
 *
 * first_name
 * last_name
 * description
 * user_url
 *
 * [[ PeepSo defaults ]]
 *
 * peepso_user_field_gender
 * peepso_user_field_birthdate
 *
 * [[ Accessibility flags ]]
 *
 * peepso_user_field_description_acc
 * peepso_user_field_user_url_acc
 * peepso_user_field_gender_acc
 * peepso_user_field_birthdate_acc
 *
 * [[ More ]]
 *
 * Additional fields can be attached with hooks
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 */

class PeepSoProfileFields
{
	/** VARS **/

	private $id;
	private $peepso_user;
	private $wp_user;

	public $profile_fields 			= array();
	public $profile_fields_stats 	= array();

	/** INIT **/

	/**
	 * PeepSoProfileFields constructor.
	 *
	 * We want this to be called only from and accessed only via PeepSoUser
	 *
	 * @param PeepSoUser $peepso_user $this of calling PeepSoUser instance
	 */
	public function __construct( PeepSoUser &$peepso_user )
	{
		$this->id = $peepso_user->get_id();
		$this->user = $peepso_user;
		#$this->create_user();
	}

	/** PROFILE FIELDS **/


	/**
	 * Install fields and usermeta
	 *
	 * DO NOT use the variables here as hard-coded fields, these are only definitions to be executed upon fresh installation
	 */
	public static function install( $verbose = FALSE )
	{
		$post_defaults = array(
				'post_status'		=> 'publish',
				'post_type'			=> 'peepso_user_field',
		);

		$fields = array(
				'first_name' => array(
						'post' => array(
								'post_title'	=> __('First Name', 'peepso-core'),
								'post_content'	=> __('What\'s your name?', 'peepso-core'),
						),
						'meta'	=>	array(
								'order'						=>	1,
								'class'						=> 	'text',
								'method'					=> 	'_render',
								'method_form'				=>	'_render_form_input',

								'is_core'					=> 	1,
								'default_acc'				=>  PeepSo::ACCESS_PUBLIC,

								'validation'				=>	array(
																	'required'			=> 1,

																	'lengthmin'			=> 1,
																	'lengthmin_value'	=> 2,
									
																	'lengthmax'			=> 1,
																	'lengthmax_value' 	=> 32
																),
								'user_disable_guest_view'	=> 	1,
								'user_disable_acc'			=>	1,
						),
				),

				'last_name' => array(
						'post' => array(
								'post_title'	=> __('Last Name', 'peepso-core'),
								'post_content'	=> __('What\'s your last name?', 'peepso-core'),
						),
						'meta'	=>	array(
								'order'						=>	2,

								'class'						=> 	'text',
								'method'					=> 	'_render',
								'method_form'				=>	'_render_form_input',

								'is_core'					=> 	1,
								'default_acc'				=>  PeepSo::ACCESS_PUBLIC,

								'validation'				=>	array(
																	'required'=>1,

																	'lengthmin'=>1,
																	'lengthmin_value'=>2,

																	'lengthmax'=>1,
																	'lengthmax_value' => 32
																),
								'user_disable_guest_view'	=> 	1,
								'user_disable_acc'			=>	1,
						),
				),

				'gender' => array(
						'post' => array(
								'post_title'	=> __('Gender', 'peepso-core'),
								'post_content'	=> __('What\'s your gender?', 'peepso-core'),
						),
						'meta'	=>	array(
								'order'						=>	3,
								'class'						=> 	'selectsingle',
								'method'					=>	'_render',
								'method_form'				=>	'_render_form_select',

								'is_core'					=> 	2,
								'default_acc'				=>  PeepSo::ACCESS_PUBLIC,

								'validation'				=>	array('required'=>1),
								'select_options'			=>	array('m'=>'Male','f'=>'Female'),
						),
				),

				'birthdate' => array(
						'post' => array(
								'post_title'	=> __('Birthdate', 'peepso-core'),
								'post_content'	=> __('When were you born?', 'peepso-core'),
						),
						'meta'	=>	array(
								'order'						=>	4,
								'class'						=> 	'textdate',
								'method'					=>	'_render',
								'method_form'				=>	'_render_form_input',

								'is_core'					=> 	2,
								'default_acc'				=>  PeepSo::ACCESS_PUBLIC,

								'validation'					=>	array('required'=>1),
						),
				),

				'description' => array(
						'post' => array(
								'post_title'	=> __('About Me', 'peepso-core'),
								'post_content'	=> __('Tell us something about yourself.', 'peepso-core'),
						),
						'meta'	=>	array(
								'order'						=>	5,
								'class'						=> 	'text',
								'method'					=> 	'_render',
								'method_form' 				=>	'_render_form_textarea',

								'is_core'					=> 	1,
								'default_acc'				=>  PeepSo::ACCESS_PUBLIC,
						),
				),

				'user_url' => array(
						'post' => array(
								'post_title'	=> __('Website', 'peepso-core'),
								'post_content'	=> __('What\'s your website\'s address?', 'peepso-core'),
						),
						'meta'	=>	array(
								'order'						=>	6,
								'class'						=> 	'texturl',
								'method'					=>	'_render_link',
								'method_form'				=>	'_render_form_input',

								'is_core'					=> 	1,
								'default_acc'				=>  PeepSo::ACCESS_PUBLIC,
						),
				),
		);

		foreach($fields as $post_name => $post_data) {

			// try to find an existing field
			$get_post_by = array(
				'name'=>$post_name,
				'post_type'=>'peepso_user_field'
			);

			$post_query = new WP_Query($get_post_by);
			$update = FALSE;

			if(count($post_query->posts)) {
				$post_id = $post_query->posts[0]->ID;
				$update = TRUE;
			} else {
				// otherwise insert
				$post = array_merge(array('post_name' => $post_name), $post_data['post'], $post_defaults);
				$post_id = wp_insert_post($post);
				$update = FALSE;
			}

			if( TRUE === $verbose) {
				var_dump('Post ID:'. $post_id);
				var_dump('Update:'. (int) $update);
			}

			foreach($post_data['meta'] as $key=>$value) {
				// only inject meta if it's a new post or the key is empty
				if( FALSE === $update ) {
					add_post_meta($post_id, $key, $value, TRUE);
				}
			}
		}
	}

	public static function reset()
	{
		global $wpdb;

		// REMOVES ALL PEEPSO PROFILE FIELDS
		$wpdb->query('DELETE FROM '.$wpdb->prefix.'posts WHERE post_type=\'peepso_user_field\'');


		// delete orphaned post metadata
		$wpdb->query('DELETE pm  FROM '.$wpdb->prefix.'postmeta pm LEFT JOIN '.$wpdb->prefix.'posts wp ON wp.ID = pm.post_id WHERE wp.ID IS NULL');

		if(isset($_GET['peepso_reset_profile_fields']) && 'all' == $_GET['peepso_reset_profile_fields']) {
			$wpdb->query('DELETE FROM '.$wpdb->prefix.'usermeta WHERE `meta_key` LIKE \'peepso_user_field_%\'');
		}
	}
	/**
	 * Grab all fields, including their config, access levels and values for the given user
	 *
	 * $args can be used to narrow down the results:
	 * $args['post_status'] = 'publish' to show only published posts
	 * $args['meta_query'] to join and filter by specific meta (eg user_registration)
	 *
	 * @param array $args
	 * @return array
	 */
	public function load_fields( $args = array() )
	{
		$this->profile_fields 		= array();
		$this->profile_fields_stats = array(
				'fields_all' 			=> 0,
				'fields_required'		=> 0,
				'filled_all' 			=> 0,
				'filled_required' 		=> 0,
				'completeness'			=> 0,
				'missing_required'		=> 0
		);

		// Grab posts of type USER_META_FIELD_KEY (remove trailing "_")

		$default_args = array(
				'post_type' => trim(PeepSoField::USER_META_FIELD_KEY,'_'),
				'posts_per_page'=>apply_filters('peepso_profile_fields_query_limit', 6),

				'meta_key' => 'order',
				'orderby' => 'meta_value_num',
				'order' => 'ASC',

				'meta_query' => array(
					array(
						'is_core',
						'value' => apply_filters('peepso_profile_fields_query_is_core', array(1,2)),
						'compare'=>'IN',
					)
				)
		);

		// Merge default args optional custom args
		$args = array_merge($default_args, $args);

		$q = new WP_Query($args);

		// if there are no posts found you should probably be very worried
		if($q->have_posts()) {

			// loop through all the fields and build an object for each
			while($q->have_posts()) {

				$post = $q->next_post();

				$field = PeepSoField::get_field_by_post($post, $this->user->get_id());

				if(!($field instanceof PeepSoField)) {
					continue;
				}

				// if not accessible
				if(!$field->prop('can_acc') ) {
					continue;
				}

				// add to all fields array
				$this->profile_fields[$field->key] = $field;

				// count stats EXCEPT
				if(isset($field::$user_disable_stats)) {
					continue;
				}
				
				if($field->prop('published')) {
					$this->profile_fields_stats['fields_all']++;

					if (!empty($field->value)) {
						$this->profile_fields_stats['filled_all']++;
					}

					if (isset($field->meta->validation->required) && 1 == $field->meta->validation->required) {
						$this->profile_fields_stats['fields_required']++;

						if (!empty($field->value)) {
							$this->profile_fields_stats['filled_required']++;
						}
					}
				}
			}

			if( $this->profile_fields_stats['fields_all'] > 0 ) {
				$this->profile_fields_stats['completeness'] = floor(100 * $this->profile_fields_stats['filled_all'] / $this->profile_fields_stats['fields_all']);
				$this->profile_fields_stats['completeness_message'] = '<a href="' . $this->user->get_profileurl().'about' .'">' . sprintf(__("Your profile is %d%% complete", 'peepso-core'), $this->profile_fields_stats['completeness']) .'</a>';
				
				if (1 === PeepSo::get_option('force_required_profile_fields',0) && $this->profile_fields_stats['filled_required'] < $this->profile_fields_stats['fields_required']) {
					$this->profile_fields_stats['completeness_message_detail'] = ' - ' . __('fill in missing required fields to be able to participate in the community.', 'peepso-core');
				}
			 } else {
				$this->profile_fields_stats['completeness'] = 100;
				$this->profile_fields_stats['completeness_message'] = '';
			}

			if( $this->profile_fields_stats['fields_required'] > 0 ) {
				$this->profile_fields_stats['missing_required'] = $this->profile_fields_stats['fields_required'] - $this->profile_fields_stats['filled_required'];
				$this->profile_fields_stats['missing_required_message'] = sprintf(__("%d required field(s) missing", 'peepso-core'), $this->profile_fields_stats['missing_required']);
			} else {
				$this->profile_fields_stats['missing_required'] = 0;
				$this->profile_fields_stats['missing_required_message'] = '';
			}

			update_user_meta(get_current_user_id(), 'peepso_profile_completeness', $this->profile_fields_stats['completeness']);
		}

		return $this->profile_fields;
	}

	public function get_fields()
	{
		return $this->profile_fields;
	}

	public function create_user()
	{
		$keys = array_merge($this->meta_keys, $this->meta_keys_acc);

		foreach($keys as $key) {
			$this->get_user_field_value($key);
		}
	}
}
// EOF