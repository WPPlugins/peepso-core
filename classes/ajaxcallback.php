<?php

abstract class PeepSoAjaxCallback
{
	protected static $_instances = array();
	protected $_input;
	protected $_request_method = 'get';

	protected function __construct()
	{
		if($_SERVER['REQUEST_METHOD'] === 'POST') {
			$this->_request_method = 'post';
		}
		$this->_input = new PeepSoInput();
	}

	/*
	 * return singleton instance
	 */
	public static function get_instance()
	{
		$class = get_called_class();
		if (!isset(self::$_instances[$class])) {
			self::$_instances[$class] = new $class();
		}
		return (self::$_instances[$class]);
	}
}

// EOF