<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2015, Phoronix Media
	Copyright (C) 2009 - 2015, Michael Larabel

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
	private $r;

	public function __construct(&$identifier, &$final, $raw = null, $json = null, $min_value = null, $max_value = null)
	{
		$this->r['identifier'] = $identifier;
		$this->r['result-final'] = $final;
		if($raw != null)
		{
			$this->r['result-raw'] = $raw;
		}
		if($min_value != null)
		{
			$this->r['result-min'] = $min_value;
		}
		if($max_value != null)
		{
			$this->r['result-max'] = $max_value;
		}
		if($json != null)
		{
			$this->r['result-json'] = $json;
		}
	}
	public function reset_result_identifier($identifier)
	{
		$this->r['identifier'] = $identifier;
	}
	public function reset_result_value($value)
	{
		$this->r['result-final'] = $value;
	}
	public function reset_raw_value($value)
	{
		if($value != null || $this->r['result-raw'] != null)
		{
			$this->r['result-raw'] = $value;
		}
	}
	public function get_result_identifier()
	{
		return $this->r['identifier'];
	}
	public function get_result_value()
	{
		return $this->r['result-final'];
	}
	public function get_min_result_value()
	{
		return isset($this->r['result-min']) ? $this->r['result-min'] : null;
	}
	public function get_max_result_value()
	{
		return isset($this->r['result-max']) ? $this->r['result-max'] : null;
	}
	public function get_result_raw()
	{
		return isset($this->r['result-raw']) ? $this->r['result-raw'] : null;
	}
	public function get_result_json()
	{
		if($this->r['result-json'] != null && !is_array($this->r['result-json']))
		{
			$this->r['result-json'] = json_decode($this->r['result-json'], true);
		}

		return $this->r['result-json'];
	}
	public function __toString()
	{
		return strtolower($this->get_result_identifier());
	}
	public static function compare_value($a, $b)
	{
		$a = $a->get_result_value();
		$b = $b->get_result_value();

		if($a == $b)
		{
			return 0;
		}

		return $a < $b ? -1 : 1;
	}
}

?>
