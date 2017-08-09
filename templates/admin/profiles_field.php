<?php
// If the details are not open, adjust CSS
$open_pref = get_user_meta(get_current_user_id(), 'peepso_admin_profile_field_open_'.$field->prop('id'),TRUE);

// Force opening of the newly added field
if(isset($force_open)) {
	$open_pref = 1;
}

// get_user_meta might return an empty string
$open = (strlen($open_pref) && 1 == $open_pref) ? FALSE : 'display:none';

// short class name for CSS
$classname = str_replace('peepsofield','',strtolower(get_class($field)));

// if not published, dim the container
$postbox_muted 			= (0 == $field->prop('published')) ? 'postbox-muted' : FALSE;

// if field is not required, hide postbox-required-mark
$required_mark_hidden 	= (0==$field->prop('meta','validation','required')) ? 'hidden' : FALSE;

// Title of the field
$title = ($field->prop('title')) ? $field->prop('title') : __('no title', 'peepso-core');

if($field->prop('meta','is_core') > 0) {
	$title_after = sprintf(__('Core %s: ', 'peepso-core'), ($field->prop('meta', 'is_core') == 1) ? 'WordPress' : 'PeepSo') . ucwords(str_replace('_', ' ', $field->user_meta_key_trim($field->key)));
} else {
	ob_start();
	do_action('peepso_admin_profiles_field_title_after', $field);
	$title_after = ob_get_clean();
}

$tabs = array(
	'general' 		=> array('id'=>1, 'title'=>__('General', 'peepso-core')),
	'appearance' 	=> array('id'=>2, 'title'=>__('Appearance', 'peepso-core')),
	'privacy' 		=> array('id'=>3, 'title'=>__('Privacy', 'peepso-core')),
	'validation' 	=> array('id'=>4, 'title'=>__('Validation', 'peepso-core')),
);

if(in_array($field->prop('key'), array('peepso_user_field_first_name','peepso_user_field_last_name'))) {
	unset($tabs['privacy']);
}

if(property_exists($field, 'admin_disable_validation')) {
	unset($tabs['validation']);
}


if(property_exists($field, 'admin_disable_privacy')) {
	unset($tabs['privacy']);
}

if(property_exists($field, 'admin_disable_appearance')) {
	unset($tabs['appearance']);
}

?>

