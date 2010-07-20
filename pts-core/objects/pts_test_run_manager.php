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
	private $tests_to_run;
	private $file_name;
	private $results_identifier;
	private $failed_tests_to_run;

	public function __construct()
	{
		$this->tests_to_run = array();
		$this->failed_tests_to_run = array();
		$this->file_name = null;
		$this->results_identifier = null;
	}
	public function add_individual_test_run($test_identifier, $arguments = "", $descriptions = "", $override_test_options = null)
	{
		$this_run_request = new pts_test_run_request($test_identifier, $arguments, $descriptions, $override_test_options);

		pts_arrays::unique_push($this->tests_to_run, $this_run_request);
	}
	public function add_single_test_run($test_identifier, $arguments, $descriptions, $override_test_options = null)
	{
		$arguments = pts_arrays::to_array($arguments);
		$descriptions = pts_arrays::to_array($descriptions);

		for($i = 0; $i < count($arguments); $i++)
		{
			$this->add_individual_test_run($test_identifier, $arguments[$i], $descriptions[$i], $override_test_options);
		}
	}
	public function add_multi_test_run($test_identifier, $arguments, $descriptions, $override_test_options = null)
	{
		$test_identifier = pts_arrays::to_array($test_identifier);
		$arguments = pts_arrays::to_array($arguments);
		$descriptions = pts_arrays::to_array($descriptions);
		$override_test_options = pts_arrays::to_array($override_test_options);

		for($i = 0; $i < count($test_identifier); $i++)
		{
			$this->add_individual_test_run($test_identifier[$i], $arguments[$i], $descriptions[$i], (isset($override_test_options[$i]) ? $override_test_options[$i] : null));
		}
	}
	protected function parse_override_test_options($override_test_options_string)
	{
		$override_options = array();

		if(!empty($override_test_options_string))
		{
			foreach(explode(";", $override_test_options_string) as $override_string)
			{
				$override_segments = pts_strings::trim_explode("=", $override_string);

				if(count($override_segments) == 2 && !empty($override_segments[0]) && !empty($override_segments[1]))
				{
					$override_options[$override_segments[0]] = $override_segments[1];
				}
			}
		}

		return $override_options;
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
				$override_options = $this->parse_override_test_options($override_test_options[$i]);

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
	public function set_tests_to_run($tests_to_run)
	{
		if(is_array($tests_to_run))
		{
			$this->tests_to_run = $tests_to_run;
		}
	}
	public function get_tests_to_run()
	{
		return $this->tests_to_run;
	}
	public function get_tests_to_run_identifiers()
	{
		$identifiers = array();

		foreach($this->tests_to_run as $test_run_request)
		{
			array_push($identifiers, $test_run_request->get_identifier());
		}

		array_unique($identifiers);

		return $identifiers;
	}
	public function get_tests_to_run_count()
	{
		return count($this->tests_to_run);
	}
	public function get_estimated_run_time_remaining($index = 0)
	{
		$est_time = 0;

		if(isset($this->tests_to_run[$index]))
		{
			for($i = $index; $i < count($this->tests_to_run); $i++)
			{
				$test_identifier = $this->tests_to_run[$i]->get_identifier(); // is a test_run_request
				$test_time = pts_estimated_run_time($test_identifier, true, false);

				$est_time += $test_time;
			}
		}

		return $est_time;
	}
	public function get_test_to_run($index)
	{
		return isset($this->tests_to_run[$index]) ? $this->tests_to_run[$index] : false;
	}
	public function get_test_count()
	{
		return count($this->get_tests_to_run());
	}
	public function set_file_name($file_name)
	{
		$this->file_name = $file_name;
	}
	public function set_results_identifier($results_identifier)
	{
		$this->results_identifier = $results_identifier;
	}
	public function get_file_name()
	{
		return $this->file_name;
	}
	public function get_results_identifier()
	{
		return $this->results_identifier;
	}
	public function add_failed_test_run_request($test_run_request)
	{
		if($test_run_request instanceOf pts_test_run_request)
		{
			array_push($this->failed_tests_to_run, $test_run_request);
		}
	}
	public function get_failed_test_run_requests()
	{
		return $this->failed_tests_to_run;
	}
}

?>
