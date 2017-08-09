<?php

class PeepSoFieldTestPatternUrl extends PeepSoFieldTestAbstract
{

	public function __construct($value)
	{
		parent::__construct($value);

		$this->admin_label 	= __('Force valid http(s):// links', 'peepso-core');
		$this->admin_type 	= 'checkbox';

		$this->message 		= __('Must be a valid URL with http(s)://', 'peepso-core');
	}

	public function test()
	{

		if ( strlen($this->value) && FALSE === filter_var($this->value, FILTER_VALIDATE_URL) ) {

			$this->error = $this->message;

			return FALSE;
		}

		return TRUE;
	}

}