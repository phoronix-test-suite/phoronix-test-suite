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

		pts_array_push($this->tests_to_run, $this_run_request);
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
	protected function parse_override_test_options($override_test_options_string)
	{
		$override_options = array();

		if(!empty($override_test_options_string))
		{
			foreach(explode(";", $override_test_options_string) as $override_string)
			{
				$override_segments = pts_trim_explode("=", $override_string);

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
		$is_weighted_suite = pts_is_weighted_suite($test_suite);

		if($is_weighted_suite)
		{
			echo "\nThe weighted suite option is currently EXPERIMENTAL.\n"; // TODO
			
			$weight_expressions = $xml_parser->getXMLArrayValues(P_SUITE_TEST_WEIGHT);

			$weighted_manager = new pts_weighted_test_run_manager();
			$weighted_manager->set_weight_suite_identifier($test_suite);
			$weighted_manager->set_weight_test_profile($xml_parser->getXMLValue(P_SUITE_WEIGHTED_BASE_FROM_TEST));
			$weighted_manager->set_weight_initial_value($xml_parser->getXMLValue(P_SUITE_WEIGHTED_INITIAL_VALUE));
			$weighted_manager->set_weight_final_expression($xml_parser->getXMLValue(P_SUITE_WEIGHTED_FINAL_WEIGHT_EXPRESSION));
		}

		for($i = 0; $i < count($tests_in_suite); $i++)
		{
			if(pts_is_test($tests_in_suite[$i]))
			{
				$override_options = $this->parse_override_test_options($override_test_options[$i]);

				if($is_weighted_suite)
				{
					// Currently weighted suites cannot be of BATCH or DEFAULTS sub-mode, but just a traditional test
					$weighted_run_request = new pts_weighted_test_run_request($tests_in_suite[$i], $sub_arguments[$i], $sub_arguments_description[$i], $override_options);
					$weighted_run_request->set_weight_expression($weight_expressions[$i]);

					echo "\nWeighted test run manager is temporarily disabled.\n";
					//array_push($weighted_manager->tests_to_run, $weighted_run_request);
					continue;
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
				if($is_weighted_suite)
				{
					$weighted_manager->add_suite_run($tests_in_suite[$i]);
				}
				else
				{
					$this->add_suite_run($tests_in_suite[$i]);
				}
			}
		}

		if($is_weighted_suite)
		{
			array_push($this->tests_to_run, $weighted_manager);
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
