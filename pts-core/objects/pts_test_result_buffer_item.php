<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2020, Phoronix Media
	Copyright (C) 2009 - 2020, Michael Larabel

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

class pts_test_result_buffer_item
{
	protected $result_identifier;
	protected $result_final;
	protected $result_raw;
	protected $result_json;
	protected $result_min;
	protected $result_max;

	public function __construct(&$identifier, &$final, $raw = null, $json = null, $min_value = null, $max_value = null)
	{
		$this->result_identifier = $identifier;
		$this->result_final = $final;
		$this->result_raw = $final != $raw ? $raw : null;
		$this->result_min = $min_value;
		$this->result_max = $max_value;
		$this->result_json = $json;
	}
	public function reset_result_identifier($identifier)
	{
		$this->result_identifier = $identifier;
	}
	public function reset_result_value($value, $set_precision = true)
	{
		if(is_numeric($this->result_final) && $set_precision && is_numeric($value))
		{
			$precision = pts_math::get_precision($this->result_final);
			$value = round($value, ($precision < 2 ? 2 : $precision));
		}

		$this->result_final = $value;
	}
	public function reset_raw_value($value)
	{
		$this->result_raw = $value;
	}
	public function get_result_identifier()
	{
		return $this->result_identifier;
	}
	public function get_result_identifier_simplified()
	{
		return pts_strings::simplify_string_for_file_handling($this->result_identifier);
	}
	public function get_result_value()
	{
		return $this->result_final;
	}
	public function get_min_result_value()
	{
		return $this->result_min;
	}
	public function get_max_result_value()
	{
		return $this->result_max;
	}
	public function get_result_raw()
	{
		return $this->result_raw;
	}
	public function get_result_raw_array()
	{
		return $this->result_raw != null ? explode(':', $this->result_raw) : array();
	}
	public function get_sample_count()
	{
		return count($this->get_result_raw_array());
	}
	public function get_result_json_raw()
	{
		return $this->result_json;
	}
	public function get_run_times()
	{
		$json_data = $this->get_result_json();
		return isset($json_data['test-run-times']) ? explode(':', $json_data['test-run-times']) : array();
	}
	public function get_run_time_total()
	{
		$times = $this->get_run_times();
		return count($times) > 0 ? array_sum($times) : -1;
	}
	public function get_result_json()
	{
		if($this->result_json != null && !is_array($this->result_json))
		{
			$this->result_json = json_decode($this->result_json, true);
		}

		return $this->result_json;
	}
	public function __toString()
	{
		return strtolower($this->get_result_identifier());
	}
	public static function compare_value($a, $b)
	{
		$a = $a->get_result_value();
		$b = $b->get_result_value();

		if(!is_array($a) && strpos($a, ',') != false && strpos($b, ',') != false)
		{
			$a = explode(',', $a);
			$b = explode(',', $b);
		}

		if(is_array($a))
		{
			$a = pts_math::arithmetic_mean($a);
		}
		if(is_array($b))
		{
			$b = pts_math::arithmetic_mean($b);
		}

		if($a == $b)
		{
			return 0;
		}

		return $a < $b ? -1 : 1;
	}
	public function get_error()
	{
		$json_data = $this->get_result_json();
		return isset($json_data['error']) ? $json_data['error'] : null;
	}
}

?>
