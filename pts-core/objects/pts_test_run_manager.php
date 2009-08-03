<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009, Phoronix Media
	Copyright (C) 2009, Michael Larabel

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

class pts_test_run_manager
{
	var $tests_to_run;
	var $instance_name;

	public function __construct($name = null)
	{
		$this->tests_to_run = array();
		$this->instance_name = $name;
	}
	public function add_individual_test_run($test_identifier, $arguments = "", $descriptions = "", $override_test_options = null)
	{
		if(count($this->tests_to_run) == 0)
		{
			$this->instance_name = $test_identifier;
		}

		$this_run_request = new pts_test_run_request($test_identifier, $arguments, $descriptions, $override_test_options);

		if(!in_array($this_run_request, $this->tests_to_run))
		{
			array_push($this->tests_to_run, $this_run_request);
		}
	}
	public function add_single_test_run($test_identifier, $arguments, $descriptions, $override_test_options = null)
	{
		$arguments = pts_to_array($arguments);
		$descriptions = pts_to_array($descriptions);

		for($i = 0; $i < count($arguments); $i++)
		{
			$this->add_individual_test_run($test_identifier, $arguments[$i], $descriptions[$i], $override_test_options);
		}
	}
	public function add_multi_test_run($test_identifier, $arguments, $descriptions, $override_test_options = null)
	{
		$test_identifier = pts_to_array($test_identifier);
		$arguments = pts_to_array($arguments);
		$descriptions = pts_to_array($descriptions);
		$override_test_options = pts_to_array($override_test_options);

		for($i = 0; $i < count($test_identifier); $i++)
		{
			$this->add_individual_test_run($test_identifier[$i], $arguments[$i], $descriptions[$i], (isset($override_test_options[$i]) ? $override_test_options[$i] : null));
		}
	}
	public function add_suite_run($test_suite)
	{
		$xml_parser = new pts_suite_tandem_XmlReader($test_suite);
		$tests_in_suite = $xml_parser->getXMLArrayValues(P_SUITE_TEST_NAME);
		$sub_modes = $xml_parser->getXMLArrayValues(P_SUITE_TEST_MODE);
		$sub_arguments = $xml_parser->getXMLArrayValues(P_SUITE_TEST_ARGUMENTS);
		$sub_arguments_description = $xml_parser->getXMLArrayValues(P_SUITE_TEST_DESCRIPTION);
		$override_test_options = $xml_parser->getXMLArrayValues(P_SUITE_TEST_OVERRIDE_OPTIONS);

		for($i = 0; $i < count($tests_in_suite); $i++)
		{
			if(pts_is_test($tests_in_suite[$i]))
			{
				$override_options = array();
				if(!empty($override_test_options[$i]))
				{
					foreach(explode(";", $override_test_options[$i]) as $override_string)
					{
						$override_segments = array_map("trim", explode("=", $override_string));

						if(count($override_segments) == 2 && !empty($override_segments[0]) && !empty($override_segments[1]))
						{
							$override_options[$override_segments[0]] = $override_segments[1];
						}
					}
				}

				switch($sub_modes[$i])
				{
					case "BATCH":
						$option_output = pts_generate_batch_run_options($tests_in_suite[$i]);
						$this->add_single_test_run($tests_in_suite[$i], $option_output[0], $option_output[1], $override_options);
						break;
					case "DEFAULTS":
						$option_output = pts_defaults_test_options($tests_in_suite[$i]);
						$this->add_single_test_run($tests_in_suite[$i], $option_output[0], $option_output[1], $override_options);
						break;
					default:
						$this->add_individual_test_run($tests_in_suite[$i], $sub_arguments[$i], $sub_arguments_description[$i], $override_options);
						break;
				}
			}
			else if(pts_is_suite($tests_in_suite[$i]))
			{
				$this->add_suite_run($tests_in_suite[$i]);
			}
		}
	}
	public function get_tests_to_run()
	{
		return $this->tests_to_run;
	}
	public function get_instance_name()
	{
		return $this->instance_name;
	}
	public function get_test_count()
	{
		return count($this->get_tests_to_run());
	}
}

?>