<div class="postbox <?php echo $classname;?> ps-postbox--settings no-padd <?php echo $postbox_muted;?>" data-id="<?php echo $field->prop('id');?>">

	<h2 class="hndle <?php echo $classname;?> ps-postbox__title ui-sortable-handle ps-js-handle">

		<div class="postbox-sorting">
			<span class="fa fa-arrows"></span>
			<span class="fa fa-<?php echo ($open) ? 'expand' : 'compress' ?> ps-js-field-toggle"></span>
		</div>

		<div class="ps-postbox__title-label ps-js-field-title">
			<span id="field-<?php echo $field->prop('id');?>-box-title" class="ps-postbox__title-text ps-js-field-title-text">
				<?php echo $title; ?>
			</span>

			<span class="postbox-required-mark <?php echo $required_mark_hidden;?>" id="field-<?php echo $field->prop('id');?>-required-mark"><strong>*</strong></span>

			<span class="fa fa-edit"></span>

			<small>
				<?php echo $title_after;?>
			</small>
		</div>

		<div class="ps-postbox__title-editor">
			<input type="text" value="<?php echo $field->prop('title'); ?>"
				   data-parent-id="<?php echo $field->prop('id'); ?>"
				   data-prop-type="prop"
				   data-prop-name="post_title" <?php echo (1 == get_post_meta($field->prop('id'),'default_title',TRUE)) ? 'data-prop-title-is-default="1"':'';?>>

			<button class="button ps-js-btn ps-js-cancel"><?php echo __('Cancel', 'peepso-core'); ?></button>
			<button class="button button-primary ps-js-btn ps-js-save"><?php echo __('Save', 'peepso-core'); ?></button>
			<span class="ps-settings__progress ps-js-progress">
				<img src="images/loading.gif" style="display:none">
				<i class="ace-icon fa fa-check bigger-110" style="display:none"></i>
			</span>
		</div>
	</h2>

	<div class="ps-js-field" data-id="<?php echo $field->prop('id');?>" style="<?php echo $open;?>">
		<div class="ps-settings">
			<!-- Tabs -->
			<ul class="ps-tabs">
				<?php
				foreach($tabs as $tab) {
				?>
				<li class="ps-tab">
					<a class="<?php echo (1==$tab['id']) ? 'active':'';?>" href="#cpf<?php echo $field->prop('id');?>-tab-<?php echo $tab['id'];?>">
						<i class="dashicons dashicons-admin-settings"></i> <span><?php echo $tab['title']; ?></span>
				</a>
				</li>
				<?php
				}
				?>
			</ul>



			<!-- GENERAL TAB -->

			<div id="cpf<?php echo $field->prop('id');?>-tab-1" class="ps-tab__content">
				<?php

				/** ENABLED **/
				$params = array(
					'type'			=> 'checkbox',
					'data'			=> array(
						'data-prop-type' 		=> 'prop',
						'data-prop-name' 		=> 'post_status',
						'data-disabled-value' 	=> 'private',
						'value'					=> 'publish',
						'admin_value'			=> $field->prop('published'),
						'id'					=> 'field-' . $field->prop('id') .'-published',
					),
					'field'			=> $field,
					'label'			=> __('Enabled', 'peepso-core'),
					'label_after'	=> '',
				);

				// add "checked" manually - the value is "published" and by default checkbox looks for "1"
				if(1 == $field->prop('published')) {
					$params['data']['checked'] = 'checked';
				}

				PeepSoTemplate::exec_template('admin','profiles_field_config_field', $params);

				/**  ADMIN ONLY **/
				/*
				$params = array(
					'type' => 'checkbox',
					'data' => array(
						'data-prop-type' => 'meta',
						'data-prop-name' => 'user_admin_only',
						'data-disabled-value' => '0',
						'value' => '1',
						'admin_value' => $field->prop('meta', 'user_admin_only'),
						'id' => 'field-' . $field->prop('id') . '-user-admin-only',
					),
					'field' => $field,
					'label' => __('Admin only field', 'peepso-core'),
					'label_after' => '',
					'desc' => __('The field will be visible and editable only by the Site Admins. Enable this if you need the field to serve an Admin-only purpose (eg a temporary field draft, or Admin user notes).', 'peepso-core'),
				);

				if (1 == $field->prop('meta', 'user_admin_only')) {
					$params['data']['checked'] = 'checked';
				}

				PeepSoTemplate::exec_template('admin', 'profiles_field_config_field', $params);
				*/
				// Fire fieldtype specific & general tab actions
				do_action(strtolower(get_class($field).'_admin_general'), $field);
				do_action('peepsofield_admin_general', $field);
				?>
			</div>


		<!-- APPEARANCE TAB -->


			<div id="cpf<?php echo $field->prop('id');?>-tab-2" class="ps-tab__content" style="display:none">
				<?php

				/** DISPLAY **/
				$params = array(
					'type'			=> 'select',
					'data'			=> array(
						'options'				=> $field->prop('render_methods'),
						'data-prop-type' 		=> 'meta',
						'data-prop-name' 		=> 'method',
						'admin_value'			=> $field->prop('meta','method'),
						'id'					=> 'field-' . $field->prop('id') .'-render',
					),
					'field'			=> $field,
					'label'			=> __('Display', 'peepso-core'),
					'label_after'	=> '',
				);

				PeepSoTemplate::exec_template('admin','profiles_field_config_field', $params);


				/** EDIT METHOD **/
				$params = array(
					'type'			=> 'select',
					'data'			=> array(
						'options'				=> $field->prop('render_form_methods'),
						'data-prop-type' 		=> 'meta',
						'data-prop-name' 		=> 'method_form',
						'admin_value'			=> $field->prop('meta','method_form'),
						'id'					=> 'field-' . $field->prop('id') .'-render_form',
					),
					'field'			=> $field,
					'label'			=> __('Edit method', 'peepso-core'),
					'label_after'	=> '',
				);

				PeepSoTemplate::exec_template('admin','profiles_field_config_field', $params);

				/** DESC **/
				$params = array(
					'type'			=> 'text',
					'data'			=> array(
						'data-prop-type' 		=> 'prop',
						'data-prop-name' 		=> 'post_content',
						'value'			=> $field->prop('desc'),
						'id'					=> 'field-' . $field->prop('id') .'-desc',
					),
					'field'			=> $field,
					'label'			=> __('Placeholder', 'peepso-core'),
					'label_after'	=> '',
				);

				PeepSoTemplate::exec_template('admin','profiles_field_config_field', $params);

				// Fire fieldtype specific & general tab actions
				do_action(strtolower(get_class($field).'_admin_appearance'), $field);
				do_action('peepsofield_admin_appearance', $field);
				?>
				</div>



			<!-- PRIVACY TAB -->
				<?php
				if(array_key_exists('privacy', $tabs)) {
					echo '<div id="cpf' . $field->prop('id') . '-tab-3" class="ps-tab__content" style="display:none">';

					/** Default Privacy **/
					$privacy = PeepSoPrivacy::get_instance();
					$privacy_options = $privacy->get_access_settings();

					foreach ($privacy_options as $k => $v) {
						$options[$k] = $v['label'];
					}

					$params = array(
						'type' => 'select',
						'data' => array(
							'options' => $options,
							'data-prop-type' => 'meta',
							'data-prop-name' => 'default_acc',
							'admin_value' => $field->prop('meta', 'default_acc'),
							'id' => 'field-' . $field->prop('id') . '-default_acc',
						),
						'field' => $field,
						'label' => __('Default Privacy', 'peepso-core'),
						'label_after' => '',
					);

					ob_start();
					do_action('peepso_admin_profiles_field_options_default_privacy', $field);
					$params['label_after'] = ob_get_clean();

					PeepSoTemplate::exec_template('admin', 'profiles_field_config_field', $params);


					/**  LOCK PRIVACY **/
					$params = array(
						'type' => 'checkbox',
						'data' => array(
							'data-prop-type' => 'meta',
							'data-prop-name' => 'user_disable_acc',
							'data-disabled-value' => '0',
							'value' => '1',
							'admin_value' => $field->prop('meta', 'user_disable_acc'),
							'id' => 'field-' . $field->prop('id') . '-user-disable-acc',
						),
						'field' => $field,
						'label' => __('Disable user privacy setting', 'peepso-core'),
						'label_after' => '',
					);


					if (1 == $field->prop('meta', 'user_disable_acc')) {
						$params['data']['checked'] = 'checked';
					}

					PeepSoTemplate::exec_template('admin', 'profiles_field_config_field', $params);

					// Fire fieldtype specific & general tab actions
					do_action(strtolower(get_class($field).'_admin_privacy'), $field);
					do_action('peepsofield_admin_privacy', $field);

					echo '</div>';
				}
				?>

			<!-- VALIDATION TAB -->
				<?php
				if(array_key_exists('validation', $tabs)) {
					echo '<div id="cpf' . $field->prop('id') . '-tab-4" class="ps-tab__content" style="display:none">';

					/** VALIDATION OPTIONS **/
					foreach ($field->validation_methods as $method) {

						$classname = 'PeepSoFieldTest' . ucfirst($method);

						// will skip "value" keys
						if (class_exists($classname)) {

							$test = new $classname(NULL, 0);

							$params = array(
								'type' => $test->admin_type,
								'data' => array(
									'data-prop-type' => 'meta',
									'data-prop-name' => 'validation',
									'data-prop-key' => $method,
									'value' => $field->prop('meta', 'validation', $method),
									'admin_value' => $field->prop('meta', 'validation', $method),
									'id' => 'field-' . $field->prop('id') . '-validation-' . $method,
								),
								'field' => $field,
								'label' => $test->admin_label,
								'label_after' => $test->admin_label_after,
							);

							if ('checkbox' == $test->admin_type) {

								$params['data']['data-disabled-value'] = '0';
								$params['data']['value'] = '1';
							}

							PeepSoTemplate::exec_template('admin', 'profiles_field_config_field', $params);

							// Some validation options have a "value" field
							if (NULL !== $test->admin_value) {

								$params = array(
									'type' => $test->admin_value,
									'data' => array(
										'data-prop-type' => 'meta',
										'data-prop-name' => 'validation',
										'data-prop-key' => $method . '_value',
										'value' => $field->prop('meta', 'validation', $method . '_value'),
										'admin_value' => $field->prop('meta', 'validation', $method . '_value'),
										'id' => 'field-' . $field->prop('id') . '-validation-' . $method . '-value',
									),
									'field' => $field,
									'label' => $test->admin_value_label,
									'label_after' => $test->admin_value_label_after,
								);


								if (0 == $field->prop('meta', 'validation', $method)) {
									$params['data']['container_style'] = 'display:none';
								}

								PeepSoTemplate::exec_template('admin', 'profiles_field_config_field', $params);
							}
						}
					}

					// Fire fieldtype specific & general tab actions
					do_action(strtolower(get_class($field).'_admin_validation'), $field);
					do_action('peepsofield_admin_validation', $field);
					echo '</div>';
				}

				do_action('peepso_admin_profiles_field_options', $field);
				?>
				<input type="hidden" id="field-<?php echo $field->prop('id');?>-id" value="<?php echo $field->prop('id');?>">
				<input type="hidden" id="field-<?php echo $field->prop('id');?>-order" value="<?php echo $field->prop('meta','order');?>">
			</div>
		</div>
	</div>
