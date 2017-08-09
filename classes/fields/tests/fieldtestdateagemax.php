<?php

class PeepSoFieldTestDateagemax extends PeepSoFieldTestAbstract
{
	public function test()
	{

		$now = date('U', current_time('timestamp', 0));
		$date = strtotime($this->value);

		$age = human_time_diff_round_alt($date, $now);

		// Date in the future needs to be negative
		if( $date > $now) {
			$age = 0 - $age;
		}

		if( $age > $this->args) {

			$this->error = sprintf( __('Maximum age: %s', 'peepso-core'), $this->args);

			return FALSE;
		}

		return TRUE;
	}
}