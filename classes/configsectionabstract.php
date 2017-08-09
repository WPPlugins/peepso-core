<?php

abstract class PeepSoConfigSectionAbstract
{
	public $config_groups = array();
	public $groups = array();
	public $form;

	public $fields  = array();
	public $args 	= array();
	public $context = 'left';

	public function __construct()
	{
		$this->form = new PeepSoForm(array('class' => 'form-horizontal'));
		wp_enqueue_script('peepso-admin-config');
	}

	/**
	 * Return this sections form object
	 * @return object An instance of PeepSoForm
	 */
	public function get_form()
	{
		return $this->form;
	}

	public function set_context($context)
	{
		$this->context = $context;
	}
	public function set_field($name, $label, $type)
	{
		$default_args = array(
			'field_wrapper_class'	=> 'controls col-sm-8',
			'field_label_class' 	=> 'control-label col-sm-4',
			'description'			=> FALSE,
			'int'					=> FALSE,
			'options'				=> FALSE,
			'raw'					=> FALSE,
			'multiple'				=> FALSE,
			'default' 				=> FALSE,
			'validation' 			=> FALSE,
			'validation_options' 	=> FALSE,
		);

		if( 'yesno_switch' == $type ) {
			$default_args['int'] = TRUE;
		}

		$args = array_merge($default_args, $this->args);


		$return = array(
			'name' 					=> $name,
			'label'					=> $label,
			'type'					=> $type,
			'field_wrapper_class'	=> $args['field_wrapper_class'],
			'field_label_class'		=> $args['field_label_class'],
			'value'					=> PeepSo::get_option($name),
		);

		// Special cases
		if (FALSE !== $args['int']) {
			$return['int'] = TRUE;
			#$return['value'] = $return['value'];
			$return['value'] = intval($return['value']).'';

			unset($args['int']);
		}


		if(FALSE != $args['default']) {
			$return['value'] = PeepSo::get_option($name, $args['default']);
			unset($args['default']);
		}

		foreach( $args as $key=>$value ) {
			if( FALSE !== $value ) {
				$return[$key] = $value;
			}
		}

		$this->args = array();

		$this->fields[]=$return;
	}

	public function set_group($name, $title, $description='')
	{
		$default_args = array(
			'summary'	=> FALSE,
		);

		$args = array_merge($default_args, $this->args);
		$return = array(
			'name' 			=> $name,
			'title' 		=> $title,
			'context' 		=> $this->context,
			'description' 	=> $description,
			'fields' 		=> $this->fields,
		);

		if (FALSE !== $args['summary']) {
			$return['summary'] = $args['summary'];
		}

		$this->fields = array();
		$this->args 	= array();

		$this->config_groups[] = $return;
	}

	public function args($key, $value)
	{
		$this->args[$key]=$value;
	}

	// Builds the groups array
	abstract public function register_config_groups();

	/**
	 * Adds all fields of each group to the $this->form
	 */
	public function build_form()
	{
		foreach ($this->config_groups as &$config_group) {
			foreach ($config_group['fields'] as &$field) {
				$field = $this->form->add_field($field);
			}
		}
	}

	/**
	 * Returns all groups defined
	 * @return array
	 */
	public function get_groups()
	{
		return $this->groups;
	}

	/**
	 * Return a single group from the groups array
	 * @param  string $group Associative key of the group as defined in the groups array
	 * @return array        The group details
	 */
	public function get_group($group)
	{
		return $this->groups[$group];
	}
}
