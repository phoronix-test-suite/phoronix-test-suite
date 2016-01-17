<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2016, Phoronix Media
	Copyright (C) 2008 - 2016, Michael Larabel

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

class pts_test_suite extends pts_test_suite_parser
{
	public function __construct($identifier)
	{
		parent::__construct($identifier);
	}
	public static function is_suite($identifier)
	{
		$identifier = pts_openbenchmarking::evaluate_string_to_qualifier($identifier, true, 'suite');
		return is_file(PTS_TEST_SUITE_PATH . $identifier . '/suite-definition.xml');
	}
	public function needs_updated_install()
	{
		foreach(pts_types::identifiers_to_test_profile_objects($this->get_identifier(), false, true) as $test_profile)
		{
			if($test_profile->test_installation == false || $test_profile->test_installation->get_installed_system_identifier() != phodevi::system_id_string())
			{
				return true;
			}
		}

		return false;
	}
	public function is_supported($report_warnings = false)
	{
		$supported_size = $original_size = count($this->get_contained_test_profiles());

		foreach(pts_types::identifiers_to_test_profile_objects($this->identifier, false, true) as $test_profile)
		{
			if($test_profile->is_supported($report_warnings) == false)
			{
				$supported_size--;
			}
		}

		if($supported_size == 0)
		{
			$return_code = 0;
		}
		else if($supported_size != $original_size)
		{
			$return_code = 1;
		}
		else
		{
			$return_code = 2;
		}

		return $return_code;
	}
	public function get_unique_test_count()
	{
		return count(pts_types::identifiers_to_test_profile_objects($this->identifier, false, true));
	}
	public function get_contained_test_result_objects()
	{
		$test_result_objects = array();
		$test_names = $this->xml_parser->getXMLArrayValues('PhoronixTestSuite/Execute/Test');
		$sub_modes = $this->xml_parser->getXMLArrayValues('PhoronixTestSuite/Execute/Mode');
		$sub_arguments = $this->xml_parser->getXMLArrayValues('PhoronixTestSuite/Execute/Arguments');
		$sub_arguments_description = $this->xml_parser->getXMLArrayValues('PhoronixTestSuite/Execute/Description');
		$override_test_options = $this->xml_parser->getXMLArrayValues('PhoronixTestSuite/Execute/OverrideTestOptions');

		for($i = 0; $i < count($test_names); $i++)
		{
			$obj = pts_types::identifier_to_object($test_names[$i]);

			if($obj instanceof pts_test_profile)
			{
				// Check for test profile values to override
				$override_options = array();
				if(!empty($override_test_options[$i]))
				{
					foreach(explode(';', $override_test_options[$i]) as $override_string)
					{
						$override_segments = pts_strings::trim_explode('=', $override_string);

						if(count($override_segments) == 2 && !empty($override_segments[0]) && !empty($override_segments[1]))
						{
							$override_options[$override_segments[0]] = $override_segments[1];
						}
					}
				}

				switch($sub_modes[$i])
				{
					case 'BATCH':
						$option_output = pts_test_run_options::batch_user_options($obj);
						break;
					case 'DEFAULTS':
						$option_output = pts_test_run_options::default_user_options($obj);
						break;
					default:
						$option_output = array(array($sub_arguments[$i]), array($sub_arguments_description[$i]));
						break;
				}

				foreach(array_keys($option_output[0]) as $x)
				{
					if($override_options != null)
					{
						$test_profile->set_override_values($override_options);
					}

					$test_result = new pts_test_result($obj);
					$test_result->set_used_arguments($option_output[0][$x]);
					$test_result->set_used_arguments_description($option_output[1][$x]);

					$test_result_objects[] = $test_result;
				}
			}
			else if($obj instanceof pts_test_suite)
			{
				foreach($obj->get_contained_test_result_objects() as $test_result)
				{
					$test_result_objects[] = $test_result;
				}
			}
		}

		return $test_result_objects;
	}
	public function pts_format_contained_tests_string()
	{
		$str = null;
		$this->pts_print_format_tests($this->identifier, $str);

		return $str;
	}
	public function is_core_version_supported()
	{
		// Check if the test suite's version is compatible with pts-core
		$core_version_min = parent::requires_core_version_min();
		$core_version_max = parent::requires_core_version_max();

		return $core_version_min <= PTS_CORE_VERSION && $core_version_max > PTS_CORE_VERSION;
	}
	public function pts_print_format_tests($object, &$write_buffer, $steps = -1)
	{
		// Print out a text tree that shows the suites and tests within an object
		$steps++;
		if(pts_test_suite::is_suite($object))
		{
			$xml_parser = new pts_suite_nye_XmlReader($object);
			$test_names = array_unique($xml_parser->getXMLArrayValues('PhoronixTestSuite/Execute/Test'));

			if($steps > 0)
			{
				asort($test_names);
			}

			if($steps == 0)
			{
				$write_buffer .= $object . PHP_EOL;
			}
			else
			{
				$write_buffer .= str_repeat('  ', $steps) . '+ ' . $object . PHP_EOL;
			}

			foreach($test_names as $test)
			{
				$write_buffer .= $this->pts_print_format_tests($test, $write_buffer, $steps);
			}
		}
		else
		{
			$write_buffer .= str_repeat('  ', $steps) . '* ' . $object . PHP_EOL;
		}
	}
	public static function pts_format_tests_to_array($object)
	{
		// Print out a text tree that shows the suites and tests within an object
		$contained = array();

		if(pts_test_suite::is_suite($object))
		{
			$xml_parser = new pts_suite_nye_XmlReader($object);
			$test_names = array_unique($xml_parser->getXMLArrayValues('PhoronixTestSuite/Execute/Test'));
			$contained[$object] = array();

			foreach($test_names as $test)
			{
				$contained[$object][] = self::pts_format_tests_to_array($test);
			}
		}
		else
		{
			$contained = $object;
		}

		return $contained;
	}
}

?>
