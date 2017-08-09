<?php

class PeepSoError
{
	const TABLE = 'peepso_errors';

	public function __construct($err_msg, $err_type='error', $err_extra='core', $override_file='', $override_line='')
	{
		if (!PeepSo::get_option('system_enable_logging')) {
			return (FALSE);
		}

		$trace = debug_backtrace();
		$caller = $trace[1];

		// Caller function
		$err_func = $caller['function'];
		if (!empty($caller['class'])) {
			$type = '->';
			if (isset($caller['type']) && !empty($caller['type']))
				$type = $caller['type'];
			$err_func = $caller['class'] . $type . $err_func;
		}

		// Caller file
		if(!array_key_exists('file', $caller)) {
			$caller['line']="n/a";
			$caller['file']='hook';
		}

		$code_file = str_replace('\\', '/', $caller['file']);
		$err_file = str_replace('\\', '/', plugin_dir_path(dirname(dirname(__FILE__)))); //), '', $code_file);
		$err_file = str_replace($err_file, '', $code_file);
		$line = $caller['line'];

		$err_file ="$err_file:$line";
		
		$err_type = strtolower($err_type);

		if( strlen($override_file) && strlen($override_line)) {;
			$err_file.= " ($override_file:$override_line)";
		}

		$data = array(
			'err_type' => $err_type,
			'err_extra' => $err_extra,
			'err_func' => $err_func,
			'err_file' => $err_file,
			'err_msg' => $err_msg,
			'err_user_id' => get_current_user_id(),
			'err_ip' => PeepSo::get_ip_address(),
		);

		global $wpdb;
		$wpdb->insert($wpdb->prefix . self::TABLE, $data);
	}
}

// EOF