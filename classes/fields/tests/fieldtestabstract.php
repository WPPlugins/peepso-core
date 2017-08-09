<?php

abstract class PeepSoFieldTestAbstract
{
	protected $value 				= NULL;
	protected $args 				= NULL;

	public $error;

	public $admin_label;
	public $admin_label_after;
	public $admin_type				= 'checkbox';

	public $admin_value 			= NULL;
	public $admin_value_label 		= '';
	public $admin_value_label_after = '';

	public $message					= '';

	public function __construct( $value, $args = array() )
	{
		$this->value 	= $value;
		$this->args 	= $args;
	}

	abstract function test();
}