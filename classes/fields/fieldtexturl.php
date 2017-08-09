<?php

class PeepSoFieldTextUrl extends PeepSoFieldText
{
	protected $field_meta_keys_extra = array(
		'user_nofollow',
	);

	public static $admin_label='URL';

	public function __construct($post, $user_id)
	{
		$this->field_meta_keys = array_merge($this->field_meta_keys, $this->field_meta_keys_extra);
		parent::__construct($post, $user_id);

		$this->default_desc = __('What\'s the site\'s address?', 'peepso-core');
		// No text area / multiline for URL
		unset($this->render_form_methods['_render_form_textarea']);

		// Add an option to render as <a href>
		$this->render_methods['_render_link'] = __('clickable link', 'peepso-core');

		// Remove inherited length validators
		$this->validation_methods = array_diff($this->validation_methods, array('lengthmax','lengthmin'));
		$this->validation_methods[] = 'patternurl';

		$this->default_desc = __('What\'s the site\'s address?', 'peepso-core');
	}

	protected function _render_link()
	{
		if(empty($this->value)) {
			return $this->_render_empty_fallback();
		}

		// add missing protocol
		if(substr($this->value,0,4) != 'http' && !stristr($this->value, '://')) {
			$this->value = 'http://'.$this->value;
		}

		// display without protocol
		$display_value = explode('://',$this->value,2);

		// nofollow attribute
		$display_nofollow = (1 == $this->prop('meta', 'user_nofollow')) ? 'nofollow="nofollow"' : '';

		return sprintf('<a href="%s" %s target="_blank">%s</a>', $this->value, $display_nofollow, $display_value[1]);
	}

}