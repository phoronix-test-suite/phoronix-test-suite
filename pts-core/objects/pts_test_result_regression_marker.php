<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2010, Phoronix Media
	Copyright (C) 2009 - 2010, Michael Larabel

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

class pts_test_result_regression_marker
{
	private $test_identifier;
	private $test_proportion;
	private $base_buffer_item;
	private $regressed_buffer_item;
	private $result_file_index;
	private $change;

	public function __construct(&$result_file_merge_test, $base_buffer_item, $regressed_buffer_item, $result_file_index = -1)
	{
		$this->test_identifier = $result_file_merge_test->test_profile->get_identifier();
		$this->test_proportion = $result_file_merge_test->test_profile->get_result_proportion();
		$this->base_buffer_item = $base_buffer_item;
		$this->regressed_buffer_item = $regressed_buffer_item;
		$this->result_file_index = $result_file_index;
		$this->change = pts_math::set_precision(abs(1 - ($regressed_buffer_item->get_result_value() / $base_buffer_item->get_result_value())), 4);
	}
	public function get_test_identifier()
	{
		return $this->test_identifier;
	}
	public function get_result_file_index()
	{
		return $this->result_file_index;
	}
	public function get_base_identifier()
	{
		return $this->base_buffer_item->get_result_identifier();
	}
	public function get_base_value()
	{
		return $this->base_buffer_item->get_result_value();
	}
	public function get_regressed_identifier()
	{
		return $this->regressed_buffer_item->get_result_identifier();
	}
	public function get_regressed_value()
	{
		return $this->regressed_buffer_item->get_result_value();
	}
	public function get_change()
	{
		return $this->change;
	}
	public function get_change_formatted()
	{
		$direction = '-';

		if($this->test_proportion == 'HIB' && $this->get_regressed_value() > $this->get_base_value())
		{
			$direction = '+';
		}
		else if($this->test_proportion == 'LIB' && $this->get_regressed_value() < $this->get_base_value())
		{
			$direction = '+';
		}

		return $direction . pts_math::set_precision($this->get_change() * 100, 2);
	}
}

?>
