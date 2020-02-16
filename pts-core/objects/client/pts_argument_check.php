<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2018, Phoronix Media
	Copyright (C) 2009 - 2018, Michael Larabel

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

class pts_argument_check
{
	private $argument_index;
	private $function_check;
	private $function_return_key;

	public function __construct($index, $function, $return_key = null)
	{
		$this->argument_index = $index;
		$this->function_check = $function;
		$this->function_return_key = $return_key; // set to null when you don't want it to be set
	}
	public function get_argument_index()
	{
		return $this->argument_index;
	}
	public function get_function_check()
	{
		return $this->function_check;
	}
	public function get_function_return_key()
	{
		return $this->function_return_key;
	}
	public function get_function_check_type()
	{
		if($this->get_function_check() == array('pts_types', 'is_result_file'))
		{
			$type = 'Test Result';
		}
		else if($this->get_function_check() == array('pts_types', 'identifier_to_object'))
		{
			$type = 'Test | Suite | OpenBenchmarking ID | Test Result';
		}
		else if($this->get_function_check() == array('pts_types', 'is_test_or_suite'))
		{
			$type = 'Test | Suite';
		}
		else if($this->get_function_check() == array('pts_test_profile', 'is_test_profile'))
		{
			$type = 'Test';
		}
		else if($this->get_function_check() == array('pts_test_suite', 'is_suite'))
		{
			$type = 'Suite';
		}
		else if($this->get_function_check() == array('pts_openbenchmarking', 'is_openbenchmarking_result_id'))
		{
			$type = 'OpenBenchmarking ID';
		}
		else if($this->get_function_check() == array('pts_results', 'is_saved_result_file'))
		{
			$type = 'Test Result';
		}
		else if($this->get_function_check() == array('pts_module', 'is_module'))
		{
			$type = 'Phoronix Test Suite Module';
		}
		else if($this->get_function_check() == 'is_file')
		{
			$type = 'File';
		}
		else
		{
			$type = 'Unknown Object';
		}
		return $type;
	}
	public function __toString()
	{
		$type = $this->get_function_check_type();

		$type = '[' . $type . ']' . (($this->get_argument_index() === 'VARIABLE_LENGTH') ? '  ...' : null);

		return $type;
	}
	protected static function available_tests()
	{
		$ob_tests = pts_openbenchmarking::available_tests(false);
		$base_tests = array();

		foreach($ob_tests as $t)
		{
			$base_tests[] = substr($t, strpos($t, '/') + 1);
		}

		return array_merge($base_tests, $ob_tests);
	}
	public function possible_values()
	{
		$possible_values = array();

		if($this->get_function_check() == array('pts_types', 'is_result_file'))
		{
			$possible_values = pts_results::saved_test_results();
		}
		else if($this->get_function_check() == array('pts_types', 'identifier_to_object'))
		{
			$possible_values = array_merge(self::available_tests(), pts_results::saved_test_results(), pts_test_suites::all_suites(), array_keys(pts_openbenchmarking::result_uploads_from_this_ip()));
		}
		else if($this->get_function_check() == array('pts_types', 'is_test_or_suite'))
		{
			$possible_values = array_merge(self::available_tests(), pts_test_suites::all_suites());
		}
		else if($this->get_function_check() == array('pts_test_profile', 'is_test_profile'))
		{
			$possible_values = self::available_tests();
		}
		else if($this->get_function_check() == array('pts_test_suite', 'is_suite'))
		{
			$possible_values = pts_test_suites::all_suites();
		}
		else if($this->get_function_check() == array('pts_openbenchmarking', 'is_openbenchmarking_result_id'))
		{
			$possible_values = array_keys(pts_openbenchmarking::result_uploads_from_this_ip());
		}
		else if($this->get_function_check() == array('pts_results', 'is_saved_result_file'))
		{
			$possible_values = pts_results::saved_test_results();
		}
		else if($this->get_function_check() == array('pts_module', 'is_module'))
		{
			$possible_values = pts_module_manager::available_modules();
		}

		return $possible_values;
	}
}

?>
