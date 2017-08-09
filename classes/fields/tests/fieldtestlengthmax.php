<?php

class PeepSoFieldTestLengthmax extends PeepSoFieldTestAbstract
{

	public function __construct($value, $args)
	{
		parent::__construct($value, $args);
		$this->admin_label 				= __('Maximum length', 'peepso-core');

		$this->admin_value				= 'int';
		$this->admin_value_label_after 	= __('character(s)', 'peepso-core');

		$this->message 					= __('Maximum length: %s', 'peepso-core');
	}

	public function test()
	{
		if( strlen($this->value) > $this->args) {

			$this->error = sprintf( $this->message, $this->args);

			return FALSE;
		}

		return TRUE;
	}
}